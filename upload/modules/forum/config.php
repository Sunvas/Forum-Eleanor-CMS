<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
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
	'abb'=>'file',#BB код форумного аттача для скрытых аттачей
	'readslimit'=>10000,#Лимит маркеров прочтенных тем для `forums_reads`

	'admintpl'=>'AdminForum',#Класс администраторского оформления
	'maintpl'=>'ForumMain',#Класс основного пользовательского оформления
	'substpl'=>'ForumSubscribe',#Класс оформления подписок
	'posttpl'=>'ForumPost',#Класс, который отвечает за формы постинга
	'topictpl'=>'ForumTopic',#Класс, которые отвечает за просмотр темы и поста

	'opts'=>'m_forum',#Название группы опций
	'api'=>'ApiForum',#Название класса
	'logos'=>'images/categories/',#Каталог с логотипами форумов
	'glogos'=>'images/forum/statuses/',#Каталог с логотипами групп

	'attachroot'=>Eleanor::$root.Eleanor::$uploads.'/forum/',#Путь к каталогу с аттачами на сервере
	'attachpath'=>Eleanor::$site_path.Eleanor::$uploads.'/forum/',#Путь к каталогу с аттачами на сайте
	'download'=>Eleanor::$services['download']['file'].'?'.Url::Query(array('module'=>$GLOBALS['Eleanor']->module['name'])).'&amp;id=',#Префикс ссылки на скачивание

	'psign'=>__file__,#Posts Signature - подпись для постов
	'tsign'=>__file__.P,#Topic Signature - подпись для тем
);