<?php
/*
	Copyright © Eleanor CMS
	URL: http://eleanor-cms.ru, http://eleanor-cms.com
	E-mail: support@eleanor-cms.ru
	Developing: Alexander Sunvas*
	Interface: Rumin Sergey
	=====
	*Pseudonym
*/
if(!defined('CMS'))die;
global$Eleanor,$title;

$Eleanor->module['config']=$mc=include$Eleanor->module['path'].'config.php';
include$Eleanor->module['path'].'forum.php';
include$Eleanor->module['path'].'core.php';

$Eleanor->Forum=new ForumCore($mc);
Eleanor::$Template->queue[]=$mc['admintpl'];
$lang=Eleanor::$Language->Load($Eleanor->module['path'].'admin-*.php',$mc['n']);
Eleanor::LoadOptions($mc['opts']);

$Eleanor->module['links']=array(
	#Список форумов
	'forums'=>$Eleanor->Url->Prefix(),
	#Добавить форум
	'add-forum'=>$Eleanor->Url->Construct(array('do'=>'add-forum')),
	#Модераторы
	'moders'=>$Eleanor->Url->Construct(array('do'=>'moders')),
	#Добавить модератора
	'add-moder'=>$Eleanor->Url->Construct(array('do'=>'add-moder')),
	#Базовые права групп
	'groups'=>$Eleanor->Url->Construct(array('do'=>'groups')),
	#Массовое назначение прав
	'massrights'=>$Eleanor->Url->Construct(array('do'=>'massrights')),
	#Пользователи
	'users'=>$Eleanor->Url->Construct(array('do'=>'users')),
	#Форматы писем
	'letters'=>$Eleanor->Url->Construct(array('do'=>'letters')),
	#Менеджер файлов
	'files'=>$Eleanor->Url->Construct(array('do'=>'files')),
	#Список префиксов тем
	'prefixes'=>$Eleanor->Url->Construct(array('do'=>'prefixes')),
	#Добавить префикс темы
	'add-prefix'=>$Eleanor->Url->Construct(array('do'=>'add-prefix')),
	#Подписки на форумы
	'fsubscriptions'=>$Eleanor->Url->Construct(array('do'=>'fsubscriptions')),
	#Подписки на темы
	'tsubscriptions'=>$Eleanor->Url->Construct(array('do'=>'tsubscriptions')),
	#Менеджер репутации
	'reputation'=>$Eleanor->Url->Construct(array('do'=>'reputation')),
	#Обслуживание
	'tasks'=>$Eleanor->Url->Construct(array('do'=>'tasks')),
	#Настройки
	'options'=>$Eleanor->Url->Construct(array('do'=>'options'))
);

