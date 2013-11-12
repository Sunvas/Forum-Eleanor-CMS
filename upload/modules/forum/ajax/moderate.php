<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
if($Eleanor->Forum->user)
{
	switch($event)
	{
		case'pin-topic':#Закрепление темы из темы
			#ToDo!
		break;
		case'pin-post':#Закрепление поста из темы
			#ToDo!
		break;
		default:
			Error();
	}
}
else
	Error();