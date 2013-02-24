<?php
/*
	Copyright � Eleanor CMS
	URL: http://eleanor-cms.ru, http://eleanor-cms.com
	E-mail: support@eleanor-cms.ru
	Developing: Alexander Sunvas*
	Interface: Rumin Sergey
	=====
	*Pseudonym
*/
class TplAdminForum
{
	public static
		$lang;

	protected static function Menu($act='')
	{
		$links=&$GLOBALS['Eleanor']->module['links'];
		$GLOBALS['Eleanor']->module['navigation']=array(
			array($links['forums'],'������ �������','act'=>$act=='forums',
				'submenu'=>array(
					array($links['add-forum'],'�������� �����','act'=>$act=='add-forum'),
				),
			),
			array($links['moders'],'������ �����������','act'=>$act=='moders',
				'submenu'=>array(
					array($links['add-moder'],'�������� ����������','act'=>$act=='add-moder'),
				),
			),
			array($links['prefixes'],'�������� ���','act'=>$act=='prefixes',
				'submenu'=>array(
					array($links['add-prefix'],'�������� �������','act'=>$act=='add-prefix'),
				),
			),
			array($links['reputation'],'���������','act'=>$act=='reputation'),
			array($links['groups'],'������� ����� �����','act'=>$act=='groups'),
			array($links['massrights'],'�������� ���������� �����','act'=>$act=='massrights'),
			array($links['users'],'������������','act'=>$act=='users'),
			array($links['letters'],'������� �����','act'=>$act=='letters'),
			array($links['files'],'����������� �����','act'=>$act=='files'),
			array($links['fsubscriptions'],'�������� �� ������','act'=>$act=='fsubscriptions'),
			array($links['tsubscriptions'],'�������� �� ����','act'=>$act=='tsubscriptions'),
			array($links['tasks'],'������������','act'=>$act=='tasks'),
			array($links['options'],'���������','act'=>$act=='options'),
		);
	}

