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
	});

	//Мультимодерирование постов
	var pmm_merge=$("#posts-mm-panel .merge [name=\"mm[author]\"]"),
		pmm_do=$("#posts-mm-panel [name=\"mm[do]\"]"),
		pmm_omerge=$("#posts-mm-panel [value=merge]"),
		pmm_old_n=0,
		pmm_posts=$("#posts [name=\"mm[p][]\"]:checkbox").change(function(){
			var checked=pmm_posts.filter(":checked"),
				mv=pmm_merge.val(),
				n=checked.size();
			$("#with-selected").text(CORE.Lang("forum_ws",[n]));
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
			$("#with-selected").text(CORE.Lang("forum_ws",[n]));
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
	//[E] Подписка на тему

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
	//[E] Подписка на форум

	//Обработчик нажатия ссылки "Отметить этот форум прочитанным"
	$("#forum-read[data-f]").click(function(){
		FORUM.ForumRead($(this).data("f"),function(){
			$("#topics .new").toggleClass("read new").prop("title",CORE.Lang("forum_tread")).closest("tr").find(".get-new-post").remove();
			$(".forum-read").remove();
		});
		return false;
	});
	//[E] Обработчик нажатия ссылки "Отметить этот форум прочитанным"

	//Удаление надписи "Отместить все форумы прочтенными", если нет непрочтенных форумов
	if($(".forumimg:not(.read)").size()==0)
		$(".all-read").remove();
	//[E] Удаление надписи "Отместить все форумы прочтенными", если нет непрочтенных форумов

	//Обработчик нажатия ссылки "Отметить все форумы прочитанными"
	$("#all-read").click(function(){
		var th=$(this);
		FORUM.AllRead(function(){
			$(".all-read").remove();
			$(".forumimg").filter(".new").toggleClass("read new").prop("title",CORE.Lang("forum_nnp"));
		});
		return false;
	});
	//[E] Обработчик нажатия ссылки "Отметить все форумы прочитанными"

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
	//[E] Пометка форума прочитанным

	//Пометка темы прочитанной
	$("#topics").on("click",".new[data-t]",function(){
		var th=$(this);
		FORUM.TopicRead(th.data("t"),function(){
			th.toggleClass("read new").prop("title",CORE.Lang("forum_tread")).closest("tr").find(".get-new-post").remove();
		});
	});
	//[E] Пометка темы прочитанной
})