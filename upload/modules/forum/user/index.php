<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
global$Eleanor,$title;

include$Eleanor->module['path'].'forum.php';
include$Eleanor->module['path'].'core.php';

$Eleanor->Forum=new ForumCore;
$Forum=$Eleanor->Forum;
$Forum->config=$Forum->Forum->config;
$Forum->vars=Eleanor::LoadOptions($Forum->config['opts'],true);
$Forum->Language=new Language(true);
$Forum->Language->loadfrom=dirname(__DIR__);

$Eleanor->module['etag']='';#Дополнение к ETAG

if($Eleanor->Url->is_static)
{
	/*
		Форматы ссылок.
		Группа ссылок на форумы:
		/forum/
		/forum/форум/
		/forum/форум/?page=1
		/forum/форум/подфорум/
		/forum/форум/подфорум/?page=2
		/forum/forum-122?page=6

		Группа ссылок на тему, пост:
		/forum/форум/подфорум/тема
		/forum/форум/подфорум/тема?page=1
		/forum/post-1
		/forum/topic-233?page=4

		Группу ссылок типа "конкретное действие":
		/forum/new-topic-3
		/forum/answer-3
		/forum/edit-3
		/forum/online
		/forum/search
		/forum/search-id
		...
	*/
	$ending=$Eleanor->Url->GetEnding($Eleanor->Url->delimiter,true);
	$furi=$Eleanor->Url->Parse();
	$furi=isset($furi['']) ? (array)$furi[''] : array();

	if($ending)
	{
		include Forum::$root.'user/forums.php';
		return ShowForum($furi);
	}
	else
	{
		$turi=array_pop($furi);
		if(count($furi)>1)
		{
			$do=false;
			$Forum->LoadUser();
			include Forum::$root.'user/topic.php';
			return ShowTopic($furi,$turi);
		}
		elseif(preg_match('#^(forum|topic|post|new\-topic|new\-post|edit|edit\-topic|answer|subscribe\-topic|subscribe\-forum|go\-new\-post|go\-last\-post|find\-post|reputation|given)\-(.+?)$#i',$turi,$m)>0)
		{
			$do=$m[1];
			$id=$m[2];
		}
		else
		{
			$do=$turi;
			$id=false;
		}
	}
}
elseif(isset($_GET['do']))
{
	$do=(string)$_GET['do'];
	$id=false;
}
else
{
	$do=$id=false;
	foreach(array('forum','topic','post','new-topic','new-post','edit-topic','edit','edit-topic','answer','subscribe-topic','subscribe-forum','go-new-post','go-last-post','find-post','reputation','given','activate-post') as $v)
		if(isset($_GET[$v]))
		{
			$do=$v;
			$id=(string)$_GET[$v];
			break;
		}
}

switch($do)
{
	case'new-topic':#Новая тема
	case'new-post':#Новое сообщение
	case'edit':#Правка сообщения
	case'edit-topic':#Правка темы
	case'answer':#Ответ на сообщение
		include Forum::$root.'user/post.php';
	break;
	case'subscribe-forum':#Подписка или отписка на форум
	case'subscribe-topic':#Подписка или отписка на тему
		$Forum->LoadUser();
		if($Forum->user)
			include Forum::$root.'user/subscribe.php';
		else
			ExitPage();
	break;
	case'go-new-post':#Переход к первому непрочитанному сообщению в теме
	case'go-last-post':#Переход к последнему сообщению в теме
	case'find-post':#Найти пост
	case'post':#Просмотр поста
	case'activate-post':#Активировать пост
	case'topic':
		$Forum->LoadUser();
		include Forum::$root.'user/topic.php';
	break;
	case'top':#Топ пользователей по репутации
	case'online':#Просмотр списка кто онлайн
	case'options':#Опции на форум: всегда входить скрытым и т.п.
	case'users':#Список пользователей
	case'reputation':#Отображение репутации пользователя
	case'given':#Отображение отданой репутации пользователем другим
	case'stats':#Отображение статистики за сегодня
	case'moderators':#Просмотр всех модераторов
		include Forum::$root.'user/misc.php';
	break;
	case'moderate':#Просмотр всех топиков на модерации и постов, ожидающих модерации
		include Forum::$root.'user/moderate.php';
	break;
	case'search':#Поиск
		include Forum::$root.'user/search.php';
	break;
	default:
		include Forum::$root.'user/forums.php';
		if($id)
			ShowForum((int)$id);
		else
		{
			Eleanor::$Template->queue[]='ForumMain';
			$title[]='Форум';
			$stats=array();
			$R=Eleanor::$Db->Query('(SELECT COUNT(`moved_to`) `cnt` FROM `'.$Forum->config['ft'].'` WHERE `moved_to`=0)UNION ALL(SELECT COUNT(`id`) `cnt` FROM `'.$Forum->config['fp'].'`)UNION ALL(SELECT COUNT(`id`) `cnt` FROM `'.$Forum->config['fu'].'`)');
			list($stats['topics'])=$R->fetch_row();
			list($stats['posts'])=$R->fetch_row();
			list($stats['users'])=$R->fetch_row();
			$Forum->LoadUser();
			SetData();
			$c=Eleanor::$Template->ForumMain(Forums(),$stats,$Forum->GetOnline());
			$Eleanor->origurl=PROTOCOL.Eleanor::$punycode.Eleanor::$site_path.$Eleanor->Url->Prefix(false);
			Start();
			echo$c;
		}
}

function SetData()
{global$Eleanor;
	#Cron
	if(isset(Eleanor::$services['cron']))
	{
		$cron=Eleanor::$Cache->Get($Eleanor->Forum->config['n'].'_nextrun');
		$t=time();
		$cron=$cron===false && $cron<=$t ? Eleanor::$services['cron']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'language'=>Language::$main==LANGUAGE ? false : Language::$main,'rand'=>$t)) : '';
	}
	else
		$cron=false;

	$Links=$Eleanor->Forum->Links;
	$Eleanor->module+=array(
		'cron'=>$cron,
		'links'=>array(
			'main'=>$q=$Eleanor->Url->Prefix(false),
			'search'=>$Links->Action('search'),
			'users'=>$Links->Action('users'),
			'online'=>$Links->Action('online'),
			'top'=>$Links->Action('top'),
			'moderators'=>$Links->Action('moderators'),
			'stats'=>$Links->Action('stats'),
			'options'=>$Eleanor->Forum->user ? $Links->Action('options') : false,
			'rss_topics'=>Eleanor::$services['rss']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'show'=>'topics')),
			'rss_posts'=>Eleanor::$services['rss']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'])),
		),
	);
}