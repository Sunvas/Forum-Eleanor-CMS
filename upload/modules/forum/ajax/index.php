<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
global$Eleanor;

include$Eleanor->module['path'].'forum.php';
include$Eleanor->module['path'].'core.php';

$Eleanor->Forum=new ForumCore;
$Forum=$Eleanor->Forum;
$Forum->config=$Forum->Forum->config;
$Forum->vars=Eleanor::LoadOptions($Forum->config['opts'],true);
$Forum->Language=new Language(true);
$Forum->Language->loadfrom=dirname(__DIR__);
$paths=Eleanor::$Template->paths;#Бэкап всех путей к шаблонам перед BeAs();

$event=isset($_POST['event']) ? (string)$_POST['event'] : '';
switch($event)
{
	case'progress':#Для админки: прогресс выполнения заданий
		BeAs('admin');

		$ids=isset($_POST['ids']) ? (array)$_POST['ids'] : array();
		if(!Eleanor::$Login->IsUser() or !$ids)
			return Error();

		$res=array();
		$R=Eleanor::$Db->Query('SELECT `id`,`status`,`done`,`total` FROM `'.$Forum->config['ta'].'` WHERE `id`'.Eleanor::$Db->In($ids));
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
		Eleanor::$Template->paths+=$paths;
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
		Eleanor::$Template->paths+=$paths;
		$Forum->LoadUser();
		include __DIR__.'/post.php';
	break;
	case'preview':
		BeAs('user');
		Eleanor::$Template->paths+=$paths;
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

		$replace=false;
		$attaches=$Forum->Attach->GetFromText($s);
		if($attaches)
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`p`,`downloads`,`size`,IF(`name`=\'\',`file`,`name`) `name`,`file` FROM `'.$config['fa'].'` WHERE `id`'.Eleanor::$Db->In($attaches));
			$attaches=array();
			while($a=$R->fetch_assoc())
				$attaches[ $a['id'] ]=array_slice($a,1);
			if($attaches)
			{
				$s=array('text'=>$s);
				$replace=$Forum->Attach->DecodePosts($s,$attaches,true);
				$s=$s['text'];
			}
		}

		$s=$Eleanor->Editor_result->GetHtml($s,true,false);

		if($replace)
			$s=str_replace($replace['from'],$replace['to'],$s);

		Result($s);
	break;
	default:
		Error(Eleanor::$Language['main']['unknown_event']);
}