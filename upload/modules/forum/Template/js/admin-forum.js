/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
function AddEditForum()
{
	$("#image").change(function(){
		var val=$(this).val(),
			pr=$("#preview");
		if(val)
			pr.prop("src",$(this).data("path")+val).closest("tr").show();
		else
			pr.prop("src","images/spacer.png").closest("tr").hide();
	}).change();
}

function EditGroup(parents)
{
	AddEditForum();

	var ga=$("input[name=\"grow_after\"]:first"),
		gt=$("select[name=\"grow_to\"]:first").change(function(){
			ga.prop("disabled",$(this).val()==0);
		}).change();
	if(parents)
	{
		var Check=function(ftd,state)
			{
				var ch=ftd.find(":checkbox");
				if(typeof state=="undefined")
					state=!ch.prop("checked");
				if(state)
				{
					ftd.css("text-decoration","line-through").prop("title",CORE.Lang("forum_inherits")).next().children("div").hide();
					if(ch.val()=="grow_to")
						ga.prop("disabled",false);
				}
				else
				{
					ftd.css("text-decoration","").prop("title","").next().children("div").show();
					if(ch.val()=="grow_to" && gt.val()==0)
						ga.prop("disabled",true);
				}
				ch.prop("checked",state)
			};
		$("#tg tr,#tp tr").find("td:first")
			.filter(function(){
				return $(this).has(":checkbox").size()>0;
			})
			.click(function(){
				Check($(this));
			})
			.each(function(){
				Check($(this),$(":checkbox",this).prop("checked"));
			}).css("cursor","pointer");
	}
}

function MassRights()
{
	var Check=function(ftd,state)
	{
		var ch=$(ftd).find(":checkbox");
		if(typeof state=="undefined")
			state=!ch.prop("checked");
		if(state)
			ch.end().css("text-decoration","line-through").prop("title",CORE.Lang("forum_ginh")).next().children("div").hide();
		else
			ch.end().css("text-decoration","").prop("title","").next().children("div").show();
		ch.prop("checked",state)
	};

	$("#fg tr").find("td:first")
		.filter(function(){
			return $(this).has(":checkbox").size()>0;
		})
		.click(function(){
			Check(this);
		})
		.each(function(){
			Check(this,$(":checkbox",this).prop("checked"));
		}).css("cursor","pointer");
}

function AddEditModer()
{
	var m=$("#moders");
	m.on("click",".del-moder",function(){
		if(m.find("li").size()>1)
			$(this).closest("li").remove();
		else
			$(this).closest("li").find(":input").val("");
		return false;
	});
	$("#add-moder").click(function(){
		$("#moders li:first").clone(true)
			.find(":input").val("").end()
			.appendTo("#moders")
			.find(".cloneable").trigger("clone");
		return false;
	});
}

function FGC()
{
	var fgr=$("#fgr"),
		showed=false,
		f,
		Pos=function(o,fade)
		{
			o=$(o);
			f=o.data("id");
			fgr.css({left:o.offset().left,top:o.offset().top+o.outerHeight()});
			if(fade)
				fgr.fadeIn("fast");
		};

	fgr.on("click",":button",function(){
		with(location)
			href=protocol+"//"+hostname+CORE.site_path+fgr.data("url").replace("_forum_",f).replace("_group_",fgr.find(":first").val());
	}).find(":first").change(function(){
			$(this).next().click();
		});

	$(".fgr").css("cursor","pointer").click(function(){
		if(showed)
			fgr.hide();
		else
			Pos(this,true);
		showed=!showed;
		return false;
	}).mouseover(function(){
			if(showed)
				Pos(this);
		});

	$(this).on("click",function(e){
		if(!$(e.target).is("#fgr,#fgr *"))
		{
			fgr.hide();
			showed=false;
		}
	});
}

function ForumGroupRights(parent)
{
	var Check=function(ftd,state)
	{
		var ch=ftd.find(":checkbox");
		if(typeof state=="undefined")
			state=!ch.prop("checked");
		if(state)
			ftd.css("text-decoration","line-through").prop("title",CORE.Lang(parent>0 ? "forum_hfrpf" : "forum_hfbrg")).next().children("div").hide();
		else
			ftd.css("text-decoration","").prop("title","").next().children("div").show();
		ch.prop("checked",state)
	};

	$("#fg tr").find("td:first").filter(function(){
		return $(this).has(":checkbox").size()>0;
	}).click(function(){
			Check($(this));
		}).each(function(){
			Check($(this),$(":checkbox",this).prop("checked"));
		}).css("cursor","pointer");
}