<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

class ForumPost extends Forum
{
	/**
	 * Получение форума и прав с точки зрения поста (создания сообщения)
	 * @param int $f ID форума
	 * @return array|false Возвращается FALSE в случае, если форум - категория либо к нему нет права доступа, либо
	 */
	public function GetForum($f)
	{
		$forum=$this->Forums->GetForum($f);
		if(!$forum)
			return false;
		$rights=$this->Core->ForumRights($forum['id']);

		#Если форум категория, к нему нет доступа или он является корзиной - публиковаться здесь нельзя
		if($forum['is_category'] or !in_array(1,$rights['access']) or $this->Core->vars['trash']==$forum['id'])
			return false;

		#Если нам запрещено публиковать посты
		if($this->Core->user and $this->Core->user['restrict_post'])
			return false;

		#В этой переменной хранятся все модераторы форума + наши права, как модератора
		if($forum['moderators'])
			list($forum['moderators'],$forum['_moderator'])=$this->Moderator->ByIds($forum['moderators'],array('movet','move','delete','deletet','edit','editt','chstatust','chstatus','mchstatus','pin','opcl'),$this->Forum->config['n'].'_moders_p'.$forum['id']);
			#mchstatus нужен для возможности цитирования неактивных сообщений
		else
			$forum['moderators']=$forum['_moderator']=array();

		return array($forum,$rights);
	}

	/**
	 * Определение возможности публиковать посты: проверка флуда, возможно, неблагоприятных IP или времени
	 * @return array Массив всех ошибок
	 */
	public function Possibility()
	{
		$errors=array();
		$TC=new TimeCheck;
		if($ch=$TC->Check($this->Forum->config['n'],false))
			$errors['FLOOD_WAIT']=$ch['_datets']-time();
		return$errors;
	}

	#ToDo!
	public function AddFloodLimit()
	{
		if($fl=Eleanor::$Permissions->FloodLimit())
		{
			$TC=new TimeCheck;
			$TC->Add($this->Forum->config['n'],1,true,$fl);
		}
	}

	/**
	 * Получение прав по взаимодействию с конкретным постом с точки зрения пользователя. Спецификой данного метода
	 * является определение тех прав, который нельзя проверить банальным in_array('value',$rights).
	 * @param array $post Дамп поста, массив с ключами:
	 *  int author_id ID автора поста
	 *  int id ID поста
	 *  string created Дата создания темы
	 * @param array $rights Права пользователя на форуме, массив с ключами:
	 *  array of boolean edit Возможность правки поста
	 *  array of boolean delete Возможность удаления поста
	 *  array of int Временное ограничение (число секунд с момента создания поста) когда можно править / удалять пост
	 * @param $moder Права модератора на форуме, массив с ключами:
	 *  array of boolean edit Возможность правки постов
	 *  array of boolean delete Возможность удаления постов
	 * @return array:
	 *  bool edit Возможность править пост
	 *  bool delete Возможность удалить пост
	 */
	public function Rights(array$post,array$rights,array$moder=array())
	{
		$Forum=$this->Core;
		$r['edit']=$r['delete']=$Forum->ugr['supermod'];
		if(!$r['edit'])
		{
			$my=$Forum->user ? $post['author_id']==$Forum->user['id'] : in_array($post['id'],$Forum->GuestSign('p'));

			if($my and $post['status']!=0 and (in_array(0,$rights['editlimit']) or time()-strtotime($post['created'])<=max($rights['editlimit'])))
			{
				$r['edit']=in_array(1,$rights['edit']);
				$r['delete']=in_array(1,$rights['delete']);
			}

			if($moder)
			{
				$r['edit']|=in_array(1,$moder['edit']);
				$r['delete']|=in_array(1,$moder['delete']);
			}
		}
		return$r;
	}

	/**
	 * Непосредственная публикация / правка поста в теме. Никаких проверок не осуществляется.
	 */
	public function Post()
	{
		#ToDo! Необходимо учесть возможность смены автора, статуса сообщения
	}

