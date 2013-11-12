<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
global$Eleanor;

BeAs('user');
include$Eleanor->module['path'].'forum.php';
include$Eleanor->module['path'].'core.php';

$Eleanor->Forum=new ForumCore;
$Forum=$Eleanor->Forum;
$config=$Forum->config=$Forum->Forum->config;
$Forum->vars=Eleanor::LoadOptions($config['opts'],true);
$Forum->LoadUser();
$Forum->Language=new Language(true);
$Forum->Language->loadfrom=dirname(__DIR__);

$show=isset($_GET['show']) ? (string)$_GET['show'] : 'posts';
$f=isset($_GET['f']) ? (int)$_GET['f'] : false;
$t=isset($_GET['t']) ? (int)$_GET['t'] : false;
$p=isset($_GET['p']) ? (int)$_GET['p'] : false;

if(isset($_GET['l']) and isset(Eleanor::$langs[ (string)$_GET['l'] ]))
	$Forum->language=$language=(string)$_GET['l'];
else
	$language='';

$lang=$Forum->Language['rss'];
$gp=$Forum->GuestSign('p');
$gt=$Forum->GuestSign('t');

$items=$forums=array();
$title=$lang['title'];
$descr=$lang['descr'];
$lastmod=false;
$link=Eleanor::$services['rss']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name']));

if($t)
{
	$R=Eleanor::$Db->Query('SELECT `id`,`f`,`status`,`language`,`author_id`,`title`,`description`,`posts` FROM `'.$config['ft'].'` WHERE `id`='.$t.' AND `language`IN(\'\',\''.$Forum->language.'\') LIMIT 1');
	if(!$topic=$R->fetch_assoc() or !$Forum->CheckTopicAccess($topic) or ($topic['status']==0 or $topic['status']==-1 and !($Forum->user and $topic['author_id']==$Forum->user['id'] or in_array($topic['id'],$gt))))
		$t=$topic=$show=false;
	else
	{
		$show='posts';
		$title=$topic['title'];
		$descr=$topic['description'];
	}
}
elseif($f)
{
	if($forum=$Forum->Forums->GetForum($f) and $Forum->CheckForumAccess($f))
	{
		$R=Eleanor::$Db->Query('SELECT `description` FROM `'.$config['fl'].'` WHERE `id`='.$forum['id'].' AND `language`=\''.$forum['language'].'\' LIMIT 1');
		if($a=$R->fetch_assoc())
		{
			$title=$forum['title'];
			$descr=$a['description'];
		}

		$forums[]=$forum['id'];
		$parents=$forum['parents'].$forum['id'].',';
		#Добавляем все подфорумы
		foreach($Forum->Forums->dump as $k=>$v)
			if(strpos($v['parents'],$parents)!==false and $Forum->CheckTopicAccess(array('f'=>$k,'author_id'=>0,'id'=>0)))
				$forums[]=$k;
	}
	else
		$show=false;
}
else
{
	$all=true;
	foreach($Forum->Forums->dump as $k=>$v)
		if($Forum->CheckTopicAccess(array('f'=>$k,'author_id'=>0,'id'=>0)))
			$forums[]=$k;
		else
			$all=false;
	if($all)
		$forums=array();
	elseif(!$forums)
		$show=false;
}

if($show=='posts')#Отображение постов
{
	if($p)
	{
		$where='`id`='.$p.' AND `status`=1 LIMIT 1';
		$t=false;
	}
	elseif($t)
	{
		$where='`t`='.$t.' AND `status`='.($topic['status']==1 ? 1 : -2).' ORDER BY `sortdate` DESC LIMIT 50';
		$topic['posts']++;
	}
	else
	{
		$where='';
		if($forums)
			$where='`f`'.Eleanor::$Db->In($forums).' AND ';
		$where.='`language`=\''.$language.'\' AND `status`=1 ORDER BY `created` DESC LIMIT 50';
	}

	$R=Eleanor::$Db->Query('SELECT `id`,`created`,`text` FROM `'.$config['fp'].'` WHERE '.$where);
	while($a=$R->fetch_assoc())
	{
		$ts=strtotime($a['created']);
		if(!$lastmod)
			$lastmod=$ts;
		$descr=preg_replace('#\['. $config['abb'].'=[^\]]*\]#','',$a['text']);
		$descr=strip_tags(OwnBB::Parse($descr),'<br><p><b><i><strong><span><small>');
		$items[ 'p'.$a['id'] ]=array(
			'title'=>'#'.($t ? $topic['posts']-- : $a['id']),
			'descr'=>$descr,
			'link'=>$Forum->Links->Action('post',$a['id']),
			'ts'=>$ts,
		);
	}
}
elseif($show)#Отображение тем
{
	$where='`language`=\''.$Forum->language.'\' AND `status`=1';
	if($forums)
		$where.=' AND `f`'.Eleanor::$Db->In($forums);

	$R=Eleanor::$Db->Query('SELECT `id`,`created`,`title`,`description` FROM `'.$config['ft'].'` WHERE '.$where.' ORDER BY `created` DESC LIMIT 50');
	while($a=$R->fetch_assoc())
	{
		$ts=strtotime($a['created']);
		if(!$lastmod)
			$lastmod=$ts;
		$items[ 't'.$a['id'] ]=array(
			'title'=>$a['title'],
			'descr'=>$a['description'],
			'link'=>$Forum->Links->Action('topic',$a['id']),
			'ts'=>$ts,

		);
	}
}
else
	return ExitPage(404);

if(Eleanor::$caching and $lastmod)
{
	Eleanor::$last_mod=$lastmod;
	$etag=Eleanor::$etag;
	Eleanor::$etag=md5($config['n'].join(',',array_keys($items)));
	if(Eleanor::$modified and Eleanor::$last_mod and Eleanor::$last_mod<=Eleanor::$modified and $etag and $etag==Eleanor::$etag)
		return Start();
	Eleanor::$modified=false;
}

Start(array(
	'title'=>$title,
	'description'=>$descr,
	'lastBuildDate'=>$lastmod,
	'language'=>substr($Forum->language,0,2),
));

foreach($items as $k=>$v)
	echo Rss(array(
		'title'=>$v['title'],#Заголовок сообщения
		'link'=>$v['link'],#URL сообщения
		'description'=>$v['descr'],#Краткий обзор сообщения
		'guid'=>$k,#Строка, уникальным образом идентифицирующая сообщение.
		'pubDate'=>$v['ts'],#Дата публикации
	));