	/*
		�������� ����������� ���� �������
		$items ������ �������. ������: ID=>array(), ����� ����������� �������:
			title �������� ������
			image ���� � ��������-�������� ������, ���� ������ - ������ �������� ���
			pos ����� �����, ��������������� ������� ������
			_aedit ������ �� �������������� ������
			_adel ������ �� �������� ������
			_aparent ������ �� �������� ���������� �������� ������
			_aup ������ �� �������� ������ �����, ���� ����� false - ������ ����� ��� � ��� ��������� � ����� �����
			_adown ������ �� ��������� ������ ����, ���� ����� false - ������ ����� ��� � ��� ��������� � ����� ����
			_aaddp ����� �� ���������� ���������� � ������ ������
		$subitems ������ ���������� ��� ������� �� ������� $items. ������: ID=>array(id=>array(), ...), ��� ID - ������������� ������, id - ������������� ���������. ����� ������� ����������:
			title ��������� ���������
			_aedit ������ �� �������������� ���������
		$navi ������, ������� ������ ���������. ������ ID=>array(), �����:
			title ��������� ������
			_a ������ �� ��������� ����� ������. ����� ���� ����� false
		$cnt ���������� ������� �����
		$pp ���������� ������� �� ��������
		$qs ������ ���������� �������� ������ ��� ������� �������
		$page ����� ������� ��������, �� ������� �� ������ ���������
		$links �������� ����������� ������, ������ � �������:
			sort_title ������ �� ���������� ������ $items �� �������� (�����������/�������� � ����������� �� ������� ����������)
			sort_pos ������ �� ���������� ������ $items �� ������� (�����������/�������� � ����������� �� ������� ����������)
			sort_id ������ �� ���������� ������ $items �� ID (�����������/�������� � ����������� �� ������� ����������)
			form_items ������ ��� ��������� action �����, ������ ������� ���������� ����������� ������� $items
			pp �������-��������� ������ �� ��������� ���������� ������� ������������ �� ��������
			first_page ������ �� ������ �������� ����������
			pages �������-��������� ������ �� ��������� ��������
			forum_groups_rights �������-��������� ������ �� ��������� ���� ����� � ���������� ������, ���������: ID ������, ID ������
	*/
	public static function Forums($items,$subitems,$navi,$cnt,$pp,$qs,$page,$links)
	{
		static::Menu('forums');
		$ltpl=Eleanor::$Language['tpl'];
		$nav=array();
		foreach($navi as &$v)
			$nav[]=$v['_a'] ? '<a href="'.$v['_a'].'">'.$v['title'].'</a>' : '<span class="fgr" title="��������� ����� �����" data-id="'.(isset($qs['parent']) ? $qs['parent'] : 0).'">'.$v['title'].'</span>';

		$Lst=Eleanor::LoadListTemplate('table-list',4)
			->begin(
				array($ltpl['title'],'href'=>$links['sort_title'],'colspan'=>2),
				array('�������',80,'href'=>$links['sort_pos']),
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
					foreach($subitems[$k] as $kk=>&$vv)
						$subs.='<a href="'.$vv['_aedit'].'">'.$vv['title'].'</a>, ';

				$pos=$posasc
					? $Lst('func',
						$v['_aup'] ? array($v['_aup'],'�����',$images.'up.png') : false,
						$v['_adown'] ? array($v['_adown'],'����',$images.'down.png') : false
					)
					: false;

				$Lst->item(
					$v['image'] ? array('<a href="'.$v['_aedit'].'"><img src="'.$v['image'].'" /></a>','style'=>'width:1px') : false,
					array('<a id="id'.$k.'" href="'.$v['_aedit'].'">'.$v['title'].'</a><br /><span class="small"><a href="'.$v['_aparent'].'" style="font-weight:bold">���������</a>: '.rtrim($subs,', ').' <a href="'.$v['_aaddp'].'" title="�������� ��������"><img src="'.$images.'plus.gif'.'" /></a></span>','colspan'=>$v['image'] ? false : 2),
					$pos && $pos[0] ? $pos : array('&empty;','center'),
					$Lst('func',
						'<span class="fgr" title="��������� ����� �����" data-id="'.$k.'"><img src="'.$images.'select_users.png" alt="" /></span>',
						array($v['_aedit'],$ltpl['edit'],$images.'edit.png'),
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					)
				);
			}
		}
		else
			$Lst->empty($nav ? '��������� �� �������' : '������ �� �������');
		return Eleanor::$Template->Cover(
			($nav ? '<table class="filtertable"><tr><td style="font-weight:bold">'.join(' &raquo; ',$nav).'</td></tr></table>' : '')
			.'<form action="'.$links['form_items'].'" method="post">'
			.$Lst->end().'<div class="submitline" style="text-align:left">'.sprintf('������� �� ��������: %s',$Lst->perpage($pp,$links['pp'])).'</div></form>'
			.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page']))
		).self::FGR($links['forum_groups_rights']);
	}

	/*
		�������� ����������/�������������� ������
		$id ������������� �������������� ������, ���� $id==0 ������ ����� �����������
		$values ������ �������� �����
			����� �����:
			parent �������� ������
			pos ������� ������
			is_category ���� ��������� (� �������� ������ ��������� ����, ����� ������ ������ ��������)
			image ������� ������
			inc_posts ���� ��������� �������� ���������
			reputation ���� ��������� ��������� � ���� ������
			hide_attach ���� ��������� �������� �������
			prefixes ������ ��������������� ��������� ���

			�������� �����:
			title �������� ������
			description �������� ������
			uri URI ������
			meta_title ��������� ���� �������� ��� ��������� ������
			meta_descr ���� �������� ������

			����������� �����:
			_onelang ���� ����������� ������� ��� ���������� ���������������
		$errors ������ ������
		$imagopts ����� option-�� ��� select-a � ���������� ����������
		$prefixes ������ ��������� ��������� ������, ������: id=>�������� ��������
		$uploader ��������� ����������
		$bypost ������� ����, ��� ����� ����� ����� �� POST �������
		$back URL ��������
		$links �������� ����������� ������, ������ � �������:
			delete ������ �� �������� ������ ��� false
	*/
	public static function AddEditForum($id,$values,$errors,$imagopts,$prefixes,$uploader,$bypost,$back,$links)
	{
		static::Menu($id ? 'edit-forum' : 'add-forum');

		$ltpl=Eleanor::$Language['tpl'];
		if(Eleanor::$vars['multilang'])
		{
			$ml=array();
			foreach(Eleanor::$langs as $k=>&$v)
			{
				$ml['title'][$k]=Eleanor::Input('title['.$k.']',$GLOBALS['Eleanor']->Editor->imgalt=Eleanor::FilterLangValues($values['title'],$k),array('tabindex'=>5,'id'=>'title-'.$k));
				$ml['uri'][$k]=Eleanor::Input('uri['.$k.']',Eleanor::FilterLangValues($values['uri'],$k),array('onfocus'=>'if(!$(this).val())$(this).val($(\'#title-'.$k.'\').val())','tabindex'=>6));
				$ml['description'][$k]=$GLOBALS['Eleanor']->Editor->Area('description['.$k.']',Eleanor::FilterLangValues($values['description'],$k),array('bypost'=>$bypost,'no'=>array('tabindex'=>7,'rows'=>15)));
				$ml['meta_title'][$k]=Eleanor::Input('meta_title['.$k.']',Eleanor::FilterLangValues($values['meta_title'],$k),array('tabindex'=>8));
				$ml['meta_descr'][$k]=Eleanor::Input('meta_descr['.$k.']',Eleanor::FilterLangValues($values['meta_descr'],$k),array('tabindex'=>9));
			}
		}
		else
			$ml=array(
				'title'=>Eleanor::Input('title',$GLOBALS['Eleanor']->Editor->imgalt=$values['title'],array('tabindex'=>5,'id'=>'title')),
				'uri'=>Eleanor::Input('uri',$values['uri'],array('onfocus'=>'if(!$(this).val())$(this).val($(\'#title\').val())','tabindex'=>6)),
				'description'=>$GLOBALS['Eleanor']->Editor->Area('description',$values['description'],array('bypost'=>$bypost,'no'=>array('tabindex'=>7,'rows'=>15))),
				'meta_title'=>Eleanor::Input('meta_title',$values['meta_title'],array('tabindex'=>8)),
				'meta_descr'=>Eleanor::Input('meta_descr',$values['meta_descr'],array('tabindex'=>9)),
			);

		if($back)
			$back=Eleanor::Input('back',$back,array('type'=>'hidden'));
		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin()

			->head('��������������')
			->item('��������',Eleanor::Select('parent',Eleanor::Option('-���-','',$values['parent']==0).$GLOBALS['Eleanor']->Forum->Forums->SelectOptions($values['parent'],$id),array('tabindex'=>1)))
			->item('�������',Eleanor::Input('pos',$values['pos'],array('tabindex'=>2)))
			->item(array('��� ���������, � �� �����',Eleanor::Check('is_category',$values['is_category'],array('tabindex'=>3)),'tip'=>'� ���������� ��������� ���������, ��������� ���� � ��� ������'))

			->head('���������������� �������������')
			->item('�������',Eleanor::Select('image',Eleanor::Option('-���-','',!$values['image']).$imagopts,array('id'=>'image','tabindex'=>4,'data-path'=>$GLOBALS['Eleanor']->module['config']['logos'])))
			->item('&nbsp;','<img src="images/spacer.png" id="preview" />
<script type="text/javascript">//<![CDATA[
$(function(){
	$("#image").change(function(){
		var val=$(this).val();
		if(val)
			$("#preview").prop("src",$(this).data("path")+val).closest("tr").show();
		else
			$("#preview").prop("src","images/spacer.png").closest("tr").hide();
	}).change();
})//]]></script>')
			->item($ltpl['title'],Eleanor::$Template->LangEdit($ml['title'],null))
			->item('URI',Eleanor::$Template->LangEdit($ml['uri'],null))
			->item('��������',Eleanor::$Template->LangEdit($ml['description'],null))
			->item('Window title',Eleanor::$Template->LangEdit($ml['meta_title'],null))
			->item('Meta description',Eleanor::$Template->LangEdit($ml['meta_descr'],null));

		if(Eleanor::$vars['multilang'])
			$Lst->item($ltpl['set_for_langs'],Eleanor::$Template->LangChecks($values['_onelang'],$values['_langs'],null,10));

		$prs='';
		foreach($prefixes as $k=>&$v)
			$prs.=Eleanor::Option($v,$k,in_array($k,$values['prefixes']));

		$Lst->head('�����')
			->item('�������� ������� ���������',Eleanor::Check('inc_posts',$values['inc_posts'],array('tabindex'=>11)))
			->item('�������� ���������',Eleanor::Check('reputation',$values['reputation'],array('tabindex'=>12)))
			->item(array('������� ��������',Eleanor::Check('hide_attach',$values['hide_attach'],array('tabindex'=>13)),'tip'=>'��������� ���� ����� ������� ����������� �������� ������ �� �������� ������� �����'))
			->item('�������� ���',Eleanor::Items('prefixes',$prs))

			->end()
			->submitline((string)$uploader)
			->submitline(
				$back
				.Eleanor::Button('Ok','submit',array('tabindex'=>14))
				.($id ? ' '.Eleanor::Button($ltpl['delete'],'button',array('onclick'=>'window.location=\''.$links['delete'].'\'')) : '')
			)
			->endform();

		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		return Eleanor::$Template->Cover((string)$Lst,$errors,'error');
	}

	/*
		��������� ��������, ������� ����������, ���� ��� �������������� ������ ������� ��������� �����
		$forum ������ ����� ������, � �������� ��������� �����, �����:
			id ID ������
			title �������� ������
		$values ������ �������� �����, �����:
			trash ID ������, ���� ����� ���� ���������� ���� � ���������� ������
		$langs ������ ��������� ������ ������
		$newlangs ������ ����� ������ ������
		$back URL ��������
	*/
	public static function SaveDeleteForm($forum,$values,$langs,$newlangs,$back,$errors)
	{
		$onelang=$newlangs==array('');
		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin()
			->item('���� ����������� ����',Eleanor::Select('trash',Eleanor::Option('-������� ����-',0,$values['trash']==0).$GLOBALS['Eleanor']->Forum->Forums->SelectOptions($onelang ? $forum['id'] : $values['trash'],$onelang ? array() : $forum['id'])))
			->end()
			->submitline(Eleanor::Button('�������').($back ? ' '.Eleanor::Button('��������','button',array('onclick'=>'location.href="'.$back.'"')) : ''))
			->endform();

		return Eleanor::$Template->Cover(
			Eleanor::$Template->Message('�� ����������� ������� �������� ������ '.join(', ',$langs).' ������ &quot;'.$forum['title'].'&quot;. �������� �����, ���� ����� ���������� ����.','info').$Lst,
			$errors
		);
	}

	/*
		��������� �������� � ������������ � �������� �������� ������
		$forum ������ ����� ������, � �������� ��������� �����, �����:
			id ID ������
			title �������� ������
		$info ������ � ������ �� ��������, �����:
			total ���������� ���, ������� ����� ��������� (�������) � ����� � ��������� ������, � ������� ��� ����������
			done ���������� ������������ (���������) ���
		$langs ������ ��������� ������ ������
		$newlangs ������ ����� ������ ������
		$links ������ ������, �����:
			go URL �������� � ��������� ����� ��������
	*/
	public static function SaveDeleteProcess($forum,$info,$langs,$newlangs,$links)
	{
		return Eleanor::$Template->RedirectScreen($links['go'],5)->Cover(
			Eleanor::$Template->Message('�������� ������ '.join(', ',$langs).' ������ &quot;'.$forum['title'].'&quot; ���������...<br /><progress max="'.$info['total'].'" value="'.$info['done'].'" style="width:100%">'.($info['total']==0 ? 100 : round($info['done']/$info['total']*100)).'%</progress>','info')
		);
	}

	/*
		��������� �������� � ������������ �� ������� ��������� ������ ������
		$forum ������ ����� ������, � �������� ��������� �����, �����:
			id ID ������
			title �������� ������
		$langs ������ ��������� ������ ������
		$newlangs ������ ����� ������ ������
		$back URL ��������
	*/
	public static function SaveDeleteComplete($forum,$langs,$newlangs,$back)
	{
		return Eleanor::$Template->Cover(
			Eleanor::$Template->Message('�������� ������ '.join(', ',$langs).' ������ &quot;'.$forum['title'].'&quot; ������� �������.'.($back ? '<br /><a href="'.$back.'">���������</a>' : ''),'info')
		);
	}

	/*
		�������� ����������� ����� �������������
		$items ������ ����� �������������. ������: ID=>array(), ����� ����������� ������� (���� �����-�� �������� ����� null, ������ �������� �����������):
			title ������� ������
			html_pref HTML ������� ������
			html_end HTML ��������� ������
			parents ������ ��������� ������
			grow_to ������������� ������, � ������� ����� ������������� ���������� ������������� �� ���������� ������������� ���������� ��������� (��� ������ �������)
			grow_after ���������� ��������� �� ������ �������, ������������ ������ ���� ��������� � ������ ������
			supermod ���� ������� ���� ��������������� � ������. ��� ����� ���� ������ ����� ����� ����� ��������������� �� ������
			_aedit - ������ �� �������������� ������
			_aparent - ������ �� �������� ��������
		$subitems ������ �������� ��� ����� �� ������� $items. ������: ID=>array(id=>array(), ...), ��� ID - ������������� ������, id - ������������� ���������. ����� ������� ���������:
			title �������� ���������
			_aedit ������ �� �������������� ���������
		$navi ������, ������� ������ ���������. ������ ID=>array(), �����:
			title - ��������� ������
			_a - ������ ��������� ����� ������. ����� ���� ����� false
		$parents ������ ���������, ��� ����������� �������� �����, � ������, ����� ��� �����������, �����: grow_to, grow_after, supermod - �������� ���� ����
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
				'������',
				'��������������',
				'�����������',
				$ltpl['functs']
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			foreach($items as $k=>$v)
			{
				$subs='';
				if(isset($subitems[$k]))
					foreach($subitems[$k] as $kk=>&$vv)
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
					array($v['html_pref'].$v['title'].$v['html_end'].($subs ? '<br /><span class="small"><a href="'.$v['_aparent'].'" style="font-weight:bold">���������</a>: '.rtrim($subs,', ').'</span>' : ''),'href'=>$v['_aedit']),
					array(Eleanor::$Template->YesNo($v['supermod']),'center'),
					array(isset($items[ $v['grow_to'] ]) ? $items[ $v['grow_to'] ]['html_pref'].$items[ $v['grow_to'] ]['title'].$items[ $v['grow_to'] ]['html_end'].' (����� '.$v['grow_after'].' ���������)' : '<i>���</i>',isset($items[ $v['grow_to'] ]) ? '' : 'center'),
					$Lst('func',
						array($v['_aedit'],$ltpl['edit'],$images.'edit.png')
					)
				);
			}
		}
		else
			$Lst->empty('��������� �� �������');
		return Eleanor::$Template->Cover(($nav ? '<table class="filtertable"><tr><td style="font-weight:bold">'.join(' &raquo; ',$nav).'</td></tr></table>' : '').$Lst->end());
	}

	/*
		�������� ������ ������
		$group ������ ����� ������ (�� ��� ������), �����:
			parents ������ ��������� ������
			title �������� ������
			html_pref HTML ������� ������
			html_end HTML ��������� ������
		$values ������ �������� �����, �����:
			grow_to ������������� ������, � ������� ����� ���������� ������������ ������� ������ �� ���������� ������������� ���������� ������
			grow_after ���������� ������, �� ���������� ������� ������������ ����� ���������� � ������ ������
			supermod ���� ������ � ������� ���������������
			see_hidden_users ���� ���������� ������������� ������ ������ ������� ������������� �� ������
			moderate ���� ��������� ������������� ������ ������������� ������
			permissions ������ HTML ���������� ��������� ����������
			_inherit ������ ���� ����������� �������� �����
			_inheritp ������ ���� ������������ ����������
		$controls ������ ��������� ����������
		$groups option-� ��� select-� � ������� ������
		$min ���������� ��������� �������� ��� grow_after
		$bypost ������� ����, ��� ����� ����� ����� �� POST �������
		$back URL ��������
		$errors ������ ������
	*/
	public static function EditGroup($group,$values,$controls,$groups,$min,$bypost,$back,$errors)
	{
		static::Menu('edit-group');
		$Lst=Eleanor::LoadListTemplate('table-form');
		$general=(string)$Lst->begin(array('id'=>'tg'))
			->head('�����������')
			->item('� ������'.($group['parents'] ? Eleanor::Check('_inherit[]',in_array('grow_to',$values['_inherit']),array('style'=>'display:none','value'=>'grow_to')) : ''),($group['parents'] ? '<div>' : '').Eleanor::Select('grow_to',Eleanor::Option('-�� ����������-',0,$values['grow_to']==0).$groups).($group['parents'] ? '</div>' : ''))
			->item('�� ���������'.($group['parents'] ? Eleanor::Check('_inherit[]',in_array('grow_after',$values['_inherit']),array('style'=>'display:none','value'=>'grow_after')) : ''),($group['parents'] ? '<div>' : '').Eleanor::Input('grow_after',$min<$values['grow_after'] ? $values['grow_after'] : $min,array('min'=>$min,'type'=>'number')).' ���������.'.($group['parents'] ? '</div>' : ''))
			->head('���������� �����')
			->item('��������������'.($group['parents'] ? Eleanor::Check('_inherit[]',in_array('supermod',$values['_inherit']),array('style'=>'display:none','value'=>'supermod')) : ''),($group['parents'] ? '<div>' : '').Eleanor::Check('supermod',$values['supermod']).($group['parents'] ? '</div>' : ''))
			->item('������ ������� �������������'.($group['parents'] ? Eleanor::Check('_inherit[]',in_array('see_hidden_users',$values['_inherit']),array('style'=>'display:none','value'=>'see_hidden_users')) : ''),($group['parents'] ? '<div>' : '').Eleanor::Check('see_hidden_users',$values['see_hidden_users']).($group['parents'] ? '</div>' : ''))
			->item('������������ ������'.($group['parents'] ? Eleanor::Check('_inherit[]',in_array('moderate',$values['_inherit']),array('style'=>'display:none','value'=>'moderate')) : ''),($group['parents'] ? '<div>' : '').Eleanor::Check('moderate',$values['moderate']).($group['parents'] ? '</div>' : ''))
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
		return Eleanor::$Template->Cover(
			$Lst->form()
			->tabs(
				array('����� ���������',$general),
				array('���������� �� ���������',$perms)
			)
			->submitline($back.Eleanor::Button('OK','submit',array('tabindex'=>100)))
			->endform(),
			$errors
		).'<script type="text/javascript">//<![CDATA[
$(function(){
	var ga=$("input[name=\"grow_after\"]:first"),
		gt=$("select[name=\"grow_to\"]:first").change(function(){
			ga.prop("disabled",$(this).val()==0);
		}).change();
'.($group['parents'] ? 'var Check=function(ftd,state)
		{
			var ch=ftd.find(":checkbox");
			if(typeof state=="undefined")
				state=!ch.prop("checked");
			if(state)
			{
				ftd.css("text-decoration","line-through").prop("title","�����������").next().children("div").hide();
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
		}
		$("#tg tr,#tp tr").find("td:first").filter(function(){
			return $(this).has(":checkbox").size()>0;
		}).click(function(){
			Check($(this));
		}).each(function(){
			Check($(this),$(":checkbox",this).prop("checked"));
		}).css("cursor","pointer");' : '').'
})//]]></script>';
	}

	/*
		�������� ��������� �������� ���� ����� ��� �������
		$controls ������ ��������� ����������
		$values ������ �������� �����, �����:
			rights ������ HTML ���������� ��������� ����������
			inherit ������ ���� ������������ ����������
		$groups option-� ��� select-� � ������� ������
		$forums option-� ��� select-� � ������� �������
		$bypost ������� ����, ��� ����� ����� ����� �� POST �������
		$errors ������ ������
	*/
	public static function MassRights($controls,$values,$groups,$forums,$bypost,$errors)
	{
		static::Menu('massrights');
		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin(array('id'=>'fg'))
			->head('����������')
			->item('������',Eleanor::Items('groups',$groups))
			->item('������',Eleanor::Items('forums',$forums))
			->head('����������');

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

		return Eleanor::$Template->Cover((!$errors && $bypost ? Eleanor::$Template->Message('����� ������� ���������','info') : '').$Lst,$errors)
			.'<script type="text/javascript">//<![CDATA[
$(function(){
	var Check=function(ftd,state)
		{
			var ch=$(ftd).find(":checkbox");
			if(typeof state=="undefined")
				state=!ch.prop("checked");
			if(state)
				ch.end().css("text-decoration","line-through").prop("title","����������� �� ���� ������ � ������������ ������ ��� �� ������� ���� �����").next().children("div").hide();
			else
				ch.end().css("text-decoration","").prop("title","").next().children("div").show();
			ch.prop("checked",state)
		};

		$("#fg tr").find("td:first").filter(function(){
			return $(this).has(":checkbox").size()>0;
		}).click(function(){
			Check(this);
		}).each(function(){
			Check(this,$(":checkbox",this).prop("checked"));
		}).css("cursor","pointer");
})//]]></script>';
	}

	/*
		������ �������� � ��������������� �������� �����
		$controls �������� ��������� � ������������ � ������� ���������. ���� �����-�� ������� ������� �� �������� ��������, ������ ��� ��������� ��������� ���������
		$values �������������� HTML ��� ���������, ������� ���������� ������� �� ��������. ����� ������ ������� ��������� � ������� $controls
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
		������ ����������� ������ ������������� ������
		$items ������ �������������. ������: ID=>array(), ����� ����������� �������:
			full_name ������ ��� ������������
			name ��� ������������ (������������ HTML!)
			email E-mail ������������
			ip IP ����� ������������
			posts ���������� �������� ������ ������������ �� ������
			_asedit ������ �� �������������� ������������ (���������)
			_adel ������ �� �������� ������������ (���������)
			_aedit ������ �� �������� �������������� ������������
			_arecount ������ �� �������� ��������� ������������
			_arep ������ �� ��������� �������� ��������� ������������
		$cnt ���������� ������� �����
		$pp ���������� ������������� ������ �� ��������
		$page ����� ������� ��������, �� ������� �� ������ ���������
		$qs ������ ���������� �������� ������ ��� ������� �������
		$links �������� ����������� ������, ������ � �������:
			sort_posts ������ �� ���������� ������ $items �� ���������� ������ (�����������/�������� � ����������� �� ������� ����������)
			sort_rep ������ �� ���������� ������ $items �� ��������� (�����������/�������� � ����������� �� ������� ����������)
			sort_name ������ �� ���������� ������ $items �� ����� ������������ (�����������/�������� � ����������� �� ������� ����������)
			sort_email ������ �� ���������� ������ $items �� email (�����������/�������� � ����������� �� ������� ����������)
			sort_ip ������ �� ���������� ������ $items �� ip ������ (�����������/�������� � ����������� �� ������� ����������)
			sort_id ������ �� ���������� ������ $items �� ID (�����������/�������� � ����������� �� ������� ����������)
			form_items ������ ��� ��������� action �����, ������ ������� ���������� ����������� ������� $items
			pp �������-��������� ������ �� ��������� ���������� ������������� ������������ �� ��������
			first_page ������ �� ������ �������� ����������
			pages �������-��������� ������ �� ��������� ��������
		$info ������ �������������� ���������, ��������� ����� � ��������:
			REPUTATION ������� ����� �������� � ���, ��� ����������� ��������� � �������������, ������� ���������� � ������� ��������. �����:
				_aedit ������ �� �������������� ������������
				name ��� ������������. ������������ HTML!
				full_name ������ ��� ������������
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
			'b'=>'���������� ��',
			'q'=>'��������� �',
			'e'=>'������������� ��',
			'm'=>'��������',
		);
		foreach($namet as $k=>&$v)
			$finamet.=Eleanor::Option($v,$k,$qs['']['fi']['namet']==$k);

		$Lst=Eleanor::LoadListTemplate('table-list',7)
			->begin(
				array('��� ������������','href'=>$links['sort_name']),
				array('������','href'=>$links['sort_posts']),
				array('���������','href'=>$links['sort_rep']),
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
					'<a href="'.$v['_afedit'].'" id="it'.$k.'">'.$v['name'].'</a>'.($v['name']==$v['full_name'] ? '' : '<br /><i>'.$v['full_name'].'</i>'),
					array($v['posts'],'right'),
					array($v['rep']===null ? '<i>���</i>' : '<a href="'.$v['_arep'].'">'.$v['rep'].'</a>','right'),
					array($v['email'],'center'),
					array($v['ip'],'center','href'=>'http://eleanor-cms.ru/whois/'.$v['ip'],'hrefextra'=>array('target'=>'_blank')),
					$Lst('func',
						array($v['_arecount'],'����������� ���������',$images.'sort.png'),
						array($v['_afedit'],$ltpl['edit'],$images.'edit.png'),
						$v['_adel'] ? array($v['_adel'],$ltpl['delete'],$images.'delete.png') : false
					),
					Eleanor::Check('mass[]',false,array('value'=>$k))
				);
		}
		else
			$Lst->empty('������������ �� �������');

		$messages='';
		foreach($info as $k=>&$v)
			if($k=='REPUTATION')
			{
				$us='';
				foreach($v as $vv)
					$us.='<a href="'.$vv['_aedit'].'">'.htmlspecialchars($vv['name'],ELENT,CHARSET).'</a>, ';
				$messages.=Eleanor::$Template->Message('��������� � �����������'.(count($v)>1 ? '�� ' : '� ').rtrim($us,', ').' �����������','info');
			}

		return Eleanor::$Template->Cover(
			($messages ? '<div id="messages">'.$messages.'</div>' : '')
			.'<form method="post">
			<table class="tabstyle tabform" id="ftable">
				<tr class="infolabel"><td colspan="2"><a href="#">'.$ltpl['filters'].'</a></td></tr>
				<tr>
					<td><b>��� ������������</b><br />'.Eleanor::Select('fi[namet]',$finamet,array('style'=>'width:30%')).Eleanor::Input('fi[name]',$qs['']['fi']['name'],array('style'=>'width:68%')).'</td>
					<td><b>E-mail</b><br />'.Eleanor::Input('fi[email]',$qs['']['fi']['email']).'</td>
				</tr>
				<tr>
					<td><b>IDs</b><br />'.Eleanor::Input('fi[id]',$qs['']['fi']['id']).'</td>
					<td><b>IP</b><br />'.Eleanor::Input('fi[ip]',$qs['']['fi']['ip']).'</td>
				</tr>
				<tr>
					<td><b>�����������</b> �� ��<br />'.Dates::Calendar('fi[regfrom]',$qs['']['fi']['regfrom'],true,array('style'=>'width:35%')).' - '.Dates::Calendar('fi[regto]',$qs['']['fi']['regto'],true,array('style'=>'width:35%')).'</td>
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
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf('������������� �� ��������: %s',$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option('����������� ���������','r')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		�������� ������ ������������ ������
		$users ������ ����� ����������� (�� ��� ������), �����:
			_aedit ������ �� ��������� �������������� ������������
			group ������ ����� �������� ������ ������������, �����
				title �������� ������
				html_pref HTML ������� ������
				html_end HTML ��������� ������
			full_name ������ ��� ������������
			name ��� ������������ (������������ HTML!)
		$values ������ �������� �����, �����:
			posts ���������� ������ � ������������
			restrict_post ���� ������� ���������� ��������� �� ������
			restrict_post_to ����, �� ����������� ������� ������������ ����� ��������� ���������� ������ �� ������
			descr �������� ������������ (������ ��� ������)
		$bypost ������� ����, ��� ����� ����� ����� �� POST �������
		$back URL ��������
		$errors ������ ������
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
			->item('������������','<a href="'.$user['_aedit'].'" title="'.$user['group']['title'].'">'.$user['group']['html_pref'].$user['name'].$user['group']['html_end'].'</a>'.($user['name']==$user['full_name'] ? '' : ' ('.$user['full_name'].')'))
			->item('������ �� ������',Eleanor::Input('posts',$values['posts'],array('min'=>0,'type'=>'number')))
			->item('��������� ���������� ���������',Eleanor::Check('restrict_post',$values['restrict_post']))
			->item('��������� ���������� ��������� ��',Dates::Calendar('restrict_post_to',$values['restrict_post_to'],true))
			->item(array('��������',Eleanor::Text('descr',$values['descr']),'descr'=>'����������, ������ ��� ��������������'))
			->end()
			->submitline($back.Eleanor::Button('OK','submit',array('tabindex'=>100)))
			->endform();

		return Eleanor::$Template->Cover($Lst,$errors);
	}

	/*
		������ ����������� ������ �����������
		$items ������ �����������. ������: ID=>array(), ����� ����������� �������:
			groups ������ ID ����� �������������, ������� ������ � ����� ����������
			users ������ ID �������������, ������� ������ � ����� ����������
			date ���� �������� ����������
			forums ������ ID �������, �� ������� ���������������� ����� ������������� ������ ����������
			_adel ������ �� �������� ����������
			_aedit ������ �� ������ ����������
		$forums ������ � ��������� ����������� � �������, � ������� ���������� ���������� ������ ������. ������: ID=>array(), ����� ����������� �������:
			title �������� ������
			_aedit ������ �� ������ ������
		$users ������ � ��������� ����������� � �������������, ������� ������ � ����������� �� ������ ������. ������: ID=>array(), ����� ����������� �������:
			group ID ������ ������������
			name ��� ������������ (������������ HTML!)
			full_name ������ ��� ������������
		$groups ������ � ��������� ����������� � ������� �������������, ������� ������ � ����������� �� ������ ������. ������: ID=>array(), ����� ����������� �������:
			title �������� ������
			html_pref HTML ������� ������
			html_end HTML ��������� ������
		$fiforums Option-� ��� Select-� ������� ����������� �� ������
		$cnt ���������� ������� �����
		$pp ���������� ����������� �� ��������
		$page ����� ������� ��������, �� ������� �� ������ ���������
		$qs ������ ���������� �������� ������ ��� ������� �������
		$links �������� ����������� ������, ������ � �������:
			sort_id ������ �� ���������� ������ $items �� ID (�����������/�������� � ����������� �� ������� ����������)
			sort_date ������ �� ���������� ������ $items �� ���� �������� ����������� (�����������/�������� � ����������� �� ������� ����������)
			form_items ������ ��� ��������� action �����, ������ ������� ���������� ����������� ������� $items
			pp �������-��������� ������ �� ��������� ���������� ����������� ������������ �� ��������
			first_page ������ �� ������ �������� ����������
			pages �������-��������� ������ �� ��������� ��������
	*/
	public static function Moderators($items,$forums,$users,$groups,$fiforums,$cnt,$pp,$page,$qs,$links)
	{
		static::Menu('moders');

		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$Lst=Eleanor::LoadListTemplate('table-list',6)
			->begin(
				'������������',
				'������',
				array('��������','href'=>$links['sort_date']),
				'������',
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
					$us ? rtrim($us,', ') : '<i>���</i>',
					$gs ? rtrim($gs,', ') : '<i>���</i>',
					array(Eleanor::$Language->Date($v['date'],'fdt'),'center'),
					$fs ? rtrim($fs,', ') : '<i>���</i>',
					$Lst('func',
						array($v['_aedit'],$ltpl['edit'],$images.'edit.png'),
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					),
					Eleanor::Check('mass[]',false,array('value'=>$k))
				);

			}
		}
		else
			$Lst->empty('���������� �� �������');

		return Eleanor::$Template->Cover(
		'<form method="post">
			<table class="tabstyle tabform" id="ftable">
				<tr class="infolabel"><td colspan="2"><a href="#">'.$ltpl['filters'].'</a></td></tr>
				<tr>
					<td><b>�����<br />'.Eleanor::Select('fi[forums]',Eleanor::Option('-�� �����-',0).$fiforums).'</td>
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
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf('����������� �� ��������: %s',$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option('�������','k')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		�������� ������ ����������
		$id ������������� �������������� ����������, ���� $id==0 ������ ��������� ���������
		$controls ������ ��������� ����������
		$values ������ �������� �����, �����:
			users ������ �������������, ������� ������ � ������ ����������, ������: id=>name
			groups ������ ID �����, ������� ������ � ������ ����������
			forums ������ ID �������, ������� ���������� ����� ���������
			descr �������� ���������� (������ ��� ��������������)
			��� �� ���� ������ �������� ����� �� �������� ��� ������� $controls
		$forums Option-� ��� Select-� ������ �������, ������� ���������� ����� ���������
		$errors ������ ������
		$bypost ������� ����, ��� ����� ����� ����� �� POST �������
		$back URL ��������
		$links �������� ����������� ������, ������ � �������:
			delete ������ �� �������� ���������� ��� false
	*/
	public static function AddEditModer($id,$controls,$values,$forums,$errors,$bypost,$back,$links)
	{
		static::Menu($id ? 'edit-moder' : 'add-moder');
		$ltpl=Eleanor::$Language['tpl'];
		$Lst=Eleanor::LoadListTemplate('table-form');

		$users=array();
		if(count($values['users'])>0)
			foreach($values['users'] as $k=>&$v)
				$users[]=Eleanor::$Template->Author(array('names[]'=>$v),array('users[]'=>$k),2).' <a href="#" class="del-moder">�������</a>';
		else
			$users[]=Eleanor::$Template->Author(array('names[]'=>''),array('users[]'=>''),2).' <a href="#" class="del-moder">�������</a>';

		$general=(string)$Lst->begin()
			->item(array('������',Eleanor::Items('groups',UserManager::GroupsOpts($values['groups']),array('tabindex'=>1))))
			->item(array('������������','<ul id="moders"><li>'.join('</li><li>',$users).'</li></ul><a href="#" id="add-moder" style="font-weight:bold;float:right">��������</a>'))
			->item('������',Eleanor::Items('forums',$forums,array('tabindex'=>3)))
			->item(array('����������',Eleanor::Text('descr',$values['descr'],array('tabindex'=>4)),'descr'=>'������ ��� ��������������'))
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
		return Eleanor::$Template->Cover(
			$Lst->form()
			->tabs(
				array('�����',$general),
				array('����� ����������',$rights)
			)
			->submitline(
				$back
				.Eleanor::Button('OK','submit',array('tabindex'=>100))
				.($id ? ' '.Eleanor::Button($ltpl['delete'],'button',array('onclick'=>'window.location=\''.$links['delete'].'\'','tabindex'=>101)) : '')
			)
			->endform(),
			$errors
		).'<script type="text/javascript">//<![CDATA[
$(function(){
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
})//]]></script>';
	}

	/*
		�������� �������� ����������
		$moder ������ ���������� ���������� ����������
			users ������ �������������, ������� ������ � ������ ����������, ������: ID=>array(), ����� ����������� �������:
				_aedit ������ �� �������������� ������������ ������
				full_name ������ ��� ������������
				name ��� ������������ (������������ HTML!)
			groups ������ ����� �������������, ������� ������ � ������ ����������, ������: ID=>array(), ����� ����������� �������:
				_aedit ������ �� ������ ������������� ������
				title �������� ������
				html_pref HTML ������� ������
				html_end HTML ��������� ������
			forums ������ �������, � ������� ���������� ����� ���������, ������: ID=>array(), ����� ����������� �������:
				_aedit ������ �� �������������� ������
				title �������� ������
		$back URL ��������
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

		return Eleanor::$Template->Cover(Eleanor::$Template->Confirm(
			'�� ������������� ������ ������� ����������� '.rtrim($forums,' ,')
			.' � ���� '.($groups ? '����� '.rtrim($groups,', ') : '')
			.($users ? ($groups ? ' � ' : '').'������������� '.rtrim($users,', ') : '')
			.'?',
			$back
		));
	}

	/*
		��������. ������������ ����-����� ��� ������ ���� ����� ������������� ������. Forum-Group-Rights
		$F �������-��������� ������ �� ��������� ���� ����� � ���������� ������, ���������: ID ������, ID ������
		$g ID ��������� ������
	*/
	private static function FGR($F,$g=0)
	{
		return'<div style="position:absolute;display:none;background:#F6F6F6;border:1px solid #A1A1A1" id="fgr" data-url="'.$F('_forum_','_group_').'">'
			.Eleanor::Select('get',UserManager::GroupsOpts($g)).Eleanor::Button('��������� �����','button')
			.'</div><script type="text/javascript">//<![CDATA[
$(function(){
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
});//]]></script>';
	}

	/*
		�������� ��������� ���� ���������� ������ � ���������� ������
		$forum ������ ����� ������, �����:
			id ID ������
			title �������� ������
			parent ID ��������
			parents ������ ���� ��������� ������
		$group ������ ����� ������, �����:
			title ������� ������
			html_pref HTML ������� ������
			html_end HTML ��������� ������
		$controls �������� ��������� ���������� � ������������ � ������� ���������. ���� �����-�� ������� ������� �� �������� ��������, ������ ��� ��������� ��������� ���������
		$values �������������� HTML ��� ���������, ������� ���������� ������� �� ��������. ����� ������ ������� ��������� � ������� $controls
		$haschild ������� ����, ��� ������� ����� ����� ���������
		$inherits ������� ����, ��� ��������� �������� ������ ��������� ���������� �������� ������
		$inherit ������ � ������� ��������� (�� ������� $controls) ��������� ������� ����������� �� ������-��������
		$navi ������, ������� ������ ���������. ������ ID=>array(), �����:
			title ��������� ������
			_a ������ �� ��������� ����� ������. ����� ���� ����� false
		$errors ������ ������
		$saved ���� ��������� ���������� ����
		$back URL ��������
		$links �������� ����������� ������, ������ � �������:
			forum_groups_rights �������-��������� ������ �� ��������� ���� ����� � ���������� ������, ���������: ID ������, ID ������
	*/
	public static function ForumGroupRights($forum,$group,$controls,$values,$haschild,$inherits,$inherit,$navi,$errors,$saved,$back,$links)
	{
		static::Menu('fgr');
		$nav=array();
		foreach($navi as $k=>&$v)
			$nav[]='<a href="'.$v['_afgr'].'">'.$v['title'].'</a>';

		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin(array('id'=>'fg'))
			->head(($nav ? join(' &raquo; ',$nav).' &raquo; ' : '').$forum['title'].': <span style="cursor:pointer" class="fgr" title="��������� ����� �����" data-id="'.$forum['id'].'">'.$group['html_pref'].$group['title'].$group['html_end'].'</span>');

		if($haschild)
			$Lst->item('<span style="color:darkred">��������� � ����������?</span>',Eleanor::Check('subs',$inherits));

		foreach($controls as $k=>&$v)
			if(is_array($v) and isset($values[$k]))
				$Lst->item(array($v['title'].Eleanor::Check('inherit[]',in_array($k,$inherit),array('style'=>'display:none','value'=>$k)),'<div>'.$values[$k].'</div>','tip'=>$v['descr']));
			elseif(is_string($v))
				$Lst->head($v);

		$Lst->end()
			->submitline(
				($back ? Eleanor::Input('back',$back,array('type'=>'hidden')) : '')
				.Eleanor::Button('OK','submit',array('tabindex'=>100))
			)
			->endform();

		return Eleanor::$Template->Cover(
			($saved ? Eleanor::$Template->Message('������� ���������'.($back ? '<br /><a href="'.$back.'">���������</a>' : ''),'info') : '')
			.$Lst,
			$errors
		).'<script type="text/javascript">//<![CDATA[
$(function(){
	var Check=function(ftd,state)
		{
			var ch=ftd.find(":checkbox");
			if(typeof state=="undefined")
				state=!ch.prop("checked");
			if(state)
				ftd.css("text-decoration","line-through").prop("title","'.($forum['parent']>0 ? '����������� �� ���� ������ � ������������ ������' : '����������� �� ������� ���� �����').'").next().children("div").hide();
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
})//]]></script>'.self::FGR($links['forum_groups_rights'],$group['id']);
	}

	/*
		�������� ������
		$forum ������ ����� ���������� ������
			id ID ������
			title �������� ������
		$values ������ �������� �����, �����:
			trash ID ������, ���� ����� ���� ���������� ���� � ���������� ������
		$back URL ��������
	*/
	public static function DeleteForm($forum,$values,$back,$errors)
	{
		static::Menu('delete-forum');
		$Lst=Eleanor::LoadListTemplate('table-form')
			->form()
			->begin()
			->item('���� ����������� ����',Eleanor::Select('trash',Eleanor::Option('-������� ����-',0,$values['trash']==0).$GLOBALS['Eleanor']->Forum->Forums->SelectOptions($values['trash'],$forum['id'])))
			->end()
			->submitline(Eleanor::Button('�������').($back ? ' '.Eleanor::Button('��������','button',array('onclick'=>'location.href="'.$back.'"')) : ''))
			->endform();

		return Eleanor::$Template->Cover(
			Eleanor::$Template->Message('�� ������������� ������ ������� �����&quot;'.$forum['title'].'&quot;? �������� �����, ���� ����� ���������� ����.','info').$Lst,
			$errors
		);
	}

	/*
		�������� �������� ������
		$forum ������ ����� ���������� ������
			id ID ������
			title �������� ������
		$info ������ � ������ �� ��������, �����:
			total ���������� ���, ������� ����� ��������� (�������) � ����� � ��������� ������, � ������� ��� ����������
			done ���������� ������������ (���������) ���
		$links ������ ������, �����:
			go URL �������� � ��������� ����� ��������
	*/
	public static function DeleteProcess($forum,$info,$links)
	{
		return Eleanor::$Template->RedirectScreen($links['go'],5)->Cover(
			Eleanor::$Template->Message('����� &quot;'.$forum['title'].'&quot; ���������...<br /><progress max="'.$info['total'].'" value="'.$info['done'].'" style="width:100%">'.($info['total']==0 ? 100 : round($info['done']/$info['total']*100)).'%</progress>','info')
		);
	}

	/*
		����� �������� ������
		$forum ������ ����� ��� ���������� ������
			id ID ������
			title �������� ������
		$back URL ��������
	*/
	public static function DeleteComplete($forum,$back)
	{
		static::Menu('delete-forum');
		return Eleanor::$Template->Cover(
			Eleanor::$Template->Message('����� &quot;'.$forum['title'].'&quot; ������� �����.'.($back ? '<br /><a href="'.$back.'">���������</a>' : ''),'info')
		);
	}

	/*
		������ ����������� ������ ��������� ���
		$items ������ ��������� ���. ������: ID=>array(), ����� ����������� �������:
			title �������� ��������
			forums ������ ID �������, � ������� ���������� ����� �������
			_adel ������ �� �������� ��������
			_aedit ������ �� ������ ��������
		$forums ������ � ��������� ����������� � �������, � ������� ���������� �������� ������ ������. ������: ID=>array(), ����� ����������� �������:
			title �������� ������
			_aedit ������ �� ������ ������
		$fiforums Option-� ��� Select-� ������� ����������� �� ������
		$cnt ���������� ������� �����
		$pp ���������� ��������� ��� �� ��������
		$page ����� ������� ��������, �� ������� �� ������ ���������
		$qs ������ ���������� �������� ������ ��� ������� �������
		$links �������� ����������� ������, ������ � �������:
			sort_id ������ �� ���������� ������ $items �� ID (�����������/�������� � ����������� �� ������� ����������)
			sort_title ������ �� ���������� ������ $items �� �������� (�����������/�������� � ����������� �� ������� ����������)
			form_items ������ ��� ��������� action �����, ������ ������� ���������� ����������� ������� $items
			pp �������-��������� ������ �� ��������� ���������� ����������� ������������ �� ��������
			first_page ������ �� ������ �������� ����������
			pages �������-��������� ������ �� ��������� ��������
	*/
	public static function Prefixes($items,$forums,$fiforums,$cnt,$pp,$page,$qs,$links)
	{
		static::Menu('prefixes');

		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$Lst=Eleanor::LoadListTemplate('table-list',4)
			->begin(
				array('�������','href'=>$links['sort_title']),
				'������',
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
					$fs ? rtrim($fs,', ') : '<i>���</i>',
					$Lst('func',
						array($v['_aedit'],$ltpl['edit'],$images.'edit.png'),
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					),
					Eleanor::Check('mass[]',false,array('value'=>$k))
				);

			}
		}
		else
			$Lst->empty('�������� �� �������');

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
					<td><b>��������</b><br />'.Eleanor::Input('fi[title]',$qs['']['fi']['title']).'</td>
					<td><b>�����<br />'.Eleanor::Select('fi[forums]',Eleanor::Option('-�� �����-',0).$fiforums).'</td>
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
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf('��������� �� ��������: %s',$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option('�������','k')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		�������� ������ ��������
		$id ������������� �������������� ��������, ���� $id==0 ������ ������� ���������
		$values ������ �������� �����
			����� �����:
			forums ������ ��������������� �������, � ������� ������������ ����� �������

			�������� �����:
			title �������� ��������

			����������� �����:
			_onelang ���� ����������� ������� ��� ���������� ���������������
		$forums Option-� ��� Select-� ������ �������, � ������� ������������ ����� �������
		$errors ������ ������
		$bypost ������� ����, ��� ����� ����� ����� �� POST �������
		$back URL ��������
		$links �������� ����������� ������, ������ � �������:
			delete ������ �� �������� ���������� ��� false
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
			->item('������',Eleanor::Items('forums',$forums,array('tabindex'=>1)))
			->item($ltpl['title'],Eleanor::$Template->LangEdit($ml['title'],null));

		if(Eleanor::$vars['multilang'])
			$Lst->item($ltpl['set_for_langs'],Eleanor::$Template->LangChecks($values['_onelang'],$values['_langs'],null,3));

		$Lst->end()
			->submitline(
				$back
				.Eleanor::Button('Ok','submit',array('tabindex'=>14))
				.($id ? ' '.Eleanor::Button($ltpl['delete'],'button',array('onclick'=>'window.location=\''.$links['delete'].'\'')) : '')
			)
			->endform();

		foreach($errors as $k=>&$v)
			if(is_int($k) and is_string($v) and isset(static::$lang[$v]))
				$v=static::$lang[$v];

		return Eleanor::$Template->Cover((string)$Lst,$errors,'error');
	}

	/*
		�������� �������� �������� ���
		$prefix ������ ���������� ���������� ��������
			title �������� ��������
			forums ������ �������, � ������� ������������ ����� ���������, ������: ID=>array(), ����� ����������� �������:
				_aedit ������ �� �������������� ������
				title �������� ������
		$back URL ��������
	*/
	public static function DeletePrefix($prefix,$back)
	{
		static::Menu('delete-prefix');
		$forums='';
		foreach($prefix['forums'] as &$v)
			$forums.='<a href="'.$v['_aedit'].'">'.$v['title'].'</a>, ';

		return Eleanor::$Template->Cover(Eleanor::$Template->Confirm(
			'�� ������������� ������ ������� ��� &quot;'.$prefix['title'].'&quot; ������������ � ������� '.rtrim($forums,',').'?',
			$back
		));
	}

	/*
		�������� ��������� �������
		#ToDo!
	*/
	public static function Files($items,$forums,$topics,$fiforums,$cnt,$pp,$page,$qs,$links)
	{
		static::Menu('files');

		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$Lst=Eleanor::LoadListTemplate('table-list',6)
			->begin(
				'����',
				array('����','href'=>$links['sort_date']),
				'�����',
				'����',
				array($ltpl['functs'],'href'=>$links['sort_id']),
				array(Eleanor::Check('mass',false,array('id'=>'mass-check')),20)
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			foreach($items as $k=>&$v)
				$Lst->item(
					'<a href="'.$v['_adown'].'">'.$v['filename'].'</a><br /><span class="small">'.Files::BytesToSize($v['filesize']).'</span>',
					Eleanor::$Language->Date($v['date'],'fdt'),
					isset($forums[ $v['f'] ][ $v['language'] ]) ? '<a href="'.$forums[ $v['f'] ][ $v['language'] ]['_a'].'" target="_blank">'.$forums[ $v['f'] ][ $v['language'] ]['title'].'</a>' : '<i>���</i>',
					isset($topics[ $v['t'] ]) ? '<a href="'.$topics[ $v['f'] ]['_a'].'" target="_blank">'.$topics[ $v['f'] ]['title'].'</a>' : '<i>���</i>',
					$Lst('func',
						array($v['_aprev'],'�������� ���������',$images.'viewfile.png'),
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					),
					Eleanor::Check('mass[]',false,array('value'=>$k))
				);
		}
		else
			$Lst->empty('����� �� �������');

		return Eleanor::$Template->Cover(
		'<form method="post">
			<table class="tabstyle tabform" id="ftable">
				<tr class="infolabel"><td colspan="2"><a href="#">'.$ltpl['filters'].'</a></td></tr>
				<tr>
					<td><b>�����<br />'.Eleanor::Select('fi[forums]',Eleanor::Option('-�� �����-',0).$fiforums).'</td>
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
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf('������ �� ��������: %s',$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option('�������','k')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		�������� �������� ������
		#ToDo!
	*/
	public static function DeleteAttach($attach,$back)
	{
		static::Menu('delete-attach');

		return Eleanor::$Template->Cover(Eleanor::$Template->Confirm(
			'�� ������������� ������ �����?',
			$back
		));
	}

	/*
		�������� ��������� �������� �� ������
		#ToDo!
	*/
	public static function FSubscriptions($items,$forums,$users,$fiforums,$fiuname,$cnt,$pp,$page,$qs,$links)
	{
		static::Menu('fsubscriptions');

		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$Lst=Eleanor::LoadListTemplate('table-list',7)
			->begin(
				array('����','href'=>$links['sort_date']),
				'�����',
				'����',
				'������.',
				'����. ��������',
				$ltpl['functs'],
				array(Eleanor::Check('mass',false,array('id'=>'mass-check')),20)
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			$intencity=array(
				'i'=>'����������',
				'd'=>'���������',
				'w'=>'�����������',
				'm'=>'����������',
				'y'=>'��������',
			);
			foreach($items as $k=>&$v)
				$Lst->item(
					Eleanor::$Language->Date($v['date'],'fdt'),
					isset($forums[ $v['f'] ][ $v['language'] ]) ? '<a href="'.$forums[ $v['f'] ][ $v['language'] ]['_a'].'" target="_blank">'.$forums[ $v['f'] ][ $v['language'] ]['title'].'</a>' : '<i>���</i>',
					isset($users[ $v['uid'] ]) ? '<a href="'.$users[ $v['uid'] ]['_aedit'].'">'.htmlspecialchars($users[ $v['uid'] ]['name'],ELENT,CHARSET).'</a>' : '<i>���</i>',
					array(isset($intencity[ $v['intencity'] ]) ? $intencity[ $v['intencity'] ] : '<i>���</i>','center'),
					'<span title="��������� �������� '.Eleanor::$Language->Date($v['nextsend'],'fdt').'">'.Eleanor::$Language->Date($v['lastsend'],'fdt').'</span>',
					$Lst('func',
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					),
					Eleanor::Check('mass[]',false,array('value'=>$v['f'].'-'.$v['language'].'-'.$v['u']))
				);
		}
		else
			$Lst->empty('�������� �� ������ �� �������');

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
					<td><b>������������</b><br />'.Eleanor::$Template->Author(array(''=>$fiuname),array('fi[u]'=>$qs['']['fi']['u'])).'</td>
					<td><b>�����<br />'.Eleanor::Select('fi[forums]',Eleanor::Option('-�� �����-',0).$fiforums).'</td>
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
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf('�������� �� ��������: %s',$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option('�������','k')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		�������� �������� �������� ������������ �� �����
		#ToDo!
	*/
	public static function DeleteFS()
	{

	}

	/*
		�������� ��������� �������� �� ����
		#ToDo!
	*/
	public static function TSubscriptions($items,$forums,$users,$topics,$fiforums,$fiuname,$fitopic,$cnt,$pp,$page,$qs,$links)
	{
		static::Menu('tsubscriptions');

		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$Lst=Eleanor::LoadListTemplate('table-list',7)
			->begin(
				array('����','href'=>$links['sort_date']),
				'����',
				'����',
				'������.',
				'����. ��������',
				$ltpl['functs'],
				array(Eleanor::Check('mass',false,array('id'=>'mass-check')),20)
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			$intencity=array(
				'i'=>'����������',
				'd'=>'���������',
				'w'=>'�����������',
				'm'=>'����������',
				'y'=>'��������',
			);
			foreach($items as $k=>&$v)
			{
				$ist=isset($topics[ $v['t'] ]);
				$Lst->item(
					Eleanor::$Language->Date($v['date'],'fdt'),
					($ist ? '<a href="'.$topics[ $v['t'] ]['_a'].'" target="_blank">'.$topics[ $v['t'] ]['title'].'</a>' : '<i>���</i>')
					.($ist && isset($forums[ $v['f'] ][ $v['language'] ]) ? '<br /><span class="small"><a href="'.$forums[ $v['f'] ][ $v['language'] ]['_a'].'" target="_blank">'.$forums[ $v['f'] ][ $v['language'] ]['title'].'</a></span>' : ''),
					isset($users[ $v['uid'] ]) ? '<a href="'.$users[ $v['uid'] ]['_aedit'].'">'.htmlspecialchars($users[ $v['uid'] ]['name'],ELENT,CHARSET).'</a>' : '<i>���</i>',
					array(isset($intencity[ $v['intencity'] ]) ? $intencity[ $v['intencity'] ] : '<i>���</i>','center'),
					'<span title="��������� �������� '.Eleanor::$Language->Date($v['nextsend'],'fdt').'">'.Eleanor::$Language->Date($v['lastsend'],'fdt').'</span>',
					$Lst('func',
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					),
					Eleanor::Check('mass[]',false,array('value'=>$v['t'].'-'.$v['u']))
				);
			}
		}
		else
			$Lst->empty('�������� �� ���� �� �������');

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
					<td><b>������������</b><br />'.Eleanor::$Template->Author(array(''=>$fiuname),array('fi[u]'=>$qs['']['fi']['u'])).'</td>
					<td><b>�����<br />'.Eleanor::Select('fi[forums]',Eleanor::Option('-�� �����-',0).$fiforums).'</td>
				</tr>
				<tr>
					<td><b>'.($fitopic ? '<a href="'.$fitopic['_a'].'">'.$fitopic['title'].'</a>' : 'ID ����').'<br />'.Eleanor::Input('fi[t]',$qs['']['fi']['t'],array('type'=>'number')).'</td>
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
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf('�������� �� ��������: %s',$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option('�������','k')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		�������� �������� �������� ������������ �� ����
		#ToDo!
	*/
	public static function DeleteTS()
	{

	}

	/*
		�������� ��������� ��������� ���������
		#ToDo!
	*/
	public static function Reputation($items,$forums,$users,$topics,$fiforums,$fiutoname,$fiufromname,$fitopic,$cnt,$pp,$page,$qs,$links)
	{
		static::Menu('reputation');

		$ltpl=Eleanor::$Language['tpl'];
		$GLOBALS['jscripts'][]='js/checkboxes.js';

		$Lst=Eleanor::LoadListTemplate('table-list',8)
			->begin(
				array('���','colspan'=>2),
				'����',
				'����',
				'���',
				'����������',
				array($ltpl['functs'],'href'=>$links['sort_id']),
				array(Eleanor::Check('mass',false,array('id'=>'mass-check')),20)
			);

		if($items)
		{
			$images=Eleanor::$Template->default['theme'].'images/';
			foreach($items as $k=>&$v)
			{
				$ist=isset($topics[ $v['t'] ]);
				$Lst->item(
					$v['value']>1 ? '<b style="color:green">'.$v['value'].'</b>' : '<b style="color:red">'.$v['value'].'</b>',
					isset($users[ $v['from'] ]) ? '<a href="'.$users[ $v['from'] ]['_a'].'">'.htmlspecialchars($users[ $v['from'] ]['name'],ELENT,CHARSET).'</a>' : $v['from_name'],
					Eleanor::$Language->Date($v['date'],'fdt'),
					isset($users[ $v['to'] ]) ? '<a href="'.$users[ $v['to'] ]['_a'].'">'.htmlspecialchars($users[ $v['from'] ]['name'],ELENT,CHARSET).'</a>' : '<i>���</i>',
					($ist ? '<a href="'.$v['_a'].'" target="_blank">'.$topics[ $v['t'] ]['title'].'</a>' : '<i>���</i>')
					.($ist && isset($forums[ $v['f'] ][ $v['language'] ]) ? '<br /><span class="small"><a href="'.$forums[ $v['f'] ][ $v['language'] ]['_a'].'" target="_blank">'.$forums[ $v['f'] ][ $v['language'] ]['title'].'</a></span>' : ''),
					$v['comment'].($v['value'] ? '<hr />' .$v['value']: ''),
					$Lst('func',
						array($v['_adel'],$ltpl['delete'],$images.'delete.png')
					),
					Eleanor::Check('mass[]',false,array('value'=>$v['f'].'-'.$v['language'].'-'.$v['u']))
				);
			}
		}
		else
			$Lst->empty('��������� ��������� �� �������');

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
					<td><b>�� ����</b><br />'.Eleanor::$Template->Author(array(''=>$fiufromname),array('fi[from]'=>$qs['']['fi']['from'])).'</td>
					<td><b>����</b><br />'.Eleanor::$Template->Author(array(''=>$fiutoname),array('fi[to]'=>$qs['']['fi']['to'])).'</td>
					<td><b>'.($fitopic ? '<a href="'.$fitopic['_a'].'">'.$fitopic['title'].'</a>' : 'ID ����').'<br />'.Eleanor::Input('fi[t]',$qs['']['fi']['t'],array('type'=>'number')).'</td>
					<td><b>����<br />'.Dates::Calendar('fi[date]',$qs['']['fi']['date'],false,array('style'=>'width:100px')).'</td>
				</tr>
				<tr>
					<td colspan="3"><b>�����<br />'.Eleanor::Select('fi[forums]',Eleanor::Option('-�� �����-',0).$fiforums).'</td>
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
		.'<div class="submitline" style="text-align:right"><div style="float:left">'.sprintf('�������� �� ��������: %s',$Lst->perpage($pp,$links['pp'])).'</div>'.$ltpl['with_selected'].Eleanor::Select('op',Eleanor::Option('�������','k')).Eleanor::Button('Ok').'</div></form>'
		.Eleanor::$Template->Pages($cnt,$pp,$page,array($links['pages'],$links['first_page'])));
	}

	/*
		�������� �������� ���������
		#ToDo!
	*/
	public static function DeleteReputation()
	{

	}

	/*
		�������� ������������ ������
		#ToDo!
	*/
	public static function Tasks($values,$statuses,$opforums,$errors,$forums)
	{
		static::Menu('tasks');
		$progress=false;
		$Lst=Eleanor::LoadListTemplate('table-form')

		#�������� ��� � �������
			->form()
			->begin()
			->head('�������� ��� � �������');
		if(isset($statuses['rectop']))
		{
			$last='';
			foreach($statuses['rectop'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="���� ����������">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span> ';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> ';
					break;
					default:
						$color='black';
						$date='<span title="���� ������">'.Eleanor::$Language->Date($v['start'],'fdt').'</span> ';
				}
				if($v['options']['forums'])
					foreach($v['options']['forums'] as $f)
					{
						if(isset($forums[ $f ]))
							$date.='<a href="'.$forums[ $f ]['_aedit'].'">'.$forums[ $f ]['title'].'</a>, ';
					}
				else
					$date.=' <i>��� ������</i>';
				$last.='<li style="color:'.$color.'">'.rtrim($date,', ').'</li>';
			}
			$Lst->item('���������� �������',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['rectop']))
			$Lst->item('������',Eleanor::$Template->Message($errors['rectop'],'error'));
		$Lst->item(array('������',Eleanor::Items('rectop',$opforums['rectop']),'descr'=>'�������� ������, � ������� ����� ����������� ���� ���� �� ��������� ������ ��� ��������� ��� �� ���� �������'))
			->end()
			->submitline(Eleanor::Button('���������'))
			->endform()

		#�������� ������ � �����
			->form()
			->begin()
			->head('�������� ������ � �����');
		if(isset($statuses['recposts']))
		{
			$last='';
			foreach($statuses['recposts'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="���� ����������">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span> ';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> ';
					break;
					default:
						$color='black';
						$date='<span title="���� ������">'.Eleanor::$Language->Date($v['start'],'fdt').'</span> ';
				}
				if(isset($v['options']['forums']))
					foreach($v['options']['forums'] as $f)
						if(isset($forums[ $f ]))
							$date.='<a href="'.$forums[ $f ]['_aedit'].'">'.$forums[ $f ]['title'].'</a>, ';
				$last.='<li style="color:'.$color.'">'.rtrim($date,', ').'</li>';
			}
			$Lst->item('���������� �������',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['recposts']))
			$Lst->item('������',Eleanor::$Template->Message($errors['recposts'],'error'));
		$Lst->item(array('������',Eleanor::Items('recpostsf',$opforums['recpostsf']),'descr'=>'�������� ������, � ����� ������� ����� ����������� �����'))
			->item(array('��� ID ���',Eleanor::Input('recpostst',$values['recpostst']),'descr'=>'���� ������� ID  ��������������� ���'))
			->end()
			->submitline(Eleanor::Button('���������'))
			->endform()

		#�������� ������ �������������
			->form()
			->begin()
			->head('�������� ������ �������������');
		if(isset($statuses['recuserposts']))
		{
			$last='';
			foreach($statuses['recuserposts'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="���� ����������">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span> ';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> ';
					break;
					default:
						$color='black';
						$date='<span title="���� ������">'.Eleanor::$Language->Date($v['start'],'fdt').'</span> ';
				}
				$last.='<li style="color:'.$color.'">'.$date.'</li>';
			}
			$Lst->item('���������� �������',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['recuserposts']))
			$Lst->item('������',Eleanor::$Template->Message($errors['recuserposts'],'error'));
		$Lst->item(array('������������',Eleanor::Input('recuserposts',$values['recuserposts']),'descr'=>'������� ID �������������, � ������� ����� ����������� �����, ������: 1,3,5-10 �������� ���� ������ ��� ��������� ������ � ����.'))
			->end()
			->submitline(Eleanor::Button('���������'))
			->endform()

		#���������� ���������� ����������� � ����
			->form()
			->begin()
			->head('���������� ���������� ����������� � ����');
		if(isset($statuses['lastposttopic']))
		{
			$last='';
			foreach($statuses['lastposttopic'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="���� ����������">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span> ';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> ';
					break;
					default:
						$color='black';
						$date='<span title="���� ������">'.Eleanor::$Language->Date($v['start'],'fdt').'</span> ';
				}
				if(isset($v['options']['forums']))
					foreach($v['options']['forums'] as $f)
						if(isset($forums[ $f ]))
							$date.='<a href="'.$forums[ $f ]['_aedit'].'">'.$forums[ $f ]['title'].'</a>, ';
				$last.='<li style="color:'.$color.'">'.rtrim($date,', ').'</li>';
			}
			$Lst->item('���������� �������',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['lastposttopic']))
			$Lst->item('������',Eleanor::$Template->Message($errors['lastposttopic'],'error'));
		$Lst->item(array('������',Eleanor::Items('recpostsf',$opforums['lastposttopicf']),'descr'=>'�������� ������, � ����� ������� ����� ��������������� ���������� � ��������� ����������'))
			->item(array('��� ID ���',Eleanor::Input('recpostst',$values['lastposttopict']),'descr'=>'���� ������� ID ��������������� ���'))
			->end()
			->submitline(Eleanor::Button('���������'))
			->endform()

		#���������� ���������� ����������� � �����
			->form()
			->begin()
			->head('���������� ���������� ����������� � �����');
		if(isset($statuses['lastpostforum']))
		{
			$last='';
			foreach($statuses['lastpostforum'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="���� ����������">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span> ';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> ';
					break;
					default:
						$color='black';
						$date='<span title="���� ������">'.Eleanor::$Language->Date($v['start'],'fdt').'</span> ';
				}
				if($v['options']['forums'])
					foreach($v['options']['forums'] as $f)
					{
						if(isset($forums[ $f ]))
							$date.='<a href="'.$forums[ $f ]['_aedit'].'">'.$forums[ $f ]['title'].'</a>, ';
					}
				else
					$date.=' <i>��� ������</i>';
				$last.='<li style="color:'.$color.'">'.rtrim($date,', ').'</li>';
			}
			$Lst->item('���������� �������',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['lastpostforum']))
			$Lst->item('������',Eleanor::$Template->Message($errors['lastpostforum'],'error'));
		$Lst->item(array('������',Eleanor::Items('lastpostforum',$opforums['lastpostforum']),'descr'=>'�������� ������, � ������� ����� �������� ���������� � ��������� ���������� ���� �� ��������� ������ ��� ��������� ��� �� ���� �������'))
			->end()
			->submitline(Eleanor::Button('���������'))
			->endform()

		#�������� ������� ������
			->form()
			->begin()
			->head('�������� ������� ������');
		if(isset($statuses['removefiles']))
		{
			$last='';
			foreach($statuses['removefiles'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="���� ����������">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span> ';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> ';
					break;
					default:
						$color='black';
						$date='<span title="���� ������">'.Eleanor::$Language->Date($v['start'],'fdt').'</span> ';
				}
				$last.='<li style="color:'.$color.'">'.$date.'</li>';
			}
			$Lst->item('���������� �������',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['removefiles']))
			$Lst->item('������',Eleanor::$Template->Message($errors['removefiles'],'error'));
		$Lst->item(array('������������',Eleanor::Input('removefilesp',$values['removefilesp']),'descr'=>'������� ID �������, � ������� ����� ������ ������� �����, ������: 1,3,5-10. �������� ���� ������ ��� ������ ������� ������ � ���� ������.'))
			->end()
			->submitline(Eleanor::Button('���������'))
			->endform()

		#������������� �������������
			->form()
			->begin()
			->head('������������� �������������');
		if(isset($statuses['syncusers']))
		{
			$last='';
			foreach($statuses['syncusers'] as $k=>&$v)
			{
				switch($v['status'])
				{
					case'done':
						$color='green';
						$date='<span title="���� ����������">'.Eleanor::$Language->Date($v['finish'],'fdt').'</span>';
					break;
					case'process':
						$progress=true;
						$color='orange';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span> <progress data-id="'.$k.'" value="'.$v['done'].'" max="'.($v['total']>0 ? $v['total'] : 1).'" title="'.($pers=$v['total']>0 ? round($v['done']/$v['total']*100,2) : 0).'%"><span>'.$pers.'</span>%</progress> ';
					break;
					case'error':
						$color='red';
						$date='<span title="���� ���������� ����������">'.Eleanor::$Language->Date($v['date'],'fdt').'</span>';
					break;
					default:
						$color='black';
						$date='<span title="���� ������">'.Eleanor::$Language->Date($v['start'],'fdt').'</span>';
				}
				if(isset($v['options']['date']))
					$date.=', ������ �������������: '.Eleanor::$Language->Date($v['options']['date'],'fdt');
				$last.='<li style="color:'.$color.'">'.$date.'</li>';
			}
			$Lst->item('���������� �������',$last ? '<ul>'.$last.'</ul>' : '');
		}
		if(isset($errors['syncusers']))
			$Lst->item('������',Eleanor::$Template->Message($errors['syncusers'],'error'));
		$Lst->item('��������� ���� �������������',Dates::Calendar('syncusersdate',$values['syncusersdate']))
			->end()
			->submitline(Eleanor::Button('���������'))
			->endform();

		if($progress)
			$Lst.='<script type="text/javascript">/*<![CDATA[*/ $(function(){ new ProgressList("'.$GLOBALS['Eleanor']->module['name'].'","'.Eleanor::$services['cron']['file'].'"); });//]]></script>';
		return Eleanor::$Template->Cover($Lst);
	}

	/*
		������� ��� ��������
		$c ��������� ��������
	*/
	public static function Options($c)
	{
		static::Menu('options');
		return$c;
	}
}