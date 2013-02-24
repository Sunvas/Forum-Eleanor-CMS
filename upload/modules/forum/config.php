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
return array(
	'n'=>'forum',#�������� ������
	'f'=>P.'forums',#������� �������
	'fl'=>P.'forums_l',#������� ������ �������
	'fg'=>P.'forum_groups',#������� �����
	'fu'=>P.'forum_users',#������� ������������� ������
	'fm'=>P.'forum_moders',#������� �����������
	'fa'=>P.'forum_files',#������� �������
	'ft'=>P.'forum_topics',#������� ���
	'ts'=>P.'forum_topics_subscribers',#������� �������� �� ����
	'fp'=>P.'forum_posts',#������� ������
	'fr'=>P.'forum_reputation',#������� ��������� ������
	're'=>P.'forum_reads',#������� ������
	'lp'=>P.'forum_lastpost',#������� ��� ��������� ���������, � ������, ����� ������������ ��������� ������ ����� ����
	'fs'=>P.'forum_subscribers',#������� �������� �� ������
	'pr'=>P.'forum_prefixes',#������� ��������� ���
	'pl'=>P.'forum_prefixes_l',#������� ������ ��������� ���
	'ta'=>P.'forum_tasks',#������� ����� ������
	'abb'=>'forumattach',#BB ��� ��������� ������ ��� ������� �������
	'readslimit'=>10000,#����� �������� ���������� ��� ��� `forums_reads`
	'admintpl'=>'AdminForum',#����� ������������������ ����������
	'usertpl'=>'Forums',#����� ����������������� ����������
	'opts'=>'m_forum',#�������� ������ �����
	'api'=>'ApiForum',#�������� ������
	'logos'=>'images/categories/',#������� � ���������� �������
	'psign'=>__file__,#Posts Signature - ������� ��� ������
	'tsign'=>__file__.P,#Topic Signature - ������� ��� ���
);