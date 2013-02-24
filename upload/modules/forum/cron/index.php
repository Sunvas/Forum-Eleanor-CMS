<?php
/*
	Copyright © Eleanor CMS
	URL: http://eleanor-cms.ru, http://eleanor-cms.su, http://eleanor-cms.com, http://eleanor-cms.net, http://eleanor.su
	E-mail: support@eleanor-cms.ru, support@eleanor.su
	Developing: Alexander Sunvas*
	Interface: Rumin Sergey
	=====
	*Pseudonym
*/
if(!defined('CMS'))die;
global$Eleanor;
$Eleanor->mconfig=include $Eleanor->module['path'].'config.php';
if(Eleanor::$Cache->Get($Eleanor->mconfig['n'].'-runned')===false)
{	Eleanor::$Cache->Put($Eleanor->mconfig['n'].'-runned',true,200);
	Eleanor::LoadOptions(array('site',$Eleanor->mconfig['opts']));
	include$Eleanor->module['path'].'base.php';
	include$Eleanor->module['path'].'core.php';
	$Eleanor->Forum=new ForumCore($Eleanor->mconfig);

	#Вместо BeAs();
	$Eleanor->Url->furl=Eleanor::$vars['furl'];
	$Eleanor->Url->delimiter=Eleanor::$vars['url_static_delimiter'];
	$Eleanor->Url->defis=Eleanor::$vars['url_static_defis'];
	$Eleanor->Url->ending=Eleanor::$vars['url_static_ending'];
	$Eleanor->Url->rep_space=Eleanor::$vars['url_rep_space'];
	Eleanor::$Login=Eleanor::LoadLogin(Eleanor::$services['user']['login']);
	$Eleanor->modules=$Eleanor->Modules->GetCache('user');

	$r=$Eleanor->Forum->Subscriptions->SendForums() && $Eleanor->Forum->Subscriptions->SendTopics();
	if($r)
	{		$near=0;#ToDo? Ближайший запуск когда? Завтра?
		$totomor=strtotime('+1 DAY',mktime(0,0,0));
		Eleanor::$Cache->Put($Eleanor->mconfig['n'].'_nextrun',$near>0 ? min($near,$totomor) : $totomor);
	}
	Eleanor::$Cache->Delete($Eleanor->mconfig['n'].'-runned');
}
Start();