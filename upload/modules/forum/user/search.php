<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
Eleanor::$Template->queue[]='ForumSearch';
$lang=Eleanor::$Language->Load($Eleanor->module['path'].'user-search-*.php',false);

$title[]=$lang['fsearch'];
$Eleanor->Forum->LoadUser();
SetData();
$s=Eleanor::$Template->SearchMain();
Start();
echo$s;