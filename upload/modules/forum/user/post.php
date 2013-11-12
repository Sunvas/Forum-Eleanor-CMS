<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;

/**
 * Создание новой темы
 * @param array $forum Форум, в котором создается тема
 * @param array $rights Права пользователя на форуме
 * @param array|true $errors Массив ошибок
 */
function NewTopic(array$forum,array$rights,$errors=array())
{global$Eleanor,$title;
	/** @var ForumCore $Forum */
	$Forum=$Eleanor->Forum;
	#Языки форума
	$langs=$Forum->Forums->GetLanguages($forum['id']);

	$R=Eleanor::$Db->Query('SELECT `rules` FROM `'.$Forum->config['fl'].'` WHERE `language`IN(\'\',\''.$forum['language'].'\') AND `id`='.$forum['id'].' LIMIT 1');
	$forum+=$R->num_rows==0 ? array('rules'=>'') : $R->fetch_assoc();

	$status=$Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['chstatust']);
	$pin=$Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['pin']);
	$close=$Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['opcl']) || in_array(1,$rights['close']);

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

		$values['extra']=isset($_POST['extra']) ? (array)$_POST['extra'] : array();

		if($Forum->user)
			$values['subscription']=isset($_POST['subscription']) ? (string)$_POST['subscription'] : '';

		if($status)
			$values['status']=isset($_POST['status']) ? (int)$_POST['status'] : 1;

		if($close)
			$values['closed']=isset($_POST['closed']);

		if($pin)
			$values['pinned']=isset($_POST['pinned']) ? (string)$_POST['pinned'] : '';

		if(!$Forum->user)
			$values['name']=isset($_POST['name']) ? (string)$_POST['name'] : '';
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
			#Языки
			'langs'=>array($Forum->language),
			#Скрытые поля
			'extra'=>array(),
		);

		#Подписка
		if($Forum->user)
			$values['subscription']=false;

		#Статус темы (для модератора)
		if($status)
			$values['status']=1;

		#Зафиксировать тему до... (для модератора)
		if($pin)
			$values['pinned']='';

		#Закрыть тему
		if($close)
			$values['closed']=false;

		#Имя гостя
		if(!$Forum->user)
			$values['name']=Eleanor::GetCookie($Forum->config['n'].'-name');

		$quotes=Eleanor::GetCookie($Forum->config['n'].'-qp');
		if($quotes)
		{
			$waitview=$Forum->ugr['supermod'] || $forum['_moderator'] && (in_array(1,$forum['_moderator']['chstatus']) || in_array(1,$forum['_moderator']['mchstatus']));
			$quotes=array_map(function($v){ return(int)$v; },explode(',',$quotes));
			$R=Eleanor::$Db->Query('SELECT `t`.`language`,`t`.`status` `tstatus`,`t`.`created` `tcreated`,`t`.`author_id` `taid`,`t`.`state`,`p`.`id`,`p`.`f`,`p`.`t`,`p`.`status`,`p`.`author`,`p`.`author_id`,`p`.`created`,`p`.`text` FROM `'.$Forum->config['fp'].'` `p` INNER JOIN `'.$Forum->config['ft'].'` `t` ON `p`.`t`=`t`.`id` WHERE `p`.`id`'.Eleanor::$Db->In($quotes));
			$quotes=array();
			$hasquote=false;
			$gp=$Forum->GuestSign('p');
			while($a=$R->fetch_assoc())
			{
				if($a['f']==$forum['id'])
				{
					$qtrights=$Forum->Topic->Rights(array('id'=>$a['t'],'state'=>$a['state'],'author_id'=>$a['taid'],'created'=>$a['tcreated']),$rights,$forum['_moderator']);

					#Возможно, мы физически не имеем доступа к посту
					if(!$qtrights['read'] or !$waitview and ($a['status']==0 or in_array($a['status'],array(-1,-3)) and !($Forum->user and $Forum->user['id']==$a['author_id'] or in_array($a['id'],$gp))))
						continue;
				}
				elseif(!$Forum->ugr['supermod'])
				{
					if(!list($qforum,$qrights)=$Forum->Post->GetForum($a['f']))
						continue;

					$qtrights=$Forum->Topic->Rights(array('id'=>$a['t'],'state'=>$a['state'],'author_id'=>$a['taid'],'created'=>$a['tcreated']),$qrights,$qforum['_moderator']);

					#Возможно, мы физически не имеем доступа к посту
					if(!$qtrights['read'] or !$forum['_moderator'] and ($a['status']==0 or in_array($a['status'],array(-1,-3)) and !($Forum->user and $Forum->user['id']!=$a['author_id'] or in_array($a['id'],$gp))))
						continue;

					#Возможно, мы физически не имеем доступа к посту
					$qwaitview=in_array(1,$qforum['_moderator']['chstatus']) || in_array(1,$qforum['_moderator']['mchstatus']);
					if(!in_array(1,$qtrights['read']) or !$qwaitview and ($a['status']==0 or in_array($a['status'],array(-1,-3)) and !($Forum->user and $Forum->user['id']==$a['author_id'] or in_array($a['id'],$gp))))
						continue;
				}

				if(!$hasquote and strpos($a['text'],'[quote')!==false)
					$hasquote=true;

				if($langs)
				{
					$lng=$a['language'] ? $a['language'] : LANGUAGE;
					$quotes[ $lng ][ $a['id'] ]=array_slice($a,6);
				}
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
						$values['extra']['quotes'][]=$id;
						$values['text'].='[quote name="'.$data['author'].'" date="'.$data['created'].'" p='.$id."]\n".$Eleanor->Editor->GetEdit($data['text'])."\n[/quote]\n\n";
					}
					$values['text']=rtrim($values['text'])."\n";
				}

				$values['extra']['quotes']=join(',',$values['extra']['quotes']);
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
		$R=Eleanor::$Db->Query('SELECT `id`,`language`,`title` FROM `'.$Forum->config['pl'].'` WHERE `id`'.Eleanor::$Db->In($forum['prefixes']).' AND `language`'.($langs ? Eleanor::$Db->In($langs) : ' IN(\'\',\''.$Forum->language.'\')'));
		while($a=$R->fetch_row())
			if($langs)
				$prefixes[ $a[1] ][ $a[0] ]=$a[1];
			else
				$prefixes[ $a[0] ]=$a[2];
	}

	$Eleanor->VotingManager->langs=$langs ? $langs : array();
	$Eleanor->VotingManager->noans=!$Forum->ugr['supermod'];
	$Eleanor->VotingManager->bypost=$bypost;

	SetData();
	$title=array($Forum->Language['user-post']['creating-topic'],$forum['title']);
	$Eleanor->Editor->preview=array('module'=>$Eleanor->module['name'],'event'=>'preview');
	$c=Eleanor::$Template->NewTopic($values,$forum,$rights,$prefixes,$bypost,$errors,$uploader,$rights['createvoting'] ? $Eleanor->VotingManager->AddEdit() : false,$Eleanor->Captcha->disabled ? false : $Eleanor->Captcha->GetCode());
	Start();
	echo$c;
}

