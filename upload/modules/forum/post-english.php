<?php
return array(
	'merged'=>function($days,$hours,$mins,$secs)
	{
		$res=array();
		if($days>0)
			$res[]=$days.' day'.($days==1 ? '' : 's');
		if($hours>0)
			$res[]=$hours.' hour'.($hours==1 ? '' : 's');
		if($mins>0)
			$res[]=$mins.' minute'.($mins==1 ? '' : 's');
		if($secs>0)
			$res[]=$secs.' second'.($secs==1 ? '' : 's');

		return'<small class="fb-merged">Added after '.join(' ',$res).'</small>';
	}
);