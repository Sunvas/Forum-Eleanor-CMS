<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

class ForumSubscriptions extends Forum
{
	/**
	 * Подписка на тему / отписка от темы. Возможность доступа в тему не проверяется.
	 * @param int|array $tid ID темы
	 * @param int $uid ID пользователя
	 * @param string $intensity Тип подписки: i (immediately) - немедленная подписка, d (daily) - ежедневная, w (weekly) - еженедельная, m (monthly) - ежемесячная, y (yearly) - ежегодная
	 * @param int $status Статус темы
	 */
	public function SubscribeTopic($tid,$uid,$intensity=0,$status=1)
	{
		if(in_array($intensity,array('i','d','w','m','y')))
		{
			switch($intensity)
			{
				case'd':
					$interval='+INTERVAL 1 DAY';
				break;
				case'w':
					$interval='+INTERVAL 1 WEEK';
				break;
				case'm':
					$interval='+INTERVAL 1 MONTH';
				break;
				case'y':
					$interval='+INTERVAL 1 YEAR';
				break;
				default:
					$interval='';
			}
			$insert=array('uid'=>$uid,'status'=>$status,'!lastview'=>'NOW()','intensity'=>$intensity,'!nextsend'=>'NOW()'.$interval);
			if(is_array($tid))
			{
				$cnt=count($tid);
				foreach($insert as &$v)
					$v=array_fill(0,$cnt,$v);
			}
			$insert['t']=$tid;
			Eleanor::$Db->Query('INSERT INTO `'.$this->Forum->config['ts'].'`'.Eleanor::$Db->GenerateInsert($insert).' ON DUPLICATE KEY UPDATE `intensity`=\''.$intensity.'\', `nextsend`=NOW()'.$interval);
		}
		else
			Eleanor::$Db->Delete($this->Forum->config['ts'],'`uid`='.$uid.' AND `t`'.Eleanor::$Db->In($tid).' LIMIT 1');
	}

	/**
	 * Подписка на форум / отписка от форума. Возможность доступа не проверяется
	 * @param int|array $fid ID форума
	 * @param string $lang язык форума
	 * @param int $uid ID пользователя
	 * @param string $intensity Тип подписки: i (immediately) - немедленная подписка, d (daily) - ежедневная, w (weekly) - еженедельная, m (monthly) - ежемесячная, y (yearly) - ежегодная
	 */
	public function SubscribeForum($fid,$lang,$uid,$intensity=0)
	{
		if(in_array($intensity,array('i','d','w','m','y')))
		{
			switch($intensity)
			{
				case'd':
					$interval='+INTERVAL 1 DAY';
				break;
				case'w':
					$interval='+INTERVAL 1 WEEK';
				break;
				case'm':
					$interval='+INTERVAL 1 MONTH';
				break;
				case'y':
					$interval='+INTERVAL 1 YEAR';
				break;
				default:
					$interval='';
			}
			$insert=array('uid'=>$uid,'language'=>$lang,'!lastview'=>'NOW()','intensity'=>$intensity,'!nextsend'=>'NOW()'.$interval);
			if(is_array($fid))
			{
				$cnt=count($fid);
				foreach($insert as &$v)
					$v=array_fill(0,$cnt,$v);
			}
			$insert['f']=$fid;
			Eleanor::$Db->Query('INSERT INTO `'.$this->Forum->config['fs'].'`'.Eleanor::$Db->GenerateInsert($insert).' ON DUPLICATE KEY UPDATE `intensity`=\''.$intensity.'\', `nextsend`=NOW()'.$interval);
		}
		else
			Eleanor::$Db->Delete($this->Forum->config['fs'],'`uid`='.$uid.' AND `f`'.Eleanor::$Db->In($fid).' AND `language`=\''.Eleanor::$Db->Escape($lang).'\' LIMIT 1');
	}

