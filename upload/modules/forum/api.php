<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;

/**
 * API форума
 */
class ApiForum extends BaseClass
{
	private
		$config=array();

	/**
	 * @param array $config Конфигурация форума
	 */
	public function __construct($config=array())
	{
		$this->config=$config ? $config : include __DIR__.'/config.php';
	}

	/**
	 * Генератор новой ссылки при смене языков сайта.
	 * @param array|string $q Старый URL
	 * @param array $lang Язык, на который нужно переключиться
	 * @return mixed
	 */
	public function LangUrl($q,$lang)
	{
		$El=Eleanor::getInstance();
		if(Eleanor::$service!='user')
			return$El->Url->Construct($q);

		include_once __DIR__.'/forum.php';
		include_once __DIR__.'/core.php';
		$Forum=new ForumCore($this->config);
		$Forum->config=$Forum->Forum->config;
		$Forum->LoadUser();

		$gt=$Forum->GuestSign('t');
		$forum=$topic=$do=$id=false;

		if($El->Url->furl)
		{
			$furi=$El->Url->Parse();
			$furi=isset($furi['']) ? (array)$furi[''] : array();

			if($El->Url->ending)
			{
				$forum=$Forum->Forums->GetForum($furi);
				if($forum and !$Forum->CheckForumAccess($forum['id']))
					$forum=false;
			}
			else
			{
				$turi=array_pop($furi);
				if(count($furi)>0)
				{
					$forum=$Forum->Forums->GetForum($furi);
					if($forum)
					{
						$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`f`,`status`,`language`,`lrelated`,`author_id` FROM `'.$this->config['ft'].'` WHERE `uri`='.Eleanor::$Db->Escape($turi).' AND `f`='.$forum['id'].' AND `language`IN(\'\',\''.$forum['language'].'\') LIMIT 1');
						$topic=$R->fetch_assoc();
					}
				}
				elseif(preg_match('#^(forum|topic|post|new\-topic|new\-post|edit|edit\-topic|answer|subscribe\-topic|subscribe\-forum|go\-new\-post|go\-last\-post|find\-post|reputation|given|activate\-post)\-(.+?)$#i',$turi,$m)>0)
				{
					$do=$m[1];
					$id=$m[2];

					if($do=='topic')
					{
						$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`f`,`status`,`language`,`lrelated`,`author_id` FROM `'.$this->config['ft'].'` WHERE `id`='.(int)$id.' AND `f`='.$forum['id'].' AND `language`IN(\'\',\''.$forum['language'].'\') LIMIT 1');
						$topic=$R->fetch_assoc();
					}
				}
				else
				{
					$do=$turi;
					$id=false;
				}
				
				if($topic and $Forum->CheckTopicAccess($topic))
				{
					if($forum['moderators'])
						list(,$moder)=$Forum->Moderator->ByIds($forum['moderators'],array('chstatust','chstatus','mchstatust','mchstatus'),$this->config['n'].'_moders_fp'.$forum['id'].$Forum->language);
					else
						$moder=false;

					if(($topic['status']==0 or $topic['status']==-1 and !($Forum->user and $Forum->user['id']==$topic['author_id'] or in_array($topic['id'],$gt))) and !$Forum->ugr['supermod'] and (!$moder or !in_array(1,$moder['chstatust']) and !in_array(1,$moder['mchstatust'])))
						$topic=false;
					elseif($topic['language']!='' and $topic['lrelated'])
					{
						$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`f`,`status`,`language`,`lrelated`,`author_id` FROM `'.$this->config['ft'].'` WHERE `id`'.Eleanor::$Db->In(explode(',,',trim($topic['lrelated'],','))).' AND `language`IN(\'\',\''.$lang.'\') LIMIT 1');
						if($topic=$R->fetch_assoc() and $Forum->CheckTopicAccess($topic))
						{
							if($forum['id']!=$topic['f'])
							{
								$forum=$Forum->Forums->GetForum($topic['f']);

								if($forum['moderators'])
									list(,$moder)=$Forum->Moderator->ByIds($forum['moderators'],array('chstatust','chstatus','mchstatust','mchstatus'),$this->config['n'].'_moders_fp'.$topic['f'].$Forum->language);
								else
									$moder=false;

								if(($topic['status']==0 or $Forum->user and $Forum->user['id']!=$topic['author_id']) and !$Forum->ugr['supermod'] and (!$moder or !in_array(1,$moder['chstatust']) and !in_array(1,$moder['mchstatust'])))
									$topic=false;
							}
						}
						else
							$topic=false;
					}
					elseif($topic['language'])
						$topic=false;
				}
				else
					$topic=false;
			}
		}
		elseif(isset($q['do']))
		{
			$do=(string)$q['do'];
			$id=false;
		}
		else
		{
			$do=$id=false;
			foreach(array('forum','topic','post','new-topic','new-post','edit-topic','edit','edit-topic','answer','subscribe-topic','subscribe-forum','go-new-post','go-last-post','find-post','reputation','given','activate-post') as $v)
				if(isset($q[$v]))
				{
					$do=$v;
					$id=(string)$q[$v];
					break;
				}

			if($do=='topic' and $id>0)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`f`,`status`,`language`,`lrelated`,`author_id` FROM `'.$this->config['ft'].'` WHERE `id`='.(int)$id.' AND `language`!=\'\' AND `lrelated`!=\'\' LIMIT 1');
				if(!$topic=$R->fetch_assoc() or !$Forum->CheckTopicAccess($topic) or ($topic['status']==0 or $topic['status']==-1 and !($Forum->user and $topic['author_id']==$Forum->user['id'] or in_array($topic['id'],$gt))))
					$id=$do=$topic=false;
				else
				{
					$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`f`,`status`,`author_id` FROM `'.$Forum->config['ft'].'` WHERE `id`'.Eleanor::$Db->In(explode(',,',trim($topic['lrelated'],','))).' AND `language`=\''.$lang.'\'');
					if($a=$R->fetch_assoc() and $Forum->CheckTopicAccess($a) and ($a['status']==1 or $a['status']==-1 and ($Forum->user and $a['author_id']==$Forum->user['id'] or in_array($a['id'],$gt))))
						#Ради экономии производительности, я не делал поддержку status=0 или status=-1 для случая модератора.
						$id=$a['id'];
				}
			}
		}

		if($do)
			return $Forum->Links->Action($do,$id);

		$Forum->language=$lang;
		$Forum->Forums->ReDump($lang);

		if($topic)
			$url=$Forum->Links->Topic($forum['id'],$topic['id'],$topic['uri'],$q);
		elseif($forum and isset($Forum->Forums->dump[ $forum['id'] ]))
			$url=$Forum->Links->Forum($forum['id'],$q);
		else
			$url=false;

		return$url;
	}

	/**
	 * Генератор контролов для настройки создания sitemap-ов.
	 * @param bool $post Флаг необходимости загрузки данных контролов из POST запроса
	 * @param int $ti tabindex iterator
	 * @return array of controls
	 */
	public function SitemapConfigure(&$post,&$ti=13)
	{
		$lang=Eleanor::$Language->Load(__DIR__.'/api-*.php',false);

		return array(
			'pf'=>array(
				'title'=>$lang['pf'],
				'type'=>'input',
				'default'=>'0.7',
				'bypost'=>&$post,
				'options'=>array(
					'type'=>'number',
					'extra'=>array(
						'tabindex'=>$ti++,
						'min'=>0.1,
						'max'=>1,
						'step'=>0.1,
					),
				),
			),
			'pt'=>array(
				'title'=>$lang['pt'],
				'type'=>'input',
				'default'=>'0.9',
				'bypost'=>&$post,
				'options'=>array(
					'type'=>'number',
					'extra'=>array(
						'tabindex'=>$ti++,
						'min'=>0.1,
						'max'=>1,
						'step'=>0.1,
					),
				),
			),
			'pp'=>array(
				'title'=>$lang['pp'],
				'type'=>'input',
				'default'=>'1',
				'bypost'=>&$post,
				'options'=>array(
					'type'=>'number',
					'extra'=>array(
						'tabindex'=>$ti++,
						'min'=>0.1,
						'max'=>1,
						'step'=>0.1,
					),
				),
			),
			'ps'=>array(
				'title'=>$lang['ps'],
				'type'=>'input',
				'default'=>'0.8',
				'bypost'=>&$post,
				'options'=>array(
					'type'=>'number',
					'extra'=>array(
						'tabindex'=>$ti++,
						'min'=>0.1,
						'max'=>1,
						'step'=>0.1,
					),
				),
			),
			'date'=>array(
				'title'=>$lang['date'],
				'descr'=>$lang['date_'],
				'type'=>'date',
				'default'=>'',
				'bypost'=>&$post,
				'options'=>array(
					'extra'=>array(
						'tabindex'=>$ti++,
					),
				),
			),
		);
	}

	/**
	 * Генератор карты сайты
	 * @param mixed $data Данные, полученные от этой функции на предыдущем этапе
	 * @param array $conf Значение конфигурации, полученной от значений контролов из SitemapConfigure
	 * @param array $opts Опции, массив с ключами:
	 *   int per_time Количество ссылок, генерируемых за один раз
	 *   string type Тип данных (один из вариантов):
	 *     number Получить полное число всех ссылок, которые потенциально могут быть сгенерированы
	 *     get Генерировать ссылки
	 *   callback callback Функция, которую следует вызывать для отправки результатов
	 *   array sections Секции модуля
	 * @return mixed
	 */
	public function SitemapGenerate($data,$conf,$opts)
	{
		$conf+=array(
			'pf'=>0.7,
			'pt'=>0.9,
			'pp'=>1,
			'ps'=>0.8,
			'date'=>false,
		);
		$limit=$opts['per_time'];

		$Url=new Url;
		$Url->file=Eleanor::$services['user']['file'];

		foreach(Eleanor::$langs as $lang=>&$_)
		{
			if($limit<1)
				break;
			if(!isset($data[$lang]))
				$data[$lang]=array(
					'service'=>false,#Флаг генерерации сервисных страниц (список пользователей, кто онлайн, поиск и т.п.)
					'offset-forums'=>0,#Отступ для форумов
					'offset-topics'=>0,#Отступ для тем
					'offset-posts'=>0,#Отступ для постов
				);
			#Сервисных страниц всего 8: главная, top, online, users, reputation, stats, moderators, search

			$qlang=$lang==LANGUAGE ? 'IN (\'\',\''.$lang.'\')' : '=\''.$lang.'\'';
			if($opts['type']=='number')
			{
				#Количество сервисных страниц, их всего 8
				call_user_func($opts['callback'],8);

				#Количество форумов
				$R=Eleanor::$Db->Query('SELECT COUNT(`language`) `cnt` FROM `'.$this->config['fl'].'` WHERE `language`'.$qlang.' LIMIT '.$data[$lang]['offset-forums'].','.$limit);
				list($cnt)=$R->fetch_row();
				call_user_func($opts['callback'],$cnt);

				#Количество тем
				$R=Eleanor::$Db->Query('SELECT COUNT(`language`) `cnt` FROM `'.$this->config['ft'].'` WHERE `language`'.$qlang.($conf['date'] ? ' AND `created`>='.$conf['date'] : '').' LIMIT '.$data[$lang]['offset-topics'].','.$limit);
				list($cnt)=$R->fetch_row();
				call_user_func($opts['callback'],$cnt);

				#Количество постов
				$R=Eleanor::$Db->Query('SELECT COUNT(`language`) `cnt` FROM `'.$this->config['fp'].'` WHERE `language`'.$qlang.($conf['date'] ? ' AND `created`>='.$conf['date'] : '').' LIMIT '.$data[$lang]['offset-topics'].','.$limit);
				list($cnt)=$R->fetch_row();
				call_user_func($opts['callback'],$cnt);
			}
			else
			{
				Language::$main=$lang;

				$sect=array();
				foreach($opts['sections'] as $k=>$v)
				{
					if(Eleanor::$vars['multilang'] and isset($v[$lang]))
						$v=reset($v[$lang]);
					else
						$v=isset($v[LANGUAGE]) ? reset($v[LANGUAGE]) : reset($v['']);
					$sect[$k]=$v;
				}
				$sect=reset($sect);
				$Url->SetPrefix(Eleanor::$vars['multilang'] && $lang!=LANGUAGE ? array('lang'=>$_['uri'],'module'=>$sect) : array('module'=>$sect));

				if(!$data[$lang]['service'])
				{
					$a=array(
						$Url->Construct(array()),
						$Url->Construct(array('do'=>'top')),
						$Url->Construct(array('do'=>'online')),
						$Url->Construct(array('do'=>'users')),
						$Url->Construct(array('do'=>'reputation')),
						$Url->Construct(array('do'=>'given')),
						$Url->Construct(array('do'=>'moderators')),
						$Url->Construct(array('do'=>'search')),
					);

					foreach($a as $v)
						call_user_func(
							$opts['callback'],
							array(
								'loc'=>$v,
								'changefreq'=>'weekly',
								'priority'=>$conf['ps'],
							)
						);
					$limit-=8;
					$data[$lang]['service']=true;
				}

				$R=Eleanor::$Db->Query('SELECT `id` FROM `'.$this->config['fl'].'` WHERE `language`'.$qlang.' ORDER BY `id` ASC LIMIT '.$data[$lang]['offset-forums'].','.$limit);
				$data[$lang]['offset-forums']+=$R->num_rows;
				while($a=$R->fetch_assoc())
				{
					$limit--;
					call_user_func(
						$opts['callback'],
						array(
							'loc'=>$Url->Construct(array('forum'=>$a['id'])),
							'priority'=>$conf['pf'],
						)
					);
				}

				$R=Eleanor::$Db->Query('SELECT `id` FROM `'.$this->config['ft'].'` WHERE `language`'.$qlang.($conf['date'] ? ' AND `created`>='.$conf['date'] : '').' ORDER BY `created` ASC LIMIT '.$data[$lang]['offset-topics'].','.$limit);
				$data[$lang]['offset-topics']+=$R->num_rows;
				while($a=$R->fetch_assoc())
				{
					$limit--;
					call_user_func(
						$opts['callback'],
						array(
							'loc'=>$Url->Construct(array('topic'=>$a['id'])),
							'priority'=>$conf['pt'],
						)
					);
				}

				$R=Eleanor::$Db->Query('SELECT `id` FROM `'.$this->config['fp'].'` WHERE `language`'.$qlang.($conf['date'] ? ' AND `created`>='.$conf['date'] : '').' ORDER BY `created` ASC LIMIT '.$data[$lang]['offset-posts'].','.$limit);
				$data[$lang]['offset-posts']+=$R->num_rows;
				while($a=$R->fetch_assoc())
				{
					$limit--;
					call_user_func(
						$opts['callback'],
						array(
							'loc'=>$Url->Construct(array('post'=>$a['id'])),
							'priority'=>$conf['pp'],
						)
					);
				}
			}
		}

		return$data;
	}
}