$post=$_SERVER['REQUEST_METHOD']=='POST' && Eleanor::$our_query;
if(isset($_GET['do']))
	switch($_GET['do'])
	{
		case'add-forum':
			if($post)
				SaveForum(0);
			else
				AddEditForum(0);
		break;
		case'add-moder':
			if($post)
				SaveModer(0);
			else
				AddEditModer(0);
		break;
		case'add-prefix':
			if($post)
				SavePrefix(0);
			else
				AddEditPrefix(0);
		break;
		case'groups':
			$Eleanor->Forum->Service->SyncGroups();

			$parent=isset($_GET['parent']) ? (int)$_GET['parent'] : 0;

			$temp=$items=$subitems=$navi=$where=array();
			$qs=array('do'=>'groups');
			$parents='';
			if($parent>0)
			{
				$qs['']['parent']=$parent;
				$R=Eleanor::$Db->Query('SELECT `parents` FROM `'.$mc['f'].'` WHERE `id`='.$parent.' LIMIT 1');
				list($parents)=$R->fetch_row();
				$parents.=$parent;
				$temp=array();
				$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['f'].'` INNER JOIN `'.$mc['fl'].'` USING(`id`) WHERE `language` IN (\'\',\''.Language::$main.'\') AND `id` IN ('.$parents.')');
				while($a=$R->fetch_assoc())
					$temp[$a['id']]=$a['title'];
				$navi[0]=array('title'=>'Список форумов','_a'=>$Eleanor->Url->Prefix());
				foreach(explode(',',$parents) as $v)
					if(isset($temp[$v]))
						$navi[$v]=array('title'=>$temp[$v],'_a'=>$v==$parent ? false : $Eleanor->Url->Construct(array('parent'=>$v)));
			}

			$R=Eleanor::$Db->Query('SELECT `id`,`g`.`title_l` `title`,`g`.`html_pref`,`g`.`html_end`,`g`.`parents`,`gf`.`grow_to`,`gf`.`grow_after`,`gf`.`supermod` FROM `'.P.'groups` `g` INNER JOIN `'.$mc['fg'].'` `gf` USING(`id`) WHERE `g`.`parents`=\''.$parents.'\'');
			$parents=array();
			while($a=$R->fetch_assoc())
			{
				$subitems[]=$a['parents'].$a['id'].',';

				$a['_aedit']=$Eleanor->Url->Construct(array('edit-group'=>$a['id']));
				$a['_aparent']=$Eleanor->Url->Construct(array('do'=>'groups','parent'=>$a['id']));
				$a['title']=$a['title'] ? Eleanor::FilterLangValues((array)unserialize($a['title'])) : '';
				$a['parents']=$a['parents'] ? explode(',',trim($a['parents'],',')) : array();
				if($a['parents'])
					$parents=array_merge($parents,$a['parents']);

				$titles[$a['id']]=$a['title'];
				$temp[$a['id']]=array_slice($a,1);
			}

			if($subitems)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`parents`,`title_l` FROM `'.P.'groups` WHERE `parents`'.Eleanor::$Db->In($subitems));
				$subitems=array();
				while($a=$R->fetch_assoc())
				{
					$p=ltrim(strrchr(','.rtrim($a['parents'],','),','),',');
					$subitems[$p][$a['id']]=array(
						'title'=>$a['title_l'] ? Eleanor::FilterLangValues((array)unserialize($a['title_l'])) : '',
						'_aedit'=>$Eleanor->Url->Construct(array('edit-group'=>$a['id']))
					);
				}
			}

			if($parents)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`grow_to`,`grow_after`,`supermod` FROM `'.$mc['fg'].'` WHERE `id`'.Eleanor::$Db->In($parents));
				$parents=array();
				while($a=$R->fetch_assoc())
					$parents[$a['id']]=array_slice($a,1);
			}

			natsort($titles);
			foreach($titles as $k=>&$v)
				$items[$k]=&$temp[$k];

			$title[]='Базовые права групп';
			$c=Eleanor::$Template->Groups($items,$subitems,$navi,$parents);
			Start();
			echo$c;
		break;
		case'massrights':
			$title[]='Массовая правка прав групп';

			$values=array(
				'groups'=>isset($_POST['forums']) ? (array)$_POST['forums'] : array(),
				'forums'=>isset($_POST['groups']) ? (array)$_POST['groups'] : array(),
				'inherit'=>isset($_POST['inherit']) ? (array)$_POST['inherit'] : array(),
			);

			$errors=array();
			$controls=GroupsControls($post,3);

			if($post)
			{
				if(!$values['forums'])
					$errors['NO_FORUMS']='Не выбраны форумы!';

				if(!$values['groups'])
					$errors['NO_GROUPS']='Не выбраны группы!';

				$co=$controls;
				foreach($values['inherit'] as &$v)
					unset($co[$v]);

				try
				{
					$co=$co ? $Eleanor->Controls->SaveControls($co) : array();
				}
				catch(EE $E)
				{
					$errors['ERROR']=$E->getMessage();
				}

				if(!$errors)
				{
					$groups=array();
					$R=Eleanor::$Db->Query('SELECT `id` FROM `'.P.'groups`');
					while($a=$R->fetch_row())
						$groups[]=$a[0];

					$R=Eleanor::$Db->Query('SELECT `id`,`permissions` FROM `'.$mc['f'].'` WHERE `id`'.Eleanor::$Db->In($values['forums']));
					while($f=$R->fetch_assoc())
					{
						$f['permissions']=$f['permissions'] ? (array)unserialize($f['permissions']) : array();
						foreach($f['permissions'] as $k=>&$v)
							if(!in_array($k,$groups))
								unset($f['permissions'][$k]);

						foreach($values['groups'] as &$v)
							if($co)
								$f['permissions'][$v]=$co;
							else
								unset($f['permissions'][$v]);

						Eleanor::$Db->Update($mc['f'],array('permissions'=>$f['permissions'] ? serialize($f['permissions']) : ''),'`id`='.$f['id'].' LIMIT 1');
					}
				}
			}
			$values['rights']=$Eleanor->Controls->DisplayControls($controls);

			$c=Eleanor::$Template->MassRights($controls,$values,Usermanager::GroupsOpts($values['groups']),$Eleanor->Forum->Forums->SelectOptions($values['forums']),$post,$errors);
			Start();
			echo$c;
		break;
		case'letters':
			$subf_i='{site} - название сайта<br />
{sitelink} - ссылка на сайт<br />
{topic} - название темы<br />
{topiclink} - ссылка на тему<br />
{topicnewlink} - ссылка на первый непрочитанный пост темы<br />
{topiclastlink} - ссылка на последний пост темы<br />
{forum} - название форума<br />
{forumlink} - ссылка на форум<br />
{author} - имя автора<br />
{authorlink} - ссылка на автора<br />
{created} - дата создания темы<br />
{lastview} - дата последнего просмотра форума<br />
{lastsend} - дата последней отправки<br />
{text} - текст<br />
{name} - имя пользователя<br />
{cancel} - ссылка на отмену подписки';

			$subf='{site} - название сайта<br />
{sitelink} - ссылка на сайт<br />
{forum} - название форума<br />
{forumlink} - ссылка на форум<br />
{cblink} - ссылка на измененные темы на форум<br />
{lastview} - дата последнего просмотра форума<br />
{lastsend} - дата последней отправки<br />
{cnt} - число новых тем<br />
{name} - имя пользователя<br />
{cancel} - ссылка на отмену подписки';

			$subt_i='{site} - название сайта<br />
{sitelink} - ссылка на сайт<br />
{forum} - название форума<br />
{forumlink} - ссылка на форум<br />
{topiclink} - ссылка на тему<br />
{topic} - название темы<br />
{topicnewlink} - ссылка на первый непрочитанный пост темы<br />
{topiclastlink} - ссылка на последний пост темы<br />
{postlink} - ссылка на пост<br />
{author} - имя автора<br />
{authorlink} - ссылка на автора<br />
{created} - дата создания поста<br />
{lastview} - дата последнего просмотра темы<br />
{lastsend} - дата последней отправки<br />
{text} - текст<br />
{name} - имя пользователя<br />
{cancel} - ссылка на отмену подписки';

			$subt='{site} - название сайта<br />
{sitelink} - ссылка на сайт<br />
{forum} - название форума<br />
{forumlink} - ссылка на форум<br />
{topic} - название темы<br />
{topiclink} - ссылка на тему<br />
{topicnewlink} - ссылка на первый непрочитанный пост темы<br />
{topiclastlink} - ссылка на последний пост темы<br />
{lastview} - дата последнего просмотра темы<br />
{lastsend} - дата последней отправки<br />
{cnt} - число новых сообщений<br />
{name} - имя пользователя<br />
{cancel} - ссылка на отмену подписки';

			$reputation='{site} - название сайта<br />
{sitelink} - ссылка на сайт<br />
{forum} - название форума<br />
{forumlink} - ссылка на форум<br />
{topic} - название темы<br />
{topiclink} - ссылка на тему<br />
{postlink} - ссылка на пост<br />
{name} - имя пользователя<br />
{author} - имя автора жалобы<br />
{authorlink} - ссылка на автора жалобы<br />
{text} - сопутствующий текст<br />
{points} - единицы изменения<br />
{current} - текущая репутация';

			$complaint='{site} - название сайта<br />
{sitelink} - ссылка на сайт<br />
{forumlink} - ссылка на форум<br />
{topiclink} - ссылка на тему<br />
{postlink} - ссылка на пост<br />
{username} - имя автора поста<br />
{userlink} - ссылка на автора поста<br />
{forum} - название форума<br />
{topic} - название темы<br />
{name} - имя пользователя модератора<br />
{complaint} - текст жалобы<br />
{author} - имя автора жалобы<br />
{authorlink} - ссылка на автора жалобы';

			$controls=array(
				'Уведомление о подписанной теме (немедленное)',
				'substi_t'=>array(
					'title'=>'Заголовок письма',
					'descr'=>$subt_i,
					'type'=>'input',
					'multilang'=>true,
					'bypost'=>&$post,
					'options'=>array(
						'htmlsafe'=>true,
					),
				),
				'substi'=>array(
					'title'=>'Текст письма',
					'descr'=>$subt_i,
					'type'=>'editor',
					'multilang'=>true,
					'bypost'=>&$post,
					'options'=>array(
						'checkout'=>false,
						'ownbb'=>false,
						'smiles'=>false,
					),
				),
				'Уведомление о подписанной теме (с задержкой)',
				'subst_t'=>array(
					'title'=>'Заголовок письма',
					'descr'=>$subt,
					'type'=>'input',
					'multilang'=>true,
					'bypost'=>&$post,
					'options'=>array(
						'htmlsafe'=>true,
					),
				),
				'subst'=>array(
					'title'=>'Текст письма',
					'descr'=>$subf,
					'type'=>'editor',
					'multilang'=>true,
					'bypost'=>&$post,
					'options'=>array(
						'checkout'=>false,
						'ownbb'=>false,
						'smiles'=>false,
					),
				),
				'Уведомление о подписанном форуме (немедленное)',
				'subsfi_t'=>array(
					'title'=>'Заголовок письма',
					'descr'=>$subf_i,
					'type'=>'input',
					'multilang'=>true,
					'bypost'=>&$post,
					'options'=>array(
						'htmlsafe'=>true,
					),
				),
				'subsfi'=>array(
					'title'=>'Текст письма',
					'descr'=>$subt_i,
					'type'=>'editor',
					'multilang'=>true,
					'bypost'=>&$post,
					'options'=>array(
						'checkout'=>false,
						'ownbb'=>false,
						'smiles'=>false,
					),
				),
				'Уведомление о подписанном форуме (с задержкой)',
				'subsf_t'=>array(
					'title'=>'Заголовок письма',
					'descr'=>$subt,
					'type'=>'input',
					'multilang'=>true,
					'bypost'=>&$post,
					'options'=>array(
						'htmlsafe'=>true,
					),
				),
				'subsf'=>array(
					'title'=>'Текст письма',
					'descr'=>$subt,
					'type'=>'editor',
					'multilang'=>true,
					'bypost'=>&$post,
					'options'=>array(
						'checkout'=>false,
						'ownbb'=>false,
						'smiles'=>false,
					),
				),
				'Уведомление модератору по кнопке "жалоба"',
				'complaint_t'=>array(
					'title'=>'Заголовок письма',
					'descr'=>$complaint,
					'type'=>'input',
					'multilang'=>true,
					'bypost'=>&$post,
					'options'=>array(
						'htmlsafe'=>true,
					),
				),
				'complaint'=>array(
					'title'=>'Текст письма',
					'descr'=>$complaint,
					'type'=>'editor',
					'multilang'=>true,
					'bypost'=>&$post,
					'options'=>array(
						'checkout'=>false,
						'ownbb'=>false,
						'smiles'=>false,
					),
				),
				'Уведомление об изменении репутации',
				'rep_t'=>array(
					'title'=>'Заголовок письма',
					'descr'=>$reputation,
					'type'=>'input',
					'multilang'=>true,
					'bypost'=>&$post,
					'options'=>array(
						'htmlsafe'=>true,
					),
				),
				'rep'=>array(
					'title'=>'Текст письма',
					'descr'=>$reputation,
					'type'=>'editor',
					'multilang'=>true,
					'bypost'=>&$post,
					'options'=>array(
						'checkout'=>false,
						'ownbb'=>false,
						'smiles'=>false,
					),
				),
			);

			$values=array();
			$multilang=Eleanor::$vars['multilang'] ? array_keys(Eleanor::$langs) : array(Language::$main);
			if($post)
			{
				$post=true;
				$letter=$Eleanor->Controls->SaveControls($controls);
				if(Eleanor::$vars['multilang'])
					foreach($multilang as &$lng)
					{
						$tosave=array();
						foreach($letter as $k=>&$v)
							$tosave[$k]=$controls[$k]['multilang'] ? Eleanor::FilterLangValues($v,$lng) : $v;
						$file=$Eleanor->module['path'].'letters-'.$lng.'.php';
						file_put_contents($file,'<?php return '.var_export($tosave,true).';');
					}
				else
				{
					$file=$Eleanor->module['path'].'letters-'.LANGUAGE.'.php';
					file_put_contents($file,'<?php return '.var_export($letter,true));
				}
			}
			else
				foreach($multilang as &$lng)
				{
					$letters=array();
					$file=$Eleanor->module['path'].'letters-'.$lng.'.php';
					$letter=file_exists($file) ? (array)include $file : array();
					$letter+=array(
						'substi_t'=>'',
						'substi'=>'',
						'subst_t'=>'',
						'subst'=>'',
						'subsfi_t'=>'',
						'subsfi'=>'',
						'subsf_t'=>'',
						'subsf'=>'',
						'complaint_t'=>'',
						'complaint'=>'',
						'rep_t'=>'',
						'rep'=>'',
					);
					foreach($letter as $k=>&$v)
						$values[$k]['value'][$lng]=$v;
				}
			$values=$Eleanor->Controls->DisplayControls($controls,$values)+$values;
			$title[]='Форматы писем';
			$c=Eleanor::$Template->Letters($controls,$values);
			Start();
			echo$c;
		break;
		case'users':
			$title[]='Пользователи форума';
			$page=isset($_GET['page']) ? (int)$_GET['page'] : 1;
			$info=$recount=$items=$where=array();
			$qs=array('do'=>'users');
			if(isset($_REQUEST['fi']) and is_array($_REQUEST['fi']))
			{
				if($post)
					$page=1;
				$qs['']['fi']=array();
				if(isset($_REQUEST['fi']['name'],$_REQUEST['fi']['namet']) and $_REQUEST['fi']['name']!='')
				{
					$name=Eleanor::$Db->Escape((string)$_REQUEST['fi']['name'],false);
					switch($_REQUEST['fi']['namet'])
					{
						case'b':
							$name=' LIKE \''.$name.'%\'';
						break;
						case'm':
							$name=' LIKE \'%'.$name.'%\'';
						break;
						case'e':
							$name=' LIKE \'%'.$name.'\'';
						break;
						default:
							$name='=\''.$name.'\'';
					}
					$qs['']['fi']['name']=$_REQUEST['fi']['name'];
					$qs['']['fi']['namet']=$_REQUEST['fi']['namet'];
					$where[]='`u`.`name`'.$name;
				}
				if(!empty($_REQUEST['fi']['id']))
				{
					$ints=explode(',',Tasks::FillInt($_REQUEST['fi']['id']));
					$qs['']['fi']['id']=(int)$_REQUEST['fi']['id'];
					$where['id']='`id`'.Eleanor::$Db->In($ints);
				}
				if(!empty($_REQUEST['fi']['regto']) and 0<$t=strtotime($_REQUEST['fi']['regto']))
				{
					$qs['']['fi']['regto']=$_REQUEST['fi']['regto'];
					$where[]='`u`.`register`<=\''.date('Y-m-d H:i:s',$t).'\'';
				}
				if(!empty($_REQUEST['fi']['regfrom']) and 0<$t=strtotime($_REQUEST['fi']['regfrom']))
				{
					$qs['']['fi']['regfrom']=$_REQUEST['fi']['regfrom'];
					$where[]='`u`.`register`>=\''.date('Y-m-d H:i:s',$t).'\'';
				}
				if(!empty($_REQUEST['fi']['ip']))
				{
					$qs['']['fi']['ip']=$_REQUEST['fi']['ip'];
					$ip=Eleanor::$Db->Escape($_REQUEST['fi']['ip'],false);
					$where[]='`ip` LIKE \''.str_replace('*','%',$ip).'\'';
				}
				if(!empty($_REQUEST['fi']['email']))
				{
					$qs['']['fi']['email']=$_REQUEST['fi']['email'];
					$email=Eleanor::$Db->Escape($_REQUEST['fi']['email'],false);
					$where[]='`email` LIKE \''.str_replace('*','%',$email).'\'';
				}
			}

			if(Eleanor::$our_query and isset($_POST['op'],$_POST['mass']) and is_array($_POST['mass']))
				switch($_POST['op'])
				{
					case'r':
						$recount=$Eleanor->Forum->Service->RecountReputation($_POST['mass']);
				}
			elseif(isset($_GET['recount']))
				$recount=explode(',',(string)$_GET['recount']);

			$where=$where ? ' WHERE '.join(' AND ',$where) : '';
			if(Eleanor::$Db===Eleanor::$UsersDb)
			{
				$table=USERS_TABLE;
				$where=' INNER JOIN `'.P.'users_site` USING(`id`)'.$where;
			}
			else
				$table=P.'users_site';

			$R=Eleanor::$Db->Query('SELECT COUNT(`id`) FROM `'.$table.'` `u` INNER JOIN `'.P.'users_extra` USING(`id`)'.$where);
			list($cnt)=$R->fetch_row();
			if($page<=0)
				$page=1;
			if(isset($_GET['new-pp']) and 4<$pp=(int)$_GET['new-pp'])
				Eleanor::SetCookie('per-page',$pp);
			else
				$pp=abs((int)Eleanor::GetCookie('per-page'));
			if($pp<5 or $pp>500)
				$pp=50;
			$offset=abs(($page-1)*$pp);
			if($cnt and $offset>=$cnt)
				$offset=max(0,$cnt-$pp);
			$sort=isset($_GET['sort']) ? (string)$_GET['sort'] : '';
			if(!in_array($sort,array('id','ip','name','email','group','full_name','last_visit','posts','rep')))
				$sort='';
			$so=$_SERVER['REQUEST_METHOD']!='POST' && $sort && isset($_GET['so']) ? $_GET['so'] : 'desc';
			if($so!='asc')
				$so='desc';
			if($sort)
				$qs+=array('sort'=>$sort,'so'=>$so);
			else
				$sort='id';
			$qs+=array('sort'=>false,'so'=>false);

			if($cnt>0)
			{
				$upref=$Eleanor->Url->file.'?section=management&amp;module=users&amp;';
				$myuid=Eleanor::$Login->GetUserValue('id');
				$R=Eleanor::$Db->Query('SELECT `id`,`u`.`full_name`,`u`.`name`,`email`,`ip`,`posts`,`rep` FROM `'.$table.'` `u` INNER JOIN `'.P.'users_extra` USING(`id`) INNER JOIN `'.$mc['fu'].'` USING(`id`)'.$where.' ORDER BY `'.$sort.'` '.$so.' LIMIT '.$offset.', '.$pp);
				while($a=$R->fetch_assoc())
				{
					$a['_arep']=$Eleanor->Url->Construct(array('do'=>'reputation',''=>array('fi'=>array('uto'=>$a['id']))));
					$a['_arecount']=$Eleanor->Url->Construct(array('recount'=>$a['id']));
					$a['_aedit']=$Eleanor->Url->Construct(array('edit-user'=>$a['id']));
					$a['_asedit']=$upref.'edit='.$a['id'];
					$a['_adel']=$myuid==$a['id'] ? false : $upref.'delete='.$a['id'];

					$items[$a['id']]=array_slice($a,1);
				}
			}

			$links=array(
				'sort_posts'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'posts','so'=>$qs['sort']=='posts' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'sort_rep'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'rep','so'=>$qs['sort']=='rep' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'sort_name'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'name','so'=>$qs['sort']=='name' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'sort_email'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'email','so'=>$qs['sort']=='email' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'sort_ip'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'ip','so'=>$qs['sort']=='ip' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'sort_id'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'id','so'=>$qs['sort']=='id' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'form_items'=>$Eleanor->Url->Construct($qs+array('page'=>$page>1 ? $page : false)),
				'pp'=>function($n)use($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('new-pp'=>$n)); },
				'first_page'=>$Eleanor->Url->Construct($qs),
				'pages'=>function($n)use($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('page'=>$n)); },
			);

			if($recount)
			{
				$R=Eleanor::$UsersDb->Query('SELECT `id`,`full_name`,`name` FROM `'.USERS_TABLE.'` WHERE `id`'.Eleanor::$UsersDb->In($recount));
				while($a=$R->fetch_assoc())
				{
					$a['_aedit']=$Eleanor->Url->Construct(array('edit-user'=>$a['id']));
					$info['REPUTATION'][ $a['id'] ]=array_slice($a,1);
				}
			}

			$c=Eleanor::$Template->UserList($items,$cnt,$pp,$page,$qs,$links,$info);
			Start();
			echo$c;
		break;
		case'moders':
			$title[]='Список модераторов';
			$page=isset($_GET['page']) ? (int)$_GET['page'] : 1;
			$items=$where=$groups=$users=$forums=array();
			$qs=array('do'=>'moders');
			if(isset($_REQUEST['fi']) and is_array($_REQUEST['fi']))
			{
				if($post)
					$page=1;
				$qs['']['fi']=array();
				if(!empty($_REQUEST['fi']['forums']))
				{
					$qs['']['fi']['forums']=is_array($_REQUEST['fi']['forums']) ? $_REQUEST['fi']['forums'] : explode(',',$_REQUEST['fi']['forums']);
					sort($qs['']['fi']['forums'],SORT_NUMERIC);
					$where[]='`forums` LIKE \'%,'.join(',%,',$qs['']['fi']['forums']).',%\'';
				}
			}

			if(Eleanor::$our_query and isset($_POST['op'],$_POST['mass']) and is_array($_POST['mass']))
			{
				$in=Eleanor::$Db->In($_POST['mass']);
				switch($_POST['op'])
				{
					case'k':
						$R=Eleanor::$Db->Query('SELECT `id`,`users`,`groups` FROM `'.$mc['fm'].'` WHERE `id`'.$in);
						while($a=$R->fetch_assoc())
						{
							if($a['forums'])
								Eleanor::$Db->Update($mc['f'],array('!moderators'=>'REPLACE(`moderators`,\','.$id.',\',\'\')'),'`id`'.Eleanor::$Db->In(explode(',,',trim($a['forums'],','))));
							if($a['users'])
								Eleanor::$Db->Update($mc['fu'],array('!moderator'=>'REPLACE(`moderator`,\','.$a['id'].',\',\'\')'),'`id`'.Eleanor::$Db->In(explode(',,',trim($a['users'],','))));
							if($a['groups'])
								Eleanor::$Db->Update($mc['fg'],array('!moderator'=>'REPLACE(`moderator`,\','.$a['id'].',\',\'\')'),'`id`'.Eleanor::$Db->In(explode(',,',trim($a['groups'],','))));
						}
						Eleanor::$Db->Delete($mc['fm'],'`id`'.$in);
				}
			}

			$where=$where ? ' WHERE '.join(' AND ',$where) : '';
			$R=Eleanor::$Db->Query('SELECT COUNT(`id`) FROM `'.$mc['fm'].'`'.$where);
			list($cnt)=$R->fetch_row();
			if($page<=0)
				$page=1;
			if(isset($_GET['new-pp']) and 4<$pp=(int)$_GET['new-pp'])
				Eleanor::SetCookie('per-page',$pp);
			else
				$pp=abs((int)Eleanor::GetCookie('per-page'));
			if($pp<5 or $pp>500)
				$pp=50;
			$offset=abs(($page-1)*$pp);
			if($cnt and $offset>=$cnt)
				$offset=max(0,$cnt-$pp);
			$sort=isset($_GET['sort']) ? (string)$_GET['sort'] : '';
			if(!in_array($sort,array('id','date')))
				$sort='';
			$so=$_SERVER['REQUEST_METHOD']!='POST' && $sort && isset($_GET['so']) ? $_GET['so'] : 'desc';
			if($so!='asc')
				$so='desc';
			if($sort)
				$qs+=array('sort'=>$sort,'so'=>$so);
			else
				$sort='id';
			$qs+=array('sort'=>false,'so'=>false);

			if($cnt>0)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`groups`,`users`,`date`,`forums` FROM `'.$mc['fm'].'`'.$where.' ORDER BY `'.$sort.'` '.$so.' LIMIT '.$offset.','.$pp);
				while($a=$R->fetch_assoc())
				{
					$a['_aedit']=$Eleanor->Url->Construct(array('edit-moder'=>$a['id']));
					$a['_adel']=$Eleanor->Url->Construct(array('delete-moder'=>$a['id']));
					$a['users']=$a['users'] ? explode(',,',trim($a['users'],',')) : array();
					$a['groups']=$a['groups'] ? explode(',,',trim($a['groups'],',')) : array();
					$a['forums']=$a['forums'] ? explode(',,',trim($a['forums'],',')) : array();
					if($a['groups'])
						$groups=array_merge($groups,$a['groups']);
					if($a['users'])
						$users=array_merge($users,$a['users']);
					if($a['forums'])
						$forums=array_merge($forums,$a['forums']);

					$items[$a['id']]=array_slice($a,1);
				}
			}
			if($users)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`groups` `group`,`name`,`full_name` FROM `'.P.'users_site` WHERE `id`'.Eleanor::$Db->In($users));
				$users=array();
				while($a=$R->fetch_assoc())
				{
					$a['_aedit']=$Eleanor->Url->Construct(array('edit-user'=>$a['id']));
					$a['group']=explode(',,',trim($a['group'],','));
					$a['group']=reset($a['group']);

					$groups[]=$a['group'];

					$users[$a['id']]=array_slice($a,1);
				}
			}
			if($groups)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`title_l` `title`,`html_pref`,`html_end` FROM `'.P.'groups` WHERE `id`'.Eleanor::$Db->In($groups));
				$groups=array();
				while($a=$R->fetch_assoc())
				{
					$a['_aedit']=$Eleanor->Url->Construct(array('edit-group'=>$a['id']));
					$a['title']=$a['title'] ? Eleanor::FilterLangValues((array)unserialize($a['title'])) : '';
					$groups[$a['id']]=$a;
				}
			}
			if($forums)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['fl'].'` WHERE `language` IN (\'\',\''.Eleanor::$Language.'\') AND `id`'.Eleanor::$Db->In($forums));
				$forums=array();
				while($a=$R->fetch_assoc())
				{
					$a['_aedit']=$Eleanor->Url->Construct(array('edit-forum'=>$a['id']));
					$forums[$a['id']]=array_slice($a,1);
				}
			}

			$links=array(
				'sort_date'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'date','so'=>$qs['sort']=='date' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'sort_id'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'id','so'=>$qs['sort']=='id' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'form_items'=>$Eleanor->Url->Construct($qs+array('page'=>$page)),
				'pp'=>function($n) use ($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('new-pp'=>$n)); },
				'first_page'=>$Eleanor->Url->Construct($qs),
				'pages'=>function($n)use($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('page'=>$n)); },
			);

			$fiforums=$Eleanor->Forum->Forums->SelectOptions(isset($qs['']['fi']['forums']) ? $qs['']['fi']['forums'] : array());
			if(isset($qs['']['fi']['forums']))
				$qs['']['fi']['forums']=join(',',$qs['']['fi']['forums']);
			$c=Eleanor::$Template->Moderators($items,$forums,$users,$groups,$fiforums,$cnt,$pp,$page,$qs,$links);
			Start();
			echo$c;
		break;
		case'files':
			$title[]='Список загруженных файлов';
			$page=isset($_GET['page']) ? (int)$_GET['page'] : 1;
			$items=$where=$forums=$topics=array();
			$qs=array('do'=>'files');
			if(isset($_REQUEST['fi']) and is_array($_REQUEST['fi']))
			{
				if($post)
					$page=1;
				$qs['']['fi']=array();
				if(!empty($_REQUEST['fi']['forums']))
				{
					$qs['']['fi']['forums']=is_array($_REQUEST['fi']['forums']) ? $_REQUEST['fi']['forums'] : explode(',',$_REQUEST['fi']['forums']);
					$where[]='`f`'.Eleanor::$Db->In($qs['']['fi']['forums']);
				}
			}

			if(Eleanor::$our_query and isset($_POST['op'],$_POST['mass']) and is_array($_POST['mass']))
				switch($_POST['op'])
				{
					case'k':
						$Eleanor->Forum->Moderate->DeleteAttach($_POST['mass']);
				}

			$where=$where ? ' WHERE '.join(' AND ',$where) : '';
			$R=Eleanor::$Db->Query('SELECT COUNT(`id`) FROM `'.$mc['fa'].'`'.$where);
			list($cnt)=$R->fetch_row();
			if($page<=0)
				$page=1;
			if(isset($_GET['new-pp']) and 4<$pp=(int)$_GET['new-pp'])
				Eleanor::SetCookie('per-page',$pp);
			else
				$pp=abs((int)Eleanor::GetCookie('per-page'));
			if($pp<5 or $pp>500)
				$pp=50;
			$offset=abs(($page-1)*$pp);
			if($cnt and $offset>=$cnt)
				$offset=max(0,$cnt-$pp);
			$sort=isset($_GET['sort']) ? (string)$_GET['sort'] : '';
			if(!in_array($sort,array('id')))
				$sort='';
			$so=$_SERVER['REQUEST_METHOD']!='POST' && $sort && isset($_GET['so']) ? $_GET['so'] : 'desc';
			if($so!='asc')
				$so='desc';
			if($sort)
				$qs+=array('sort'=>$sort,'so'=>$so);
			else
				$sort='id';
			$qs+=array('sort'=>false,'so'=>false);

			if($cnt>0)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`f`,`language`,`t`,`p`,`downloads`,`size`,`name`,`preview`,`date` FROM `'.$mc['fa'].'`'.$where.' ORDER BY `'.$sort.'` '.$so.' LIMIT '.$offset.','.$pp);
				while($a=$R->fetch_assoc())
				{
					$a['_adown']=Eleanor::$services['download']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'id'=>$a['id']));
					$a['_aprev']=$a['preview'] ? Eleanor::$services['download']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'id'=>$a['id'],'do'=>'preview')) : false;
					$a['_adel']=$Eleanor->Url->Construct(array('delete-attach'=>$a['id']));

					$topics[]=$a['t'];
					$forums[$a['f']][]=$a['language'];

					$items[$a['id']]=array_slice($a,1);
				}
			}

			if($forums)
			{
				$temp=array();
				$R=Eleanor::$Db->Query('SELECT `id`,`language`,`title` FROM `'.$mc['fl'].'` WHERE `id`'.Eleanor::$Db->In(array_keys($forums)));
				while($a=$R->fetch_assoc())
					if(in_array($a['language'],$forums[ $a['id'] ]))
					{
						$a['_a']=Eleanor::$services['user']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'f'=>$a['id']));
						$a['_aedit']=$Eleanor->Url->Construct(array('edit-forum'=>$a['id']));
						$temp[ $a['id'] ][ $a['language'] ]=array_slice($a,2);
					}
				$forums=$temp;
			}

			if($topics)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['ft'].'` WHERE `id`'.Eleanor::$Db->In($topics));
				$topics=array();
				while($a=$R->fetch_assoc())
				{
					$a['_a']=Eleanor::$services['user']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'t'=>$a['id']));
					$topics[ $a['id'] ]=array_slice($a,1);
				}
			}

			$links=array(
				'sort_date'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'date','so'=>$qs['sort']=='date' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'sort_id'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'id','so'=>$qs['sort']=='id' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'form_items'=>$Eleanor->Url->Construct($qs+array('page'=>$page)),
				'pp'=>function($n) use ($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('new-pp'=>$n)); },
				'first_page'=>$Eleanor->Url->Construct($qs),
				'pages'=>function($n)use($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('page'=>$n)); },
			);

			$fiforums=$Eleanor->Forum->Forums->SelectOptions(isset($qs['']['fi']['forums']) ? $qs['']['fi']['forums'] : array());
			if(isset($qs['']['fi']['forums']))
				$qs['']['fi']['forums']=join(',',$qs['']['fi']['forums']);
			$s=Eleanor::$Template->Files($items,$forums,$topics,$fiforums,$cnt,$pp,$page,$qs,$links);
			Start();
			echo$s;
		break;
		case'prefixes':
			$title[]='Список префиксов';
			$page=isset($_GET['page']) ? (int)$_GET['page'] : 1;
			$items=$where=$forums=array();
			$qs=array('do'=>'prefixes');
			if(isset($_REQUEST['fi']) and is_array($_REQUEST['fi']))
			{
				if($post)
					$page=1;
				$qs['']['fi']=array();
				if(!empty($_REQUEST['fi']['title']))
				{
					$qs['']['fi']['email']=$_REQUEST['fi']['title'];
					$where[]='`title` LIKE \''.Eleanor::$Db->Escape($_REQUEST['fi']['title']).'\'';
				}
				if(!empty($_REQUEST['fi']['forums']))
				{
					$qs['']['fi']['forums']=is_array($_REQUEST['fi']['forums']) ? $_REQUEST['fi']['forums'] : explode(',',$_REQUEST['fi']['forums']);
					sort($qs['']['fi']['forums'],SORT_NUMERIC);
					$where[]='`forums` LIKE \'%,'.join(',%,',$qs['']['fi']['forums']).',%\'';
				}
			}

			if(Eleanor::$our_query and isset($_POST['op'],$_POST['mass']) and is_array($_POST['mass']))
			{
				$in=Eleanor::$Db->In($_POST['mass']);
				switch($_POST['op'])
				{
					case'k':
						Eleanor::$Db->Delete($mc['pl'],'`id`'.$in);
						Eleanor::$Db->Delete($mc['pr'],'`id`'.$in);
				}
			}

			$where=$where ? ' AND '.join(' AND ',$where) : '';
			$R=Eleanor::$Db->Query('SELECT COUNT(`id`) FROM `'.$mc['pr'].'` INNER JOIN `'.$mc['pl'].'` USING(`id`) WHERE `language`IN(\'\',\''.Language::$main.'\')'.$where);
			list($cnt)=$R->fetch_row();
			if($page<=0)
				$page=1;
			if(isset($_GET['new-pp']) and 4<$pp=(int)$_GET['new-pp'])
				Eleanor::SetCookie('per-page',$pp);
			else
				$pp=abs((int)Eleanor::GetCookie('per-page'));
			if($pp<5 or $pp>500)
				$pp=50;
			$offset=abs(($page-1)*$pp);
			if($cnt and $offset>=$cnt)
				$offset=max(0,$cnt-$pp);
			$sort=isset($_GET['sort']) ? (string)$_GET['sort'] : '';
			if(!in_array($sort,array('id','title')))
				$sort='';
			$so=$_SERVER['REQUEST_METHOD']!='POST' && $sort && isset($_GET['so']) ? $_GET['so'] : 'desc';
			if($so!='asc')
				$so='desc';
			if($sort)
				$qs+=array('sort'=>$sort,'so'=>$so);
			else
				$sort='id';
			$qs+=array('sort'=>false,'so'=>false);

			if($cnt>0)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`title`,`forums` FROM `'.$mc['pr'].'` INNER JOIN `'.$mc['pl'].'` USING(`id`) WHERE `language`IN(\'\',\''.Language::$main.'\')'.$where.' ORDER BY `'.$sort.'` '.$so.' LIMIT '.$offset.','.$pp);
				while($a=$R->fetch_assoc())
				{
					$a['_aedit']=$Eleanor->Url->Construct(array('edit-prefix'=>$a['id']));
					$a['_adel']=$Eleanor->Url->Construct(array('delete-prefix'=>$a['id']));
					$a['forums']=$a['forums'] ? explode(',,',trim($a['forums'],',')) : array();
					if($a['forums'])
						$forums=array_merge($forums,$a['forums']);

					$items[$a['id']]=array_slice($a,1);
				}
			}

			if($forums)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['fl'].'` WHERE `language` IN (\'\',\''.Eleanor::$Language.'\') AND `id`'.Eleanor::$Db->In($forums));
				$forums=array();
				while($a=$R->fetch_assoc())
				{
					$a['_aedit']=$Eleanor->Url->Construct(array('edit-forum'=>$a['id']));
					$forums[$a['id']]=array_slice($a,1);
				}
			}

			$links=array(
				'sort_title'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'title','so'=>$qs['sort']=='title' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'sort_id'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'id','so'=>$qs['sort']=='id' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'form_items'=>$Eleanor->Url->Construct($qs+array('page'=>$page)),
				'pp'=>function($n) use ($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('new-pp'=>$n)); },
				'first_page'=>$Eleanor->Url->Construct($qs),
				'pages'=>function($n)use($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('page'=>$n)); },
			);

			$fiforums=$Eleanor->Forum->Forums->SelectOptions(isset($qs['']['fi']['forums']) ? $qs['']['fi']['forums'] : array());
			if(isset($qs['']['fi']['forums']))
				$qs['']['fi']['forums']=join(',',$qs['']['fi']['forums']);
			$c=Eleanor::$Template->Prefixes($items,$forums,$fiforums,$cnt,$pp,$page,$qs,$links);
			Start();
			echo$c;
		break;
		case'fsubscriptions':
			$title[]='Cписок подписок на форумы';
			$page=isset($_GET['page']) ? (int)$_GET['page'] : 1;
			$items=$where=$forums=$users=array();
			$qs=array('do'=>'fsubscriptions');
			$fiuname='';
			if(isset($_REQUEST['fi']) and is_array($_REQUEST['fi']))
			{
				if($post)
					$page=1;
				$qs['']['fi']=array();
				if(!empty($_REQUEST['fi']['forums']))
				{
					$qs['']['fi']['forums']=is_array($_REQUEST['fi']['forums']) ? $_REQUEST['fi']['forums'] : explode(',',$_REQUEST['fi']['forums']);
					$where[]='`f`'.Eleanor::$Db->In($qs['']['fi']['forums']);
				}
				if(!empty($_REQUEST['fi']['u']))
				{
					$uid=(int)$_REQUEST['fi']['u'];
					$R=Eleanor::$UsersDb->Query('SELECT `name` FROM `'.USERS_TABLE.'` WHERE `id`='.$uid.' LIMIT 1');
					if(list($fiuname)=$R->fetch_row())
					{
						$qs['']['fi']['u']=$uid;
						$where[]='`uid`='.$uid;
					}
				}
			}

			if(Eleanor::$our_query and isset($_POST['op'],$_POST['mass']) and is_array($_POST['mass']))
				switch($_POST['op'])
				{
					case'k':
						foreach($_POST['mass'] as $v)
						{
							#fid-language-user
							$v=explode('-',$v,3);
							if(count($v)==3)
								$Eleanor->Forum->Subscriptions->SubscribeForum($v[0],$v[1],$v[2],false);
						}
				}

			$where=$where ? ' WHERE '.join(' AND ',$where) : '';
			$R=Eleanor::$Db->Query('SELECT COUNT(`f`) FROM `'.$mc['fs'].'`'.$where);
			list($cnt)=$R->fetch_row();
			if($page<=0)
				$page=1;
			if(isset($_GET['new-pp']) and 4<$pp=(int)$_GET['new-pp'])
				Eleanor::SetCookie('per-page',$pp);
			else
				$pp=abs((int)Eleanor::GetCookie('per-page'));
			if($pp<5 or $pp>500)
				$pp=50;
			$offset=abs(($page-1)*$pp);
			if($cnt and $offset>=$cnt)
				$offset=max(0,$cnt-$pp);
			$sort=isset($_GET['sort']) ? (string)$_GET['sort'] : '';
			if(!in_array($sort,array('date','')))
				$sort='';
			$so=$_SERVER['REQUEST_METHOD']!='POST' && $sort && isset($_GET['so']) ? $_GET['so'] : 'desc';
			if($so!='asc')
				$so='desc';
			if($sort)
				$qs+=array('sort'=>$sort,'so'=>$so);
			else
				$sort='';
			$qs+=array('sort'=>false,'so'=>false);

			if($cnt>0)
			{
				$R=Eleanor::$Db->Query('SELECT `date`,`f`,`uid`,`language`,`sent`,`lastsend`,`nextsend`,`intensity` FROM `'.$mc['fs'].'`'.$where.($sort ? ' ORDER BY `'.$sort.'` '.$so : '').' LIMIT '.$offset.','.$pp);
				while($a=$R->fetch_assoc())
				{
					$a['_adel']=$Eleanor->Url->Construct(array('delete-fs'=>$a['id'],'u'=>$a['uid'],'l'=>$a['language']));

					$forums[$a['f']][]=$a['language'];
					$users[]=$a['uid'];

					$items[]=$a;
				}
			}

			if($users)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`name`,`full_name` FROM `'.P.'users_site` WHERE `id`'.Eleanor::$Db->In($users));
				$users=array();
				while($a=$R->fetch_assoc())
				{
					$a['_aedit']=$Eleanor->Url->Construct(array('edit-user'=>$a['id']));
					$users[$a['id']]=array_slice($a,1);
				}
			}

			if($forums)
			{
				$temp=array();
				$R=Eleanor::$Db->Query('SELECT `id`,`language`,`title` FROM `'.$mc['fl'].'` WHERE `id`'.Eleanor::$Db->In(array_keys($forums)));
				while($a=$R->fetch_assoc())
					if(in_array($a['language'],$forums[ $a['id'] ]))
					{
						$a['_a']=Eleanor::$services['user']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'f'=>$a['id']));
						$a['_aedit']=$Eleanor->Url->Construct(array('edit-forum'=>$a['id']));
						$temp[ $a['id'] ][ $a['language'] ]=array_slice($a,2);
					}
				$forums=$temp;
			}

			$links=array(
				'sort_date'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'date','so'=>$qs['sort']=='date' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'form_items'=>$Eleanor->Url->Construct($qs+array('page'=>$page)),
				'pp'=>function($n) use ($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('new-pp'=>$n)); },
				'first_page'=>$Eleanor->Url->Construct($qs),
				'pages'=>function($n)use($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('page'=>$n)); },
			);

			$fiforums=$Eleanor->Forum->Forums->SelectOptions(isset($qs['']['fi']['forums']) ? $qs['']['fi']['forums'] : array());
			if(isset($qs['']['fi']['forums']))
				$qs['']['fi']['forums']=join(',',$qs['']['fi']['forums']);

			$s=Eleanor::$Template->FSubscriptions($items,$forums,$users,$fiforums,$fiuname,$cnt,$pp,$page,$qs,$links);
			Start();
			echo$s;
		break;
		case'tsubscriptions':
			$title[]='Cписок подписок на темы';
			$page=isset($_GET['page']) ? (int)$_GET['page'] : 1;
			$items=$where=$forums=$users=$topics=$fitopic=array();
			$qs=array('do'=>'tsubscriptions');
			$fiuname='';
			if(isset($_REQUEST['fi']) and is_array($_REQUEST['fi']))
			{
				if($post)
					$page=1;
				$qs['']['fi']=array();
				if(!empty($_REQUEST['fi']['forums']))
				{
					$qs['']['fi']['forums']=is_array($_REQUEST['fi']['forums']) ? $_REQUEST['fi']['forums'] : explode(',',$_REQUEST['fi']['forums']);
					$where[]='`f`'.Eleanor::$Db->In($qs['']['fi']['forums']);
				}
				if(!empty($_REQUEST['fi']['u']))
				{
					$uid=(int)$_REQUEST['fi']['u'];
					$R=Eleanor::$UsersDb->Query('SELECT `name` FROM `'.USERS_TABLE.'` WHERE `id`='.$uid.' LIMIT 1');
					if(list($fiuname)=$R->fetch_row())
					{
						$qs['']['fi']['u']=$uid;
						$where[]='`uid`='.$uid;
					}
				}
				if(!empty($_REQUEST['fi']['t']))
				{
					$id=(int)$_REQUEST['fi']['t'];
					$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['ft'].'` WHERE `id`='.$id.' LIMIT 1');
					if($fitopic=$R->fetch_assoc())
					{
						$fitopic['_a']=Eleanor::$services['user']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'t'=>$id));
						$qs['']['fi']['t']=$id;
						$where[]='`f`='.$id;
					}
				}
			}

			if(Eleanor::$our_query and isset($_POST['op'],$_POST['mass']) and is_array($_POST['mass']))
				switch($_POST['op'])
				{
					case'k':
						foreach($_POST['mass'] as $v)
						{
							#tid-user
							$v=explode('-',$v,3);
							if(count($v)==2)
								$Eleanor->Forum->Subscriptions->SubscribeTopic($v[0],$v[1],false);
						}
				}

			$where=$where ? ' WHERE '.join(' AND ',$where) : '';
			$R=Eleanor::$Db->Query('SELECT COUNT(`t`) FROM `'.$mc['ts'].'`'.$where);
			list($cnt)=$R->fetch_row();
			if($page<=0)
				$page=1;
			if(isset($_GET['new-pp']) and 4<$pp=(int)$_GET['new-pp'])
				Eleanor::SetCookie('per-page',$pp);
			else
				$pp=abs((int)Eleanor::GetCookie('per-page'));
			if($pp<5 or $pp>500)
				$pp=50;
			$offset=abs(($page-1)*$pp);
			if($cnt and $offset>=$cnt)
				$offset=max(0,$cnt-$pp);
			$sort=isset($_GET['sort']) ? (string)$_GET['sort'] : '';
			if(!in_array($sort,array('date','')))
				$sort='';
			$so=$_SERVER['REQUEST_METHOD']!='POST' && $sort && isset($_GET['so']) ? $_GET['so'] : 'desc';
			if($so!='asc')
				$so='desc';
			if($sort)
				$qs+=array('sort'=>$sort,'so'=>$so);
			else
				$sort='';
			$qs+=array('sort'=>false,'so'=>false);

			if($cnt>0)
			{
				$R=Eleanor::$Db->Query('SELECT `date`,`t`,`uid`,`sent`,`lastsend`,`nextsend`,`intensity`,`t`,`language` FROM `'.$mc['fs'].'`'.$where.($sort ? ' ORDER BY `'.$sort.'` '.$so : '').' LIMIT '.$offset.','.$pp);
				while($a=$R->fetch_assoc())
				{
					$a['_adel']=$Eleanor->Url->Construct(array('delete-ts'=>$a['id'],'u'=>$a['uid']));

					$forums[$a['f']][]=$a['language'];
					$users[]=$a['uid'];
					$topics[]=$a['t'];

					$items[]=$a;
				}
			}

			if($users)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`name`,`full_name` FROM `'.P.'users_site` WHERE `id`'.Eleanor::$Db->In($users));
				$users=array();
				while($a=$R->fetch_assoc())
				{
					$a['_aedit']=$Eleanor->Url->Construct(array('edit-user'=>$a['id']));
					$users[$a['id']]=array_slice($a,1);
				}
			}

			if($topics)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['ft'].'` WHERE `id`'.Eleanor::$Db->In($topics));
				$topics=array();
				while($a=$R->fetch_assoc())
				{
					$a['_a']=Eleanor::$services['user']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'t'=>$a['id']));
					$topics[ $a['id'] ]=array_slice($a,1);
				}
			}

			if($forums)
			{
				$temp=array();
				$R=Eleanor::$Db->Query('SELECT `id`,`language`,`title` FROM `'.$mc['fl'].'` WHERE `id`'.Eleanor::$Db->In(array_keys($forums)));
				while($a=$R->fetch_assoc())
					if(in_array($a['language'],$forums[ $a['id'] ]))
					{
						$a['_a']=Eleanor::$services['user']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'f'=>$a['id']));
						$a['_aedit']=$Eleanor->Url->Construct(array('edit-forum'=>$a['id']));
						$temp[ $a['id'] ][ $a['language'] ]=array_slice($a,2);
					}
				$forums=$temp;
			}

			$links=array(
				'sort_date'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'date','so'=>$qs['sort']=='date' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'form_items'=>$Eleanor->Url->Construct($qs+array('page'=>$page)),
				'pp'=>function($n) use ($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('new-pp'=>$n)); },
				'first_page'=>$Eleanor->Url->Construct($qs),
				'pages'=>function($n)use($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('page'=>$n)); },
			);

			$fiforums=$Eleanor->Forum->Forums->SelectOptions(isset($qs['']['fi']['forums']) ? $qs['']['fi']['forums'] : array());
			if(isset($qs['']['fi']['forums']))
				$qs['']['fi']['forums']=join(',',$qs['']['fi']['forums']);

			$s=Eleanor::$Template->TSubscriptions($items,$forums,$users,$topics,$fiforums,$fiuname,$fitopic,$cnt,$pp,$page,$qs,$links);
			Start();
			echo$s;
		break;
		case'reputation':
			$title[]='Cписок изменений репутации';
			$page=isset($_GET['page']) ? (int)$_GET['page'] : 1;
			$items=$where=$forums=$users=$topics=$fitopic=array();
			$qs=array('do'=>'reputation');
			$fiutoname=$fiufromname='';
			if(isset($_REQUEST['fi']) and is_array($_REQUEST['fi']))
			{
				if($post)
					$page=1;
				$qs['']['fi']=array();
				if(!empty($_REQUEST['fi']['forums']))
				{
					$qs['']['fi']['forums']=is_array($_REQUEST['fi']['forums']) ? $_REQUEST['fi']['forums'] : explode(',',$_REQUEST['fi']['forums']);
					$where[]='`f`'.Eleanor::$Db->In($qs['']['fi']['forums']);
				}
				if(!empty($_REQUEST['fi']['uto']))
				{
					$uid=(int)$_REQUEST['fi']['uto'];
					$R=Eleanor::$UsersDb->Query('SELECT `name` FROM `'.USERS_TABLE.'` WHERE `id`='.$uid.' LIMIT 1');
					if(list($fiuname)=$R->fetch_row())
					{
						$qs['']['fi']['uto']=$uid;
						$where[]='`uid`='.$uid;
					}
				}
				if(!empty($_REQUEST['fi']['ufrom']))
				{
					$uid=(int)$_REQUEST['fi']['ufrom'];
					$R=Eleanor::$UsersDb->Query('SELECT `name` FROM `'.USERS_TABLE.'` WHERE `id`='.$uid.' LIMIT 1');
					if(list($fiufromname)=$R->fetch_row())
					{
						$qs['']['fi']['ufrom']=$uid;
						$where[]='`uid`='.$uid;
					}
				}
				if(!empty($_REQUEST['fi']['t']))
				{
					$id=(int)$_REQUEST['fi']['t'];
					$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['ft'].'` WHERE `id`='.$id.' LIMIT 1');
					if($fitopic=$R->fetch_assoc())
					{
						$fitopic['_a']=Eleanor::$services['user']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'t'=>$id));
						$qs['']['fi']['t']=$id;
						$where[]='`f`='.$id;
					}
				}
			}

			if(Eleanor::$our_query and isset($_POST['op'],$_POST['mass']) and is_array($_POST['mass']))
				switch($_POST['op'])
				{
					case'k':
						$Eleanor->Forum->Moderate->DeleteReputation($_POST['mass']);
				}

			$where=$where ? ' WHERE '.join(' AND ',$where) : '';
			$R=Eleanor::$Db->Query('SELECT COUNT(`id`) FROM `'.$mc['fr'].'`'.$where);
			list($cnt)=$R->fetch_row();
			if($page<=0)
				$page=1;
			if(isset($_GET['new-pp']) and 4<$pp=(int)$_GET['new-pp'])
				Eleanor::SetCookie('per-page',$pp);
			else
				$pp=abs((int)Eleanor::GetCookie('per-page'));
			if($pp<5 or $pp>500)
				$pp=50;
			$offset=abs(($page-1)*$pp);
			if($cnt and $offset>=$cnt)
				$offset=max(0,$cnt-$pp);
			$sort=isset($_GET['sort']) ? (string)$_GET['sort'] : '';
			if($sort!='id')
				$sort='';
			$so=$_SERVER['REQUEST_METHOD']!='POST' && $sort && isset($_GET['so']) ? $_GET['so'] : 'desc';
			if($so!='asc')
				$so='desc';
			if($sort)
				$qs+=array('sort'=>$sort,'so'=>$so);
			else
				$sort='id';
			$qs+=array('sort'=>false,'so'=>false);

			if($cnt>0)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`from`,`from_name`,`to`,`p`,`t`,`f`,`language`,`text`,`comment`,`value` FROM `'.$mc['fr'].'`'.$where.' ORDER BY `'.$sort.'` '.$so.' LIMIT '.$offset.','.$pp);
				while($a=$R->fetch_assoc())
				{
					$a['_adel']=$Eleanor->Url->Construct(array('delete-reputation'=>$a['id'],'u'=>$a['uid']));
					$a['_a']=Eleanor::$services['user']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'findpost'=>$a['p']));

					$forums[$a['f']][]=$a['language'];
					$topics[]=$a['t'];
					$users=array_merge($users,array($a['from'],$a['to']));

					$items[ $a['id'] ]=array_slice($a,1);
				}
			}

			if($users)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`name`,`full_name` FROM `'.P.'users_site` WHERE `id`'.Eleanor::$Db->In($users));
				$users=array();
				while($a=$R->fetch_assoc())
				{
					$a['_aedit']=$Eleanor->Url->Construct(array('edit-user'=>$a['id']));
					$users[$a['id']]=array_slice($a,1);
				}
			}

			if($topics)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['ft'].'` WHERE `id`'.Eleanor::$Db->In($topics));
				$topics=array();
				while($a=$R->fetch_assoc())
				{
					$a['_a']=Eleanor::$services['user']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'t'=>$a['id']));
					$topics[ $a['id'] ]=array_slice($a,1);
				}
			}

			if($forums)
			{
				$temp=array();
				$R=Eleanor::$Db->Query('SELECT `id`,`language`,`title` FROM `'.$mc['fl'].'` WHERE `id`'.Eleanor::$Db->In(array_keys($forums)));
				while($a=$R->fetch_assoc())
					if(in_array($a['language'],$forums[ $a['id'] ]))
					{
						$a['_a']=Eleanor::$services['user']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'f'=>$a['id']));
						$a['_aedit']=$Eleanor->Url->Construct(array('edit-forum'=>$a['id']));
						$temp[ $a['id'] ][ $a['language'] ]=array_slice($a,2);
					}
				$forums=$temp;
			}

			$links=array(
				'sort_id'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'id','so'=>$qs['sort']=='id' && $qs['so']=='asc' ? 'desc' : 'asc'))),
				'form_items'=>$Eleanor->Url->Construct($qs+array('page'=>$page)),
				'pp'=>function($n) use ($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('new-pp'=>$n)); },
				'first_page'=>$Eleanor->Url->Construct($qs),
				'pages'=>function($n)use($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('page'=>$n)); },
			);

			$fiforums=$Eleanor->Forum->Forums->SelectOptions(isset($qs['']['fi']['forums']) ? $qs['']['fi']['forums'] : array());
			if(isset($qs['']['fi']['forums']))
				$qs['']['fi']['forums']=join(',',$qs['']['fi']['forums']);

			$s=Eleanor::$Template->Reputation($items,$forums,$users,$topics,$fiforums,$fiutoname,$fiufromname,$fitopic,$cnt,$pp,$page,$qs,$links);
			Start();
			echo$s;
		break;
		case'tasks':
			$title[]='Обслуживание форума';
			$values=$statuses=$errors=$forums=array();
			if($post)
			{
				$run=false;
				#Пересчет тем в форумах
				if(isset($_POST['rectop']))
				{
					#ToDo!
					$errors['rectop']='В разработке...';
				}

				#Пересчет постов в темах
				if(isset($_POST['recpostsf'],$_POST['recpostst']))
				{
					#ToDo!
					$errors['recposts']='В разработке...';
				}

				#Пересчет постов пользователей
				if(isset($_POST['recuserposts']))
				{
					#ToDo!
					$errors['recuserposts']='В разработке...';
				}

				#Обновление последнего ответившего в тему
				if(isset($_POST['recpostsf'],$_POST['recpostst']))
				{
					#ToDo!
					$errors['recposts']='В разработке...';
				}

				#Обновление последнего ответившего в форум
				if(isset($_POST['lastpostforum']))
				{
					#ToDo!
					$errors['lastpostforum']='В разработке...';
				}

				#Удаление мертвых файлов
				if(isset($_POST['removefilesp']))
				{
					#ToDo!
					$errors['removefiles']='В разработке...';
				}

				#Синхронизация пользователей
				if(isset($_POST['syncusersdate']))
				{
					$date=(string)$_POST['syncusersdate'];
					$err=false;

					if($date!=='' and !strtotime($date))
						$errors['syncusers'][]='SYNCUSERS_DATE';

					if(!isset($errors['syncusers']))
						$run=array(
							'type'=>'syncusers',
							'!start'=>'NOW()',
							'!date'=>'NOW()',
							'status'=>'wait',
							'options'=>$date ? serialize(array('date'=>$date)) : '',
						);
				}

				if($run)
				{
					$R=Eleanor::$Db->Query('SELECT `id` FROM `'.P.'tasks` WHERE `name`=\'module_forum\' LIMIT 1');
					if($R->num_rows)
						list($tid)=$R->fetch_row();
					else
						$tid=false;

					$task=array();
					if(!$tid)
					{
						$do=date_offset_get(date_create());
						$task+=array(
							'task'=>'special_forum.php',
							'title_l'=>serialize(array(''=>'Модуль форума')),
							'name'=>'module_forum',
							'do'=>$do,
						);
					}
					$task+=array(
						'nextrun'=>date('Y-m-d H:i:s',Tasks::CalcNextRun()),
						'ondone'=>'delete',
						'maxrun'=>1,
						'status'=>1,
						'run_year'=>'*',
						'run_month'=>'*',
						'run_day'=>'*',
						'run_hour'=>'*',
						'run_minute'=>'*',
						'run_second'=>'*',
					);

					Eleanor::$Db->Insert($mc['ta'],$run);
					if($tid)
						Eleanor::$Db->Update(P.'tasks',$task,'`id`='.$tid.' LIMIT 1');
					else
						Eleanor::$Db->Replace(P.'tasks',$task);
					Tasks::UpdateNextRun();
				}
			}

			if($forums)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['fl'].'` WHERE `language` IN (\'\',\''.Eleanor::$Language.'\') AND `id`'.Eleanor::$Db->In($forums));
				$forums=array();
				while($a=$R->fetch_assoc())
				{
					$a['_aedit']=$Eleanor->Url->Construct(array('edit-forum'=>$a['id']));
					$forums[$a['id']]=array_slice($a,1);
				}
			}

			$values+=array(
				'recpostst'=>'',#ИДы тем для пересчета постов
				'recuserposts'=>'',#ИДы пользователей для пересчета постов
				'lastposttopict'=>'',#ИДы тем для поиска последнего поста
				'removefilesp'=>'',#ИДы постов для поиска мертвых файлов
				'syncusersdate'=>'',#Начальная дата синхронизации пользователей

				'recpostsf'=>array(),
				'recpostsf'=>array(),
				'lastposttopicf'=>array(),
				'lastpostforum'=>array(),
			);

			$q='';
			foreach(array('rectop','recposts','recuserposts','lastposttopic','lastpostforum','removefiles','syncusers') as $v)
				$q.='(SELECT `id`,`type`,`start`,`date`,`finish`,`status`,`options`,`done`,`total` FROM `'.$mc['ta'].'` WHERE `type`=\''.$v.'\' ORDER BY `date` DESC LIMIT 3)UNION ALL';
			$R=Eleanor::$Db->Query(substr($q,0,-9));
			while($a=$R->fetch_assoc())
			{
				$a['options']=$a['options'] ? (array)unserialize($a['options']) : array();
				$statuses[ $a['type'] ][ $a['id'] ]=array_slice($a,2);
			}

			$opforums=array(
				'rectop'=>$Eleanor->Forum->Forums->SelectOptions($values['recpostsf']),
				'recpostsf'=>$Eleanor->Forum->Forums->SelectOptions($values['recpostsf']),
				'lastposttopicf'=>$Eleanor->Forum->Forums->SelectOptions($values['lastposttopicf']),
				'lastpostforum'=>$Eleanor->Forum->Forums->SelectOptions($values['lastpostforum']),
			);

			$s=Eleanor::$Template->Tasks($values,$statuses,$opforums,$errors,$forums);
			Start();
			echo$s;
		break;
		case'options':
			$Eleanor->Url->SetPrefix(array('do'=>'options'),true);
			$c=$Eleanor->Settings->GetInterface('group',$mc['opts']);
			if($c)
			{
				$c=Eleanor::$Template->Options($c);
				Start();
				echo$c;
			}
		break;
		default:
			Forums();
	}
