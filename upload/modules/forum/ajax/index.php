<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;

global$Eleanor;
$Eleanor->module['config']=$mc=include$Eleanor->module['path'].'config.php';
include$Eleanor->module['path'].'forum.php';
include$Eleanor->module['path'].'core.php';

Eleanor::LoadOptions($mc['opts']);
$Eleanor->Forum=new ForumCore($mc);
$Forum=$Eleanor->Forum;
$Forum->config=$Forum->Forum->config;
$Forum->Language=new Language(true);
$Forum->Language->loadfrom=dirname(__DIR__);

$ev=isset($_POST['event']) ? (string)$_POST['event'] : '';
switch($ev)
{
	case'progress':#Для админки: прогресс выполнения заданий
		BeAs('admin');
		if(!Eleanor::$Login->IsUser())
			return Error();
		$ids=isset($_POST['ids']) ? (array)$_POST['ids'] : array();
		if(!$ids)
			return Error();
		$res=array();
		$R=Eleanor::$Db->Query('SELECT `id`,`status`,`done`,`total` FROM `'.$mc['ta'].'` WHERE `id`'.Eleanor::$Db->In($ids));
		while($a=$R->fetch_assoc())
			$res[$a['id']]=array(
				'done'=>$a['status']!='process',
				'percent'=>$a['total']>0 ? round($a['done']/$a['total']*100) : 0,
				'val'=>$a['done'],
				'total'=>$a['total'],
			);
		Result($res ? $res : false);
	break;

	case'all-read':#Пометка всего прочитанным
	case'forum-read':#Пометка конкретного форума прочитанным
	case'topic-read':#Пометка конкретной темы прочитанной
		BeAs('user');
		$Forum->LoadUser();
		include __DIR__.'/markread.php';
	break;

	case'subscribe-forum':#Подписка на форум
	case'subscribe-topic':#Подписка на тему
		BeAs('user');
		$Forum->LoadUser();
		include __DIR__.'/subscribe.php';
	break;

	case'pin-topic':#Закрепление темы из темы
	case'pin-post':#Закрепление поста из темы
		BeAs('user');
		$Forum->LoadUser();
		include __DIR__.'/moderate.php';
	break;

	case'show-post':#Показ поста
		BeAs('user');
		$Forum->LoadUser();
		$do=false;
		include$Eleanor->module['path'].'user/topic.php';
		Result(ShowPost(isset($_POST['id']) ? (int)$_POST['id'] : 0,true));
	break;

	case'edit':#Правка поста
	case'save':#Сохранение поста
	case'delete':#Удаление поста
	case'new-post':#Новый пост
	case'lnp':#Load New Posts
		BeAs('user');
		$Forum->LoadUser();
		include __DIR__.'/post.php';
	break;
	case'preview':
		BeAs('user');
		$Forum->LoadUser();

		$Eleanor->Editor_result->type='bb';
		$Eleanor->Editor_result->ownbb=$Eleanor->Editor_result->smiles=true;
		$s=isset($_POST['text']) ? (string)$_POST['text'] : '';
		if($Eleanor->Editor_result->ownbb and !isset(OwnBB::$replace['quote']) and strpos($s,'[quote')!==false)
		{
			if(!class_exists('ForumBBQoute',false))
				include$Eleanor->module['path'].'Misc/bb-quote.php';
			OwnBB::$replace['quote']='ForumBBQoute';
		}
		Result($Eleanor->Editor_result->GetHtml($s,true,false));
	break;
	default:
		Error(Eleanor::$Language['main']['unknown_event']);
}