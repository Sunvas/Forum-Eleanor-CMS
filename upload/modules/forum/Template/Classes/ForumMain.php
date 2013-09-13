<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
class TplForumMain
{
	public static
		$lang=array();

	/**
	 * Главная страница форума
	 * @param array|false $forums Форумы, ключи:
	 *   array forums Форумы, не объединенные в категории, формат: id=>array, ключи внутреннего масива:
	 *     string language язык форума
	 *     string title название форума
	 *     bool _read форум прочитан
	 *     string _a ссылка на форум
	 *     string _amwt ссылка на модерирование ожидающих тем (иногда доступно)
	 *     string _amwp ссылка на модерирование ожидающих постов (иногда доступно)
	 *     bool _topics флаг доступа к просмотру тем (если доступа к просмотру тем нет, то ключи _alpa, _anp, _alp не доступны)
	 *     string _alpa ссылка на профиль пользователя, оставившего последний пост
	 *     string _anp ссылка на последний непрочтенный пост, последней обновленной темы
	 *     string _alp ссылка на последний пост, последней обновленной темы
	 *     array moderators
	 *       int _group (только для пользователей)
	 *       bool _me это я, или моя группа
	 *       string _a ссылка на пользователя или группу
	 *       string title (только для групп)
	 *     array subforums подфорумы текущего форума, формат: id=>array ключи внутреннего массива:
	 *       string language язык форума
	 *       string title название форума
	 *       string _a ссылка на форум
	 *   array categories Категории с форумами, формат: id=>array, ключи внутреннего массива:
	 *     string language язык категории
	 *     string title название категори
	 *     string _a ссылка на категорию
	 *     array forums Форумы категории с ключами, идентичными ключам форумов, описанных выше
	 * @param array $stats Количественные показатели форума, ключи массива:
	 *   int topics Количество тем на форуме
	 *   int posts Количество постов на форуме
	 *   int users Количество пользователей на форуме
	 * @param array $online Список пользователей, находящихся сейчас на форуме
	 *   int user_id ID пользователя, если 0, значит это либо гость, либо бот
	 *   timestamp enter Дата входа
	 *   string name Имя пользователя
	 *   int group Группа пользователя (только для пользователя)
	 *   bool _hidden является ли пользователь скрытым
	 *   string botname Имя бота
	 *   string _a Ссылка на пользователя (только для пользователя)
	 */
	public static function ForumMain($forums,array$stats,array$online)
	{
		$images=Eleanor::$Template->default['theme'].'images/';
		list($u,$g,$b,$h,$onforum)=static::Online($online);

		$whoon=static::$lang['whoon'];
		$tau=static::$lang['tau'];

		return Eleanor::$Template->ForumMenu()
			.($forums===false ? Eleanor::$Template->Message(static::$lang['inaccessible'],'info') : static::DrawCategories($forums))
			.'<div class="all-read"><a href="#" id="all-read">'.static::$lang['challread'].'</a></div>
<div class="fcathead">
	<a href="'.$GLOBALS['Eleanor']->module['links']['stats'].'">'.static::$lang['stats'].'</a>
	<a href="#" data-id="st" class="toggle"><img src="'.$images.'minus.gif" data-src="'.$images.'plus.gif" /></a>
</div>
<table class="tabstyle" id="fcst">
	<tr>
		<td class="img"><img src="images/categories/spy.png" /></td>
		<td>'.static::$lang['nowonforum'].$whoon($g,$u,$h,$b).($onforum ? ': '.rtrim($onforum,', ') : '').'</td>
	</tr>
	<tr>
		<td class="img"><img src="images/categories/cookies.png" /></td>
		<td>
			<b>'.static::$lang['quantity'].'</b><br />'.$tau($stats['topics'],$stats['posts']-$stats['topics'],$stats['users']).'</td>
	</tr>
</table>';
	}