elseif(isset($_GET['g'],$_GET['f']))
{
	$g=(int)$_GET['g'];
	$f=(int)$_GET['f'];
	$R=Eleanor::$Db->Query('SELECT `id`,`title`,`parent`,`parents`,`permissions` FROM `'.$mc['f'].'` INNER JOIN `'.$mc['fl'].'` USING(`id`) WHERE `language` IN (\'\',\''.Eleanor::$Language.'\') AND `id`='.$f.' LIMIT 1');
	if($forum=$R->fetch_assoc())
		$forum['permissions']=$forum['permissions'] ? (array)unserialize($forum['permissions']) : array();
	else
		return GoAway();

	$R=Eleanor::$Db->Query('SELECT `id`,`title_l` `title`,`html_pref`,`html_end` FROM `'.P.'groups` WHERE `id`='.$g.' LIMIT 1');
	if($group=$R->fetch_assoc())
		$group['title']=$group['title'] ? Eleanor::FilterLangValues((array)unserialize($group['title'])) : '';
	else
		return GoAway();

	$navi=array();
	if($forum['parents'])
	{
		$parents=explode(',',rtrim($forum['parents'],','));
		$temp=array();
		$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['f'].'` INNER JOIN `'.$mc['fl'].'` USING(`id`) WHERE `language` IN (\'\',\''.Eleanor::$Language.'\') AND `id`'.Eleanor::$Db->In($parents));
		while($a=$R->fetch_assoc())
			$temp[$a['id']]=$a['title'];
		foreach($parents as &$v)
			if(isset($temp[$v]))
				$navi[$v]=array('title'=>$temp[$v],'_afgr'=>$Eleanor->Url->Construct(array('f'=>$v,'g'=>$g)));
	}

	$R=Eleanor::$Db->Query('SELECT `permissions` FROM `'.$mc['f'].'` WHERE `parents` LIKE \''.$forum['parents'].$forum['id'].',%\' LIMIT 1');

	$haschild=$R->num_rows>0;
	$inherits=true;
	$errors=$inherit=array();
	$saved=$bypost=false;

	while($a=$R->fetch_assoc())
	{
		$a['permissions']=$a['permissions'] ? (array)unserialize($a['permissions']) : false;
		if($a['permissions'] and isset($a['permissions'][$g]) and is_array($a['permissions'][$g]))
			foreach($a['permissions'][$g] as &$v)
				if($v!==null)
				{
					$inherits=false;
					break;
				}
	}

	$controls=GroupsControls($post,2);
	if($post)
	{
		$co=$controls;
		$inherit=isset($_POST['inherit']) ? (array)$_POST['inherit'] : array();
		foreach($inherit as &$v)
			unset($co[$v]);
		try
		{
			$co=$co ? $Eleanor->Controls->SaveControls($co) : array();
		}
		catch(EE$E)
		{
			$errors['ERROR']=$E->getMessage();
		}

		if(!$errors)
		{
			if($co)
				$forum['permissions'][$g]=$co;
			else
				unset($forum['permissions'][$g]);

			Eleanor::$Db->Update($mc['f'],array('permissions'=>$forum['permissions'] ? serialize($forum['permissions']) : ''),'`id`='.$f.' LIMIT 1');
			if($haschild and isset($_POST['subs']))
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`permissions` FROM `'.$mc['f'].'` WHERE `parents` LIKE \''.$forum['parents'].$f.',%\' LIMIT 1');
				while($a=$R->fetch_assoc())
				{
					$a['permissions']=$a['permissions'] ? (array)unserialize($a['permissions']) : array();
					$a['permissions'][$g]=$co.find(":first");
					Eleanor::$Db->Update($mc['f'],array('permissions'=>$a['permissions'] ? serialize($a['permissions']) : ''),'`id`='.$a['id'].' LIMIT 1');
				}
			}
			Eleanor::$Cache->Lib->DeleteByTag($mc['n'].'_');
			$saved=true;
		}
	}
	else
	{
		foreach($controls as $k=>&$v)
			if(isset($forum['permissions'][$g][$k]))
				$controls[$k]['value']=$forum['permissions'][$g][$k];
			else
				$inherit[]=$k;

		$fgp=$Eleanor->Forum->Core->GroupPerms($forum['parent'],$g);
		foreach($fgp as $k=>&$v)
			if(isset($controls[$k]))
				$controls[$k]['default']=$v;
	}
	$values=$Eleanor->Controls->DisplayControls($controls);
	$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');
	$links=array(
		'forum_groups_rights'=>function($f,$g){ return$GLOBALS['Eleanor']->Url->Construct(array('f'=>$f,'g'=>$g)); },
	);

	$title[]='Права группы &quot;'.$group['title'].'&quot; в форуме &quot;'.$forum['title'].'&quot;';
	$c=Eleanor::$Template->ForumGroupRights($forum,$group,$controls,$values,$haschild,$inherits,$inherit,$navi,$errors,$saved,$back,$links);
	Start();
	echo$c;
}
elseif(isset($_GET['edit-group']))
{
	$id=(int)$_GET['edit-group'];
	if($post)
	{
		$post=false;
		$co=GroupsControls($post);
		$inherit=isset($_POST['_inheritp']) ? (array)$_POST['_inheritp'] : array();
		foreach($inherit as &$v)
			unset($co[$v]);
		try
		{
			$co=$co ? $Eleanor->Controls->SaveControls($co) : array();
		}
		catch(EE$E)
		{
			return EditGroup($id,array('ERROR'=>$E->getMessage()));
		}

		$inherit=isset($_POST['_inherit']) ? (array)$_POST['_inherit'] : array();
		Eleanor::$Db->Update(
			$mc['fg'],
			array(
				'id'=>$id,
				'grow_to'=>isset($_POST['grow_to']) && !in_array('grow_to',$inherit) ? (int)$_POST['grow_to'] : null,
				'grow_after'=>in_array('grow_after',$inherit) ? null : (isset($_POST['grow_after']) ? abs((int)$_POST['grow_after']) : 0),
				'supermod'=>in_array('supermod',$inherit) ? null : isset($_POST['supermod']),
				'see_hidden_users'=>in_array('see_hidden_users',$inherit) ? null : isset($_POST['see_hidden_users']),
				'permissions'=>$co ? serialize($co) : '',
				'moderate'=>in_array('moderate',$inherit) ? null : isset($_POST['moderate']),
			),
			'`id`='.$id.' LIMIT 1'
		);
		Eleanor::$Cache->Lib->DeleteByTag($mc['n'].'_groups');
		GoAway(empty($_POST['back']) ? true : $_POST['back']);
	}
	else
		EditGroup($id);
}
elseif(isset($_GET['edit-moder']))
{
	$id=(int)$_GET['edit-moder'];
	if($post)
		SaveModer($id);
	else
		AddEditModer($id);
}
elseif(isset($_GET['edit-prefix']))
{
	$id=(int)$_GET['edit-prefix'];
	if($post)
		SavePrefix($id);
	else
		AddEditPrefix($id);
}
elseif(isset($_GET['edit-forum']))
{
	$id=(int)$_GET['edit-forum'];
	if($post)
		SaveForum($id);
	else
		AddEditForum($id);
}
elseif(isset($_GET['edit-user']))
{
	$id=(int)$_GET['edit-user'];
	if($post)
	{
		$values=array(
			'posts'=>isset($_POST['posts']) ? (int)$_POST['posts'] : 0,
			'restrict_post'=>isset($_POST['restrict_post']),
			'restrict_post_to'=>isset($_POST['restrict_post_to']) ? (string)$_POST['restrict_post_to'] : '',
			'descr'=>isset($_POST['descr']) ? (string)$_POST['descr'] : '',
		);
		$Eleanor->Forum->Service->UpdateUser($values,$id);
		GoAway(empty($_POST['back']) ? true : $_POST['back']);
	}
	else
		EditUser($id);
}
elseif(isset($_GET['recount']))
{
	$id=(int)$_GET['recount'];
	$Eleanor->Forum->Service->RecountReputation($id);
	$back=getenv('HTTP_REFERER');
	if($back)
	{
		$back=preg_replace('%&recount=.+%','',$back);
		$back=preg_replace('%#.+%','',$back);
		$back.='&recount='.$id;
	}
	GoAway($back,301,'it'.$id);
}
elseif(isset($_GET['up-forum']))
{
	$id=(int)$_GET['up-forum'];
	$R=Eleanor::$Db->Query('SELECT `parents`,`pos` FROM `'.$mc['f'].'` WHERE `id`='.$id.' LIMIT 1');
	if($R->num_rows==0 or !Eleanor::$our_query)
		return GoAway();
	list($parents,$posit)=$R->fetch_row();
	$R=Eleanor::$Db->Query('SELECT COUNT(`parents`),`pos` FROM `'.$mc['f'].'` WHERE `pos`=(SELECT MAX(`pos`) FROM `'.$mc['f'].'` WHERE `pos`<'.$posit.' AND `parents`=\''.$parents.'\') AND `parents`=\''.$parents.'\'');
	list($cnt,$np)=$R->fetch_row();
	if($cnt>0)
	{
		if($cnt>1 or $np+1!=$posit)
		{
			OptimizeForums($parents);
			$R=Eleanor::$Db->Query('SELECT `pos` FROM `'.$mc['f'].'` WHERE `id`='.$id.' LIMIT 1');
			list($posit)=$R->fetch_row();
		}
		Eleanor::$Db->Update($mc['f'],array('!pos'=>'`pos`+1'),'`pos`='.--$posit.' AND `parents`=\''.$parents.'\' LIMIT 1');
		Eleanor::$Db->Update($mc['f'],array('!pos'=>'`pos`-1'),'`id`='.$id.' AND `parents`=\''.$parents.'\' LIMIT 1');
	}
	GoAway(false,301,'cat'.$id);
}
elseif(isset($_GET['down-forum']))
{
	$id=(int)$_GET['down-forum'];
	$R=Eleanor::$Db->Query('SELECT `parents`,`pos` FROM `'.$mc['f'].'` WHERE `id`='.$id.' LIMIT 1');
	if($R->num_rows==0 or !Eleanor::$our_query)
		return GoAway();
	list($parents,$posit)=$R->fetch_row();
	$R=Eleanor::$Db->Query('SELECT COUNT(`parents`),`pos` FROM `'.$mc['f'].'` WHERE `pos`=(SELECT MIN(`pos`) FROM `'.$mc['f'].'` WHERE `pos`>'.$posit.' AND `parents`=\''.$parents.'\') AND `parents`=\''.$parents.'\'');
	list($cnt,$np)=$R->fetch_row();
	if($cnt>0)
	{
		if($cnt>1 or $np-1!=$posit)
		{
			OptimizeForums($parents);
			$R=Eleanor::$Db->Query('SELECT `pos` FROM `'.$mc['f'].'` WHERE `id`='.$id.' LIMIT 1');
			list($posit)=$R->fetch_row();
		}
		Eleanor::$Db->Update($mc['f'],array('!pos'=>'`pos`-1'),'`pos`='.++$posit.' AND `parents`=\''.$parents.'\' LIMIT 1');
		Eleanor::$Db->Update($mc['f'],array('!pos'=>'`pos`+1'),'`id`='.$id.' AND `parents`=\''.$parents.'\' LIMIT 1');
	}
	GoAway(false,301,'cat'.$id);
}
elseif(isset($_GET['delete-forum']))
{
	$id=(int)$_GET['delete-forum'];
	$sess=isset($_GET['s']) ? (string)$_GET['s'] : false;
	if($sess)
		Eleanor::StartSession($sess);

	$errors=array();
	$values=array(
		'trash'=>isset($_POST['trash']) ? (int)$_POST['trash'] : $Eleanor->Forum->Core->vars['trash'],
	);
	if(isset($_GET['noback']))
		$back='';
	else
		$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');

	if(!$sess or !isset($_SESSION['forum']) or $_SESSION['forum']['id']!=$id)
	{
		$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['fl'].'` WHERE `id`='.$id.' AND `language`IN(\'\',\''.Language::$main.'\') LIMIT 1');
		if(!$forum=$R->fetch_assoc())
			return GoAway();
		elseif($sess)
			$_SESSION['forum']=$forum;
	}

	if(isset($_SESSION['proccess']))
		try
		{
			$info=$Eleanor->Forum->Service->DeleteForum($_SESSION['id'],array('trash'=>$_SESSION['proccess']['trash']));
		}
		catch(EE$E)
		{
			unset($_SESSION['proccess']);
			$errors[]=$E->getMessage();
		}
	elseif($post)
		try
		{
			$info=$Eleanor->Forum->Service->DeleteForum($id,array('trash'=>$values['trash']));
			$_SESSION['id']=$id;
			$_SESSION['forum']=$forum;
			$_SESSION['proccess']=$values;
			$_SESSION['back']=$back;
		}
		catch(EE$E)
		{
			$errors[]=$E->getMessage();
		}

	if(isset($info))
	{
		if($info['done']>=$info['total'])
		{
			$s=Eleanor::$Template->DeleteComplete($_SESSION['forum'],$_SESSION['back']);
			Eleanor::$Cache->Lib->DeleteByTag($mc['n'].'_');
		}
		else
			$s=Eleanor::$Template->DeleteProcess($_SESSION['forum'],$info,array('go'=>$Eleanor->Url->Construct(array('delete-forum'=>$id,'s'=>session_id(),'rand'=>uniqid()))));
	}
	else
		$s=Eleanor::$Template->DeleteForm($forum,$values,$back,$errors);

	$title[]='Удаление форума';
	Start();
	echo$s;
}
elseif(isset($_GET['delete-moder']))
{
	if(!Eleanor::$our_query)
		return GoAway();
	$id=(int)$_GET['delete-moder'];
	$R=Eleanor::$Db->Query('SELECT `users`,`groups`,`forums` FROM `'.$mc['fm'].'` WHERE `id`='.$id.' LIMIT 1');
	if(!$moder=$R->fetch_assoc())
		return GoAway(true);

	if($moder['groups'])
	{
		$R=Eleanor::$Db->Query('SELECT `id`,`title_l` `title`,`html_pref`,`html_end` FROM `'.P.'groups` WHERE `id`'.Eleanor::$Db->In(explode(',,',trim($moder['groups'],','))));
		$moder['groups']=array();
		while($a=$R->fetch_assoc())
		{
			$a['title']=$a['title'] ? Eleanor::FilterLangValues((array)unserialize($a['title'])) : '';
			$a['_aedit']=$Eleanor->Url->Construct(array('edit-group'=>$a['a']));
			$moder['groups'][ $a['id'] ]=array_slice($a,1);
		}
	}

	if($moder['users'])
	{
		$R=Eleanor::$Db->Query('SELECT `id`,`name`,`full_name` FROM `'.P.'users_site` WHERE `id`'.Eleanor::$Db->In(explode(',,',trim($moder['users'],','))));
		$moder['users']=array();
		while($a=$R->fetch_assoc())
		{
			$a['_aedit']=$Eleanor->Url->Construct(array('edit-user'=>$a['a']));
			$moder['users'][ $a['id'] ]=array_slice($a,1);
		}
	}

	if($moder['forums'])
	{
		$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['fl'].'` WHERE `language` IN (\'\',\''.Eleanor::$Language.'\') AND `id`'.Eleanor::$Db->In(explode(',,',trim($moder['forums'],','))));
		$moder['forums']=array();
		while($a=$R->fetch_assoc())
		{
			$a['_aedit']=$Eleanor->Url->Construct(array('edit-forum'=>$a['id']));
			$moder['forums'][ $a['id'] ]=array_slice($a,1);
		}
	}

	if(isset($_POST['ok']))
	{
		Eleanor::$Db->Update($mc['f'],array('!moderators'=>'REPLACE(`moderators`,\','.$id.',\',\'\')'),'`id`'.Eleanor::$Db->In(array_keys($moder['forums'])));
		Eleanor::$Db->Update($mc['fu'],array('!moderator'=>'REPLACE(`moderator`,\','.$id.',\',\'\')'),'`id`'.Eleanor::$Db->In(array_keys($moder['users'])));
		Eleanor::$Db->Update($mc['fg'],array('!moderator'=>'REPLACE(`moderator`,\','.$id.',\',\'\')'),'`id`'.Eleanor::$Db->In(array_keys($moder['groups'])));
		Eleanor::$Db->Delete($mc['fm'],'`id`='.$id.' LIMIT 1');
		Eleanor::$Cache->Lib->DeleteByTag($mc['n'].'_moders_');
		return GoAway(empty($_POST['back']) ? true : $_POST['back']);
	}
	$title='Подтверждение удаления';
	if(isset($_GET['noback']))
		$back='';
	else
		$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');

	$s=Eleanor::$Template->DeleteModerator($moder,$back);
	Start();
	echo$s;
}
elseif(isset($_GET['delete-prefix']))
{
	if(!Eleanor::$our_query)
		return GoAway();
	$id=(int)$_GET['delete-prefix'];
	$R=Eleanor::$Db->Query('SELECT `title`,`forums` FROM `'.$mc['pr'].'` INNER JOIN `'.$mc['pl'].'` USING(`id`) WHERE `id`='.$id.' LIMIT 1');
	if(!$prefix=$R->fetch_assoc())
		return GoAway(true);

	if($prefix['forums'])
	{
		$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['fl'].'` WHERE `language` IN (\'\',\''.Eleanor::$Language.'\') AND `id`'.Eleanor::$Db->In(explode(',,',trim($prefix['forums'],','))));
		$prefix['forums']=array();
		while($a=$R->fetch_assoc())
		{
			$a['_aedit']=$Eleanor->Url->Construct(array('edit-forum'=>$a['id']));
			$prefix['forums'][ $a['id'] ]=array_slice($a,1);
		}
	}

	if(isset($_POST['ok']))
	{
		Eleanor::$Db->Update($mc['f'],array('!prefixes'=>'REPLACE(`prefixes`,\','.$id.',\',\'\')'),'`id`'.Eleanor::$Db->In(array_keys($prefix['forums'])));
		Eleanor::$Db->Delete($mc['pl'],'`id`='.$id);
		Eleanor::$Db->Delete($mc['pr'],'`id`='.$id.' LIMIT 1');
		Eleanor::$Cache->Lib->DeleteByTag($mc['n'].'_prefixes_');
		return GoAway(empty($_POST['back']) ? true : $_POST['back']);
	}
	$title='Подтверждение удаления';
	if(isset($_GET['noback']))
		$back='';
	else
		$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');

	$s=Eleanor::$Template->DeletePrefix($prefix,$back);
	Start();
	echo$s;
}
elseif(isset($_GET['delete-attach']))
{#ToDo!
	if(!Eleanor::$our_query)
		return GoAway();
	$id=(int)$_GET['delete-attach'];
	$R=Eleanor::$Db->Query('SELECT `f`,`language`,`t` FROM `'.$mc['ta'].'` WHERE `id`='.$id.' LIMIT 1');
	if(!$attach=$R->fetch_assoc())
		return GoAway(true);

	if(isset($_POST['ok']))
	{
		$Eleanor->Forum->Moderate->DeleteAttach($id);
		return GoAway(empty($_POST['back']) ? true : $_POST['back']);
	}
	$title='Подтверждение удаления файла';
	if(isset($_GET['noback']))
		$back='';
	else
		$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');

	$s=Eleanor::$Template->DeleteAttach($attach,$back);
	Start();
	echo$s;
}
elseif(isset($_GET['delete-fs'],$_GET['u'],$_GET['l']))
{#ToDo!
	if(!Eleanor::$our_query)
		return GoAway();

	$f=(int)$_GET['delete-fs'];
	$u=(int)$_GET['u'];
	$l=(string)$_GET['l'];
	if(isset($_POST['ok']))
	{
		$Eleanor->Forum->Subscriptions->SubscribeForum($f,$l,$u,false);
		return GoAway(empty($_POST['back']) ? true : $_POST['back']);
	}
	$title='Подтверждение удаления подписки на форум';
	if(isset($_GET['noback']))
		$back='';
	else
		$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');

	$s=Eleanor::$Template->DeleteFS($back);
	Start();
	echo$s;
}
elseif(isset($_GET['delete-ts'],$_GET['u']))
{#ToDo!
	if(!Eleanor::$our_query)
		return GoAway();

	$f=(int)$_GET['delete-fs'];
	$u=(int)$_GET['u'];
	if(isset($_POST['ok']))
	{
		$Eleanor->Forum->Subscriptions->SubscribeTopic($f,$u,false);
		return GoAway(empty($_POST['back']) ? true : $_POST['back']);
	}
	$title='Подтверждение удаления подписки на тему';
	if(isset($_GET['noback']))
		$back='';
	else
		$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');

	$s=Eleanor::$Template->DeleteTS($back);
	Start();
	echo$s;
}
elseif(isset($_GET['delete-reputation']))
{#ToDo!
	if(!Eleanor::$our_query)
		return GoAway();

	$id=(int)$_GET['delete-reputation'];

	if(isset($_POST['ok']))
	{
		$Eleanor->Forum->Moderate->DeleteReputation($id);
		return GoAway(empty($_POST['back']) ? true : $_POST['back']);
	}
	$title='Подтверждение удаления репутации';
	if(isset($_GET['noback']))
		$back='';
	else
		$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');

	$s=Eleanor::$Template->DeleteReputation();
	Start();
	echo$s;
}
elseif(isset($_GET['savedelete']))
{
	Eleanor::StartSession((string)$_GET['savedelete']);
	if(!isset($_SESSION['id'],$_SESSION['values'],$_SESSION['langs']))
		return GoAway();

	$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['fl'].'` WHERE `id`='.$_SESSION['id'].' AND `language`IN(\'\',\''.Language::$main.'\') LIMIT 1');
	if(!$forum=$R->fetch_assoc())
		return GoAway();

	$errors=array();
	$values=array(
		'trash'=>isset($_POST['trash']) ? (int)$_POST['trash'] : $Eleanor->Forum->Core->vars['trash'],
	);

	$onelang=$_SESSION['newlangs']==array('');
	if($post)
		try
		{
			$info=$Eleanor->Forum->Service->DeleteForum($_SESSION['id'],array('langs'=>$_SESSION['langs'],'trash'=>$values['trash'],'trashlangs'=>$_SESSION['newlangs'],'tolang'=>$onelang ? '' : false));
			$_SESSION['proccess']=$values;
		}
		catch(EE$E)
		{
			$errors[]=$E->getMessage();
		}
	elseif(isset($_SESSION['proccess']))
		try
		{
			$info=$Eleanor->Forum->Service->DeleteForum($_SESSION['id'],array('langs'=>$_SESSION['langs'],'trash'=>$_SESSION['proccess']['trash'],'trashlangs'=>$_SESSION['newlangs'],'tolang'=>$onelang ? '' : false));
		}
		catch(EE$E)
		{
			unset($_SESSION['proccess']);
			$errors[]=$E->getMessage();
		}

	if(isset($info))
	{
		if($info['done']>=$info['total'])
		{
			UpdateForum($_SESSION['id'],$_SESSION['values'],$_SESSION['lvalues'],$_SESSION['newlangs']);
			Eleanor::$Cache->Lib->DeleteByTag($mc['n'].'_');
			$s=Eleanor::$Template->SaveDeleteComplete($forum,$_SESSION['langs'],$_SESSION['newlangs'],$_SESSION['back']);
		}
		else
			$s=Eleanor::$Template->SaveDeleteProcess($forum,$info,$_SESSION['langs'],$_SESSION['newlangs'],array('go'=>$Eleanor->Url->Construct(array('savedelete'=>session_id(),'rand'=>uniqid()))));
	}
	else
		$s=Eleanor::$Template->SaveDeleteForm($forum,$values,$_SESSION['langs'],$_SESSION['newlangs'],$_SESSION['back'],$errors);

	$title[]='Удаление языковых версий форума';
	Start();
	echo$s;
}
else
	Forums();

