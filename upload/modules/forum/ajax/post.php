<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;

$config=$Forum->config;

switch($event)
{
	case'edit':#Правка поста
	case'save':#Сохранение поста
		$id=isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$gp=$Forum->GuestSign('p');

		if($Forum->user ? $Forum->user['restrict_post'] : !in_array($id,$gp))
			return Error();

		#Получение данных поста
		$R=Eleanor::$Db->Query('SELECT `id`,`t`,`status`,`author`,`author_id`,`created`,`edit_reason`,`text` FROM `'.$config['fp'].'` WHERE `id`='.$id.' LIMIT 1');
		if(!$edpost=$R->fetch_assoc())
			return Error();

		#Получение данных темы
		$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`f`,`prefix`,`status`,`language`,`author_id`,`state`,`moved_to`,`title` FROM `'.$config['ft'].'` WHERE `id`='.$edpost['t'].' LIMIT 1');
		if(!$topic=$R->fetch_assoc() or !list($forum,$rights)=$Forum->Post->GetForum($topic['f']))
			return Error();

		#Проверка возможности доступа к посту
		$prights=$Forum->Post->Rights($edpost,$rights,$forum['_moderator']);
		if(!$prights['edit'])
			return Error();

		#Проверка возможности доступа к теме
		$trights=$Forum->Topic->Rights($topic,$rights,$forum['_moderator']);
		if(!$trights['read'])
			return Error();

		if($event=='edit')
		{
			if(!isset(OwnBB::$replace['quote']) and strpos($edpost['text'],'[quote')!==false)
			{
				if(!class_exists('ForumBBQoute',false))
					include$Eleanor->module['path'].'Misc/bb-quote.php';
				OwnBB::$replace['quote']='ForumBBQoute';
			}

			$values=array(
				#Текст поста
				'text'=>$edpost['text'],
				#Причина редактирования
				'edit_reason'=>$edpost['edit_reason'],

				'extra'=>array(
					'sign'=>md5(md5($edpost['text']).$edpost['edit_reason']),
				),
			);

			$links=array(
				'action'=>$Forum->Links->Action('edit',$edpost['id']),
			);

			$Eleanor->Editor->preview=array('module'=>$Eleanor->module['name'],'event'=>'preview');
			Eleanor::$Template->queue[]=$config['posttpl'];
			Result(Eleanor::$Template->AjaxEditPost($values,$links));
		}
		else
		{
			#ToDO! Save post
			Error('В разработке...');
			/*$values=GetPost('s');
			if(!$values)
			{
				Error();
				break;
			}
	
			if(!isset(OwnBB::$replace['quote']))
			{
				if(!class_exists('ForumBBQoute',false))
					include$Eleanor->module['path'].'Misc/bb-quote.php';
				OwnBB::$replace['quote']='ForumBBQoute';
			}
	
			$values['edit_reason']=isset($_POST['edit_reason']) ? (string)Eleanor::$POST['edit_reason'] : '';
			$values['text']=trim($Eleanor->Editor->GetHTML('text'));
			if(mb_strlen($values['text'])<5)
			{
				Error(sprintf('Минимальная длина поста составляет %s символов.',5));
				break;
			}
			$hidden=isset($_POST['h']) ? (array)$_POST['h'] : array();
			$files=(Eleanor::$Permissions->MaxUpload()!==false and isset($hidden['s']));
			if($files)
				$values['_files']=$Eleanor->Uploader->WorkingPath('',$hidden['s']);
	
			if(isset($hidden['postsign']) and $hidden['postsign']!=md5(md5($values['text']).$values['edit_reason']))
			{
				$me=$Forum->user ? Eleanor::$Login->GetUserValue(array('name','id')) : array('name'=>$values['author'],'id'=>0);
				$values+=array(
					'who_edit'=>$me['name'],
					'who_edit_id'=>$me['id'],
					'edit_date'=>date('Y-m-d H:i:s'),
				);
			}
	
			Eleanor::$Template->queue[]='ForumPost';
			try
			{
				$values=$Forum->Post->SavePost($values);
				Result(array('text'=>OwnBB::Parse($values['text']),'edited'=>isset($values['who_edit']) ? Eleanor::$Template->Edited($values) : false));
			}
			catch(EE_SQL$E)
			{
				$E->LogIt($E->addon['logfile'],$E->getMessage());
				Error($E->getMessage());
			}
			catch(EE$E)
			{
				Error($E->getMessage());
			}*/

		}

	break;
	case'delete':#Удаление поста
		/*$values=GetPost('d');
		if(!$values)
		{
			Error();
			break;
		}
		$Forum->Moderate->DeletePost($values['id']);
		Result('ok');*/
	break;
	case'new-post':#Новый пост
		$t=isset($_POST['t']) ? (int)$_POST['t'] : 0;
		#Получение данных темы
		$R=Eleanor::$Db->Query('SELECT `id`,`f`,`status`,`language`,`author_id`,`state` FROM `'.$config['ft'].'` WHERE `id`='.(int)$t.' LIMIT 1');
		if(!$topic=$R->fetch_assoc() or !list($forum,$rights)=$Forum->Post->GetForum($topic['f']))
			return Error();

		#Проверка возможности публиковать в тему
		$trights=$Forum->Topic->Rights($topic,$rights,$forum['_moderator']);
		if(!$trights['post'])
			return Error();

		$lang=$Forum->Language['user-post'];
		$errors=array();

		$pre=$Forum->Post->Possibility();
		if($pre)
			foreach($pre as $k=>$l)
				switch(is_int($k) ? $l : $k)
				{
					case'FLOOD_WAIT':
						$errors['FLOOD_WAIT']=$lang['FLOOD_WAIT'](Eleanor::$Permissions->FloodLimit(),$l);
				}

		$extra=isset($_POST['extra']) ? (array)$_POST['extra'] : array();
		$text=isset($_POST['text']) ? trim((string)$_POST['text']) : '';
		$status=$Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['chstatus']);

		if(!isset(OwnBB::$replace['quote']) and strpos($text,'[quote')!==false)
		{
			if(!class_exists('ForumBBQoute',false))
				include$Eleanor->module['path'].'Misc/bb-quote.php';
			OwnBB::$replace['quote']='ForumBBQoute';
			OwnBB::$opts['you_quoted']=true;
		}

		if(!$Forum->user)
		{
			$name=isset($_POST['name']) ? (string)$_POST['name'] : '';

			if($name)
				Eleanor::SetCookie($config['n'].'-name',$name);
			else
				$errors[]='ENTER_NAME';
		}

		if(5>$yi=mb_strlen($text))
			$errors['TOO_SHORT']=$lang['TOO_SHORT'](5,$yi);

		$cach=$Eleanor->Captcha->Check(isset($_POST['check']) ? (string)$_POST['check'] : '');
		$Eleanor->Captcha->Destroy();
		if(!$cach)
			$errors[]='WRONG_CAPTCHA';

		if($errors)
			return Error(array('error'=>$errors));

		$values=array(
			'files'=>Eleanor::$Permissions->MaxUpload()!==false && isset($extra['session']) ? $Eleanor->Uploader->WorkingPath('',$extra['session']) : false,
			'text'=>$Eleanor->Editor_result->GetHTML($text,true),
			't'=>$topic['id'],
			'f'=>$forum['id'],
			'language'=>$forum['language'],
			'merge'=>true,
		);

		if(!$Forum->user)
		{
			$values['author']=$name;
			$values['author_id']=null;
		}

		if($status and isset($_POST['status']) and in_array((int)$_POST['status'],range(-1,1)))
			$values['status']=(int)$_POST['status'];
		else
			$values['status']=Eleanor::$Permissions->Moderate() || $Forum->user && $Forum->user['moderate'] || $Forum->ugr['moderate'] ? -1 : 1;

		try
		{
			$p=$Forum->Post->Post($values);
		}
		catch(EE_SQL$E)
		{
			$E->Log();
			return Error($E->getMessage());
		}
		catch(EE$E)
		{
			return Error($E->getMessage());
		}

		#Подписка
		if($Forum->user)
			$Forum->Subscriptions->SubscribeTopic($topic['id'],$Forum->user['id'],
				isset($_POST['subscription']) ? (string)$_POST['subscription'] : false,$topic['status']);
		elseif(!$p['merged'])
			$Forum->GuestSign('p',$p['id']);

		#Обновляем тему
		$tupd=array();
		if(($Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['pin'])) and isset($_POST['pinned']) and $ts=strtotime((string)$_POST['pinned']) and time()<$ts and strtotime('+1000day')>=$ts)
			$tupd['pinned']=date('Y-m-d H:i:s',$ts);

		if($Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['opcl']) or in_array(1,$rights['close']))
			$tupd['state']=isset($_POST['closed']) ? 'closed' : 'open';

		if($tupd)
			$Forum->Post->Topic(array('id'=>$topic['id'])+$tupd);

		$result=$p['merged'] ? array('text'=>$p['text'],'merged'=>$p['id']) : array('runtask'=>true);
	case'lnp':
		if(!isset($topic))
		{
			$t=isset($_POST['t']) ? (int)$_POST['t'] : 0;
			#Получение данных темы
			$R=Eleanor::$Db->Query('SELECT `id`,`f`,`status`,`language`,`author_id`,`state` FROM `'.$config['ft'].'` WHERE `id`='.(int)$t.' LIMIT 1');
			if(!$topic=$R->fetch_assoc())
				return Error();
			
			$forum=$Forum->Forums->GetForum($topic['f']);
		}

		#модераторы форума + наши права, как модератора
		if($forum['moderators'])
			list($forum['moderators'],$forum['_moderator'])= $Forum->Moderator->ByIds($forum['moderators'],array('movet','move','deletet','delete','editt','edit','chstatust','chstatus','pin','mmove','mdelete','user_warn','viewip','opcl','merge','editq','mchstatus'),$config['n'].'_moders_t'.$forum['id']);
		else
			$forum['moderators']=$forum['_moderator']=array();

		$active=$topic['status']==1;
		$gp=$Forum->GuestSign('p');
		$gt=$Forum->GuestSign('t');

		if($Forum->user)
			$my=$Forum->user['id']==$topic['author_id'] || in_array($topic['id'],$gt);
		else
		{
			$my=in_array($topic['id'],$gt);
			$ingp=$gp ? Eleanor::$Db->In($gp) : false;
		}

		if($topic['status']!=1 and ($topic['status']==0 or !$my) and !$Forum->ugr['supermod'] and (!$forum['_moderator'] or !in_array(1,$forum['_moderator']['chstatust']) and !in_array(1,$forum['_moderator']['mchstatust'])))
			return Error();

		$rights=$Forum->ForumRights($forum['id']);
		$rights+=array(
			#Возможность "модерировать" свою тему: править / удалять / перемещать сообщения в своей теме
			'_mod'=>in_array(1,$rights['mod']) and $my,
			#Возможность просматривать свои посты с разными статусами
			'_status'=>$Forum->user or $gp,
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
			#Эту тему создал я?
			'_my'=>$my,
			#По-факту тема для нас открыта?
			'_open'=>$topic['state']=='open' || $Forum->ugr['supermod'] || in_array(1,$rights['canclose']),
		);

		$forum+=array(
			#TimeStamp прочтения форума
			'_read'=>0,
			#Является ли форум мусорником
			'_trash'=>$Forum->vars['trash']==$forum['id'],
		);

		$authors=$attaches=$posts=$where=array();
		$filter=isset($_POST['filter']) ? (array)$_POST['filter'] : array();
		if($filter)
		{
			$status=isset($filter['status']) ? $filter['status'] : false;
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
					$filter['my']=1;
				elseif($rights['_status'])
				{
					if(!$active)
					{
						if(isset($status[-1]))
							$status[-1]=-3;
						if(isset($status[1]))
							$status[1]=-2;
					}
					$where['status']=' AND `status`'.Eleanor::$Db->In($status);
				}
			}

			#Мои посты
			if(isset($filter['my']) and $Forum->user || $ingp)
			{
				if($status and $status!=array(1=>1))
				{
					if(!$rights['_toggle'])
						unset($status[0]);
					if(!$active)
					{
						if(isset($status[-1]))
							$status[-1]=-3;
						if(isset($status[1]))
							$status[1]=-2;
					}
					$where['status']=' AND `status`'.Eleanor::$Db->In($status);
				}
				$where['author']= $Forum->user ? ' AND `author_id`='. $Forum->user['id'] : ' AND `id`'.$ingp;
			}
		}

		#Создание запроса
		$ld=isset($_POST['ld']) ? (string)$_POST['ld'] : '';
		if($ld and strtotime($ld))
		{
			$where=' FROM `'.$config['fp'].'` WHERE `sortdate`>'.Eleanor::$Db->Escape($ld).' AND `t`='.$topic['id'].join($where)
				.(isset($where['status']) ? '' : ' AND `status`='.($active ? 1 : -2));
			$R=Eleanor::$Db->Query('SELECT COUNT(`t`)'.$where);
			list($cnt)=$R->fetch_row();
		}
		else
			$cnt=0;

		if($cnt==0)
		{
			$result['posts']=false;
			return Result($result);
		}

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
					$forum['_read']=$a['allread'];
				$topic['_read']=max($forum['_read'],isset($a['topics'][ $topic['id'] ]) ? $a['topics'][ $topic['id'] ] : 0);
			}
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

		$t=time();

		if($forum['_moderator'])
		{
			$medit=in_array(1,$forum['_moderator']['edit']);
			$mdelete=in_array(1,$forum['_moderator']['delete']);
		}
		else
			$medit=$mdelete=false;

		$edit=in_array(1,$rights['edit']);
		$delete=in_array(1,$rights['delete']);
		$deletet=in_array(1,$rights['deletet']);

		$tread=$topic['_read'];
		$hasquote=false;

		$ln=isset($_POST['ln']) ? (int)$_POST['ln'] : 1;
		$answer=$topic['_open'] && !$forum['_trash'] && (!$Forum->user or !$Forum->user['restrict_post']) && ($my && in_array(1,$rights['post']) || !$my && in_array(1,$rights['apost']));

		$R=Eleanor::$Db->Query('SELECT `id`,`status`,`author`,`author_id`,`ip`,`created`,`sortdate`,`updated`,`edited`,`edited_by`,`edited_by_id`,`edit_reason`,`approved`,`approved_by`,`approved_by_id`,`text`'.$where.' ORDER BY `sortdate` ASC LIMIT '.$Forum->vars['ppp']);
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

			$a['_approved']=$a['_rejected']=array();#Одобрено и отвергнуто
			$a['_my']=$Forum->user && $a['author_id']==$Forum->user['id'] || in_array($a['id'],$gp);
			if($a['_my'])
				$a['_r+']=$a['_r-']=false;
			else
			{
				$a['_r+']=$rplus;
				$a['_r-']=$rminus;
			}
			$a['_atp']=$Forum->Links->Action('find-post',$a['id']);#Ссылка на пост в теме
			$a['_ap']=$Forum->Links->Action('post',$a['id']);#Ссылка на пост
			$a['_checked']=false;
			$a['_n']=++$ln;
			$a['_attached']=array();#ID прикрепленных аттачей
			#Аттачи, используемые в тексте
			$a['_attaches']=$Forum->Attach->GetFromText($a['text']);
			if($a['_attaches'])
				$attaches=array_merge($attaches,$a['_attaches']);

			$a['_answer']=$answer ? $Forum->Links->Action('answer',$a['id']) : false;
			$a['_edit']=$Forum->ugr['supermod'] || $rights['_mod'] || $medit;
			$a['_delete']=$Forum->ugr['supermod'] || $mdelete;

			if($rights['_mod'] and !$a['_delete'] and ($deletet or $a['_n']>1))
				$a['_delete']=true;

			if((!$a['_edit'] or !$a['_delete']) and $a['_my'])
			{
				$mined=min($rights['editlimit']);
				if($mined==0 or $t-strtotime($a['created'])<=$mined)
				{
					$a['_edit']|=$edit;
					$a['_delete']|=$a['_n']==1 && !$deletet ? false : $delete;
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

			if(!$hasquote and strpos($a['text'],'[quote')!==false)
				$hasquote=true;
			$ld=$a['sortdate'];
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

		$inp=$posts ? Eleanor::$Db->In(array_keys($posts)) : false;
		if($inp)
		{
			#Отключим возможность менять репутацию постам, за которые мы уже меняли репутацию
			if($Forum->user)
			{
				$R=Eleanor::$Db->Query('SELECT `p` FROM `'.$config['fr'].'` WHERE `from`='.$Forum->user['id'].' AND `p`'.$inp);
				while($a=$R->fetch_assoc())
					$posts[ $a['p'] ]['_r+']=$posts[ $a['p'] ]['_r-']=false;
			}

			#Вложения (аттачи)
			if(in_array(1,$rights['attach']))
			{
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
					#Чтобы тема оформления могла создать превьюшку в случае надобности
					$a['_path']=$config['attachroot'].'p'.$a['p'].'/'.$a['file'];

					$attaches[ $a['id'] ]=array_slice($a,1);
				}
			}
			else
				$attaches=array();

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

			#Репутация
			$R=Eleanor::$Db->Query('SELECT `id`,`p`,`from`,`from_name`,`value` FROM `'.$config['fr'].'` WHERE `p`'.$inp);
			while($a=$R->fetch_assoc())
			{
				if($a['from'])
					$authors[]=$a['from'];
				$posts[ $a['p'] ][$a['value']>0 ? '_approved' : '_rejected'][ $a['id'] ]=array_slice($a,2);
			}
		}

		if($authors)
		{
			$do=false;
			include_once$Eleanor->module['path'].'user/topic.php';
			$ag=GetAuthors($authors,$forum);
			foreach($ag[0] as $k=>$v)
				$result['userposts'][$k]=$v['posts'];
		}

		Eleanor::$Template->queue[]=$config['topictpl'];
		$result['posts']=(string)Eleanor::$Template->AjaxLoadNewPosts($forum,$rights,$topic,$posts,$attaches,$authors ? $ag : array());

		Result($result+array('ld'=>$ld,'ln'=>$ln,'first'=>key($posts)));
	break;
	default:
		Error();
}