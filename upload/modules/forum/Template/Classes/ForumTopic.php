<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
class TplForumTopic
{
	public static
		$lang=array();

	/**
	 * Отображение темы.
	 * @param array $forum Частичный дамп форума с полями из БД, которые теоретически нужны при просмотре темы +
	 *   int _read Timestamp чтения форума
	 *   bool _trash Является ли форум мусорником
	 * @param array $rights наши права на форуме, описание прав дано в классе ForumForums +
	 *   bool _mod Возможность "модерировать" свою тему: править / удалять / перемещать сообщения в этой теме
	 *   bool _status Возможность просматривать свои посты с разными статусами
	 *   bool _toggle Возможность просматривать чужие посты с разными статусами и менять эти статусы (модератор)
	 *   bool _mchstatus Мультиизменение статуса постов
	 *   bool _mdelete Мультиудаление постов
	 *   bool _mmove Мультиперемещение постов
	 *   bool _merge Мультиобъединение постов
	 * @param array $topic Частичный дамп тем с полями из БД, которые теоретически нужны при просмотре темы +
	 *   int _read TimeStamp прочтения темы
	 *   int _cnt Количество постов в теме
	 *   int _page Номер текущей страницы
	 *   int _pages Количество страниц
	 *   int _pp Количество постов на страницу
	 *   int _my Флаг принадлежности темы нам
	 *   bool _open Тема для нас открыта или нет?
	 *   array _filter Фильтры, ключи:
	 *     array status Статусы постов, которые нужно отображать, значения массива идентичны ключам этих значений, например array(1=>1)
	 *     bool my Просмотр только своих постов внутри темы
	 *   array _statuses Сводка количества постов в теме с определенным статусом, формат статус=>количество постов
	 * @param array $posts Посты темы, формат id=>дамп поста +
	 *   array _approved Пользователи, повысившие репутацию автору за текущий пост, ключи
	 *     int from ID пользователя, изменившего репутацию
	 *     string from_name Имя пользователя, изменившего репутацию, не безопасный HTML!
	 *     int value Число, на которое изменена репутация
	 *   array _rejected Пользователи, понизившие репутацию автору за текущий пост, аналогично _approved
	 *   bool _my Флаг принадлежности поста нам
	 *   bool r+
	 *   bool r-
	$a['_atp']=$Forum->Links->Action('find-post',$a['id']);#Ссылка на пост в теме
	$a['_ap']=$Forum->Links->Action('post',$a['id']);#Ссылка на пост
	$a['_checked']=in_array($a['id'],$checked);
	$a['_n']=++$offset;
	$a['_attached']=array();#ID прикрепленных аттачей
	#Аттачи, используемые в тексте
	$a['_attaches']=$Forum->Attach->GetFromText($a['text']);
	if($a['_attaches'])
	$attaches=array_merge($attaches,$a['_attaches']);

	$a['_answer']=$links['new-post'] ? $Forum->Links->Action('answer',$a['id']) : false;
	$a['_edit']=$mod || $medit;
	$a['_delete']=$mod || $mdelete;
	 * @param $attaches
	 * @param $ag
	 * @param $errors
	 * @param $online
	 * @param array $errors Ошибки
	 * @param array $info Информационные сообщения
	 * @param $links
	 * @param $voting
	 * @param $values
	 * @param $captcha
	 * @return string
	 */
	public static function ShowTopic($forum,$rights,$topic,$posts,$attaches,$ag,$errors,$info,$online,$links,$voting,$values,$captcha)
	{
		#Шапка
		$nav=array();
		$forum['parents']=explode(',',$forum['parents']);
		$Forum = $GLOBALS['Eleanor']->Forum;
		foreach($forum['parents'] as $p)
			if(isset($Forum->Forums->dump[$p]))
				$nav[]=array($Forum->Links->Forum($p), $Forum->Forums->dump[$p]['title']);
		array_push($nav,array($Forum->Links->Forum($forum['id']),$forum['title']),array(false,$topic['title']));

		$Header=Eleanor::$Template->ForumMenu($nav,array(array($links['rss'],'RSS '.$topic['title'])));

		if($errors)
		{
			foreach($errors as $k=>&$v)
				if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
					$v=static::$lang[$v];

			$Header->Message($errors,'error');
		}

		$fistatus=isset($topic['_filter']['status']) ? $topic['_filter']['status'] : array(1=>1);
		if($links['wait-posts'] and !isset($fistatus[-1]))
		{
			$lang=$rights['_toggle'] ? static::$lang['wait-moder'] : static::$lang['wait-my'];
			$Header->Message($lang($links['wait-posts'],$topic['_statuses'][-1]),'info');
		}

		switch($topic['status'])
		{
			case -1:
				$Header->Message(static::$lang['mess-1'],'info');
			break;
			case 0:
				$Header->Message(static::$lang['mess0'],'info');
		}

		#Сообщение о доступных языковых версиях
		if($topic['_related'])
		{
			$lang=static::$lang['related'];
			$Header->Message($lang($topic['_related']),'info');
		}

		#[E] Шапка

		#Модераторские опции
		$mtopic=$mposts='';#Option-ы для селектов модерирования темы и постов
		$movet=$opcl=$deletet=$move=$merge=false;
		if($forum['_moderator'] || $GLOBALS['Eleanor']->Forum->ugr['supermod'])
		{
			$GLOBALS['jscripts'][]='js/checkboxes.js';
			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['chstatust']))
				$mtopic.=Eleanor::OptGroup(static::$lang['togglestatus'],
					($topic['status']==-1 ? '' : Eleanor::Option(static::$lang['onmod'],'status=-1',$topic['status']==-1))
					.($topic['status']==0 ? '' : Eleanor::Option(static::$lang['ban'],'status=0',$topic['status']==0))
					.($topic['status']==1 ? '' : Eleanor::Option(static::$lang['activate'],'status=1',$topic['status']==1))
				);

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['deletet']))
			{
				$mtopic.=Eleanor::Option($forum['_trash'] ? static::$lang['permdelete'] : static::$lang['delete'],'delete');
				$deletet=true;
			}

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['movet']))
			{
				$movet=true;
				$mtopic.=Eleanor::Option(static::$lang['move'],'move');
			}

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['opcl']))
			{
				$mtopic.=$topic['state']=='open' ? Eleanor::Option(static::$lang['close'],'close') : Eleanor::Option(static::$lang['open'],'open');
				$opcl=true;
			}

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['pin']))
				$mtopic.=($topic['_pin'] ? Eleanor::Option(static::$lang['unpin'],'unpin') : '').Eleanor::Option(static::$lang['pin'],'pin');

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['merget']))
				$mtopic.=Eleanor::Option(static::$lang['merget'],'merge');

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['mchstatus']))
				$mposts.=Eleanor::OptGroup(static::$lang['togglestatus'],
					($fistatus==array(-1=>-1) ? '' : Eleanor::Option(static::$lang['onmod'],'status=-1'))
					.($fistatus==array(0) ? '' : Eleanor::Option(static::$lang['ban'],'status=0'))
					.($fistatus==array(1=>1) ? '' : Eleanor::Option(static::$lang['activate'],'status=1'))
				);

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['delete']))
				$mposts.=Eleanor::Option(static::$lang['delete'],'delete');

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['move']))
				$mposts.=Eleanor::Option(static::$lang['move'],'move');

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['merge']))
			{
				$mposts.=Eleanor::Option(static::$lang['merge'],'merge');
				$merge=true;
			}
		}

		if($topic['_my'])
		{
			if(!$opcl and in_array(1,$rights['close']))
				$mtopic.=$topic['state']=='open' ? Eleanor::Option(static::$lang['close'],'close') : Eleanor::Option(static::$lang['open'],'open');
			if(!$deletet and in_array(1,$rights['deletet']))
				$mtopic.=Eleanor::Option(static::$lang['delete'],'deletet');
		}
		#[E] Модераторские опции

		#Генерация списка постов
		$postinfo=array(
			'ltp'=>true,#Отображать ссылку на пост
			'multi'=>(bool)$mposts,#Включить опции модерирования
			'viewip'=>$Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['viewip']),#Отображать IP
		);
		$posthtml='<div id="posts">';
		$first=$topic['_page']>1 && $links['edit'] ? 0 : key($posts);
		foreach($posts as $id=>$post)
		{
			#Пусть ссылка первого поста темы ведет на редактирование самой темы
			if($id==$first)
				$post['_edit']=$links['edit'];
			$posthtml.=static::Post($id,$post,$attaches,$forum,$ag,$postinfo);
		}

		if(isset($post))
			switch($post['status'])
			{
				case -1:
					$Header->Message(sprintf(static::$lang['psmess-1'],$links['topic']),'info');
				break;
				case 0:
					$Header->Message(sprintf(static::$lang['psmess0'],$links['topic']),'info');
			}
		else
		{
			$post=false;
			if($topic['_filter'])
				$posthtml.='<div class="noposts">'.Eleanor::$Template->Message(static::$lang['noposts'],'info').'</div>';
		}

		$posthtml.='</div>';

		$lastpage=$post && $post['_n']==$topic['_cnt'];
		if($lastpage)
			$posthtml.='<div id="topic-status"></div><div style="text-align:center">'
				.Eleanor::Button(static::$lang['lnp'],'button',array('class'=>'fb-lnp'))
				.'</div>';
		#[E] Генерация списка постов


		$c=(string)$Header;

		if($forum['rules'])
			$c.='<fieldset class="forums"><legend>'.static::$lang['rules'].'</legend>'.$forum['rules'].'</fieldset>';

		$buttons='';
		if($links['new-post'])
			$buttons.='<a href="'.$links['new-post'].'">'.static::$lang['answer'].'</a> | ';
		if($links['delete'])
			$buttons.='<a href="'.$links['delete'].'">'.static::$lang['deletet'].'</a> | ';
		if($links['new-topic'])
			$buttons.='<a href="'.$links['new-topic'].'">'.static::$lang['new-topic'].'</a> | ';
		$buttons=rtrim($buttons,' |');

		if($buttons || isset($topic['_subscription']))
		{
			$c.='<div class="new-buttons">';

			if(isset($topic['_subscription']))
				$c.=Eleanor::Select(false,Eleanor::Option(static::$lang['not-subscribe'],0,!$topic['_subscription'])
						.Eleanor::OptGroup(static::$lang['notify'],Eleanor::Option(static::$lang['immediately'],'i',$topic['_subscription']=='i')
							.Eleanor::Option(static::$lang['daily'],'d',$topic['_subscription']=='d')
							.Eleanor::Option(static::$lang['weekly'],'w',$topic['_subscription']=='w')
							.Eleanor::Option(static::$lang['monthly'],'m',$topic['_subscription']=='m')
						),array('id'=>'topic-subscription','data-t'=>$topic['id'])
					).($buttons ? ' | ' : '');

			$c.=$buttons.'</div>';
		}

		$pages=Eleanor::$Template->Pages($topic['_cnt'],$topic['_pp'],$topic['_pp'],array($links['pages'],$links['first_page']));
		$c.=$pages;

		if($mposts)
			$c.='<form method="posts" id="posts-mm-form" action="'.$links['form_items'].'"><div style="text-align:right;padding-right:4px;">'
				.Eleanor::Check(false,false,array('id'=>'mass-check')).'</div>';

		$c.=$voting.$posthtml.$pages;
		if($info)
		{
			foreach($info as $k=>&$v)
				if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
					$v=static::$lang[$v];

			$c.=Eleanor::$Template->Message($info,'info');
		}

		if($mposts)
			$c.='<fieldset id="posts-mm-panel" class="moderator"><legend>'.static::$lang['moder-posts'].'</legend><span id="with-selected"></span> '
				.Eleanor::Select('mm[do]',$mposts).' '.Eleanor::Button('Ok')
				.($move
					? '<div class="move extra" style="display:none"><ul class="moder"><li>'
						.Eleanor::Input('mm[to]','',array('placeholder'=>static::$lang['moveposts'])).'</li></ul></div>'
					: '')
				.($merge ? '<div class="merge extra" style="display:none"><ul class="moder"><li>'
					.static::$lang['main-author'].Eleanor::Select('mm[author]')
					.'</li></ul></label></div>'
					: '')
				.'</fieldset><script type="text/javascript">/*<![CDATA[*/$(function(){ One2AllCheckboxes("#posts-mm-form","#mass-check","[name=\"mm[p][]\"]:checkbox",true) });//]]></script></form>';

		if($mtopic)
			$c.='<form method="post" action="'.$links['form_items'].'"><fieldset id="topic-mm-panel" class="moderator"><legend>'.static::$lang['moder-topic']
				.'</legend>'.Eleanor::Select('moderate[do]',$mtopic).Eleanor::Button('Ok')
				.($movet
					? '<div class="move extra" style="display:none"><ul class="moder"><li>'
					.Eleanor::Select('moderate[to]', $Forum->Forums->SelectOptions(0,$forum['id'],false))
					.'</li><li><label>'.Eleanor::Check('moderate[link]').static::$lang['leave_link'].'</label></li></ul></div>'
					: '')
				.'</fieldset></form>';

		if($mposts or $mtopic)
			$c.='<div class="clr"></div>';

		if($buttons)
			$c.='<div class="new-buttons">'.$buttons.'</div>';

		#Форма быстрого написания поста
		if($links['new-post'])
		{
			$ti=1;
			$Lst=Eleanor::LoadListTemplate('table-form')
				->form(array('action'=>$links['new-post'],'id'=>'quick-reply'))
				->begin();

			if(!$Forum->user)
				$Lst->item(static::$lang['your-name'],Eleanor::Input('name',$values['name'],array('tabindex'=>$ti++)));

			$Lst->item(static::$lang['text'],$GLOBALS['Eleanor']->Editor->Area('text',$values['text'],array('bb'=>array('tabindex'=>$ti++))));

			if($captcha)
				$Lst->item(static::$lang['enter-captcha'],$captcha.'<br />'.Eleanor::Input('check','',array('tabindex'=>$ti++)));

			$Lst->button(Eleanor::Button(static::$lang['post'],'submit',array('tabindex'=>$ti++)).' '.Eleanor::Button(static::$lang['tofull'],'submit',array('name'=>'_to-full','tabindex'=>$ti++,'class'=>'fb-to-full')))
				->end()
				->endform();

			$c.=Eleanor::$Template->OpenTable()
				.$Lst
				.Eleanor::$Template->CloseTable()
				.($lastpage ? Eleanor::JsVars(array(
					't'=>$topic['id'],
					'ld'=>$post ? $post['sortdate'] : 0,
					'ln'=>$topic['_cnt'],
					'lp'=>true,
					'filter'=>$topic['_filter']
				),true,false,'FORUM.') : '');
		}
		#[E] Форма быстро написания поста

		$statuses='';
		if($rights['_status'])
		{
			if($topic['_statuses'][-1]>0)
				$statuses.=Eleanor::Option(sprintf($rights['_toggle'] ? static::$lang['on_mod%'] : static::$lang['my_on_mod%'],$topic['_statuses'][-1]),-1,isset($fistatus[-1]));
			if($topic['_statuses'][1]>0 and ($topic['_statuses'][-1]>0 or $topic['_statuses'][0]>0))
				$statuses.=Eleanor::Option(sprintf(static::$lang['active%'],$topic['_statuses'][1]),1,isset($fistatus[1]));
			if($topic['_statuses'][0]>0)
				$statuses.=Eleanor::Option(sprintf(static::$lang['blocked%'],$topic['_statuses'][0]),0,isset($fistatus[0]));
			if($topic['_statuses'][0]>0 or $topic['_statuses'][-1]>0)
			{
				$st=$rights['_toggle'] ? '-1,0,1' : '-1,1';
				$statuses.=Eleanor::Option(sprintf(static::$lang['all%'],array_sum($topic['_statuses'])),$st,join(',',$fistatus)==$st);
			}
		}

		$c.='<br /><form method="post" action="'.$links['form_items'].'"><fieldset class="forums"><legend>'.static::$lang['filter'].'</legend>'
			.($statuses ? static::$lang['with_status:'].Eleanor::Select('fi[status]',$statuses).' | ' : '')
			.'<label>'.Eleanor::Check('fi[my]',!empty($forum['_filter']['my'])).static::$lang['only_my'].'</label><div style="text-align:center">'
			.Eleanor::Button(static::$lang['apply-filter']).'</div></fieldset></form>';

		list($u,$g,$b,$h,$onforum)=static::Online($online);
		$whoon=static::$lang['whoon'];
		$herenow=static::$lang['here-now'];

		$c.='<br /><table class="tabstyle">
	<tr>
		<td>'.$herenow($g,$u,$h,$b).$whoon($g,$u,$h,$b)
			.($onforum ? ': '.rtrim($onforum,', ') : '').'</td>
	</tr></table>';

		return$c.($links['cron'] ? '<script type="text/javascript">/*<![CDATA[*/ $(function(){ FORUM.Cron("'.$links['cron'].'") }) //]]></script>' : '');
	}

	/**
	 * Отображение конкретного поста
	 * @param $forum
	 * @param $rights
	 * @param $topic
	 * @param $post
	 * @param $attaches
	 * @param $ag
	 * @param $online
	 * @param $links
	 * @return string
	 */
	public static function ShowPost($forum,$rights,$topic,$post,$attaches,$ag,$online,$links,$ajax)
	{
		$Forum = $GLOBALS['Eleanor']->Forum;

		#Сам пост
		$posthtml='<div id="post-buttons">'
			.($links['prev'] ? '<a href="'.$links['prev'].'" class="fb-prev-post" data-id="'.$post['_prev'].'" title="'.static::$lang['prev'].'">&laquo;&laquo;</a> ' : '&laquo;&laquo; ')
			.sprintf(static::$lang['post-from'],$post['_n'],$topic['_cnt'])
			.($links['next'] ? ' <a href="'.$links['next'].'" class="fb-next-post" data-id="'.$post['_next'].'" title="'.static::$lang['next'].'">&raquo;&raquo;</a>' : ' &raquo;&raquo;')
			.($links['activate'] ? '<br /><a href="'.$links['activated'].'">'.static::$lang['activate'].'</a>' : '')
			.'</div>'
			.static::Post($post['id'],$post,$attaches,$forum,$ag,array(
				'ltp'=>false,#Отображать ссылку на пост
				'multi'=>false,#Включить опции модерирования
				'viewip'=>$Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['viewip']),#Отображать IP
			));
		#[E] Сам пост
		if($ajax)
			return$posthtml;

		#Шапка
		$nav=array();
		$forum['parents']=explode(',',$forum['parents']);
		foreach($forum['parents'] as $p)
			if(isset($Forum->Forums->dump[$p]))
				$nav[]=array($Forum->Links->Forum($p), $Forum->Forums->dump[$p]['title']);
		array_push($nav,array($Forum->Links->Forum($forum['id']),$forum['title']),array($topic['_a'],$topic['title']));

		$Header=Eleanor::$Template->ForumMenu($nav,array(array($links['rss'],'RSS '.$topic['title'])));

		switch($post['status'])
		{
			case -1:
				$Header->Message(static::$lang['pmess-1'],'info');
			break;
			case 0:
				$Header->Message(static::$lang['pmess0'],'info');
		}
		#[E]Шапка

		$c=(string)$Header;

		$buttons='';
		if($links['new-post'])
			$buttons.='<a href="'.$links['new-post'].'">'.static::$lang['answer'].'</a> | ';
		if($links['new-topic'])
			$buttons.='<a href="'.$links['new-topic'].'">'.static::$lang['new-topic'].'</a> | ';
		$buttons=rtrim($buttons,' |');

		if($buttons)
			$c.='<div class="new-buttons">'.$buttons.'</div>';

		$c.='<div id="posts">'.$posthtml.'</div>';

		list($u,$g,$b,$h,$onforum)=static::Online($online);
		$whoon=static::$lang['whoon'];
		$herenow=static::$lang['here-now-post'];

		$c.='<br /><table class="tabstyle">
	<tr>
		<td>'.$herenow($g,$u,$h,$b).$whoon($g,$u,$h,$b)
			.($onforum ? ': '.rtrim($onforum,', ') : '').'</td>
	</tr></table>';

		return$c;
	}

	/**
	 * Дозагрузка постов на ajax
	 * @param $forum
	 * @param $rights
	 * @param $topic
	 * @param $posts
	 * @param $attaches
	 * @param $ag
	 * @return string
	 */
	public static function AjaxLoadNewPosts($forum,$rights,$topic,$posts,$attaches,$ag)
	{
		$Forum = $GLOBALS['Eleanor']->Forum;

		$info=array(
			#Отображать ссылку на пост
			'ltp'=>true,
			#Включить опции модерирования
			'multi'=>$Forum->ugr['supermod'] || in_array(1,$forum['_moderator']['mchstatus']) || in_array(1,$forum['_moderator']['delete'])
			|| in_array(1,$forum['_moderator']['move']) || in_array(1,$forum['_moderator']['merge']),
			#Отображать IP
			'viewip'=>$Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['viewip']),
		);
		$posthtml='';
		foreach($posts as $id=>$post)
			$posthtml.=static::Post($id,$post,$attaches,$forum,$ag,$info);

		return$posthtml;
	}

	/**
	 * Рендеринг конкретного поста
	 * @param $id
	 * @param $post
	 * @param $attaches
	 * @param $forum
	 * @param $ag
	 * @param $info
	 * @return string
	 */
	protected static function Post($id,$post,$attaches,$forum,$ag,$info)
	{
		$Forum=$GLOBALS['Eleanor']->Forum;
		$author=$post['author_id'] && isset($ag[0][ $post['author_id'] ]) ? $ag[0][ $post['author_id'] ] : false;

		if($author)
			switch($author['avatar_type'])
			{
				case'local':
					$avatar='images/avatars/'.$author['avatar_location'];
				break;
				case'upload':
					$avatar=Eleanor::$uploads.'/avatars/'.$author['avatar_location'];
				break;
				case'url':
					$avatar=$author['avatar_location'];
				break;
				default:
					$avatar='images/avatars/user.png';
			}
		else
			$avatar='images/avatars/guest.png';

		$buttons='';
		if($post['_edit'])
			$buttons.='<a href="'.$post['_edit'].'">'.static::$lang['edit'].'</a> | <a href="#" class="fb-quick-edit" data-id="'.$id.'">'.static::$lang['quick-edit'].'</a> | ';
		if($post['_delete'])
			$buttons.='<a href="#" class="fb-delete-post" data-id="'.$id.'">'.static::$lang['delete'].'</a> | ';
		if($post['_answer'])
			$buttons.='<a href="'.$post['_answer'].'">'.static::$lang['answer'].'</a> | ';
		if($post['_answer'])
		{
			$buttons.='<a href="#" class="fb-quote" data-id="'.$id.'">'.static::$lang['quote'].'</a> | ';
			if($info['ltp'])
				$buttons.='<a href="#" class="fb-quick-quote" data-id="'.$id.'" data-date="'.$post['created'].'" data-name="'.$post['author'].'">'.static::$lang['quick-quote'].'</a> | ';
		}

		if($post['edited_by'])
		{
			$edlang=static::$lang['edited'];
			$editor=$post['edited_by_id'] && isset($ag[0][ $post['edited_by_id'] ]) ? $ag[0][ $post['edited_by_id'] ] : false;
			$edited=$edlang($post['edited'],$editor ? $editor['name'] : $post['edited_by'],$editor ? Eleanor::$Login->UserLink($editor['name'],$post['edited_by_id']) : false,$post['edit_reason']);
		}
		elseif((int)$post['updated']>0)
		{
			$edlang=static::$lang['updated'];
			$edited=$edlang($post['updated']);
		}
		else
			$edited=false;

		$reject=$approve='';
		foreach($post['_approved'] as $v)
		{
			$from=isset($ag[0][ $v['from'] ]) ? $ag[0][ $v['from'] ] : false;
			$name=htmlspecialchars($from ? $from['name'] : $v['from_name'],ELENT,CHARSET);
			$pref=$from && isset($ag[1][ $from['_group'] ]) ? $ag[1][ $from['_group'] ]['html_pref'] : false;
			$end=$pref===false ? false : $ag[1][ $from['_group'] ]['html_end'];

			$approve.=$from ? '<a href="'.$from['_a'].'">'.$pref.$name.$end.'</a>, ' : $name.', ';
		}
		foreach($post['_rejected'] as $v)
		{
			$from=isset($ag[0][ $v['from'] ]) ? $ag[0][ $v['from'] ] : false;
			$name=htmlspecialchars($from ? $from['name'] : $v['from_name'],ELENT,CHARSET);
			$pref=$from && isset($ag[1][ $from['_group'] ]) ? $ag[1][ $from['_group'] ]['html_pref'] : false;
			$end=$pref===false ? false : $ag[1][ $from['_group'] ]['html_end'];

			$reject.=$from ? '<a href="'.$from['_a'].'">'.$pref.$name.$end.'</a>, ' : $name.', ';
		}

		$atts=$attsi='';
		$langdown=static::$lang['downloaded'];
		foreach($post['_attached'] as $v)
			if(isset($attaches[$v]) and !in_array($v,$post['_attaches']))
			{
				$attach=$attaches[$v];

				if(preg_match('#\.(jpe?g|bmp|gif|png)$#i',$attach['name'])>0)
				{
					$attsi.='<li><a href="'.$attach['_a'].'" target="_blank" class="gallery" title="'.$attach['name']
						.'"><img src="'.$attach['_a'].'" /></a><br /><span>'
						.Files::BytesToSize($attach['size']).' - '.$langdown($attach['downloads']).'</span></li>';

					if(!isset($GLOBALS['head']['colorbox']))
					{
						$GLOBALS['jscripts'][]='addons/colorbox/jquery.colorbox-min.js';
						$GLOBALS['head']['colorbox']='<link rel="stylesheet" media="screen" href="addons/colorbox/colorbox.css" />';
						$GLOBALS['head'][]='<script type="text/javascript">//<![CDATA[
$(function(){
	var F=function(){
		$("a.gallery").colorbox({
			title: function(){
				var url=$(this).attr("href"),
					title=$(this).find("img").attr("title");
				return "<a href=\""+url+"\" target=\"_blank\">"+(title ? title : url)+"</a>";
			},
			maxWidth:Math.round(screen.width/1.5),
			maxHeight:Math.round(screen.height/1.5)
		});
	}
	if(CORE.in_ajax.length)
		CORE.after_ajax.push(F);
	else
		F();
});//]]></script>';
				}
			}
			else
				$atts.='<li><a href="'.$attach['_a'].'" target="_blank">'.$attach['name'].'</a><span> '
					.Files::BytesToSize($attach['size']).' - '.$langdown($attach['downloads'])
					.'</span></li>';
		}

		$name=htmlspecialchars($author ? $author['name'] : $post['author'],ELENT,CHARSET);
		$group=$author && isset($ag[1][ $author['_group'] ]) ? $ag[1][ $author['_group'] ] : false;
		$pref=$group ? $group['html_pref'] : '';
		$end=$group ? $group['html_end'] : '';

		#Важно! У каждого контейнера поста должен быть id=postID, .post и data-id=ID
		return'<table class="tabstyle post" id="post'.$id.'" data-id="'.$id.'"><tr><th><a href="#" class="fb-insert-nick"'
			.($author ? ' data-id="'.$post['author_id'].'">'.$name.'</a>'.($author['_online'] ? ' <span style="color:green">'.static::$lang['online'].'</span>' : '') : '>'.$name.'</a>').'</th><td>'
			.($info['ltp'] ? '#<a href="'.$post['_ap'].'">'.$post['_n'].'</a> ' : '')
			.'<span class="small">'.static::$lang['from'].'</span> '.Eleanor::$Language->Date($post['created'],'fdt')
			.' <a href="'.$post['_atp'].'" class="small fb-ltp">'.static::$lang['link'].'</a>'
			.($info['viewip'] ? '<a class="ip" href="http://eleanor-cms.ru/whois/'.$post['ip'].'" target="_blank">'.$post['ip'].'</a>' : '')
			.'</td>'
			.($info['multi'] ? '<td style="width:1%">'.Eleanor::Check('mm[p][]',$post['_checked'],array('value'=>$id)).'</td>' : '')
			.'</tr><tr class="main"><td class="author"><div><img src="'.$avatar.'" alt="'.$name.'" />'.($author && $author['statustext'] ? '<br />'.$author['statustext'] : '').'</div>'
			.($author
				? '<a href="'.$author['_a'].'">'.static::$lang['profile'].'</a>'
					.($group ? '<br />'.sprintf(static::$lang['group%'],'<a href="'.$group['_a'].'">'.$pref.$group['title'].$end.'</a>') : '')
					.'<br />'.sprintf(static::$lang['posts%'],'<span class="user-posts-'.$post['author_id'].'">'.$author['posts'].'</span').'><br />'.sprintf(static::$lang['register%'],Eleanor::$Language->Date($author['register'],'fd'))
					.'<br />'.($author['location'] ? sprintf(static::$lang['from%'],$author['location']).'<br />' : '')
					.'<br />'.sprintf(static::$lang['repa%'],($post['_r-'] ? '<a href="#" class="fb-minus" data-id="'.$id.'">&minus</a>' : '&minus;')
						.' <a href="'.$author['_afrep'].'" class="user-rep-f'.$forum['id'].'-'.$post['author_id'].'">'
						.($author['rep']===false ? static::$lang['rno'] : $author['rep']).'</a>'
						.($post['_r+'] ? '<a href="#" class="fb-plus" data-id="'.$id.'">+</a>' : '+'))
					.'<br />'.sprintf(static::$lang['total%'],'<a href="'.$author['_arep'].'">'.($author['reputation']===false ? static::$lang['rno'] : $author['reputation']).'</a>')
				: '')
			.'</td><td'.($info['multi'] ? ' colspan="2"' : '').'><div class="text">'.$post['text'].'</div>'
			.($author && $author['signature'] ? '<div class="small signature">----<br />'.$author['signature'].'</div>' : '')
			.'<div class="small edited"'.($edited ? '' : ' style="display:none"').'>'.$edited.'</div>'
			.($attsi ? '<div class="images clr">'.static::$lang['attached-images'].'<ul>'.$attsi.'</ul></div>' : '')
			.($atts ? '<div class="files clr">'.static::$lang['attached-files'].'<ul>'.$atts.'</ul></div>' : '')
			.'<div class="approved"'.($approve ? '><span style="color:green">'.static::$lang['approved'].'</span>'.rtrim($approve,', ') : ' style="display:none">').'</div>'
			.'<div class="rejected"'.($approve ? '><span style="color:red">'.static::$lang['rejected'].'</span>'.rtrim($reject,', ') : ' style="display:none">')
			.'</div></td></tr><tr><td>'.($post['_r+'] ? '<a href="#" class="fb-thanks" data-id="'.$id.'">'.static::$lang['thanks'].'</a>' : '')
			.'</td><td'.($info['multi'] ? ' colspan="2"' : '').'><span style="float:right">'.rtrim($buttons,'| ').'</span></td></tr>
		</table>';
	}

	protected static function Online($online)
	{
		$ltpl=Eleanor::$Language['tpl'];
		$u=$g=$b=$h=0;
		$onforum='';

		$t=time();
		$groups=array();
		foreach($online as $v)
		{
			$et=floor(($t-$v['enter'])/60);
			if($v['user_id']>0)
			{
				if(isset($groups[ $v['group'] ]))
					list($pref,$end)=$groups[ $v['group'] ];
				else
				{
					$pref=join(Eleanor::Permissions(array($v['group']),'html_pref'));
					$end=join(Eleanor::Permissions(array($v['group']),'html_end'));
					$groups[ $v['group'] ]=array($pref,$end);
				}
				if($v['_hidden'])
				{
					$h++;
					if($GLOBALS['Eleanor']->Forum->ugr['shu'])
						$end.='*';
					else
						continue;
				}
				else
					$u++;

				$onforum.='<a href="'.$v['_a'].'" title="'.$ltpl['minutes_ago']($et).'">'.$pref.htmlspecialchars($v['name'],ELENT,CHARSET).$end.'</a>, ';
			}
			elseif($v['name'] and Eleanor::$vars['bots_enable'])
			{
				$onforum.='<span title="'.$ltpl['minutes_ago']($et).'">'.$v['_pref'].$v['botname'].$v['_end'].'</span>, ';
				$b++;
			}
			else
				$g++;
		}
		return array($u,$g,$b,$h,$onforum);
	}
}
TplForumTopic::$lang=Eleanor::$Language->Load(dirname(__DIR__).'/langs/forum-user-topic-*.php',false);