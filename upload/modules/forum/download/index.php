<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
BeAs('user');
global$Eleanor;

include$Eleanor->module['path'].'forum.php';
include$Eleanor->module['path'].'core.php';

$Eleanor->Forum=new ForumCore;
$Forum=$Eleanor->Forum;
$config=$Forum->Forum->config;

$id=isset($_GET['id']) ? (int)$_GET['id'] : 0;

$R=Eleanor::$Db->Query('SELECT `a`.`f`,`a`.`t`,`a`.`p`,`a`.`name`,`a`.`file`,`p`.`status`,`p`.`author_id` `paid`,`t`.`id`,`t`.`status` `tstatus`,`t`.`author_id` FROM `'.$config['fa'].'` `a` INNER JOIN `'.$config['fp'].'` `p` ON `a`.`p`=`p`.`id` INNER JOIN `'.$config['ft'].'` `t` ON `a`.`t`=`t`.`id` WHERE `a`.`id`='.$id.' LIMIT 1');
if(!$data=$R->fetch_assoc())
{
	Exit404:
	return ExitPage(404);
}

$Forum->LoadUser();
if(!$Forum->CheckTopicAccess($data))
{
	Exit403:
	return ExitPage(403);
}

$forum=$Forum->Forums->GetForum($data['f']);
$moder=false;
$qtopic=$data['tstatus']!=1;#Queued topic?
$qpost=!in_array($data['status'],array(-2,1));#Queued post?

if(($qtopic or $qpost) and $forum['moderators'])
	list(,$moder)=$Forum->Moderator->ByIds($forum['moderators'],array('chstatust','chstatus','mchstatust','mchstatus'),$config['n'].'_moders_fp'.$forum['id'].$Forum->language);

if($qtopic)
{
	$gt=$Forum->GuestSign('t');

	if(($data['tstatus']==0 or $data['tstatus']==-1 and !($Forum->user and $Forum->user['id']==$data['author_id'] or in_array($data['t'],$gt))) and (!$moder or !in_array(1,$moder['chstatust']) and !in_array(1,$moder['mchstatust'])))
		goto Exit403;
}

if($qpost)
{
	$gp=$Forum->GuestSign('p');

	if(($data['status']==0 or in_array($data['status'],array(-1,-3)) and !($Forum->user and $Forum->user['id']==$data['paid'] or in_array($data['p'],$gp))) and (!$moder or !in_array(1,$moder['chstatus']) and !in_array(1,$moder['mchstatus'])))
		goto Exit403;
}

$rights=$Forum->ForumRights($forum['id']);
if(!in_array(1,$rights['attach']))
	goto Exit403;

$file=$config['attachroot'].'p'.$data['p'].DIRECTORY_SEPARATOR.$data['file'];
if(!is_file($file))
	goto Exit404;

Eleanor::$Db->Update($config['fa'],array('!downloads'=>'`downloads`+1'),'`id`='.$id.' LIMIT 1');

if($forum['hide_attach'] or $data['name'] and $data['file'])
	Files::OutPutStream(array(
		'file'=>$file,
		'filename'=>$data['name'],
		'save'=>preg_match('#\.(gif|png|jpe?g|bmp)$#i',$data['file'])==0,
	));
else
	GoAway($config['attachpath'].'p'.$data['p'].'/'.$data['file']);