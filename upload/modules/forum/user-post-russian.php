<?php
return array(
	'pet'=>'Ошибка создания темы',
	'FLOOD_WAIT'=>function($limit,$wait)
	{
		return'Писать посты можно раз в '.$limit.Russian::Plural($limit,array(' секунд',' секунды',' секунд'))
			.'. Подождите еще '.$wait.Russian::Plural($wait,array(' секунд',' секунды',' секунд')).'.';
	},
	'creating-topic'=>'Создание темы',

	'pep'=>'Ошибка создания поста',
	'editing-post'=>'Редактирование поста',
	'creating-post'=>'Создание поста',
	'TOO_SHORT'=>function($minlen,$yourinp)
	{
		return'Для публикации поста в нем должно быть как минимум '
			.$minlen.Russian::Plural($minlen,array(' символ',' символа',' символов'))
			.'. Вы ввели '.$yourinp.Russian::Plural($yourinp,array(' символ.',' символа.',' символов.'));
	},
);