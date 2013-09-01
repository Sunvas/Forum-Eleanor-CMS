<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
$no=false;
switch($ev)
{
	case'new-post':#Новый пост
	case'lnp':
		#ToDo!
	break;
	default:
		$no=true;
}
if($no)
{
	$no=false;
	switch($ev)
	{
		case'edit':#Правка поста
			#ToDo!
		break;
		case'save':#Сохранение поста
			#ToDo!
		break;
		case'delete':#Удаление поста
			#ToDo!
		break;
		default:
			$no=true;
	}
}
