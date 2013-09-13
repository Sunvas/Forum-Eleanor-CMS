<?php
return array(
	'pet'=>'Error of creating topic',
	'FLOOD_WAIT'=>function($limit,$wait)
	{
		return'You can write only 1 post every '.$limit.' second'.($limit>1 ? 's' : '').'. Wait '.$wait.' second'.($wait>1 ? 's' : '').'.';
	},
);