	/**
	 * Непосредственное создание / правка темы на форуме. Никаких проверок не осуществляется.
	 * @param array $values Данные для создания/правки темы
	 * @param array $topic Начальные значения темы при создании
	 * @param array $post Начальные значения поста при создании
	 */
	public function Topic(array$values,array$topic=array(),array$post=array())
	{
		$config=$this->Forum->config;

		if(isset($values['id']))
		{
			#ToDo! Необходимо учесть возможность смены автора, статуса темы
			#Topic
/*`uri` varchar(50) default NULL,
`f` smallint unsigned NOT NULL,
`prefix` smallint(5) unsigned NOT NULL,
`status` tinyint NOT NULL,
`language` enum('russian','english','ukrainian') NOT NULL,
`lrelated` varchar(30) NOT NULL,
`created` timestamp NOT NULL default '0000-00-00 00:00:00',
`author` varchar(25) NOT NULL,
`author_id` mediumint unsigned default NULL,
`state` enum('moved','closed','open','merged') NOT NULL default 'open',
`moved_to` mediumint unsigned NOT NULL,
`moved_to_forum` mediumint unsigned NOT NULL,
`who_moved` varchar(25) NOT NULL,
`who_moved_id` mediumint unsigned NOT NULL,
`when_moved` timestamp NOT NULL default '0000-00-00 00:00:00',
`trash` mediumint unsigned NOT NULL,
`title` tinytext NOT NULL,
`description` tinytext NOT NULL,
`posts` mediumint unsigned NOT NULL,
`queued_posts` mediumint unsigned NOT NULL,
`views` mediumint unsigned NOT NULL,
`sortdate` timestamp NOT NULL default '0000-00-00 00:00:00',
`pinned` timestamp NOT NULL default '0000-00-00 00:00:00',
`lp_date` timestamp NOT NULL default '0000-00-00 00:00:00',
`lp_id` mediumint unsigned NOT NULL,
`lp_author` varchar(25) NOT NULL,
`lp_author_id` mediumint unsigned default NULL,
`voting` tinyint NOT NULL,
`last_mod` timestamp NOT NULL default '0000-00-00 00:00:00',*/

			#Post
/*`id` mediumint unsigned NOT NULL auto_increment,
`f` smallint unsigned NOT NULL,
`language` enum('russian','english','ukrainian') NOT NULL,
`t` mediumint unsigned NOT NULL,
`status` tinyint NOT NULL default '1',
`author` varchar(25) NOT NULL,
`author_id` mediumint unsigned default NULL,
`ip` varchar(39) NOT NULL,
`created` timestamp NOT NULL default '0000-00-00 00:00:00',
`sortdate` timestamp NOT NULL default '0000-00-00 00:00:00',
`who_edit` varchar(25) NOT NULL,
`who_edit_id` mediumint unsigned default NULL,
`edit_date` timestamp NOT NULL default '0000-00-00 00:00:00',
`edit_reason` tinytext NOT NULL,
`text` mediumtext NOT NULL,
`last_mod` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,*/
		}
		elseif(isset($values['f'],$values['title'],$values['text']))
		{
			$created=isset($values['created']) ? $values['created'] : date('Y-m-d H:i:s');
			$forum=$this->Forums->GetForum($values['f']);
			$topic+=array(
				'f'=>$values['f'],
				'prefix'=>isset($values['prefix']) ? $values['prefix'] : 0,
				'status'=>isset($values['status']) ? $values['status'] : 1,
				'created'=>$created,
				'state'=>isset($values['state']) ? $values['state'] : 'open',
				'sortdate'=>isset($values['pinned']) ? $values['pinned'] : $created,
				'pinned'=>isset($values['pinned']) ? $values['pinned'] : '0000-00-00 00:00:00',
				'lp_date'=>$created,
				'lp_author'=>$values['author'],
				'lp_author_id'=>isset($values['author_id']) ? $values['author_id'] : null,
				'voting'=>isset($values['voting']) ? $values['voting'] : 0,
				'last_mod'=>$created,
			);

			#Авторство темы
			if(!isset($topic['author']) or !array_key_exists('author_id',$topic))
			{
				if(isset($values['author']) and array_key_exists('author_id',$values))
					$topic['author']=$values['author'];
				else
				{
					$me=$this->Forum->user ? Eleanor::$Login->GetUserValue(array('name','id')) : array('name'=>'Guest','id'=>null);
					$topic+=array(
						'author'=>$me['name'],
						'author_id'=>$me['id'],
					);
				}
			}

			if(isset($values['status']) and $values['status']!=1)
				$status=$values['status']==-1 ? -3 : -2;
			else
				$status=1;

			$post+=array(
				'f'=>$values['f'],
				'status'=>$status,
				'author'=>$topic['author'],
				'author_id'=>isset($topic['author_id']) ? $values['author_id'] : null,
				'ip'=>isset($values['ip']) ? $values['ip'] : Eleanor::$ip,
				'created'=>$created,
				'sortdate'=>$created,
				'last_mod'=>$created,
			);

			if(is_array($values['title']))
			{
				$result=array();
				foreach($values['title'] as $lang=>$title)
					$result[$lang]=static::NewTopic(
						$topic+array(
							'uri'=>isset($values['uri'][$lang]) ? $values['uri'][$lang] : '',
							'title'=>$title,
							'description'=>isset($values['description'][$lang]) ? $values['description'][$lang] : '',
							'language'=>$lang,
						),
						$post+array(
							'text'=>$values['text'][$lang],
							'language'=>$lang,
						),
						isset($values['files'][$lang]) ? $values['files'][$lang] : false,
						$forum
					);

				foreach($result as $lang1=>$t1)
				{
					$related=array();
					foreach($result as $lang2=>$t2)
						if($lang1!=$lang2)
							$related[]=$t2['t'];
					if($related)
					{
						sort($related,SORT_NUMERIC);
						Eleanor::$Db->Update($config['ft'],array('lrelated'=>','.join(',,',$related).','),'`id`='.$t1['t'].' LIMIT 1');
					}
				}

				$cnt=count($values['title']);
				$topic=$result;
			}
			elseif(isset($values['language']))
			{
				$topic=static::NewTopic(
					$topic+array(
						'uri'=>isset($values['uri']) ? $values['uri'] : '',
						'title'=>$values['title'],
						'description'=>isset($values['description']) ? $values['description'] : '',
						'language'=>$values['language'],
					),
					$post+array(
						'text'=>$values['text'],
						'language'=>$values['language'],
					),
					isset($values['files']) ? $values['files'] : false,
					$forum
				);
				$cnt=1;
			}
			else
				throw new EE('MISSING_LANGUAGE',EE::USER);

			if($cnt>0 and $forum['inc_posts'] and $post['status']==1 and $post['author_id'])
				$this->IncUserPosts($cnt,$post['author_id']);

			return$topic;
		}
		else
			throw new EE('MISSING_ARGUMENTS',EE::USER);
	}

