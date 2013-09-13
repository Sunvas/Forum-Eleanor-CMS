<?php
defined('CMS')||die;

if(!isset(Eleanor::$Language['forum-global']))
	Eleanor::$Language->queue['forum-global']=__DIR__.'/langs/forum-user-global-*.php';

echo'<blockquote class="extend"><div class="top">',
	Eleanor::$Language['forum-global']['quote'],
	$name || $date ? ' ('.$name.' @ '.$date.')' : '',
	$_a ? ' <a href="'.$_a.'" target="_blank"><img src="images/forum/findpost.gif" /></a>' : '',
	'</div><div class="text"><!-- NOBR -->',
	$text,
	'</div></blockquote><!-- NOBR -->';