<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
#ToDo! Разрешить редактирование опросов в сообщениях (право)
function NewTopic($forum,$rights,$errors=array())
{global$Eleanor,$title;
	$Forum=$Eleanor->Forum;
	#Языки форума
	$langs=$Forum->Forums->GetLanguages($forum['id']);

	if($errors)
	{
		$bypost=true;
		if($errors===true)
			$errors=array();

		if($langs)
		{
			if(isset($_POST['langs']))
			{
				$values['langs']=array_intersect($langs,(array)$_POST['langs']);
				if(!$values['langs'])
					$values['langs']=array($Forum->language);
			}
			else
				$values['langs']=array($Forum->language);

			foreach($langs as $l)
			{
				$values['prefix'][$l]=isset($_POST['prefix'][$l]) ? (int)$_POST['prefix'][$l] : 0;
				$values['uri'][$l]=isset($_POST['uri'][$l]) ? (string)$_POST['uri'][$l] : '';
				$values['title'][$l]=isset($_POST['title'][$l]) ? (string)$_POST['title'][$l] : '';
				$values['description'][$l]=isset($_POST['description'][$l]) ? (string)$_POST['description'][$l] : '';
				$values['text'][$l]=isset($_POST['text'][$l]) ? (string)$_POST['text'][$l] : '';
			}
		}
		else
		{
			$values['prefix']=isset($_POST['prefix']) ? (string)$_POST['prefix'] : '';
			$values['uri']=isset($_POST['uri']) ? (string)$_POST['uri'] : '';
			$values['title']=isset($_POST['title']) ? (string)$_POST['title'] : '';
			$values['description']=isset($_POST['description']) ? (string)$_POST['description'] : '';
			$values['text']=isset($_POST['text']) ? (string)$_POST['text'] : '';
		}

		$values['subscription']=isset($_POST['subscription']) ? (string)$_POST['subscription'] : '';
		$values['closed']=isset($_POST['closed']);
		$values['pinned']=isset($_POST['pinned']) ? (string)$_POST['pinned'] : '';
		$values['status']=isset($_POST['status']) ? (int)$_POST['status'] : 1;
		$values['_name']=$Forum->user && isset($_POST['name']) ? (string)$_POST['name'] : '';
		$values['extra']=isset($_POST['extra']) ? (array)$_POST['extra'] : array();
	}
	else
	{
		$bypost=false;

		#Дэфотовые значения для мультиязычных полей
		$default=$langs ? array_combine($langs,array_fill(0,count($langs),'')) : '';

		$values=array(
			#Префикс
			'prefix'=>$default,
			#URI темы
			'uri'=>$default,
			#Название темы
			'title'=>$default,
			#Описание темы
			'description'=>$default,
			#Содержимое темы
			'text'=>$default,

			#Статус темы (для модератора)
			'status'=>1,

			#Подписка
			'subscription'=>false,
			#Закрыть тему
			'closed'=>false,
			#Зафиксировать тему до... (для модератора)
			'pinned'=>'',
			#Языки
			'_langs'=>array($Forum->language),
			#Скрытые поля
			'extra'=>array(),
			#Имя гостя
			'name'=>$Forum->user ? '' : Eleanor::GetCookie($Forum->config['n'].'-name'),
		);

		$quotes=Eleanor::GetCookie($Forum->config['n'].'-qp');
		if($quotes)
		{
			$waitview=$Forum->ugr['supermod'] || $forum['_moderator'] && (in_array(1,$forum['_moderator']['chstatus']) || in_array(1,$forum['_moderator']['mchstatus']));
			$quotes=array_map(function($v){ return(int)$v; },explode(',',$quotes));
			$R=Eleanor::$Db->Query('SELECT `t`.`language`,`t`.`status` `tstatus`,`t`.`created` `tcreated`,`t`.`author_id` `taid`,`t`.`state`,`p`.`id`,`p`.`f`,`p`.`t`,`p`.`status`,`p`.`author`,`p`.`author_id`,`p`.`created`,`p`.`text` FROM `'.$Forum->config['fp'].'` `p` INNER JOIN `'.$Forum->config['ft'].'` `t` ON `p`.`t`=`t`.`id` WHERE `p`.`id`'.Eleanor::$Db->In($quotes));
			$quotes=array();
			$hasquote=false;
			$gp=$Forum->user ? array() : $Forum->GuestSign('p');
			while($a=$R->fetch_assoc())
			{
				if($a['f']==$forum['id'])
				{
					$qtrights=$Forum->Topic->Rights(array('id'=>$a['t'],'state'=>$a['state'],'author_id'=>$a['taid'],'created'=>$a['tcreated']),$rights,$forum['_moderator']);

					#Возможно, мы физически не имеем доступа к посту
					if(!in_array(1,$qtrights['read']) or !$waitview and ($a['status']==0 or in_array($a['status'],array(-1,-3)) and ($Forum->user and $Forum->user['id']!=$a['author_id'] or !$Forum->user and !in_array($a['id'],$gp))))
						continue;
				}
				elseif(!$Forum->ugr['supermod'])
				{
					if(!list($qforum,$qrights)=$Forum->Post->GetForum($a['f']))
						continue;

					$qtrights=$Forum->Topic->Rights(array('id'=>$a['t'],'state'=>$a['state'],'author_id'=>$a['taid'],'created'=>$a['tcreated']),$qrights,$qforum['_moderator']);

					#Возможно, мы физически не имеем доступа к посту
					if(!in_array(1,$qtrights['read']) or !$forum['_moderator'] and ($a['status']==0 or in_array($a['status'],array(-1,-3)) and ($Forum->user and $Forum->user['id']!=$a['author_id'] or !$Forum->user and !in_array($a['id'],$gp))))
						continue;

					#Возможно, мы физически не имеем доступа к посту
					$qwaitview=in_array(1,$qforum['_moderator']['chstatus']) || in_array(1,$qforum['_moderator']['mchstatus']);
					if(!in_array(1,$qtrights['read']) or !$qwaitview and ($a['status']==0 or in_array($a['status'],array(-1,-3)) and ($Forum->user and $Forum->user['id']!=$a['author_id'] or !$Forum->user and !in_array($a['id'],$gp))))
						continue;
				}

				$hasquote|=strpos($a['text'],'[quote')!==false;

				if($langs)
					$quotes[ $a['language'] ][ $a['id'] ]=array_slice($a,6);
				else
					$quotes[ $a['id'] ]=array_slice($a,6);
			}

			if($quotes)
			{
				if(!isset(OwnBB::$replace['quote']) and $hasquote)
				{
					if(!class_exists('ForumBBQoute',false))
						include$Eleanor->module['path'].'Misc/bb-quote.php';
					OwnBB::$replace['quote']='ForumBBQoute';
				}

				if($langs)
					foreach($quotes as $lng=>$quote)
					{
						foreach($quote as $id=>$data)
						{
							$values['extra']['quotes'][]=$id;
							$values['text'][$lng].='[quote name="'.$data['author'].'" date="'.$data['created'].'" p='.$id."]\n".$Eleanor->Editor->GetEdit($data['text'])."\n[/quote]\n\n";
						}

						if($quote)
							$values['text'][$lng]=rtrim($values['text'][$lng])."\n";
					}
				else
				{
					foreach($quotes as $id=>$data)
					{
						$values['_extra']['quotes'][]=$id;
						$values['text'].='[quote name="'.$data['author'].'" date="'.$data['created'].'" p='.$id."]\n".$Eleanor->Editor->GetEdit($data['text'])."\n[/quote]\n\n";
					}
					$values['text']=rtrim($values['text'])."\n";
				}
			}
		}
	}

	if(Eleanor::$Permissions->MaxUpload()!==false)
	{
		$Eleanor->Uploader->watermark=true;
		$Eleanor->Uploader->allow_walk=$Eleanor->Uploader->previews=false;
		$Eleanor->Uploader->buttons_top=array('update'=>true,'show_previews'=>true);
		$Eleanor->Uploader->buttons_item['edit']=true;
		$Eleanor->Uploader->buttons_item['insert_link']=false;

		if($langs)
			foreach($langs as $l)
				$uploader[$l]=$Eleanor->Uploader->Show(false,$l);
		else
			$uploader=$Eleanor->Uploader->Show();
		$values['extra']['session']=session_id();
	}
	else
		$uploader=false;

	#Префиксы
	$prefixes=array();
	if($forum['prefixes'])
	{
		$R=Eleanor::$Db->Query('SELECT `id`,`title` FROM `'.$Forum->config['pl'].'` WHERE `id`'.Eleanor::$Db->In($forum['prefixes']).' AND `language` IN(\'\',\''.$Forum->language.'\')');
		while($a=$R->fetch_row())
			$prefixes[ $a[0] ]=$a[1];
	}

	$Eleanor->VotingManager->langs=$langs ? $langs : array();
	$Eleanor->VotingManager->noans=!$Forum->ugr['supermod'];
	$Eleanor->VotingManager->bypost=$bypost;

	SetData();
	$title=array('Создание темы',$forum['title']);
	$Eleanor->Editor->preview=array('module'=>$Eleanor->module['name'],'event'=>'preview');
	$c=Eleanor::$Template->NewTopic($values,$forum,$rights,$prefixes,$bypost,$errors,$uploader,$Eleanor->VotingManager->AddEdit(),$Eleanor->Captcha->disabled ? false : $Eleanor->Captcha->GetCode());
	Start();
	echo$c;
}