#Работа с форумами

function Forums()
{global$Eleanor,$title;
	$title[]='Список форумов';
	$parent=isset($_GET['parent']) ? (int)$_GET['parent'] : 0;

	$mc=$Eleanor->module['config'];
	$items=$subitems=$navi=$where=array();
	$qs=array(
		'parent'=>$parent>0 ? $parent : false,
	);
	if($parent>0)
	{
		$qs['']['parent']=$parent;
		$R=Eleanor::$Db->Query('SELECT `parents` FROM `'.$mc['f'].'` WHERE `id`='.$parent.' LIMIT 1');
		list($parents)=$R->fetch_row();
		$parents.=$parent;
		$temp=array();
		$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['f'].'` INNER JOIN `'.$mc['fl'].'` USING(`id`) WHERE `language` IN (\'\',\''.Language::$main.'\') AND `id` IN ('.$parents.')');
		while($a=$R->fetch_assoc())
			$temp[$a['id']]=$a['title'];
		$navi[0]=array('title'=>'Список форумов','_a'=>$Eleanor->Url->Prefix());
		foreach(explode(',',$parents) as $v)
			if(isset($temp[$v]))
				$navi[$v]=array('title'=>$temp[$v],'_a'=>$v==$parent ? false : $Eleanor->Url->Construct(array('parent'=>$v)));
		$Eleanor->module['links']['add-forum']=$Eleanor->Url->Construct(array('do'=>'add-forum','parent'=>$parent));
	}

	$R=Eleanor::$Db->Query('SELECT COUNT(`parent`) FROM `'.$mc['f'].'` WHERE `parent`='.$parent);
	list($cnt)=$R->fetch_row();

	$page=isset($_GET['page']) ? (int)$_GET['page'] : 1;
	if($page<=0)
		$page=1;
	if(isset($_GET['new-pp']) and 4<$pp=(int)$_GET['new-pp'])
		Eleanor::SetCookie('per-page',$pp);
	else
		$pp=abs((int)Eleanor::GetCookie('per-page'));
	if($pp<5 or $pp>500)
		$pp=50;
	$offset=abs(($page-1)*$pp);
	if($cnt and $offset>=$cnt)
		$offset=max(0,$cnt-$pp);
	$sort=isset($_GET['sort']) ? (string)$_GET['sort'] : '';
	if(!in_array($sort,array('id','title','pos')))
		$sort='';
	$so=$_SERVER['REQUEST_METHOD']!='POST' && $sort && isset($_GET['so']) ? $_GET['so'] : 'asc';
	if($so!='desc')
		$so='asc';
	if($sort)
		$qs+=array('sort'=>$sort,'so'=>$so);
	else
		$sort='pos';
	$qs+=array('sort'=>false,'so'=>false);

	if($cnt>0)
	{
		$R=Eleanor::$Db->Query('SELECT `id`,`title`,`parent`,`pos`,`image`,`moderators` FROM `'.$mc['f'].'` INNER JOIN `'.$mc['fl'].'` USING(`id`) WHERE `language` IN (\'\',\''.Language::$main.'\') AND `parent`='.$parent.' ORDER BY `'.$sort.'` '.$so.' LIMIT '.$offset.','.$pp);
		while($a=$R->fetch_assoc())
		{
			$a['_aedit']=$Eleanor->Url->Construct(array('edit-forum'=>$a['id']));
			$a['_adel']=$Eleanor->Url->Construct(array('delete-forum'=>$a['id']));
			$a['_aparent']=$Eleanor->Url->Construct(array('parent'=>$a['id']));
			$a['_aup']=$a['pos']>1 ? $Eleanor->Url->Construct(array('up-forum'=>$a['id'])) : false;
			$a['_adown']=$a['pos']<$cnt ? $Eleanor->Url->Construct(array('down-forum'=>$a['id'])) : false;
			$a['_aaddp']=$Eleanor->Url->Construct(array('do'=>'add-forum','parent'=>$a['id']));

			if($a['image'])
				$a['image']=$mc['logos'].$a['image'];

			$subitems[]=$a['id'];
			$items[$a['id']]=array_slice($a,1);
		}
	}

	if($subitems)
	{
		$R=Eleanor::$Db->Query('SELECT `id`,`parent`,`title` FROM `'.$mc['f'].'` INNER JOIN `'.$mc['fl'].'` USING(`id`) WHERE `language` IN (\'\',\''.Language::$main.'\') AND `parent`'.Eleanor::$Db->In($subitems).' ORDER BY `pos` ASC');
		$subitems=array();
		while($a=$R->fetch_assoc())
			$subitems[$a['parent']][$a['id']]=$a['title'];

		foreach($subitems as &$v)
		{
			asort($v,SORT_STRING);
			foreach($v as $kk=>&$vv)
				$vv=array(
					'title'=>$vv,
					'_aedit'=>$Eleanor->Url->Construct(array('edit-forum'=>$kk)),
				);
		}
	}

	$links=array(
		'sort_title'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'title','so'=>$qs['sort']=='title' && $qs['so']=='asc' ? 'desc' : 'asc'))),
		'sort_pos'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'pos','so'=>$qs['sort']=='pos' && $qs['so']=='asc' ? 'desc' : 'asc'))),
		'sort_id'=>$Eleanor->Url->Construct(array_merge($qs,array('sort'=>'id','so'=>$qs['sort']=='id' && $qs['so']=='asc' ? 'desc' : 'asc'))),
		'form_items'=>$Eleanor->Url->Construct($qs+array('page'=>$page)),
		'pp'=>function($n) use ($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('new-pp'=>$n)); },
		'first_page'=>$Eleanor->Url->Construct($qs),
		'pages'=>function($n)use($qs){ return$GLOBALS['Eleanor']->Url->Construct($qs+array('page'=>$n)); },
		'forum_groups_rights'=>function($f,$g){ return$GLOBALS['Eleanor']->Url->Construct(array('f'=>$f,'g'=>$g)); },
	);
	$c=Eleanor::$Template->Forums($items,$subitems,$navi,$cnt,$pp,$qs,$page,$links);

	Start();
	echo$c;
}

