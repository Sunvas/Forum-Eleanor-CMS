﻿/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
CORE.Lang({
	nnp:"Нових постів немає",
	tread:"Тему прочитаноа",
	pws:function(n){
		return n>0 ? "З "+n+CORE.Ukrainian.Plural(n,[" відміченим:"," відміченими:"," відміченими:"]) : "";
	},
	tws:function(n){
		return n>0 ? "З "+n+CORE.Ukrainian.Plural(n,[" відміченою:"," відміченими:"," відміченими:"]) : "";
	},
	delete_topics_confirm:"Ви дійсно хочете видалити ці теми?",
	delete_posts_confirm:"Ви дійсно хочете видалити ці пости?",

	copy_link:"Скопіюйте посилання:",
	qqe:"Користувач {0} не писав цього!",
	waitpost:"Зачекайте, йде відправка повідомлення...",
	merged:"Ваш пост успішно склеєний з попереднім",
	lnp:"Завантаження нових постів...",
	posts_loaded:"Пости успішно завантажені",
	no_new_posts:"Нових постів немає",

	subscription_cancelled:"Підписку скасовано.",
	//Підписка на форум
	subscription_fi:"Ви підписані на форум з негайним повідомленням.",
	subscription_fd:"Ви підписані на форум з щоденним повідомленням.",
	subscription_fw:"Ви підписані на форум з щотижневим повідомленням.",
	subscription_fm:"Ви підписані на форум з щомісячним повідомленням.",
	//[E] Підписка на форум

	//Підписка на тему
	subscription_ti:"Ви підписані на тему з негайним повідомленням.",
	subscription_td:"Ви підписані на тему з щоденним повідомленням.",
	subscription_tw:"Ви підписані на тему з щотижневим повідомленням.",
	subscription_tm:"Ви підписані на тему з щомісячним повідомленням.",
	//[E] Підписка на тему

	//Помилки
	WRONG_CAPTCHA:"Захисний код введений з помилкою",
	ENTER_NAME:"Будь ласка, представтесь"
	//[E] Помилки
},"forum_");