/**
 * Редактирование темы или нескольких (одновременное редактирование всех языковых версий темы)
 * @param array $forum Форум, в котором тема редактируется
 * @param array $rights Права пользователя на форуме
 * @param array $trights Права пользователя в теме
 * @param array $topics Значения полей
 */
function EditTopic(array$forum,array$rights,array$trights,array$topics,array$posts,$errors=array())
{global$Eleanor,$title;
	/** @var ForumCore $Forum */
	$Forum=$Eleanor->Forum;
	#Языки форума
	$langs=$Forum->Forums->GetLanguages($forum['id']);

	$R=Eleanor::$Db->Query('SELECT `rules` FROM `'.$Forum->config['fl'].'` WHERE `language`IN(\'\',\''.$forum['language'].'\') AND `id`='.$forum['id'].' LIMIT 1');
	$forum+=$R->num_rows==0 ? array('rules'=>'') : $R->fetch_assoc();

	$status=$Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['chstatust']);
	$pin=$Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['pin']);
	$close=$Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['opcl']) || in_array(1,$rights['close']);
	$editname=$Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['edit']);

	#Удалим лишние языки
	if(count($topics)==1)
		$langs=false;
	else
		foreach($langs as $lk=>$lv)
		{
			$missed=true;
			foreach($topics as $tv)
				if($tv['language']==$lv)
				{
					$missed=false;
					break;
				}
				elseif($tv['language']=='')
				{
					$langs=false;
					break 2;
				}

			if($missed)
				unset($langs[$lk]);
		}

	$canupload=Eleanor::$Permissions->MaxUpload()!==false;
	$values=array();

	if($errors)
	{
		$bypost=true;
		if($errors===true)
			$errors=array();

		if($langs)
			foreach($langs as $l)
			{
				#Topic values
				$values['prefix'][$l]=isset($_POST['prefix'][$l]) ? (int)$_POST['prefix'][$l] : 0;
				$values['uri'][$l]=isset($_POST['uri'][$l]) ? (string)$_POST['uri'][$l] : '';
				$values['title'][$l]=isset($_POST['title'][$l]) ? (string)$_POST['title'][$l] : '';
				$values['description'][$l]=isset($_POST['description'][$l]) ? (string)$_POST['description'][$l] : '';

				if($Forum->user)
					$values['subscription'][$l]=isset($_POST['subscription']) ? (string)$_POST['subscription'] : '';

				if($editname)
					$values['name'][$l]=isset($_POST['name'][$l]) ? (string)$_POST['name'][$l] : '';

				if($close)
					$values['closed'][$l]=isset($_POST['closed'][$l]);

				if($pin)
					$values['pinned'][$l]=isset($_POST['pinned'][$l]) ? (string)$_POST['pinned'][$l] : '';

				if($status)
					$values['status'][$l]=isset($_POST['status'][$l]) ? (int)$_POST['status'][$l] : 1;

				#Posts values
				if(isset($posts[$l]))
				{
					$values['text'][$l]=isset($_POST['text'][$l]) ? (string)$_POST['text'][$l] : '';
					$values['edit_reason'][$l]=isset($_POST['edit_reason'][$l]) ? (string)$_POST['edit_reason'][$l] : '';
				}
			}
		else
		{
			#Topic values
			$values['prefix']=isset($_POST['prefix']) ? (string)$_POST['prefix'] : '';
			$values['uri']=isset($_POST['uri']) ? (string)$_POST['uri'] : '';
			$values['title']=isset($_POST['title']) ? (string)$_POST['title'] : '';
			$values['description']=isset($_POST['description']) ? (string)$_POST['description'] : '';

			if($Forum->user)
				$values['subscription']=isset($_POST['subscription']) ? (string)$_POST['subscription'] : '';

			if($editname)
				$values['name']=isset($_POST['name']) ? (string)$_POST['name'] : '';

			if($close)
				$values['closed']=isset($_POST['closed']);

			if($pin)
				$values['pinned']=isset($_POST['pinned']) ? (string)$_POST['pinned'] : '';

			if($status)
				$values['status']=isset($_POST['status']) ? (int)$_POST['status'] : 1;

			#Posts values
			if(isset($posts))
			{
				$values['text']=isset($_POST['text']) ? (string)$_POST['text'] : '';
				$values['edit_reason']=isset($_POST['edit_reason']) ? (string)$_POST['edit_reason'] : '';
			}
		}

		$values['extra']=isset($_POST['extra']) ? (array)$_POST['extra'] : array();
	}
	else
	{
		$bypost=$hasquote=false;

		$subscrs=array();
		if($Forum->user)
		{
			$R=Eleanor::$Db->Query('SELECT `t`,`intensity` FROM `'.$Forum->config['ts'].'` WHERE `t`'.Eleanor::$Db->In(array_keys($topics)).' AND `uid`='.$Forum->user['id'].' LIMIT 1');
			while($a=$R->fetch_row())
				$subscrs[ $a[0] ]=$a[1];
		}

		$t=time();
		if($langs)
		{
			foreach($topics as $k=>$topic)
				if(in_array($l=$topic['language'],$langs))
				{
					#Topic values
					$values['prefix'][$l]=$topic['prefix'];
					$values['uri'][$l]=$topic['uri'];
					$values['title'][$l]=$topic['title'];
					$values['description'][$l]=$topic['description'];

					if($editname)
						$values['name'][$l]=isset($_POST['name'][$l]) ? (string)$_POST['name'][$l] : '';

					if($Forum->user)
						$values['subscription'][$l]=isset($subscrs[$k]) ? (string)$subscrs[$k] : false;

					if($close)
						$values['closed'][$l]=$topic['state']=='closed';

					if($pin)
						$values['pinned'][$l]=(int)$topic['pinned']>0 && strtotime($topic['pinned'])>$t ? $topic['pinned'] : '';

					if($status)
						$values['status'][$l]=$topic['status'];

					#Posts values
					if(isset($posts[$k]))
					{
						$values['text'][$l]=$posts[$k]['text'];
						$values['edit_reason'][$l]=$posts[$k]['edit_reason'];

						if(!$hasquote and strpos($posts[$k]['text'],'[quote')!==false)
							$hasquote=true;
					}
				}
		}
		else
		{
			$topic=reset($topics);
			$k=key($topics);

			#Topic values
			$values['prefix']=$topic['prefix'];
			$values['uri']=$topic['uri'];
			$values['title']=$topic['title'];
			$values['description']=$topic['description'];

			if($editname)
				$values['name']=isset($_POST['name']) ? (string)$_POST['name'] : '';

			if($Forum->user)
				$values['subscription']=isset($subscrs[$k]) ? (string)$subscrs[$k] : false;

			if($close)
				$values['closed']=$topic['state']=='closed';

			if($pin)
				$values['pinned']=(int)$topic['pinned']>0 && strtotime($topic['pinned'])>$t ? $topic['pinned'] : '';

			if($status)
				$values['status']=$topic['status'];

			#Posts values
			if(isset($posts[$k]))
			{
				$values['text']=$posts[$k]['text'];
				$values['edit_reason']=$posts[$k]['edit_reason'];

				if(!$hasquote and strpos($values['text'],'[quote')!==false)
					$hasquote=true;
			}
		}

		if(!isset(OwnBB::$replace['quote']) and $hasquote)
		{
			if(!class_exists('ForumBBQoute',false))
				include$Eleanor->module['path'].'Misc/bb-quote.php';
			OwnBB::$replace['quote']='ForumBBQoute';
		}
	}

	$uploader=array();
	if($canupload)
	{
		$Eleanor->Uploader->watermark=true;
		$Eleanor->Uploader->allow_walk=$Eleanor->Uploader->previews=false;
		$Eleanor->Uploader->buttons_top=array('update'=>true,'show_previews'=>true);
		$Eleanor->Uploader->buttons_item['edit']=true;
		$Eleanor->Uploader->buttons_item['insert_link']=false;

		#Проверим, возможно к посту прикреплены аттачи
		foreach($topics as $k=>$topic)
			if(isset($posts[$k]) and is_dir($Forum->config['attachroot'].'p'.$posts[$k]['id']))
			{
				$dir=($Forum->user ? $Forum->user['id'].'-' : '0-').$Forum->config['n'].'-post-'.$posts[$k]['id'].'/';
				$upl='temp/'.$dir;

				if(!$errors)
				{
					$sitedir=Eleanor::$uploads.'/'.$upl;
					if($langs)
						$Forum->Post->EditPost($posts[$k]['id'],$values[ $topic['language'] ]['text'],Eleanor::$root.$sitedir,$sitedir);
					else
						$Forum->Post->EditPost($posts[$k]['id'],$values['text'],Eleanor::$root.$sitedir,$sitedir);
				}

				if($langs)
					$uploader[ $topic['language'] ]=$Eleanor->Uploader->Show($upl,$topic['language']);
				else
				{
					$uploader=$Eleanor->Uploader->Show($upl);
					break;
				}
			}

		$values['extra']['session']=session_id();
	}

	#Префиксы
	$prefixes=array();
	if($forum['prefixes'])
	{
		$R=Eleanor::$Db->Query('SELECT `id`,`language`,`title` FROM `'.$Forum->config['pl'].'` WHERE `id`'.Eleanor::$Db->In($forum['prefixes']).' AND `language`'.($langs ? Eleanor::$Db->In($langs) : ' IN(\'\',\''.$Forum->language.'\')'));
		while($a=$R->fetch_row())
			if($langs)
				$prefixes[ $a[1] ][ $a[0] ]=$a[1];
			else
				$prefixes[ $a[0] ]=$a[2];
	}

	$Eleanor->VotingManager->langs=$langs ? $langs : array();
	$Eleanor->VotingManager->noans=!$Forum->ugr['supermod'];
	$Eleanor->VotingManager->bypost=$bypost;

	SetData();
	$title=array('Редактирование темы',$forum['title']);
	$Eleanor->Editor->preview=array('module'=>$Eleanor->module['name'],'event'=>'preview');
	$voting=reset($topics);
	$c=Eleanor::$Template->EditTopic($values,$forum,$rights,$prefixes,$bypost,$errors,$uploader,$rights['createvoting'] ? $Eleanor->VotingManager->AddEdit($voting['voting']) : false,$Eleanor->Captcha->disabled ? false : $Eleanor->Captcha->GetCode());
	Start();
	echo$c;
}