	/**
	 * Непосредственное создание темы
	 * @param array $topic Данные темы
	 * @param array $post Данные первого поста темы
	 * @param array $dirpath Путь к каталогу, в котором хранятся аттачи
	 * @param bool $hide Флаг сокрытия аттачей для форума, в котором создается тема
	 * @return array Ключи:
	 * string uri URI темы
	 * int t ИД темы
	 * int p ИД поста
	 */
	protected function NewTopic($topic,$post,$dirpath,$forum)
	{
		$config=$this->Forum->config;
		if($topic['uri'])
		{
			$R=Eleanor::$Db->Query('SELECT `uri` FROM `'.$config['ft'].'` WHERE `f`='.$topic['f'].' AND `language`=\''.$topic['language'].'\' AND `uri`='.Eleanor::$Db->Escape($topic['uri']).' LIMIT 1');
			if($R->num_rows>0)
			{
				$regexp=Eleanor::$Db->Escape($topic['uri'],false);
				$regexp=str_replace('\\','\\\\',$regexp);#Замена \ -> \\
				$R=Eleanor::$Db->Query('SELECT `uri` FROM `'.$config['ft'].'` WHERE `f`='.$topic['f'].' AND `language`=\''.$topic['language'].'\' AND `uri` REGEXP \'^'.$regexp.'\\\\-[[:digit:]]+\' ORDER BY `uri` DESC LIMIT 1');
				if($a=$R->fetch_assoc() and preg_match('#(\d+)$#',$a['uri'],$m)>0)
					$topic['uri'].='-'.((int)$m[1]+1);
				else
					$topic['uri'].='-2';
			}
		}

		Eleanor::$Db->Transaction();
		$t=Eleanor::$Db->Insert($config['ft'],$topic);
		if($t)
		{
			$post['t']=$t;
			$p=Eleanor::$Db->Insert($config['fp'],$post);
			if($p)
			{
				Eleanor::$Db->Update($config['ft'],array('lp_id'=>$p),'`id`='.$t.' LIMIT 1');
				$update=static::SaveAttach($post['text'],$dirpath,array('f'=>$forum['id'],'language'=>$topic['language'],'t'=>$t,'p'=>$p),$forum['hide_attach']);
				if($update)
					Eleanor::$Db->Update($config['fp'],array('text'=>$post['text']),'`id`='.$p.' LIMIT 1');
				switch($topic['status'])
				{
					case 1:
						Eleanor::$Db->Update($config['fl'],array('lp_date'=>$topic['lp_date'],'lp_id'=>$t,'lp_title'=>$topic['title'],'lp_uri'=>$topic['uri'],'lp_author'=>$post['author'],'lp_author_id'=>$post['author_id'],'!topics'=>'`topics`+1'),'`id`='.$topic['f'].' AND `language`=\''.$topic['language'].'\' LIMIT 1');
					break;
					case -1:
						Eleanor::$Db->Update($config['fl'],array('!queued_topics'=>'`queued_topics`+1'),'`id`='.$topic['f'].' AND `language`=\''.$topic['language'].'\' LIMIT 1');
					break;
				}
				Eleanor::$Db->Commit();
				return array(
					'uri'=>$topic['uri'],
					't'=>$t,
					'p'=>$p,
				);
			}
			else
			{
				Eleanor::$Db->RollBack();
				throw new EE('UNABLE_TO_CREATE_POST',EE::UNIT);
			}
		}
		Eleanor::$Db->RollBack();
		throw new EE('UNABLE_TO_CREATE_TOPIC',EE::UNIT);
	}