$post=$_SERVER['REQUEST_METHOD']=='POST' && Eleanor::$our_query;
Eleanor::$Template->queue[]=$Forum->config['posttpl'];
$Forum->LoadUser();

switch($do)
{
	case'new-topic':#Новая тема
		if(!list($forum,$rights)=$Forum->Post->GetForum($id) or !in_array(1,$rights['new']))
			return ExitPage();

		$errors=array();
		$Eleanor->origurl=$Forum->Links->Action('new-topic',$forum['id']);
		$pre=$Forum->Post->Possibility();
		if($pre)
		{
			$lang=$Forum->Language['user-post'];
			foreach($pre as $k=>$l)
				switch(is_int($k) ? $l : $k)
				{
					case'FLOOD_WAIT':
						$errors['FLOOD_WAIT']=$lang['FLOOD_WAIT'](Eleanor::$Permissions->FloodLimit(),$l);
				}
		}

		#В этой переменной хранятся все модераторы форума + наши права, как модератора
		if($forum['moderators'])
			list($forum['moderators'],$forum['_moderator'])=$this->Moderator->ByIds($forum['moderators'],array('movet','move','delete','deletet','edit','editt','chstatust','chstatus','pin','opcl'),$this->Forum->config['n'].'_moders_p'.$forum['id']);
		else
			$forum['moderators']=$forum['_moderator']=array();

		if($errors and !$post)
		{
			$title[]=$lang['pet'];
			$s=Eleanor::$Template->PostErrors($errors);
			Start();
			echo$s;
		}
		elseif(!$post or $errors)
			NewTopic($forum,$rights,$errors);
		else
		{
			$values=array();
			$langs=$Forum->Forums->GetLanguages($forum['id']);
			$extra=isset($_POST['extra']) ? (array)$_POST['extra'] : array();
			$files=Eleanor::$Permissions->MaxUpload()!==false && isset($extra['session']);

			if(!isset(OwnBB::$replace['quote']))
			{
				if(!class_exists('ForumBBQoute',false))
					include$Eleanor->module['path'].'Misc/bb-quote.php';
				OwnBB::$replace['quote']='ForumBBQoute';
			}

			if($langs)
			{
				if(isset($_POST['langs']))
				{
					$langs=array_intersect($langs,(array)$_POST['langs']);
					if(!$langs)
						$langs=array($Forum->language);
				}
				else
					$langs=array($Forum->language);

				foreach($langs as $l)
				{
					$values['files'][$l]=$files ? $Eleanor->Uploader->WorkingPath($l,$extra['session']) : false;
					$values['title'][$l]=isset($_POST['title'][$l]) ? trim((string)Eleanor::$POST['title'][$l]) : '';
					$values['description'][$l]=isset($_POST['description'][$l]) ? trim((string)Eleanor::$POST['description'][$l]) : '';
					$values['text'][$l]=isset($_POST['text'][$l]) ? trim($Eleanor->Editor_result->GetHTML((string)$_POST['text'][$l],true)) : '';

					if(isset($_POST['uri'][$l]))
						$values['uri'][$l]=trim((string)Eleanor::$POST['uri'][$l]);
					elseif(isset($_POST['title'][$l]))
						$values['uri'][$l]=$Eleanor->Url->Filter((string)$_POST['title'][$l]);
					else
						$values['uri'][$l]='';

					if(mb_strlen($values['title'][$l])<5)
					{
						$er='EMPTY_TITLE_'.strtoupper($l);
						$errors[$er]=sprintf('Минимальная длина названия темы составляет %s символов. Исправьте, пожалуйста, для языка %s.',5,Eleanor::$langs[$l]['name']);
					}

					if(mb_strlen($values['text'][$l])<5)
					{
						$er='EMPTY_TEXT_'.strtoupper($l);
						$errors[$er]=sprintf('Минимальная длина поста составляет %s символов. Исправьте, пожалуйста, для языка %s.',5,Eleanor::$langs[$l]['name']);
					}
				}
			}
			else
			{
				$values+=array(
					'files'=>$files ? $Eleanor->Uploader->WorkingPath('',$extra['session']) : false,
					'title'=>isset($_POST['title']) ? trim((string)$_POST['title']) : '',
					'description'=>isset($_POST['description']) ? trim((string)$_POST['description']) : '',
					'text'=>trim($Eleanor->Editor_result->GetHTML('text')),
					'language'=>$forum['language'],
				);

				if(isset($_POST['uri']))
					$values['uri']=trim((string)Eleanor::$POST['uri']);
				elseif(isset($_POST['title']))
					$values['uri']=$Eleanor->Url->Filter((string)$_POST['title']);
				else
					$values['uri']='';

				if(mb_strlen($values['title'])<5)
					$errors['EMPTY_TITLE']=sprintf('Минимальная длина названия темы составляет %s символов. Исправьте, пожалуйста, для языка %s.',5);

				if(mb_strlen($values['text'])<5)
					$errors['EMPTY_TEXT']=sprintf('Минимальная длина поста составляет %s символов.',5);
			}

			$cach=$Eleanor->Captcha->Check(isset($_POST['check']) ? (string)$_POST['check'] : '');
			$Eleanor->Captcha->Destroy();
			if(!$cach)
				$errors[]='WRONG_CAPTCHA';

			if(!$Forum->user)
			{
				$name=isset($_POST['name']) ? (string)$_POST['name'] : '';

				if($name)
					Eleanor::SetCookie($Forum->config['n'].'-name',$name);
				else
					$errors[]='ENTER_NAME';
			}

			$Eleanor->VotingManager->langs=$langs ? $langs : array();
			$voting=$Eleanor->VotingManager->Save(false,(bool)$errors);
			if(is_array($voting))
				$errors+=$voting;
			elseif(!$errors)
				$values['voting']=$voting;

			$cach=$Eleanor->Captcha->Check(isset($_POST['check']) ? (string)$_POST['check'] : '');
			$Eleanor->Captcha->Destroy();
			if(!$cach)
				$errors[]='WRONG_CAPTCHA';

			if($errors)
			{
				NewTopic($forum,$rights,$errors);
				break;
			}

			if(($Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['chstatust'])) and isset($_POST['status']) and in_array((int)$_POST['status'],range(-1,1)))
				$values['status']=(int)$_POST['status'];
			else
				$values['status']=Eleanor::$Permissions->Moderate() || $Forum->user && $Forum->user['moderate'] || $Forum->ugr['moderate'] ? -1 : 1;

			if(($Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['pin'])) and isset($_POST['pinned']) and $ts=strtotime((string)$_POST['pinned']) and time()<$ts and strtotime('+1000day')>=$ts)
				$values['pinned']=date('Y-m-d H:i:s',$ts);

			if(($Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['opcl']) or in_array(1,$rights['close'])) and isset($_POST['closed']))
				$values['state']='closed';
			else
				$values['state']='open';

			try
			{
				$values['f']=$forum['id'];
				if(!$Forum->user)
					$values['author']=$name;
				$topic=$Forum->Post->Topic($values);
			}
			catch(EE_SQL$E)
			{
				$E->Log();
				return NewTopic($forum,$rights,array($E->getMessage()));
			}
			catch(EE$E)
			{
				return NewTopic($forum,$rights,array($E->getMessage()));
			}

			if(isset($extra['quotes']) and $qids=Eleanor::GetCookie($Forum->config['n'].'-qp'))
			{
				$qids=array_diff(explode(',',$qids),explode(',',(string)$extra['quotes']));
				Eleanor::SetCookie($Forum->config['n'].'-qp',$qids ? join(',',$qids) : '');
			}

			$subscr=isset($_POST['subscription']) ? (string)$_POST['subscription'] : false;
			if(isset($topic['t']))
				if($Forum->user)
				{
					$Forum->Topic->MarkRead($topic['t'],$forum['id']);
					if($subscr)
						$Forum->Subscriptions->SubscribeTopic($topic['t'],$Forum->user['id'],$subscr,$values['status']);
				}
				else
				{
					$Forum->GuestSign('p',$topic['p']);
					$Forum->GuestSign('t',$topic['t']);
				}
			else
			{
				if($Forum->user)#Мультиязычная тема
					foreach($topic as $data)
					{
						$Forum->Topic->MarkRead($data['t'],$forum['id']);
						if($subscr)
							$Forum->Subscriptions->SubscribeTopic($topic['t'],$Forum->user['id'],$subscr,$values['status']);
					}
				else
				{
					$posts=$topics=array();
					foreach($topic as $data)
					{
						$posts[]=$data['p'];
						$topics[]=$data['t'];
					}
					$Forum->GuestSign('p',$posts);
					$Forum->GuestSign('t',$topics);
				}

				$data=Eleanor::FilterLangValues($topic,$Forum->language);
				$topic=$data ? $data : reset($topic);
			}

			#Just-created нужно для отправки реализации крона по отправке сообщений
			GoAway($Forum->Links->Topic($forum['id'],$topic['t'],$topic['uri'],array('event'=>'just-created')),301,'post'.$topic['p']);
		}
	break;
	case'edit-topic':#Правка темы
		if(!$Forum->user)
		{
			$gp=$Forum->GuestSign('p');
			$gt=$Forum->GuestSign('t');
			if(!in_array($id,$gt))
				return ExitPage();
		}

		#Получение данных темы
		$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`f`,`prefix`,`status`,`language`,`lrelated`,`created`,`author_id`,`state`,`moved_to`,`title`,`description`,`pinned`,`voting` FROM `'.$Forum->config['ft'].'` WHERE `id`='.$id.' LIMIT 1');
		if(!$values=$R->fetch_assoc() or $Forum->user and $Forum->user['restrict_post'])
			return ExitPage();

		$trights=$Forum->Topic->Rights($values,$rights,$forum['_moderator']);
		if(!$trights['editt'])
			return ExitPage();

		if($values['moved_to'])
			return GoAway($Forum->Links->Action('edit-topic',$values['moved_to']));

		$forum=$Forum->Forums->GetForum($values['f']);
		$rights=$Forum->ForumRights($values['f']);

		#В этой переменной хранятся все модераторы форума + наши права, как модератора
		if($forum['moderators'])
			list($forum['moderators'],$forum['_moderator'])=$this->Moderator->ByIds($forum['moderators'],array('movet','move','delete','deletet','edit','editt','chstatust','chstatus','pin','opcl'),$this->Forum->config['n'].'_moders_p'.$forum['id']);
		else
			$forum['moderators']=$forum['_moderator']=array();

		$values['lrelated']=$values['lrelated'] ? explode(',,',trim($values['lrelated'],',')) : array();
		if($values['lrelated'])
		{
			#ToDo! Правка сродненных тем с других языков
		}

		if($post)
			SaveTopic($forum,$rights,$trights,$values);
		else
			EditTopic($forum,$rights,$trights,$values);
	break;
	case'new-post':#Новое сообщение
		/*if(!list($forum,$rights,$topic)=PrePostTopic($id))
			return ExitPage();
		$trights=TopicRights($topic,$forum['_moderator'],$rights);

		if(!$trights['post'] or $Forum->GetOption('trash')==$forum['id'])
			return ExitPage();

		$error=CanPost();
		if($post and !$error)
			SavePost($forum,$topic,$rights,$trights,array(),array());
		else
			AddEditPost($forum,$topic,$rights,$trights,array(),array(),$error);*/
	break;
	case'edit':#Правка сообщения
		/*#Подготовка к редактированию
		$R=Eleanor::$Db->Query('SELECT `id`,`t`,`status`,`author`,`author_id` FROM `'.$Eleanor->mconfig['fp'].'` WHERE `id`='.$id.' LIMIT 1');
		if(!$values=$R->fetch_assoc())
			return ExitPage();

		if(!list($forum,$rights,$topic)=PrePostTopic($values['t']))
			return ExitPage();
		$trights=TopicRights($topic,$forum['_moderator'],$rights);

		$prights=PostRights($values,$forum['_moderator'],$rights);
		if(!$prights['mod'] and (!$trights['edit'] or !$prights['edit']))
			return ExitPage();

		if($post)
			SavePost($forum,$topic,$rights,$trights,$prights,$values);
		else
			AddEditPost($forum,$topic,$rights,$trights,$prights,$values);*/
	break;
	case'answer':#Ответ на сообщение
		/*$R=Eleanor::$Db->Query('SELECT `id`,`t`,`status`,`author`,`author_id`,`created`,`text` FROM `'.$Eleanor->mconfig['fp'].'` WHERE `id`='.$id.' LIMIT 1');
		if($a=$R->fetch_assoc())
		{
			if(!list($forum,$rights,$topic)=PrePostTopic($a['t']))
				return ExitPage();
			$trights=TopicRights($topic,$forum['_moderator'],$rights);

			if(!$trights['post'] or $Forum->GetOption('trash')==$forum['id'] or !in_array(1,$rights['read']))
				return ExitPage();

			$error=CanPost();
			if($post and !$error)
				SavePost($forum,$topic,$rights,$trights,array(),array());
			else
				AddEditPost($forum,$topic,$rights,$trights,array(),array('_quotes'=>array($a['id']=>array_slice($a,1))),$error);
		}
		else
			return ExitPage();*/
	break;
	default:
		ExitPage();
}

