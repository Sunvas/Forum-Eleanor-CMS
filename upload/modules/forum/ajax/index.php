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

global$Eleanor;
$Eleanor->module['config']=$mc=include$Eleanor->module['path'].'config.php';
include$Eleanor->module['path'].'forum.php';
include$Eleanor->module['path'].'core.php';

Eleanor::LoadOptions($mc['opts']);
$Eleanor->Forum=new ForumCore($mc);

$ev=isset($_POST['event']) ? (string)$_POST['event'] : '';
switch($ev)
{
	case'progress':
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

	case'allread':
	case'forumread':
	case'topicread':
		BeAs('user');
		$Eleanor->Forum->LoadUser();
		include Forum::$root.'markread.php';
	break;

	case'fsubscribe':
	case'tsubscribe':
		BeAs('user');
		$Eleanor->Forum->LoadUser();
		include Forum::$root.'subscribe.php';
	break;

	case'topic-move-forums':
	case'topics-move-forums':
	case'topic-pin':
		BeAs('user');
		$Eleanor->Forum->LoadUser();
		include Forum::$root.'moderate.php';
	break;

	case'showpost':#Показ поста
		BeAs('user');
		$Eleanor->Forum->LoadUser();
		$do=false;
		include$Eleanor->module['path'].'user/topic.php';
		Result(ShowPost(isset($_POST['id']) ? (int)$_POST['id'] : 0,true));
	break;

	case'edit':#Правка поста
	case'save':#Сохранение поста
	case'delete':#Удаление поста
	case'newpost':#Новый пост
	case'lnp':#Load New Posts
		BeAs('user');
		$Eleanor->Forum->LoadUser();
		include Forum::$root.'post.php';
	break;
	default:
		$type=isset($_POST['type']) ? (string)$_POST['type'] : '';
		if($type=='bbpreview')
		{
			BeAs('user');
			$Eleanor->Forum->LoadUser();

			$Eleanor->Editor->type='bb';
			$Eleanor->Editor->ownbb=isset($_POST['ownbb']);
			$Eleanor->Editor->smiles=isset($_POST['smiles']);
			$s=isset($_POST['text']) ? (string)$_POST['text'] : '';
			if($Eleanor->Editor->ownbb and !isset(OwnBB::$replace['quote']) and strpos($s,'[quote')!==false)
			{
				if(!class_exists('ForumBBQoute',false))
					include$Eleanor->module['path'].'Misc/bb-quote.php';
				OwnBB::$replace['quote']='ForumBBQoute';
			}
			Result($Eleanor->Editor_result->GetHtml($s,true,false));
		}
		else
			Error(Eleanor::$Language['main']['unknown_event']);
}

/*function CheckForumAccess($fid)
{global$Eleanor;
	foreach($Eleanor->Forum->ug as &$g)
	{
		$fr=$Eleanor->Forum->GroupPerms($fid,$g);
		foreach($fr as $k=>&$v)
			$rights[$k][]=$v;
	}
	return (isset($Eleanor->Forum->Forums->dump[$fid]) and in_array(1,$rights['access']));
}*/