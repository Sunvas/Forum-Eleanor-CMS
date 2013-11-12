<?php
return array(
	'merged'=>function($days,$hours,$mins,$secs)
	{
		$res=array();
		if($days>0)
			$res[]=$days.Russian::Plural($days,array(' день',' дня',' дней'));
		if($hours>0)
			$res[]=$hours.Russian::Plural($hours,array(' час',' часа',' часов'));
		if($mins>0)
			$res[]=$mins.Russian::Plural($mins,array(' минуту',' минуты',' минут'));
		if($secs>0)
			$res[]=$secs.Russian::Plural($secs,array(' секунду',' секунды',' секунд'));

		return'<small class="fb-merged">Добавлено через '.join(' ',$res).'</small>';
	}
);