	/**
	 * Отправка подписок по форумам. Учитываются ТОЛЬКО активные темы со status=1. А что делать, если на форум подписан
	 * модератор, который имеет доступ к скрытым и неактивированным темам? Вряд-ли стоит делать и для него отправку
	 * подписки. Ему скорее нужно делать уведомление о новой теме. Оставим эту задачу на будущее. ToDo?
	 * @param int|array $fid ID форумов, с которых нужно обработать подписки
	 * @param int $limit Предел потенциально отправляемых писем
	 * @return bool Отправка завершена? true - да, false - нет (нужны последующие вызовы этого метода)
	 */
	public function SendForums($fid=array(),$limit=100)
	{
		$chtz='';#Change TimeZone
		$oldlang=$this->Core->language;#Old language
		$cache=$langs=array();#Текстовый кэш первых постов тем, массив языков
		$sitelink=PROTOCOL.Eleanor::$punycode.Eleanor::$site_path;
		$Eleanor=Eleanor::getInstance();

		$config = $this->Forum->config;
		$R=Eleanor::$Db->Query('SELECT `s`.`f`,`s`.`uid`,`s`.`language`,`s`.`lastview`,`s`.`lastsend`,`s`.`intensity`,`u`.`email`,`u`.`groups`,`u`.`name`,`u`.`language` `ulanguage`,`u`.`timezone` FROM `'. $config['fs'].'` `s` INNER JOIN `'. $config['fl'].'` `fl` ON `s`.`f`=`fl`.`id` AND `s`.`language`=`fl`.`language` INNER JOIN `'.P.'users_site` `u` ON `u`.`id`=`s`.`uid` WHERE `fl`.`lp_date`>`s`.`lastview` AND `s`.`sent`=0 AND `s`.`nextsend`<=\''.date('Y-m-d H:i:s').'\''.($fid ? '`s`.`f`'.Eleanor::$Db->In($fid) : '').' ORDER BY `s`.`language` ASC LIMIT '.$limit);
		while($a=$R->fetch_assoc())
		{
			$a['groups']=$a['groups'] ? explode(',,',trim($a['groups'],',')) : array();

			#Проверим, есть ли у нас доступ ко всем темам, а не только к своим
			$rta=array();#Right All Topic
			foreach($a['groups'] as $g)
			{
				$r=$this->Core->GroupPerms($a['f'],$g);
				$rta[]=$r['atopics'];
			}

			if($this->Core->CheckForumAccess($a['f'],$a['groups']) and in_array(1,$rta))
			{
				$lang=$a['language'] ? $a['language'] : LANGUAGE;
				$ulang=$a['ulanguage'] ? $a['ulanguage'] : LANGUAGE;
				if(!isset($langs[$ulang]))
					$langs[$ulang]=include __DIR__.'/letters-'.$ulang.'.php';

				#В дальнейшем мы будем генерить ссылки на форумы
				if($oldlang!=$lang)
				{
					$this->Forums->ReDump($lang);
					$luri=$lang==LANGUAGE ? false : Eleanor::$langs[ $a['language'] ]['uri'];
					$Eleanor->Url->SetPrefix(array('lang'=>$luri,'module'=>Eleanor::$vars['prefix_free_module']==$Eleanor->module['id'] ? false : $Eleanor->module['name']),false);

					#Костыль ради UserLink
					$Eleanor->Url->special=$luri ? $Eleanor->Url->Construct(array('lang'=>$luri),false,false) : '';

					$oldlang=$lang;
				}

				if($a['timezone']!=$chtz)
				{
					date_default_timezone_set($a['timezone'] ? $a['timezone'] : Eleanor::$vars['time_zone']);
					Eleanor::$Db->SyncTimeZone();
					$chtz=$a['timezone'];
				}

				if($a['intensity']=='i')
				{
					$R2=Eleanor::$Db->Query('SELECT `id`,`uri`,`title`,`author`,`author_id`,`created` FROM `'. $config['ft'].'` WHERE `f`='.$a['f'].' AND `language`=\''.$a['language'].'\' AND `status`=1 AND `created`>\''.$a['lastview'].'\' AND `author_id`!='.$a['uid'].' ORDER BY `created` ASC LIMIT 1');
					if($topics=$R2->fetch_assoc())
					{
						if(!isset($cache[ $topics['id'] ]))
						{
							$R3=Eleanor::$Db->Query('SELECT `text` FROM `'. $config['fp'].'` WHERE `t`='.$topics['id'].' AND `status`=1 ORDER BY `sortdate` ASC LIMIT 1');
							if(!list($cache[$topics['id']])=$R3->fetch_row())
									continue;
							$cache[$topics['id']]=Strings::CutStr(strip_tags(OwnBB::Parse($cache[ $topics['id'] ]),'<br><b><i><span><a>'),500);
						}
						$repl=array(
							'site'=>Eleanor::$vars['site_name'],
							'sitelink'=>$sitelink,
							'topiclink'=>$sitelink.$this->Links->Topic($a['f'],$topics['id'],$topics['uri']),
							'forumlink'=>$sitelink.$this->Links->Forum($a['f']),
							'authorlink'=>$sitelink.Eleanor::$Login->UserLink($topics['author'],$topics['author_id']),
							'cflink'=>$this->Links->Forum($a['f'],array('fi'=>array('cf'=>$a['lastview']))),
							'author'=>htmlspecialchars($topics['author'],ELENT,CHARSET),
							'created'=>$ulang::Date($topics['created'],'fdt'),
							'lastview'=>$ulang::Date($a['lastview'],'fdt'),
							'lastsend'=>$ulang::Date($a['lastsend'],'fdt'),
							'forum'=>$this->Forums->dump[ $a['f'] ]['title'],
							'title'=>$topics['title'],
							'text'=>$cache[ $topics['id'] ],
							'name'=>htmlspecialchars($a['name'],ELENT,CHARSET),
							'cancel'=>$sitelink.$this->Links->Action('subscribe-forum',$a['f'],array('set'=>0)),
						);
						Email::Simple(
							$a['email'],
							Eleanor::ExecBBLogic($langs[$ulang]['subsfi_t'],$repl),
							Eleanor::ExecBBLogic($langs[$ulang]['subsfi'],$repl)
						);
					}
				}
				else
				{
					$R2=Eleanor::$Db->Query('SELECT COUNT(`f`) `cnt` FROM `'. $config['ft'].'` WHERE `f`='.$a['f'].' AND `status`=1 AND `language`=\''.$a['language'].'\' AND `created`>=\''.$a['lastsend'].'\'');
					if($topics=$R2->fetch_assoc())
					{
						$repl=array(
							'site'=>Eleanor::$vars['site_name'],
							'sitelink'=>$sitelink,
							'forumlink'=>$sitelink.$this->Links->Forum($a['f']),
							'cflink'=>$this->Links->Forum($a['f'],array('fi'=>array('cf'=>$a['lastsend']))),
							'cnt'=>$topics['cnt'],
							'lastview'=>$ulang::Date($a['lastview'],'fdt'),
							'lastsend'=>$ulang::Date($a['lastsend'],'fdt'),
							'forum'=>$this->Forums->dump[ $a['f'] ]['title'],
							'title'=>$topics['title'],
							'name'=>htmlspecialchars($a['name'],ELENT,CHARSET),
							'cancel'=>$sitelink.$this->Links->Action('subscribe-forum',$a['f'],array('set'=>0)),
						);
						Email::Simple(
							$a['email'],
							Eleanor::ExecBbLogic($langs[$ulang]['subsf_t'],$repl),
							Eleanor::ExecBbLogic($langs[$ulang]['subsf'],$repl)
						);
					}
				}
				$limit--;
				switch($a['intensity'])
				{
					case'd':
						$pl='+INTERVAL 1 DAY';
					break;
					case'w':
						$pl='+INTERVAL 1 WEEK';
					break;
					case'm':
						$pl='+INTERVAL 1 MONTH';
					break;
					case'y':
						$pl='+INTERVAL 1 YEAR';
					break;
					default:
						$pl='';
				}
				Eleanor::$Db->Update($config['fs'],array('sent'=>1,'!lastsend'=>'NOW()','!nextsend'=>'NOW()'.$pl),'`f`='.$a['f'].' AND `uid`='.$a['uid'].' AND `language`=\''.$a['language'].'\' LIMIT 1');
			}
			else
				Eleanor::$Db->Delete($config['fs'],'`f`='.$a['f'].' AND `uid`='.$a['uid'].' AND `language`=\''.$a['language'].'\'');
		}

		if($chtz)
		{
			date_default_timezone_set(Eleanor::$vars['time_zone']);
			Eleanor::$Db->SyncTimeZone();
		}

		if($oldlang!=$this->Core->language)
			$this->Forums->ReDump($this->Core->language);

		return $limit>0;
	}

