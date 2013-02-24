<?php
/*
	Copyright © Eleanor CMS
	URL: http://eleanor-cms.ru, http://eleanor-cms.com
	E-mail: support@eleanor-cms.ru
	Developing: Alexander Sunvas*
	Interface: Rumin Sergey
	=====
	*Pseudonym
*/
return array(
	'n'=>'forum',#Название модуля
	'f'=>P.'forums',#Таблица форумов
	'fl'=>P.'forums_l',#Таблица языков форумов
	'fg'=>P.'forum_groups',#Таблица групп
	'fu'=>P.'forum_users',#Таблица пользователей форума
	'fm'=>P.'forum_moders',#Таблица модераторов
	'fa'=>P.'forum_files',#Таблица аттачей
	'ft'=>P.'forum_topics',#Таблица тем
	'ts'=>P.'forum_topics_subscribers',#Таблица подписок на темы
	'fp'=>P.'forum_posts',#Таблица постов
	'fr'=>P.'forum_reputation',#Таблица репутации форума
	're'=>P.'forum_reads',#Таблица чтений
	'lp'=>P.'forum_lastpost',#Таблица для последних сообщений, в случае, когда пользователю запрещено видеть чужие темы
	'fs'=>P.'forum_subscribers',#Таблица подписок на форумы
	'pr'=>P.'forum_prefixes',#Таблица префиксов тем
	'pl'=>P.'forum_prefixes_l',#Таблица языков префиксов тем
	'ta'=>P.'forum_tasks',#Таблица задач форума
	'abb'=>'forumattach',#BB код форумного аттача для скрытых аттачей
	'readslimit'=>10000,#Лимит маркеров прочтенных тем для `forums_reads`
	'admintpl'=>'AdminForum',#Класс администраторского оформления
	'usertpl'=>'Forums',#Класс пользовательского оформления
	'opts'=>'m_forum',#Название группы опций
	'api'=>'ApiForum',#Название класса
	'logos'=>'images/categories/',#Каталог с логотипами форумов
	'psign'=>__file__,#Posts Signature - подпись для постов
	'tsign'=>__file__.P,#Topic Signature - подпись для тем
);