/**
 * Создание или правка поста
 * @param array|false $post Пост
 * @param array $forum Форум
 * @param array $topic Тема
 * @param array $rights Права на форуме
 * @param array $trights Права в теме
 * @param array|true $errors Ошибки
 * @param array $extra Дополнительные параметры поста, возможные ключи:
 *   string action Ссылка на action формы отправки поста
 *   string quote Пост, который нужно процитировать (ответ)
 */
function AddEditPost($post,$forum,$topic,$rights,$trights,$errors,array$extra=array())
{global$Eleanor,$title;
	/** @var ForumCore $Forum */
	$Forum=$Eleanor->Forum;
	$extra+=array(
		'action'=>false,
		'quote'=>false,
	);

	$R=Eleanor::$Db->Query('SELECT `rules` FROM `'.$Forum->config['fl'].'` WHERE `language`IN(\'\',\''.$topic['language'].'\') AND `id`='.$forum['id'].' LIMIT 1');
	$forum+=$R->num_rows==0 ? array('rules'=>'') : $R->fetch_assoc();

	$lang=$Forum->Language['user-post'];
	$canupload=Eleanor::$Permissions->MaxUpload()!==false;
	$status=$Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['chstatus']);
	$close=$Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['opcl']) || in_array(1,$rights['close']);
	$editname=$Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['edit']);
	$uploader=false;

	if($post)
	{
		#Проверим, возможно к посту прикреплены аттачи
		if($canupload and is_dir($Forum->config['attachroot'].'p'.$post['id']))
		{
			$dir=($Forum->user ? $Forum->user['id'].'-' : '0-').$Forum->config['n'].'-post-'.$post['id'].'/';
			$uploader='temp/'.$dir;
			if(!$errors)
			{
				$sitedir=Eleanor::$uploads.'/'.$uploader;
				$Forum->Post->EditPost($post['id'],$post['text'],Eleanor::$root.$sitedir,$sitedir);
			}
		}

		if(!$errors)
		{
			if(!isset(OwnBB::$replace['quote']) and strpos($post['text'],'[quote')!==false)
			{
				if(!class_exists('ForumBBQoute',false))
					include$Eleanor->module['path'].'Misc/bb-quote.php';
				OwnBB::$replace['quote']='ForumBBQoute';
			}

			$values=array(
				#Текст поста
				'text'=>$post['text'],
				#Причина редактирования
				'edit_reason'=>$post['edit_reason'],
				#Имя пользователя (только для супермодератора)
				'extra'=>array(
					'sign'=>md5(md5($post['text']).$post['edit_reason']),
				),
			);

			#Статус поста
			if($status)
			{
				$values['status']=$post['status'];
				switch($values['status'])
				{
					case -3:#Ожидающие посты неактивной темы
						$values['status']=-1;
					break;
					case -2:#Активные посты неактивной темы
						$values['status']=1;
				}
			}

			#Имя гостя
			if(!$post['author_id'] && $editname)
				$values['name']=$post['author'];
		}
		$title=array($lang['editing-post'],$topic['title'],$forum['title']);
	}
	else
	{
		$title=array($lang['creating-post'],$topic['title'],$forum['title']);
		$values=array(
			#Текст
			'text'=>'',
			#Скрытые поля
			'extra'=>array(),
		);

		#Имя гостя
		if(!$Forum->user)
			$values['name']=Eleanor::GetCookie($Forum->config['n'].'-name');

		#Статус поста
		if($status)
			$values['status']=1;

		if(!$errors)
		{
			#Цитаты
			if($extra['quote'])
			{
				$hasquote=strpos($extra['quote']['text'],'[quote')!==false;
				$quotes=array( $extra['quote']['id']=>array_slice($extra['quote'],1) );
			}
			elseif($quotes=Eleanor::GetCookie($Forum->config['n'].'-qp'))
			{
				$waitview=$Forum->ugr['supermod'] || $forum['_moderator'] && (in_array(1,$forum['_moderator']['chstatus']) || in_array(1,$forum['_moderator']['mchstatus']));
				$quotes=array_map(function($v){ return(int)$v; },explode(',',$quotes));
				$R=Eleanor::$Db->Query('SELECT `t`.`status` `tstatus`,`t`.`created` `tcreated`,`t`.`author_id` `taid`,`t`.`state`,`p`.`id`,`p`.`f`,`p`.`t`,`p`.`status`,`p`.`author`,`p`.`author_id`,`p`.`created`,`p`.`text` FROM `'.$Forum->config['fp'].'` `p` INNER JOIN `'.$Forum->config['ft'].'` `t` ON `p`.`t`=`t`.`id` WHERE `p`.`id`'.Eleanor::$Db->In($quotes).($forum['language'] ? ' AND `p`.`language` IN(\'\',\''.$forum['language'].'\')' : ''));
				$quotes=array();
				$hasquote=false;
				$gp=$Forum->GuestSign('p');
				while($a=$R->fetch_assoc())
				{
					if($a['t']==$topic['id'])
					{
						if(!$waitview and ($a['status']==0 or in_array($a['status'],array(-1,-3)) and !($Forum->user and $Forum->user['id']==$a['author_id'] or in_array($a['id'],$gp))))
							continue;
					}
					elseif($a['f']==$forum['id'])
					{
						$qtrights=$Forum->Topic->Rights(array('id'=>$a['t'],'state'=>$a['state'],'author_id'=>$a['taid'],'created'=>$a['tcreated']),$rights,$forum['_moderator']);

						#Возможно, мы физически не имеем доступа к посту
						if(!$qtrights['read'] or !$waitview and ($a['status']==0 or in_array($a['status'],array(-1,-3)) and !($Forum->user and $Forum->user['id']==$a['author_id'] or in_array($a['id'],$gp))))
							continue;
					}
					elseif(!$Forum->ugr['supermod'])
					{
						if(!list($qforum,$qrights)=$Forum->Post->GetForum($a['f']))
							continue;

						$qtrights=$Forum->Topic->Rights(array('id'=>$a['t'],'state'=>$a['state'],'author_id'=>$a['taid'],'created'=>$a['tcreated']),$qrights,$qforum['_moderator']);

						#Возможно, мы физически не имеем доступа к посту
						if(!$qtrights['read'] or !$forum['_moderator'] and ($a['status']==0 or in_array($a['status'],array(-1,-3)) and !($Forum->user and $Forum->user['id']!=$a['author_id'] or in_array($a['id'],$gp))))
							continue;

						#Возможно, мы физически не имеем доступа к посту
						$qwaitview=in_array(1,$qforum['_moderator']['chstatus']) || in_array(1,$qforum['_moderator']['mchstatus']);
						if(!in_array(1,$qtrights['read']) or !$qwaitview and ($a['status']==0 or in_array($a['status'],array(-1,-3)) and !($Forum->user and $Forum->user['id']==$a['author_id'] or in_array($a['id'],$gp))))
							continue;
					}

					if(!$hasquote and strpos($a['text'],'[quote')!==false)
						$hasquote=true;

					$quotes[ $a['id'] ]=array_slice($a,5);
				}
			}

			if($quotes)
			{
				if(!isset(OwnBB::$replace['quote']) and $hasquote)
				{
					if(!class_exists('ForumBBQoute',false))
						include$Eleanor->module['path'].'Misc/bb-quote.php';
					OwnBB::$replace['quote']='ForumBBQoute';
				}

				foreach($quotes as $id=>$data)
				{
					$values['extra']['quotes'][]=$id;
					$values['text'].='[quote name="'.$data['author'].'" date="'.$data['created'].'" p='.$id."]\n".$Eleanor->Editor->GetEdit($data['text'])."\n[/quote]\n\n";
				}
				$values['text']=rtrim($values['text'])."\n";

				$values['extra']['quotes']=join(',',$values['extra']['quotes']);
			}
		}
	}

	if($canupload)
	{
		$Eleanor->Uploader->watermark=true;
		$Eleanor->Uploader->allow_walk=$Eleanor->Uploader->previews=false;
		$Eleanor->Uploader->buttons_top=array('update'=>true,'show_previews'=>true);
		$Eleanor->Uploader->buttons_item['edit']=true;
		$Eleanor->Uploader->buttons_item['insert_link']=false;
		$uploader=$Eleanor->Uploader->Show($uploader);
		$values['extra']['session']=session_id();
	}
	else
		$uploader=false;

	if($errors)
	{
		$bypost=true;
		if($errors===true)
			$errors=array();

		$values['text']=isset($_POST['text']) ? (string)$_POST['text'] : '';
		$values['edit_reason']=isset($_POST['edit_reason']) ? (string)$_POST['edit_reason'] : '';
		$values['extra']=isset($_POST['extra']) ? (array)$_POST['extra'] : array();

		if($Forum->user)
			$values['subscription']=isset($_POST['_subscription']) ? (string)$_POST['_subscription'] : '';

		if($close)
			$values['closed']=isset($_POST['_closed']);

		if($status)
			$values['status']=isset($_POST['status']) ? (int)$_POST['status'] : 1;

		if($post ? $editname : !$Forum->user)
			$values['name']=isset($_POST['name']) ? (string)$_POST['name'] : '';
	}
	else
	{
		$bypost=false;
		$values['closed']=$topic['state']=='closed';

		if($Forum->user)
		{
			$R=Eleanor::$Db->Query('SELECT `intensity` FROM `'.$Forum->config['ts'].'` WHERE `t`='.$topic['id'].' AND `uid`='.$Forum->user['id'].' LIMIT 1');
			list($values['subscription'])=$R->num_rows>0 ? $R->fetch_row() : array(false);
		}

		if(isset($_GET['return']))
			$values['extra']['return']=(string)$_GET['return'];
	}

	$values['extra']['back']=isset($_POST['back']) ? (string)$_POST['back'] : getenv('HTTP_REFERER');
	$links=array(
		'topic'=>$Forum->Links->Topic($forum['id'],$topic['id'],$topic['uri']),
		'action'=>$extra['action'],
		'ltp'=>$post ? $Forum->Links->Action(isset($values['extra']['return']) ? 'post' : 'find-post',$post['id']) : false,
	);

	#Сэкономим память
	if($post)
		foreach($values as $k=>$v)
			unset($post[$k]);

	SetData();
	$Eleanor->Editor->preview=array('module'=>$Eleanor->module['name'],'event'=>'preview');

	$c=Eleanor::$Template->AddEditPost($values,$post,$forum,$topic,$rights,$trights,$errors,$bypost,$uploader,$links,$Eleanor->Captcha->disabled ? false : $Eleanor->Captcha->GetCode());
	Start();
	echo$c;
}

