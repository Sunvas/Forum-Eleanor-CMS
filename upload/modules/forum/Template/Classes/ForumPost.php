<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
class TplForumPost
{
	#Языковой массив
	public static $lang;

	/**
	 * Страница с ошибками о невозможности создания / правки поста или темы
	 * @param array $errors Ошибки
	 */
	public static function PostErrors(array$errors)
	{
		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		return Eleanor::$Template->ForumMenu()->Message($errors,'error');
	}

	/**
	 * Создание новой темы
	 * @param array $values Значения полей
	 * @param array $forum Форум
	 * @param array $rights Права на форуме
	 * @param array $prefixes Префиксы форума
	 * @param bool $bypost Флаг загрузки значений из POST запроса
	 * @param array $errors Ошибки
	 * @param string|false $uploader Интерфейс загрузки файлов
	 * @param string|false $voting Интерфейс опроса
	 * @param string|false $captcha Интерфейс капчи
	 */
	public static function NewTopic($values,$forum,$rights,$prefixes,$bypost,$errors,$uploader,$voting,$captcha)
	{
		$nav=array();
		$forum['parents']=explode(',',$forum['parents']);
		$Forum = $GLOBALS['Eleanor']->Forum;
		foreach($forum['parents'] as $v)
			if(isset($Forum->Forums->dump[$v]))
				$nav[]=array($Forum->Links->Forum($v), $Forum->Forums->dump[$v]['title']);
		array_push($nav,array($Forum->Links->Forum($forum['id']),$forum['title']),array(false,static::$lang['creating-topic']));

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
		if(isset($values['name']))
			$Lst->item(static::$lang['your-name'],Eleanor::Input('name',$values['name'],array('tabindex'=>$ti++)));

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
					$prefs[$l]=Eleanor::Select('prefix['.$l.']',Eleanor::Option(static::$lang['elect-prefix'],0).$prefo,array('tabindex'=>$ti++));

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
				$Lst->item(static::$lang['topic-prefix'],Eleanor::$Template->LangEdit($prefs,false));

			$Lst->item(static::$lang['title'],Eleanor::$Template->LangEdit($title,false))
				->item(static::$lang['descr'],Eleanor::$Template->LangEdit($descr,false))
				->item(static::$lang['text'],Eleanor::$Template->LangEdit($text,false));

			if(count($checks)>1)
			{
				$GLOBALS['jscripts'][]='js/multilang.js';
				$Lst->item(static::$lang['for-langs'],join('<br />',$checks)
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
				$Lst->item(static::$lang['topic-prefix'],Eleanor::Select('prefix',Eleanor::Option(static::$lang['elect-prefix'],0).$prefo,array('tabindex'=>$ti++)));

			$Lst->item(static::$lang['title'],Eleanor::Input('title',$values['title'],array('tabindex'=>$ti++)))
				->item(static::$lang['descr'],Eleanor::Input('description',$values['description'],array('tabindex'=>$ti++)))
				->item(static::$lang['text'],$GLOBALS['Eleanor']->Editor->Area('text',$values['text'],array('bypost'=>$bypost,'bb'=>array('tabindex'=>$ti++))).$uploader);

			if($uploader)
				$js.='FI.editor="text";';
		}

		if(isset($values['subscription']))
			$Lst->item(static::$lang['topic-subscription'],Eleanor::Select('subscription',Eleanor::Option(static::$lang['not-subscribe'],0,!$values['subscription']).Eleanor::OptGroup(static::$lang['notify'],Eleanor::Option(static::$lang['immediately'],'i',$values['subscription']=='i').Eleanor::Option(static::$lang['daily'],'d',$values['subscription']=='d').Eleanor::Option(static::$lang['weekly'],'w',$values['subscription']=='w').Eleanor::Option(static::$lang['monthly'],'m',$values['subscription']=='m')),array('tabindex'=>$ti++)));

		if(isset($values['status']))
			$Lst->item(static::$lang['set-status'],Eleanor::Select('status',Eleanor::Option(static::$lang['on-mod'],-1,$values['status']==-1).Eleanor::Option(static::$lang['blocked'],0,$values['status']==0).Eleanor::Option(static::$lang['activate'],1,$values['status']==1),array('tabindex'=>$ti++)));

		if(isset($values['pinned']))
			$Lst->item(static::$lang['pin-until'],Dates::Calendar('pinned',$values['pinned'],true,array('tabindex'=>$ti++)));

		if(isset($values['closed']))
			$Lst->item(static::$lang['close-topic'],Eleanor::Check('closed',$values['closed'],array('tabindex'=>$ti++)));

		if($captcha)
			$Lst->item(static::$lang['enter-captcha'],$captcha.'<br />'.Eleanor::Input('check','',array('tabindex'=>$ti++)));

		$extra='';
		foreach($values['extra'] as $k=>$v)
			$extra.=Eleanor::Input('extra['.$k.']',$v,array('type'=>'hidden'));

		$topic=(string)$Lst->end();

		if($voting)
			$Lst->form()
				->tabs(
					array(static::$lang['topic'],$topic),
					array(static::$lang['voting'],$voting)
				);
		else
			$C.=$topic;

		return$C
			.($forum['rules'] ? '<fieldset class="forums"><legend>'.static::$lang['rules'].'</legend>'.$forum['rules'].'</fieldset>' : '')
			.$Lst->submitline($extra.Eleanor::Button(static::$lang['create-topic'],'submit',array('tabindex'=>$ti)))->endform()
			.($js ? '<script type="text/javascript">/*<![CDATA[*/$(function(){'.$js.'});//]]></script>' : '');
	}

	/**
	 * Редактирование темы (или нескольких сродненных).
	 * @param array $values Значения полей
	 * @param array $forum Форум
	 * @param array $rights Права на форуме
	 * @param array $prefixes Префиксы тем
	 * @param bool $bypost Флаг загрузки значений из POST запроса
	 * @param array $errors Ошибки
	 * @param string|false $uploader Интерфейс загрузки файлов
	 * @param string|false $voting Интерфейс опроса
	 * @param string|false $captcha Интерфейс капчи
	 */
	public static function EditTopic($values,$forum,$rights,$prefixes,$bypost,$errors,$uploader,$voting,$captcha)
	{
		$nav=array();
		$forum['parents']=explode(',',$forum['parents']);
		$Forum = $GLOBALS['Eleanor']->Forum;
		foreach($forum['parents'] as $v)
			if(isset($Forum->Forums->dump[$v]))
				$nav[]=array($Forum->Links->Forum($v), $Forum->Forums->dump[$v]['title']);
		array_push($nav,array($Forum->Links->Forum($forum['id']),$forum['title']),array(false,static::$lang['editing-topic']));

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
			$Lst->item(static::$lang['your-name'],Eleanor::Input('name',$values['name'],array('tabindex'=>$ti++)));

		$js='';
		if(is_array($values['title']))
		{
			$prefs=$title=$text=$descr=$subscr=$status=$pin=$close=array();
			foreach($values['title'] as $l=>$v)
			{
				$prefo='';
				if(isset($prefixes[$l]))
					foreach($prefixes[$l] as $pref)
						$prefo.=Eleanor::Option($pref['title'],$pref['id'],$pref['id']==$values['prefix'][$l]);

				if($prefo)
					$prefs[$l]=Eleanor::Select('prefix['.$l.']',Eleanor::Option(static::$lang['elect-prefix'],0).$prefo,array('tabindex'=>$ti++));

				$title[$l]=Eleanor::Input('title['.$l.']',$v,array('tabindex'=>$ti++));
				$descr[$l]=Eleanor::Input('description['.$l.']',$values['description'][$l],array('tabindex'=>$ti++));

				if(isset($values['text'][$l]))
					$text[$l]=$GLOBALS['Eleanor']->Editor->Area('text['.$l.']',$values['text'][$l],array('bypost'=>$bypost,'bb'=>array('tabindex'=>$ti++,'class'=>'sic')));

				if(isset($values['subscription'][$l]))
					$subscr[$l]=Eleanor::Select('subscription['.$l.']',Eleanor::Option(static::$lang['not-subscribe'],0,!$values['subscription'][$l]).Eleanor::OptGroup(static::$lang['notify'],Eleanor::Option(static::$lang['immediately'],'i',$values['subscription'][$l]=='i').Eleanor::Option(static::$lang['daily'],'d',$values['subscription'][$l]=='d').Eleanor::Option(static::$lang['weekly'],'w',$values['subscription'][$l]=='w').Eleanor::Option(static::$lang['monthly'],'m',$values['subscription'][$l]=='m')),array('tabindex'=>$ti++,'class'=>'select sic'));

				if(isset($values['status']))
					$status[$l]=Eleanor::Select('status['.$l.']',Eleanor::Option(static::$lang['on-mod'],-1,$values['status'][$l]==-1).Eleanor::Option(static::$lang['blocked'],0,$values['status'][$l]==0).Eleanor::Option(static::$lang['activate'],1,$values['status'][$l]==1),array('tabindex'=>$ti++,'class'=>'select sic'));

				if(isset($values['pinned']))
					$pin[$l]=Dates::Calendar('pinned['.$l.']',$values['pinned'][$l],true,array('tabindex'=>$ti++));

				if(isset($values['closed']))
					$close[$l]=Eleanor::Check('closed['.$l.']',$values['closed'][$l],array('tabindex'=>$ti++));

				if(isset($uploader[$l]))
				{
					$text[$l].=$uploader[$l];
					$js.='FI'.$l.'.editor="text'.$l.'";';
				}
			}

			if($prefs)
				$Lst->item(static::$lang['topic-prefix'],Eleanor::$Template->LangEdit($prefs,false));

			$Lst->item(static::$lang['title'],Eleanor::$Template->LangEdit($title,false))
				->item(static::$lang['descr'],Eleanor::$Template->LangEdit($descr,false));

			if($text)
				$Lst->item(static::$lang['text'],Eleanor::$Template->LangEdit($text,false));

			if($subscr)
				$Lst->item(static::$lang['topic-subscription'],Eleanor::$Template->LangEdit($subscr,false));

			if($status)
				$Lst->item(static::$lang['set-status'],Eleanor::$Template->LangEdit($status,false));

			if($pin)
				$Lst->item(static::$lang['pin-until'],Eleanor::$Template->LangEdit($pin,false));

			if($close)
				$Lst->item(static::$lang['close-topic'],Eleanor::$Template->LangEdit($close,false));
		}
		else
		{
			$prefo='';
			foreach($prefixes as $id=>$pref)
				$prefo.=Eleanor::Option($pref,$id,$id==$values['prefix']);

			if($prefo)
				$Lst->item(static::$lang['topic-prefix'],Eleanor::Select('prefix',Eleanor::Option(static::$lang['elect-prefix'],0).$prefo,array('tabindex'=>$ti++)));

			$Lst->item(static::$lang['title'],Eleanor::Input('title',$values['title'],array('tabindex'=>$ti++)))
				->item(static::$lang['descr'],Eleanor::Input('description',$values['description'],array('tabindex'=>$ti++)));

			if(isset($values['text']))
				$Lst->item(static::$lang['text'],$GLOBALS['Eleanor']->Editor->Area('text',$values['text'],array('bypost'=>$bypost,'bb'=>array('tabindex'=>$ti++,'class'=>'sic'))).$uploader);

			if(isset($values['subscription']))
				$Lst->item(static::$lang['topic-subscription'],Eleanor::Select('subscription',Eleanor::Option(static::$lang['not-subscribe'],0,!$values['subscription']).Eleanor::OptGroup(static::$lang['notify'],Eleanor::Option(static::$lang['immediately'],'i',$values['subscription']=='i').Eleanor::Option(static::$lang['daily'],'d',$values['subscription']=='d').Eleanor::Option(static::$lang['weekly'],'w',$values['subscription']=='w').Eleanor::Option(static::$lang['monthly'],'m',$values['subscription']=='m')),array('tabindex'=>$ti++,'class'=>'select sic')));

			if(isset($values['status']))
				$Lst->item(static::$lang['set-status'],Eleanor::Select('status',Eleanor::Option(static::$lang['on-mod'],-1,$values['status']==-1).Eleanor::Option(static::$lang['blocked'],0,$values['status']==0).Eleanor::Option(static::$lang['activate'],1,$values['status']==1),array('tabindex'=>$ti++,'class'=>'select sic')));

			if(isset($values['pinned']))
				$Lst->item(static::$lang['pin-until'],Dates::Calendar('pinned',$values['pinned'],true,array('tabindex'=>$ti++,'class'=>'sic')));

			if(isset($values['closed']))
				$Lst->item(static::$lang['close-topic'],Eleanor::Check('closed',$values['closed'],array('tabindex'=>$ti++)));

			if($uploader)
				$js.='FI.editor="text";';
		}

		if($captcha)
			$Lst->item(static::$lang['enter-captcha'],$captcha.'<br />'.Eleanor::Input('check','',array('tabindex'=>$ti++)));

		$extra='';
		foreach($values['extra'] as $k=>$v)
			$extra.=Eleanor::Input('extra['.$k.']',$v,array('type'=>'hidden'));

		$topic=(string)$Lst->end();

		if($voting)
			$Lst->form()
				->tabs(
					array(static::$lang['topic'],$topic),
					array(static::$lang['voting'],$voting)
				);
		else
			$C.=$topic;

		return$C
			.($forum['rules'] ? '<fieldset class="forums"><legend>'.static::$lang['rules'].'</legend>'.$forum['rules'].'</fieldset>' : '')
			.$Lst->submitline($extra.Eleanor::Button(static::$lang['save-topic'],'submit',array('tabindex'=>$ti)))->endform()
			.($js ? '<script type="text/javascript">/*<![CDATA[*/$(function(){'.$js.'});//]]></script>' : '');
	}

	/**
	 * Интерфейс создания / правки поста
	 * @param array $values Значения формы
	 * @param array $post Пост
	 * @param array $forum Форум
	 * @param array $topic Тема
	 * @param array $rights Права на форуме
	 * @param array $trights Права в теме
	 * @param array $errors Ошибоки
	 * @param bool $bypost Флаг загрузки значений из POST запроса
	 * @param string|false $uploader Загрузчик файлов
	 * @param array $links Ссылки
	 * @param string|false $captcha Интерфейс капчи
	 */
	public static function AddEditPost($values,$post,$forum,$topic,$rights,$trights,$errors,$bypost,$uploader,$links,$captcha)
	{
		$nav=array();
		$forum['parents']=explode(',',$forum['parents']);
		$Forum = $GLOBALS['Eleanor']->Forum;
		foreach($forum['parents'] as $v)
			if(isset($Forum->Forums->dump[$v]))
				$nav[]=array($Forum->Links->Forum($v), $Forum->Forums->dump[$v]['title']);
		array_push($nav,
			array($Forum->Links->Forum($forum['id']),$forum['title']),
			array($links['topic'],$topic['title']),
			array($links['ltp'],$post ? static::$lang['editing-post'] : static::$lang['creating-post'])
		);

		$C=Eleanor::$Template->ForumMenu($nav,null);
		if($errors)
		{
			foreach($errors as $k=>&$v)
				if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
					$v=static::$lang[$v];
			$C->Message($errors,'error');
		}

		$Lst=Eleanor::LoadListTemplate('table-form')
			->form(array('action'=>$links['action']))
			->begin();

		$ti=1;
		if(isset($values['name']))
			$Lst->item(static::$lang['your-name'],Eleanor::Input('name',$values['name'],array('tabindex'=>$ti++)));

		$Lst->item(static::$lang['text'],$GLOBALS['Eleanor']->Editor->Area('text',$values['text'],array('bypost'=>$bypost,'bb'=>array('class'=>'sic','tabindex'=>$ti++))).$uploader);

		if($post)
			$Lst->item(static::$lang['edit-reason'],Eleanor::Input('edit_reason',$values['edit_reason'],array('class'=>'sic','tabindex'=>$ti++)));

		if(isset($values['status']))
			$Lst->item(static::$lang['set-status'],Eleanor::Select('status',Eleanor::Option(static::$lang['on-mod'],-1,$values['status']==-1).Eleanor::Option(static::$lang['blocked'],0,$values['status']==0).Eleanor::Option(static::$lang['activate'],1,$values['status']==1),array('tabindex'=>$ti++,'class'=>'select sic')));

		if(isset($values['subscription']))
			$Lst->item(static::$lang['topic-subscription'],Eleanor::Select('subscription',Eleanor::Option(static::$lang['not-subscribe'],0,!$values['subscription']).Eleanor::OptGroup(static::$lang['notify'],Eleanor::Option(static::$lang['immediately'],'i',$values['subscription']=='i').Eleanor::Option(static::$lang['daily'],'d',$values['subscription']=='d').Eleanor::Option(static::$lang['weekly'],'w',$values['subscription']=='w').Eleanor::Option(static::$lang['monthly'],'m',$values['subscription']=='m')),array('tabindex'=>$ti++,'class'=>'select sic')));

		if(isset($values['closed']))
			$Lst->item(static::$lang['close-topic'],Eleanor::Check('closed',$values['closed'],array('tabindex'=>$ti++)));

		if($captcha)
			$Lst->item(static::$lang['enter-captcha'],$captcha.'<br />'.Eleanor::Input('check','',array('tabindex'=>$ti++)));

		$extra='';
		foreach($values['extra'] as $k=>$v)
			$extra.=Eleanor::Input('extra['.$k.']',$v,array('type'=>'hidden'));

		return$C
			.($forum['rules'] ? '<fieldset class="forums"><legend>'.static::$lang['rules'].'</legend>'.$forum['rules'].'</fieldset>' : '')
			.$Lst->end()->submitline(
				$extra.Eleanor::Button($post ? static::$lang['save-post'] : static::$lang['create-post'],'submit',array('tabindex'=>$ti))
				.($post ? ' <a href="#" class="fb-delete-post" data-id="'.$post['id'].'">'.static::$lang['delete-post'].'</a>' : '')
			)->endform()
			.($uploader ? '<script type="text/javascript">/*<![CDATA[*/$(function(){ FI.editor="text" })//]]></script>' : '');
	}

	/**
	 * Форма быстрого редактирования поста (сразу в теме при помощи ajax)
	 * @param array $values Значения полей формы, ключи:
	 *   string text Текст поста
	 *   string edit_reason Причина правки поста
	 * @param array $links Ссылки, ключи:
	 *   string action Ссылка для параметра action формы
	 */
	public static function AjaxEditPost($values,$links)
	{
		$extra='';
		foreach($values['extra'] as $k=>$v)
			$extra.=Eleanor::Input('extra['.$k.']',$v,array('type'=>'hidden'));

		$ti=0;
		return'<form method="post" action="'.$links['action'].'">'
			.$GLOBALS['Eleanor']->Editor->Area('text',$values['text'],array('bb'=>array('tabindex'=>$ti++)))
			.'<div class="clr"></div><div style="float:left">'
			.sprintf(static::$lang['reason%'],Eleanor::Input('edit_reason',$values['edit_reason'],array('tabindex'=>$ti++)))
			.'</div><div style="float:right">'.$extra
			.Eleanor::Button(static::$lang['save-post'],'submit',array('tabindex'=>$ti++)).' '
			.Eleanor::Button(static::$lang['cancel'],'button',array('class'=>'fb-cancel','tabindex'=>$ti++)).' '
			.Eleanor::Button(static::$lang['to-full'],'submit',array('class'=>'fb-to-full','name'=>'_to-full','tabindex'=>$ti++))
			.'</div></form>';
	}
}
TplForumPost::$lang=Eleanor::$Language->Load(dirname(__DIR__).'/langs/forum-user-post-*.php',false);