/*function PrePostTopic($id)
{global$Eleanor;
	$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`f`,`status`,`author_id`,`state`,`title`,`pinned`>\''.date('Y-m-d H:i:s').'\' `_topic_pinned` FROM `'.$Eleanor->mconfig['ft'].'` WHERE `id`='.$id.' LIMIT 1');
	if($topic=$R->fetch_assoc() and $Eleanor->Forum->CheckTopicAccess($topic) and $r=GetForum($topic['f']))
	{
		if($topic['state']=='closed' && !$Forum->ugr['supermod'] && !in_array(1,$r[1]['canclose']))
			return;
		$r[]=$topic;
		return$r;
	}
}

/*
	Добавление поста в тему.
	Если указан $id - считается, что мы редактируем пост, если он не указан - считаем, что добавляем пост в форум $id
	$error - ошибка
	$values - начальные значения для добавления поста
*/
/*function AddEditPost($forum,$topic,$rights,$trights,$prights,$values,$error='')
{global$Eleanor,$title;
	if(!isset(OwnBB::$replace['quote']))
	{
		if(!class_exists('ForumBBQoute',false))
			include$Eleanor->module['path'].'Misc/bb-quote.php';
		OwnBB::$replace['quote']='ForumBBQoute';
	}

	$hidden=array();
	$edit=isset($values['id']);
	if($edit)
	{
		$values['_uploader']='temp/'.($Forum->user ? $Forum->user['id'].'-' : '').$Eleanor->mconfig['n'].'-post-'.$values['id'];
		if(!$error)
		{
			try
			{
				$values+=$Forum->Post->EditPost($values['id'],$values['_uploader']);
			}
			catch(EE$E)
			{
				return ExitPage();
			}
			$hidden['postsign']=md5(md5($values['text']).$values['edit_reason']);
		}
		$title=array('Редактирование поста ',$topic['title'],$forum['title']);
	}
	else
	{
		$title=array('Новый пост',$topic['title'],$forum['title']);
		$qids=Eleanor::GetCookie($Eleanor->mconfig['n'].'-qp');
		$values+=array(
			'text'=>'',
			#Статус поста (для модератора)
			'status'=>1,
			'_uploader'=>false,
			#Готовые цитаты
			'_quotes'=>array(),
			#IDы, которые нужны в сообщении
			'_qids'=>$qids ? explode(',',$qids) : array(),
		);
		if(!$Forum->user)
			$values['_name']=Eleanor::GetCookie($Eleanor->mconfig['n'].'-name');#Имя гсстя

		if(!$error)
		{
			if($Forum->user)
				$gp=$gt=array();
			else
			{
				$gp=$Forum->GuestSign();
				$gt=$Forum->GuestSign('t');
			}
			$nint=!in_array($topic['id'],$gt);
			$moder=$Forum->ugr['supermod'] || $forum['_moderator'] && (in_array(1,$forum['_moderator']['chstatus']) or in_array(1,$forum['_moderator']['mchstatus']));

			if($quotes)
			{
				$R=Eleanor::$Db->Query('SELECT `t`.`status` `tstatus`,`t`.`author_id` `taid`,`p`.`id`,`p`.`f`,`p`.`t`,`p`.`status`,`p`.`author`,`p`.`author_id`,`p`.`created`,`p`.`text` FROM `'.$Eleanor->mconfig['fp'].'` `p` INNER JOIN `'.$Eleanor->mconfig['ft'].'` `t` ON `p`.`t`=`t`.`id` WHERE `p`.`id`'.Eleanor::$Db->In($quotes));
				while($a=$R->fetch_assoc())
				{
					if($a['t']==$topic['id'])
					{
						if(!in_array(1,$rights['read']) or !$moder and ($a['status']==0 or (in_array($a['status'],array(-1,-3)) and (!$Forum->user and !in_array($id,$gp) or $Forum->user and $Forum->user['id']!=$a['author_id'])) or ($a['tstatus']==-1 and (!$Forum->user and $nint or $Forum->user and $Forum->user['id']!=$a['taid']))))
							continue;
					}
					elseif($a['f']==$forum['id'])
					{
						$nint_=!in_array($a['t'],$gt);
						if(!in_array(1,$rights['read']) or !$moder and ($a['status']==0 or $a['tstatus']==0 or (in_array($a['status'],array(-1,-3)) and (!$Forum->user and !in_array($id,$gp) and $nint_ or $Forum->user and $Forum->user['id']!=$a['author_id'])) or ($a['tstatus']==-1 and (!$Forum->user and $nint_ or $Forum->user and $Forum->user['id']!=$a['taid']))))
							continue;
					}
					elseif(!$Forum->ugr['supermod'])
					{
						#Локальный вариант функции SF user/topic.php InactiveAccess($f)
						if(!list($forum_,$rights_)=GetForum($a['f']) or !in_array(1,$rights['read']))
							continue;

						if($Forum->user and $forum_['moderators'])
						{
							list(,$fmoder)=$Forum->Moderator->ByIds($forum_['moderators'],array('chstatus','mchstatus'),$Eleanor->mconfig['n'].'_moders_chstp'.$a['f']);
							if($fmoder)
								$fmoder=in_array(1,$fmoder['chstatus']) || in_array(1,$fmoder['mchstatus']);
						}
						else
							$fmoder=false;
						$nint_=!in_array($a['t'],$gt);
						if(!$fmoder and ($a['status']==0 or $a['tstatus']==0 or (in_array($a['status'],array(-1,-3)) and (!$Forum->user and !in_array($id,$gp) and $nint_ or $Forum->user and $Forum->user['id']!=$a['author_id'])) or ($a['tstatus']==-1 and (!$Forum->user and $nint_ or $Forum->user and $Forum->user['id']!=$a['taid']))))
							continue;
					}
					$values['_quotes'][$a['id']]=array_slice($a,3);
				}
			}

			if($values['_quotes'])
			{
				foreach($values['_quotes'] as $k=>&$v)
				{
					$hidden['q'][]=$k;
					$values['text'].='[quote name="'.$v['author'].'" date="'.$v['created'].'" p='.$k.']
'.$Eleanor->Editor_result->GetEdit($v['text']).'
[/quote]

';
				}

				if(isset($hidden['q']))
				{
					$hidden['q']=join(',',$hidden['q']);
					$values['text']=rtrim($values['text']).'
';
				}
				unset($values['_quotes']);
			}
		}
	}
	if(Eleanor::$Permissions->MaxUpload()!==false)
	{
		$Eleanor->Uploader->watermark=$Eleanor->Uploader->previews=true;
		$Eleanor->Uploader->allow_walk=false;
		$Eleanor->Uploader->buttons_top=array('update'=>true,'show_previews'=>true);
		$values['_uploader']=$Eleanor->Uploader->Show($values['_uploader']);
		$hidden['s']=session_id();
	}
	else
		unset($values['_uploader']);
	if($error)
	{
		$bypost=true;
		if($error===true)
			$error='';
		$values['text']=isset($_POST['text']) ? (string)$_POST['text'] : '';
		$values['edit_reason']=isset($_POST['edit_reason']) ? (string)$_POST['edit_reason'] : '';
		$values['_subscription']=isset($_POST['_subscription']) ? (string)$_POST['_subscription'] : '';
		$values['_closed']=isset($_POST['_closed']);
		$values['status']=isset($_POST['status']) ? (int)$_POST['status'] : 1;
		if(!$Forum->user)
			$values['_name']=isset($_POST['_name']) ? (string)$_POST['_name'] : '';
		if(isset($_POST['h']))
			$hidden+=(array)$_POST['h'];
	}
	else
	{
		$bypost=false;
		if($Forum->user)
		{
			$R=Eleanor::$Db->Query('SELECT `intensity` FROM `'.$Eleanor->mconfig['ts'].'` WHERE `t`='.$topic['id'].' AND `uid`='.$Forum->user['id'].' LIMIT 1');
			list($values['_subscription'])=$R->fetch_row();
		}
		else
			$values['_subscription']=0;
		$values['_closed']=$topic['state']=='closed';

		if($edit)
		{
			$back=isset($_GET['noback']) ? false : getenv('HTTP_REFERER');
			if($back and strpos($back,PROTOCOL.Eleanor::$punycode.Eleanor::$site_path)===0 and preg_match('#(\d+)$#',$back,$m)>0 and $m[1]==$values['id'])
				$hidden['returnto']='post';
		}
	}
	Eleanor::$Template->queue[]='ForumPost';
	$c=Eleanor::$Template->AddEditPost($edit,$bypost,$values,$rights,$prights,$forum,$topic,$error,$hidden);
	Start();
	echo$c;
}*/