/**
 * Сохранение или обновление поста
 * @param array|false $post Пост
 * @param array $forum Форум
 * @param array $topic Тема
 * @param array $rights Права на форуме
 * @param array $trights Права в теме
 */
function SavePost($post,$forum,$topic,$rights,$trights)
{global$Eleanor;
	/** @var ForumCore $Forum */
	$Forum=$Eleanor->Forum;
	$lang=$Forum->Language['user-post'];
	$status=$Forum->ugr['supermod'] || $forum['_moderator'] && in_array(1,$forum['_moderator']['chstatus']);

	$errors=array();
	$extra=isset($_POST['extra']) ? (array)$_POST['extra'] : array();
	$text=isset($_POST['text']) ? trim((string)$_POST['text']) : '';

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
			Eleanor::SetCookie($Forum->config['n'].'-name',$name);
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
		return AddEditPost($post,$forum,$topic,$rights,$trights,$errors);

	$values=array(
		'files'=>Eleanor::$Permissions->MaxUpload()!==false && isset($extra['session']) ? $Eleanor->Uploader->WorkingPath('',$extra['session']) : false,
		'text'=>$Eleanor->Editor_result->GetHTML($text,true),
	);

	if($post)
	{
		$values+=array(
			'edit_reason'=>isset($_POST['edit_reason']) ? (string)Eleanor::$POST['edit_reason'] : '',
		);
		#ToDo! Обновление поста
	}
	else
	{
		$values+=array(
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
			return AddEditPost($post,$forum,$topic,$rights,$trights,array($E->getMessage()));
		}
		catch(EE$E)
		{
			return AddEditPost($post,$forum,$topic,$rights,$trights,array($E->getMessage()));
		}

		if(isset($extra['quotes']) and $qids=Eleanor::GetCookie($Forum->config['n'].'-qp'))
		{
			$qids=array_diff(explode(',',$qids),explode(',',(string)$extra['quotes']));
			Eleanor::SetCookie($Forum->config['n'].'-qp',$qids ? join(',',$qids) : '');
		}

		#Подписка
		if($Forum->user)
			$Forum->Subscriptions->SubscribeTopic($topic['id'],$Forum->user['id'],
				isset($_POST['subscription']) ? (string)$_POST['subscription'] : false,$topic['status']);
		elseif(!$p['merged'])
			$Forum->GuestSign('p',$p['id']);
	}

	#Обновляем тему
	$tupd=array();
	if(($Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['pin'])) and isset($_POST['pinned']) and $ts=strtotime((string)$_POST['pinned']) and time()<$ts and strtotime('+1000day')>=$ts)
		$tupd['pinned']=date('Y-m-d H:i:s',$ts);

	if($Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['opcl']) or in_array(1,$rights['close']))
		$tupd['state']=isset($_POST['closed']) ? 'closed' : 'open';

	if($tupd)
		$Forum->Post->Topic(array('id'=>$topic['id'])+$tupd);

	#Just-created нужно для отправки реализации крона по отправке сообщений
	if(isset($_GET['return']) and $post)
		GoAway($Forum->Links->Action('find-post',$post['id']));
	else
		GoAway($Forum->Links->Action('find-post',$post ? $post['id'] : $p['id'],array('event'=>$post ? false : ($p['merged'] ? 'merged' : 'just-created'))));
}