function AddEditForum($id,$errors=array())
{global$Eleanor,$title;
	$mc=$Eleanor->module['config'];
	if($id)
	{
		$title[]='Правка форума';
		$R=Eleanor::$Db->Query('SELECT '.($errors ? '`id`' : '*').' FROM `'.$mc['f'].'` WHERE id='.$id.' LIMIT 1');
		if(!$values=$R->fetch_assoc())
			return GoAway(true);

		if(!$errors)
		{
			$values['prefixes']=$values['prefixes'] ? explode(',,',trim($values['prefixes'],',')) : array();
			$values['uri']=$values['title']=$values['description']=$values['meta_title']=$values['meta_descr']=array();
			$R=Eleanor::$Db->Query('SELECT `language`,`uri`,`title`,`description`,`meta_title`,`meta_descr` FROM `'.$mc['fl'].'` WHERE `id`='.$id);
			while($temp=$R->fetch_assoc())
				if(!Eleanor::$vars['multilang'] and (!$temp['language'] or $temp['language']==Language::$main))
				{
					foreach(array_slice($temp,1) as $tk=>$tv)
						$values[$tk]=$tv;
					if(!$temp['language'])
						break;
				}
				elseif(!$temp['language'] and Eleanor::$vars['multilang'])
				{
					foreach(array_slice($temp,1) as $tk=>$tv)
						$values[$tk][Language::$main]=$tv;
					$values['_onelang']=true;
					break;
				}
				elseif(Eleanor::$vars['multilang'] and isset(Eleanor::$langs[$temp['language']]))
					foreach(array_slice($temp,1) as $tk=>$tv)
						$values[$tk][$temp['language']]=$tv;

			if(Eleanor::$vars['multilang'])
			{
				if(!isset($values['_onelang']))
					$values['_onelang']=false;
				$values['_langs']=array_keys($values['title']);
			}
		}
	}
	else
	{
		$dv=Eleanor::$vars['multilang'] ? array(''=>'') : '';
		$values=array(
			'parent'=>isset($_GET['parent']) ? (int)$_GET['parent'] : 0,
			'pos'=>'',
			'is_category'=>'',
			'inc_posts'=>true,
			'reputation'=>true,
			'image'=>'',
			'hide_attach'=>false,
			#Языковые
			'title'=>$dv,
			'description'=>$dv,
			'uri'=>$dv,
			'meta_title'=>$dv,
			'meta_descr'=>$dv,
		);
		if(Eleanor::$vars['multilang'])
		{
			$values['_onelang']=true;
			$values['_langs']=array_keys(Eleanor::$langs);
		}
		$title[]='Добавить форум';
	}

	if($errors)
	{
		$bypost=true;
		if($errors===true)
			$errors=array();
		if(Eleanor::$vars['multilang'])
		{
			$values['meta_title']=isset($_POST['meta_title']) ? (array)$_POST['meta_title'] : array();
			$values['meta_descr']=isset($_POST['meta_descr']) ? (array)$_POST['meta_descr'] : array();
			$values['title']=isset($_POST['title']) ? (array)$_POST['title'] : array();
			$values['description']=isset($_POST['description']) ? (array)$_POST['description'] : array();
			$values['uri']=isset($_POST['uri']) ? (array)$_POST['uri'] : array();
			$values['_onelang']=isset($_POST['_onelang']);
			$values['_langs']=isset($_POST['_langs']) ? (array)$_POST['_langs'] : array(Language::$main);
		}
		else
		{
			$values['meta_title']=isset($_POST['meta_title']) ? (string)$_POST['meta_title'] : '';
			$values['meta_descr']=isset($_POST['meta_descr']) ? (string)$_POST['meta_descr'] : '';
			$values['title']=isset($_POST['title']) ? (string)$_POST['title'] : '';
			$values['description']=isset($_POST['description']) ? (string)$_POST['description'] : '';
			$values['uri']=isset($_POST['uri']) ? (string)$_POST['uri'] : '';
		}

		$values['parent']=isset($_POST['parent']) ? (int)$_POST['parent'] : 0;
		$values['pos']=isset($_POST['pos']) ? (string)$_POST['pos'] : '';
		$values['image']=isset($_POST['image']) ? (string)$_POST['image'] : '';
		$values['hide_attach']=isset($_POST['hide_attach']);
		$values['reputation']=isset($_POST['reputation']);
		$values['inc_posts']=isset($_POST['inc_posts']);
		$values['is_category']=isset($_POST['is_category']);
		$values['prefixes']=isset($_POST['prefixes']) ? (array)$_POST['prefixes'] : array();
	}
	else
		$bypost=false;

	if(isset($_GET['noback']))
		$back='';
	else
		$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');

	$links=array(
		'delete'=>$id ? $Eleanor->Url->Construct(array('delete-forum'=>$id,'noback'=>1)) : false,
	);

	$imagopts='';
	$images=glob(Eleanor::$root.$mc['logos'].'*.{jpg,jpeg,bmp,ico,gif,png}',GLOB_BRACE | GLOB_MARK);
	foreach($images as $v)
	{
		if(substr($v,-1)==DIRECTORY_SEPARATOR)
			continue;
		$v=basename($v);
		$imagopts.=Eleanor::Option($v,false,$v==$values['image']);
	}

	$prefixes=array();
	$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$mc['pr'].'` INNER JOIN `'.$mc['pl'].'` USING(`id`) WHERE `language`IN(\'\',\''.Language::$main.'\')');
	while($a=$R->fetch_row())
		$prefixes[ $a[0] ]=$a[1];
	natsort($prefixes);

	$c=Eleanor::$Template->AddEditForum($id,$values,$errors,$imagopts,$prefixes,$Eleanor->Uploader->Show($mc['n']),$bypost,$back,$links);
	Start();
	echo$c;
}

