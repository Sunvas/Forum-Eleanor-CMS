<?php
return array(
	'pet'=>'Помилка створення теми',
	'FLOOD_WAIT'=>function($limit,$wait)
	{
		return'Писати пости можна раз на '.$limit.Ukrainian::Plural($limit,array(' секунду',' секунди',' секунд')).'. Подочекайте ще '.$wait.Russian::Plural($wait,array(' секунду',' секунди',' секунд')).'.';
	},
);