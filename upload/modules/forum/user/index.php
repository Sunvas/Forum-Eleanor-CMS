<?php
/*
	Copyright � Eleanor CMS
	URL: http://eleanor-cms.ru, http://eleanor-cms.com
	E-mail: support@eleanor-cms.ru
	Developing: Alexander Sunvas*
	Interface: Rumin Sergey
	=====
	*Pseudonym
*/
if(!defined('CMS'))die;

global$title;
$title='����� � ����������';
$s=Eleanor::$Template->Message('����� ��������� � ����������. �� ��������� ����� ��������� ��������� <a href="http://eleanor-cms.ru/git/">GIT</a>.','info');
Start();
echo$s;