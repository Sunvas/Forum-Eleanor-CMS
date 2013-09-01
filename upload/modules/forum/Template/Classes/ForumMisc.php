<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
class TplForumMisc
{
	public static
		$lang;

	/**
	 * Страница настроек пользователя на форуме
	 * @param array $values Массив значений полей, ключи:
	 *   string statustext Надпись под аватаром
	 *   bool hidden Входить скрытым
	 * @param bool $saved Флаг сохраненности настроек
	 * @param array $errors Массив ошибок
	 */
	public static function ForumOptions($values,$saved,$errors)
	{
		$C=Eleanor::$Template->ForumMenu(array(
			array(false,static::$lang['options'])
		),null);
		if($saved)
			$C->Message(static::$lang['savedoptions'],'info',20);
		if($errors)
		{
			foreach($errors as $k=>&$v)
				if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
					$v=static::$lang[$v];

			$C->Message($errors,'errors');
		}

		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin()
			->head(static::$lang['options'])
			->item(static::$lang['hideme'],Eleanor::Check('hidden',$values['hidden']))
			->item(static::$lang['statustext'],Eleanor::Input('statustext',$values['statustext'],array('maxlength'=>15)))
			->button(Eleanor::Button())
			->end()
			->endform();
		return$C.$Lst;
	}

	/**
	 * Страница "лидеров" форума
	 */
	public static function ForumTop()
	{
		return Eleanor::$Template->ForumMenu(array(
				array(false,static::$lang['leaders'])
			),null)->Message('Страница лидеров форума в разработке','info');
	}

	/**
	 * Страница пользователей форума
	 */
	public static function ForumUsers()
	{
		return Eleanor::$Template->ForumMenu(array(
			array(false,static::$lang['fusers'])
		),null)->Message('Страница пользователей форума в разработке','info');
	}

	/**
	 * Страница "кто онлайн" на форуме
	 */
	public static function ForumOnline()
	{
		return Eleanor::$Template->ForumMenu(array(
			array(false,static::$lang['nowonforum'])
		),null)->Message('Страница "кто онлайн" форума в разработке','info');
	}

	/**
	 * Страница модераторов форума
	 */
	public static function ForumModerators()
	{
		return Eleanor::$Template->ForumMenu(array(
			array(false,static::$lang['fmoderators'])
		),null)->Message('Страница администрации форума в разработке','info');
	}

	/**
	 * Страница со статистикой форума
	 */
	public static function ForumStats()
	{
		return Eleanor::$Template->ForumMenu(array(
			array(false,static::$lang['todayact'])
		),null)->Message('Страница с информацией об активных пользователях сегодня в разработке','info');
	}

	/**
	 * Страница с репутацией пользователя
	 */
	public static function ForumUserRepuation()
	{
		return Eleanor::$Template->ForumMenu(array(
			array(false,'Репутация пользователя')
		),null)->Message('Страница с репутацией пользователя в разработке','info');
	}

	/**
	 * Страница с репутацией, которую (репутацию) пользователь отдал
	 */
	public static function ForumGivenRepuation()
	{
		return Eleanor::$Template->ForumMenu(array(
			array(false,'Отданая репутация')
		),null)->Message('Страница с отданной пользователем репутации в разработке','info');
	}
}
TplForumMisc::$lang=Eleanor::$Language->Load(dirname(__DIR__).'/langs/forum-user-misc-*.php',false);