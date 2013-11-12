/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

//API форума - постоянное
var FORUM={
	name:"forum",//Значение ключа module в ссылке на форум
	n:"forum",//Имя модуля, имя секции
	cron:"cron.php",//Имя cron файла системы
	language:CORE.language,//Язык форума

	//Для загрузчика новых постов
	autoupdate:10000,//Автообновление темы
	t:0,//ID темы
	ld:0,//Last Date - Дата последнего поста на странице
	ln:0,//Last Number - Номер последнего поста на странице
	lp:false,//Last Page - флаг нахождения на последней странице темы
	filter:{},//Фильтр постов для дозагрузки постов с нужными параметрами

	/**
	 * Запуск крона
	 * @param string addr Ссылка на задачу
	 */
	Cron:function(addr)
	{
		addr=addr||this.cron+"?module="+this.name;
		addr+="&amp;t="+Math.random();

		var t=$("#"+this.n+"-task");
		if(t.size()==0)
			$("<img src=\""+addr+"\" />").appendTo("body");
		else
			t.prop("src",addr);
	},

	/**
	 * Пометка всего форума прочтённым
	 * @param callback F Метод, который будет вызван после успешной пометки форума прочтённым
	 */
	AllRead:function(F)
	{
		return CORE.Ajax(
			{
				module:this.name,
				language:this.language,
				event:"all-read"
			},
			F
		);
	},

	/**
	 * Пометка прочтённым конкретного форума
	 * @param int f ID форума
	 * @param callback F Метод, который будет вызван после успешной пометки форума прочтённым
	 */
	ForumRead:function(f,F)
	{
		return CORE.Ajax(
			{
				module:this.name,
				language:this.language,
				f:f,
				event:"forum-read"
			},
			F
		);
	},

	/**
	 * Пометка конкретной темы прочтённой
	 * @param int t ID темы
	 * @param callback F Метод, который будет вызван после успешной пометки темы прочтённоё
	 */
	TopicRead:function(t,F)
	{
		return CORE.Ajax(
			{
				module:this.name,
				language:this.language,
				t:t,
				event:"topic-read"
			},
			F
		);
	},

	/**
	 * Подписка на конкретный форум
	 * @param int f ID форума
	 * @param string lang Язык форума
	 * @param string type Тип подписки: m - ежемесячная, w - еженедельная, d - ежедневная, i - немедленная, 0 - отменить подписку
	 * @param callback F Метод, который будет вызван после успешной подписки
	 */
	SubscribeForum:function(f,lang,type,F)
	{
		CORE.Ajax(
			{
				module:this.name,
				language:this.language,
				f:f,
				language:lang,
				type:type,
				event:"subscribe-forum"
			},
			F
		);
	},

	/**
	 * Подписка на конкретную тему
	 * @param int t ID темы
	 * @param string type Тип подписки: m - ежемесячная, w - еженедельная, d - ежедневная, i - немедленная, 0 - отменить подписку
	 * @param callback F Метод, который будет вызван после успешной подписки
	 */
	SubscribeTopic:function(t,type,F)
	{
		CORE.Ajax(
			{
				module:this.name,
				language:this.language,
				t:t,
				type:type,
				event:"subscribe-topic"
			},
			F
		);
	}
};