function SaveForum($id)
{global$Eleanor;
	$mc=$Eleanor->module['config'];
	$lang=Eleanor::$Language[$mc['n']];

	if(Eleanor::$vars['multilang'] and !isset($_POST['_onelang']))
	{
		$langs=empty($_POST['_langs']) || !is_array($_POST['_langs']) ? array() : $_POST['_langs'];
		$langs=array_intersect(array_keys(Eleanor::$langs),$langs);
		if(!$langs)
			$langs=array(Language::$main);
	}
	else
		$langs=array('');

	$errors=array();
	$prefixes=isset($_POST['prefixes']) ? (array)$_POST['prefixes'] : array();
	sort($prefixes,SORT_NUMERIC);
	$values=array(
		'parent'=>isset($_POST['parent']) ? (int)$_POST['parent'] : 0,
		'pos'=>isset($_POST['pos']) ? $_POST['pos'] : '',
		'image'=>isset($_POST['image']) ? (string)$_POST['image'] : '',
		'hide_attach'=>isset($_POST['hide_attach']),
		'reputation'=>isset($_POST['reputation']),
		'inc_posts'=>isset($_POST['inc_posts']),
		'is_category'=>isset($_POST['is_category']),
		'prefixes'=>$prefixes ? ','.join(',,',$prefixes).',' : '',
	);

	if(Eleanor::$vars['multilang'])
	{
		$lvalues=array(
			'title'=>array(),
			'uri'=>array(),
			'description'=>array(),
			'meta_title'=>array(),
			'meta_descr'=>array(),
		);
		foreach($langs as $l)
		{
			$lng=$l ? $l : Language::$main;
			$Eleanor->Editor_result->imgalt=$lvalues['title'][$l]=isset($_POST['title'],$_POST['title'][$lng]) && is_array($_POST['title']) ? (string)Eleanor::$POST['title'][$lng] : '';
			$lvalues['uri'][$l]=isset($_POST['uri'],$_POST['uri'][$lng]) && is_array($_POST['uri']) ? (string)$_POST['uri'][$lng] : '';
			$lvalues['description'][$l]=isset($_POST['description'],$_POST['description'][$lng]) && is_array($_POST['description']) ? $Eleanor->Editor_result->GetHtml((string)$_POST['description'][$lng],true) : '';
			$lvalues['meta_title'][$l]=isset($_POST['meta_title'],$_POST['meta_title'][$lng]) && is_array($_POST['meta_title']) ? (string)Eleanor::$POST['meta_title'][$lng] : '';
			$lvalues['meta_descr'][$l]=isset($_POST['meta_descr'],$_POST['meta_descr'][$lng]) && is_array($_POST['meta_descr']) ? (string)Eleanor::$POST['meta_descr'][$lng] : '';
		}
	}
	else
	{
		$Eleanor->Editor_result->imgalt=isset($_POST['title']) ? (string)Eleanor::$POST['title'] : '';
		$lvalues=array(
			'title'=>array(''=>$Eleanor->Editor_result->imgalt),
			'description'=>array(''=>$Eleanor->Editor_result->GetHtml('description')),
			'uri'=>array(''=>isset($_POST['uri']) ? (string)$_POST['uri'] : ''),
			'meta_title'=>array(''=>isset($_POST['meta_title']) ? (string)Eleanor::$POST['meta_title'] : ''),
			'meta_descr'=>array(''=>isset($_POST['meta_descr']) ? (string)Eleanor::$POST['meta_descr'] : ''),
		);
	}

	$ml=in_array('',$langs) ? Language::$main : '';
	$emp=array('title'=>array());
	foreach($emp as $kf=>&$field)
		foreach($lvalues[$kf] as $k=>&$v)
			$field[$k]=$v=='' && (in_array($k,$langs) or $ml==$k);

	foreach($emp['title'] as $k=>&$v)
		if($v)
		{
			$er='EMPTY_TITLE'.strtoupper($k ? '_'.$k : '');
			$errors[$er]=$lang['EMPTY_TITLE']($k);
		}

	foreach($lvalues['uri'] as $k=>&$v)
	{
		if($v=='')
			$v=htmlspecialchars_decode($lvalues['title'][$k],ELENT);
		$v=$Eleanor->Url->Filter($v,$k);
		$R=Eleanor::$Db->Query('SELECT `id` FROM `'.$mc['f'].'` INNER JOIN `'.$mc['fl'].'` USING(`id`) WHERE `uri`='.Eleanor::$Db->Escape($v).' AND `language`'.($k ? 'IN(\'\',\''.$k.'\')' : '=\'\'').($id ? ' AND `id`!='.$id : '').' LIMIT 1');
		if($R->num_rows>0)
			$v='';
	}

	if($values['parent']>0)
	{
		$R=Eleanor::$Db->Query('SELECT `parents` FROM `'.$mc['f'].'` WHERE `id`='.(int)$values['parent'].' LIMIT 1');
		if(!list($parents)=$R->fetch_row() or $parents and strpos(','.$parents,','.$id.',')!==false)
			$errors['ERROR_PARENT']='Error parent';
		elseif($id>0)
		{
			$in=$parents.$values['parent'];
			if(in_array('',$langs))
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`language` FROM `'.$mc['fl'].'` WHERE `id`IN('.$in.') AND `language`!=\'\'');
				if($R->num_rows>0)
					$errors['PARENT_HAS_NOT_SAME_LANG']='Ошибка в наследовании языков';
			}
			else
			{
				$exin=explode(',',$in);
				$check=array_combine($langs,array_fill(0,count($langs),true));
				$check=array_combine($exin,array_fill(0,count($exin),$check));
				$R=Eleanor::$Db->Query('SELECT `id`,`language` FROM `'.$mc['fl'].'` WHERE `id`IN('.$in.')');
				while($a=$R->fetch_assoc())
					if($a['language']=='')
						unset($check[ $a['id'] ]);
					else
					{
						unset($check[ $a['id'] ][ $a['language'] ]);
						if(!$check[ $a['id'] ])
							unset($check[ $a['id'] ]);
					}
				if($check)
					$errors['PARENT_HAS_NOT_SAME_LANG']='Ошибка в наследовании языков';
			}
		}
	}

	if($errors)
		return AddEditForum($id,$errors);

	$back=empty($_POST['back']) ? true : $_POST['back'];
	if($id)
	{
		$R=Eleanor::$Db->Query('SELECT `prefixes` FROM `'.$mc['f'].'` WHERE `id`='.$id.' LIMIT 1');
		if(!list($prs)=$R->fetch_row())
			return GoAway();
		if($prs)
			Eleanor::$Db->Update($mc['pr'],array('!forums'=>'REPLACE(`forums`,\','.$id.',\',\'\')'),'`id`'.Eleanor::$Db->In(explode(',,',trim($prs,','))));
		if($prefixes)
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`forums` FROM `'.$mc['pr'].'` WHERE `id`'.Eleanor::$Db->In($prefixes));
			while($a=$R->fetch_assoc())
			{
				$a['forums']=$a['forums'] ? explode(',,',trim($a['forums'],',')) : array();
				$a['forums'][]=$id;
				sort($a['forums'],SORT_NUMERIC);
				Eleanor::$Db->Update($mc['pr'],array('forums'=>','.join(',,',$a['forums']).','),'`id`='.$a['id'].' LIMIT 1');
			}
		}


		$oldlangs=array();
		$R=Eleanor::$Db->Query('SELECT `language` FROM `'.$mc['fl'].'` WHERE `id`='.$id);
		while($a=$R->fetch_assoc())
			$oldlangs[]=$a['language'];

		if($oldlangs=array_diff($oldlangs,$langs))
		{
			#Переход к интерфейсу удаления форума
			Eleanor::StartSession();
			$_SESSION=array(
				'id'=>$id,
				'back'=>$back,
				'langs'=>$oldlangs,
				'values'=>$values,
				'lvalues'=>$lvalues,
				'newlangs'=>$langs,
			);

			return GoAway(array('savedelete'=>session_id()));
		}

		UpdateForum($id,$values,$lvalues,$langs);
	}
	else
	{
		if($values['pos']=='')
		{
			$R=Eleanor::$Db->Query('SELECT MAX(`pos`) FROM `'.$mc['f'].'` WHERE `parent`=\''.$values['parent'].'\'');
			list($pos)=$R->fetch_row();
			$values['pos']=$pos===null ? 1 : $pos+1;
		}
		else
		{
			if($values['pos']<=0)
				$values['pos']=1;
			Eleanor::$Db->Update($mc['f'],array('!pos'=>'`pos`+1'),'`pos`>='.(int)$values['pos'].' AND `parent`=\''.$values['parent'].'\'');
		}
		$id=Eleanor::$Db->Insert($mc['f'],$values);

		$values=array('id'=>array(),'language'=>array(),'uri'=>array(),'title'=>array(),'description'=>array(),'meta_title'=>array(),'meta_descr'=>array());
		foreach($langs as &$v)
		{
			$values['id'][]=$id;
			$values['language'][]=$v;
			$values['uri'][]=$lvalues['uri'][$v];
			$values['title'][]=$lvalues['title'][$v];
			$values['description'][]=$lvalues['description'][$v];
			$values['meta_title'][]=$lvalues['meta_title'][$v];
			$values['meta_descr'][]=$lvalues['meta_descr'][$v];
		}
		Eleanor::$Db->Insert($mc['fl'],$values);

		Eleanor::$Db->Update($mc['pr'],array('!forums'=>'CONCAT(`forums`,\','.$id.',\')'),'`id`'.Eleanor::$Db->In($prefixes));
	}
	Eleanor::$Cache->Lib->DeleteByTag($mc['n'].'_');
	GoAway($back);
}

