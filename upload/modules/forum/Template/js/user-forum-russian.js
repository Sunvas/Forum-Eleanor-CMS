﻿/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
CORE.Lang({
	nnp:"Новых постов нет",
	tread:"Тема прочтена",
	pws:function(n){
		return n>0 ? "С "+n+CORE.Russian.Plural(n,[" отмеченным:"," отмеченными:"," отмеченными:"]) : "";
	},
	tws:function(n){
		return n>0 ? "С "+n+CORE.Russian.Plural(n,[" отмеченной:"," отмеченными:"," отмеченными:"]) : "";
	},
	delete_topics_confirm:"Вы действительно хотите удалить эти темы?",
	delete_posts_confirm:"Вы действительно хотите удалить эти посты?",

	subscription_cancelled:"Подписка отменена.",
	//Подписка на форум
	subscription_fi:"Вы подписаны на форум с немедленным уведомлением.",
	subscription_fd:"Вы подписаны на форум с ежедневным уведомлением.",
	subscription_fw:"Вы подписаны на форум с еженедельным уведомлением.",
	subscription_fm:"Вы подписаны на форум с ежемесячным уведомлением.",
	//[E] Подписка на форум

	//Подписка на тему
	subscription_ti:"Вы подписаны на тему с немедленным уведомлением.",
	subscription_td:"Вы подписаны на тему с ежедневным уведомлением.",
	subscription_tw:"Вы подписаны на тему с еженедельным уведомлением.",
	subscription_tm:"Вы подписаны на тему с ежемесячным уведомлением."
	//[E] Подписка на тему
},"forum_");