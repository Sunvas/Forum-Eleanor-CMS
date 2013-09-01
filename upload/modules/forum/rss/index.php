<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
/*
	RSS должно включать в себя:
		последние темы (из форумов, включая категории)
		последние посты (из темы, форумов, включая категории)

	.. RSS постов всего форума
	..&show=topics RSS топиков всего форума
*/
BeAs('user');
Start();

#ToDo!
Rss(array(
	'title'=>'В разработке',#Заголовок сообщения
	'link'=>'http://eleanor-cms.ru',#URL сообщения
	'description'=>'RSS форума пока еще в разработке',#Краткий обзор сообщения
	'guid'=>1,#Строка, уникальным образом идентифицирующая сообщение.
	//'category'=>$cats,#Включает сообщение в одну или более категорий.
	//'pubDate'=>(int)$v['date'],
));