	/**
	 * Отправка подписок по темам
	 * @param array $tid ID тем, по которым нужно обработать подписки
	 * @param int $limit Предел потенциально отправляемых писем
	 * @return bool Отправка завершена? true - да, false - нет (нужны последующие вызовы этого метода)
	 */
	public function SendTopics($tid=array(),$limit=100)
	{
		$chtz='';#Change TimeZone
		$oldlang=$this->Core->language;#Old language
		$cache=$langs=array();#Текстовый кэш первых постов тем, массив языков
		$sitelink=PROTOCOL.Eleanor::$punycode.Eleanor::$site_path;
		$Eleanor=Eleanor::getInstance();

		$config = $this->Forum->config;
		$R=Eleanor::$Db->Query('SELECT `s`.`t`,`s`.`uid`,`s`.`lastview`,`s`.`lastsend`,`s`.`intensity`,`u`.`email`,`u`.`groups`,`u`.`name`,`u`.`language` `ulanguage`,`u`.`timezone`,`ft`.`f`,`ft`.`language`,`ft`.`uri`,`ft`.`status`,`ft`.`author_id`,`ft`.`title` FROM `'. $config['ts'].'` `s` INNER JOIN `'. $config['ft'].'` `ft` ON `s`.`t`=`ft`.`id` INNER JOIN `'.P.'users_site` `u` ON `u`.`id`=`s`.`uid` WHERE `ft`.`lp_date`>`s`.`lastview` AND `s`.`status`=1 AND `s`.`sent`=0 AND `s`.`nextsend`<=\''.date('Y-m-d H:i:s').'\''.($tid ? '`s`.`t`'.Eleanor::$Db->In($tid) : '').' ORDER BY `ft`.`language` ASC LIMIT '.$limit);
		while($a=$R->fetch_assoc())
		{
			$a['groups']=$a['groups'] ? explode(',,',trim($a['groups'],',')) : array();

			if($this->Core->CheckTopicAccess(array('author_id'=>$a['author_id'],'id'=>$a['t'],'f'=>$a['f']),array('id'=>$a['uid'],'groups'=>$a['groups'])))
			{
				$lang=$a['language'] ? $a['language'] : LANGUAGE;
				$ulang=$a['ulanguage'] ? $a['ulanguage'] : LANGUAGE;
				if(!isset($langs[$ulang]))
					$langs[$ulang]=include __DIR__.'/letters-'.$ulang.'.php';

				#В дальнейшем мы будем генерить ссылки на форумы
				if($oldlang!=$lang)
				{
					$this->Forums->ReDump($lang);
					$luri=$lang==LANGUAGE ? false : Eleanor::$langs[ $a['language'] ]['uri'];
					$Eleanor->Url->SetPrefix(array('lang'=>$luri,'module'=>Eleanor::$vars['prefix_free_module']==$Eleanor->module['id'] ? false : $Eleanor->module['name']),false);

					#Костыль ради UserLink
					$Eleanor->Url->special=$luri ? $Eleanor->Url->Construct(array('lang'=>$luri),false,false) : '';

					$oldlang=$lang;
				}

				if($a['timezone']!=$chtz)
				{
					date_default_timezone_set($a['timezone'] ? $a['timezone'] : Eleanor::$vars['time_zone']);
					Eleanor::$Db->SyncTimeZone();
					$chtz=$a['timezone'];
				}

				$R2=Eleanor::$Db->Query('SELECT `id`,`author`,`author_id`,`created`,`text` FROM `'. $config['fp'].'` WHERE `t`='.$a['t'].' AND `status`=1 AND `sortdate`>\''.$a['lastview'].'\' AND `author_id`!='.$a['uid'].' ORDER BY `sortdate` ASC LIMIT 1');
				if($post=$R2->fetch_assoc())
					if($a['intensity']=='i')
					{
						if(!isset($cache[ $post['id'] ]))
							$cache[ $post['id'] ]=Strings::CutStr(strip_tags(OwnBB::Parse($post['text']),'<br><b><i><span><a>'),500);
						$repl=array(
							'site'=>Eleanor::$vars['site_name'],
							'sitelink'=>$sitelink,
							'topiclink'=>$sitelink.$this->Links->Topic($a['f'],$a['t'],$a['uri']),
							'forumlink'=>$sitelink.$this->Links->Forum($a['f']),
							'postlink'=>$sitelink.$this->Links->Action('find-post',$post['id']),
							'author'=>htmlspecialchars($post['author'],ELENT,CHARSET),
							'authorlink'=>$sitelink.Eleanor::$Login->UserLink($post['author'],$post['author_id']),
							'created'=>$ulang::Date($post['created'],'fdt'),
							'lastview'=>$ulang::Date($a['lastview'],'fdt'),
							'lastsend'=>$ulang::Date($a['lastsend'],'fdt'),
							'forum'=>$this->Forums->dump[ $a['f'] ]['title'],
							'title'=>$a['title'],
							'text'=>$cache[ $post['id'] ],
							'name'=>htmlspecialchars($a['name'],ELENT,CHARSET),
							'cancel'=>$sitelink.$this->Links->Action('subscribe-topic',$a['t'],array('set'=>0)),
						);
						Email::Simple(
							$a['email'],
							Eleanor::ExecBbLogic($langs[$lang]['substi_t'],$repl),
							Eleanor::ExecBbLogic($langs[$lang]['substi'],$repl)
						);
					}
					else
					{
						$R2=Eleanor::$Db->Query('SELECT COUNT(`t`) `cnt` FROM `'. $config['fp'].'` WHERE `t`='.$a['t'].' AND `status`=1 AND `sortdate`>FROM_UNIXTIME(\''.$a['lastsend'].'\')');
						if($posts=$R2->fetch_assoc())
						{
							$repl=array(
								'site'=>Eleanor::$vars['site_name'],
								'sitelink'=>$sitelink,
								'topiclink'=>$sitelink.$this->Links->Topic($a['f'],$a['t'],$a['uri']),
								'forumlink'=>$sitelink.$this->Links->Forum($a['f']),
								'forum'=>$this->Forums->dump[ $a['f'] ]['title'],
								'cnt'=>$posts['cnt'],
								'lastview'=>$ulang::Date($a['lastview'],'fdt'),
								'lastsend'=>$ulang::Date($a['lastsend'],'fdt'),
								'name'=>htmlspecialchars($a['name'],ELENT,CHARSET),
								'title'=>$a['title'],
								'cancel'=>$sitelink.$this->Links->Action('subscribe-topic',$a['t'],array('set'=>0)),
							);
							Email::Simple(
								$a['email'],
								Eleanor::ExecBbLogic($langs[$lang]['subst_t'],$repl),
								Eleanor::ExecBbLogic($langs[$lang]['subst'],$repl)
							);
						}
					}
				switch($a['intensity'])
				{
					case'd':
						$pl='+ INTERVAL 1 DAY';
					break;
					case'w':
						$pl='+ INTERVAL 1 WEEK';
					break;
					case'm':
						$pl='+ INTERVAL 1 MONTH';
					break;
					case'y':
						$pl='+ INTERVAL 1 YEAR';
					break;
					default:
						$pl='';
				}
				Eleanor::$Db->Update($config['ts'],array('sent'=>1,'!lastsend'=>'NOW()','!nextsend'=>'NOW()'.$pl),'`t`='.$a['t'].' AND `uid`='.$a['uid'].' LIMIT 1');
			}
			else
				Eleanor::$Db->Delete($config['ts'],'`f`='.$a['f'].' AND `uid`='.$a['uid']);
		}

		if($chtz)
		{
			date_default_timezone_set(Eleanor::$vars['time_zone']);
			Eleanor::$Db->SyncTimeZone();
		}

		if($oldlang!=$this->Core->language)
			$this->Forums->ReDump($this->Core->language);

		return $limit>0;
	}
}