function SavePost($forum,$topic,$rights,$trights,$prights,$bv)
{global$Eleanor;
	if(isset($_POST['tofull']))#С быстрой правки
		return AddEditPost($forum,$topic,$rights,$trights,$prights,$bv,true);

	$hidden=$values=$tvalues=array();
	$edit=isset($bv['id']);

	if(!isset(OwnBB::$replace['quote']))
	{
		if(!class_exists('ForumBBQoute',false))
			include$Eleanor->module['path'].'Misc/bb-quote.php';
		OwnBB::$replace['quote']='ForumBBQoute';
	}

	$values['text']=trim($Eleanor->Editor->GetHTML('text'));
	if(mb_strlen($values['text'])<5)
		return AddEditPost($forum,$topic,$rights,$trights,$prights,$bv,sprintf('Минимальная длина поста составляет %s символов.',5));

	if($Forum->user)
		$me=Eleanor::$Login->GetUserValue(array('name','id'));
	elseif(!$edit)
	{
		$me=array(
			'id'=>0,
			'name'=>isset($_POST['_name']) ? (string)Eleanor::$POST['_name'] : false,
		);
		if($me['name'])
			Eleanor::SetCookie($Eleanor->mconfig['n'].'-name',$me['name']);
		else
			return AddEditPost($forum,$topic,$rights,$trights,$prights,$bv,'Пожалуйста, представьтесь!');
	}

	$cach=$Eleanor->Captcha->Check(isset($_POST['_check']) ? (string)$_POST['_check'] : '');
	$Eleanor->Captcha->Destroy();
	if(!$cach)
		return AddEditPost($forum,$topic,$rights,$trights,$prights,$bv,'Неправильно введен код защиты');

	$hidden=isset($_POST['h']) ? (array)$_POST['h'] : array();
	if(isset($hidden['q']) and $qids=Eleanor::GetCookie($Eleanor->mconfig['n'].'-qp'))
	{
		$qids=array_diff(explode(',',$qids),explode(',',(string)$hidden['q']));
		Eleanor::SetCookie($Eleanor->mconfig['n'].'-qp',$qids ? join(',',$qids) : '');
	}
	$files=Eleanor::$Permissions->MaxUpload()!==false && isset($hidden['s']);
	if($files)
		$values['_files']=$Eleanor->Uploader->WorkingPath('',$hidden['s']);

	if(($Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['chstatus'])) and isset($_POST['status']) and in_array($_POST['status'],range(-1,1)))
		$values['status']=(int)$_POST['status'];
	else
		$values['status']=Eleanor::$Permissions->Moderate() || $Forum->user && $Forum->user['moderate'] || $Forum->ugr['moderate'] ? -1 : 1;

	if(($Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['opcl']) or in_array(1,$rights['close'])) and isset($_POST['_closed']))
		$tvalues['state']='closed';
	else
		$tvalues['state']='open';

	try
	{
		if($tvalues)
			$Forum->Post->SaveTopic(array('id'=>$topic['id'])+$tvalues);

		if($edit)
		{
			$values['edit_reason']=isset($_POST['edit_reason']) ? (string)Eleanor::$POST['edit_reason'] : '';
			if(isset($hidden['postsign']) and $hidden['postsign']!=md5(md5($values['text']).$values['edit_reason']))
				$values+=array(
					'who_edit'=>isset($me['name']) ? $me['name'] : $bv['author'],
					'who_edit_id'=>isset($me['id']) ? $me['id'] : 0,
					'edit_date'=>date('Y-m-d H:i:s'),
				);
			$values['id']=$bv['id'];
		}
		else
		{
			ForumBBQoute::$cansend=true;
			$values+=array(
				'id'=>false,
				'f'=>$forum['id'],
				't'=>$topic['id'],
				'author'=>$me['name'],
				'author_id'=>$me['id'],
				'ip'=>Eleanor::$ip,
				'language'=>$forum['language'],
				'_topic_status'=>$topic['status'],
				'_topic_pinned'=>$topic['_topic_pinned'],
				'_gp'=>$Forum->GuestSign('p'),
			);
		}
		$values=$Forum->Post->SavePost($values);
		if(!$edit and !$Forum->user)
			$Forum->GuestSign('p',$values['id']);
	}
	catch(EE_SQL$E)
	{
		$E->LogIt($E->addon['logfile'],$E->getMessage());
		return AddEditPost($forum,$topic,$rights,$trights,$prights,$bv,$E->getMessage());
	}
	catch(EE$E)
	{
		return AddEditPost($forum,$topic,$rights,$trights,$prights,$bv,$E->getMessage());
	}

	if($Forum->user and isset($_POST['_subscription']))
		$Forum->Subscriptions->SubscribeTopic($topic['id'],$me['id'],(string)$_POST['_subscription'],$values['status']);

	if(isset($hidden['returnto']) and $hidden['returnto']=='post')
		Go(array('do'=>'post','id'=>$values['_merged'] ? $values['_merged'] : $values['id']),'','post');
	else
		Go(array('do'=>'findpost','id'=>$values['_merged'] ? $values['_merged'] : $values['id']),'');
}