	/*public function GetLastPost($fid,$lang)
	{
		$R=Eleanor::$Db->Query('SELECT `id` `lp_id`,`lp_date`,`title` `lp_title`,`lp_author`,`lp_author_id` FROM `'.$this->Forum->config['ft'].'` WHERE `fid`='.$fid.' AND `language`=\''.$lang.'\' AND `status`=1 ORDER BY `sortdate` DESC LIMIT 1');
		return$R->num_rows>0
			? $R->fetch_assoc()
			: array(
			'lp_date'=>'0000-00-00 00:00:00',
			'lp_id'=>0,
			'lp_title'=>'',
			'lp_author'=>'',
			'lp_author_id'=>0,
		);
	}*/

	/**
	 * Сохранение аттачей
	 * @param string &$text Текст поста, в котором будет заменены все ссылки на аттачи на [file=ID]
	 * @param string $dirpath Путь к каталогу, в котором хранятся аттачи
	 * @param $data array('f','language','t','p')
	 * @param $hide Флаг сокрытия аттачей для форума, в котором находится пост
	 * @return array|bool Флаг необходимости пересохранения текста поста
	 */
	protected function SaveAttach(&$text,$dirpath,$data,$hide)
	{
		$files=glob(rtrim($dirpath,'/\\').'/*',GLOB_MARK);
		natsort($files);
		foreach($files as $k=>&$v)
			if(substr($v,-1)==DIRECTORY_SEPARATOR)
				unset($files[$k]);
			else
				$v=array(
					'size'=>Files::BytesToSize(filesize($v)),
					'hash'=>md5_file($v),
					'file'=>basename($v),
				);
		if(!$files)
			return false;

		$dest=$this->Forum->config['attachroot'];
		if(!is_dir($dest))
			Files::MkDir($dest);
		$dest.='p'.$data['p'].'/';
		if(is_dir($dest))
			Files::Delete($dest);
		if(!rename($dirpath,$dest))
			return false;
		$data['!date']='NOW()';

		$from=rtrim(substr(Eleanor::$os=='w' ? str_replace('\\','/',$dirpath) : $dirpath,strlen(Eleanor::$root)),'/').'/';

		$config=$this->Forum->config;
		$dbf=array();
		$R=Eleanor::$Db->Query('SELECT `id`,`name`,`file`,`date`,`hash` FROM `'.$config['fa'].'` WHERE `p`='.$data['p']);
		while($a=$R->fetch_assoc())
			$dbf[$a['name'] ? $a['name'] : $a['file']]=array($a['id'],$a['hash'],$a['file'] and $a['name'],$a['date']);

		$todb=$remdb=$rfrom=$rto=array();#Массив для записи в БД, удаления из БД, замены $rfrom=>$rto в $text
		if($hide)
			foreach($files as $v)
			{
				#Если имя файла в системе начинается с -xxxxx-, где x - случайные символы, этот "префикс" нужно отбросить
				$un='-'.substr(uniqid(),-5).'-';
				$v['name']=$v['file'];
				$v['file']=$un.(strlen($v['file'])>254 ? substr($v['file'],7) : $v['file']);
				rename($dest.$v['name'],$dest.$v['file']);

				if(isset($dbf[ $v['name'] ]) and $dbf[ $v['name'] ][1]==$v['hash'])
				{
					#Для вставленных ссылок
					$rfrom[]=$from.$v['name'];
					$rto[]='['.$config['abb'].'='.$dbf[ $v['name'] ][0].']';

					Eleanor::$Db->Update($config['fa'],$v,'`id`='.$dbf[ $v['name'] ][0].' LIMIT 1');
					unset($dbf[ $v['file'] ]);
				}
				else
					$todb[]=$data+$v;
			}
		else
			foreach($files as $k=>&$v)
			{
				$v['name']='';
				if(isset($dbf[ $v['file'] ]) and $dbf[ $v['file'] ][1]==$v['hash'])
				{
					$rfrom[]=$from.$v['file'];
					$rto[]='['.$config['abb'].'='.$dbf[ $v['file'] ][0].']';

					if($dbf[$v['file']][2])
						Eleanor::$Db->Update($config['fa'],$v,'`id`='.$dbf[ $v['file'] ][0].' LIMIT 1');
					else
						unset($dbf[ $v['file'] ]);
				}
				else
					$todb[$k]=$data+$v;
			}

		foreach($dbf as &$v)
			$remdb[]=$v[0];

		if($remdb)
			Eleanor::$Db->Delete($config['fa'],'`id`'.Eleanor::$Db->In($remdb));

		if($todb)
		{
			$id=Eleanor::$Db->Insert($config['fa'],$todb);
			foreach($todb as &$v)
			{
				$rfrom[]=$from.($v['name'] ? $v['name'] : $v['file']);
				$rto[]='['.$this->config['abb'].'='.$id++.']';
			}
		}

		if($rto)
		{
			$text=str_replace($rfrom,$rto,$text,$cnt);
			return$cnt>0;
		}

		return false;
	}


