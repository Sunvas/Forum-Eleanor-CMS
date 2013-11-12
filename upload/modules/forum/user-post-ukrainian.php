<?php
return array(
	'pet'=>'Помилка створення теми',
	'FLOOD_WAIT'=>function($limit,$wait)
	{
		return'Писати пости можна раз на '.$limit.Ukrainian::Plural($limit,array(' секунду',' секунди',' секунд'))
			.'. Подочекайте ще '.$wait.Ukrainian::Plural($wait,array(' секунду',' секунди',' секунд')).'.';
	},
	'creating-topic'=>'Створення теми',

	'pep'=>'Помилка створення посту',
	'editing-post'=>'Редагування поста',
	'creating-post'=>'Створення поста',
	'TOO_SHORT'=>function($minlen,$yourinp)
	{
		return'Для публікації поста в ньому має бути як мінімум '
		.$minlen.Ukrainian::Plural($minlen,array(' символ',' символи',' символів'))
		.'. Ви ввели '.$yourinp.Ukrainian::Plural($yourinp,array(' символ.',' символи.',' символів.'));
	},
);