#Обновление существущего форума
function UpdateForum($id,$values,$lvalues,$langs)
{global$Eleanor;
	$mc=$Eleanor->module['config'];

	$R=Eleanor::$Db->Query('SELECT `parent`,`parents`,`pos` FROM `'.$mc['f'].'` WHERE `id`='.$id.' LIMIT 1');
	if(!list($parent,$parents,$pos)=$R->fetch_row())
		return GoAway();

	$values['pos']=(int)$values['pos'];
	if($values['pos']<=0)
		$values['pos']=1;
	if($pos!=$values['pos'])
	{
		Eleanor::$Db->Update($mc['f'],array('!pos'=>'`pos`-1'),'`pos`>'.$pos.' AND `parent`=\''.$parent.'\'');
		Eleanor::$Db->Update($mc['f'],array('!pos'=>'`pos`+1'),'`pos`>='.$values['pos'].' AND `parent`=\''.$values['parent'].'\'');
	}

	if($parent!=$values['parent'])
		Eleanor::$Db->Update($mc['f'],array('!parents'=>'REPLACE(`parents`,\''.$parents.'\',\''.$values['parents'].'\')'),'`parents` LIKE \''.$parents.$id.',%\'');
	Eleanor::$Db->Update($mc['f'],$values,'id='.$id.' LIMIT 1');
	Eleanor::$Db->Delete($mc['fl'],'`id`='.$id.' AND `language`'.Eleanor::$Db->In($langs,true));

	#Помним, что в таблице форума могут быть еще и сторонние поля: количество сообщений, количество тем. Эти поля нужно сохранить.
	$othf=array();
	$R=Eleanor::$Db->Query('SELECT * FROM `'.$mc['fl'].'` WHERE `id`='.$id);
	while($a=$R->fetch_assoc())
		$othf[$a['language']]=array_slice($a,2);

	$values=array();
	foreach($langs as &$v)
		$values[]=array(
			'id'=>$id,
			'language'=>$v,
			'uri'=>$lvalues['uri'][$v],
			'title'=>$lvalues['title'][$v],
			'description'=>$lvalues['description'][$v],
			'meta_title'=>$lvalues['meta_title'][$v],
			'meta_descr'=>$lvalues['meta_descr'][$v],
		)+(isset($othf[$v]) ? $othf[$v] : array());
	Eleanor::$Db->Replace($mc['fl'],$values);
}

function OptimizeForums($p='')
{global$Eleanor;
	$mc=$Eleanor->module['config'];
	$R=Eleanor::$Db->Query('SELECT `id`,`pos` FROM `'.$mc['f'].'` WHERE `parents`=\''.$p.'\' ORDER BY `pos` ASC');
	$cnt=1;
	while($a=$R->fetch_assoc())
	{
		if($a['pos']!=$cnt)
			Eleanor::$Db->Update($mc['f'],array('pos'=>$cnt),'`id`='.$a['id'].' LIMIT 1');
		++$cnt;
	}
}

#Работа с группами

function EditGroup($id,$errors=array())
{global$Eleanor,$title;
	$R=Eleanor::$Db->Query('SELECT `id`,`parents`,`title_l` `title`,`html_pref`,`html_end` FROM `'.P.'groups` WHERE `id`='.$id.' LIMIT 1');
	if(!$group=$R->fetch_assoc())
		return GoAway();
	$group['parents']=$group['parents'] ? explode(',',rtrim($group['parents'],',')) : array();
	$group['title']=$group['title'] ? Eleanor::FilterLangValues((array)unserialize($group['title'])) : '';

	$mc=$Eleanor->module['config'];
	if($errors)
	{
		$bypost=true;
		if($errors===true)
			$errors=array();
		$values=array(
			'grow_to'=>isset($_POST['grow_to']) ? (int)$_POST['grow_to'] : 0,
			'grow_after'=>isset($_POST['grow_after']) ? (int)$_POST['grow_after'] : 0,
			'supermod'=>isset($_POST['supermod']),
			'see_hidden_users'=>isset($_POST['see_hidden_users']),
			'moderate'=>isset($_POST['moderate']),
			'_inherit'=>isset($_POST['_inherit']) ? (array)$_POST['_inherit'] : array(),
			'_inheritp'=>isset($_POST['_inheritp']) ? (array)$_POST['_inheritp'] : array(),
		);
		$values['permissions']=array();
	}
	else
	{
		$bypost=false;
		$R=Eleanor::$Db->Query('SELECT * FROM `'.$mc['fg'].'` WHERE `id`='.$id.' LIMIT 1');
		if($values=$R->fetch_assoc())
		{
			$values['permissions']=$values['permissions'] ? (array)unserialize($values['permissions']) : array();
			$values['_inherit']=$values['_inheritp']=array();
			foreach($values as $k=>$v)
				if($v===null)
					$values['_inherit'][]=$k;

			foreach($values['permissions'] as $k=>$v)
				if($v===null)
					$values['_inheritp'][]=$k;
		}
		else
			return GoAway();
	}

	$controls=GroupsControls($bypost,20);
	if(!$bypost)
		foreach($values['permissions'] as &$v)
			$v=array('value'=>$values['permissions'][$k]);
	$values['permissions']=$Eleanor->Controls->DisplayControls($controls,$values['permissions']);

	$grows=array();
	$R=Eleanor::$Db->Query('SELECT `id`,`grow_to`,`grow_after` FROM `'.$mc['fg'].'`');
	while($a=$R->fetch_assoc())
		$grows[$a['id']]=array_slice($a,1);

	$min=1;
	$exclude=array();
	if($id)
		foreach($grows as $k=>&$v)
			if($id==$v['grow_to'])
			{
				if($v['grow_after']>$min)
					$min=$v['grow_after'];
				$exclude[]=$k;
			}

	if(isset($_GET['noback']))
		$back='';
	else
		$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');

	$title[]='Редактирование группы &quot;'.$group['title'].'&quot;';
	$c=Eleanor::$Template->EditGroup($group,$values,$controls,Usermanager::GroupsOpts($values['grow_to'],$exclude),$min,$bypos,$back,$errors);
	Start();
	echo$c;
}

