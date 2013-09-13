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
	lp:0,
	t:0,
	n:0,
	lastpage:false,

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
	var qp=CORE.GetCookie(FORUM.n+"-qp"),
		qF=function(){
			var id=$(this).data("id").toString();
			if(id && $.inArray(id,qp)!=-1)
				$(this).addClass("active");
		};
	qp=qp ? $.unique(qp.split(",")) : [];
	$(".fb-quote").each(qF);

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
		alert(qp.join(","));
		CORE.SetCookie(FORUM.n+"-qp",qp.join(","));
	})

	//Быстрый поиск поста, если он находится на этой же странице
	.on("click",".fb-find-post",function(){
		var id=$(this).data("id");
		if($("#post"+id).size()>0)
			$(this).prop("href","#post"+id);
	})

	.on("click","a.fb-ltp",function(e){
		e.preventDefault();
		prompt("Скопируйте ссылку:",$(this).prop("href"));
	})

	.on("click","a.fb-insert-nick",function(e){
		e.preventDefault();
		EDITOR.Embed("nick",{name:$(this).text()});
	})

	/*.on("click",".fb-quick-quote",function(){
		var o=$(this),
			name=o.data("name"),
			text=o.closest(".post").find(".text:first").html(),
			sel,m;

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
			alert("Пользователь "+name+" не писал этого!");
		return false;
	})

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

	//Быстрая правка поста
	.on("click",".fb-quick-edit",function(){
		var id=$(this);
		CORE.Ajax(
			{
				module:FORUM.name,
				language:FORUM.language,
				event:"edit",
				id:th.data("id")
			},
			function(r)
			{
				var tofull=false,
					post=th.closest(".post");
				post.find("form").remove().end().find(".text,.edited,.signature").hide().after(r).end()
				.on("submit","form",function(){
					if(tofull)
						return true;
					var params={};
					$.each($(this).serializeArray(),function(i,n){
						params[n.name]=n.value;
					});
					CORE.Ajax(
						$.extend(
							params,
							{
								module:FORUM.name,
								language:FORUM.language,
								event:"save",
								id:id
							}),
						function(rs)
						{
							if(rs.edited)
								post.find(".edited").html(rs.edited);
							post.find("form").remove().end().find(".text").html(rs.text).end()
								.find(".text,.edited").not(":empty").show();
						}
					);
					return false;
				}).on("click",".fb-cancel",function(){
					post.find("form").remove().end().find(".text,.edited,.signature").not(":empty").show();
					return false;
				}).on("click",".fb-tofull",function(){
					tofull=true;
					return false;
				});
			}
		);
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
	});
//ToDo! Container
	//Загрузка новых постов
	var to,
		Finish=function(tl)
		{
			clearTimeout(to);
			to=setTimeout(function(){
				container.children(".status").fadeOut("slow",function(){
					$(this).removeClass("load ok error")
				});
			},tl||3000);
		},
		LoadNewPosts=function(r)
		{
			if(r)
			{
				for(var i in r.uposts)
					$(".uposts"+i).text(r.uposts[i]);

				$("#nphere").show().append(r.posts);
				FORUM.postn=r.n;
				FORUM.lp=r.lp;

				window.location.hash="#post"+r.first;
				if(FORUM.postschecks)
				{
					FORUM.postschecks.Rescan();
					FORUM.postschecks.DoModerPosts();
				}
			}
			$("#lnp-status").toggleClass("load ok").text(r ? "Посты успешно загружены" : "Новых постов нет");
			Finish();
		};
	$(document).on("click",".fb-lnp",function(){
		CORE.Ajax(
			{
				module:FORUM.name,
				language:FORUM.language,
				event:"lnp",
				t:FORUM.t,
				lp:FORUM.lp,
				n:FORUM.postn
			},
			{
				OnBegin:function()
				{
					clearTimeout(to);
					$("#lnp-status").removeClass("ok error").addClass("load").text("Загрузка новых постов...").show();
				},
				OnEnd:Finish,
				OnSuccess:LoadNewPosts,
				OnFail:function(s)
				{
					$("#lnp-status").toggleClass("load error").text(s);
					Finish(5000);
				}
			}
		);
		return false;
	});*/

	//Quick post form - форма быстрого ответа
	/*var tofull=false;
	$("#qpf").submit(function(e){
		if(tofull)
			return true;
		var params={},
			th=this;
		$.each($(this).serializeArray(),function(i,n){
			params[n.name]=n.value;
		});
		CORE.Ajax(
			$.extend(
				params,
				{
					module:FORUM.name,
					language:FORUM.language,
					event:"newpost",
					t:FORUM.t,
					lp:FORUM.lp,
					n:FORUM.postn,
					redirect:FORUM.lastpage ? 0 : 1
				}
			),
			{
				OnBegin:function(){
					clearTimeout(to);
					$("#lnp-status").removeClass("ok error").addClass("load").text("Подождите, идет отправка сообщения...").show();
				},
				OnEnd:Finish,
				OnSuccess:function(r){
					if(typeof r=="string")
						with(window.location)
						{
							href=protocol+"//"+hostname+(port ? ":"+port : "")+CORE.site_path+r;
						}
					else
					{
						if(r.posts)
						{
							LoadNewPosts(r);
							if(r.post.subs)
								FORUM.RunTask();
							if(r.post.info)
								$("#postinfo").html(r.post.info).show();
							else
								$("#postinfo").hide();
						}
						else if(r.merged)
						{
							$("#post"+r.merged+" .text:first").html(r.text);
							$("#lnp-status").toggleClass("load ok").text("Ваше сообщение успешно склеено с предыдущим...");
							window.location.hash='post'+r.merged;
						}
						$("#captcha_img").click();
						$("textarea,input[name=\"_check\"]").val("");
					}
				},
				OnFail:function(s){
					if(typeof s!="string")
					{
						if(s.captcha)
							$("#captcha_img").click();
						s=s.error;
					}
					$("#lnp-status").toggleClass("load error").text(s);
					Finish(3000);
				}
			}
		);
		return false;
	}).find("input[type=\"submit\"][name=\"_tofull\"]").click(function(){
		tofull=true;
	});*/

	//Переходы между постами при просмотре поста
	/*var pm=$("#post");//Post mark
	if(pm.is("a[name=post]"))
	{
		var posts=[],
			oldid=pm.data("id"),
			oldsp=pm.next(),//old showpost
			FSP=function(id)
			{
				if(typeof posts[id]=="undefined")
					CORE.Ajax(
						{
							module:FORUM.name,
							language:FORUM.language,
							event:"showpost",
							id:id
						},
						function(r)
						{
							posts[oldid]=oldsp.detach();
							oldid=id;
							pm.after(r);
							oldsp=pm.next();
							$(".fb-quote",oldsp).each(qF);
						}
					);
				else
				{
					posts[oldid]=oldsp.detach();
					posts[id].insertAfter(pm);
					oldid=id;
					oldsp=posts[id];
				}
			};
		CORE.HistoryInit(FSP,oldid);
		$(document).on("click","a.pnpost",function(){
			var id=$(this).data("id");
			FSP(id);
			CORE.HistoryPush($(this).prop("href"),FSP,id);
			return false;
		});
	};*/

	//Мультимодерирование постов
	var pmm_merge=$("#posts-mm-panel .merge [name=\"mm[author]\"]"),
		pmm_do=$("#posts-mm-panel [name=\"mm[do]\"]"),
		pmm_omerge=$("#posts-mm-panel [value=merge]"),
		pmm_old_n=0,
		pmm_posts=$("#posts [name=\"mm[p][]\"]:checkbox").change(function(){
			var checked=pmm_posts.filter(":checked"),
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
})