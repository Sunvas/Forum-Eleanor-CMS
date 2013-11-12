<?php
return array(
	'merged'=>function($days,$hours,$mins,$secs)
	{
		$res=array();
		if($days>0)
			$res[]=$days.Ukrainian::Plural($days,array(' день',' дня',' днів'));
		if($hours>0)
			$res[]=$hours.Ukrainian::Plural($hours,array(' годину',' години',' годин'));
		if($mins>0)
			$res[]=$mins.Ukrainian::Plural($mins,array(' хвилину',' хвилини',' хвилин'));
		if($secs>0)
			$res[]=$secs.Ukrainian::Plural($secs,array(' секунду',' секунды',' секунд'));

		return'<small class="fb-merged">Додано через '.join(' ',$res).'</small>';
	}
);