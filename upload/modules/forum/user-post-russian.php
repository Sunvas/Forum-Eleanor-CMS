<?php
return array(
	'pet'=>'Ошибка создания темы',
	'FLOOD_WAIT'=>function($limit,$wait)
	{
		return'Писать посты можно раз в '.$limit.Russian::Plural($limit,array(' секунд',' секунды',' секунд')).'. Подождите еще '.$wait.Russian::Plural($wait,array(' секунд',' секунды',' секунд')).'.';
	},
);