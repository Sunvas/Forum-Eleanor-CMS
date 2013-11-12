<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
class TplAdminForum
{
	public static
		$lang;

	protected static function Menu($act='',$js=false)
	{
		if($js)
			array_push($GLOBALS['jscripts'],'modules/forum/Template/js/admin-forum.js','modules/forum/Template/js/admin-forum-'.Language::$main.'.js');

		$links=&$GLOBALS['Eleanor']->module['links'];
		$GLOBALS['Eleanor']->module['navigation']=array(
			array($links['forums'],static::$lang['flist'],'act'=>$act=='forums',
				'submenu'=>array(
					array($links['add-forum'],static::$lang['addforum'],'act'=>$act=='add-forum'),
				),
			),
			array($links['moders'],static::$lang['mlist'],'act'=>$act=='moders',
				'submenu'=>array(
					array($links['add-moder'],static::$lang['addmoder'],'act'=>$act=='add-moder'),
				),
			),
			array($links['prefixes'],static::$lang['prefixes'],'act'=>$act=='prefixes',
				'submenu'=>array(
					array($links['add-prefix'],static::$lang['addprefix'],'act'=>$act=='add-prefix'),
				),
			),
			array($links['reputation'],static::$lang['~reputation'],'act'=>$act=='reputation'),
			array($links['groups'],static::$lang['bgr'],'act'=>$act=='groups'),
			array($links['massrights'],static::$lang['massrights'],'act'=>$act=='massrights'),
			array($links['users'],static::$lang['fusers'],'act'=>$act=='users'),
			array($links['letters'],static::$lang['letters'],'act'=>$act=='letters'),
			array($links['files'],static::$lang['uploads'],'act'=>$act=='files'),
			array($links['fsubscriptions'],static::$lang['fsubscr'],'act'=>$act=='fsubscriptions'),
			array($links['tsubscriptions'],static::$lang['tsubscr'],'act'=>$act=='tsubscriptions'),
			array($links['tasks'],static::$lang['service'],'act'=>$act=='tasks'),
			array($links['settings'],Eleanor::$Language['main']['options'],'act'=>$act=='settings'),
		);
	}

