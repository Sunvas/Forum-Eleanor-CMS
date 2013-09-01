<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
class TplForumSearch
{
	public static function SearchMain()
	{
		return Eleanor::$Template->ForumMenu(array(
			array(false,'Поиск')
		),null)->Message('Поиск по форуму в разработке...','info');
	}
}