	/**
	 * Увеличение счетчика постов пользователю и возможный перевод его в другую группу.
	 * @param $cnt Число, на которое нужно увеличить счетчик постов
	 * @param $uid ID поьзователя
	 */
	public function IncUserPosts($cnt,$uid=false)
	{
		$Forum = $this->Forum;
		$config = $Forum->config;

		if(!$uid)
			if($Forum->user)
				$uid=$Forum->user['id'];
			else
				return;

		Eleanor::$Db->Update($config['fu'],array('!posts'=>'`posts`+'.$cnt),'`id`='.$uid.' LIMIT 1');

		if($Forum->user and $uid==$Forum->user['id'])
		{
			$Forum->user['posts']+=$cnt;

			$posts=$Forum->user['posts'];
			$groups=$Forum->ug;
		}
		else
		{
			$R=Eleanor::$Db->Query('SELECT `s`.`groups`,`f`.`posts` FROM `'.$config['fu'].'` INNER JOIN `'.P.'users_site` USING(`id`) WHERE `id`='.$uid.' LIMIT 1');
			if(!list($groups,$posts)=$R->fetch_row())
				return;

			$posts+=$cnt;
			$groups=explode(',,',trim($groups,','));
		}
		$group=reset($groups);

		#Продвижение в другую группу
		$R=Eleanor::$Db->Query('SELECT `parents` FROM `'.$config['fg'].'` WHERE `id`='.$group.' LIMIT 1');
		if(list($parents)=$R->fetch_row())
		{
			$grows=array();
			$R=Eleanor::$Db->Query('SELECT `id`,`grow_to`,`grow_after` FROM `'.$config['fg'].'` WHERE `id`'.($parents ? 'IN('.$parents.$group.')' : '='.$group.' LIMIT 1'));
			while($a=$R->fetch_assoc())
				$grows[ $a['id'] ]=array_slice($a,1);

			$parents=$parents ? explode(',',rtrim($parents,',')) : array();
			$parents[]=$group;
			$parents=array_reverse($parents);

			$growto=$after=false;
			foreach($parents as $v)
				if(isset($grows[$v]) and (!$growto or !$after))
				{
					if(!$growto and $grows[$v]['grow_to']!==null)
						$growto=$grows[$v]['grow_to'];

					if(!$after and $grows[$v]['grow_after']!==null)
						$after=$grows[$v]['grow_after'];
				}
				else
					break;

			if($after and $growto and $after<=$posts)
			{
				array_splice($groups,0,1,$growto);
				UserManager::Update(array('groups'=>$groups),$uid);
			}
		}
	}
}