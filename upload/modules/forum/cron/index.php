<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
global$Eleanor;

$mc=include$Eleanor->module['path'].'config.php';
include$Eleanor->module['path'].'forum.php';
include$Eleanor->module['path'].'core.php';
$F=new ForumCore($mc);

if(isset($_GET['f']))
{
	$forum=is_array($_GET['f']) ? $_GET['f'] : explode(',',(string)$_GET['f']);
	foreach($forum as &$v)
		$v=(int)$v;
	$F->Subscriptions->SendForums($forum);
}

if(isset($_GET['t']))
{
	$topic=is_array($_GET['t']) ? $_GET['t'] : explode(',',(string)$_GET['t']);
	foreach($topic as &$v)
		$v=(int)$v;
	$F->Subscriptions->SendTopics($topic);
}

if(Eleanor::$Cache->Get($mc['n'].'-runned')===false)
{
	Eleanor::$Cache->Put($mc['n'].'-runned',true,200);

	$d=date('Y-m-d H:i:s');
	$r=$F->Subscriptions->SendForums() && $F->Subscriptions->SendTopics();
	if($r)
	{
		$nextrun=strtotime('+1 DAY',mktime(0,0,0));
		$R=Eleanor::$Db->Query('(SELECT UNIX_TIMESTAMP(`nextsend`) `nextsend` FROM `'.$mc['ts'].'` WHERE `sent`=0 AND `nextsend`>\''.$d.'\' ORDER BY `nextsend` ASC LIMIT 1)UNION ALL(SELECT UNIX_TIMESTAMP(`nextsend`) `nextsend` FROM `'.$mc['fs'].'` WHERE `sent`=0 AND `nextsend`>\''.$d.'\' ORDER BY `nextsend` ASC LIMIT 1)');
		while($a=$R->fetch_row())
			if($nextrun>$a[0])
				$nextrun=$a[0];

		Eleanor::$Cache->Put($mc['n'].'_nextrun',$nextrun);
	}
	Eleanor::$Cache->Obsolete($mc['n'].'-runned');
}
Start();