	/**
	 * Страница конкретного форума, либо конкретной категории
	 * @param array $forum Форум, либо катеория, ключи:
	 *   bool _trash Является ли форум мусорником
	 *   bool _read Форум прочтен
	 *   array prefixes Префиксы тем, формат: id=>array. Ключи внутреннего массива:
	 *     _cnt Число тем с этим префиксом с учетом фильтров
	 *     _a Ссылка на фильтр тем по префиксу
	 *     title Название фильтра
	 *   array _topics Список тем, формат: id=>array. Ключи внутреннего массива:
	 *     string lp_date Дата последнего поста
	 *     string lp_author Имя автора последнего поста
	 *     int lp_author_id ID автора последнего поста
	 *     int voting Идентификатор голосования, если равно 0, значит голосования в теме нет
	 *     string state Состояние темы: moved - перемещена, merged - склеена, closed - закрыта, open - открыта (нормальное состояние)
	 *     string _a Ссылка на тему
	 *     string|false _twp Ссылка на ожидающие посты темы
	 *     string _alp Ссылка на последний пост темы
	 *     string _anp Ссылка на последний непрочтенный пост темы
	 *     bool _wmp Флаг присутствия в темы постов пользователя
	 *     bool _checked Флаг отмеченности темы (для мультимодерации)
	 *     int status Статус темы
	 *     string created Дата создания темы
	 *     string author Имя автора темы
	 *     int author_id ID Автора темы
	 *     int moved_to_forum ID Форума, куда перемещна тема
	 *     string who_moved Имя пользователя, переместившего тему
	 *     int who_moved_id ID пользователя, переместившего тему
	 *     string when_moved Дата перемещения темы
	 *     string title Название темы
	 *     string description Описание темы
	 *     int posts Число постов
	 *     int queued_posts Число постов, ожидающих проверки
	 *     int views Число просмотров темы
	 *     bool _pin флаг Зафиксированной темы
	 *   array _filter Значение фильтров тем, ключи:
	 *      array status Числовые идентификаторы отображаемых тем (-1 для ожидающих, 0 для заблокированных, 1 для активных)
	 *   array _moved'=>array(),#Массив дампов форумов, куда перемещаются темы
	 *   int _cnt Всего тем
	 *   int _page Текущая страница, если нужно скакнуть на последнюю - пишем 0
	 *   int _pages Количество страниц dctuj
	 *   array _authors Информация об авторах, формат: id=>array. Ключи внутреннего массива:
	 *     string name Имя пользователя
	 *     string full_name Полное имя пользователя
	 *     string _a Ссылка на пользователя
	 *   array _statuses Число тем каждого статус, формат статус=>число тем
	 * @param array $rights наши права на форуме, описание прав дано в классе ForumForums +
	 *   bool _status Возможность просматривать свои темы с разными статусами
	 *   bool _toggle Возможность просматривать чужие темы с разными статусами и менять эти статусы (модератор)
	 * @param array|false $forums подфорумы, содержимое массива идентично одноименному массиву метода ForumMain
	 * @param array $online Список пользователей, просматривающих форум, содержимое массива идентично одноименному массиву метода ForumMain
	 * @param array $errors Ошибки
	 * @param array $info Информационные сообщения
	 * @param array $links ссылки, ключи:
	 *   string rss_topics Ссылка на RSS поток тем форума (только для форума)
	 *   string rss_posts Ссылка на RSS потом постов форума (только для форума)
	 *   string first_page Ссылка на первую страницу форума (только для форума)
	 *   string form_items Ссылка для формы управления темами (только для форума)
	 *   callback pages Функция генератор страниц для пагинатора (только для форума)
	 *   string|false new-topic Ссылка на форуму создания новой темы на форуме (только для форума)
	 *   string|false wait-topics Ссылка на ожидающие модерации топики (возможно только пользователя)
	 */
	public static function ShowForum(array$forum,array$rights,$forums,array$online,array$errors,$info,array$links)
	{
		$nav=$rss=array();
		$forum['parents']=explode(',',$forum['parents']);
		$Forum = $GLOBALS['Eleanor']->Forum;
		foreach($forum['parents'] as $v)
			if(isset($Forum->Forums->dump[$v]))
				$nav[]=array($Forum->Links->Forum($v), $Forum->Forums->dump[$v]['title']);
		$nav[]=array(false,$forum['title']);

		if(isset($links['rss_topics']))
			$rss[]=array($links['rss_topics'],sprintf(static::$lang['rss_topics'],$forum['title']));
		if(isset($links['rss_posts']))
			$rss[]=array($links['rss_posts'],sprintf(static::$lang['rss_posts'],$forum['title']));
		$c=Eleanor::$Template->ForumMenu($nav,$rss);

		if($forum['rules'])
			$c.='<fieldset class="forums"><legend>'.static::$lang['rules'].'</legend>'.$forum['rules'].'</fieldset>';

		if($errors)
		{
			foreach($errors as $k=>&$v)
				if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
					$v=static::$lang[$v];

			$c.=Eleanor::$Template->Message($errors,'error');
		}
		if($forums)
			$c.=static::DrawCategories($forums,!$forum['is_category']);

		if($forum['is_category'])
			return$c;

		$fistatus=isset($forum['_filter']['status']) ? $forum['_filter']['status'] : array(1=>1);
		if($links['wait-topics'] and !isset($fistatus[-1]))
		{
			$lang=$rights['_toggle'] ? static::$lang['wait-moder'] : static::$lang['wait-my'];
			$c.=Eleanor::$Template->Message($lang($links['wait-topics'],$forum['_statuses'][-1]),'info');
		}

		if(!isset($forum['_topics']))
		{
			if(!$forum['_trash'] and $links['new-topic'])
				$c.='<div class="new-buttons"><a href="'.$links['new-topic'].'">'.static::$lang['new-topic'].'</a></div>';
			return $c.Eleanor::$Template->Message(static::$lang['nort'],'info');
		}

		#Наличие права перемещения тем; наличия права склейки тем
		$move=$merge=false;
		$moder=$forum['_cnt']>0 ? $forum['_moderator'] || $Forum->ugr['supermod'] : false;

		if($moder)
		{
			$GLOBALS['jscripts'][]='js/checkboxes.js';
			$moder='';

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['mchstatust']))
				$moder.=Eleanor::OptGroup(static::$lang['chst'],
					($fistatus==array(-1=>-1) ? '' : Eleanor::Option(static::$lang['onmod'],'status=-1'))
					.($fistatus==array(0) ? '' : Eleanor::Option(static::$lang['hided'],'status=0'))
					.($fistatus==array(1=>1) ? '' : Eleanor::Option(static::$lang['active'],'status=1'))
				);

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['mdeletet']))
				$moder.=Eleanor::Option(static::$lang['delete'],'delete');

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['mmovet']))
			{
				$moder.=Eleanor::Option(static::$lang['move'],'move');
				$move=true;
			}

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['mopcl']))
				$moder.=Eleanor::Option(static::$lang['open'],'open').Eleanor::Option(static::$lang['close'],'close');

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['mpin']))
				$moder.=Eleanor::Option(static::$lang['pin'],'pin').Eleanor::Option(static::$lang['unpin'],'unpin');

			if($Forum->ugr['supermod'] or in_array(1,$forum['_moderator']['merget']))
			{
				$moder.=Eleanor::Option(static::$lang['merge'],'merge');
				$merge=true;
			}
		}

		$moders='';
		foreach($forum['moderators'] as $fm)
		{
			if(isset($fm['title']))
				$moders.=sprintf(static::$lang['group:'],'<a href="'.$fm['_a'].'">'.$fm['html_pref'].$fm['title_l'].$fm['html_end'].'</a>, ');
			else
			{
				if(!$fm['_group'])
					$pr=$en='';
				else
				{
					$pr=join(Eleanor::Permissions(array($fm['_group']),'html_pref'));
					$en=join(Eleanor::Permissions(array($fm['_group']),'html_end'));
				}
				$moders.='<a href="'.$fm['_a'].'" title="'.$fm['full_name'].'">'.$pr.htmlspecialchars($fm['name'],ELENT,CHARSET).$en.'</a>, ';
			}
		}
		if($moders)
			$c.='<div class="moderators">'.sprintf(static::$lang['moders:'],rtrim($moders,', ')).'</div>';

		if(!$forum['_trash'])
			$c.='<div class="new-buttons">'
				.($Forum->user ? Eleanor::Select(false,Eleanor::Option(static::$lang['not-subscribe'],0,!$forum['_subscription']).Eleanor::OptGroup(static::$lang['notify'],Eleanor::Option(static::$lang['immediately'],'i',$forum['_subscription']=='i').Eleanor::Option(static::$lang['daily'],'d',$forum['_subscription']=='d').Eleanor::Option(static::$lang['weekly'],'w',$forum['_subscription']=='w').Eleanor::Option(static::$lang['monthly'],'m',$forum['_subscription']=='m')),array('id'=>'forum-subscription','data-f'=>$forum['id'],'data-l'=>$forum['language'])).' ' : '')
				.($links['new-topic'] ? '<a href="'.$links['new-topic'].'">'.static::$lang['new-topic'].'</a>' : '')
				.'</div>';

		if($forum['prefixes'])
		{
			$c.='<div class="prefixes">';
			$pref=isset($forum['_filter']['prefix']) ? $forum['_filter']['prefix'] : 0;
			if($pref>0)
				$c.='<span><a href="'.$links['form_items'].'">'.static::$lang['all-topics'].'</a></span>';
			foreach($forum['prefixes'] as $k=>$v)
				$c.=$k==$pref ? '<span>'.$v['title'].' ('.$v['_cnt'].')</span>' : '<span><a href="'.$v['_a'].'">'.$v['title'].'</a> ('.$v['_cnt'].')</span>';
			$c.='</div>';
		}

		$Lst=Eleanor::LoadListTemplate('table-list',$moder ? 6 : 5);

		if($moder)
			$Lst->form(array(
				'data-trash'=>$forum['_trash'] ? 1 : false,
				'data-f'=>$forum['id'],
				'id'=>'topics-mm-form',
				'action'=>$links['form_items'],
			));
		$Lst->begin(
				array(static::$lang['topic'],'colspan'=>2,'tableextra'=>array('id'=>'topics','')),
				array(static::$lang['views'],70),
				array(static::$lang['answers'],70),
				array(static::$lang['lastpost'],150),
				$moder ? array(Eleanor::Check(false,false,array('id'=>'mass-check','value'=>false)),20) : false
			);

		if($forum['_cnt']==0)
			$Lst->empty(static::$lang['tnf']);
		else
		{
			foreach($forum['_topics'] as $k=>$v)
			{
				$moved='';
				switch($v['state'])
				{
					case'closed':
						$img='<img src="images/forum/closed.gif" alt="'.static::$lang['closed'].'" title="'.static::$lang['closed'].'" />';
					break;
					case'moved':
						$img='<img src="images/forum/moved.gif" alt="'.static::$lang['moved'].'" title="'.static::$lang['moved'].'" />';
						$moved='<br />'.static::$lang['moved'].' '.($forum['_moved'][ $v['moved_to_forum'] ] ? ' &rarr; <a href="'.UrlForum($v['moved_to_forum']).'">'.$forum['_moved'][ $v['moved_to_forum'] ]['title'].'</a> '.Eleanor::$Language->Date($v['when_moved'],'fdt') : '');
					break;
					case'merged':
						$img='<img src="images/forum/merged.gif" alt="'.static::$lang['merged'].'" title="'.static::$lang['merged'].'" />';
					break;
					default:
						$img='<img'.($v['_read'] ? ' title="'.static::$lang['tread'].'" class="topicimg read"' : ' title="'.static::$lang['hasnp'].'" alt="'.static::$lang['hasnp'].'" class="topicimg new" data-t="'.$k.'"').' src="images/forum/'
							.($v['_wmp'] ? 'mytopic.gif' : 'topic.gif').'"  />';
				}

				$Lst->item(
					array($img,'class'=>'img'),
					($v['_anp'] ? '<a href="'.$v['_anp'].'" title="'.static::$lang['gnp'].'" class="get-new-post"><img src="images/forum/newpost.gif" alt="" /></a> ' : '')
					.($v['status']==-1 ? static::$lang['waits'].' ' : '')
					.($v['_pin'] ? static::$lang['imp'].' ' : '')
					.($v['voting'] ? static::$lang['voting'].' ' : '')
					.(isset($forum['prefixes'][ $v['prefix'] ]) ? '<a class="prefix" href="'.$forum['prefixes'][ $v['prefix'] ]['_a'].'">'.$forum['prefixes'][ $v['prefix'] ]['title'].'</a> ' : '')
					.'<a href="'.$v['_a'].'" class="topic" data-t="'.$k.'">'.$v['title'].'</a>'.($v['description'] ? '<br /><span class="small">'.$v['description'].'</span>' : '')
					.$moved,
					array($v['views'],'center'),
					array($v['posts'].($v['_twp'] ? ' | <a href="'.$v['_twp'].'" title="'.static::$lang['twp'].'">'.$v['queued_posts'].'</a>' : ''),'center'),
					'<a href="'.$v['_alp'].'" title="'.static::$lang['glp'].'"><img src="images/forum/lastpost.png" /></a> '.Eleanor::$Language->Date($v['lp_date'],'fdt').'<br /><b>'.static::$lang['author:'].'</b> '
					.(isset($forum['_authors'][ $v['lp_author_id'] ]) ? '<a href="'.$forum['_authors'][ $v['lp_author_id'] ]['_a'].'">'.$v['lp_author'].'</a>' : $v['lp_author']).'',
					$moder ? Eleanor::Check('mm[t][]',$v['_checked'],array('value'=>$k,'class'=>$v['state'].' '.($v['_pin'] ? 'pinned' : 'unpinned'))) : false
				);
			}
		}
		$c.=$Lst->end()
			.Eleanor::$Template->Pages($forum['_cnt'], $Forum->vars['tpp'],$forum['_page'],array($links['pages'],$forum['_pages']=>$links['first_page']));

		if($links['new-topic'])
			$c.='<div class="new-buttons"><a href="'.$links['new-topic'].'">'.static::$lang['new-topic'].'</a></div>';

		if(!$forum['_read'])
			$c.='<div class="forum-read"><a href="#" id="forum-read" data-f="'.$forum['id'].'">'.static::$lang['mfr'].'</a></div>';

		if($moder)
		{
			if($info)
			{
				foreach($info as $k=>&$v)
					if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
						$v=static::$lang[$v];

				$c.=Eleanor::$Template->Message($info,'info');
			}

			$c.='<fieldset id="topics-mm-panel" class="moderator"><legend>'.static::$lang['moder-topics'].'</legend><span id="with-selected"></span> '
				.Eleanor::Select('mm[do]',$moder).' '.Eleanor::Button('Ok')
				.($move ? '<div class="move extra" style="display:none"><ul class="moder"><li>'
						.Eleanor::Select('mm[to]', $Forum->Forums->SelectOptions(0,$forum['id'],false))
						.'</li><li><label>'.Eleanor::Check('mm[link]').static::$lang['leave_link'].'</label></li></ul></div>'
					: '')
				.($merge ? '<div class="merge extra" style="display:none"><ul class="moder"><li>'.static::$lang['main_topic'].Eleanor::Select('mm[main]',Eleanor::Option(static::$lang['other_topic'],0))
						.'</li><li id="merge-other">'.Eleanor::Input('mm[to]','',array('placeholder'=>static::$lang['id_or_url_ot']))
						.'</li><li><label>'.Eleanor::Check('mm[link]').static::$lang['leave_link'].'</li></ul></label></div>'
					: '')
				.'</fieldset></form><div class="clr"></div>';
		}

		$ld=isset($forum['_filter']['ld']) ? $forum['_filter']['ld'] : 0;
		$lds='';
		foreach(array(
			static::$lang['ld_all'],
			30=>static::$lang['ld_30'],
			60=>static::$lang['ld_60'],
			90=>static::$lang['ld_90'],
		) as $ldk=>$ldv)
			$lds.=Eleanor::Option($ldv,$ldk,$ldk==$ld);

		$statuses='';
		if($rights['_status'])
		{
			if($forum['_statuses'][-1]>0)
				$statuses.=Eleanor::Option(sprintf($forum['_toggle'] ? static::$lang['on_mod%'] : static::$lang['my_on_mod%'],$forum['_statuses'][-1]),-1,isset($fistatus[-1]));
			if($forum['_statuses'][1]>0 and ($forum['_statuses'][-1]>0 or $forum['_statuses'][0]>0))
				$statuses.=Eleanor::Option(sprintf(static::$lang['active%'],$forum['_statuses'][1]),1,isset($fistatus[1]));
			if($forum['_statuses'][0]>0)
				$statuses.=Eleanor::Option(sprintf(static::$lang['blocked%'],$forum['_statuses'][0]),0,isset($fistatus[0]));
			if($forum['_statuses'][0]>0 or $forum['_statuses'][-1]>0)
			{
				$st=$rights['_toggle'] ? '-1,0,1' : '-1,1';
				$statuses.=Eleanor::Option(sprintf(static::$lang['all%'],array_sum($forum['_statuses'])),$st,join(',',$fistatus)==$st);
			}
		}

		$c.='<br /><form method="post" action="'.$links['form_items'].'"><fieldset class="forums"><legend>'.static::$lang['filter'].'</legend>'
			.($statuses ? static::$lang['with_status:'].Eleanor::Select('fi[status]',$statuses).' | ' : '')
			.static::$lang['for:'].Eleanor::Select('fi[ld]',$lds)
			.(in_array(1,$rights['atopics']) && $Forum->user ? ' <label>'.Eleanor::Check('fi[my]',!empty($forum['_filter']['my'])).static::$lang['only_my'].'</label>' : '')
			.'<div style="text-align:center">'.Eleanor::Button(static::$lang['apply-filter']).'</div></fieldset></form>';

		list($u,$g,$b,$h,$onforum)=static::Online($online);
		$whoon=static::$lang['whoon'];
		$herenow=static::$lang['here-now'];

		$c.='<br /><table class="tabstyle">
	<tr>
		<td>'.$herenow($g,$u,$h,$b).$whoon($g,$u,$h,$b)
			.($onforum ? ': '.rtrim($onforum,', ') : '').'</td>
	</tr></table>';

		if($moder)
			$c.='<script type="text/javascript">/*<![CDATA[*/$(function(){ One2AllCheckboxes("#topics-mm-form","#mass-check","[name=\"mm[t][]\"]:checkbox",true) })//]]></script>';
		return$c;
	}

	protected static function DrawCategories($forums,$headers=true)
	{
		if(!$forums)
			return;

		$images=Eleanor::$Template->default['theme'].'images/';
		$result='';
		foreach($forums['categories'] as $k=>$v)
			$result.=($headers ? '<div class="fcathead"><a href="'.$v['_a'].'">'.$v['title'].'</a><a href="#" data-id="'.$k.'" class="toggle"><img src="'.$images.'minus.gif" data-src="'.$images.'plus.gif" /></a></div>' : '<br />')
				.($v['_forums'] ? static::DrawForums($v['_forums'],array('id'=>$headers ? 'fc'.$k : false)) : '');

		if($forums['forums'])
			$result.=static::DrawForums($forums['forums']);

		return$result;
	}

	protected static function DrawForums($forums,$extra=array())
	{
		$c='<table class="tabstyle forums"'.Eleanor::TagParams($extra).'>
<tr>
	<th colspan="2">'.static::$lang['forum'].'</th>
	<th class="topics">'.static::$lang['topics'].'</th>
	<th class="posts">'.static::$lang['answers'].'</th>
	<th class="lastpost">'.static::$lang['lastpost'].'</th>
</tr>';

		foreach($forums as $k=>&$v)
		{
			$c.='<tr>
	<td class="img"><img'.($v['_read'] ? ' class="forumimg read" title="'.static::$lang['nonewposts'].'"' : ' class="forumimg new" title="'.static::$lang['hasnp'].'" data-f="'.$k.'"').'src="'.$GLOBALS['Eleanor']->Forum->config['logos'].($v['image'] ? $v['image'] : 'none.png').'" /></td>
	<td class="ftitle"><a href="'.$v['_a'].'" style="font-weight:bold">'.$v['title'].'</a><br /><span>'.$v['description'].'</span>';

			$ism=$GLOBALS['Eleanor']->Forum->ugr['supermod'];
			$moders='';
			if(isset($v['moderators']))
				foreach($v['moderators'] as $fm)
				{
					if($fm['_me'])
						$ism=true;
					if(isset($fm['title']))
						$moders.=sprintf(static::$lang['group:'],'<a href="'.$fm['_a'].'">'.$fm['html_pref'].$fm['title_l'].$fm['html_end'].'</a>, ');
					else
					{
						if(!$fm['_group'])
							$pr=$en='';
						else
						{
							$pr=join(Eleanor::Permissions(array($fm['_group']),'html_pref'));
							$en=join(Eleanor::Permissions(array($fm['_group']),'html_end'));
						}
						$moders.='<a href="'.$fm['_a'].'" title="'.$fm['full_name'].'">'.$pr.htmlspecialchars($fm['name'],ELENT,CHARSET).$en.'</a>, ';
					}
				}
			if($moders)
				$c.='<br />'.sprintf(static::$lang['moders:'],rtrim($moders,', '));

			if(isset($v['_alp']))
			{
				$lpt=strtotime($v['lp_date']);
				$lp=array(
					'date'=>$v['lp_date'],
					'title'=>$v['lp_title'],
					'author'=>$v['lp_author'],
					'_alpa'=>$v['_alpa'],
				);
			}
			else
			{
				$lpt=0;
				$lp=array();
			}

			if(isset($v['_subforums']))
			{
				$subf='';
				foreach($v['_subforums'] as $sf)
				{
					$subf.=' <a href="'.$sf['_a'].'">'.$sf['title'].'</a>,';
					if(isset($sf['_alp']) and $lpt<$lptsf=strtotime($sf['lp_date']))
					{
						$lpt=$lptsf;
						$lp=array(
							'date'=>$sf['lp_date'],
							'title'=>$sf['lp_title'],
							'author'=>$sf['lp_author'],
							'_alpa'=>$sf['_alpa'],
						);
					}
				}
				$c.='<br /><span class="small">'.sprintf(static::$lang['subforums:'],rtrim($subf,','));
			}

			$c.='</td>
<td class="topics">'.$v['topics'].($ism && isset($v['_amwt']) ? ' | <a href="'.$v['_amwt'].'" title="'.static::$lang['mt'].'">'.$v['queued_topics'].'</a>' : '').'</td>
<td class="posts">'.$v['posts'].($ism && isset($v['_amwp']) ? ' | <a href="'.$v['_amwp'].'" title="'.static::$lang['ma'].'">'.$v['queued_posts'].'</a>' : '').'</td>
<td>';

			if(!$v['_topics'])#Доступа к темам вообще нет
				$c.='<b>'.static::$lang['tar'].'</b>';
			elseif($lp)#Есть последний пост
			{
				$sa=htmlspecialchars($lp['author'],ELENT,CHARSET);
				$c.='<a href="'.$v['_alp'].'" title="'.static::$lang['glp'].'"><img src="images/forum/lastpost.png" /></a> '.Eleanor::$Language->Date($lp['date'],'fdt').'
<br /><b>'.static::$lang['topic:'].'</b> <a href="'.$v['_anp'].'" title="'.$lp['title'].'">'.Strings::CutStr($lp['title'],300).'</a>
<br /><b>'.static::$lang['author:'].'</b> '.($lp['_alpa'] ? '<a href="'.$lp['_alpa'].'">'.$sa.'</a>' : $sa);
			}
			else#Нету последнего поста
				$c.='<b>'.static::$lang['topic:'].'</b> -- <br /><b>'.static::$lang['author:'].'</b> --';
		$c.='</td>
	</tr>';
		}
		return$c.'</table>';
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
TplForumMain::$lang=Eleanor::$Language->Load(dirname(__DIR__).'/langs/forum-user-main-*.php',false);