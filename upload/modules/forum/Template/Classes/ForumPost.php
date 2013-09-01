<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
class TplForumPost
{
	public static $lang;

	public static function PostErrors(array$errors)
	{
		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		return Eleanor::$Template->ForumMenu()->Message($errors,'error');
	}

	public static function NewTopic($values,$forum,$rights,$prefixes,$bypost,$errors,$uploader,$voting,$captcha)
	{
		$nav=array();
		$forum['parents']=explode(',',$forum['parents']);
		$Forum = $GLOBALS['Eleanor']->Forum;
		foreach($forum['parents'] as $v)
			if(isset($Forum->Forums->dump[$v]))
				$nav[]=array($Forum->Links->Forum($v), $Forum->Forums->dump[$v]['title']);
		array_push($nav,array($Forum->Links->Forum($forum['id']),$forum['title']),array(false,'Создание темы'));

		$C=Eleanor::$Template->ForumMenu($nav,null);
		if($errors)
		{
			foreach($errors as $k=>&$v)
				if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
					$v=static::$lang[$v];
			$C->Message($errors,'error');
		}

		$ti=1;
		$Lst=Eleanor::LoadListTemplate('table-form')->begin();
		if(!$Forum->user)
			$Lst->item('Ваше имя',Eleanor::Input('name',$values['name'],array('tabindex'=>$ti++)));

		$js='';
		if(is_array($values['title']))
		{
			$prefs=$title=$text=$descr=$checks=array();
			foreach($values['title'] as $l=>$v)
			{
				$prefo='';
				if(isset($prefixes[$l]))
					foreach($prefixes[$l] as $pref)
						$prefo.=Eleanor::Option($pref['title'],$pref['id'],$pref['id']==$values['prefix'][$l]);

				if($prefo)
					$prefs[$l]=Eleanor::Select('prefix['.$l.']',Eleanor::Option('-выберите префикс-',0).$prefo,array('tabindex'=>$ti++));

				$title[$l]=Eleanor::Input('title['.$l.']',$v,array('tabindex'=>$ti++));
				$descr[$l]=Eleanor::Input('description['.$l.']',$values['description'][$l],array('tabindex'=>$ti++));
				$text[$l]=$GLOBALS['Eleanor']->Editor->Area('text['.$l.']',$values['text'][$l],array('bypost'=>$bypost,'bb'=>array('tabindex'=>$ti++)));
				if(isset($uploader[$l]))
				{
					$text[$l].=$uploader[$l];
					$js.='FI'.$l.'.editor="text'.$l.'";';
				}

				$checks[]='<label>'.Eleanor::Check('langs[]',in_array($l,$values['langs']),array('tabindex'=>$ti++,'value'=>$l)).' '.Eleanor::$langs[$l]['name'].'</label>';
			}

			if($prefs)
				$Lst->item('Префикс темы',Eleanor::$Template->LangEdit($prefs,false));

			$Lst->item('Заголовок',Eleanor::$Template->LangEdit($title,false))
				->item('Описание',Eleanor::$Template->LangEdit($descr,false))
				->item('Текст',Eleanor::$Template->LangEdit($text,false));

			if(count($checks)>1)
			{
				$GLOBALS['jscripts'][]='js/multilang.js';
				$Lst->item('Для языков',join('<br />',$checks)
					.'<script type="text/javascript">/*<![CDATA[*/$(function(){new MultilangChecks({mainlang:"'.$Forum->language
					.'",langs:"[name=\"langs[]\"]",Switch:function(show,hide,where){
	for(var i in show)
		show[i]="."+show[i];
	for(var i in hide)
		hide[i]="."+hide[i];
	$(show.join(","),where).show().filter(show[0]).each(function(){
		try
		{
			this.Switch();
		}catch(e){}
	});
	if(show.length==1 && show[0]==".'.$GLOBALS['Eleanor']->Forum->language.'")
		hide.push(show[0]);
	$(hide.join(","),where).hide();
}});
});//]]></script>');
			}
		}
		else
		{
			$prefo='';
			foreach($prefixes as $id=>$pref)
				$prefo.=Eleanor::Option($pref,$id,$id==$values['prefix']);

			if($prefo)
				$Lst->item('Префикс темы',Eleanor::Select('prefix',Eleanor::Option('-выберите префикс-',0).$prefo,array('tabindex'=>$ti++)));

			$Lst->item('Заголовок',Eleanor::Input('title',$values['title'],array('tabindex'=>$ti++)))
				->item('Описание',Eleanor::Input('description',$values['description'],array('tabindex'=>$ti++)))
				->item('Текст',$GLOBALS['Eleanor']->Editor->Area('text',$values['text'],array('bypost'=>$bypost,'bb'=>array('tabindex'=>$ti++))).$uploader);

			if($uploader)
				$js.='FI.editor="text";';
		}

		if($captcha)
			$Lst->item('Введите код',Eleanor::Input('check','',array('tabindex'=>$ti++)).$captcha);

		if($Forum->user)
			$Lst->item('Подписка на тему',Eleanor::Select('subscription',Eleanor::Option('Подписка отсутствует',0,!$values['subscription']).Eleanor::OptGroup('Подписка с уведомлением',Eleanor::Option('Немедленным','i',$values['subscription']=='i').Eleanor::Option('Ежедневным','d',$values['subscription']=='d').Eleanor::Option('Еженедельным','w',$values['subscription']=='w').Eleanor::Option('Ежемесячным','m',$values['subscription']=='m')),array('tabindex'=>$ti++)));

		if($Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['chstatust']))
			$Lst->item('Установить статус',Eleanor::Select('status',Eleanor::Option('На модерации',-1,$values['status']==-1).Eleanor::Option('Блокировка',0,$values['status']==0).Eleanor::Option('Активировать',1,$values['status']==1),array('tabindex'=>$ti++)));

		if($Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['pin']))
			$Lst->item('Закрепить тему до',Dates::Calendar('pinned',$values['pinned'],true,array('tabindex'=>$ti++)));

		if($Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['opcl']) or in_array(1,$rights['close']))
			$Lst->item('Закрыть тему',Eleanor::Check('closed',$values['closed'],array('tabindex'=>$ti++)));

		if(!$GLOBALS['Eleanor']->Captcha->disabled)
			$Lst->item('Введите код',Eleanor::Input('check','',array('tabindex'=>$ti++)).$GLOBALS['Eleanor']->Captcha->GetCode());

		$extra='';
		foreach($values['extra'] as $k=>$v)
			$extra.=Eleanor::Input('extra['.$k.']',$v,array('type'=>'hidden'));

		$topic=(string)$Lst->end();

		return$C.$Lst->form()
			->tabs(
				array('Тема',$topic),
				array('Опрос',$voting)
			)->submitline($extra.Eleanor::Button('Создать тему','submit',array('tabindex'=>$ti)))->endform()
			.($js ? '<script type="text/javascript">/*<![CDATA[*/$(function(){'.$js.'});//]]></script>' : '');
	}

	public static function AddEditPost($edit,$bypost,$values,$rights,$prights,$forum,$topic,$error,$hidden)
	{

	}

	public static function AjaxEditPost($values,$hidden)
	{
		#ToDo!
	}

	public static function Edited($data)
	{
		#ToDo!
	}
}
TplForumPost::$lang=Eleanor::$Language->Load(dirname(__DIR__).'/langs/forum-user-post-*.php',false);