<?php
return array(
	'pet'=>'Error of creating topic',
	'FLOOD_WAIT'=>function($limit,$wait)
	{
		return'You can write only 1 post every '.$limit.' second'.($limit>1 ? 's' : '')
			.'. Wait '.$wait.' second'.($wait>1 ? 's' : '').'.';
	},
	'creating-topic'=>'Creating topic',

	'pep'=>'Error of creating post',
	'editing-post'=>'Editing post',
	'creating-post'=>'Creating post',
	'TOO_SHORT'=>function($minlen,$yourinp)
	{
		return'For post publication in it must have at least '
			.$minlen.' symbol'.($minlen==1 ? '' : 's')
			.'. You have entered '.$yourinp.' symbol'.($yourinp==1 ? '.' : 's.');
	},
);