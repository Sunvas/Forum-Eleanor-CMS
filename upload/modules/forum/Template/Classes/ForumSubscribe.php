<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
class TplForumSubscribe
{
	/**
	 * Интерфейс управлением подпиской на форум
	 * @param array $forum Форум, на который подписываются
	 * @param false|array $current Текущая подписка
	 * @param string|false $set Предлагаемый тип подписки (если переходим с письма об отписке)
	 */
	public static function SubscribeForum($forum,$current,$set)
	{
		$intensity=$current && $current['intensity'] ? (string)$current['intensity'] : '';
		$opts='';
		foreach(array(
			''=>'не уведомлять',
			'i'=>'немедленные',
			'd'=>'ежедневные',
			'w'=>'еженедельные',
			'm'=>'ежемесячные',
		) as $k=>$v)
			$opts.=Eleanor::Option($v,$k,$set==$k,array('style'=>$intensity==$k ? 'background:lightgreen' : ''));

		$Lst=Eleanor::LoadListTemplate('table-form')->form()->begin()
			->item(array('Тип уведомлений',Eleanor::Select('set',$opts),'td1'=>array('style'=>'width:200px'),'descr'=>'Зеленым выделена текущая подписка'));

		if($current)
		{
			if((int)$current['lastview'])
				$Lst->item('Последний просмотр форума',Eleanor::$Language->Date($current['lastview'],'fdt'));
			if((int)$current['lastsend'])
				$Lst->item('Последнее уведомление',Eleanor::$Language->Date($current['lastsend'],'fdt'));
			if((int)$current['nextsend'] and $current['sent']==0 and $current['intensity']!='i')
				$Lst->item(array('Следующе уведомление',Eleanor::$Language->Date($current['nextsend'],'fdt'),'descr'=>'При наличии новых тем'));
		}

		$Lst->end()->submitline(Eleanor::Button('Ok'))->endform();

		$nav=array();
		$forum['parents']=explode(',',$forum['parents']);
		$Forum = $GLOBALS['Eleanor']->Forum;
		foreach($forum['parents'] as $v)
			if(isset($Forum->Forums->dump[$v]))
				$nav[]=array($Forum->Links->Forum($v), $Forum->Forums->dump[$v]['title']);
		array_push($nav,array($forum['_a'],$forum['title']),array(false,'Подписка на форум'));

		$C=Eleanor::$Template->ForumMenu($nav,null);
		if($_SERVER['REQUEST_METHOD']=='POST')
			$C->Message($current ? 'Подписка успешно установлена' : 'Отписка прошла успешно','info',10);

		return$C.$Lst;
	}

	/**
	 * Интерфейс подписки на тему
	 * @param array $topic Тема, на которую происходит подписка
	 * @param array $forum Форум, в котором расположена тема
	 * @param array|false $current Текущая подписка
	 * @param string|false $set Предлагаемый тип подписки (если переходим с письма об отписке)
	 */
	public static function SubscribeTopic($topic,$forum,$current,$set)
	{
		$intensity=$current && $current['intensity'] ? (string)$current['intensity'] : '';
		$opts='';
		foreach(array(
			''=>'не уведомлять',
			'i'=>'немедленные',
			'd'=>'ежедневные',
			'w'=>'еженедельные',
			'm'=>'ежемесячные',
		) as $k=>$v)
			$opts.=Eleanor::Option($v,$k,$set==$k,array('style'=>$intensity==$k ? 'background:lightgreen' : ''));

		$Lst=Eleanor::LoadListTemplate('table-form')->form()->begin()
			->item(array('Тип уведомлений',Eleanor::Select('set',$opts),'td1'=>array('style'=>'width:200px'),'descr'=>'Зеленым выделена текущая подписка'));

		if($current)
		{
			if((int)$current['lastview'])
				$Lst->item('Последний просмотр форума',Eleanor::$Language->Date($current['lastview'],'fdt'));
			if((int)$current['lastsend'])
				$Lst->item('Последнее уведомление',Eleanor::$Language->Date($current['lastsend'],'fdt'));
			if((int)$current['nextsend'] and $current['sent']==0 and $current['intensity']!='i')
				$Lst->item(array('Следующе уведомление',Eleanor::$Language->Date($current['nextsend'],'fdt'),'descr'=>'При наличии новых постов'));
		}

		$Lst->end()->submitline(Eleanor::Button('Ok'))->endform();

		$nav=array();
		$forum['parents']=explode(',',$forum['parents']);
		$Forum = $GLOBALS['Eleanor']->Forum;
		foreach($forum['parents'] as $v)
			if(isset($Forum->Forums->dump[$v]))
				$nav[]=array($Forum->Links->Forum($v), $Forum->Forums->dump[$v]['title']);
		array_push($nav,array($forum['_a'],$forum['title']),array($topic['_a'],$topic['description'] ? '<span title="'.$topic['description'].'">'.$topic['title'].'</span>' : $topic['title']),array(false,'Подписка на тему'));

		$C=Eleanor::$Template->ForumMenu($nav,null);
		if($_SERVER['REQUEST_METHOD']=='POST')
			$C->Message($current ? 'Подписка успешно установлена' : 'Отписка прошла успешно','info',10);

		return$C.$Lst;
	}
}