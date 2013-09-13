<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

/**
 * Отображение конретного форума (либо категории, что без тем)
 * @param int ID форума
 */
function ShowForum($id)
{global$Eleanor,$title;
	$Forum = $Eleanor->Forum;
	$forum = $Forum->Forums->GetForum($id);
	if(!$forum)
		return ExitPage();

	$Forum->LoadUser();

	#Возможно, к форуму нет доступа
	$rights= $Forum->ForumRights($forum['id']);
	if(!in_array(1,$rights['access']))
		return ExitPage();

	$forums=Forums($forum['id']);

	$config = $Forum->config;
	$R=Eleanor::$Db->Query('SELECT `f`.*,`lp`.`lp_date` FROM
	(SELECT `id`,`description`,`meta_title`,`meta_descr`,`rules`,`queued_topics` FROM `'. $config['f'].'` INNER JOIN `'. $config['fl'].'` USING(`id`) WHERE `language`IN(\'\',\''.$Forum->language.'\') AND `id`='.$forum['id'].' LIMIT 1) `f`,
	(SELECT UNIX_TIMESTAMP(MAX(`lp_date`)) `lp_date` FROM `'. $config['fl'].'` WHERE `id`='.$forum['id'].') `lp`');
	if($R->num_rows==0)
		return ExitPage();
	$forum+=$R->fetch_assoc();

	if($forum['rules'])
		$forum['rules']=OwnBB::Parse($forum['rules']);

	if($forum['meta_title'])
		$title=$forum['meta_title'];
	else
		$title[]=$forum['title'];

	if(!$Forum->user)
	{
		$gt= $Forum->GuestSign('t');
		$ingt=$gt ? Eleanor::$Db->In($gt) : false;

		$gp= $Forum->GuestSign('p');
	}

	$errors=$links=$info=array();
	if(!$forum['is_category'] and in_array(1,$rights['topics']))
	{
		#В этой переменной хранятся все модераторы форума + наши права, как модератора
		if($forum['moderators'])
			list($forum['moderators'],$forum['_moderator'])= $Forum->Moderator->ByIds($forum['moderators'],array('chstatust','chstatus','mmovet','mdeletet','mopcl','mpin','merget','mchstatust','mchstatus'), $config['n'].'_moders_mm'.$forum['id'].$Forum->language);
		else
			$forum['moderators']=$forum['_moderator']=array();

		$forum+=array(
			'_trash'=> $Forum->vars['trash']==$forum['id'],#Является ли форум мусорником
			'_read'=>true,#Флаг прочитанности форума
			'_topics'=>array(),#Список тем
			'_filter'=>array(),#Фильтры
			'_moved'=>array(),#Массив дампов форумов, куда перемещаются темы
			'_cnt'=>0,#Всего тем
			'_page'=>isset($_GET['page']) ? (int)$_GET['page'] : 0,#Текущая страница, если нужно скакнуть на последнюю - пишем 0
			'_pages'=>1,#Количество страниц
			'_authors'=>array(),#Информация об авторах
			'_statuses'=>array(-1=>0,0,0),#Инофрмация о статусах тем в данном форуме
		);

		$rights+=array(
			#Возможность просматривать свои темы с разными статусами
			'_status'=> $Forum->user or $ingt,
			#Возможность просматривать чужие темы с разными статусами и менять эти статусы (модератор)
			'_toggle'=> $Forum->ugr['supermod'] or $forum['_moderator'] and (in_array(1,$forum['_moderator']['chstatust']) or in_array(1,$forum['_moderator']['mchstatust'])),
		);

		if(isset($_POST['mm']) and ($forum['_moderator'] or $Forum->ugr['supermod']))
		{
			$checked=isset($_POST['mm']['t']) ? (array)$_POST['mm']['t'] : array();
			include_once __DIR__.'/forums-moderate.php';
			try
			{
				$info=TopicsModerate($forum,$rights,$checked);
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
		else
			$checked=array();

		if($forum['prefixes'])
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'. $config['pl'].'` WHERE `id`'.Eleanor::$Db->In($forum['prefixes']).' AND `language`IN(\'\',\''.$Forum->language.'\')');
			$forum['prefixes']=array();
			while($a=$R->fetch_assoc())
				$forum['prefixes'][ $a['id'] ]=array('title'=>$a['title'],'_cnt'=>0,'_a'=>false);
		}

		if($Forum->user)
		{#Состояние подписки
			$R=Eleanor::$Db->Query('SELECT `intensity` FROM `'. $config['fs'].'` WHERE `f`='.$forum['id'].' AND `uid`='. $Forum->user['id'].' AND `language`=\''.$forum['language'].'\' LIMIT 1');
			list($forum['_subscription'])=$R->fetch_row();
		}

		$where=$wmp=$read=array();
		$w_status='';#Условия запроса для статуса, из-за особенностей функционирования, пришлось вынести в отдельную переменную
		if(!empty($_REQUEST['fi']) and is_array($_REQUEST['fi']))
		{
			if($_SERVER['REQUEST_METHOD']=='POST')
				$forum['_page']=0;

			$status=isset($_REQUEST['fi']['status']) ? $_REQUEST['fi']['status'] : false;
			if($status!==false)
			{
				if(is_string($status))
					$status=$status ? explode(',',$status) : array(-1,0,1);
				$status=array_intersect(array(-1=>-1,0,1),$status);
			}

			#Активные темы и так отображаются, поэтому 1 не пишем
			if($status and $status!=array(1=>1))
			{
				if(!$rights['_toggle'])
					$_REQUEST['fi']['my']=1;
				elseif($rights['_status'])
				{
					$forum['_filter']['status']=$status;
					$w_status=' AND `status`'.Eleanor::$Db->In($status);
				}
			}
			else
				$status=false;

			#Темы, обновленные с даты (updated from)
			if(isset($_REQUEST['fi']['uf']) and is_string($_REQUEST['fi']['uf']) and $ts=strtotime($_REQUEST['fi']['uf']))
			{
				$forum['_filter']['uf']=$_REQUEST['fi']['uf'];
				$where['lp_date']=' AND `lp_date`>=FROM_UNIXTIME('.$ts.')';
			}
			#Темы, созданные до (created from)
			elseif(isset($_REQUEST['fi']['cf']) and is_string($_REQUEST['fi']['cf']) and $ts=strtotime($_REQUEST['fi']['cf']))
			{
				$forum['_filter']['cf']=$_REQUEST['fi']['cf'];
				$where['created']=' AND `created`>=FROM_UNIXTIME('.$ts.')';
			}
			#Темы за последние N дней (last days)
			elseif(isset($_REQUEST['fi']['ld']) and 0<$ld=(int)$_REQUEST['fi']['ld'] and $ld<300)
			{
				$forum['_filter']['ld']=$ld;
				$where['lp_date']=' AND `lp_date`>=\''.date('Y-m-d H:i:s').'\' - INTERVAL '.$ld.' DAY';
			}

			#Мои темы
			if(isset($_REQUEST['fi']['my']) and $Forum->user || $ingt)
			{
				$forum['_filter']['my']=1;
				if($status and $status!=array(1=>1))
				{
					$forum['_filter']['status']=(int)$_REQUEST['fi']['status'];
					if(!$rights['_toggle'])
						unset($status[0]);
					$forum['_filter']['status']=$status;
					$w_status=' AND `status`'.Eleanor::$Db->In($status);
				}
				$where['author']= $Forum->user ? ' AND `author_id`='. $Forum->user['id'] : ' AND `id`'.$ingt;
			}

			#Префикс
			if(isset($_REQUEST['fi']['prefix']) and isset($forum['prefixes'][ $_REQUEST['fi']['prefix'] ]))
			{
				$forum['_filter']['prefix']=(int)$_REQUEST['fi']['prefix'];
				$where['lp_date']=' AND `prefix`='.$forum['_filter']['prefix'];
			}
		}
		else
			$status=false;

		#Создание запроса
		$where=' FROM `'. $config['ft'].'` WHERE `f`='.$forum['id'].join($where).' AND `language`=\''.$forum['language'].'\'';

		if($forum['prefixes'])
		{
			$R=Eleanor::$Db->Query('SELECT `prefix`, COUNT(`prefix`) `cnt`'.$where.' AND `prefix`'.Eleanor::$Db->In(array_keys($forum['prefixes'])).' GROUP BY `prefix`');
			while($a=$R->fetch_row())
			{
				$forum['prefixes'][ $a[0] ]['_cnt']=$a[1];
				$forum['prefixes'][ $a[0] ]['_a']= $Forum->Links->Forum($forum['id'],array('fi'=>array('prefix'=>$a[0])+$forum['_filter']));
			}
		}

		if($rights['_toggle'])
		{
			$R=Eleanor::$Db->Query('SELECT `status`,COUNT(`status`)'.$where.'GROUP BY `status`');
			while($a=$R->fetch_row())
				$forum['_statuses'][$a[0]]+=$a[1];
		}
		else
		{
			$R=Eleanor::$Db->Query('SELECT COUNT(`status`) `cnt`'.$where.'AND `status`=1');
			if($a=$R->fetch_assoc())
				$forum['_statuses'][1]=$a['cnt'];

			if($forum['queued_topics']>0)
			{
				if($Forum->user)
				{
					$R=Eleanor::$Db->Query('SELECT COUNT(`f`) `cnt`'.$where.'AND `status`=-1 AND `author_id`='. $Forum->user['id']);
					list($forum['_statuses'][-1])=$R->fetch_row();
				}
				elseif($ingt)
				{
					$R=Eleanor::$Db->Query('SELECT COUNT(`f`) `cnt`'.$where.'AND `status`=-1 AND `id`'.$ingt);
					list($forum['_statuses'][-1])=$R->fetch_row();
				}
			}
		}

		#Теперь к условию можно добавить и условие отбора по статусу
		$where.=$w_status ? $w_status : ' AND `status`=1';

		#Подсчет количества тем
		if($status)
			foreach($status as $v)
				$forum['_cnt']+=$forum['_statuses'][$v];
		else
			$forum['_cnt']=$forum['_statuses'][1];

		if($forum['_cnt']>0)
		{
			$np=$forum['_cnt'] % $Forum->vars['tpp'];
			$forum['_pages']=max(ceil($forum['_cnt']/ $Forum->vars['tpp'])-($np>0 ? 1 : 0),1);
			if($forum['_page']<1)
				$forum['_page']=$forum['_pages'];
			$intpage=$forum['_pages'] - $forum['_page'] + 1;
			$offset=max(0,$intpage-1)* $Forum->vars['tpp'];

			$limit= $Forum->vars['tpp'];
			if($offset==0)
				$limit+=$np;
			else
				$offset+=$np;

			$allread=$fread=$lastmod=0;#Дата полного чтения, дата чтения конкретно этого форума, дата последнего изменения форума для кэша
			#Читанность форума для пользователя
			if($Forum->user)
			{
				$R=Eleanor::$Db->Query('SELECT UNIX_TIMESTAMP(`allread`) `allread`,`topics` FROM `'. $config['re'].'` WHERE `uid`='. $Forum->user['id'].' AND `f`='.$forum['id'].' LIMIT 1');
				if($a=$R->fetch_row())
				{
					$fread=$a[0];
					$allread=max($a[0], $Forum->user['allread']);
					$read=$a[1] ? (array)unserialize($a[1]) : array();
				}
			}
			#Читанность форума для гостя
			else
			{
				$allread=(int)Eleanor::GetCookie($config['n'].'-ar');
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
					if(isset($fr[ $forum['id'] ]) and $fr[ $forum['id'] ]>$allread)
						$fread=$allread=$fr[ $forum['id'] ];
				}

				#Читанность тем для гостя
				if($tr)
				{
					$tr=explode(',',$tr);
					foreach($tr as $v)
						if(strpos($v,'-')!==false)
						{
							$v=explode('-',$v,2);
							$read[ $v[0] ]=(int)$v[1];
						}
				}
			}
			if($forum['lp_date']>$allread)
				$forum['_read']=false;

			#Ссылка на ожидающие посты темы (topic wait posts)
			$twp= $Forum->ugr['supermod'] || $forum['_moderator'] && (in_array(1,$forum['_moderator']['chstatus']) || in_array(1,$forum['_moderator']['mchstatus']));
			$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`prefix`,`status`,`created`,`author`,`author_id`,`state`,`moved_to`,`moved_to_forum`,`who_moved`,`who_moved_id`,`when_moved`,`title`,`description`,`posts`,`queued_posts`,`views`,`pinned`>\''.date('Y-m-d H:i:s').'\' `_pin`,`lp_date`,`lp_id`,`lp_author`,`lp_author_id`,`voting`,`last_mod`'.$where.' ORDER BY `sortdate` DESC LIMIT '.$offset.','.$limit);
			while($a=$R->fetch_assoc())
			{
				$lv=strtotime($a['lp_date']);
				$lastmod=max($lv,strtotime($a['last_mod']),$lastmod);

				if($lv<=$allread)
					$a['_read']=true;
				else
				{
					$a['_read']=isset($read[ $a['id'] ]) && $read[ $a['id'] ]>=$lv;
					if(!$a['_read'])
						$forum['_read']=false;
				}

				switch($a['state'])
				{
					case'moved':
						if(!isset($forum['_moved'][ $a['moved_to_forum'] ]))
						{
							$mf= $Forum->Forums->GetForum($a['moved_to_forum']);
							$mfr= $Forum->ForumRights($a['moved_to_forum']);
							$forum['_moved'][ $a['moved_to_forum'] ]=in_array(1,$mfr['access']) ? $mf : false;
						}
						$a['_a']=$forum['_moved'][ $a['moved_to_forum'] ] ? $Forum->Links->Topic($a['moved_to_forum'],$a['moved_to']) : false;
						$a['_twp']=false;
						$a['_alp']= $Forum->Links->Action('go-last-post',$a['moved_to']);
						$a['_anp']=$a['_read'] ? false : $Forum->Links->Action('go-new-post',$a['moved_id']);
					break;
					case'merged':
						$a['_a']= $Forum->Links->Topic($forum['id'],$a['moved_to']);
						$a['_twp']=false;
						$a['_alp']= $Forum->Links->Action('go-last-post',$a['moved_to']);
						$a['_anp']=$a['_read'] ? false : $Forum->Links->Action('go-new-post',$a['moved_id']);
					break;
					default:
						$a['_a']= $Forum->Links->Topic($forum['id'],$a['id'],$a['uri']);
						$a['_twp']=$twp && $a['queued_posts']>0 ? $Forum->Links->Topic($forum['id'],$a['id'],$a['uri'],array('fi'=>array('status'=>-1))) : false;
						$a['_alp']= $Forum->Links->Action('go-last-post',$a['id']);
						$a['_anp']=$a['_read'] ? false : $Forum->Links->Action('go-new-post',$a['id']);
						$wmp[]=$a['id'];
				}

				$a['_wmp']=false;
				$a['_checked']=in_array($a['id'],$checked);

				$forum['_topics'][ $a['id'] ]=array_slice($a,1);

				if($a['author_id'])
					$forum['_authors'][]=$a['author_id'];
				if($a['who_moved_id'])
					$forum['_authors'][]=$a['who_moved_id'];
				if($a['lp_author_id'])
					$forum['_authors'][]=$a['lp_author_id'];
			}

			#Возможно, мы уже прочли весь форум? Пометим это
			if($forum['_read'])
			{
				#Попытаемся извлечь хоть одну тему, которую мы не читали, но у которой lp_date>$allread.
				#key! mark_forum_read
				$R=Eleanor::$Db->Query('SELECT UNIX_TIMESTAMP(`lp_date`) `lp_date` FROM `'. $config['ft'].'` WHERE `f`='.$forum['id'].' AND `status`=1 AND `lp_date`>FROM_UNIXTIME('.$allread.') ORDER BY `lp_date` ASC LIMIT 1');
				if($a=$R->fetch_assoc())
					$Forum->Forums->MarkRead($forum['id'],$a['lp_date']-1);
				else
				{
					$mark=true;
					if($read)
					{
						$minread=$fread;
						$R=Eleanor::$Db->Query('SELECT `id`,UNIX_TIMESTAMP(`lp_date`) `lp_date` FROM `'. $config['ft'].'` WHERE `f`='.$forum['id'].' AND `id`'.Eleanor::$Db->In(array_keys($read)).' LIMIT '.$config['readslimit']);
						while($a=$R->fetch_assoc())
						{
							if($a['lp_date']<$minread)
								$minread=$a['lp_date'];

							if($read[ $a['id'] ]<$a['lp_date'])
								$mark=false;
						}

						if(!$mark and $minread>$fread)
							$Forum->Forums->MarkRead($forum['id'],$minread);
					}

					if($mark)
						$Forum->Forums->MarkRead($forum['id']);
				}
			}

			if(Eleanor::$caching)
			{
				Eleanor::$last_mod=$lastmod;
				$etag=Eleanor::$etag;
				$uid= $Forum->user ? $Forum->user['id'] : 0;
				Eleanor::$etag=md5($forum['id'].$where.$uid.$Eleanor->module['etag']);
				if(Eleanor::$modified and Eleanor::$last_mod and Eleanor::$last_mod<=Eleanor::$modified and $etag and $etag==Eleanor::$etag)
					return Start();
				Eleanor::$modified=false;
			}

			if($wmp and $Forum->user || $gp)
			{
				$R=Eleanor::$Db->Query('SELECT `t` FROM `'. $config['fp'].'` WHERE '.($Forum->user ? '`author_id`='. $Forum->user['id'] : '`id`'.Eleanor::$Db->In($gp)).' AND `t`'.Eleanor::$Db->In($wmp).' GROUP BY `t`');
				while($a=$R->fetch_row())
					$forum['_topics'][ $a[0] ]['_wmp']=true;
			}

			if($Forum->user)
				Eleanor::$Db->Update($config['fs'],array('sent'=>0,'!lastview'=>'FROM_UNIXTIME('.$allread.')'),'`uid`='. $Forum->user['id'].' AND `f`='.$forum['id'].' AND `language`=\''.$forum['language'].'\' AND `lastview`<FROM_UNIXTIME('.$allread.') LIMIT 1');

			if($forum['_authors'])
			{
				$R=Eleanor::$UsersDb->Query('SELECT `id`,`name`,`full_name` FROM `'.USERS_TABLE.'` WHERE `id`'.Eleanor::$UsersDb->In($forum['_authors']));
				$forum['_authors']=array();
				while($a=$R->fetch_assoc())
				{
					$a['_a']=Eleanor::$Login->UserLink($a['name'],$a['id']);
					$forum['_authors'][ $a['id'] ]=array_slice($a,1);
				}
			}
		}
		elseif($forum['_page']<1)
			$forum['_page']=1;

		if($forum['meta_descr'])
			$forum['meta_descr']=Eleanor::ExecBBLogic($forum['meta_descr'],array('page'=>$forum['_pages']==$forum['_page'] ? false : $forum['_page']));

		$links['rss_topics']=Eleanor::$services['rss']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'f'=>$forum['id'],'l'=>$forum['language'] ? $forum['language'] : false,'show'=>'topics'));
		$links['rss_posts']=Eleanor::$services['rss']['file'].'?'.Url::Query(array('module'=>$Eleanor->module['name'],'f'=>$forum['id'],'l'=>$forum['language'] ? $forum['language'] : false));
		$links['first_page']= $Forum->Links->Forum($forum['id'],array('fi'=>$forum['_filter']));
		$links['form_items']= $Forum->Links->Forum($forum['id'],array(
			'fi'=>$forum['_filter'],
			'page'=>$forum['_page']<$forum['_pages'] ? $forum['_page'] : false,
		));
		$links['pages']=function($n)use($forum,$Forum){ return $Forum->Links->Forum($forum['id'],array('fi'=>$forum['_filter'])+array('page'=>$n)); };
		$links['new-topic']=!$forum['_trash'] && in_array(1,$rights['new']) && (!$Forum->user or !$Forum->user['restrict_post']) ? $Forum->Links->Action('new-topic',$forum['id']) : false;
		$links['wait-topics']=$forum['_statuses'][-1]>0 ? $Forum->Links->Forum($forum['id'],array('fi'=>array('status'=>-1))) : false;

		if(!$forum['_filter'])
			$Eleanor->origurl=$links['form_items'];
	}
	else
		$Eleanor->origurl=$Forum->Links->Forum($forum['id']);

	$Eleanor->module['description']=$forum['meta_descr'] ? $forum['meta_descr'] : Strings::CutStr(strip_tags(str_replace("\n",' ',$forum['description'])),250);

	#Реверс страниц
	if(isset($forum['_page']))
		$forum['_page']=-$forum['_page'];

	SetData();
	Eleanor::$Template->queue[]=$config['maintpl'];
	$c=Eleanor::$Template->ShowForum($forum,$rights,$forums, $Forum->GetOnline(($forum['parents'] ? '-f'.str_replace(',','-f',$forum['parents']) : '').'-f'.$forum['id']),$errors,$info,$links);
	Start();
	echo$c;
}

/**
 * Получение категорий, форумов и подфорумов
 * @param int $parent ID родительского форума
 */
function Forums($parent=0)
{global$Eleanor;
	$Forum = $Eleanor->Forum;
	$Forums = $Forum->Forums;
	if($parent>0)
	{
		if(!isset($Forums->dump[$parent]))
			return false;
		$parents= $Forums->dump[$parent]['parents'];
		$level=substr_count($parents,',');
		$parents.=$parent.',';
	}
	else
	{
		$parents='';
		$level=0;
	}
	$parlen=strlen($parents);

	#ID форумов
	$fids=
	#"Читанность" форумов
	$read=
	#ID форумов, ВСЕ темы которх нам не доступны
	$rtopics=
	#ID форумов, чужие темы в которых нам недоступны
	$ratopics=
	#Информация о последнем посте для форумов, в которых все темы нам недоступны
	$lastpost=
	#Данные предваритльного отбора форумов
	$preforums=
	#Данные о модераторах
	$moders=array();

	$usefids=false;#использовать далее $fids, или брать все форумы из БД;
	foreach($Forums->dump as $k=>$v)
	{
		if($parents and strpos($v['parents'],$parents)!==0)
		{
			$usefids=true;
			continue;
		}

		#Нас не интересуют подфорумы подфорумов. Иногда, правда, бывает, что форум располгается внутри категории, в этом случае глубина "интереса" увеличивается на 1
		$outof=substr_count($v['parents'],',')-$level;
		if($outof>1)
		{
			$sparent=(int)substr($v['parents'],$parlen);
			if($Forums->dump[$sparent]['is_category'])
				$outof--;
		}
		if($outof>1)
		{
			$usefids=true;
			continue;
		}

		#a (access) - доступ к форуму, t (topics) - просмотр списка тем, at - (alltopics) - просмотр всем тем (включая чужие)
		$a=$t=$at=array();
		foreach($Forum->ug as $g)
		{
			$gp= $Forum->GroupPerms($k,$g);
			$a[]=$gp['access'];
			$t[]=$gp['topics'];
			$at[]=$gp['atopics'];
		}
		if(!in_array(1,$a))
		{
			$usefids=true;
			continue;
		}
		$fids[]=$k;
		if(!in_array(1,$t))
			$rtopics[]=$k;
		if(!in_array(1,$at))
			$ratopics[]=$k;
	}

	#Если нет доступа к форумам или просто нет форумов
	if(!$fids)
		return false;

	$usefids=$usefids ? Eleanor::$Db->In($fids) : false;

	if($Forum->user)
		$allread= $Forum->user['allread'];
	else
	{
		$allread=(int)Eleanor::GetCookie($Forum->config['n'].'-ar');
		$gt= $Forum->GuestSign('t');
		$ingt=$gt ? Eleanor::$Db->In($gt) : false;
	}

	#Извлечение последнего поста из базы данных с учетом всех ограничений
	if($Forum->user)
	{
		$R=Eleanor::$Db->Query('SELECT `f`,UNIX_TIMESTAMP(`allread`) `allread` FROM `'. $Forum->config['re'].'` WHERE `uid`='. $Forum->user['id'].($usefids ? ' AND `f`'.$usefids : ''));
		while($a=$R->fetch_row())
			$read[ $a[0] ]=$a[1];

		if($ratopics)
		{
			$R=Eleanor::$Db->Query('SELECT `f`,`language`,`lp_date`,`lp_id`,`lp_title`,`lp_author`,`lp_author_id` FROM `'. $Forum->config['lp'].'` WHERE `uid`='. $Forum->user['id'].' AND `f`'.Eleanor::$Db->In($ratopics).' AND `language`IN(\'\',\''.$Forum->language.'\')');
			while($a=$R->fetch_assoc())
			{
				#Если тема устарела...
				if($a['lp_title']=='')
				{
					$R=Eleanor::$Db->Query('SELECT `f`,`language`,`lp_date`,`id` `lp_id`,`lp_title`,`lp_author`,`lp_author_id` FROM `'. $Forum->config['ft'].'` WHERE `f`='.$a['f'].' AND `language`=\''.$a['language'].'\' AND `status` IN (-1,1) AND `author_id`='. $Forum->user['id'].' ORDER BY `sortdate` DESC');
					if($temp=$R->fetch_assoc())
					{
						Eleanor::$Db->Update($Forum->config['lp'],$temp,'`uid`='. $Forum->user['id'].' AND `f`='.$a['f'].' AND `language`=\''.$a['language'].'\' LIMIT 1');
						$a=$temp;
					}
					else
					{
						Eleanor::$Db->Delete($Forum->config['lp'],'`uid`='. $Forum->user['id'].' AND `f`='.$a['f'].' AND `language`=\''.$a['language'].'\'');
						continue;
					}
				}
				$lastpost[ $a['f'] ]=array_slice($a,2);
			}

			#В списке форумов показываем число своих тем и своих постов
			if($lastpost)
			{
				$lin=Eleanor::$Db->In(array_keys($lastpost));
				$R=Eleanor::$Db->Query('(SELECT `f`, `status`, COUNT(`author_id`) `topics`, SUM(`posts`) `posts` FROM `'. $Forum->config['ft'].'` WHERE `f`'.$lin.' AND `author_id`='. $Forum->user['id'].' AND `status`=1 GROUP BY `f`)UNION ALL(SELECT `f`, `status`, COUNT(`author_id`) `topics`, SUM(`posts`) `posts` FROM `'. $Forum->config['ft'].'` WHERE `f`'.$lin.' AND `author_id`='. $Forum->user['id'].' AND `status`=-1 GROUP BY `f`)');
				while($a=$R->fetch_assoc())
					if($a['status']==1)
						$lastpost[ $a['f'] ]+=array(
							'topics'=>$a['topics'],
							'posts'=>$a['posts'],
						);
					else
						$lastpost[ $a['f'] ]+=array(
							'queued_topics'=>$a['topics'],
							'queued_posts'=>$a['posts'],
						);
			}
		}
	}
	else
	{
		$fr=Eleanor::GetCookie($Forum->config['n'].'-fr');
		$fr=$fr ? explode(',',$fr) : array();
		foreach($fr as $v)
			if(strpos($v,'-')!==false)
			{
				$v=explode('-',$v,2);
				$read[ $v[0] ]=(int)$v[1];
			}

		if($ratopics and $ingt)
		{
			$R=Eleanor::$Db->Query('SELECT `f`,`lp_date`,`id` `lp_id`,`lp_title`,`lp_author`,`lp_author_id` FROM `'. $Forum->config['ft'].'` WHERE `id`'.$ingt.' AND `f`'.Eleanor::$Db->In($ratopics).' AND `language`IN(\'\',\''.$Forum->language.'\') AND `status` IN (-1,1)');
			while($a=$R->fetch_assoc())
				$lastpost[ $a['f'] ]=array_slice($a,1);
		}
	}

	#Компиляция последнего поста в каждом форуме
	$lastview=0;#TimeStamp последнего просмотра форума. Далее это понадобится для проверки, возможно весь форум уже прочитан.
	$R=Eleanor::$Db->Query('SELECT `id`,`lp_date`,`lp_id`,`lp_title`,`lp_author`,`lp_author_id`,`topics`,`posts`,`queued_topics`,`queued_posts`,`moderators`,`image`,`title`,`description` FROM `'. $Forum->config['f'].'` INNER JOIN `'. $Forum->config['fl'].'` USING(`id`) WHERE `language` IN (\'\',\''.$Forum->language.'\')'.($usefids ? ' AND `id`'.$usefids : ''));
	while($a=$R->fetch_assoc())
	{
		$iscat= $Forums->dump[ $a['id'] ]['is_category'];

		#Модераторы в категорию не прописываются: в категории нет тем
		if($iscat)
			$offset=1;
		else
		{
			$offset=0;
			$a['moderators']=$a['moderators'] ? explode(',,',trim($a['moderators'],',')) : array();
			foreach($a['moderators'] as $v)
				$moders[$v][]=$a['id'];
		}

		$lp=array(
			'_a'=> $Forum->Links->Forum($a['id']),#Ссылка на форум
			'_read'=>true,#Форум прочитан
			'_topics'=>true,
		);
		if(in_array($a['id'],$rtopics) or $iscat)
		{
			$lp['_topics']=false;
			$offset+=10;
		}
		elseif(in_array($a['id'],$ratopics))
		{
			if(isset($lastpost[ $a['id'] ]))
			{
				$lp+=$lastpost[ $a['id'] ];
				$lpt=strtotime($lp['lp_date']);

				$lp['_read']=$lpt<=( isset($read[ $a['id'] ]) ? $read[ $a['id'] ] : $allread);
				$lp['_alpa']=$lp['lp_author_id'] ? Eleanor::$Login->UserLink($lp['lp_author'],$lp['lp_author_id']) : false;#Ссылка на последнего автора

				if($lpt>$lastview)
					$lastview=$lpt;
			}
			$offset=10;
		}
		else
		{
			$lpt=strtotime($a['lp_date']);
			if($a['lp_id']>0)
			{
				$lp['_read']=$lpt==0 || $lpt<=(isset($read[ $a['id'] ]) ? $read[ $a['id'] ] : $allread);
				$lp['_alpa']=$a['lp_author_id'] ? Eleanor::$Login->UserLink($a['lp_author'],$a['lp_author_id']) : false;
			}
			$offset=1;

			if($lpt>$lastview)
				$lastview=$lpt;
		}

		#Если последней темы нет, зачем тогда ссылки?
		if(isset($lp['_alpa']))
		{
			$lp+=array(
				'_anp'=> $Forum->Links->Action('go-new-post',$a['id']),#Ссылка на новые посты с момента последнего просмотра темы
				'_alp'=> $Forum->Links->Action('go-last-post',$a['id']),#Ссылка на последний пост темы
			);

			if($a['queued_topics']>0)
				$lp['_amwt']= $Forum->Links->Forum($a['id'],array('fi'=>array('status'=>-1)));#Ссылка на модерирование тем
			if($a['queued_posts'])
				$lp['_amwp']= $Forum->Links->Action('moderate',array('fi'=>array('f'=>$a['id']),'show'=>'posts'));#Ссылка на модерирование постов
		}

		$preforums[ $a['id'] ]=$lp+array_slice($a,$offset);
	}

	if(Eleanor::$caching)
	{
		#В etag пропишем последнее изменение: возможно мы отображаем подфорумы конкретного форума и в подфоруме опубликовался ответ.
		$Eleanor->module['etag'].=$lastview;
		if($parent==0)
		{
			Eleanor::$last_mod=$lastview;
			$etag=Eleanor::$etag;
			$uid= $Forum->user ? $Forum->user['id'] : 0;
			Eleanor::$etag=md5($Forum->config['n'].$uid.$Eleanor->module['etag']);
			if(Eleanor::$modified and Eleanor::$last_mod and Eleanor::$last_mod<=Eleanor::$modified and $etag and $etag==Eleanor::$etag)
				return Start();
			Eleanor::$modified=false;
		}
	}

	#Извлечение модераторов
	if($moders)
	{
		$users=$groups=array();
		$R=Eleanor::$Db->Query('SELECT `id`,`users`,`groups` FROM `'. $Forum->config['fm'].'` WHERE `id`'.Eleanor::$Db->In(array_keys($moders)));
		while($a=$R->fetch_assoc())
		{
			$a['users']=$a['users'] ? explode(',,',trim($a['users'],',')) : array();
			$a['groups']=$a['groups'] ? explode(',,',trim($a['groups'],',')) : array();
			foreach($a['users'] as $v)
				$users[$v][]=$a['id'];
			foreach($a['groups'] as $v)
				$groups[$v][]=$a['id'];
		}
		if($users)
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`groups` `_group`,`full_name`,`name` FROM `'.P.'users_site` WHERE `id`'.Eleanor::$Db->In(array_keys($users)));
			while($a=$R->fetch_assoc())
			{
				$a['_group']=(int)ltrim($a['_group'],',');
				$a['_me']=$a['id']== $Forum->user['id'];
				$a['_a']=Eleanor::$Login->UserLink($a['name'],$a['id']);

				foreach($users[ $a['id'] ] as $v)
					foreach($moders[ $v ] as $mv)
						$preforums[ $mv ]['moderators'][]=$a;
			}
		}
		if($groups)
		{
			#Ссылки на модуль групп
			$mg=array_keys($GLOBALS['Eleanor']->modules['sections'],'groups');
			$mg=reset($mg);

			$R=Eleanor::$Db->Query('SELECT `id`,`title_l` `title`,`html_pref`,`html_end` FROM `'.P.'groups` WHERE `id`'.Eleanor::$Db->In(array_keys($groups)));
			while($a=$R->fetch_assoc())
			{
				$a['title']=$a['title'] ? Eleanor::FilterLangValues((array)unserialize($a['title'])) : '';
				$a['_me']= $Forum->user && in_array($a['id'], $Forum->ug);
				$a['_a']=$Eleanor->Url->Construct(array('module'=>$mg),false).'#group-'.$a['id'];

				foreach($groups[ $a['id'] ] as $v)
					foreach($moders[ $v ] as $mv)
						$preforums[ $mv ]['moderators'][]=$a;
			}
		}
	}

	$forums=array(
		'categories'=>array(),
		'forums'=>array(),
	);

	$oldv=-1;
	foreach($fids as $v)
	{
		$p= $Forums->dump[$v]['parent']==$parent;
		if($p)
		{
			if(isset($forums['categories'][$oldv]) and empty($forums['categories'][$oldv]['_forums']))
				unset($forums['categories'][$oldv]);

			if($Forums->dump[$v]['is_category'])
			{
				$forums['categories'][$v]=$preforums[$v]+array('_forums'=>array());
				$oldv=$v;
				continue;
			}
		}

		if($p)
		{
			unset($f);
			$f=$preforums[$v];
			$forums['forums'][$v]=&$f;
		}
		elseif($Forums->dump[$v]['parent']==$oldv)
		{
			unset($f);
			$f=$preforums[$v];
			$forums['categories'][$oldv]['_forums'][$v]=&$f;
		}
		else
			$f['_subforums'][$v]=$preforums[$v];
	}

	if(empty($forums['categories'][$oldv]['_forums']))
		unset($forums['categories'][$oldv]);

	return$forums;
}