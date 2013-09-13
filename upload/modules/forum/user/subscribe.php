<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym

	Реализация интерфейса подписок для пользователей. Проверка на пользователя уже сделана в файле index.php
*/
defined('CMS')||die;

$Forum=$Eleanor->Forum;
Eleanor::$Template->queue[]=$Forum->config['substpl'];
$id=(int)$id;

switch($do)
{
	case'subscribe-forum':#Подписка или отписка на форум
		$forum=$Forum->Forums->GetForum($id);

		#Проверка возможности доступа к темам форума
		if(!$forum or $forum['is_category'] or $Forum->vars['trash']==$id or !$Forum->CheckTopicAccess(array('f'=>$id,'t'=>0,'author_id'=>0)))
			return ExitPage();

		if(isset($_POST['set']))
		{
			$set=(string)$_POST['set'];
			$Forum->Subscriptions->SubscribeForum($id, $forum['language'], $Forum->user['id'], $set);
		}

		$R=Eleanor::$Db->Query('SELECT `sent`,`lastview`,`lastsend`,`nextsend`,`intensity` FROM `'. $Forum->config['fs'].'` WHERE `f`='.$id.' AND `language`=\''. $Forum->Forums->dump[$id]['language'].'\' AND `uid`='.$Forum->user['id'].' LIMIT 1');
		if($R->num_rows>0)
		{
			$current=$R->fetch_assoc();
			if(!isset($set))
				$set=isset($_GET['set']) ? (string)$_GET['set'] : $current['intensity'];
		}
		else
		{
			$current=false;
			if(!isset($set))
				$set=isset($_GET['set']) ? (string)$_GET['set'] : false;
		}

		$forum['_a']=$Forum->Links->Forum($id);

		$title[]='Подписка на форум';
		SetData();
		$c=Eleanor::$Template->SubscribeForum($forum,$current,$set);
		Start();
		echo$c;
	break;
	case'subscribe-topic':#Подписка или отписка на тему
		$R=Eleanor::$Db->Query('SELECT `uri`,`author_id`,`f`,`status`,`title`,`description` FROM `'.$Forum->config['ft'].'` WHERE `id`='.$id.' LIMIT 1');
		if(!$topic=$R->fetch_assoc() or !$Forum->CheckTopicAccess($topic) or $Forum->vars['trash']==$topic['f'])
			return ExitPage();

		$forum=$Forum->Forums->GetForum($topic['f']);
		if($topic['status']!=1)
		{
			#В этой переменной хранятся наши права, как модератора
			if($forum['moderators'])
				list(,$moder)=$Forum->Moderator->ByIds($forum['moderators'],array('chstatust','mchstatust'),$Forum->config['n'].'_moders_ta'.$forum['id'].$Forum->language);
			else
				$moder=false;

			if(($topic['status']==0 or $Forum->user['id']!=$topic['author_id']) and !$Forum->ugr['supermod'] and (!$moder or !in_array(1,$moder['chstatust']) and !in_array(1,$moder['mchstatust'])))
				return ExitPage();
		}

		if(isset($_POST['set']))
		{
			$current=$set=(string)$_POST['set'];
			$Forum->Subscriptions->SubscribeTopic($id, $Forum->user['id'], $set, $topic['status']);
		}

		$R=Eleanor::$Db->Query('SELECT `sent`,`lastview`,`lastsend`,`nextsend`,`intensity` FROM `'. $Forum->config['ts'].'` WHERE `t`='.$id.' AND `uid`='.$Forum->user['id'].' LIMIT 1');
		if($R->num_rows>0)
		{
			$current=$R->fetch_assoc();
			if(!isset($set))
				$set=isset($_GET['set']) ? (string)$_GET['set'] : $current['intensity'];
		}
		else
		{
			$current=false;
			if(!isset($set))
				$set=isset($_GET['set']) ? (string)$_GET['set'] : false;
		}

		$forum['_a']=$Forum->Links->Forum($topic['f']);
		$topic['_a']=$Forum->Links->Topic($topic['f'],$id,$topic['uri']);

		$title[]='Подписка на тему';
		SetData();
		$c=Eleanor::$Template->SubscribeTopic($topic,$forum,$current,$set);
		Start();
		echo$c;
	break;
}