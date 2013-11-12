<?php
/**
 * Шаблон "Шапки" форума. Здесь подключаются скрипты форума, rss ссылки, cron, выводится "шапочное" меню и "хлебные крошки".
 * @var array Навигация со значением в виде массива array(ссылка,название)
 * @var array RSS ссылки со значением в виде массива array(ссылка,название)
 */
defined('CMS')||die;

$Forum = $GLOBALS['Eleanor']->Forum;
$module=$GLOBALS['Eleanor']->module;
$links=$module['links'];
Eleanor::$Language->queue['forum-global']=__DIR__.'/langs/forum-user-global-*.php';
$l=Eleanor::$Language['forum-global'];

#JavaScripts
array_push($GLOBALS['jscripts'],$Forum->language=='english' ? false : 'js/'.$Forum->language.'.js','modules/forum/Template/js/user-forum-'. $Forum->language.'.js','modules/forum/Template/js/user-forum.js');

#RSS
$Lst=Eleanor::LoadListTemplate('headfoot');
if(isset($v_1))
	foreach($v_1 as $v)
		$Lst->link(array(
			'rel'=>'alternate',
			'type'=>'application/rss+xml',
			'href'=>$v[0],
			'title'=>$v[1],
		));
$Lst->link(array(
	'rel'=>'alternate',
	'type'=>'application/rss+xml',
	'href'=>$links['rss_topics'],
	'title'=>$l['rss_topics'],
))->link(array(
	'rel'=>'alternate',
	'type'=>'application/rss+xml',
	'href'=>$links['rss_posts'],
	'title'=>$l['rss_posts'],
));
$GLOBALS['head']['rss']=$Lst;

#Cron
$cron=$module['cron'] ? '<img src="'.$module['cron'].'" style="width:1px;height1px;" />' : '';

#Вывод Верхнего меню.
echo Eleanor::$Template->Menu(array(
		'menu'=>array(
			$links['search'] ? array($links['search'],$l['search']) : false,
			array($links['users'],$l['users']),
			array($links['online'],$l['online']),
			array($links['top'],$l['top']),
			array($links['moderators'],$l['moderators']),
			array($links['stats'],$l['stats']),
			$links['settings'] ? array($links['settings'],$l['settings']) : false,
		),
		'title'=>$module['title'].$cron,#(is_array($GLOBALS['title']) ? reset($GLOBALS['title']) : $GLOBALS['title'])
	)),
	Eleanor::JsVars(array(
		'name'=>$module['name'],
		'n'=> $Forum->config['n'],
		'cron'=>Eleanor::$services['cron']['file'],
		'language'=> $Forum->language,
	),true,false,'FORUM.');

#Вывод "хлебных крошек"
if(isset($v_0))
{
	echo'<nav class="forums"><a href="',$links['main'],'">',$module['title'],'</a>';
	foreach($v_0 as $v)
		echo' &raquo; ',
			$v[0] ? '<a href="'.$v[0].'">'.$v[1].'</a>' : $v[1];
	echo'</nav>';
}