	/*
		Страница отображения всех форумов
		$items Массив форумов. Формат: ID=>array(), ключи внутреннего массива:
			title Название форума
			image Путь к картинке-логотипу форума, если пустое - значит логотипа нет
			pos Целое число, характеризующее позицию форума
			_aedit Ссылка на редактирование форума
			_adel Ссылка на удаление форума
			_aparent Ссылка на просмотр подфорумов текущего форума
			_aup Ссылка на поднятие форума вверх, если равна false - значит форум уже и так находится в самом верху
			_adown Ссылка на опускание форума вниз, если равна false - значит форум уже и так находится в самом низу
			_aaddp Сылка на добавление подфорумов к даному форуму
		$subitems Массив подфорумов для страниц из массива $items. Формат: ID=>array(id=>array(), ...), где ID - идентификатор форума, id - идентификатор подфорума. Ключи массива подфорумов:
			title Заголовок подфорума
			_aedit Ссылка на редактирование подфорума
		$navi Массив, хлебные крошки навигации. Формат ID=>array(), ключи:
			title Заголовок крошки
			_a Ссылка на подпункты даной крошки. Может быть равно false
		$cnt Количество форумов всего
		$pp Количество форумов на страницу
		$qs Массив параметров адресной строки для каждого запроса
		$page Номер текущей страницы, на которой мы сейчас находимся
		$links Перечень необходимых ссылок, массив с ключами:
			sort_title Ссылка на сортировку списка $items по названию (возрастанию/убыванию в зависимости от текущей сортировки)
			sort_pos Ссылка на сортировку списка $items по позиции (возрастанию/убыванию в зависимости от текущей сортировки)
			sort_id Ссылка на сортировку списка $items по ID (возрастанию/убыванию в зависимости от текущей сортировки)
			form_items Ссылка для параметра action формы, внутри которой происходит отображение перечня $items
			pp Фукнция-генератор ссылок на изменение количества форумов отображаемых на странице
			first_page Ссылка на первую страницу пагинатора
			pages Функция-генератор ссылок на остальные страницы
			forum_groups_rights Функция-генератор ссылок на установку прав групп в конкретном форуме, параметры: ID форума, ID группы
	*/
	public static function Forums($items,$subitems,$navi,$cnt,$pp,$qs,$page,$links)
	{
		static::Menu('forums',true);
		$ltpl=Eleanor::$Language['tpl'];
		$nav=array();
		foreach($navi as &$v)
			$nav[]=$v['_a'] ? '<a href="'.$v['_a'].'">'.$v['title'].'</a>' : '<span class="fgr" title="'.static::$lang['oprgr'].'" data-id="'.(isset($qs['parent']) ? $qs['parent'] : 0).'">'.$v['title'].'</span>';

		$Lst=Eleanor::LoadListTemplate('table-list',4)
			->begin(
				array($ltpl['title'],'href'=>$links['sort_title'],'colspan'=>2),
				array(static::$lang['pos'],80,'href'=>$links['sort_pos']),
				array($ltpl['functs'],80,'href'=>$links['sort_id'])
			);
		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';

			$posasc=!$qs['sort'] || $qs['sort']=='pos' && $qs['so']=='asc';
			foreach($items as $k=>&$v)
			{
				$subs='';
				if(isset($subitems[$k]))
					foreach($subitems[$k] as &$vv)
						$subs.='<a href="'.$vv['_aedit'].'">'.$vv['title'].'</a>, ';

				$pos=$posasc
					? $Lst('func',
						$v['_aup'] ? array($v['_aup'],static::$lang['up'],$images.'up.png') : false,
						$v['_adown'] ? array($v['_adown'],static::$lang['down'],$images.'down.png') : false
					)
					: false;

				$Lst->item(
					$v['image'] ? array('<a href="'.$v['_aedit'].'"><img src="'.$v['image'].'" /></a>','style'=>'width:1px') : false,
					array('<a id="id'.$k.'" href="'.$v['_aedit'].'">'.$v['title'].'</a><br /><span class="small"><a href="'.$v['_aparent'].'" style="font-weight:bold">'.static::$lang['subforums'].'</a>: '.rtrim($subs,', ').' <a href="'.$v['_aaddp'].'" title="'.static::$lang['addsubforum'].'"><img src="'.$images.'plus.gif'.'" /></a></span>','colspan'=>$v['image'] ? false : 2),
					$pos && $pos[0] ? $pos : array('&empty;','center'),
					$Lst('func',
						'<span class="fgr" title="'.static::$lang['oprgr'].'" data-id="'.$k.'"><img src="'.$images.'select_users.png" alt="" /></span>',
						array($v['_aedit'],$ltpl['edit'],$images.'edit.png'),
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					)
				);
			}
		}
		else
			$Lst->empty($nav ? static::$lang['fbnf'] : static::$lang['fnf']);
		return Eleanor::$Template->Cover(
			($nav ? '<table class="filtertable"><tr><td style="font-weight:bold">'.join(' &raquo; ',$nav).'</td></tr></table>' : '')
			.'<form action="'.$links['form_items'].'" method="post">'
			.$Lst->end().'<div class="submitline" style="text-align:left">'.sprintf(static::$lang['fpp'],$Lst->perpage($pp,$links['pp'])).'</div></form>'
			.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page']))
		).static::FGR($links['forum_groups_rights']);
	}

	/*
		Страница добавления/редактирования форума
		$id идентификатор редактируемого форума, если $id==0 значит форум добавляется
		$values массив значений полей
			Общие ключи:
			parent Родитель форума
			pos Позиция форума
			is_category Флаг категории (в катеории нельзя создавать темы, можно только форумы помещать)
			image Логотип форума
			inc_posts Флаг включения счетчика сообщений
			reputation Флаг включение репутации в этом форуме
			hide_attach Флаг включения сокрытия аттачей
			prefixes Массив идентификаторов префиксов тем

			Языковые ключи:
			title Название форума
			description Описание форума
			rules Правила форума
			uri URI форума
			meta_title Заголовок окна браузера при просмотре форума
			meta_descr Мета описание форума

			Специальные ключи:
			_onelang Флаг моноязычной новости при включенной мультиязычности
		$errors Массив ошибок
		$imagopts Набор option-ов для select-a с возможными логотипами
		$prefixes Массив возможных префиксов форума, формат: id=>название префикса
		$uploader Интерфейс загрузчика
		$bypost Признак того, что даные нужно брать из POST запроса
		$back URL возврата
		$links Перечень необходимых ссылок, массив с ключами:
			delete Ссылка на удаление форума или false
	*/
	public static function AddEditForum($id,$values,$errors,$imagopts,$prefixes,$uploader,$bypost,$back,$links)
	{
		static::Menu($id ? 'edit-forum' : 'add-forum',true);

		$ltpl=Eleanor::$Language['tpl'];
		if(Eleanor::$vars['multilang'])
		{
			$ml=array();
			foreach(Eleanor::$langs as $k=>$v)
			{
				$ml['title'][$k]=Eleanor::Input('title['.$k.']',$GLOBALS['Eleanor']->Editor->imgalt=Eleanor::FilterLangValues($values['title'],$k),array('tabindex'=>5,'id'=>'title-'.$k));
				$ml['uri'][$k]=Eleanor::Input('uri['.$k.']',Eleanor::FilterLangValues($values['uri'],$k),array('onfocus'=>'if(!$(this).val())$(this).val($(\'#title-'.$k.'\').val())','tabindex'=>6));
				$ml['description'][$k]=$GLOBALS['Eleanor']->Editor->Area('description['.$k.']',Eleanor::FilterLangValues($values['description'],$k),array('bypost'=>$bypost,'no'=>array('tabindex'=>7,'rows'=>15)));
				$ml['meta_title'][$k]=Eleanor::Input('meta_title['.$k.']',Eleanor::FilterLangValues($values['meta_title'],$k),array('tabindex'=>8));
				$ml['meta_descr'][$k]=Eleanor::Input('meta_descr['.$k.']',Eleanor::FilterLangValues($values['meta_descr'],$k),array('tabindex'=>9));
				$ml['rules'][$k]=$GLOBALS['Eleanor']->Editor->Area('rules['.$k.']',Eleanor::FilterLangValues($values['rules'],$k),array('bypost'=>$bypost,'no'=>array('tabindex'=>10,'rows'=>15)));
			}
		}
		else
			$ml=array(
				'title'=>Eleanor::Input('title',$GLOBALS['Eleanor']->Editor->imgalt=$values['title'],array('tabindex'=>5,'id'=>'title')),
				'uri'=>Eleanor::Input('uri',$values['uri'],array('onfocus'=>'if(!$(this).val())$(this).val($(\'#title\').val())','tabindex'=>6)),
				'description'=>$GLOBALS['Eleanor']->Editor->Area('description',$values['description'],array('bypost'=>$bypost,'no'=>array('tabindex'=>7,'rows'=>15))),
				'meta_title'=>Eleanor::Input('meta_title',$values['meta_title'],array('tabindex'=>8)),
				'meta_descr'=>Eleanor::Input('meta_descr',$values['meta_descr'],array('tabindex'=>9)),
				'rules'=>$GLOBALS['Eleanor']->Editor->Area('rules',$values['rules'],array('bypost'=>$bypost,'no'=>array('tabindex'=>10,'rows'=>15))),
			);

		$posopts='';
		$i=1;
		$parents=isset($values['parents']) ? $values['parents'] : '';
		foreach($GLOBALS['Eleanor']->Forum->Forums->dump as $k=>$v)
			if($k!=$id and $v['parents']==$parents)
				$posopts.=Eleanor::Option($v['title'],++$i,$i==$values['pos']);
		$posopts=Eleanor::Option(static::$lang['begin'],1,$values['pos']<=1)
			.Eleanor::Optgroup(static::$lang['after'],$posopts);

		if($back)
			$back=Eleanor::Input('back',$back,array('type'=>'hidden'));
		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin()

			->head(static::$lang['location'])
			->item(static::$lang['parent'],Eleanor::Select('parent',Eleanor::Option('-'.static::$lang['no'].'-','',$values['parent']==0).$GLOBALS['Eleanor']->Forum->Forums->SelectOptions($values['parent'],$id),array('tabindex'=>1)))
			->item(static::$lang['pos'],Eleanor::Select('pos',$posopts,array('tabindex'=>2)))
			->item(array(static::$lang['fcat'],Eleanor::Check('is_category',$values['is_category'],array('tabindex'=>3)),'tip'=>static::$lang['fcat_']))

			->head(static::$lang['uid'])
			->item(static::$lang['logo'],Eleanor::Select('image',Eleanor::Option('-'.static::$lang['no'].'-','',!$values['image']).$imagopts,array('id'=>'image','tabindex'=>4,'data-path'=>$GLOBALS['Eleanor']->module['config']['logos'])))
			->item('&nbsp;','<img src="images/spacer.png" id="preview" /><script type="text/javascript">/*<![CDATA[*/$(function(){ AddEditForum() })//]]></script>')
			->item($ltpl['title'],Eleanor::$Template->LangEdit($ml['title'],null))
			->item('URI',Eleanor::$Template->LangEdit($ml['uri'],null))
			->item(static::$lang['descr'],Eleanor::$Template->LangEdit($ml['description'],null))
			->item('Page title',Eleanor::$Template->LangEdit($ml['meta_title'],null))
			->item('Meta description',Eleanor::$Template->LangEdit($ml['meta_descr'],null))
			->item(static::$lang['rules'],Eleanor::$Template->LangEdit($ml['rules'],null));

		if(Eleanor::$vars['multilang'])
			$Lst->item($ltpl['set_for_langs'],Eleanor::$Template->LangChecks($values['_onelang'],$values['_langs'],null,11));

		$prs='';
		foreach($prefixes as $k=>&$v)
			$prs.=Eleanor::Option($v,$k,in_array($k,$values['prefixes']));

		$Lst->head(static::$lang['opts'])
			->item(static::$lang['incposts'],Eleanor::Check('inc_posts',$values['inc_posts'],array('tabindex'=>12)))
			->item(static::$lang['enrep'],Eleanor::Check('reputation',$values['reputation'],array('tabindex'=>13)))
			->item(array(static::$lang['hideattach'],Eleanor::Check('hide_attach',$values['hide_attach'],array('tabindex'=>14)),'tip'=>static::$lang['hideattach_']))
			->item(static::$lang['prefixes'],Eleanor::Items('prefixes',$prs))

			->end()
			->submitline((string)$uploader)
			->submitline(
				$back
				.Eleanor::Button($id>0 ? static::$lang['save-forum'] : static::$lang['add-forum'],'submit',array('tabindex'=>15))
				.($id ? ' '.Eleanor::Button($ltpl['delete'],'button',array('onclick'=>'window.location=\''.$links['delete'].'\'')) : '')
			)
			->endform();

		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		return Eleanor::$Template->Cover((string)$Lst,$errors,'error');
	}

	/*
		Интерфейс страницы, который появляется, если при редактировании форума удалить некоторые языки
		$forum Массив даных форума, у которого удаляются языки, ключи:
			id ID форума
			title Название форума
		$values Массив значений полей, ключи:
			trash ID форума, куда могут быть перемещены темы с удаляемого форума
		$langs Массив удаляемых языков форума
		$newlangs Массив новых языков форума
		$back URL возврата
	*/
	public static function SaveDeleteForm($forum,$values,$langs,$newlangs,$back,$errors)
	{
		static::Menu();

		$nlopts='';
		foreach($newlangs as $v)
			$nlopts.=Eleanor::Option($v=='' ? static::$lang['uni'] : Eleanor::$langs[$v]['name'],$v,$v==$values['trash']);

		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin()
			->item(static::$lang['puttopics'],Eleanor::Select('trash',Eleanor::Option(static::$lang['deltopics'],0,$values['trash']=='0')
				.($nlopts ? Eleanor::OptGroup(static::$lang['ltif'],$nlopts) : '')
				.Eleanor::OptGroup(static::$lang['ptaf'],$GLOBALS['Eleanor']->Forum->Forums->SelectOptions($values['trash'],$forum['id']))
			))
			->end()
			->submitline(Eleanor::Button(static::$lang['delete']).($back ? ' '.Eleanor::Button(static::$lang['cancel'],'button',array('onclick'=>'location.href="'.$back.'"')) : ''))
			->endform();

		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		$dc=static::$lang['delcf'];
		return Eleanor::$Template->Cover(
			Eleanor::$Template->Message($dc($langs,$forum['title']),'info').$Lst,
			$errors
		);
	}

	/*
		Интерфейс страницы с иноформацией о процессе удаления языков
		$forum Массив даных форума, у которого удаляются языки, ключи:
			id ID форума
			title Название форума
		$info Массив с даными об удалении, ключи:
			total Количество тем, которые нужно перенести (удалить) в связи с удалением форума, в котором они находились
			done Количество перемещенных (удаленных) тем
		$langs Массив удаляемых языков форума
		$newlangs Массив новых языков форума
		$links Массив ссылок, ключи:
			go URL перехода к очередной части удаления
	*/
	public static function SaveDeleteProcess($forum,$info,$langs,$newlangs,$links)
	{
		static::Menu();
		$dp=static::$lang['delfp'];
		return Eleanor::$Template->RedirectScreen($links['go'],5)->Cover(
			Eleanor::$Template->Message($dp($links,$forum['title']).'<br /><progress max="'.$info['total'].'" value="'.$info['done'].'" style="width:100%">'.($info['total']==0 ? 100 : round($info['done']/$info['total']*100)).'%</progress>','info')
		);
	}

	/*
		Интерфейс страницы с иноформацией об успешно удаленных языках форума
		$forum Массив даных форума, у которого удалились языки, ключи:
			id ID форума
			title Название форума
		$langs Массив удаляемых языков форума
		$newlangs Массив новых языков форума
		$back URL возврата
	*/
	public static function SaveDeleteComplete($forum,$langs,$newlangs,$back)
	{
		static::Menu();
		$ded=static::$lang['deld'];
		return Eleanor::$Template->Cover(
			Eleanor::$Template->Message($ded($langs,$forum['title']).($back ? '<br /><a href="'.$back.'">'.static::$lang['back'].'</a>' : ''),'info')
		);
	}

	/*
		Страница отображения групп пользователей
		$items Массив групп пользователей. Формат: ID=>array(), ключи внутреннего массива (если какое-то значение равно null, значит свойство наследуется):
			title Нзвание группы
			html_pref HTML префикс группы
			html_end HTML окончание группы
			parents Массив родителей группы
			grow_to Идентификатор группы, в которую нужно автоматически переводить пользователей по достижению определенного количества сообщений (или других условия)
			grow_after Количество сообщений по набору которых, пользователь должен быть переведен в другую группу
			supermod Флаг наличия прав супермодератора у группы. Все члены этой группы будут иметь права супермодератора на форуме
			_aedit - ссылка на редактирование группы
			_aparent - ссылка на просмотр подгрупп
		$subitems Массив подгрупп для групп из массива $items. Формат: ID=>array(id=>array(), ...), где ID - идентификатор группы, id - идентификатор подгруппы. Ключи массива подгруппы:
			title Название подгруппы
			_aedit Ссылка на редактирование подгруппы
		$navi Массив, хлебные крошки навигации. Формат ID=>array(), ключи:
			title - заголовок крошки
			_a - ссылка подпункта даной крошки. Может быть равно false
		$parents Массив родителей, для определения настроек групп, в случае, когда они наследуются, ключи: grow_to, grow_after, supermod - описание даны выше
	*/
	public static function Groups($items,$subitems,$navi,$parents)
	{
		static::Menu('groups');
		$ltpl=Eleanor::$Language['tpl'];

		$nav=array();
		foreach($navi as &$v)
			$nav[]=$v['_a'] ? '<a href="'.$v['_a'].'">'.$v['title'].'</a>' : $v['title'];

		$Lst=Eleanor::LoadListTemplate('table-list',4)
			->begin(
				static::$lang['group'],
				static::$lang['supermod'],
				static::$lang['propag'],
				$ltpl['functs']
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			$lga=static::$lang['ga'];
			foreach($items as $k=>$v)
			{
				$subs='';
				if(isset($subitems[$k]))
					foreach($subitems[$k] as &$vv)
						$subs.='<a href="'.$vv['_aedit'].'">'.$vv['title'].'</a>, ';

				foreach($v['parents'] as &$p)
				{
					if($v['supermod']===null and isset($parents[$p]['supermod']))
						$v['supermod']=$parents[$p]['supermod'];
					if($v['grow_to']===null and isset($parents[$p]['grow_to']))
						$v['grow_to']=$parents[$p]['grow_to'];
					if($v['grow_after']===null and isset($parents[$p]['grow_after']))
						$v['grow_after']=$parents[$p]['grow_after'];
				}

				$Lst->item(
					array($v['html_pref'].$v['title'].$v['html_end'].($subs ? '<br /><span class="small"><a href="'.$v['_aparent'].'" style="font-weight:bold">'.static::$lang['subgroups'].'</a>: '.rtrim($subs,', ').'</span>' : ''),'href'=>$v['_aedit']),
					array(Eleanor::$Template->YesNo($v['supermod']),'center'),
					array(isset($items[ $v['grow_to'] ]) ? $items[ $v['grow_to'] ]['html_pref'].$items[ $v['grow_to'] ]['title'].$items[ $v['grow_to'] ]['html_end'].' ('.$lga($v['grow_after']).')' : '<i>'.static::$lang['no'].'</i>',isset($items[ $v['grow_to'] ]) ? '' : 'center'),
					$Lst('func',
						array($v['_aedit'],$ltpl['edit'],$images.'edit.png')
					)
				);
			}
		}
		else
			$Lst->empty(static::$lang['sgnf']);
		return Eleanor::$Template->Cover(($nav ? '<table class="filtertable"><tr><td style="font-weight:bold">'.join(' &raquo; ',$nav).'</td></tr></table>' : '').$Lst->end());
	}

	/*
		Страница правки группы
		$group Массив даных группы (не для правки), ключи:
			parents Массив родителей группы
			title Название группы
			html_pref HTML префикс группы
			html_end HTML окончание группы
		$values Массив значений полей, ключи:
			grow_to Идентификатор группы, в которую будут переведены пользователи текущий группы по достижению определенного количества постов
			grow_after Количество постов, до достижению которых пользователи будут переведены в другую группу
			supermod Флаг группы с правами супермодератора
			see_hidden_users Флаг позволения пользователям группы видеть скрытых пользователей на форуме
			moderate Флаг включения перемодерации постов пользователям группы
			permissions Массив HTML результата контролов разрешений
			_inherit Массив имен наследуемых значений формы
			_inheritp Массив имен наследуюемых разрешений
		$controls Массив контролов разрешений
		$groups option-ы для select-а с выбором группы
		$min Минимально возможное значение для grow_after
		$bypost Признак того, что даные нужно брать из POST запроса
		$back URL возврата
		$errors Массив ошибок
	*/
	public static function EditGroup($group,$values,$imagopts,$controls,$groups,$min,$bypost,$back,$errors)
	{
		static::Menu('edit-group',true);
		$Lst=Eleanor::LoadListTemplate('table-form');
		$general=(string)$Lst->begin(array('id'=>'tg'))
			->head(static::$lang['propag'])
			->item(static::$lang['togr'].($group['parents'] ? Eleanor::Check('_inherit[]',in_array('grow_to',$values['_inherit']),array('style'=>'display:none','value'=>'grow_to')) : ''),($group['parents'] ? '<div>' : '').Eleanor::Select('grow_to',Eleanor::Option(static::$lang['-nopropag-'],0,$values['grow_to']==0).$groups,array('tabindex'=>1)).($group['parents'] ? '</div>' : ''))
			->item(static::$lang['afposts'].($group['parents'] ? Eleanor::Check('_inherit[]',in_array('grow_after',$values['_inherit']),array('style'=>'display:none','value'=>'grow_after')) : ''),($group['parents'] ? '<div>' : '').Eleanor::Input('grow_after',$min<$values['grow_after'] ? $values['grow_after'] : $min,array('min'=>$min,'type'=>'number','tabindex'=>2)).static::$lang['posts.'].($group['parents'] ? '</div>' : ''))
			->head(static::$lang['gr'])
			->item(static::$lang['supermod'].($group['parents'] ? Eleanor::Check('_inherit[]',in_array('supermod',$values['_inherit']),array('style'=>'display:none','value'=>'supermod')) : ''),($group['parents'] ? '<div>' : '').Eleanor::Check('supermod',$values['supermod'],array('tabindex'=>3)).($group['parents'] ? '</div>' : ''))
			->item(static::$lang['shu'].($group['parents'] ? Eleanor::Check('_inherit[]',in_array('see_hidden_users',$values['_inherit']),array('style'=>'display:none','value'=>'see_hidden_users')) : ''),($group['parents'] ? '<div>' : '').Eleanor::Check('see_hidden_users',$values['see_hidden_users'],array('tabindex'=>4)).($group['parents'] ? '</div>' : ''))
			->item(static::$lang['premod'].($group['parents'] ? Eleanor::Check('_inherit[]',in_array('moderate',$values['_inherit']),array('style'=>'display:none','value'=>'moderate')) : ''),($group['parents'] ? '<div>' : '').Eleanor::Check('moderate',$values['moderate'],array('tabindex'=>5)).($group['parents'] ? '</div>' : ''))
			->head(static::$lang['mains'])
			->item(static::$lang['logo'],Eleanor::Select('image',Eleanor::Option('-'.static::$lang['no'].'-','',!$values['image']).$imagopts,array('id'=>'image','tabindex'=>6,'data-path'=>$GLOBALS['Eleanor']->module['config']['glogos'])))
			->item('&nbsp;','<img src="images/spacer.png" id="preview" />')
			->end();

		$Lst->begin(array('id'=>'tp'));
		foreach($controls as $k=>&$v)
			if(is_array($v) and isset($values['permissions'][$k]))
				$Lst->item(array($v['title'].($group['parents'] ? Eleanor::Check('_inheritp[]',in_array($k,$values['_inheritp']),array('style'=>'display:none','value'=>$k)) : ''),($group['parents'] ? '<div>' : '').$values['permissions'][$k].($group['parents'] ? '</div>' : ''),'tip'=>$v['descr']));
			elseif(is_string($v))
				$Lst->head($v);
		$perms=(string)$Lst->end();

		if($back)
			$back=Eleanor::Input('back',$back,array('type'=>'hidden'));

		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		return Eleanor::$Template->Cover(
			$Lst->form()
			->tabs(
				array(static::$lang['mains'],$general),
				array(static::$lang['rbd'],$perms)
			)
			->submitline($back.Eleanor::Button('OK','submit',array('tabindex'=>100)))
			->endform(),
			$errors
		).'<script type="text/javascript">/*<![CDATA[*/$(function(){ EditGroup('.($group['parents'] ? 'true' : 'false').') })//]]></script>';
	}

	/*
		Страница установки массовых прав групп для форумов
		$controls Массив контролов разрешений
		$values Массив значений полей, ключи:
			rights Массив HTML результата контролов разрешений
			inherit Массив имен наследуюемых разрешений
		$groups option-ы для select-а с выбором группы
		$forums option-ы для select-а с выбором форумов
		$bypost Признак того, что даные нужно брать из POST запроса
		$errors Массив ошибок
	*/
	public static function MassRights($controls,$values,$groups,$forums,$bypost,$errors)
	{
		static::Menu('massrights',true);
		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin(array('id'=>'fg'))
			->head(static::$lang['assignment'])
			->item(static::$lang['groups'],Eleanor::Items('groups',$groups))
			->item(static::$lang['forums'],Eleanor::Items('forums',$forums))
			->head(static::$lang['rights']);

		foreach($controls as $k=>&$v)
			if(is_array($v) and isset($values['rights'][$k]))
				$Lst->item(array(
					$v['title'].Eleanor::Check('inherit[]',in_array($k,$values['inherit']),array('style'=>'display:none','value'=>$k)),
					'<div>'.$values['rights'][$k].'</div>',
					'tip'=>$v['descr']
				));
			elseif(is_string($v))
				$Lst->head($v);

		$Lst->end()
			->submitline(Eleanor::Button('OK','submit',array('tabindex'=>100)))
			->endform();

		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		return Eleanor::$Template->Cover((!$errors && $bypost ? Eleanor::$Template->Message(static::$lang['riss'],'info') : '').$Lst,$errors)
			.'<script type="text/javascript">/*<![CDATA[*/$(function(){ MassRights() })//]]></script>';
	}

	/*
		Шаблон страницы с редактированием форматов писем
		$controls Перечень контролов в соответствии с классом контролов. Если какой-то элемент массива не является массивом, значит это заголовок подгруппы контролов
		$values Результирующий HTML код контролов, который необходимо вывести на странице. Ключи даного массива совпадают с ключами $controls
	*/
	public static function Letters($controls,$values)
	{
		static::Menu('letters');
		$Lst=Eleanor::LoadListTemplate('table-form')->form()->begin();
		foreach($controls as $k=>&$v)
			if(is_array($v) and isset($values[$k]))
				$Lst->item(array($v['title'],Eleanor::$Template->LangEdit($values[$k],null),'tip'=>$v['descr']));
			elseif(is_string($v))
				$Lst->head($v);
		return Eleanor::$Template->Cover($Lst->button(Eleanor::Button())->end()->endform());
	}

	/*
		Шаблон отображения списка пользователей форума
		$items Массив пользователей. Формат: ID=>array(), ключи внутреннего массива:
			full_name Полное имя пользователя
			name Имя пользователя (небезопасный HTML!)
			email E-mail пользователя
			ip IP адрес пользователя
			posts Количество полезных постов пользователя на форуме
			_asedit Ссылка на редактирование пользователя (системное)
			_adel Ссылка на удаление пользователя (системное)
			_aedit Ссылка на форумное редактирование пользователя
			_arecount Ссылка на пересчет репутации пользователя
			_arep Ссылка на подробный просмотр репутации пользователя
		$cnt Количество страниц всего
		$pp Количество пользователей форума на страницу
		$page Номер текущей страницы, на которой мы сейчас находимся
		$qs Массив параметров адресной строки для каждого запроса
		$links Перечень необходимых ссылок, массив с ключами:
			sort_posts Ссылка на сортировку списка $items по количеству постов (возрастанию/убыванию в зависимости от текущей сортировки)
			sort_rep Ссылка на сортировку списка $items по репутации (возрастанию/убыванию в зависимости от текущей сортировки)
			sort_name Ссылка на сортировку списка $items по имени пользователя (возрастанию/убыванию в зависимости от текущей сортировки)
			sort_email Ссылка на сортировку списка $items по email (возрастанию/убыванию в зависимости от текущей сортировки)
			sort_ip Ссылка на сортировку списка $items по ip адресу (возрастанию/убыванию в зависимости от текущей сортировки)
			sort_id Ссылка на сортировку списка $items по ID (возрастанию/убыванию в зависимости от текущей сортировки)
			form_items Ссылка для параметра action формы, внутри которой происходит отображение перечня $items
			pp Фукнция-генератор ссылок на изменение количества пользователей отображаемых на странице
			first_page Ссылка на первую страницу пагинатора
			pages Функция-генератор ссылок на остальные страницы
		$info Массив информационных сообщений, возможные ключи и значения:
			REPUTATION Наличие ключа сообщает о том, что пересчитана репутация у пользователей, которые содержатся в массиве значения. Ключи:
				_aedit Ссылка на редактирование пользователя
				_asedit Ссылка на редактирование пользователя в системе
				name Имя пользователя. Небезопасный HTML!
				full_name Полное имя пользователя
	*/
	public static function UserList($items,$cnt,$pp,$page,$qs,$links,$info)
	{
		static::Menu('users');
		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$qs+=array(''=>array());
		$qs['']+=array('fi'=>array());
		$fs=(bool)$qs['']['fi'];
		$qs['']['fi']+=array(
			'name'=>false,
			'namet'=>false,
			'id'=>false,
			'regto'=>false,
			'regfrom'=>false,
			'ip'=>false,
			'email'=>false,
		);

		$finamet='';
		$namet=array(
			'b'=>static::$lang['b'],
			'q'=>static::$lang['q'],
			'e'=>static::$lang['e'],
			'm'=>static::$lang['m'],
		);
		foreach($namet as $k=>&$v)
			$finamet.=Eleanor::Option($v,$k,$qs['']['fi']['namet']==$k);

		$Lst=Eleanor::LoadListTemplate('table-list',7)
			->begin(
				array(static::$lang['un'],'href'=>$links['sort_name']),
				array(static::$lang['posts'],'href'=>$links['sort_posts']),
				array(static::$lang['reputation'],'href'=>$links['sort_rep']),
				array('E-mail','href'=>$links['sort_email']),
				array('IP','href'=>$links['sort_ip']),
				array($ltpl['functs'],'href'=>$links['sort_id']),
				array(Eleanor::Check('mass',false,array('id'=>'mass-check')),20)
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			foreach($items as $k=>&$v)
				$Lst->item(
					'<a href="'.$v['_aedit'].'" id="it'.$k.'">'.$v['name'].'</a>'.($v['name']==$v['full_name'] ? '' : '<br /><i>'.$v['full_name'].'</i>'),
					array($v['posts'],'right'),
					array($v['rep']===null ? '<i>'.static::$lang['no'].'</i>' : '<a href="'.$v['_arep'].'">'.$v['rep'].'</a>','right'),
					array($v['email'],'center'),
					array($v['ip'],'center','href'=>'http://eleanor-cms.ru/whois/'.$v['ip'],'hrefextra'=>array('target'=>'_blank')),
					$Lst('func',
						array($v['_arecount'],static::$lang['recrep'],$images.'sort.png'),
						array($v['_asedit'],$ltpl['edit'],$images.'edit.png'),
						$v['_adel'] ? array($v['_adel'],$ltpl['delete'],$images.'delete.png') : false
					),
					Eleanor::Check('mass[]',false,array('value'=>$k))
				);
		}
		else
			$Lst->empty(static::$lang['unf']);

		$messages='';
		$rc=static::$lang['rcounted'];
		foreach($info as $k=>&$v)
			if($k=='REPUTATION')
			{
				foreach($v as &$vv)
					$vv='<a href="'.$vv['_aedit'].'">'.htmlspecialchars($vv['name'],ELENT,CHARSET).'</a>';
				$messages.=Eleanor::$Template->Message($rc($v),'info');
			}

		return Eleanor::$Template->Cover(
			($messages ? '<div id="messages">'.$messages.'</div>' : '')
			.'<form method="post">
			<table class="tabstyle tabform" id="ftable">
				<tr class="infolabel"><td colspan="2"><a href="#">'.$ltpl['filters'].'</a></td></tr>
				<tr>
					<td><b>'.static::$lang['un'].'</b><br />'.Eleanor::Select('fi[namet]',$finamet,array('style'=>'width:30%')).Eleanor::Input('fi[name]',$qs['']['fi']['name'],array('style'=>'width:68%')).'</td>
					<td><b>E-mail</b><br />'.Eleanor::Input('fi[email]',$qs['']['fi']['email']).'</td>
				</tr>
				<tr>
					<td><b>IDs</b><br />'.Eleanor::Input('fi[id]',$qs['']['fi']['id']).'</td>
					<td><b>IP</b><br />'.Eleanor::Input('fi[ip]',$qs['']['fi']['ip']).'</td>
				</tr>
				<tr>
					<td><b>'.static::$lang['reg'].'</b> '.static::$lang['ft'].'<br />'.Dates::Calendar('fi[regfrom]',$qs['']['fi']['regfrom'],true,array('style'=>'width:35%')).' - '.Dates::Calendar('fi[regto]',$qs['']['fi']['regto'],true,array('style'=>'width:35%')).'</td>
					<td style="text-align:center;vertical-align:middle">'.Eleanor::Button($ltpl['apply']).'</td>
				</tr>
			</table>
<script type="text/javascript">//<![CDATA[
$(function(){
	var fitrs=$("#ftable tr:not(.infolabel)");
	$("#ftable .infolabel a").click(function(){
		fitrs.toggle();
		$("#ftable .infolabel a").toggleClass("selected");
		return false;
	})'.($fs ? '' : '.click()').';
	One2AllCheckboxes("#checks-form","#mass-check","[name=\"mass[]\"]",true);
	setTimeout(function(){
		$("#messages").fadeOut("slow").remove();
	},20000);
});//]]></script>
		</form>
		<form id="checks-form" action="'.$links['form_items'].'" method="post" onsubmit="return (CheckGroup(this) && ($(\'select\',this).val()==\'dr\' || confirm(\''.$ltpl['are_you_sure'].'\')))">'
		.$Lst->end()
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf(static::$lang['upp'],$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option(static::$lang['recrep'],'r')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		Страница правки пользователя форума
		$users Массив даных пользоватея (не для правки), ключи:
			_aedit Ссылка на системное редактирование пользователя
			group Массив даных основной группы пользователя, ключи
				title Название группы
				html_pref HTML префикс группы
				html_end HTML окончание группы
			full_name Полное имя пользователя
			name Имя пользователя (небезопасный HTML!)
		$values Массив значений полей, ключи:
			posts Количество постов у пользователя
			restrict_post Флаг запрета публикации сообщений на форуме
			restrict_post_to Дата, до наступления которой пользователю будет запрещена публикация постов на форуме
			descr Описание пользователя (только для админа)
		$bypost Признак того, что даные нужно брать из POST запроса
		$back URL возврата
		$errors Массив ошибок
	*/
	public static function EditUser($user,$values,$bypost,$back,$errors)
	{
		static::Menu('edit-user');

		$user['name']=htmlspecialchars($user['name'],ELENT,CHARSET);
		if($back)
			$back=Eleanor::Input('back',$back,array('type'=>'hidden'));

		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin()
			->item(static::$lang['un'],'<a href="'.$user['_aedit'].'" title="'.$user['group']['title'].'">'.$user['group']['html_pref'].$user['name'].$user['group']['html_end'].'</a>'.($user['name']==$user['full_name'] ? '' : ' ('.$user['full_name'].')'))
			->item(static::$lang['pon'],Eleanor::Input('posts',$values['posts'],array('min'=>0,'type'=>'number')))
			->item(static::$lang['rpr'],Eleanor::Check('restrict_post',$values['restrict_post']))
			->item(static::$lang['rpru'],Dates::Calendar('restrict_post_to',$values['restrict_post_to'],true))
			->item(array(static::$lang['note'],Eleanor::Text('descr',$values['descr']),'descr'=>static::$lang['internal']))
			->end()
			->submitline($back.Eleanor::Button('OK','submit',array('tabindex'=>100)))
			->endform();

		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		return Eleanor::$Template->Cover($Lst,$errors);
	}

	/*
		Шаблон отображения списка модераторов
		$items Массив модераторов. Формат: ID=>array(), ключи внутреннего массива:
			groups Массив ID групп пользователей, которые входят в этого модератора
			users Массив ID пользователей, которые входят в этого модератора
			date Дата создания модератора
			forums Массив ID форумов, на которые распространяются права модерирования даного модератора
			_adel Ссылка на удаление модератора
			_aedit Ссылка на правку модератора
		$forums Массив с подробной информацией о форумах, в которых модерируют модераторы даного списка. Формат: ID=>array(), ключи внутреннего массива:
			title Название форума
			_aedit Ссылка на правку форума
		$users Массив с подробной информацией о пользователях, которые входят в модераторов из даного списка. Формат: ID=>array(), ключи внутреннего массива:
			group ID группы пользователя
			name Имя пользователя (небезопасный HTML!)
			full_name Полное имя пользователя
		$groups Массив с подробной информацией о группах пользователей, которые входят в модераторов из даного списка. Формат: ID=>array(), ключи внутреннего массива:
			title Название группы
			html_pref HTML префикс группы
			html_end HTML окончание группы
		$fiforums Option-ы для Select-а фильтра модераторов по форуму
		$cnt Количество страниц всего
		$pp Количество модераторов на страницу
		$page Номер текущей страницы, на которой мы сейчас находимся
		$qs Массив параметров адресной строки для каждого запроса
		$links Перечень необходимых ссылок, массив с ключами:
			sort_id Ссылка на сортировку списка $items по ID (возрастанию/убыванию в зависимости от текущей сортировки)
			sort_date Ссылка на сортировку списка $items по дате создания модераторов (возрастанию/убыванию в зависимости от текущей сортировки)
			form_items Ссылка для параметра action формы, внутри которой происходит отображение перечня $items
			pp Фукнция-генератор ссылок на изменение количества модераторов отображаемых на странице
			first_page Ссылка на первую страницу пагинатора
			pages Функция-генератор ссылок на остальные страницы
	*/
	public static function Moderators($items,$forums,$users,$groups,$fiforums,$cnt,$pp,$page,$qs,$links)
	{
		static::Menu('moders');

		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$Lst=Eleanor::LoadListTemplate('table-list',6)
			->begin(
				static::$lang['users'],
				static::$lang['groups'],
				array(static::$lang['creation'],'href'=>$links['sort_date']),
				static::$lang['forums'],
				array($ltpl['functs'],'href'=>$links['sort_id']),
				array(Eleanor::Check('mass',false,array('id'=>'mass-check')),20)
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			foreach($items as $k=>&$v)
			{
				$fs=$gs=$us='';

				foreach($v['users'] as $u)
					if(isset($users[$u]))
					{
						if(isset($groups[ $users[$u]['group'] ]))
						{
							$hp=$groups[ $users[$u]['group'] ]['html_pref'];
							$he=$groups[ $users[$u]['group'] ]['html_end'];
						}
						else
							$hp=$he='';
						$name=htmlspecialchars($users[$u]['name'],ELENT,CHARSET);
						$us.='<a href="'.$users[$u]['_aedit'].'">'.$hp.$name.$he.($name==$users[$u]['full_name'] ? '' : ' ('.$users[$u]['full_name'].')').'</a>, ';
					}

				foreach($v['groups'] as $g)
					if(isset($groups[$g]))
						$gs.='<a href="'.$groups[$g]['_aedit'].'">'.$groups[$g]['html_pref'].$groups[$g]['title'].$groups[$g]['html_end'].'</a>, ';

				foreach($v['forums'] as &$f)
					if(isset($forums[$f]))
						$fs.='<a href="'.$forums[$f]['_aedit'].'">'.$forums[$f]['title'].'</a>, ';

				$Lst->item(
					$us ? rtrim($us,', ') : '<i>'.static::$lang['no'].'</i>',
					$gs ? rtrim($gs,', ') : '<i>'.static::$lang['no'].'</i>',
					array(Eleanor::$Language->Date($v['date'],'fdt'),'center'),
					$fs ? rtrim($fs,', ') : '<i>'.static::$lang['no'].'</i>',
					$Lst('func',
						array($v['_aedit'],$ltpl['edit'],$images.'edit.png'),
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					),
					Eleanor::Check('mass[]',false,array('value'=>$k))
				);

			}
		}
		else
			$Lst->empty(static::$lang['mnf']);

		return Eleanor::$Template->Cover(
		'<form method="post">
			<table class="tabstyle tabform" id="ftable">
				<tr class="infolabel"><td colspan="2"><a href="#">'.$ltpl['filters'].'</a></td></tr>
				<tr>
					<td><b>'.static::$lang['forum'].'<br />'.Eleanor::Select('fi[forums]',Eleanor::Option(static::$lang['dnm'],0).$fiforums).'</td>
					<td style="text-align:center;vertical-align:middle">'.Eleanor::Button($ltpl['apply']).'</td>
				</tr>
			</table>
<script type="text/javascript">//<![CDATA[
$(function(){
	var fitrs=$("#ftable tr:not(.infolabel)");
	$("#ftable .infolabel a").click(function(){
		fitrs.toggle();
		$("#ftable .infolabel a").toggleClass("selected");
		return false;
	})'.(isset($qs['']['fi']) ? '' : '.click()').';
	One2AllCheckboxes("#checks-form","#mass-check","[name=\"mass[]\"]",true);
});//]]></script>
		</form>
		<form id="checks-form" action="'.$links['form_items'].'" method="post" onsubmit="return (CheckGroup(this) && ($(\'select\',this).val()==\'dr\' || confirm(\''.$ltpl['are_you_sure'].'\')))">'
		.$Lst->end()
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf(static::$lang['mpp'],$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option(static::$lang['delete'],'k')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		Страница правки модератора
		$id идентификатор редактируемого модератора, если $id==0 значит модератор создается
		$controls Массив контролов разрешений
		$values массив значений полей, ключи:
			users Массив пользователей, которые входят в даного модератора, формат: id=>name
			groups Массив ID групп, которые входят в даного модератора
			forums Массив ID форумов, которые модерирует даный модератор
			descr Описание модератора (только для администратора)
			Так же этот массив содержат ключи со зданиями для массива $controls
		$forums Option-ы для Select-а выбора форумов, которые модерирует даный модератор
		$errors Массив ошибок
		$bypost Признак того, что даные нужно брать из POST запроса
		$back URL возврата
		$links Перечень необходимых ссылок, массив с ключами:
			delete Ссылка на удаление модератора или false
	*/
	public static function AddEditModer($id,$controls,$values,$forums,$errors,$bypost,$back,$links)
	{
		static::Menu($id ? 'edit-moder' : 'add-moder',true);
		$ltpl=Eleanor::$Language['tpl'];
		$Lst=Eleanor::LoadListTemplate('table-form');

		$users=array();
		if(count($values['users'])>0)
			foreach($values['users'] as $k=>&$v)
				$users[]=Eleanor::$Template->Author(array('names[]'=>$v),array('users[]'=>$k),2).' <a href="#" class="del-moder">'.static::$lang['delete'].'</a>';
		else
			$users[]=Eleanor::$Template->Author(array('names[]'=>''),array('users[]'=>''),2).' <a href="#" class="del-moder">'.static::$lang['delete'].'</a>';

		$general=(string)$Lst->begin()
			->item(array(static::$lang['groups'],Eleanor::Items('groups',UserManager::GroupsOpts($values['groups']),array('tabindex'=>1))))
			->item(array(static::$lang['users'],'<ul id="moders"><li>'.join('</li><li>',$users).'</li></ul><a href="#" id="add-moder" style="font-weight:bold;float:right">'.static::$lang['add'].'</a>'))
			->item(static::$lang['forums'],Eleanor::Items('forums',$forums,array('tabindex'=>3)))
			->item(array(static::$lang['note'],Eleanor::Text('descr',$values['descr'],array('tabindex'=>4)),'descr'=>static::$lang['internal']))
			->end();

		$Lst->begin();
		foreach($controls as $k=>&$v)
			if(is_array($v) and isset($values[$k]))
				$Lst->item(array($v['title'],Eleanor::$Template->LangEdit($values[$k],null),'tip'=>$v['descr']));
			elseif(is_string($v))
				$Lst->head($v);
		$rights=(string)$Lst->end();

		if($back)
			$back=Eleanor::Input('back',$back,array('type'=>'hidden'));

		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		return Eleanor::$Template->Cover(
			$Lst->form()
			->tabs(
				array(static::$lang['mains'],$general),
				array(static::$lang['moderrights'],$rights)
			)
			->submitline(
				$back
				.Eleanor::Button('OK','submit',array('tabindex'=>100))
				.($id ? ' '.Eleanor::Button($ltpl['delete'],'button',array('onclick'=>'window.location=\''.$links['delete'].'\'','tabindex'=>101)) : '')
			)
			->endform(),
			$errors
		).'<script type="text/javascript">/*<![CDATA[*/$(function(){ AddEditModer() })//]]></script>';
	}

	/*
		Страница удаления модератора
		$moder массив параметров удаляемого модератора
			users Массив пользователей, которые входят в даного модератора, формат: ID=>array(), ключи внутреннего массива:
				_aedit Ссылка на редактирование пользователя форума
				full_name Полное имя пользователя
				name Имя пользователя (небезопасный HTML!)
			groups Массив групп пользователей, которые входят в даного модератора, формат: ID=>array(), ключи внутреннего массива:
				_aedit Ссылка на группы пользователей форума
				title Название группы
				html_pref HTML префикс группы
				html_end HTML окончание группы
			forums Массив форумов, в которых модерирует даный модератор, формат: ID=>array(), ключи внутреннего массива:
				_aedit Ссылка на редактирование форума
				title Название форума
		$back URL возврата
	*/
	public static function DeleteModerator($moder,$back)
	{
		static::Menu('delete-moder');
		$groups=$forums=$users='';
		foreach($moder['forums'] as &$v)
			$forums.='<a href="'.$v['_aedit'].'">'.$v['title'].'</a>, ';

		foreach($moder['groups'] as &$v)
			$groups.='<a href="'.$v['_aedit'].'">'.$v['html_pref'].$v['title'].$v['html_end'].'</a>, ';

		foreach($moder['users'] as &$v)
			$users.='<a href="'.$v['_aedit'].'">'.htmlspecialchars($v['name'],ELENT,CHARSET).'</a>, ';

		$gdc=static::$lang['gdc'];
		return Eleanor::$Template->Cover(Eleanor::$Template->Confirm(
			$gdc($forums,$groups,$users),
			$back
		));
	}

	/*
		Приватно. Прикрепление мини-формы для выбора прав групп определенного форума. Forum-Group-Rights
		$F Функция-генератор ссылок на установку прав групп в конкретном форуме, параметры: ID форума, ID группы
		$g ID выбранной группы
	*/
	private static function FGR($F,$g=0)
	{
		return'<div style="position:absolute;display:none;background:#F6F6F6;border:1px solid #A1A1A1" id="fgr" data-url="'.$F('_forum_','_group_').'">'
			.Eleanor::Select('get',UserManager::GroupsOpts($g)).Eleanor::Button(static::$lang['setrights'],'button')
			.'</div><script type="text/javascript">/*<![CDATA[*/$(function(){ FGC() })//]]></script>';
	}

	/*
		Страница настройки прав конкретной группы в конкретном форуме
		$forum Массив даных форума, ключи:
			id ID форума
			title Название форума
			parent ID родителя
			parents массив всех родителей форума
		$group Массив даных группы, ключи:
			title Нзвание группы
			html_pref HTML префикс группы
			html_end HTML окончание группы
		$controls Перечень контролов разрешений в соответствии с классом контролов. Если какой-то элемент массива не является массивом, значит это заголовок подгруппы контролов
		$values Результирующий HTML код контролов, который необходимо вывести на странице. Ключи даного массива совпадают с ключами $controls
		$haschild Признак того, что текущий форум имеет подфорумы
		$inherits Признак того, что подфорумы текущего форума наследуют разрешения текущего форума
		$inherit Массив с именами контролов (из массива $controls) настройки которых наследуются из форума-родителя
		$navi Массив, хлебные крошки навигации. Формат ID=>array(), ключи:
			title Заголовок крошки
			_a Ссылка на подпункты даной крошки. Может быть равно false
		$errors Массив ошибок
		$saved Флаг успешного сохранения прав
		$back URL возврата
		$links Перечень необходимых ссылок, массив с ключами:
			forum_groups_rights Функция-генератор ссылок на установку прав групп в конкретном форуме, параметры: ID форума, ID группы
	*/
	public static function ForumGroupRights($forum,$group,$controls,$values,$haschild,$inherits,$inherit,$navi,$errors,$saved,$back,$links)
	{
		static::Menu('fgr',true);
		$nav=array();
		foreach($navi as &$v)
			$nav[]='<a href="'.$v['_afgr'].'">'.$v['title'].'</a>';

		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin(array('id'=>'fg'))
			->head(($nav ? join(' &raquo; ',$nav).' &raquo; ' : '').$forum['title'].': <span style="cursor:pointer" class="fgr" title="'.static::$lang['oprgr'].'" data-id="'.$forum['id'].'">'.$group['html_pref'].$group['title'].$group['html_end'].'</span>');

		if($haschild)
			$Lst->item('<span style="color:darkred">'.static::$lang['atsf'].'</span>',Eleanor::Check('subs',$inherits));

		foreach($controls as $k=>&$v)
			if(is_array($v) and isset($values[$k]))
				$Lst->item(array($v['title'].Eleanor::Check('inherit[]',in_array($k,$inherit),array('style'=>'display:none','value'=>$k)),'<div>'.$values[$k].'</div>','tip'=>$v['descr']));
			elseif(is_string($v))
				$Lst->head($v);

		$Lst->end()
			->submitline(
				($back ? Eleanor::Input('back',$back,array('type'=>'hidden')) : '')
				.Eleanor::Button('OK','submit',array('tabindex'=>100))
			)->endform();

		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		return Eleanor::$Template->Cover(
			($saved ? Eleanor::$Template->Message(static::$lang['saved'].($back ? '<br /><a href="'.$back.'">'.static::$lang['back'].'</a>' : ''),'info') : '')
			.$Lst,
			$errors
		).static::FGR($links['forum_groups_rights'],$group['id'])
		.'<script type="text/javascript">/*<![CDATA[*/$(function(){ ForumGroupRights('.$forum['parent'].') })//]]></script>';
	}

	/*
		Удаление форума
		$forum Массив даных удаляемого форума
			id ID форума
			title Название форума
		$values Массив значений полей, ключи:
			trash ID форума, куда могут быть перемещены темы с удаляемого форума
		$back URL возврата
	*/
	public static function DeleteForm($forum,$values,$back,$errors)
	{
		static::Menu('delete-forum');
		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin()
			->item(static::$lang['puttopics'],Eleanor::Select('trash',Eleanor::Option(static::$lang['deltopics'],0,$values['trash']==0).$GLOBALS['Eleanor']->Forum->Forums->SelectOptions($values['trash'],$forum['id'])))
			->end()
			->submitline(Eleanor::Button(static::$lang['delete']).($back ? ' '.Eleanor::Button(static::$lang['cancel'],'button',array('onclick'=>'location.href="'.$back.'"')) : ''))
			->endform();

		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		return Eleanor::$Template->Cover(
			Eleanor::$Template->Message(sprintf(static::$lang['dfc'],$forum['title']),'info').$Lst,
			$errors
		);
	}

	/*
		Процессе удаления форума
		$forum Массив даных удаляемого форума
			id ID форума
			title Название форума
		$info Массив с даными об удалении, ключи:
			total Количество тем, которые нужно перенести (удалить) в связи с удалением форума, в котором они находились
			done Количество перемещенных (удаленных) тем
		$links Массив ссылок, ключи:
			go URL перехода к очередной части удаления
	*/
	public static function DeleteProcess($forum,$info,$links)
	{
		static::Menu();
		return Eleanor::$Template->RedirectScreen($links['go'],5)->Cover(
			Eleanor::$Template->Message(sprintf(static::$lang['fdels'],$forum['title']).'<br /><progress max="'.$info['total'].'" value="'.$info['done'].'" style="width:100%">'.($info['total']==0 ? 100 : round($info['done']/$info['total']*100)).'%</progress>','info')
		);
	}

	/*
		Финиш удаления форума
		$forum Массив даных УЖЕ удаленного форума
			id ID форума
			title Название форума
		$back URL возврата
	*/
	public static function DeleteComplete($forum,$back)
	{
		static::Menu('delete-forum');
		return Eleanor::$Template->Cover(
			Eleanor::$Template->Message(sprintf(static::$lang['fdeleted'],$forum['title']).($back ? '<br /><a href="'.$back.'">'.static::$lang['back'].'</a>' : ''),'info')
		);
	}

	/*
		Шаблон отображения списка префиксов тем
		$items Массив префиксов тем. Формат: ID=>array(), ключи внутреннего массива:
			title Название префикса
			forums Массив ID форумов, в которых существует даный префикс
			_adel Ссылка на удаление префикса
			_aedit Ссылка на правку префикса
		$forums Массив с подробной информацией о форумах, в которых существуют префиксы даного списка. Формат: ID=>array(), ключи внутреннего массива:
			title Название форума
			_aedit Ссылка на правку форума
		$fiforums Option-ы для Select-а фильтра модераторов по форуму
		$cnt Количество страниц всего
		$pp Количество префиксов тем на страницу
		$page Номер текущей страницы, на которой мы сейчас находимся
		$qs Массив параметров адресной строки для каждого запроса
		$links Перечень необходимых ссылок, массив с ключами:
			sort_id Ссылка на сортировку списка $items по ID (возрастанию/убыванию в зависимости от текущей сортировки)
			sort_title Ссылка на сортировку списка $items по названию (возрастанию/убыванию в зависимости от текущей сортировки)
			form_items Ссылка для параметра action формы, внутри которой происходит отображение перечня $items
			pp Фукнция-генератор ссылок на изменение количества модераторов отображаемых на странице
			first_page Ссылка на первую страницу пагинатора
			pages Функция-генератор ссылок на остальные страницы
	*/
	public static function Prefixes($items,$forums,$fiforums,$cnt,$pp,$page,$qs,$links)
	{
		static::Menu('prefixes');

		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$Lst=Eleanor::LoadListTemplate('table-list',4)
			->begin(
				array(static::$lang['prefix'],'href'=>$links['sort_title']),
				static::$lang['forums'],
				array($ltpl['functs'],'href'=>$links['sort_id']),
				array(Eleanor::Check('mass',false,array('id'=>'mass-check')),20)
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			foreach($items as $k=>&$v)
			{
				$fs='';

				foreach($v['forums'] as &$f)
					if(isset($forums[$f]))
						$fs.='<a href="'.$forums[$f]['_aedit'].'">'.$forums[$f]['title'].'</a>, ';

				$Lst->item(
					$v['title'],
					$fs ? rtrim($fs,', ') : '<i>'.static::$lang['no'].'</i>',
					$Lst('func',
						array($v['_aedit'],$ltpl['edit'],$images.'edit.png'),
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					),
					Eleanor::Check('mass[]',false,array('value'=>$k))
				);

			}
		}
		else
			$Lst->empty(static::$lang['pnf']);

		$qs+=array(''=>array());
		$qs['']+=array('fi'=>array());
		$fs=(bool)$qs['']['fi'];
		$qs['']['fi']+=array(
			'title'=>false,
		);

		return Eleanor::$Template->Cover(
		'<form method="post">
			<table class="tabstyle tabform" id="ftable">
				<tr class="infolabel"><td colspan="2"><a href="#">'.$ltpl['filters'].'</a></td></tr>
				<tr>
					<td><b>'.static::$lang['design'].'</b><br />'.Eleanor::Input('fi[title]',$qs['']['fi']['title']).'</td>
					<td><b>'.static::$lang['forum'].'<br />'.Eleanor::Select('fi[forums]',Eleanor::Option(static::$lang['dnm'],0).$fiforums).'</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align:center">'.Eleanor::Button($ltpl['apply']).'</td>
				</tr>
			</table>
<script type="text/javascript">//<![CDATA[
$(function(){
	var fitrs=$("#ftable tr:not(.infolabel)");
	$("#ftable .infolabel a").click(function(){
		fitrs.toggle();
		$("#ftable .infolabel a").toggleClass("selected");
		return false;
	})'.($fs ? '' : '.click()').';
	One2AllCheckboxes("#checks-form","#mass-check","[name=\"mass[]\"]",true);
});//]]></script>
		</form>
		<form id="checks-form" action="'.$links['form_items'].'" method="post" onsubmit="return (CheckGroup(this) && ($(\'select\',this).val()==\'dr\' || confirm(\''.$ltpl['are_you_sure'].'\')))">'
		.$Lst->end()
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf(static::$lang['ppp'],$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option(static::$lang['delete'],'k')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		Страница правки префикса
		$id идентификатор редактируемого префикса, если $id==0 значит префикс создается
		$values массив значений полей
			Общие ключи:
			forums Массив идентификаторов форумов, в которых используются даный префикс

			Языковые ключи:
			title Название префикса

			Специальные ключи:
			_onelang Флаг моноязычной новости при включенной мультиязычности
		$forums Option-ы для Select-а выбора форумов, в которых используется даный префикс
		$errors Массив ошибок
		$bypost Признак того, что даные нужно брать из POST запроса
		$back URL возврата
		$links Перечень необходимых ссылок, массив с ключами:
			delete Ссылка на удаление модератора или false
	*/
	public static function AddEditPrefix($id,$values,$forums,$errors,$bypost,$back,$links)
	{
		static::Menu($id ? 'edit-prefix' : 'add-prefix');

		$ltpl=Eleanor::$Language['tpl'];
		if(Eleanor::$vars['multilang'])
		{
			$ml=array();
			foreach(Eleanor::$langs as $k=>&$v)
				$ml['title'][$k]=Eleanor::Input('title['.$k.']',$GLOBALS['Eleanor']->Editor->imgalt=Eleanor::FilterLangValues($values['title'],$k),array('tabindex'=>2));
		}
		else
			$ml=array(
				'title'=>Eleanor::Input('title',$GLOBALS['Eleanor']->Editor->imgalt=$values['title'],array('tabindex'=>2,'id'=>'title')),
			);

		if($back)
			$back=Eleanor::Input('back',$back,array('type'=>'hidden'));
		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin()
			->item(static::$lang['forums'],Eleanor::Items('forums',$forums,array('tabindex'=>1)))
			->item($ltpl['title'],Eleanor::$Template->LangEdit($ml['title'],null));

		if(Eleanor::$vars['multilang'])
			$Lst->item($ltpl['set_for_langs'],Eleanor::$Template->LangChecks($values['_onelang'],$values['_langs'],null,3));

		$Lst->end()
			->submitline(
				$back
				.Eleanor::Button($id>0 ? static::$lang['save-prefix'] : static::$lang['add-prefix'],'submit',array('tabindex'=>14))
				.($id ? ' '.Eleanor::Button($ltpl['delete'],'button',array('onclick'=>'window.location=\''.$links['delete'].'\'')) : '')
			)
			->endform();

		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		return Eleanor::$Template->Cover((string)$Lst,$errors,'error');
	}

	/*
		Страница удаления префикса тем
		$prefix массив параметров удаляемого префикса
			title Название префикса
			forums Массив форумов, в которых используется даный префиксов, формат: ID=>array(), ключи внутреннего массива:
				_aedit Ссылка на редактирование форума
				title Название форума
		$back URL возврата
	*/
	public static function DeletePrefix($prefix,$back)
	{
		static::Menu('delete-prefix');
		foreach($prefix['forums'] as &$v)
			$v='<a href="'.$v['_aedit'].'">'.$v['title'].'</a>';

		$pdf=static::$lang['pdf'];
		return Eleanor::$Template->Cover(Eleanor::$Template->Confirm(
			$pdf($prefix['title'],$prefix['forums']),
			$back
		));
	}

	/*
		Страница просмотра аттачей
		#ToDo!
	*/
	public static function Files($items,$forums,$topics,$fiforums,$cnt,$pp,$page,$qs,$links)
	{
		static::Menu('files');

		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$Lst=Eleanor::LoadListTemplate('table-list',6)
			->begin(
				'Файл',
				array('Дата','href'=>$links['sort_date']),
				'Форум',
				'Тема',
				array($ltpl['functs'],'href'=>$links['sort_id']),
				array(Eleanor::Check('mass',false,array('id'=>'mass-check')),20)
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			foreach($items as $k=>$v)
				$Lst->item(
					'<a href="'.$v['_adown'].'">'.$v['name'].'</a><br /><span class="small">'.$v['size'].'</span>',#$v['_orig']
					Eleanor::$Language->Date($v['date'],'fdt'),
					isset($forums[ $v['f'] ][ $v['language'] ]) ? '<a href="'.$forums[ $v['f'] ][ $v['language'] ]['_a'].'" target="_blank">'.$forums[ $v['f'] ][ $v['language'] ]['title'].'</a>' : '<i>нет</i>',
					isset($topics[ $v['t'] ]) ? '<a href="'.$topics[ $v['t'] ]['_a'].'" target="_blank">'.$topics[ $v['t'] ]['title'].'</a>' : '<i>нет</i>',
					$Lst('func',
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					),
					Eleanor::Check('mass[]',false,array('value'=>$k))
				);
		}
		else
			$Lst->empty('Файлы не найдены');

		return Eleanor::$Template->Cover(
		'<form method="post">
			<table class="tabstyle tabform" id="ftable">
				<tr class="infolabel"><td colspan="2"><a href="#">'.$ltpl['filters'].'</a></td></tr>
				<tr>
					<td><b>Форум<br />'.Eleanor::Select('fi[forums]',Eleanor::Option('-не важно-',0).$fiforums).'</td>
					<td>'.Eleanor::Button($ltpl['apply']).'</td>
				</tr>
			</table>
<script type="text/javascript">//<![CDATA[
$(function(){
	var fitrs=$("#ftable tr:not(.infolabel)");
	$("#ftable .infolabel a").click(function(){
		fitrs.toggle();
		$("#ftable .infolabel a").toggleClass("selected");
		return false;
	})'.(isset($qs['']['fi']) ? '' : '.click()').';
	One2AllCheckboxes("#checks-form","#mass-check","[name=\"mass[]\"]",true);
});//]]></script>
		</form>
		<form id="checks-form" action="'.$links['form_items'].'" method="post" onsubmit="return (CheckGroup(this) && ($(\'select\',this).val()==\'dr\' || confirm(\''.$ltpl['are_you_sure'].'\')))">'
		.$Lst->end()
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf('Файлов на страницу: %s',$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option('Удалить','k')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		Страница удаления аттача
		#ToDo!
	*/
	public static function DeleteAttach($attach,$back)
	{
		static::Menu('delete-attach');

		return Eleanor::$Template->Cover(Eleanor::$Template->Confirm(
			'Вы действительно хотить аттач?',
			$back
		));
	}

	/*
		Страница просмотра подписок на форумы
		#ToDo!
	*/
	public static function FSubscriptions($items,$forums,$users,$fiforums,$fiuname,$cnt,$pp,$page,$qs,$links)
	{
		static::Menu('fsubscriptions');

		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$Lst=Eleanor::LoadListTemplate('table-list',7)
			->begin(
				array('Дата','href'=>$links['sort_date']),
				'Форум',
				'Юзер',
				'Интенс.',
				'Посл. отправка',
				$ltpl['functs'],
				array(Eleanor::Check('mass',false,array('id'=>'mass-check')),20)
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			$intencity=array(
				'i'=>'Немедленно',
				'd'=>'Ежедневно',
				'w'=>'Еженедельно',
				'm'=>'Ежемесячно',
				'y'=>'Ежегодно',
			);
			foreach($items as &$v)
				$Lst->item(
					Eleanor::$Language->Date($v['date'],'fdt'),
					isset($forums[ $v['f'] ][ $v['language'] ]) ? '<a href="'.$forums[ $v['f'] ][ $v['language'] ]['_a'].'" target="_blank">'.$forums[ $v['f'] ][ $v['language'] ]['title'].'</a>' : '<i>нет</i>',
					isset($users[ $v['uid'] ]) ? '<a href="'.$users[ $v['uid'] ]['_aedit'].'">'.htmlspecialchars($users[ $v['uid'] ]['name'],ELENT,CHARSET).'</a>' : '<i>нет</i>',
					array(isset($intencity[ $v['intencity'] ]) ? $intencity[ $v['intencity'] ] : '<i>нет</i>','center'),
					'<span title="Следующая отправка '.Eleanor::$Language->Date($v['nextsend'],'fdt').'">'.Eleanor::$Language->Date($v['lastsend'],'fdt').'</span>',
					$Lst('func',
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					),
					Eleanor::Check('mass[]',false,array('value'=>$v['f'].'-'.$v['language'].'-'.$v['u']))
				);
		}
		else
			$Lst->empty('Подписки на форумы не найдены');

		$qs+=array(''=>array());
		$qs['']+=array('fi'=>array());
		$fs=(bool)$qs['']['fi'];
		$qs['']['fi']+=array(
			'u'=>false,
		);

		return Eleanor::$Template->Cover(
		'<form method="post">
			<table class="tabstyle tabform" id="ftable">
				<tr class="infolabel"><td colspan="2"><a href="#">'.$ltpl['filters'].'</a></td></tr>
				<tr>
					<td><b>Пользователь</b><br />'.Eleanor::$Template->Author(array(''=>$fiuname),array('fi[u]'=>$qs['']['fi']['u'])).'</td>
					<td><b>Форум<br />'.Eleanor::Select('fi[forums]',Eleanor::Option('-не важно-',0).$fiforums).'</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align:center">'.Eleanor::Button($ltpl['apply']).'</td>
				</tr>
			</table>
<script type="text/javascript">//<![CDATA[
$(function(){
	var fitrs=$("#ftable tr:not(.infolabel)");
	$("#ftable .infolabel a").click(function(){
		fitrs.toggle();
		$("#ftable .infolabel a").toggleClass("selected");
		return false;
	})'.($fs ? '' : '.click()').';
	One2AllCheckboxes("#checks-form","#mass-check","[name=\"mass[]\"]",true);
});//]]></script>
		</form>
		<form id="checks-form" action="'.$links['form_items'].'" method="post" onsubmit="return (CheckGroup(this) && ($(\'select\',this).val()==\'dr\' || confirm(\''.$ltpl['are_you_sure'].'\')))">'
		.$Lst->end()
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf('Подписок на страницу: %s',$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option('Удалить','k')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		Страница удаления подписки пользователя на форум
		#ToDo!
	*/
	public static function DeleteFS()
	{

	}

	/*
		Страница просмотра подписок на темы
		#ToDo!
	*/
	public static function TSubscriptions($items,$forums,$users,$topics,$fiforums,$fiuname,$fitopic,$cnt,$pp,$page,$qs,$links)
	{
		static::Menu('tsubscriptions');

		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$Lst=Eleanor::LoadListTemplate('table-list',7)
			->begin(
				array('Дата','href'=>$links['sort_date']),
				'Тема',
				'Юзер',
				'Интенс.',
				'Посл. отправка',
				$ltpl['functs'],
				array(Eleanor::Check('mass',false,array('id'=>'mass-check')),20)
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			$intencity=array(
				'i'=>'Немедленно',
				'd'=>'Ежедневно',
				'w'=>'Еженедельно',
				'm'=>'Ежемесячно',
				'y'=>'Ежегодно',
			);
			foreach($items as &$v)
			{
				$topic=isset($topics[ $v['t'] ]) ? $topics[ $v['t'] ] : false;
				$forum=$topic && isset($forums[ $topic['f'] ][ $topic['language'] ]) ? $forums[ $topic['f'] ][ $topic['language'] ] : false;
				$Lst->item(
					Eleanor::$Language->Date($v['date'],'fdt'),
					($topic ? '<a href="'.$topic['_a'].'" target="_blank">'.$topic['title'].'</a>' : '<i>нет</i>')
					.($forum ? '<br /><span class="small"><a href="'.$forum['_a'].'" target="_blank">'.$forum['title'].'</a></span>' : ''),
					isset($users[ $v['uid'] ]) ? '<a href="'.$users[ $v['uid'] ]['_aedit'].'">'.htmlspecialchars($users[ $v['uid'] ]['name'],ELENT,CHARSET).'</a>' : '<i>нет</i>',
					array(isset($intencity[ $v['intensity'] ]) ? $intencity[ $v['intensity'] ] : '<i>нет</i>','center'),
					array((int)$v['lastsend']>0 ? '<span title="Следующая отправка '.Eleanor::$Language->Date($v['nextsend'],'fdt').'">'.Eleanor::$Language->Date($v['lastsend'],'fdt').'</span>' : '<i>нет</i>','center'),
					$Lst('func',
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					),
					Eleanor::Check('mass[]',false,array('value'=>$v['t'].'-'.$v['uid']))
				);
			}
		}
		else
			$Lst->empty('Подписки на темы не найдены');

		$qs+=array(''=>array());
		$qs['']+=array('fi'=>array());
		$fs=(bool)$qs['']['fi'];
		$qs['']['fi']+=array(
			'u'=>false,
			't'=>false,
		);

		return Eleanor::$Template->Cover(
		'<form method="post">
			<table class="tabstyle tabform" id="ftable">
				<tr class="infolabel"><td colspan="2"><a href="#">'.$ltpl['filters'].'</a></td></tr>
				<tr>
					<td><b>Пользователь</b><br />'.Eleanor::$Template->Author(array(''=>$fiuname),array('fi[u]'=>$qs['']['fi']['u'])).'</td>
					<td><b>Форум<br />'.Eleanor::Select('fi[forums]',Eleanor::Option('-не важно-',0).$fiforums).'</td>
				</tr>
				<tr>
					<td><b>'.($fitopic ? '<a href="'.$fitopic['_a'].'">'.$fitopic['title'].'</a>' : 'ID темы').'<br />'.Eleanor::Input('fi[t]',$qs['']['fi']['t'],array('type'=>'number')).'</td>
					<td style="text-align:center">'.Eleanor::Button($ltpl['apply']).'</td>
				</tr>
			</table>
<script type="text/javascript">//<![CDATA[
$(function(){
	var fitrs=$("#ftable tr:not(.infolabel)");
	$("#ftable .infolabel a").click(function(){
		fitrs.toggle();
		$("#ftable .infolabel a").toggleClass("selected");
		return false;
	})'.($fs ? '' : '.click()').';
	One2AllCheckboxes("#checks-form","#mass-check","[name=\"mass[]\"]",true);
});//]]></script>
		</form>
		<form id="checks-form" action="'.$links['form_items'].'" method="post" onsubmit="return (CheckGroup(this) && ($(\'select\',this).val()==\'dr\' || confirm(\''.$ltpl['are_you_sure'].'\')))">'
		.$Lst->end()
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf('Подписок на страницу: %s',$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option('Удалить','k')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		Страница удаления подписки пользователя на тему
		#ToDo!
	*/
	public static function DeleteTS()
	{

	}

	/*
		Страница просмотра изменений репутации
		#ToDo!
	*/
	public static function Reputation($items,$forums,$users,$topics,$fiforums,$fiutoname,$fiufromname,$fitopic,$cnt,$pp,$page,$qs,$links)
	{
		static::Menu('reputation');

		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$Lst=Eleanor::LoadListTemplate('table-list',8)
			->begin(
				array('Кто','colspan'=>2),
				'Дата',
				'Кому',
				'Где',
				'Примечание',
				array($ltpl['functs'],'href'=>$links['sort_id']),
				array(Eleanor::Check('mass',false,array('id'=>'mass-check')),20)
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			foreach($items as &$v)
			{
				$ist=isset($topics[ $v['t'] ]);
				$Lst->item(
					$v['value']>1 ? '<b style="color:green">'.$v['value'].'</b>' : '<b style="color:red">'.$v['value'].'</b>',
					isset($users[ $v['from'] ]) ? '<a href="'.$users[ $v['from'] ]['_a'].'">'.htmlspecialchars($users[ $v['from'] ]['name'],ELENT,CHARSET).'</a>' : $v['from_name'],
					Eleanor::$Language->Date($v['date'],'fdt'),
					isset($users[ $v['to'] ]) ? '<a href="'.$users[ $v['to'] ]['_a'].'">'.htmlspecialchars($users[ $v['from'] ]['name'],ELENT,CHARSET).'</a>' : '<i>нет</i>',
					($ist ? '<a href="'.$v['_a'].'" target="_blank">'.$topics[ $v['t'] ]['title'].'</a>' : '<i>нет</i>')
					.($ist && isset($forums[ $v['f'] ][ $v['language'] ]) ? '<br /><span class="small"><a href="'.$forums[ $v['f'] ][ $v['language'] ]['_a'].'" target="_blank">'.$forums[ $v['f'] ][ $v['language'] ]['title'].'</a></span>' : ''),
					$v['comment'].($v['value'] ? '<hr />' .$v['value']: ''),
					$Lst('func',
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					),
					Eleanor::Check('mass[]',false,array('value'=>$v['f'].'-'.$v['language'].'-'.$v['uid']))
				);
			}
		}
		else
			$Lst->empty('Изменения репутации не найдены');

		$qs+=array(''=>array());
		$qs['']+=array('fi'=>array());
		$fs=(bool)$qs['']['fi'];
		$qs['']['fi']+=array(
			'from'=>false,
			'to'=>false,
			't'=>false,
			'date'=>false,
		);

		return Eleanor::$Template->Cover(
		'<form method="post">
			<table class="tabstyle tabform" id="ftable">
				<tr class="infolabel"><td colspan="4"><a href="#">'.$ltpl['filters'].'</a></td></tr>
				<tr>
					<td><b>От кого</b><br />'.Eleanor::$Template->Author(array(''=>$fiufromname),array('fi[from]'=>$qs['']['fi']['from'])).'</td>
					<td><b>Кому</b><br />'.Eleanor::$Template->Author(array(''=>$fiutoname),array('fi[to]'=>$qs['']['fi']['to'])).'</td>
					<td><b>'.($fitopic ? '<a href="'.$fitopic['_a'].'">'.$fitopic['title'].'</a>' : 'ID темы').'<br />'.Eleanor::Input('fi[t]',$qs['']['fi']['t'],array('type'=>'number')).'</td>
					<td><b>Дата<br />'.Dates::Calendar('fi[date]',$qs['']['fi']['date'],false,array('style'=>'width:100px')).'</td>
				</tr>
				<tr>
					<td colspan="3"><b>Форум<br />'.Eleanor::Select('fi[forums]',Eleanor::Option('-не важно-',0).$fiforums).'</td>
					<td style="text-align:center">'.Eleanor::Button($ltpl['apply']).'</td>
				</tr>
			</table>
<script type="text/javascript">//<![CDATA[
$(function(){
	var fitrs=$("#ftable tr:not(.infolabel)");
	$("#ftable .infolabel a").click(function(){
		fitrs.toggle();
		$("#ftable .infolabel a").toggleClass("selected");
		return false;
	})'.($fs ? '' : '.click()').';
	One2AllCheckboxes("#checks-form","#mass-check","[name=\"mass[]\"]",true);
});//]]></script>
		</form>
		<form id="checks-form" action="'.$links['form_items'].'" method="post" onsubmit="return (CheckGroup(this) && ($(\'select\',this).val()==\'dr\' || confirm(\''.$ltpl['are_you_sure'].'\')))">'
		.$Lst->end()
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf('Подписок на страницу: %s',$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option('Удалить','k')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		Страница удаления репутации
		#ToDo!
	*/
	public static function DeleteReputation()
	{

	}

	/*
		Страница обслуживания форума
		#ToDo!
	*/
	public static function Tasks($values,$statuses,$opforums,$errors,$forums)
	{
		static::Menu('tasks');
		$progress=false;
		$Lst=Eleanor::LoadListTemplate('table-form')

		#Пересчет тем в форумах
			->form()
			->begin()
			->head('Пересчет тем в форумах');
		if(isset($statuses['rectop']))
		{
			$last='';
			foreach($statuses['rectop'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="Дата завершения">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span> ';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="Дата очередного обновления">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="Дата очередного обновления">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> ';
					break;
					default:
						$color='black';
						$date='<span title="Дата начала">'.Eleanor::$Language->Date($v['start'],'fdt').'</span> ';
				}
				if($v['settings']['forums'])
					foreach($v['settings']['forums'] as $f)
					{
						if(isset($forums[ $f ]))
							$date.='<a href="'.$forums[ $f ]['_aedit'].'">'.$forums[ $f ]['title'].'</a>, ';
					}
				else
					$date.=' <i>все форумы</i>';
				$last.='<li style="color:'.$color.'">'.rtrim($date,', ').'</li>';
			}
			$Lst->item('Предыдущие запуски',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['rectop']))
			$Lst->item('Ошибки',Eleanor::$Template->Message($errors['rectop'],'error'));
		$Lst->item(array('Форумы',Eleanor::Items('rectop',$opforums['rectop']),'descr'=>'Выберите форумы, в которых нужно пересчитать темы либо не выбирайте ничего для пересчета тем во всех форумах'))
			->end()
			->submitline(Eleanor::Button('Запустить'))
			->endform()

		#Пересчет постов в темах
			->form()
			->begin()
			->head('Пересчет постов в темах');
		if(isset($statuses['recposts']))
		{
			$last='';
			foreach($statuses['recposts'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="Дата завершения">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span> ';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="Дата очередного обновления">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="Дата очередного обновления">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> ';
					break;
					default:
						$color='black';
						$date='<span title="Дата начала">'.Eleanor::$Language->Date($v['start'],'fdt').'</span> ';
				}
				if(isset($v['settings']['forums']))
					foreach($v['settings']['forums'] as $f)
						if(isset($forums[ $f ]))
							$date.='<a href="'.$forums[ $f ]['_aedit'].'">'.$forums[ $f ]['title'].'</a>, ';
				$last.='<li style="color:'.$color.'">'.rtrim($date,', ').'</li>';
			}
			$Lst->item('Предыдущие запуски',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['recposts']))
			$Lst->item('Ошибки',Eleanor::$Template->Message($errors['recposts'],'error'));
		$Lst->item(array('Форумы',Eleanor::Items('recpostsf',$opforums['recpostsf']),'descr'=>'Выберите форумы, в темах которых нужно пересчитать посты'))
			->item(array('ИЛИ ID тем',Eleanor::Input('recpostst',$values['recpostst']),'descr'=>'либо введите ID  непосредственно тем'))
			->end()
			->submitline(Eleanor::Button('Запустить'))
			->endform()

		#Пересчет постов пользователей
			->form()
			->begin()
			->head('Пересчет постов пользователей');
		if(isset($statuses['recuserposts']))
		{
			$last='';
			foreach($statuses['recuserposts'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="Дата завершения">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span> ';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="Дата очередного обновления">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="Дата очередного обновления">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> ';
					break;
					default:
						$color='black';
						$date='<span title="Дата начала">'.Eleanor::$Language->Date($v['start'],'fdt').'</span> ';
				}
				$last.='<li style="color:'.$color.'">'.$date.'</li>';
			}
			$Lst->item('Предыдущие запуски',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['recuserposts']))
			$Lst->item('Ошибки',Eleanor::$Template->Message($errors['recuserposts'],'error'));
		$Lst->item(array('Пользователи',Eleanor::Input('recuserposts',$values['recuserposts']),'descr'=>'Введите ID пользователей, у которых нужно пересчитать посты, формат: 1,3,5-10 Оставьте поле пустым для пересчета постов у всех.'))
			->end()
			->submitline(Eleanor::Button('Запустить'))
			->endform()

		#Обновление последнего ответившего в тему
			->form()
			->begin()
			->head('Обновление последнего ответившего в тему');
		if(isset($statuses['lastposttopic']))
		{
			$last='';
			foreach($statuses['lastposttopic'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="Дата завершения">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span> ';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="Дата очередного обновления">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="Дата очередного обновления">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> ';
					break;
					default:
						$color='black';
						$date='<span title="Дата начала">'.Eleanor::$Language->Date($v['start'],'fdt').'</span> ';
				}
				if(isset($v['settings']['forums']))
					foreach($v['settings']['forums'] as $f)
						if(isset($forums[ $f ]))
							$date.='<a href="'.$forums[ $f ]['_aedit'].'">'.$forums[ $f ]['title'].'</a>, ';
				$last.='<li style="color:'.$color.'">'.rtrim($date,', ').'</li>';
			}
			$Lst->item('Предыдущие запуски',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['lastposttopic']))
			$Lst->item('Ошибки',Eleanor::$Template->Message($errors['lastposttopic'],'error'));
		$Lst->item(array('Форумы',Eleanor::Items('lastposttopicf',$opforums['lastposttopicf']),'descr'=>'Выберите форумы, в темах которых нужно актуализировать информацию о последнем ответившем'))
			->item(array('ИЛИ ID тем',Eleanor::Input('lastposttopict',$values['lastposttopict']),'descr'=>'либо введите ID непосредственно тем'))
			->end()
			->submitline(Eleanor::Button('Запустить'))
			->endform()

		#Обновление последнего ответившего в форум
			->form()
			->begin()
			->head('Обновление последнего ответившего в форум');
		if(isset($statuses['lastpostforum']))
		{
			$last='';
			foreach($statuses['lastpostforum'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="Дата завершения">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span> ';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="Дата очередного обновления">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="Дата очередного обновления">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> ';
					break;
					default:
						$color='black';
						$date='<span title="Дата начала">'.Eleanor::$Language->Date($v['start'],'fdt').'</span> ';
				}
				if($v['settings']['forums'])
					foreach($v['settings']['forums'] as $f)
					{
						if(isset($forums[ $f ]))
							$date.='<a href="'.$forums[ $f ]['_aedit'].'">'.$forums[ $f ]['title'].'</a>, ';
					}
				else
					$date.=' <i>все форумы</i>';
				$last.='<li style="color:'.$color.'">'.rtrim($date,', ').'</li>';
			}
			$Lst->item('Предыдущие запуски',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['lastpostforum']))
			$Lst->item('Ошибки',Eleanor::$Template->Message($errors['lastpostforum'],'error'));
		$Lst->item(array('Форумы',Eleanor::Items('lastpostforum',$opforums['lastpostforum']),'descr'=>'Выберите форумы, в которых нужно обновить информацию о последнем ответившем либо не выбирайте ничего для пересчета тем во всех форумах'))
			->end()
			->submitline(Eleanor::Button('Запустить'))
			->endform()

		#Удаление мертвых файлов
			->form()
			->begin()
			->head('Удаление мертвых файлов');
		if(isset($statuses['removefiles']))
		{
			$last='';
			foreach($statuses['removefiles'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="Дата завершения">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span> ';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="Дата очередного обновления">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="Дата очередного обновления">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> ';
					break;
					default:
						$color='black';
						$date='<span title="Дата начала">'.Eleanor::$Language->Date($v['start'],'fdt').'</span> ';
				}
				$last.='<li style="color:'.$color.'">'.$date.'</li>';
			}
			$Lst->item('Предыдущие запуски',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['removefiles']))
			$Lst->item('Ошибки',Eleanor::$Template->Message($errors['removefiles'],'error'));
		$Lst->item(array('Пользователи',Eleanor::Input('removefilesp',$values['removefilesp']),'descr'=>'Введите ID постовы, у которых нужно искать мертвые файлы, формат: 1,3,5-10. Оставьте поле пустым для поиска мертвых файлов у всех постов.'))
			->end()
			->submitline(Eleanor::Button('Запустить'))
			->endform()

		#Синхронизация пользователей
			->form()
			->begin()
			->head(static::$lang['syncusers']);
		if(isset($statuses['syncusers']))
		{
			$last='';
			foreach($statuses['syncusers'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="'.static::$lang['finishdate'].'">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span>';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="'.static::$lang['dateupdate'].'">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="'.static::$lang['dateupdate'].'">'.Eleanor::$Language->Date($v['date'],'fdt').'</span>';
					break;
					default:
						$color='black';
						$date='<span title="'.static::$lang['begindate'].'">'.Eleanor::$Language->Date($v['start'],'fdt').'</span>';
				}
				if(isset($v['settings']['date']))
					$date.=', '.static::$lang['startedsync'].': '.Eleanor::$Language->Date($v['settings']['date'],'fdt');
				$last.='<li style="color:'.$color.'">'.$date.'</li>';
			}
			$Lst->item(static::$lang['prevrun'],$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['syncusers']))
			$Lst->item(static::$lang['error'],Eleanor::$Template->Message($errors['syncusers'],'error'));
		$Lst->item(static::$lang['sudbs'],Dates::Calendar('syncusersdate',$values['syncusersdate']))
			->end()
			->submitline(Eleanor::Button(static::$lang['run']))
			->endform();

		if($progress)
			$Lst.='<script type="text/javascript">/*<![CDATA[*/ $(function(){ new ProgressList("'.$GLOBALS['Eleanor']->module['name'].'","'.Eleanor::$services['cron']['file'].'"); });//]]></script>';
		return Eleanor::$Template->Cover($Lst);
	}

	/*
		Обертка для настроек
		$c Интерфейс настроек
	*/
	public static function Options($c)
	{
		static::Menu('settings');
		return$c;
	}
}
TplAdminForum::$lang=Eleanor::$Language->Load(dirname(__DIR__).'/langs/forum-admin-*.php',false);