$post=$_SERVER['REQUEST_METHOD']=='POST' && Eleanor::$our_query;
Eleanor::$Template->queue[]=$Forum->config['posttpl'];
$Forum->LoadUser();

switch($do)
{
	case'new-topic':#Новая тема
		if(!list($forum,$rights)=$Forum->Post->GetForum((int)$id) or !in_array(1,$rights['new']) or $Forum->user and $Forum->user['restrict_post'])
			return ExitPage();

		$errors=array();
		/** @var Eleanor $Eleanor */
		$Eleanor->origurl=$Forum->Links->Action('new-topic',$forum['id']);
		$pre=$Forum->Post->Possibility();
		if($pre)
		{
			$lang=$Forum->Language['user-post'];
			$title[]=$lang['pet'];
			foreach($pre as $k=>$l)
				switch(is_int($k) ? $l : $k)
				{
					case'FLOOD_WAIT':
						$errors['FLOOD_WAIT']=$lang['FLOOD_WAIT'](Eleanor::$Permissions->FloodLimit(),$l);
				}
		}

		if($errors and !$post)
		{
			$s=Eleanor::$Template->PostErrors($errors);
			Start();
			echo$s;
		}
		elseif(!$post or $errors)
			NewTopic($forum,$rights,$errors);
		else
		{#Сохранение темы
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
					$errors['EMPTY_TITLE']=sprintf('Минимальная длина названия темы составляет %s символов. Исправьте, пожалуйста.',5);

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
				{
					Eleanor::SetCookie($Forum->config['n'].'-name',$name);
					$values['author']=$name;
					$values['author_id']=null;
				}
				else
					$errors[]='ENTER_NAME';
			}

			$Eleanor->VotingManager->langs=$langs ? $langs : array();
			$voting=$Eleanor->VotingManager->Save(false,(bool)$errors);
			if(is_array($voting))
				$errors+=$voting;
			elseif(!$errors)
				$values['voting']=$voting;

			if(isset($_POST['prefix']))
			{
				$prefix=(int)$_POST['prefix'];
				if(in_array($prefix,$forum['prefixes']))
					$values['prefix']=$prefix;
			}

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
		$gp=$Forum->GuestSign('p');
		$gt=$Forum->GuestSign('t');

		if(!$Forum->user and !in_array($id,$gt))
			return ExitPage();

		#Получение данных темы
		$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`f`,`prefix`,`status`,`language`,`lrelated`,`created`,`author_id`,`state`,`moved_to`,`title`,`description`,`pinned`,`voting` FROM `'.$Forum->config['ft'].'` WHERE `id`='.(int)$id.' LIMIT 1');
		if(!$topic=$R->fetch_assoc() or $Forum->user and $Forum->user['restrict_post'])
			return ExitPage();

		if(!list($forum,$rights)=$Forum->Post->GetForum($topic['f']))
			return ExitPage();

		$trights=$Forum->Topic->Rights($topic,$rights,$forum['_moderator']);
		if(!$trights['editt'] or !$trights['read'])
			return ExitPage();

		if($topic['moved_to'])
			return GoAway($Forum->Links->Action('edit-topic',$topic['moved_to']));

		$topic['lrelated']=$topic['lrelated'] ? explode(',,',trim($topic['lrelated'],',')) : array();

		if(!$Forum->user)
			$topic['lrelated']=array_intersect($topic['lrelated'],$gt);

		$topics=array( $topic['id']=>array_slice($topic,1) );
		
		if($topic['lrelated'])
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`prefix`,`status`,`language`,`lrelated`,`created`,`author_id`,`state`,`moved_to`,`title`,`description`,`pinned`,`voting` FROM `'.$Forum->config['ft'].'` WHERE `id`'.Eleanor::$Db->In($topic['lrelated']).' AND `f`='.$topic['f'].' AND `voting`='.$topic['voting']);
			while($a=$R->fetch_assoc())
			{
				$ltrights=$Forum->Topic->Rights($a,$rights,$forum['_moderator']);
				if(!$ltrights['editt'] or $a['moved_to'])
					continue;

				$a['lrelated']=$a['lrelated'] ? explode(',,',trim($a['lrelated'],',')) : array();

				if(!$Forum->user)
					$a['lrelated']=array_intersect($a['lrelated'],$gt);
				
				$topics[ $a['id'] ]=array_slice($a,1);
			}
		}

		$q=$posts=array();

		if($Forum->ugr['supermod'] or in_array(1,$rights['edit']))
		{
			foreach($topics as $k=>$v)
				$q[]='(SELECT `t`,`id`,`status`,`author`,`author_id`,`created`,`edit_reason`,`text` FROM `'.$Forum->config['fp'].'` WHERE `t`='.$k.' ORDER BY `sortdate` ASC LIMIT 1)';

			$R=Eleanor::$Db->Query(join('UNION ALL',$q));
			while($a=$R->fetch_assoc())
			{
				#Проверка возможности доступа к посту
				$prights=$Forum->Post->Rights($a,$rights,$forum['_moderator']);
				if($prights['edit'])
					$posts[ $a['t'] ]=array_slice($a,1);
			}
		}

		if($post)
		{
			#ToDo! Сохранение темы
		}
		else
			EditTopic($forum,$rights,$trights,$topics,$posts);
	break;
	case'new-post':#Новое сообщение
		#Получение данных темы
		$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`f`,`status`,`language`,`author_id`,`state`,`title` FROM `'.$Forum->config['ft'].'` WHERE `id`='.(int)$id.' LIMIT 1');
		if(!$topic=$R->fetch_assoc() or !list($forum,$rights)=$Forum->Post->GetForum($topic['f']))
			return ExitPage();

		#Проверка возможности публиковать в тему
		$trights=$Forum->Topic->Rights($topic,$rights,$forum['_moderator']);
		if(!$trights['post'])
			return ExitPage();

		$errors=array();
		$Eleanor->origurl=$Forum->Links->Action('new-post',$topic['id']);
		$pre=$Forum->Post->Possibility();
		if($pre)
		{
			$lang=$Forum->Language['user-post'];
			$title[]=$lang['pep'];
			foreach($pre as $k=>$l)
				switch(is_int($k) ? $l : $k)
				{
					case'FLOOD_WAIT':
						$errors['FLOOD_WAIT']=$lang['FLOOD_WAIT'](Eleanor::$Permissions->FloodLimit(),$l);
				}
		}

		if($errors and !$post)
		{
			$s=Eleanor::$Template->PostErrors($errors);
			Start();
			echo$s;
		}
		else
		{
			if(isset($_POST['_to-full']))
				$errors=true;
			
			if(!$post or $errors)
				AddEditPost(false,$forum,$topic,$rights,$trights,$errors);
			else
				SavePost(false,$forum,$topic,$rights,$trights);
		}
	break;
	case'answer':#Ответ на сообщение
		#Получение данных поста
		$R=Eleanor::$Db->Query('SELECT `id`,`t`,`status`,`author`,`author_id`,`created`,`text` FROM `'.$Forum->config['fp'].'` WHERE `id`='.(int)$id.' LIMIT 1');
		if(!$anspost=$R->fetch_assoc() or $Forum->user and $Forum->user['restrict_post'])
			return ExitPage();

		#Получение данных темы
		$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`f`,`status`,`language`,`author_id`,`state`,`title` FROM `'.$Forum->config['ft'].'` WHERE `id`='.$anspost['t'].' LIMIT 1');
		if(!$topic=$R->fetch_assoc() or !list($forum,$rights)=$Forum->Post->GetForum($topic['f']))
			return ExitPage();

		#Проверка возможности доступа к посту
		$prights=$Forum->Post->Rights($anspost,$rights,$forum['_moderator']);
		if(!$prights['show'])
			return ExitPage();
		
		#Проверка возможности публиковать в тему
		$trights=$Forum->Topic->Rights($topic,$rights,$forum['_moderator']);
		if(!$trights['post'] or !$trights['read'])
			return ExitPage();

		$errors=array();
		$Eleanor->origurl=$Forum->Links->Action('answer',$anspost['id']);
		$pre=$Forum->Post->Possibility();
		if($pre)
		{
			$lang=$Forum->Language['user-post'];
			$title[]=$lang['pep'];
			foreach($pre as $k=>$l)
				switch(is_int($k) ? $l : $k)
				{
					case'FLOOD_WAIT':
						$errors['FLOOD_WAIT']=$lang['FLOOD_WAIT'](Eleanor::$Permissions->FloodLimit(),$l);
				}
		}

		if($errors and !$post)
		{
			$s=Eleanor::$Template->PostErrors($errors);
			Start();
			echo$s;
		}
		elseif(!$post or $errors)
			AddEditPost(false,$forum,$topic,$rights,$trights,$errors,array(
				'action'=>$Forum->Links->Action('new-post',$topic['id']),
				'quote'=>$anspost,
			));
		else
			SavePost(false,$forum,$topic,$rights,$trights);
	break;
	case'edit':#Правка сообщения
		$gp=$Forum->GuestSign('p');

		if($Forum->user ? $Forum->user['restrict_post'] : !in_array($id,$gp))
			return ExitPage();

		#Получение данных поста
		$R=Eleanor::$Db->Query('SELECT `id`,`t`,`status`,`author`,`author_id`,`created`,`edit_reason`,`text` FROM `'.$Forum->config['fp'].'` WHERE `id`='.(int)$id.' LIMIT 1');
		if(!$edpost=$R->fetch_assoc())
			return ExitPage();

		#Получение данных темы
		$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`f`,`status`,`language`,`author_id`,`state`,`title` FROM `'.$Forum->config['ft'].'` WHERE `id`='.$edpost['t'].' LIMIT 1');
		if(!$topic=$R->fetch_assoc() or !list($forum,$rights)=$Forum->Post->GetForum($topic['f']))
			return ExitPage();

		#Проверка возможности доступа к посту
		$prights=$Forum->Post->Rights($edpost,$rights,$forum['_moderator']);
		if(!$prights['edit'])
			return ExitPage();

		#Проверка возможности доступа к теме
		$trights=$Forum->Topic->Rights($topic,$rights,$forum['_moderator']);
		if(!$trights['read'])
			return ExitPage();

		$errors=array();
		if(isset($_POST['_to-full']))
			$errors=true;

		if(!$post or $errors)
			AddEditPost($edpost,$forum,$topic,$rights,$trights,$errors);
		else
			SavePost($edpost,$forum,$topic,$rights,$trights);
	break;
	default:
		ExitPage();
}