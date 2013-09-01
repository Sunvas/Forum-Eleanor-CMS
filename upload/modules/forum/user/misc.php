<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
Eleanor::$Template->queue[]='ForumMisc';
$lang=Eleanor::$Language->Load($Eleanor->module['path'].'user-misc-*.php',false);

switch($do)
{
 	case'top':#Топ пользователей по репутации
		#ToDo!
		$title[]=$lang['leaders'];
		$Eleanor->Forum->LoadUser();
		SetData();
		$s=Eleanor::$Template->ForumTop();
		Start();
		echo$s;
	break;
	case'online':#Просмотр списка кто онлайн
		#ToDo!
		$title[]=$lang['newonforum'];
		$Eleanor->Forum->LoadUser();
		SetData();
		$s=Eleanor::$Template->ForumOnline();
		Start();
		echo$s;
	break;
	case'options':#Опции пользователя на форуме
		$Eleanor->Forum->LoadUser();
		if(!$Eleanor->Forum->user)
			return ExitPage();
		$saved=false;
		$errors=array();
		if($_SERVER['REQUEST_METHOD']=='POST' and Eleanor::$our_query)
		{
			$values=array(
				'hidden'=>isset($_POST['hidden']),
				'statustext'=>isset($_POST['statustext']) ? (string)Eleanor::$POST['statustext'] : '',
			);
			$Eleanor->Forum->Service->UpdateUser($values,$Eleanor->Forum->user['id']);
			$Eleanor->Forum->user['hidden']=$values['hidden'];
			$saved=true;
		}
		Eleanor::$sessextra=$Eleanor->Forum->config['n'].'-option'.($Eleanor->Forum->user['hidden'] ? '-h' : '-');
		$values=array();
		if($errors)
		{
			if($errors===true)
				$errors=array();
			$values['hidden']=isset($_POST['hidden']);
			$values['statustext']=isset($_POST['statustext']) ? (string)$_POST['statustext'] : '';
		}
		else
		{
			$values['hidden']=$Eleanor->Forum->user['hidden'];
			$R=Eleanor::$Db->Query('SELECT `statustext` FROM `'.$Eleanor->Forum->config['fu'].'` WHERE `id`='.$Eleanor->Forum->user['id'].' LIMIT 1');
			$values+=$R->fetch_assoc();
		}
		SetData();
		$title[]=$lang['options'];
		$c=Eleanor::$Template->ForumOptions($values,$saved,$errors);
		Start();
		echo$c;
	break;
	case'users':#Список пользователей
		#ToDo!
		$title[]=$lang['fusers'];
		$Eleanor->Forum->LoadUser();
		SetData();
		$s=Eleanor::$Template->ForumUsers();
		Start();
		echo$s;
	break;
	case'reputation':#Отображение репутации пользователя
		#ToDo!
		$title[]='Репутация пользователя';
		$Eleanor->Forum->LoadUser();
		SetData();
		$s=Eleanor::$Template->ForumUserRepuation();
		Start();
		echo$s;
	break;
	case'given':#Отображение отданой репутации пользователем другим
		#ToDo!
		$title[]='Отданая репутация';
		$Eleanor->Forum->LoadUser();
		SetData();
		$s=Eleanor::$Template->ForumGivenRepuation();
		Start();
		echo$s;
	break;
	case'stats':#Отображение статистики за сегодня
		#ToDo!
		$title[]=$lang['todayact'];
		$Eleanor->Forum->LoadUser();
		SetData();
		$s=Eleanor::$Template->ForumStats();
		Start();
		echo$s;
	break;
	case'moderators':#Просмотр всех модераторов
		#ToDo!
		$title[]=$lang['fmoderators'];
		$Eleanor->Forum->LoadUser();
		SetData();
		$s=Eleanor::$Template->ForumModerators();
		Start();
		echo$s;
	break;
	default:
		ExitPage();
}