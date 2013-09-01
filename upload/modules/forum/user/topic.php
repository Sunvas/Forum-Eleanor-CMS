<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
$config = $Forum->config;

switch($do)
{
	case'activate-post':#Активировать пост
		#ToDo!
	break;
	case'find-post':#Найти пост
		$R=Eleanor::$Db->Query('SELECT `p`.`f`,`p`.`status` `pstatus`,`p`.`author_id` `paid`,`p`.`sortdate`,`t`.`id`,`t`.`uri`,`t`.`status`,`t`.`author_id` FROM `'. $config['fp'].'` `p` INNER JOIN `'. $config['ft'].'` `t` ON `t`.`id`=`p`.`t` WHERE `p`.`id`='.(int)$id.' LIMIT 1');
		if(!$topic=$R->fetch_assoc() or !$Forum->CheckTopicAccess($topic))
		{
			ExitPage:
			return ExitPage();
		}

		if(!$Forum->user)
		{
			$gp=$Forum->GuestSign('p');
			$gt=$Forum->GuestSign('t');
		}

		$forum=$Forum->Forums->GetForum($topic['f']);
		if($forum['moderators'])
			list(,$moder)=$Forum->Moderator->ByIds($forum['moderators'],array('chstatust','chstatus','mchstatust','mchstatus'),$config['n'].'_moders_fp'.$forum['id'].$Forum->language);
		else
			$moder=false;

		#Проверка доступа к теме (не уникальная сторка, в других файлах она тоже имеется: ajax/markread.php, ajax/subscribe.php, user/subscribe.php)
		if(($topic['status']==0 or $Forum->user and $Forum->user['id']!=$topic['author_id'] or !$Forum->user and !in_array($t,$gt)) and (!$moder or !in_array(1,$moder['chstatust']) and !in_array(1,$moder['mchstatust'])))
			goto ExitPage;

		#Проверка доступа к посту
		$modposts=$moder && (in_array(1,$moder['chstatus']) or in_array(1,$moder['mchstatus']));
		if(($topic['pstatus']==0 or $Forum->user and $Forum->user['id']!=$topic['paid'] or !$Forum->user and !in_array($t,$gp)) and !$modposts)
			goto ExitPage;

		if(!$modposts)
			$rights=$Forum->ForumRights($topic['f']);
		$active=$topic['status']==1;
		$qq='';#Queued quantity
		if(in_array($topic['pstatus'],array(-3,-1)))
			if($modposts or in_array('mod',$rights))
				$qq='UNION ALL(SELECT COUNT(`sortdate`) `cnt` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`='.($active ? -1 : -3).' AND `sortdate`<\''.$topic['sortdate'].'\')';
			elseif($Forum->user)
				$qq='UNION ALL(SELECT COUNT(`sortdate`) `cnt` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `author_id`='.$Forum->user['id'].' AND `sortdate`<\''.$topic['sortdate'].'\')';
			elseif($gp)
				$qq='UNION ALL(SELECT COUNT(`sortdate`) `cnt` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `id`'.Eleanor::$Db->In($gp).' AND `sortdate`<\''.$topic['sortdate'].'\')';

		$R=Eleanor::$Db->Query('SELECT SUM(`c`.`cnt`) FROM (
			(SELECT COUNT(`sortdate`) `cnt` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`='.($active ? 1 : -2).' AND `sortdate`<\''.$topic['sortdate'].'\')'
			.($modposts ? 'UNION ALL(SELECT COUNT(`sortdate`) `cnt` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`=0 AND `sortdate`<\''.$topic['sortdate'].'\')' : '')
			.$qq
		.') `c`');
		list($qq)=$R->fetch_row();

		$R=Eleanor::$Db->Query('SELECT COUNT(`t`) `cnt` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`='.($active ? 1 : -2).' AND `sortdate`<\''.$topic['sortdate'].'\'');
		list($cnt)=$R->fetch_row();
		$cnt+=$qq;

		$page=(int)($cnt/$Forum->vars['ppp'])+1;
		return GoAway($Forum->Links->Topic($topic['f'],$topic['id'],$topic['uri'],array('page'=>$page>1 ? $page : false)),301,'post'.$id);
	case'go-new-post':#Переход к последнему непрочитанному сообщению в теме
		if($Forum->user)
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`f`,`uri`,`status`,`author_id`,`lp_date` FROM `'.$config['ft'].'` WHERE `id`='.(int)$id.' LIMIT 1');
			if(!$topic=$R->fetch_assoc() or !$Forum->CheckTopicAccess($topic))
				goto ExitPage;

			$forum=$Forum->Forums->GetForum($topic['f']);
			if($forum['moderators'])
				list(,$moder)=$Forum->Moderator->ByIds($forum['moderators'],array('chstatust','chstatus','mchstatust','mchstatus'),$config['n'].'_moders_fp'.$forum['id'].$Forum->language);
			else
				$moder=false;

			if(($topic['status']==0 or $Forum->user and $Forum->user['id']!=$topic['author_id']) and (!$moder or !in_array(1,$moder['chstatust']) and !in_array(1,$moder['mchstatust'])))
				goto ExitPage;

			$lp=strtotime($topic['lp_date']);
			if($lp>$Forum->user['allread'])
			{
				$R=Eleanor::$Db->Query('SELECT UNIX_TIMESTAMP(`allread`) `allread`,`topics` FROM `'.$config['re'].'` WHERE `uid`='.$Forum->user['id'].' AND `f`='.$topic['f'].' LIMIT 1');
				if($re=$R->fetch_assoc())
				{
					if($re['allread']<$lp)
					{
						$re['topics']=$re['topics'] ? (array)unserialize($re['topics']) : array();
						$lp=isset($re['topics'][ $topic['id'] ]) ? min($lp,$re['topics'][ $topic['id'] ]) : false;
					}
				}
				else
					$lp=false;
			}

			$lp=$lp ? ' AND `sortdate`>=FROM_UNIXTIME('.$lp.')' : '';
			$active=$topic['status']==1;
			$modposts=$moder && (in_array(1,$moder['chstatus']) or in_array(1,$moder['mchstatus']));

			if(!$modposts)
			{
				$rights=$Forum->ForumRights($topic['f']);
				$usermod=in_array('mod',$rights);
			}

			$R=Eleanor::$Db->Query('SELECT `id`,`status`,`author_id`,`sortdate`,UNIX_TIMESTAMP(`sortdate`) `_sd` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`='.($active ? -1 : -3).$lp.($modposts || $usermod ? '' : ' AND `author_id`='.$Forum->user['id']).' ORDER BY `sortdate` ASC LIMIT 1');
			$inact=$R->fetch_assoc();

			if($modposts)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`status`,`author_id`,`sortdate`,UNIX_TIMESTAMP(`sortdate`) `_sd` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`=0'.$lp.' ORDER BY `sortdate` ASC LIMIT 1');
				if($a=$R->fetch_assoc() and (!$inact or $a['_sd']>$inact['_sd']))
					$inact=$a;
			}

			$R=Eleanor::$Db->Query('SELECT `id`,`status`,`sortdate`,UNIX_TIMESTAMP(`sortdate`) `_sd` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`='.($active ? 1 : -2).$lp.' ORDER BY `sortdate` ASC LIMIT 1');
			$act=$R->fetch_assoc();

			if($act and $inact)
				$post=$act['_sd']>$inact['_sd'] ? $act : $inact;
			elseif($act or $inact)
				$post=$act ? $act : $inact;
		}
	case'go-last-post':#Переход к последнему сообщению в теме
		if(!isset($topic))
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`f`,`uri`,`status`,`author_id` FROM `'.$config['ft'].'` WHERE `id`='.(int)$id.' LIMIT 1');
			if(!$topic=$R->fetch_assoc() or !$Forum->CheckTopicAccess($topic))
				goto ExitPage;

			$forum=$Forum->Forums->GetForum($topic['f']);
			if($forum['moderators'])
				list(,$moder)=$Forum->Moderator->ByIds($forum['moderators'],array('chstatust','chstatus','mchstatust','mchstatus'),$config['n'].'_moders_fp'.$forum['id'].$Forum->language);
			else
				$moder=false;

			if(($topic['status']==0 or $Forum->user and $Forum->user['id']!=$topic['author_id']) and (!$moder or !in_array(1,$moder['chstatust']) and !in_array(1,$moder['mchstatust'])))
				goto ExitPage;

			$active=$topic['status']==1;
			$modposts=$moder && (in_array(1,$moder['chstatus']) or in_array(1,$moder['mchstatus']));

			if(!$modposts)
			{
				$rights=$Forum->ForumRights($topic['f']);
				$usermod=in_array('mod',$rights);
			}
		}

		if(!isset($post))
		{
			$q='SELECT `id`,`status`,`author_id`,`sortdate`,UNIX_TIMESTAMP(`sortdate`) `_sd` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`='.($active ? -1 : -3);

			if($Forum->user)
			{
				$gp=$gt=array();
				if(!$modposts and !$usermod)
					$q.=' AND `author_id`='.$Forum->user['id'];
			}
			else
			{
				$gp=$Forum->GuestSign('p');
				$gt=$Forum->GuestSign(`t`);
				if(!$gp)
					$q='';
				elseif(!$modposts and !$usermod)
					$q.=' AND `id`'.Eleanor::$Db->In($gp);
			}

			if($q)
			{
				$R=Eleanor::$Db->Query($q.' ORDER BY `sortdate` DESC LIMIT 1');
				$inact=$R->fetch_assoc();
			}
			else
				$inact=false;

			if($modposts)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`status`,`author_id`,`sortdate`,UNIX_TIMESTAMP(`sortdate`) `_sd` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`=0 ORDER BY `sortdate` DESC LIMIT 1');
				if($a=$R->fetch_assoc() and (!$inact or $a['_sd']>$inact['_sd']))
					$inact=$a;
			}

			$R=Eleanor::$Db->Query('SELECT `id`,`status`,`sortdate`,UNIX_TIMESTAMP(`sortdate`) `_sd` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`='.($active ? 1 : -2).' ORDER BY `sortdate` DESC LIMIT 1');
			$act=$R->fetch_assoc();

			if($act and $inact)
				$post=$act['_sd']>$inact['_sd'] ? $act : $inact;
			elseif($act or $inact)
				$post=$act ? $act : $inact;
			else
				goto ExitPage;
		}

		$qq='';
		if(in_array($post['status'],array(-3,-1)))
			if($modposts or $usermod)
				$qq='UNION ALL(SELECT COUNT(`sortdate`) `cnt` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`='.($active ? -1 : -3).' AND `sortdate`<\''.$post['sortdate'].'\')';
			else
				$qq='UNION ALL(SELECT COUNT(`sortdate`) `cnt` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `author_id`='.$Forum->user['id'].' AND `sortdate`<\''.$post['sortdate'].'\')';

		$R=Eleanor::$Db->Query('SELECT SUM(`c`.`cnt`) FROM (
			(SELECT COUNT(`sortdate`) `cnt` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`='.($active ? 1 : -2).' AND `sortdate`<\''.$post['sortdate'].'\')'
			.($modposts ? 'UNION ALL(SELECT COUNT(`sortdate`) `cnt` FROM `'.$config['fp'].'` WHERE `t`='.$topic['id'].' AND `status`=0 AND `sortdate`<\''.$post['sortdate'].'\')' : '')
			.$qq
		.') `c`');
		list($cnt)=$R->fetch_row();

		$page=(int)($cnt/$Forum->vars['ppp'])+1;
		return GoAway($Forum->Links->Topic($topic['f'],$topic['id'],$topic['uri'],array('page'=>$page>1 ? $page : false)),301,'post'.$id);
	case'topic':
		ShowTopic((int)$id);
	break;
	case'post':
		ShowPost((int)$id);
}

/**
 * Отображение темы. Первым параметром сделать $turi невозможно по причине того, что может возникнуть неоднозначность.
 * @param array|int $furi Определяет URI путь к форуму или ID темы
 * @param string $turi URI темы. В случае, если $furi определяет ID темы, параметр игнорируется
 * @param array $filter Значение фильтров постов.
 */
function ShowTopic($furi,$turi='',$filter=array())
{global$Eleanor,$title;
	$Forum = $Eleanor->Forum;
	$config=$Forum->config;

	$q='SELECT `id`,`uri`,`f`,`language`,`status`,`author_id`,`state`,`moved_to`,`title`,`description`,`posts`,`queued_posts`,`last_mod`,`pinned`>\''.date('Y-m-d H:i:s').'\' `_pin` FROM `'.$config['ft'].'` WHERE ';
	if(is_array($furi))
	{
		$forum= $Forum->Forums->GetForum($furi);
		if(!$forum)
		{
			ExitPage:
			return ExitPage();
		}
		$q.='`f`='.$forum['id'].' AND `language`=\''.$forum['language'].'\' AND `uri`='.Eleanor::$Db->Escape($turi);
	}
	else
		$q.='`id`='.$furi;
	$R=Eleanor::$Db->Query($q.' LIMIT 1');
	if(!$topic=$R->fetch_assoc() or !$Forum->CheckTopicAccess($topic))
		goto ExitPage;

	if(in_array($topic['state'],array('moved','merged')))
		return GoAway(Eleanor::$site_path.$Forum->Links->Topic($topic['moved_to_forum'],$topic['moved_to']),301);

	if(!isset($forum))
		$forum=$Forum->Forums->GetForum($topic['f']);

	#модераторы форума + наши права, как модератора
	if($forum['moderators'])
		list($forum['moderators'],$forum['_moderator'])= $Forum->Moderator->ByIds($forum['moderators'],array('movet','move','deletet','delete','editt','edit','chstatust','chstatus','pin','mmove','mdelete','user_warn','viewip','opcl','merge','editq','mchstatus'),$Eleanor->mconfig['n'].'_moders_t'.$forum['id']);
	else
		$forum['moderators']=$forum['_moderator']=array();

	if($Forum->user)
		$my=$Forum->user['id']==$topic['author_id'];
	else
	{
		$gp=$Forum->GuestSign('p');
		$gt=$Forum->GuestSign(`t`);

		$my=in_array($topic['id'],$gt);
		$ingp=$gp ? Eleanor::$Db->In($gp) : false;
	}

	if($topic['status']!=1 and ($topic['status']==0 or !$my) and (!$forum['_moderator'] or !in_array(1,$forum['_moderator']['chstatust']) and !in_array(1,$forum['_moderator']['mchstatust'])))
		goto ExitPage;

	$R=Eleanor::$Db->Query('SELECT `rules` FROM `'. $config['fl'].'` WHERE `language`IN(\'\',\''.$topic['language'].'\') AND `id`='.$forum['id'].' LIMIT 1');
	if($R->num_rows==0)
		goto ExitPage;
	$forum+=$R->fetch_assoc();

	if($forum['rules'])
		$forum['rules']=OwnBB::Parse($forum['rules']);

	$rights=$Forum->ForumRights($forum['id']);
	$rights+=array(
		#Возможность "модерировать" свою тему: править / удалять / перемещать сообщения в своей теме
		'_mod'=>in_array(1,$rights['mod']) and $my,
		#Возможность просматривать свои посты с разными статусами
		'_status'=> $Forum->user or $gp,
		#Возможность просматривать чужие посты с разными статусами и менять эти статусы (модератор)
		'_toggle'=>$Forum->ugr['supermod'] || ($forum['_moderator'] ? in_array(1,$forum['_moderator']['chstatus']) || in_array(1,$forum['_moderator']['mchstatus']) : false),

		'_mchstatus'=>$forum['_moderator'] && in_array(1,$forum['_moderator']['mchstatus']),
		'_mdelete'=>$forum['_moderator'] && in_array(1,$forum['_moderator']['mdelete']),
		'_mmove'=>$forum['_moderator'] && in_array(1,$forum['_moderator']['mmove']),
		'_merge'=>$forum['_moderator'] && in_array(1,$forum['_moderator']['merge']),
	);

	$topic+=array(
		#TimeStamp прочтения темы
		'_read'=>0,
		#Количество постов в теме
		'_cnt'=>0,
		#Текущая страница
		'_page'=>isset($_GET['page']) ? (int)$_GET['page'] : 1,
		#Количество страниц
		'_pages'=>1,
		#Количество постов на страницу
		'_pp'=>$Forum->vars['ppp'],
		#Эту тему создал я?
		'_my'=>$my,
		#По-факту тема для нас открыта?
		'_open'=>$topic['state']=='open' || $Forum->ugr['supermod'] || in_array(1,$rights['canclose']),
		#Фильтры
		'_filter'=>array(),
		#Инофрмация о статусах постов в данной теме
		'_statuses'=>array(-1=>0,0,0),
	);

	$forum+=array(
		#TimeStamp прочтения форума
		'_read'=>0,
		#Является ли форум мусорником
		'_trash'=>$Forum->vars['trash']==$forum['id'],
	);

	if($topic['_page']<1)
		$topic['_page']=1;

	$errors=$checked=$authors=$attaches=$posts=$where=array();
	if(isset($_POST['mm']) and ($forum['_moderator'] or $Forum->ugr['supermod']))
	{
		isset($_POST['mm']['p']) ? (array)$_POST['mm']['p'] : array();
		include_once __DIR__.'/topic-moderate.php';
		try
		{
			$info=PostsModerate($forum,$topic,$rights,$checked);
			#Отображение какой-то промежуточной формы
			if($info===false)
				return;
		}
		catch(EE_SQL$E)
		{
			$E->Log();
			$errors[]=$E->getMessage();
		}
		catch(EE$E)
		{
			$errors[]=$E->getMessage();
		}
	}
	elseif(isset($_POST['moderate']))
	{
		include_once __DIR__.'/topic-moderate.php';
		try
		{
			$info=TopicModerate($forum,$topic,$rights);
			#Отображение какой-то промежуточной формы
			if($info===false)
				return;
		}
		catch(EE_SQL$E)
		{
			$E->Log();
			$errors[]=$E->getMessage();
		}
		catch(EE$E)
		{
			$errors[]=$E->getMessage();
		}
	}

	$links=array(
		#Первая страница темы
		'first-page'=>$Forum->Links->Topic($topic['f'],$topic['id'],$topic['uri']),
		#Новый пост
		'new-post'=>$topic['_open'] && !$forum['_trash'] && (!$Forum->user or !$Forum->user['restrict_post']) && ($my && in_array(1,$rights['post']) || !$my && in_array(1,$rights['apost'])) ? $Forum->Links->Action('new-post',$forum['id']) : false,
	);

	#Remove
	$links['new-post']=false;

	if(!Eleanor::$is_bot and !$my)
	{
		#Не будем увеличивать счетчик просмотров от переходов по страницам новости
		$ref=getenv('HTTP_REFERER');
		$fp=Eleanor::$punycode.Eleanor::$site_path.$links['first-page'];
		$fp=htmlspecialchars_decode($fp,ELENT);
		if(strpos($ref,$fp)===false)
			Eleanor::$Db->Update($config['ft'],array('!views'=>'`views`+1'),'`id`='.$topic['id'].' LIMIT 1');
	}

	$w_status='';#Условия запроса для статуса, из-за особенностей функционирования, пришлось вынести в отдельную переменную
	if(!empty($_REQUEST['fi']) and is_array($_REQUEST['fi']))
	{
		if($_SERVER['REQUEST_METHOD']=='POST')
			$topic['_page']=1;

		$status=isset($_REQUEST['fi']['status']) ? $_REQUEST['fi']['status'] : false;
		if($status!==false)
		{
			if(is_string($status))
				$status=$status ? explode(',',$status) : array(-1,0,1);
			$status=array_intersect(array(-1=>-1,0,1),$status);
		}

		#Активные посты и так отображаются, поэтому 1 не пишем
		if($status and $status!=array(1=>1))
		{
			if(!$rights['_toggle'])
				$_REQUEST['fi']['my']=1;
			elseif($rights['_status'])
			{
				$topic['_filter']['status']=$status;
				$w_status=' AND `status`'.Eleanor::$Db->In($status);
			}
		}
		else
			$status=false;

		#Мои посты
		if(isset($_REQUEST['fi']['my']) and $Forum->user || $ingp)
		{
			$topic['_filter']['my']=1;
			if($status and $status!=array(1=>1))
			{
				$topic['_filter']['status']=(int)$_REQUEST['fi']['status'];
				if(!$rights['_toggle'])
					unset($status[0]);
				$topic['_filter']['status']=$status;
				$w_status=' AND `status`'.Eleanor::$Db->In($status);
			}
			$where['author']= $Forum->user ? ' AND `author_id`='. $Forum->user['id'] : ' AND `id`'.$ingp;
		}
	}
	else
		$status=false;

	#Создание запроса
	$where=' FROM `'. $config['fp'].'` WHERE `t`='.$topic['id'].join($where);

	if($rights['_toggle'])
	{
		$R=Eleanor::$Db->Query('SELECT `status`,COUNT(`status`)'.$where.' GROUP BY `status`');
		while($a=$R->fetch_row())
			$topic['_statuses'][$a[0]]+=$a[1];
	}
	else
	{
		$R=Eleanor::$Db->Query('SELECT COUNT(`status`) `cnt`'.$where.' AND `status`=1');
		if($a=$R->fetch_assoc())
			$topic['_statuses'][1]=$a['cnt'];

		if($topic['queued_posts']>0)
		{
			if($Forum->user)
			{
				$R=Eleanor::$Db->Query('SELECT COUNT(`t`) `cnt`'.$where.' AND `status`=-1 AND `author_id`='. $Forum->user['id']);
				list($topic['_statuses'][-1])=$R->fetch_row();
			}
			elseif($ingp)
			{
				$R=Eleanor::$Db->Query('SELECT COUNT(`t`) `cnt`'.$where.' AND `status`=-1 AND `id`'.$ingp);
				list($topic['_statuses'][-1])=$R->fetch_row();
			}
		}
	}

	#Теперь к условию можно добавить и условие отбора по статусу
	$where.=$w_status ? $w_status : ' AND `status`=1';

	#Подсчет количества постов
	if($status)
		foreach($status as $v)
			$topic['_cnt']+=$topic['_statuses'][$v];
	else
		$topic['_cnt']=$topic['_statuses'][1];

	if($topic['_cnt']>0)
	{
		$offset=($topic['_page']-1)*$Forum->vars['ppp'];
		$cnt=$topic['_cnt']+$topic['_statuses'][-1];;
		if($cnt and $offset>=$cnt)
			$offset=max(0,$cnt-$Forum->vars['ppp']);
		$topic['_pages']=ceil($topic['_cnt']/ $Forum->vars['ppp']);

		if($Forum->user)
		{
			#Репутация
			$rplus=$Forum->user['posts']>=$Forum->vars['r+'];
			$rminus=$Forum->user['posts']>=$Forum->vars['r-'];

			#Читанность форума для пользователя
			$topic['_read']=$forum['_read']=$Forum->user['allread'];
			$R=Eleanor::$Db->Query('SELECT UNIX_TIMESTAMP(`allread`) `allread`,`topics` FROM `'.$config['re'].'` WHERE `f`='.$forum['id'].' AND `uid`='. $Forum->user['id'].' LIMIT 1');
			if($a=$R->fetch_assoc())
			{
				$a['topics']=$a['topics'] ? (array)unserialize($a['topics']) : array();
				if($forum['_read']<$a['allread'])
					$forum['_read']=$forum['_read'];
				$topic['_read']=max($forum['_read'],isset($a['topics'][ $topic['id'] ]) ? $a['topics'][ $topic['id'] ] : 0);
			}

			#Подписка
			$R=Eleanor::$Db->Query('SELECT `intensity` FROM `'.$config['ts'].'` WHERE `t`='.$topic['id'].' AND `uid`='. $Forum->user['id'].' LIMIT 1');
			if($R->num_rows>0)
				list($topic['_subscription'])=$R->fetch_row();
			else
				$topic['_subscription']=false;
		}
		else
		{
			#Репутация
			$rplus=$rminus=false;

			#Читанность темы для гостя
			$topic['_read']=$forum['_read']=(int)Eleanor::GetCookie($config['n'].'-ar');
			$fr=Eleanor::GetCookie($config['n'].'-fr');
			$tr=Eleanor::GetCookie($config['n'].'-tr');

			if($fr)
			{
				$fr=explode(',',$fr);
				$freads=array();
				foreach($fr as $v)
					if(strpos($v,'-')!==false)
					{
						$v=explode('-',$v,2);
						$freads[ $v[0] ]=(int)$v[0];
					}
				$fr=$freads;
				if(isset($fr[ $forum['id'] ]) and $fr[$forum['id'] ]>$forum['_read'])
					$topic['_read']=$forum['_read']=$fr[ $forum['id'] ];
			}

			if($tr)
			{
				$tr=explode(',',$tr);
				$treads=array();
				foreach($tr as $v)
					if(strpos($v,'-')!==false)
					{
						$v=explode('-',$v,2);
						$treads[ $v[0] ]=(int)$v[0];
					}
				$tr=$treads;

				if(isset($tr[ $topic['id'] ]) and $tr[ $topic['id'] ]>$topic['_read'])
					$topic['_read']=$tr[ $topic['id'] ];
			}
			unset($tr,$fr,$freads,$treads);
		}

		$lastmod=strtotime($topic['last_mod']);
		$t=time();
		$mod=$Forum->ugr['supermod'] || $rights['_mod'];

		if($forum['_moderator'])
		{
			$medit=in_array(1,$forum['_moderator']['edit']);
			$mdelete=in_array(1,$forum['_moderator']['edit']);
		}
		else
			$medit=$mdelete=false;

		$edit=in_array(1,$rights['edit']);
		$delete=in_array(1,$rights['delete']);

		$tread=$topic['_read'];
		$hasquote=false;

		$quotes=Eleanor::GetCookie($config['n'].'-qp');
		$quotes=$quotes ? array_map(function($v){ return(int)$v; },explode(',',$quotes)) : array();

		$R=Eleanor::$Db->Query('SELECT `id`,`status`,`author`,`author_id`,`ip`,`created`,`sortdate`,`edited`,`edited_by`,`edited_by_id`,`edit_reason`,`approved`,`approved_by`,`approved_by_id`,`text`,`last_mod`'.$where.' ORDER BY `sortdate` ASC LIMIT '.$offset.','.$Forum->vars['ppp']);
		while($a=$R->fetch_assoc())
		{
			switch($a['status'])
			{
				case -3;
					$a['status']=-1;
					break;
				case -2:
					$a['status']=1;
			}

			$a['_quoted']=in_array($a['id'],$quotes);#Пост процитирован
			$a['_approved']=$a['_rejected']=array();#Одобрено и отвергнуто
			$a['_my']=$Forum->user && $a['author_id']== $Forum->user['id'] || !$Forum->user && in_array($a['id'],$gp);
			if($a['_my'])
				$a['_r+']=$a['_r-']=false;
			else
			{
				$a['_r+']=$rplus;
				$a['_r-']=$rminus;
			}
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
			if((!$a['_edit'] or !$a['_delete']) and $a['_my'])
			{
				$mined=min($forum['_rights']['editlimit']);
				if($mined==0 or $t-strtotime($a['created'])<=$mined)
				{
					$a['_edit']|=$edit;
					$a['_delete']|=$delete;
				}
			}
			if($a['_edit'])
				$a['_edit']=$Forum->Links->Action('edit',$a['id']);

			$posts[ $a['id'] ]=array_slice($a,1);

			if($a['author_id'])
				$authors[]=$a['author_id'];
			if($a['edited_by_id'])
				$authors[]=$a['edited_by_id'];
			if($a['approved_by_id'])
				$authors[]=$a['approved_by_id'];

			$topic['_read']=max($topic['_read'],strtotime($a['created']));
			$lastmod=max($lastmod,strtotime($a['last_mod']));
			$hasquote|=strpos($a['text'],'[quote')!==false;
		}

		#Пометим тему прочтенной
		if($topic['_read']>$tread)
			$Forum->Topic->MarkRead($topic['id'],$forum['id'],$topic['_read']);

		#Если просмотр этой страницы мог хоть как-то повлиять на прочитанность форума...
		if($topic['_read']>$forum['_read'])
		{
			$R=Eleanor::$Db->Query('SELECT UNIX_TIMESTAMP(MAX(`lp_date`)) `lp_date` FROM `'. $config['fl'].'` WHERE `id`='.$forum['id']);
			if($a=$R->fetch_assoc() and $a['lp_date']<=$topic['_read'])
			{
				#Попытаемся извлечь хоть одну тему, которую мы не читали, но у которой lp_date>$topic['_read']
				#key! mark_forum_read
				$R=Eleanor::$Db->Query('SELECT UNIX_TIMESTAMP(`lp_date`) `lp_date` FROM `'. $config['ft'].'` WHERE `f`='.$forum['id'].' AND `status`=1 AND `lp_date`>FROM_UNIXTIME('.$topic['_read'].') ORDER BY `lp_date` ASC LIMIT 1');
				$Forum->Forums->MarkRead($forum['id'],$a=$R->fetch_assoc() ? $a['lp_date']-1 : $topic['_read']);
			}
		}

		if($Forum->user)
			Eleanor::$Db->Update($config['ts'],array('sent'=>0,'!lastview'=>'FROM_UNIXTIME('.$topic['_read'].')'),'`uid`='.$Forum->user['id'].' AND `t`='.$topic['id'].' AND `lastview`<FROM_UNIXTIME('.$topic['_read'].') LIMIT 1');

		if(Eleanor::$caching)
		{
			Eleanor::$last_mod=$lastmod;
			$etag=Eleanor::$etag;
			$uid= $Forum->user ? $Forum->user['id'] : 0;
			Eleanor::$etag=md5($topic['id'].$where.$uid.$Eleanor->module['etag']);
			if(Eleanor::$modified and Eleanor::$last_mod and Eleanor::$last_mod<=Eleanor::$modified and $etag and $etag==Eleanor::$etag)
				return Start();
			Eleanor::$modified=false;
		}

		$inp=$posts ? Eleanor::$Db->In(array_keys($posts)) : false;
		if($inp)
		{
			if($Forum->user)
			{
				$R=Eleanor::$Db->Query('SELECT `p` FROM `'.$config['fr'].'` WHERE `from`='.$Forum->user['id'].' AND `p`'.$inp);
				while($a=$R->fetch_assoc())
					$posts[ $a['p'] ]['_r+']=$posts[ $a['p'] ]['_r-']=false;
			}

			$q='SELECT `id`,`p`,`downloads`,`size`,IF(`name`=\'\',`file`,`name`) `name`,`file` FROM `'.$config['fa'].'` WHERE ';
			$q1=$q.'`p`'.$inp;
			$q2=$attaches ? $q.'`id`'.Eleanor::$Db->In($attaches) : false;
			$R=Eleanor::$Db->Query($q2 ? '('.$q1.')UNION('.$q2.')' : $q1);
			$attaches=array();
			while($a=$R->fetch_assoc())
			{
				if(isset($posts[ $a['p'] ]))
					$posts[ $a['p'] ]['_attached'][]=$a['id'];

				$a['_a']=$config['download'].$a['id'];
				$a['_path']=$config['attachpath'].'p'.$a['p'].'/'.$a['file'];

				$attaches[ $a['id'] ]=array_slice($a,1);
			}

			$replace=$Forum->Attach->DecodePosts($posts,$attaches);
			if(!isset(OwnBB::$replace['quote']) and $hasquote)
			{
				if(!class_exists('ForumBBQoute',false))
					include$Eleanor->module['path'].'Misc/bb-quote.php';
				OwnBB::$replace['quote']='ForumBBQoute';
			}
			foreach($posts as &$v)
			{
				$v['text']=OwnBB::Parse($v['text']);
				if($replace)
					$v['text']=str_replace($replace['from'],$replace['to'],$v['text']);
			}

			$R=Eleanor::$Db->Query('SELECT `id`,`p`,`from`,`from_name`,`value` FROM `'.$config['fr'].'` WHERE `p`'.$inp);
			while($a=$R->fetch_assoc())
			{
				if($a['from'])
					$authors[]=$a['from'];
				$posts[ $a['p'] ][$a['value']>0 ? '_approved' : '_rejected'][ $a['id'] ]=array_slice($a,2);
			}
		}
	}

	$title=array($topic['title'],$forum['title']);
	$p1=reset($posts);
	$Eleanor->module['description']=$topic['description'] && $topic['_page']==1 ? $topic['description'].' ' : '';
	$Eleanor->module['description'].=Strings::CutStr(strip_tags(str_replace("\n",' ',$p1['text'])),250);

	$dynl=Url::Query(array('module'=>$Eleanor->module['name'],'t'=>$topic['id']));
	$links+=array(
		#Редактирование темы
		'edit'=>$my && in_array(1,$rights['editt']) || $Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['editt']) ? $Forum->Links->Action('edit-topic',$topic['id']) : false,
		#Удаление темы
		'delete'=>$my && in_array(1,$rights['deletet']) || $Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['deletet']) ? $Forum->Links->Action('delete-topic',$topic['id']) : false,
		#Ссылка на крон, для рассылки если пришло event=just-created
		'cron'=>isset($_GET['event']) && $_GET['event']=='just-created' ? Eleanor::$services['cron']['file'].'?'.$dynl : false,
		#Ссылка на RSS постов темы
 		'rss'=>Eleanor::$services['rss']['file'].'?'.$dynl,
		#Ссылка на "дэфолтовую" тему, т.е. на первую страницу темы без использования фильтра
		'topic'=>$Forum->Links->Topic($forum['id'],$topic['id'],$topic['uri']),
		#Ссылка на первую страницу
		'first_page'=>$Forum->Links->Topic($forum['id'],$topic['id'],$topic['uri'],array('fi'=>$topic['_filter'])),
		#Ссылка для формы на мультимодерацию постов
		'form_items'=>$Forum->ugr['supermod'] || $forum['_moderator'] && ($rights['_mchstatus'] || $rights['_mmove'] || $rights['_mdelete'] || $rights['_move'])
			? $Forum->Links->Topic($forum['id'],$topic['id'],$topic['uri'],array(
				'fi'=>$topic['_filter'],
				'page'=>$topic['_page']>1 ? $topic['_page'] : false,
			))
			: false,
		#Функция-генератор для страниц
		'pages'=>function($n)use($topic,$forum,$Forum){ return $Forum->Links->Topic($forum['id'],$topic['id'],$topic['uri'],array('fi'=>$topic['_filter'])+array('page'=>$n)); },
		#Новый топик
		'new-topic'=>!$forum['_trash'] && in_array(1,$rights['new']) && (!$Forum->user or !$Forum->user['restrict_post']) ? $Forum->Links->Action('new-topic',$forum['id']) : false,
		#Ссылка на ожидающие посты
		'wait-posts'=>$topic['_statuses'][-1]>0 ? $Forum->Links->Topic($forum['id'],$topic['id'],$topic['uri'],array('fi'=>array('status'=>-1))) : false,
	);

	if(!$topic['_filter'])
		$Eleanor->origurl=PROTOCOL.Eleanor::$punycode.Eleanor::$site_path.$links['form_items'];

	SetData();
	Eleanor::$Template->queue[]=$config['topictpl'];
	unset(OwnBB::$replace['quote']);

	if($a['voting'])
	{
		$Eleanor->Voting=new Voting($topic['voting']);
		$Eleanor->Voting->mid=$Eleanor->module['id'];
		$voting=$Eleanor->Voting->Show(array('module'=>$Eleanor->module['name'],'event'=>'voting','id'=>$topic['id']));
	}
	else
		$voting=false;

	$captcha=$Eleanor->Captcha->disabled ? false : $Eleanor->Captcha->GetCode();
	$values=array(
		#Имя гостя
		'name'=>$Forum->user ? '' : Eleanor::GetCookie($config['n'].'-name'),
		#Текст быстрого ответа. Возможно, здесь будет какой-то инструмент сохранения
		'text'=>'',
	);

	$Eleanor->Editor->preview=array('module'=>$Eleanor->module['name'],'event'=>'preview');
	$c=Eleanor::$Template->ShowTopic($forum,$rights,$topic,$posts,$attaches,$authors ? GetAuthors($authors,$forum) : array(),$errors,$Forum->GetOnline('-f'.$forum['id'].'-t'.$topic['id']),$links,$voting,$values,$captcha);
	Start();
	echo$c;
}

/**
 * Просмотр конкретного поста на форуме
 * @param int $id ID поста
 * @param bool $ajax При вызове данной функции из AJAX, содержимое не выводится, а возвращается
 */
function ShowPost($id,$ajax=false)
{global$Eleanor,$title;
	$Forum = $Eleanor->Forum;
	$config=$Forum->config;

	$R=Eleanor::$Db->Query('SELECT `id`,`t`,`status`,`author`,`author_id`,`ip`,`created`,`sortdate`,`edited`,`edited_by`,`edited_by_id`,`edit_reason`,`approved`,`approved_by`,`approved_by_id`,`text`,UNIX_TIMESTAMP(`last_mod`) `last_mod` FROM '.$config['fp'].' WHERE `id`='.$id.' LIMIT 1');
	if(!$post=$R->fetch_assoc())
	{
		ExitPage:
		return $ajax ? false : ExitPage();
	}

	$R=Eleanor::$Db->Query('SELECT `uri`,`f`,`status`,`state`,`author_id`,`title`,`description` FROM `'.$config['ft'].'` WHERE `id`='.$post['t'].' LIMIT 1');
	if(!$topic=$R->fetch_assoc() or !$Forum->CheckTopicAccess($topic))
		goto ExitPage;

	$status=$post['status'];
	switch($post['status'])
	{
		case -3;
			$post['status']=-1;
		break;
		case -2:
			$post['status']=1;
	}

	$forum=$Forum->Forums->GetForum($topic['f']);

	#модераторы форума + наши права, как модератора
	if($forum['moderators'])
		list($forum['moderators'],$forum['_moderator'])= $Forum->Moderator->ByIds($forum['moderators'],array('movet','move','deletet','delete','editt','edit','chstatust','chstatus','pin','mmove','mdelete','user_warn','viewip','opcl','merge','editq','mchstatus'),$Eleanor->mconfig['n'].'_moders_t'.$forum['id']);
	else
		$forum['moderators']=$forum['_moderator']=array();

	if($Forum->user)
	{
		$mytopic=$Forum->user['id']==$topic['author_id'];
		$mypost=$Forum->user['id']==$post['author_id'];;
	}
	else
	{
		$gp=$Forum->GuestSign('p');
		$gt=$Forum->GuestSign(`t`);

		$mytopic=in_array($topic['id'],$gt);
		$mypost=in_array($id,$gp);
	}

	if($topic['status']!=1 and ($topic['status']==0 or !$mytopic) and (!$forum['_moderator'] or !in_array(1,$forum['_moderator']['chstatust']) and !in_array(1,$forum['_moderator']['mchstatust'])))
		goto ExitPage;

	$toggle=$forum['_moderator'] ? in_array(1,$forum['_moderator']['chstatus']) || in_array(1,$forum['_moderator']['mchstatus']) : false;
	if($post['status']!=1 and ($post['status']==0 or !$mypost) and !$toggle)
		goto ExitPage;

	if($Forum->user)
		Eleanor::$Db->Update($config['ts'],array('sent'=>0,'lastview'=>$post['created']),'`uid`='.$Forum->user['id'].' AND `t`='.$post['t'].' AND `lastview`<\''.$post['created'].'\'');

	if(Eleanor::$caching)
	{
		Eleanor::$last_mod=$post['last_mod'];
		$etag=Eleanor::$etag;
		$uid= $Forum->user ? $Forum->user['id'] : 0;
		Eleanor::$etag=md5($id.$uid.$Eleanor->module['etag']);
		if(Eleanor::$modified and Eleanor::$last_mod and Eleanor::$last_mod<=Eleanor::$modified and $etag and $etag==Eleanor::$etag)
			return Start();
		Eleanor::$modified=false;
	}

	$rights=$Forum->ForumRights($forum['id']);
	$rights+=array(
		#Возможность "модерировать" свою тему: править / удалять / перемещать сообщения в своей теме
		'_mod'=>in_array(1,$rights['mod']) and $mytopic,
	);

	$topic+=array(
		#Количество постов в теме
		'_cnt'=>0,
		#Эту тему создал я?
		'_my'=>$mytopic,
		#По-факту тема для нас открыта?
		'_open'=>$topic['state']=='open' || $Forum->ugr['supermod'] || in_array(1,$rights['canclose']),
	);

	$forum+=array(
		#Является ли форум мусорником
		'_trash'=>$Forum->vars['trash']==$forum['id'],
	);

	$mod=$Forum->ugr['supermod'] || $rights['_mod'];
	if($forum['_moderator'])
	{
		$medit=in_array(1,$forum['_moderator']['edit']);
		$mdelete=in_array(1,$forum['_moderator']['edit']);
	}
	else
		$medit=$mdelete=false;

	$post+=array(
		#Одобрено и отвергнуто
		'_rejected'=>array(),
		'_approved'=>array(),
		#ID предыдущего и следующего поста
		'_prev'=>false,
		'_next'=>false,
		#Номер поста
		'_n'=>0,
		#Возможность поднять репутацию
		'_r+'=>$mypost || $Forum->user ? false : $Forum->user['posts']>=$Forum->vars['r+'],
		#Возможность опустить репутацию
		'_r-'=>$mypost || $Forum->user ? false : $Forum->user['posts']>=$Forum->vars['r-'],
		#Этот пост написал я?
		'_my'=>$mypost,
		#Флаг возможности редактировать пост
		'_edit'=>$mod || $medit ? $Forum->Links->Action('edit',$post['id']) : false,
		#Флаг возможности удалить пост
		'_delete'=>$mod || $mdelete,
		#Ссылка на ответ
		'_answer'=>false,
		#Прикрепленные аттачи
		'_attached'=>array(),
		#Аттачи, используемые в тексте
		'_attaches'=>$Forum->Attach->GetFromText($post['text']),
		#Ссылка на пост в теме
		'_atp'=>$Forum->Links->Action('find-post',$post['id']),
	);

	if($Forum->user)
	{
		$R=Eleanor::$Db->Query('SELECT `p` FROM `'.$config['fr'].'` WHERE `from`='.$Forum->user['id'].' AND `p`='.$id.' LIMIT 1');
		if($R->num_rows>0)
			$posts['_r+']=$posts['_r-']=false;
	}

	$attaches=array();
	$q='SELECT `id`,`p`,`downloads`,`size`,IF(`name`=\'\',`file`,`name`) `name`,`file` FROM `'.$config['fa'].'` WHERE ';
	$q1=$q.'`p`='.$id.' LIMIT 1';
	$q2=$post['_attaches'] ? $q.'`id`'.Eleanor::$Db->In($post['_attaches']) : false;
	$R=Eleanor::$Db->Query($q2 ? '('.$q1.')UNION('.$q2.')' : $q1);
	while($a=$R->fetch_assoc())
	{
		$a['_a']=$config['download'].$a['id'];
		$a['_path']=$config['attachpath'].'p'.$a['p'].'/'.$a['file'];

		$attaches[ $a['id'] ]=array_slice($a,1);
		$post['_attached'][]=$a['id'];
	}

	$R=Eleanor::$Db->Query('SELECT `id`,`p`,`from`,`from_name`,`value` FROM `'.$config['fr'].'` WHERE `p`='.$id.' LIMIT 1');
	if($a=$R->fetch_assoc())
	{
		if($a['from'])
			$authors[]=$a['from'];
		$post[$a['value']>0 ? '_approved' : '_rejected'][ $a['id'] ]=array_slice($a,2);
	}

	$replace=$Forum->Attach->DecodePosts($post,$attaches,true);
	if(!isset(OwnBB::$replace['quote']) and strpos($post['text'],'[quote')!==false)
	{
		if(!class_exists('ForumBBQoute',false))
			include$Eleanor->module['path'].'Misc/bb-quote.php';
		OwnBB::$replace['quote']='ForumBBQoute';
	}

	$post['text']=OwnBB::Parse($post['text']);
	if($replace)
		$post['text']=str_replace($replace['from'],$replace['to'],$post['text']);

	if((!$post['_edit'] or !$post['_delete']) and $mypost)
	{
		$mined=min($forum['_rights']['editlimit']);
		if($mined==0 or time()-strtotime($post['created'])<=$mined)
		{
			$post['_edit']|=in_array(1,$rights['edit']);
			$post['_delete']|=in_array(1,$rights['delete']);
		}
	}

	#Author filter
	if($post['status']==1 or $toggle)
		$af='';
	elseif($Forum->user)
		$af=' AND `author_id`='.$Forum->user['id'];
	elseif($gp)
		$af=' AND `id`'.Eleanor::$Db->In($gp);
	else
		goto ExitPage;

	$R=Eleanor::$Db->Query('(SELECT COUNT(`t`)+1 `data` FROM `'.$config['fp'].'` WHERE `t`='.$post['t'].' AND `status`='.$status.$af.' AND `sortdate`<\''.$post['sortdate'].'\')
UNION ALL (SELECT COUNT(`t`) `data` FROM `'.$config['fp'].'` WHERE `t`='.$post['t'].' AND `status`='.$status.$af.')');
	list($post['_n'])=$R->fetch_row();
	list($topic['_cnt'])=$R->fetch_row();

	$links=array(
		#Ссылка на RSS постов темы
		'rss'=>Eleanor::$services['rss']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'t'=>$post['t'])),
		#Ссылка активации поста
		'activate'=>$post['status']!=1 && $toggle ? $Forum->Links->Action('activate-post',$id) : false,
		#Ссылка на предыдущий пост
		'prev'=>false,
		#Ссылка на следующий пост
		'next'=>false,
		#Новый пост
		'new-post'=>$topic['_open'] && !$forum['_trash'] && (!$Forum->user or !$Forum->user['restrict_post']) && ($mytopic && in_array(1,$rights['post']) || !$mytopic && in_array(1,$rights['apost'])) ? $Forum->Links->Action('new-post',$post['t']) : false,
		#Новый топик
		'new-topic'=>!$forum['_trash'] && in_array(1,$rights['new']) && (!$Forum->user or !$Forum->user['restrict_post']) ? $Forum->Links->Action('new-topic',$forum['id']) : false,
	);

	#Remove
	$links['new-post']=false;

	$post['_answer']=$links['new-post'] ? $Forum->Links->Action('answer',$a['id']) : false;

	if($post['_n']>1)
	{
		$R=Eleanor::$Db->Query('SELECT `id` FROM `'.$config['fp'].'` WHERE `t`='.$post['t'].' AND `status`='.$status.$af.' AND `sortdate`<\''.$post['sortdate'].'\' ORDER BY `sortdate` DESC LIMIT 1');
		list($post['_prev'])=$R->fetch_row();
		if($post['_prev'])
			$links['prev']=$Forum->Links->Action('show-post',$post['_prev']);
	}

	if($post['_n']<$topic['_cnt'])
	{
		$R=Eleanor::$Db->Query('SELECT `id` FROM `'.$config['fp'].'` WHERE `t`='.$post['t'].' AND `status`='.$status.$af.' AND `sortdate`>\''.$post['sortdate'].'\' ORDER BY `sortdate` ASC LIMIT 1');
		list($post['_next'])=$R->fetch_row();
		if($post['_next'])
			$links['next']=$Forum->Links->Action('show-post',$post['_next']);
	}

	if(!$ajax)
	{
		$title=array('Пост #'.$post['_n'],$topic['title']);
		$Eleanor->module['description']=Strings::CutStr(strip_tags(str_replace("\n",' ',$post['text'])),250);
		SetData();
	}

	Eleanor::$Template->queue[]=$config['topictpl'];
	unset(OwnBB::$replace['quote']);

	$authors=array();
	if($post['author_id'])
		$authors[]=$post['author_id'];
	if($a['edited_by_id'])
		$authors[]=$post['edited_by_id'];
	if($a['approved_by_id'])
		$authors[]=$post['approved_by_id'];

	$c=Eleanor::$Template->ShowPost($forum,$rights,$topic,$post,$attaches,$authors ? GetAuthors($authors,$forum) : array(),$Forum->GetOnline('-f'.$forum['id'].'-t'.$post['t'].'-p'.$id),$links,$ajax);
	if($ajax)
		return$c;
	Start();
	echo$c;
}

/**
 * Получение пользователей и групп
 * @param array $ids ID пользователей
 * @param array $forum дамп форума
 * @return array(array,array) пользователи, группы
 */
function GetAuthors($ids,$forum)
{global$Eleanor;
	$ag=array(array(),array());
	$groups=array();
	$lcl=get_class(Eleanor::$Login);
	$t=time();

	$R=Eleanor::$Db->Query('SELECT `id`,`login_keys`,`groups` `_group`,`full_name`,`name`,`register`,`last_visit`,`location`,`signature`,`avatar_location`,`avatar_type`,`statustext`,`posts`,`rep`,`reputation` FROM `'.P.'users_site` INNER JOIN `'.P.'users_extra` USING(`id`) INNER JOIN `'.$Eleanor->Forum->config['fu'].'` USING(`id`) WHERE `id`'.Eleanor::$Db->In($ids));
	while($a=$R->fetch_assoc())
	{
		if($forum['reputation'])
		{
			$a['reputation']=$a['reputation'] ? (array)unserialize($a['reputation']) : array();
			if($a['reputation'])
				$a['reputation']=isset($a['reputation'][ $forum['id'] ]) ? $a['reputation'][ $forum['id'] ] : false;
			else
				$a['reputation']=$a['rep']=false;
		}
		elseif(!$a['reputation'])
			$a['reputation']=$a['rep']=false;
		else
			$a['reputation']=false;

		$a['_online']=false;
		$a['login_keys']=$a['login_keys'] ? (array)unserialize($a['login_keys']) : array();
		if(isset($a['login_keys'][$lcl]))
			foreach($a['login_keys'][$lcl] as $v)
				if($v[0]>$t)
				{
					$a['_online']=true;
					break;
				}

		$a['signature']=OwnBB::Parse($a['signature']);

		$a['_a']=Eleanor::$Login->UserLink($a['name'],$a['id']);
		$a['_arep']=$Eleanor->Forum->Links->Action('reputation',$a['id']);
		$a['_afrep']=$Eleanor->Forum->Links->Action('reputation',$a['id'],array('f'=>$forum['id']));

		$groups[]=$a['_group']=(int)ltrim($a['_group'],',');
		$ag[0][$a['id']]=array_slice($a,2);
	}

	if($groups)
	{
		$R=Eleanor::$Db->Query('SELECT `id`,`title_l` `title`,`html_pref`,`html_end` FROM `'.P.'groups` WHERE `id`'.Eleanor::$Db->In($groups));
		while($a=$R->fetch_assoc())
		{
			$a['_a']=$Eleanor->Forum->Links->Action('users',false,array('fi'=>array('group'=>$a['id'])));
			$a['title']=$a['title'] ? Eleanor::FilterLangValues((array)unserialize($a['title'])) : '';
			$ag[1][ $a['id'] ]=array_slice($a,1);
		}
	}
	return$ag;
}