//Шаблонозависимые скрипты
$(function(){
	//Выделение постов, которые будут процитированы при ответе
	var qp=CORE.GetCookie(FORUM.n+"-qp"),
		qF=function(){
			var id=$(this).data("id").toString();
			if(id && $.inArray(id,qp)!=-1)
				$(this).addClass("active");
		};
	qp=qp ? $.unique(qp.split(",")) : [];
	$(".fb-quote").each(qF);

	var posts=$("#posts"),//Контейнер постов
		//Преобразование последнего fb-merged в id="mergedID"
		Merged=function()
		{
			$("#posts .post:has(.fb-merged)").each(function(){
				var th=$(this),
					id=th.data("id");
				th.find(".fb-merged").prop("id",false).filter(":last").prop("id","merged"+id);
			});
		};
	Merged();
	
	

	$(document)
		.on("click",".fb-thanks",function(e){
			e.preventDefault();
			alert("В разработке");
			return false;
		})

	.on("click",".fb-quote",function(e){
		e.preventDefault();
		
		var id=$(this).data("id").toString(),
			k=$.inArray(id,qp);
		if(k==-1)
		{
			qp.push(id);
			$(this).addClass("active");
		}
		else
		{
			qp.splice(k,1);
			$(this).removeClass("active");
		}
		CORE.SetCookie(FORUM.n+"-qp",qp.join(","));
	})

	//Быстрый поиск поста, если он находится на этой же странице
	.on("click",".fb-find-post",function(){
		var id=$(this).data("id");
		if($("#post"+id).size()>0)
			$(this).prop("href","#post"+id);
	})

	//Окно копирования ссылки на пост
	.on("click",".fb-ltp",function(e){
		e.preventDefault();
		prompt(CORE.Lang("forum_copy_link"),$(this).prop("href"));
	})

	//Вставка имени пользователя (ника) в редактор
	.on("click",".fb-insert-nick",function(e){
		e.preventDefault();
		EDITOR.Embed("nick",{name:$(this).text()});
	});

	//Переходы между постами при просмотре поста
	var postscache={};
	$(document).on("click",".fb-prev-post,.fb-next-post",function(e){
		e.preventDefault();

		var th=$(this),
			F=function(id,url){
				var post=$("#posts .post:first"),
					current=post.data("id");

				if(id in postscache)
				{
					postscache[current]=posts.html();
					posts.html( postscache[id] );
					if(url)
						CORE.HistoryPush(url,F,id);
				}
				else
					CORE.Ajax(
						{
							module:FORUM.name,
							language:FORUM.language,
							event:"show-post",
							id:id
						},
						function(r)
						{
							postscache[current]=posts.html();
							posts.html( r );
							$("#posts .post .fb-quote").each(qF);

							if(url)
								CORE.HistoryPush(url,F,id);
						}
					);
			};

		if(CORE.history===false)
			CORE.HistoryInit(F,$("#posts .post:first").data("id"));
		F(th.data("id"),th.prop("href"));
	})

	//Быстрая цитата
	.on("click",".fb-quick-quote",function(){
		var o=$(this),
			name=o.data("name"),
			text=o.closest(".post").find(".text:first").html(),
			sel,sele,m;

		if(!o.data("id") || !o.data("date") || !name)
			return true;

		if(window.getSelection)
			sel=window.getSelection().toString();
		else if(document.getSelection)
			sel=document.getSelection().toString();
		else if(document.selection)
			sel=document.selection.createRange().text;
		if(!sel)
			return false;

		if(CORE.browser.firefox)
			while(m=text.match(/<img[^>]+>/))
				text=text.replace(m[0],m[0].indexOf("alt=")==-1 ? "" : m[0].match(/alt="([^"]+)"/)[1]);

		text=text.replace(/<[^>]+>/g,"");
		//Это аналог функции html_entity_decode :)
		text=$("<textarea>").html(text).val();
		sele=sel;
 		while(sele.match(/(\r|\n|\s){2,}/))
	 		sele=sele.replace(/(\r|\n|\s)+/g," ");
 		while(text.match(/(\r|\n|\s){2,}/))
	 		text=text.replace(/(\r|\n|\s)+/g," ");

		if(text.indexOf(sele)!=-1)
		{
			sel=sel.replace(/\s+\n/g,"\n");
			EDITOR.Insert("[quote name=\""+name+"\" date=\""+o.data("date")+"\" p="+o.data("id")+"]\r\n"+sel+"\r\n[/quote]\r\n");
		}
		else
			alert(CORE.Lang("forum_qqe",[name]));
		return false;
	})

	//Быстрая правка поста
	.on("click",".fb-quick-edit",function(e){
		e.preventDefault();

		var th=$(this),
			id=th.data("id");

		CORE.Ajax(
			{
				module:FORUM.name,
				language:FORUM.language,
				event:"edit",
				id:id
			},
			function(r)
			{
				var tofull=false,
					post=th.closest(".post");

				post
					.find("form").remove().end()
					.find(".text,.edited,.signature,.approved,.rejected").hide().filter(":first").after(r).end().end()

				.on("submit","form",function(e2){
					if(tofull)
						return true;

					CORE.Ajax(
						$.extend(
							CORE.Inputs2object( $(":input",this) ),
							{
								module:FORUM.name,
								language:FORUM.language,
								event:"save",
								id:id
							}),
						function(r2)
						{
							post.find("form").remove();
							$.each(r2,function(i,v){
								post.find("."+i).html(v).not(":empty").show();
							});
						}
					);
					e2.preventDefault();
				}).on("click",".fb-cancel",function(e2){
					e2.preventDefault();
					post
						.find("form").remove().end()
						.find(".text,.edited,.signature,.approved,.rejected").not(":empty").show();
				}).on("click",".fb-to-full",function(){
					tofull=true;
				});
			}
		);
	});

	/*
		Подумать: эта кнопка расположена и в интерфейсе редактирования поста
		.on("click",".fb-delete-post",function(){
		if(confirm("Вы действительно хотите удалить этот пост?"))
		{
			var id=$(this).data("id");

			CORE.Ajax(
				{
					module:FORUM.name,
					language:FORUM.language,
					event:"delete",
					id:id
				},
				function(d)
				{
					$("#post"+id).remove();

					var np=$("a.pnpost:last");
					if(np.size()>0)
						window.location.href=np.attr("href")+"#post";
					else if($(".fb-delete-post").size()==0)
						window.location.reload();
				}
			);
		}
		return false;
	})

	//Кнопка изменения закрепленности темы
	.on("click",".changepin",function(){
		var d=$("input[name=\"_pin\"]"),
			n=prompt("Введите количество дней",d.val());
		n=parseInt(n);
		if(n>0 && n<1000)
		{
			d.val(n);
			$("span",this).text(n);
		}
		return false;
	});*/

	//Загрузка новых постов
	var to,
		autoupdate=FORUM.autoupdate,
		status=$("#topic-status"),
		aui,//AutoUpdateInterval
		NewHash=function(h)
		{
			with(window.location)
			{
				if(hash.replace(/#/g,"")==h)
					hash="";
				hash=h;
			}
		},
		Finish=function(tl)
		{
			clearTimeout(to);
			to=setTimeout(function(){
				status.fadeOut("slow",function(){
					$(this).removeClass("load ok error")
				});
			},tl||5000);
		},
		LoadNewPosts=function(r)
		{
			if(r.userposts)
				$.each(r.userposts,function(i,v){
					$(".user-posts-"+i).text(v);
				});

			if(r.posts)
			{
				posts.append(r.posts);
				FORUM.ln=r.ln;
				FORUM.ld=r.lp;
			}

			if(r.first)
				NewHash("post"+r.first);

			if(r.runtask)
				FORUM.RunTask();
		},
		DoLoadNewPosts=function(auto)
		{
			CORE.QAjax(
				{
					module:FORUM.name,
					language:FORUM.language,
					event:"lnp",
					t:FORUM.t,
					ld:FORUM.ld,
					ln:FORUM.ln,
					filter:FORUM.lp
				},
				{
					OnBegin:function()
					{
						if(!auto)
						{
							clearTimeout(to);
							autoupdate=false;
							status.removeClass("ok error").addClass("load").text(CORE.Lang("forum_lnp")).show();
						}
					},
					OnSuccess:function(r)
					{
						LoadNewPosts(r);
						if(!auto)
						{
							status.removeClass("load error").addClass("ok").text(CORE.Lang(r.posts ? "forum_posts_loaded" : "forum_no_new_posts")).show();
							Finish();
						}
					},
					OnFail:function(s)
					{
						if(auto)
							autoupdate=false;
						else
						{
							status.removeClass("load ok").addClass("error").text(s);
							Finish(10000);
						}
					}
				}
			);
		};

	$(document).on("click",".fb-lnp",function(e){
		e.preventDefault();
		DoLoadNewPosts(false);
	});

	if(autoupdate)
		aui=setInterval(function(){
			if(autoupdate)
				DoLoadNewPosts(true);
		},autoupdate);

	//Quick post form - форма быстрого ответа
	var tofull=false;
	$("#quick-reply").submit(function(e){
		if(tofull || !FORUM.lp || !$.isEmptyObject(FORUM.filter))
			return true;

		CORE.QAjax(
			$.extend(
				CORE.Inputs2object( $(":input",this) ),
				{
					module:FORUM.name,
					language:FORUM.language,
					event:"new-post",
					text:EDITOR.Get("text"),
					t:FORUM.t,
					ld:FORUM.ld,
					ln:FORUM.ln
				}
			),
			{
				OnBegin:function()
				{
					clearTimeout(to);
					autoupdate=false;
					status.removeClass("ok error").addClass("load").text(CORE.Lang("forum_waitpost")).show();
				},
				OnSuccess:function(r){
					if(r.merged && $("#post"+r.merged+" .text:first").append(r.text).size()>0)
					{
						status.removeClass("load error").addClass("ok").text(CORE.Lang("forum_merged"));
						Merged();
						setTimeout(function(){
							NewHash("merged"+r.merged);
						},200);
					}
					else
						r.merged=false;

					LoadNewPosts(r);
					if(!r.merged)
						status.removeClass("load error").addClass("ok").text(CORE.Lang(r.posts ? "forum_posts_loaded" : "forum_no_new_posts")).show();

					Finish();
					$("#captcha").click();
					EDITOR.Set("","text");
					$("#quick-reply input[name=check]").val("");
					autoupdate=true;
				},
				OnFail:function(r){
					if(typeof r!="string")
					{
						if(r.captcha)
							$("#captcha").click();
						r=r.error;
					}
					var s="";
					if(typeof r=="string")
						s=r;
					else
						$.each(r,function(i,v){
							s+=(s ? "<br />" : "")+(CORE.Lang("forum_"+v)||v);
						});
					status.removeClass("load ok").addClass("error").html(s);
					NewHash("topic-status");
					Finish(10000);
				}
			}
		);
		e.preventDefault();
	}).on("click",".fb-to-full",function(){
		tofull=true;
	});

	//Мультимодерирование постов
	var pmm_merge=$("#posts-mm-panel .merge [name=\"mm[author]\"]"),
		pmm_do=$("#posts-mm-panel [name=\"mm[do]\"]"),
		pmm_omerge=$("#posts-mm-panel [value=merge]"),
		pmm_old_n=0;
		$("#posts").on("change","[name=\"mm[p][]\"]:checkbox").change(function(){
			var checked=$("#posts [name=\"mm[p][]\"]:checked"),
				mv=pmm_merge.val(),
				n=checked.size();
			$("#with-selected").text( (CORE.Lang("forum_pws"))(n) );
			$("#posts-mm-panel :input").prop("disabled",n<1);

			//Запись в селект с основными темами
			pmm_merge.children().remove();
			if(n>1)
			{
				pmm_omerge.prop("disabled",false);
				checked.closest(".post").find(".fb-insert-nick").each(function(){
					var id=$(this).data("id")||0;
					if(pmm_merge.find("[value="+id+"]").size()==0)
						$("<option>").text( $(this).text() ).val(id).appendTo(pmm_merge);
				});
				if(mv)
					pmm_merge.val(mv);
			}
			else
				pmm_omerge.prop("disabled",true);
			pmm_merge.change();

			if(pmm_do.val()===null)
				pmm_do.find(":selected").prop("selected",false).end().change();

			//Предотвращение излишнего вызова методов
			if(n>0 && pmm_old_n==0 || n==0 && pmm_old_n>0)
			{
				pmm_old_n=n;
				if(n==0)
					$("#posts-mm-panel .extra").hide();
				else
					pmm_do.change();
			}
		});

	pmm_do.change(function(){
		var v=$(this).val().replace(/[^a-z0-9\-_]+/,"");
		$("#posts-mm-panel .extra:not(."+v+")").hide();
		$("#posts-mm-panel ."+v).show();
	}).change();

	$("#topics-mm-form").submit(function(){
		switch(pmm_do.val())
		{
			case "delete":
				return confirm(CORE.Lang("forum_delete_posts_confirm"));
		}
		return true;
	});
	//[E] Мультимодерирование постов

	//Мультимодерирование тем
	var tmm_merge=$("#topics-mm-panel .merge [name=\"mm[main]\"]"),
		tmm_pin=$("#topics-mm-panel [value=pin]"),
		tmm_unpin=$("#topics-mm-panel [value=unpin]"),
		tmm_omerge=$("#topics-mm-panel [value=merge]"),
		tmm_close=$("#topics-mm-panel [value=close]"),
		tmm_open=$("#topics-mm-panel [value=open]"),
		tmm_do=$("#topics-mm-panel [name=\"mm[do]\"]"),
		tmm_old_n=0,
		tmm_topics=$("#topics [name=\"mm[t][]\"]:checkbox").change(function(){
			var checked=tmm_topics.filter(":checked"),
				mv=tmm_merge.val(),
				n=checked.size();
			$("#with-selected").text( (CORE.Lang("forum_tws"))(n) );
			$("#topics-mm-panel :input").prop("disabled",n<1);

			//Запись в селект с основными темами
			tmm_merge.find("option:gt(0)").remove();
			if(n>1)
			{
				tmm_omerge.prop("disabled",false);
				checked.closest("tr").find("a.topic").each(function(){
					$("<option>").text( $(this).text() ).val( $(this).data("t")).appendTo(tmm_merge);
				});
				tmm_merge.val(mv);
			}
			else
				tmm_omerge.prop("disabled",true);
			tmm_merge.change();

			tmm_pin.prop("disabled",checked.filter(".unpinned").size()==0);
			tmm_unpin.prop("disabled",checked.filter(".pinned").size()==0);
			tmm_close.prop("disabled",checked.filter(".open").size()==0);
			tmm_open.prop("disabled",checked.filter(".closed").size()==0);
			tmm_omerge.prop("disabled",checked.filter(".closed, .open").size()==0);

			//Сокрытие неактивного выбранного пункта
			if(tmm_do.val()===null)
				tmm_do.find(":selected").prop("selected",false).end().change();

			//Предотвращение излишнего вызова методов
			if(n>0 && tmm_old_n==0 || n==0 && tmm_old_n>0)
			{
				tmm_old_n=n;
				if(n==0)
					$("#topics-mm-panel .extra").hide();
				else
					tmm_do.change();
			}
		});

	tmm_do.change(function(){
		var v=$(this).val().replace(/[^a-z0-9\-_]+/,"");
		$("#topics-mm-panel .extra:not(."+v+")").hide();
		$("#topics-mm-panel ."+v).show();
	});

	tmm_merge.change(function(){
		if($(this).val()==0)
			$("#merge-other").show();
		else
			$("#merge-other").hide();
	}).change();

	$("#topics-mm-form").submit(function(){
		switch(tmm_do.val())
		{
			case "delete":
				return confirm(CORE.Lang("forum_delete_topics_confirm"));
		}
		return true;
	});
	//[E] Мультимодерирование тем

	//Подписка на тему
	$("#forum-subscription[data-t]").change(function(){
		var th=$(this),
			v=th.val();
		FORUM.SubscribeTopic(th.data("t"),v,function(){
			switch(v)
			{
				case "0":
					v="cancelled";
				break;
				case "i":
					v="ti";
				break;
				case "d":
					v="td";
				break;
				case "w":
					v="tw";
				break;
				case "m":
					v="tm";
				break;
			}
			alert(CORE.Lang("forum_subscription_"+v));
		});
	});

	//Подписка на форум
	$("#forum-subscription[data-f][data-l]").change(function(){
		var th=$(this),
			v=th.val();
		FORUM.SubscribeForum(th.data("f"),th.data("l"),v,function(){
			switch(v)
			{
				case "0":
					v="cancelled";
				break;
				case "i":
					v="fi";
				break;
				case "d":
					v="fd";
				break;
				case "w":
					v="fw";
				break;
				case "m":
					v="fm";
				break;
			}
			alert(CORE.Lang("forum_subscription_"+v));
		});
	});

	//Обработчик нажатия ссылки "Отметить этот форум прочитанным"
	$("#forum-read[data-f]").click(function(){
		FORUM.ForumRead($(this).data("f"),function(){
			$("#topics .new").toggleClass("read new").prop("title",CORE.Lang("forum_tread")).closest("tr").find(".get-new-post").remove();
			$(".forum-read").remove();
		});
		return false;
	});

	//Удаление надписи "Отместить все форумы прочтенными", если нет непрочтенных форумов
	if($(".forumimg:not(.read)").size()==0)
		$(".all-read").remove();

	//Обработчик нажатия ссылки "Отметить все форумы прочитанными"
	$("#all-read").click(function(){
		var th=$(this);
		FORUM.AllRead(function(){
			$(".all-read").remove();
			$(".forumimg").filter(".new").toggleClass("read new").prop("title",CORE.Lang("forum_nnp"));
		});
		return false;
	});

	//Показ-скрытие категорий
	var hide=localStorage.getItem("forum-categories");
	hide=hide ? $.unique(hide.split(".")) : [];
	$("a.toggle")
		.click(function(){
			var img=$(this).closest("div").toggleClass("fcathide").end().find("img"),
				src=img.prop("src"),
				id=$(this).data("id"),
				k=$.inArray(id,hide),
				hided=$("#fc"+id).fadeToggle("slow").is(":hidden");

			img.prop("src", img.data("src") ).data("src",src);
			if(hided && k==-1)
				hide.push(id);
			else if(!hided && k>-1)
				hide.splice(k,1);

			localStorage.setItem("forum-categories",hide.join("."));
			return false;
		})
		.each(function(){
			var id=$(this).data("id");
			if($.inArray(id,hide)>-1)
			{
				var img=$(this).closest("div").toggleClass("fcathide").end().find("img"),
					src=img.prop("src");
				img.prop("src", img.data("src") ).data("src",src);
				$("#fc"+id).hide();
			}
		});
	//[E] Показ-скрытие категорий

	//Пометка форума прочитанным
	$(".forums").on("click",".new[data-f]",function(e){
		e.preventDefault();
		var th=$(this);
		FORUM.ForumRead(th.data("f"),function(){
			th.toggleClass("read new").prop("title",CORE.Lang("forum_nnp"));
		});
	});

	//Пометка темы прочитанной
	$("#topics").on("click",".new[data-t]",function(){
		var th=$(this);
		FORUM.TopicRead(th.data("t"),function(){
			th.toggleClass("read new").prop("title",CORE.Lang("forum_tread")).closest("tr").find(".get-new-post").remove();
		});
	});

	//Сужение размеров td под иконки категорий
	$("td.img > img").load(function(){
		$(this).parent().width( $(this).outerWidth()+"px" );
	}).each(function(){
		$(this).trigger("load");
	});
});