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

	/**
	 * Учет публикации поста с целью избежания флуда
	 * @param int $uid Идентификатор пользователя
	 */
	public function AddFloodLimit($uid=0)
	{
		if($fl=Eleanor::$Permissions->FloodLimit())
		{
			$TC=new TimeCheck;
			$TC->uid=$uid;
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
	 *  int status Статус поста
	 * @param array $rights Права пользователя на форуме, массив с ключами:
	 *  array of boolean edit Возможность правки поста
	 *  array of boolean delete Возможность удаления поста
	 *  array of int Временное ограничение (число секунд с момента создания поста) когда можно править / удалять пост
	 * @param $moder Права модератора на форуме, массив с ключами:
	 *  array of boolean chstatus Возможность увидеть неактивный пост
	 *  array of boolean mchstatus Возможность увидеть неактивный пост
	 *  array of boolean edit Возможность правки постов
	 *  array of boolean delete Возможность удаления постов
	 * @return array:
	 *  bool show Возможность отобразить пост
	 *  bool edit Возможность править пост
	 *  bool delete Возможность удалить пост
	 */
	public function Rights(array$post,array$rights,array$moder=array())
	{
		$Forum=$this->Core;
		$r['edit']=$r['delete']=$r['show']=$Forum->ugr['supermod'];
		if(!$r['edit'])
		{
			$my=$Forum->user && $post['author_id']==$Forum->user['id'] || in_array($post['id'],$Forum->GuestSign('p'));

			$r['show']=$my && $post['status']!=0 || in_array($post['status'],array(1,-2));
			if($my and $post['status']!=0 and (in_array(0,$rights['editlimit']) or time()-strtotime($post['created'])<=max($rights['editlimit'])))
			{
				$r['edit']=in_array(1,$rights['edit']);
				$r['delete']=in_array(1,$rights['delete']);
			}

			if($moder)
			{
				$r['show']|=in_array(1,$moder['chstatus']) || in_array(1,$moder['mchstatus']);
				$r['edit']|=in_array(1,$moder['edit']);
				$r['delete']|=in_array(1,$moder['delete']);
			}
		}
		return$r;
	}

	/**
	 * Непосредственная публикация / правка поста в теме. Никаких проверок не осуществляется
	 * @params array $values Значения поста
	 */
	public function Post(array$values,$topic=false)
	{
		$config=$this->Forum->config;
		if(isset($values['id']))
		{#Правка поста
			$p=(int)$values['id'];
			$R=Eleanor::$Db->Query('SELECT `` FROM `'.$config['fp'].'` WHERE `id`='.$p.' LIMIT 1');
			if(!$oldpost=$R->fetch_assoc())
				throw new EE('INCORRECT_TOPIC',EE::USER);

			#ToDo! Необходимо учесть возможность смены автора, статуса сообщения
		}
		elseif(isset($values['t'],$values['text']))
		{#Создание поста
			$t=(int)$values['t'];
			$R=Eleanor::$Db->Query('SELECT `uri`,`f`,`language`,`status`,`title` FROM `'.$config['ft'].'` WHERE `id`='.$t.' LIMIT 1');
			if(!$topic=$R->fetch_assoc())
				throw new EE('INCORRECT_TOPIC',EE::USER);

			$created=isset($values['created']) ? $values['created'] : date('Y-m-d H:i:s');
			$post=array(
				'f'=>$topic['f'],
				'language'=>$topic['language'],
				't'=>$values['t'],
				'ip'=>isset($values['ip']) ? $values['ip'] : Eleanor::$ip,
				'created'=>$created,
				'sortdate'=>$created,
				'last_mod'=>$created,
				'text'=>$values['text'],
				'edit_reason'=>isset($values['edit_reason']) ? (string)$values['edit_reason'] : '',
			);

			#Статус поста
			if(isset($values['status']) and in_array($values['status'],array(-1,0,1)))
			{
				$status=$values['status'];
				if($topic['status']==1)
					$post['status']=$status;
				else
					switch($status)
					{
						case -1:#Ожидающие посты неактивной темы
							$post['status']=-3;
						break;
						case 1:#Активные посты неактивной темы
							$post['status']=-2;
						break;
						default:
							$post['status']=0;
					}
			}
			else
			{
				$status=1;
				$post['status']=$topic['status']==1 ? 1 : -2;
			}

			#Авторство поста
			if(isset($values['author']) and array_key_exists('author_id',$values))
				$post+=array(
					'author'=>$values['author'],
					'author_id'=>$values['author_id'],
				);
			else
			{
				$me=$this->Core->user ? Eleanor::$Login->GetUserValue(array('name','id')) : array('name'=>'Guest','id'=>null);
				$post+=array(
					'author'=>$me['name'],
					'author_id'=>$me['id'],
				);
			}

			#Редактор поста
			if(isset($values['edited_by']) and array_key_exists('edited_by_id',$values))
				$post+=array(
					'edited_by'=>$values['edited_by'],
					'edited_by_id'=>$values['edited_by_id'],
				);

			#Информация одобрения поста
			if(isset($values['approved_by']) and array_key_exists('approved_by_id',$values))
				$post+=array(
					'approved_by'=>$values['approved_by'],
					'approved_by_id'=>$values['approved_by_id'],
				);

			$merge=false;
			if(!isset($values['merge']) or $values['merge'])
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`author_id`,IF(`updated`>`created`,`updated`,`created`) `updated` FROM `'.$this->Forum->config['fp'].'` WHERE `t`='.$post['t'].' AND `status`='.$post['status'].' ORDER BY `sortdate` DESC LIMIT 1');
				if($a=$R->fetch_assoc())
					if($post['author_id'])
					{
						if($a['author_id']==$post['author_id'])
							$merge=$a;
					}
					elseif(in_array($a['id'],isset($values['merge_ids']) ? $values['merge_ids'] : $this->Core->GuestSign('p')))
						$merge=$a;
			}

			$forum=$this->Forums->GetForum($topic['f']);
			if($merge)
			{
				$diff=strtotime($created)-strtotime($merge['updated']);
				if($diff>0)
				{
					$days=floor($diff/86400);
					$diff-=$days*86400;

					$hours=floor($diff/3600);
					$diff-=$hours*3600;

					$mins=floor($diff/60);
					$secs=$mins>0 ? $diff % $mins : $diff;
				}
				else
					$days=$hours=$mins=$secs=0;

				$lang=$this->Core->Language['post'];
				$post['text']=$lang['merged']($days,$hours,$mins,$secs).$post['text'];

				if(isset($values['files']))
					static::SaveAttach($post['text'],$values['files'],array('f'=>$topic['f'],'language'=>$topic['language'],'t'=>$t,'p'=>$merge['id']),$forum['hide_attach'],false);

				Eleanor::$Db->Update($config['fp'],
					array(
						'updated'=>$created,
						'!text'=>'CONCAT(`text`,'.Eleanor::$Db->Escape($post['text']).')',
					),
					'`id`='.$merge['id'].' LIMIT 1');

				$this->AddFloodLimit($post['author_id']);
				return array('id'=>$merge['id'],'merged'=>true,'text'=>$post['text']);
			}

			if(isset($values['updated']))
				$post['updated']=$values['updated'];

			$p=Eleanor::$Db->Insert($config['fp'],$post);

			if(isset($values['files']))
			{
				$update=static::SaveAttach($post['text'],$values['files'],array('f'=>$topic['f'],'language'=>$topic['language'],'t'=>$t,'p'=>$p),$forum['hide_attach']);
				if($update)
					Eleanor::$Db->Update($config['fp'],array('text'=>$post['text']),'`id`='.$p.' LIMIT 1');
			}

			switch($status)
			{
				case 1:
					#Обновление темы
					Eleanor::$Db->Update($config['ft'],array(
							'!posts'=>'`posts`+1',
							'lp_date'=>$created,
							'lp_id'=>$p,
							'lp_author'=>$post['author'],
							'lp_author_id'=>$post['author_id']),
						'`id`='.$t.' LIMIT 1');

					#Обновление форума
					Eleanor::$Db->Update($config['fl'],array(
							'!posts'=>'`posts`+1',
							'lp_date'=>$created,
							'lp_id'=>$t,
							'lp_title'=>$topic['title'],
							'lp_uri'=>$topic['uri'],
							'lp_author'=>$post['author'],
							'lp_author_id'=>$post['author_id']),
						'`id`='.$topic['f'].' AND `language`=\''.$topic['language'].'\' LIMIT 1');

					Eleanor::$Db->Update($config['lp'],array(
							'lp_date'=>$created,
							'lp_id'=>$t,
							'lp_title'=>$topic['title'],
							'lp_uri'=>$topic['uri'],
							'lp_author'=>$post['author'],
							'lp_author_id'=>$post['author_id']
						),
						'`lp_id`='.$t);

					if($forum['inc_posts'] and $post['author_id'])
						$this->IncUserPosts(1,$post['author_id']);
				break;
				case -1:
					#Обновление темы
					Eleanor::$Db->Update($config['ft'],array('!queued_posts'=>'`queued_posts`+1'),'`id`='.$t.' LIMIT 1');

					#Обновление форума
					Eleanor::$Db->Update(
						$config['fl'],
						array('!queued_posts'=>'`queued_posts`+1'),
						'`id`='.$topic['f'].' AND `language`=\''.$topic['language'].'\' LIMIT 1'
					);
			}

			$this->AddFloodLimit($post['author_id']);
			return array('id'=>$p,'merged'=>false);
		}
		else
			throw new EE('MISSING_ARGUMENTS',EE::USER);
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
			$t=(int)$values['id'];
			$R=Eleanor::$Db->Query('SELECT `id` FROM `'.$config['ft'].'` WHERE `id`='.$t.' LIMIT 1');
			if(!$oldtopic=$R->fetch_assoc())
				throw new EE('INCORRECT_TOPIC',EE::USER);

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
				'status'=>isset($values['status']) ? $values['status'] : 1,
				'created'=>$created,
				'state'=>isset($values['state']) ? $values['state'] : 'open',
				'sortdate'=>isset($values['pinned']) ? $values['pinned'] : $created,
				'pinned'=>isset($values['pinned']) ? $values['pinned'] : '0000-00-00 00:00:00',
				'lp_date'=>$created,
				'voting'=>isset($values['voting']) ? $values['voting'] : 0,
				'last_mod'=>$created,
			);

			#Префикс
			if(!isset($topic['prefix']) and isset($values['prefix']))
				$topic['prefix']=$values['prefix'];

			#Авторство темы
			if(!isset($topic['author']) or !array_key_exists('author_id',$topic))
			{
				if(isset($values['author']) and array_key_exists('author_id',$values))
					$topic+=array(
						'author'=>$values['author'],
						'author_id'=>$values['author_id'],
						'lp_author'=>$values['author'],
						'lp_author_id'=>$values['author_id'],
					);
				else
				{
					$me=$this->Core->user ? Eleanor::$Login->GetUserValue(array('name','id')) : array('name'=>'Guest','id'=>null);
					$topic+=array(
						'author'=>$me['name'],
						'author_id'=>$me['id'],
						'lp_author'=>$me['name'],
						'lp_author_id'=>$me['id'],
					);
				}
			}

			$post+=array(
				'f'=>$values['f'],
				'status'=>$topic['status']==1 ? 1 : -2,
				'author'=>$topic['author'],
				'author_id'=>$topic['author_id'],
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
							'uri'=>isset($values['uri']) && array_key_exists($lang,$values['uri']) ? $values['uri'][$lang] : null,
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
						'uri'=>array_key_exists('uri',$values) ? $values['uri'] : null,
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

			if($cnt>0 and $post['author_id'])
			{
				$this->AddFloodLimit($post['author_id']);

				if($forum['inc_posts'] and $post['status']==1)
					$this->IncUserPosts($cnt,$post['author_id']);
			}

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
	 *   string uri URI темы
	 *   int t ИД темы
	 *   int p ИД поста
	 */
	protected function NewTopic($topic,$post,$dirpath,$forum)
	{
		$config=$this->Forum->config;
		if(isset($topic['uri']))
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
			Eleanor::$Db->Update($config['ft'],array('lp_id'=>$p),'`id`='.$t.' LIMIT 1');
			if($dirpath)
			{
				$update=static::SaveAttach($post['text'],$dirpath,array('f'=>$forum['id'],'language'=>$topic['language'],'t'=>$t,'p'=>$p),$forum['hide_attach']);
				if($update)
					Eleanor::$Db->Update($config['fp'],array('text'=>$post['text']),'`id`='.$p.' LIMIT 1');
			}
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

			if($topic['author_id'])
				Eleanor::$Db->Insert($config['lp'],array(
						'uid'=>$topic['author_id'],
						'f'=>$forum['id'],
						'language'=>$topic['language'],
						'lp_date'=>$topic['created'],
						'lp_id'=>$t,
						'lp_title'=>$topic['title'],
						'lp_uri'=>$topic['uri'],
						'lp_author'=>$post['author'],
						'lp_author_id'=>$post['author_id']
					));

			return array(
				'uri'=>$topic['uri'],
				't'=>$t,
				'p'=>$p,
			);
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
	 * @param array $data array('f','language','t','p')
	 * @param bool $hide Флаг сокрытия аттачей для форума, в котором находится пост
	 * @param bool $replace Флаг замещения файлов (если false файлы будут совмещены с текущими - в случае склейки)
	 * @return array|bool Флаг необходимости пересохранения текста поста
	 */
	protected function SaveAttach(&$text,$dirpath,$data,$hide,$replace=true)
	{
		if(!$dirpath)
			return false;

		$dirpath=rtrim($dirpath,'/\\').'/';
		$files=glob($dirpath.'*',GLOB_MARK);

		if($files)
		{
			natsort($files);
			foreach($files as $k=>&$v)
				if(substr($v,-1)==DIRECTORY_SEPARATOR)
					unset($files[$k]);
				else
					$v=array(
						'size'=>Files::BytesToSize(filesize($v)),
						'hash'=>md5_file($v),
						'file'=>$replace ? basename($v) : $v,
					);
		}

		if(!$files)
			return false;

		$dest=$this->Forum->config['attachroot'].'p'.$data['p'].'/';
		if(is_dir($dest))
			if($replace)
			{
				Files::Delete($dest);
				goto Rename;
			}
			else
			{
				foreach($files as $k=>&$v)
					if(!rename($v['file'],$dest.($v['file']=basename($v['file']))))
						unset($files[$k]);
			}
		else
		{
			Rename:
			if(!rename($dirpath,$dest))
				return false;
		}
		unset($v);
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
				$rto[]='['.$config['abb'].'='.$id++.']';
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
		$Forum = $this->Core;
		$config = $this->Forum->config;

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
		$R=Eleanor::$Db->Query('SELECT `parents` FROM `'.P.'groups` WHERE `id`='.$group.' LIMIT 1');
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

	/**
	 * Метод нужно вызывать перед редактированием поста, для того, чтобы в процессе правки поста, можно было нормально
	 * работать с аттачами.
	 * @param int $id ID поста
	 * @param string $text Текст поста
	 * @param string $rootpath Путь на сервере для аттачей. Обязательно должен оканчиваться на /
	 * @param string $sitepath Путь на сайте для аттачей. Обязательно должен оканчиваться на /
	 * @return string
	 */
	public function EditPost($id,&$text,$rootpath,$sitepath)
	{
		$config=$this->Forum->config;
		$origpath=$config['attachroot'].'p'.$id.DIRECTORY_SEPARATOR;
		if(is_dir($origpath))
		{
			$R=Eleanor::$Db->Query('SELECT `id`,IF(`name`=\'\',`file`,`name`) `name`,`file` FROM `'.$config['fa'].'` WHERE `p`='.$id);
			while($a=$R->fetch_assoc())
				if(Files::Copy($origpath.$a['file'],$rootpath.$a['name']))
					$text=str_replace('['.$config['abb'].'='.$a['id'].']',$sitepath.$a['name'],$text);
		}
	}
}