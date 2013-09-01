<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;

class ForumBBQoute extends OwnBbCode
{
	/**
	 * Обработка цитаты перед показом её на странице
	 * @param string $t Тег, который обрабатывается
	 * @param string $p Параметры тега
	 * @param string $c Содержимое тега [tag...] Вот это [/tag]
	 * @param bool $cu Флаг возможности использования тега
	 */
	public static function PreDisplay($t,$p,$c,$cu)
	{
		$p=$p ? Strings::ParseParams($p) : array();
		if(isset($p['noparse']))
		{
			unset($p['noparse']);
			return parent::PreSave($t,$p,$c,true);
		}
		if(!$cu)
			return self::RestrictDisplay($t,$p,$c);
		return Eleanor::$Template->ForumQuote(array(
			'date'=>isset($p['date']) ? Eleanor::$Language->Date($p['date'],'fdt') : false,
			'name'=>isset($p['name']) ? $p['name'] : false,
			'_a'=>isset($p['p']) ? Eleanor::getInstance()->Forum->Links->Action('find-post',(int)$p['p']) : false,
			'text'=>$c,
		));
	}

	/**
	 * Обработка цитаты перед её сохранением
	 * @param string $t Тег, который обрабатывается
	 * @param string $p Параметры тега
	 * @param string $c Содержимое тега [tag...] Вот это [/tag]
	 * @param bool $cu Флаг возможности использования тега
	 */
	public static function PreSave($t,$p,$c,$cu)
	{
		#Зачатки функционала "Вас процитировали"
		OwnBB::$opts['you_quoted']='';#Здесь можно сделать отправку письма "Вас процитировали"

		$c=preg_replace("#^(\r?\n?<br />\r?\n?)+#i",'<br />',$c);
		$c=preg_replace("#(\r?\n?<br />\r?\n?)+$#i",'<br />',$c);
		return parent::PreSave($t,$p,$c,$cu);
	}
}