function GroupsControls(&$post,$ti=13)
{global$Eleanor;
	if(!class_exists('ForumForums',false))
		include$Eleanor->module['path'].'forums.php';
	return array(
		'access'=>array(
			'title'=>'Общая доступность форума',
			'descr'=>'Форум виден группе',
			'type'=>'check',
			'default'=>ForumForums::$rights['access'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'topics'=>array(
			'title'=>'Просмотр списка тем',
			'descr'=>'Пользователи группы смогут просматривать список тем',
			'type'=>'check',
			'default'=>ForumForums::$rights['topics'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'antopics'=>array(
			'title'=>'Отображать не только свои, но и чужие темы',
			'descr'=>'В списке тем будут присутствовать темы других пользователей',
			'type'=>'check',
			'default'=>ForumForums::$rights['atopics'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'read'=>array(
			'title'=>'Позволить читать темы',
			'descr'=>'Пользователи смогут читать темы и отдельные сообщения в них',
			'type'=>'check',
			'default'=>ForumForums::$rights['read'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'attach'=>array(
			'title'=>'Открыть доступ к вложениям',
			'descr'=>'Пользователи смогут получать доступ к файловым вложениям',
			'type'=>'check',
			'default'=>ForumForums::$rights['attach'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'post'=>array(
			'title'=>'Позволить отвечать в свои темы',
			'descr'=>'Пользователи смогут отвечать в свои темы.',
			'type'=>'check',
			'default'=>ForumForums::$rights['post'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'apost'=>array(
			'title'=>'Позволить отвечать в чужие темы',
			'descr'=>'Пользователи смогут отвечать в чужие темы.',
			'type'=>'check',
			'default'=>ForumForums::$rights['apost'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'edit'=>array(
			'title'=>'Позволить править свои сообщения',
			'descr'=>'Пользователи смогут отредактировать или удалить свои сообщения',
			'type'=>'check',
			'default'=>ForumForums::$rights['edit'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'editlimit'=>array(
			'title'=>'Временное ораничение правки/удаления сообщения',
			'descr'=>'После публикации, пользователь сможет отредактировать или удалить своё сообщение только в течении указанного количества секунд. 0 - отключено',
			'type'=>'input',
			'default'=>ForumForums::$rights['editlimit'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
					'type'=>'number',
				),
			),
		),
		'new'=>array(
			'title'=>'Позволить создавать темы',
			'descr'=>'Пользователи смогут создавать темы',
			'type'=>'check',
			'default'=>ForumForums::$rights['new'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'mod'=>array(
			'title'=>'Позволить править/удалять чужие сообщения в своих темах',
			'descr'=>'Пользователи смогут отредактировать или удалить чужие сообщения в своих темах. Внимание! При включении этой опции, пользователи смогут править/удалять свои сообщения в своих темах без ограничений!',
			'type'=>'check',
			'default'=>ForumForums::$rights['mod'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'close'=>array(
			'title'=>'Позволить открывать/закрывать свои темы',
			'descr'=>'Пользователи смогут открывать/закрывать свои темы',
			'type'=>'check',
			'default'=>ForumForums::$rights['close'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'deletet'=>array(
			'title'=>'Позволить удалять свои темы',
			'descr'=>'Пользователи смогут удалять свои темы. Если эта опция отключена, то удалить первое сообщение пользователи не смогут.',
			'type'=>'check',
			'default'=>ForumForums::$rights['deletet'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'delete'=>array(
			'title'=>'Позволить удалять свои сообщений',
			'descr'=>'Пользователи смогут удалять свои свообщения.',
			'type'=>'check',
			'default'=>ForumForums::$rights['deletet'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'editt'=>array(
			'title'=>'Позволить править заголовки своих тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$rights['editt'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'complaint'=>array(
			'title'=>'Позволить пользоваться кнопкой "жалоба"',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$rights['complaint'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'canclose'=>array(
			'title'=>'Позволить работать с закрытой темой, как с открытой',
			'descr'=>'Публиковать/править/удалять посты',
			'type'=>'check',
			'default'=>ForumForums::$rights['complaint'],
			'bypost'=>&$post,
			'options'=>array(
				'extra'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
	);
}

#Работа с пользователями

function EditUser($id,$errors=array())
{global$Eleanor,$title;
	$R=Eleanor::$Db->Query('SELECT `id`,`groups` `group`,`name`,`full_name` FROM `'.P.'users_site` WHERE `id`='.$id.' LIMIT 1');
	if(!$user=$R->fetch_assoc())
	{
		$Eleanor->Forum->Service->DeleteUser($id);
		return GoAway();
	}
	$user['group']=explode(',,',trim($user['group'],','));
	$R=Eleanor::$Db->Query('SELECT `title_l` `title`,`html_pref`,`html_end` FROM `'.P.'groups` WHERE `id`='.(int)reset($user['group']).' LIMIT 1');
	if($user['group']=$R->fetch_assoc())
		$user['group']['title']=$user['group']['title'] ? Eleanor::FilterLangValues((array)unserialize($user['group']['title'])) : '';

	if($errors)
	{
		$bypost=true;
		$values=array(
			'posts'=>isset($_POST['posts']) ? (int)$_POST['posts'] : 0,
			'restrict_post'=>isset($_POST['restrict_post']),
			'restrict_post_to'=>isset($_POST['restrict_post_to']) ? (string)$_POST['restrict_post_to'] : '',
			'descr'=>isset($_POST['descr']) ? (string)$_POST['descr'] : '',
		);
	}
	else
	{
		$bypost=false;
		$R=Eleanor::$Db->Query('SELECT * FROM `'.$Eleanor->module['config']['fu'].'` WHERE `id`='.$id.' LIMIT 1');
		if(!$values=$R->fetch_assoc())
			return GoAway();
		if((int)$values['restrict_post_to']==0)
			$values['restrict_post_to']='';
	}

	$user['_aedit']=$Eleanor->Url->file.'?section=management&amp;module=users&amp;edit='.$id;
	if(isset($_GET['noback']))
		$back='';
	else
		$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');

	$title[]='Редактирование пользователя &quot;'.htmlspecialchars($user['name'],ELENT,CHARSET).'&quot;';
	$c=Eleanor::$Template->EditUser($user,$values,$bypost,$back,$errors);
	Start();
	echo$c;
}

#Работа с префиксами тем

function AddEditPrefix($id,$errors=array())
{global$Eleanor,$title;
	$mc=$Eleanor->module['config'];
	if($id)
	{
		$R=Eleanor::$Db->Query('SELECT * FROM `'.$mc['pr'].'` WHERE `id`='.$id.' LIMIT 1');
		if(!$values=$R->fetch_assoc())
			return GoAway();
		if(!$errors)
		{
			$values['forums']=$values['forums'] ? explode(',,',trim($values['forums'],',')) : array();
			$values['title']=array();
			$R=Eleanor::$Db->Query('SELECT `language`,`title` FROM `'.$mc['pl'].'` WHERE `id`='.$id);
			while($temp=$R->fetch_assoc())
				if(!Eleanor::$vars['multilang'] and (!$temp['language'] or $temp['language']==Language::$main))
				{
					foreach(array_slice($temp,1) as $tk=>$tv)
						$values[$tk]=$tv;
					if(!$temp['language'])
						break;
				}
				elseif(!$temp['language'] and Eleanor::$vars['multilang'])
				{
					foreach(array_slice($temp,1) as $tk=>$tv)
						$values[$tk][Language::$main]=$tv;
					$values['_onelang']=true;
					break;
				}
				elseif(Eleanor::$vars['multilang'] and isset(Eleanor::$langs[$temp['language']]))
					foreach(array_slice($temp,1) as $tk=>$tv)
						$values[$tk][$temp['language']]=$tv;

			if(Eleanor::$vars['multilang'])
			{
				if(!isset($values['_onelang']))
					$values['_onelang']=false;
				$values['_langs']=array_keys($values['title']);
			}
		}
		$title[]='Редактирование префикса тем';
	}
	else
	{
		$values=array(
			'forums'=>'',
			#Языковые
			'title'=>Eleanor::$vars['multilang'] ? array(''=>'') : '',

		);
		if(Eleanor::$vars['multilang'])
		{
			$values['_onelang']=true;
			$values['_langs']=array_keys(Eleanor::$langs);
		}
		$title[]='Добавление префикса тем';
	}

	if($errors)
	{
		if($errors===true)
			$errors=array();
		$bypost=true;
		$values['forums']=isset($_POST['forums']) ? (array)$_POST['forums'] : array();
		if(Eleanor::$vars['multilang'])
			$values['title']=isset($_POST['title']) ? (array)$_POST['title'] : array();
		else
			$values['title']=isset($_POST['title']) ? (string)$_POST['title'] : '';
	}
	else
		$bypost=false;

	$links=array(
		'delete'=>$id ? $Eleanor->Url->Construct(array('delete-prefix'=>$id,'noback'=>1)) : false,
	);

	if(isset($_GET['noback']))
		$back='';
	else
		$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');

	$c=Eleanor::$Template->AddEditPrefix($id,$values,$Eleanor->Forum->Forums->SelectOptions($values['forums']),$errors,$bypost,$back,$links);
	Start();
	echo$c;
}

function SavePrefix($id)
{global$Eleanor;
	$errors=array();
	$forums=isset($_POST['forums']) ? (array)$_POST['forums'] : array();
	sort($forums,SORT_NUMERIC);

	if(Eleanor::$vars['multilang'] and !isset($_POST['_onelang']))
	{
		$langs=empty($_POST['_langs']) || !is_array($_POST['_langs']) ? array() : $_POST['_langs'];
		$langs=array_intersect(array_keys(Eleanor::$langs),$langs);
		if(!$langs)
			$langs=array(Language::$main);
	}
	else
		$langs=array('');

	$values=array(
		'forums'=>$forums ? ','.join(',,',$forums).',' : '',
	);

	if(Eleanor::$vars['multilang'])
	{
		$lvalues=array(
			'title'=>array(),
		);
		foreach($langs as $l)
		{
			$lng=$l ? $l : Language::$main;
			$lvalues['title'][$l]=isset($_POST['title'],$_POST['title'][$lng]) && is_array($_POST['title']) ? (string)Eleanor::$POST['title'][$lng] : '';
		}
	}
	else
		$lvalues=array(
			'title'=>array(''=>isset($_POST['title']) ? (string)Eleanor::$POST['title'] : ''),
		);

	$ml=in_array('',$langs) ? Language::$main : '';
	$emp=array('title'=>array());
	foreach($emp as $kf=>&$field)
		foreach($lvalues[$kf] as $k=>&$v)
			$field[$k]=$v=='' && (in_array($k,$langs) or $ml==$k);

	foreach($emp['title'] as $k=>&$v)
		if($v)
		{
			$er='EMPTY_TITLE'.strtoupper($k ? '_'.$k : '');
			$errors[$er]=$lang['EMPTY_NAME']($k);
		}

	if($errors)
		return AddEditPrefix($id,$errors);

	$mc=$Eleanor->module['config'];
	if($id)
	{
		$R=Eleanor::$Db->Query('SELECT `forums` FROM `'.$mc['pr'].'` WHERE `id`='.$id.' LIMIT 1');
		if(!list($fs)=$R->fetch_row())
			return GoAway();
		Eleanor::$Db->Update($mc['pr'],$values,'id='.$id.' LIMIT 1');
		if($fs)
			Eleanor::$Db->Update($mc['f'],array('!prefixes'=>'REPLACE(`prefixes`,\','.$id.',\',\'\')'),'`id`'.Eleanor::$Db->In(explode(',,',trim($fs,','))));
		if($forums)
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`prefixes` FROM `'.$mc['f'].'` WHERE `id`'.Eleanor::$Db->In($forums));
			while($a=$R->fetch_assoc())
			{
				$a['prefixes']=$a['prefixes'] ? explode(',,',trim($a['prefixes'],',')) : array();
				$a['prefixes'][]=$id;
				sort($a['prefixes'],SORT_NUMERIC);
				Eleanor::$Db->Update($mc['f'],array('prefixes'=>','.join(',,',$a['prefixes']).','),'`id`='.$a['id'].' LIMIT 1');
			}
		}

		$values=array();
		foreach($langs as &$v)
			$values[]=array(
				'id'=>$id,
				'language'=>$v,
				'title'=>$lvalues['title'][$v],
			);
		Eleanor::$Db->Replace($mc['pl'],$values);
	}
	else
	{
		$id=Eleanor::$Db->Insert($mc['pr'],$values);
		Eleanor::$Db->Update($mc['f'],array('!prefixes'=>'CONCAT(`prefixes`,\','.$id.',\')'),'`id`'.Eleanor::$Db->In($forums));

		$values=array('id'=>array(),'language'=>array(),'title'=>array());
		foreach($langs as &$v)
		{
			$values['id'][]=$id;
			$values['language'][]=$v;
			$values['title'][]=$lvalues['title'][$v];
		}
		Eleanor::$Db->Insert($mc['pl'],$values);
	}
	Eleanor::$Cache->Lib->DeleteByTag($mc['n'].'_prefixes_');
	GoAway(empty($_POST['back']) ? true : $_POST['back']);
}

#Работа с модераторами

function AddEditModer($id,$errors=array())
{global$Eleanor,$title;
	if($id)
	{
		$R=Eleanor::$Db->Query('SELECT * FROM `'.$Eleanor->module['config']['fm'].'` WHERE `id`='.$id.' LIMIT 1');
		if(!$values=$R->fetch_assoc())
			return GoAway();
		if(!$errors)
		{
			$values['forums']=$values['forums'] ? explode(',,',trim($values['forums'],',')) : array();
			$values['groups']=$values['groups'] ? explode(',,',trim($values['groups'],',')) : array();
			$values['users']=$values['users'] ? explode(',,',trim($values['users'],',')) : array();
			if($values['users'])
			{
				$R=Eleanor::$UsersDb->Query('SELECT `id`,`name` FROM `'.USERS_TABLE.'` WHERE `id`'.Eleanor::$UsersDb->In($values['users']));
				$values['users']=array();
				while($a=$R->fetch_assoc())
					$values['users'][ $a['id'] ]=$a['name'];
			}
		}
		$title[]='Редактирование модератора';
	}
	else
	{
		$values=array(
			'users'=>array(),
			'groups'=>array(),
			'forums'=>'',
			'descr'=>'',
		);
		$title[]='Добавление модератора';
	}

	if($errors)
	{
		if($errors===true)
			$errors=array();
		$bypost=true;
		$values['forums']=isset($_POST['forums']) ? (array)$_POST['forums'] : array();
		$values['groups']=isset($_POST['groups']) ? (array)$_POST['groups'] : array();
		$values['descr']=isset($_POST['descr']) ? (string)$_POST['descr'] : '';

		$names=isset($_POST['names']) ? (array)$_POST['names'] : array();
		$values['users']=isset($_POST['users']) ? (array)$_POST['users'] : array();
		$values['users']=count($names)==count($values['users']) ? array_combine($values['users'],$names) : array();
	}
	else
		$bypost=false;

	$controls=ModerPermissions($bypost,20);
	foreach($controls as $k=>&$v)
		if(isset($values[$k]))
			$v['value']=$values[$k];
	$values=$Eleanor->Controls->DisplayControls($controls)+$values;

	$links=array(
		'delete'=>$id ? $Eleanor->Url->Construct(array('delete-moder'=>$id,'noback'=>1)) : false,
	);

	if(isset($_GET['noback']))
		$back='';
	else
		$back=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');

	$c=Eleanor::$Template->AddEditModer($id,$controls,$values,$Eleanor->Forum->Forums->SelectOptions($values['forums']),$errors,$bypost,$back,$links);
	Start();
	echo$c;
}

function SaveModer($id)
{global$Eleanor;
	$users=isset($_POST['names']) ? (array)$_POST['names'] : array();
	$groups=isset($_POST['groups']) ? (array)$_POST['groups'] : array();
	$forums=isset($_POST['forums']) ? (array)$_POST['forums'] : array();

	if($users)
	{
		$R=Eleanor::$UsersDb->Query('SELECT `id` FROM `'.USERS_TABLE.'` WHERE `name`'.Eleanor::$UsersDb->In($users));
		$users=array();
		while($a=$R->fetch_assoc())
			$users[]=$a['id'];
	}

	if($users)
	{
		$Eleanor->Forum->Service->CheckUser($users);
		sort($users,SORT_NUMERIC);
	}

	sort($groups,SORT_NUMERIC);
	sort($forums,SORT_NUMERIC);

	$errors=array();
	if(!$forums)
		$errors['EMPTY_FORUMS']='Не выбраны форумы';
	if(!$groups and !$user)
		$errors['EMPTY_MODERS']='Не ни пользователи, ни группы';

	try
	{
		$bypost=false;
		$values=$Eleanor->Controls->SaveControls(ModerPermissions($bypost));
	}
	catch(EE$E)
	{
		$errors['ERROR']=$E->getMessage();
	}

	if($errors)
		return AddEditModer($id,$errors);

	$values+=array(
		'groups'=>$groups ? ','.join(',,',$groups).',' : '',
		'users'=>$users ? ','.join(',,',$users).',' : '',
		'forums'=>$forums ? ','.join(',,',$forums).',' : '',
		'descr'=>isset($_POST['descr']) ? $_POST['descr'] : '',
	);

	$mc=$Eleanor->module['config'];
	if($id)
	{
		$R=Eleanor::$Db->Query('SELECT `groups`,`users`,`forums` FROM `'.$mc['fm'].'` WHERE `id`='.$id.' LIMIT 1');
		if(!list($gs,$us,$fs)=$R->fetch_row())
			return GoAway();
		Eleanor::$Db->Update($mc['fm'],$values,'id='.$id.' LIMIT 1');
		$gs=$gs ? explode(',,',trim($gs,',')) : array();
		if($gs!=$groups)
		{
			Eleanor::$Db->Update($mc['fg'],array('!moderator'=>'REPLACE(`moderator`,\','.$id.',\',\'\')'),'`id`'.Eleanor::$Db->In($gs));
			if($groups)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`moderator` FROM `'.$mc['fg'].'` WHERE `id`'.Eleanor::$Db->In($groups));
				while($a=$R->fetch_assoc())
				{
					$a['moderator']=$a['moderator'] ? explode(',,',trim($a['moderator'],',')) : array();
					$a['moderator'][]=$id;
					sort($a['moderator'],SORT_NUMERIC);
					Eleanor::$Db->Update($mc['fg'],array('moderator'=>','.join(',,',$a['moderator']).','),'`id`='.$a['id'].' LIMIT 1');
				}
			}
		}

		$us=$us ? explode(',,',trim($us,',')) : array();
		if($us!=$users)
		{
			Eleanor::$Db->Update($mc['fu'],array('!moderator'=>'REPLACE(`moderator`,\','.$id.',\',\'\')'),'`id`'.Eleanor::$Db->In($us));
			if($users)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`moderator` FROM `'.$mc['fu'].'` WHERE `id`'.Eleanor::$Db->In($users));
				while($a=$R->fetch_assoc())
				{
					$a['moderator']=$a['moderator'] ? explode(',,',trim($a['moderator'],',')) : array();
					$a['moderator'][]=$id;
					sort($a['moderator'],SORT_NUMERIC);
					Eleanor::$Db->Update($mc['fu'],array('moderator'=>','.join(',,',$a['moderator']).','),'`id`='.$a['id'].' LIMIT 1');
				}
			}
		}
		if($fs)
			Eleanor::$Db->Update($mc['f'],array('!moderators'=>'REPLACE(`moderators`,\','.$id.',\',\'\')'),'`id`'.Eleanor::$Db->In(explode(',,',trim($fs,','))));
		if($forums)
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`moderators` FROM `'.$mc['f'].'` WHERE `id`'.Eleanor::$Db->In($forums));
			while($a=$R->fetch_assoc())
			{
				$a['moderators']=$a['moderators'] ? explode(',,',trim($a['moderators'],',')) : array();
				$a['moderators'][]=$id;
				sort($a['moderators'],SORT_NUMERIC);
				Eleanor::$Db->Update($mc['f'],array('moderators'=>','.join(',,',$a['moderators']).','),'`id`='.$a['id'].' LIMIT 1');
			}
		}
	}
	else
	{
		$values['!date']='NOW()';
		$id=Eleanor::$Db->Insert($mc['fm'],$values);
		Eleanor::$Db->Update($mc['f'],array('!moderators'=>'CONCAT(`moderators`,\','.$id.',\')'),'`id`'.Eleanor::$Db->In($forums));
		if($users)
			Eleanor::$Db->Update($mc['fu'],array('!moderator'=>'CONCAT(`moderator`,\','.$id.',\')'),'`id`'.Eleanor::$Db->In($users));
		if($groups)
			Eleanor::$Db->Update($mc['fg'],array('!moderator'=>'CONCAT(`moderator`,\','.$id.',\')'),'`id`'.Eleanor::$Db->In($groups));
	}
	Eleanor::$Cache->Lib->DeleteByTag($mc['n'].'_moders_');
	GoAway(empty($_POST['back']) ? true : $_POST['back']);
}

function ModerPermissions(&$post,$ti=1)
{global$Eleanor;
	if(!class_exists('ForumForums',false))
		include$Eleanor->module['path'].'forums.php';
	return array(
		'Одиночная модерация',
		'movet'=>array(
			'title'=>'Перемещение тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['movet'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'move'=>array(
			'title'=>'Перемещение сообщений',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['move'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'deletet'=>array(
			'title'=>'Удаление тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['deletet'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'delete'=>array(
			'title'=>'Удаление сообщений',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['delete'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'editt'=>array(
			'title'=>'Правка заголовков тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['editt'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'edit'=>array(
			'title'=>'Правка сообщений',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['edit'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'chstatust'=>array(
			'title'=>'Изменение статуса тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['chstatust'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'chstatus'=>array(
			'title'=>'Изменение статуса сообщений',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['chstatus'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'merget'=>array(
			'title'=>'Объединение тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['merget'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'merge'=>array(
			'title'=>'Объединение сообщений',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['merge'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'pin'=>array(
			'title'=>'Закрепление / открепление тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['pin'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'opcl'=>array(
			'title'=>'Открытие / закрытие тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['opcl'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'editq'=>array(
			'title'=>'Разрешить редактирование опросов в сообщениях',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['editq'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'viewip'=>array(
			'title'=>'Отображать IP адреса сообщений',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['viewip'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'user_warn'=>array(
			'title'=>'Разрешить предупреждать пользователей',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['user_warn'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'Мультимодерация',
		'mmovet'=>array(
			'title'=>'Мультиперемещение тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['mmovet'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'mmove'=>array(
			'title'=>'Мультиперемещение сообщений',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['mmove'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'mdeletet'=>array(
			'title'=>'Мультиудаление тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['mdeletet'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'mdelete'=>array(
			'title'=>'Мультиудаление сообщений',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['mdelete'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'mchstatust'=>array(
			'title'=>'Мультиизменение статусов тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['mchstatust'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'mchstatus'=>array(
			'title'=>'Мультиизменение статусов сообщений',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['mchstatus'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'mopcl'=>array(
			'title'=>'Мультиоткрытие / закрытие тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['mopcl'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'mpin'=>array(
			'title'=>'Мультизакрепление / открепление тем',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['mpin'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
		'editrep'=>array(
			'title'=>'Разрешить править репутацию',
			'descr'=>'',
			'type'=>'check',
			'default'=>ForumForums::$moder['editrep'],
			'bypost'=>&$post,
			'options'=>array(
				'addon'=>array(
					'tabindex'=>$ti++,
				),
			),
		),
	);
}