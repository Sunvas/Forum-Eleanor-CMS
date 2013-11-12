<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
/** @var ForumCore $Forum */
$Forum = $Eleanor->Forum;
if($Forum->user)
	switch($event)
	{
		case'subscribe-forum':#Подписка на форум
			$f=isset($_POST['f']) ? (int)$_POST['f'] : 0;
			$type=isset($_POST['type']) ? (string)$_POST['type'] : 'm';
			$lang=isset($_POST['language']) ? (string)$_POST['language'] : '';

			if($Forum->vars['trash']==$f or !$Forum->CheckTopicAccess(array('f'=>$f,'t'=>0,'author_id'=>0)))
				return Error();
			$R=Eleanor::$Db->Query('SELECT `id` FROM `'. $Forum->config['fl'].'` WHERE `id`='.$f.' AND `language`='.Eleanor::$Db->Escape($lang).' LIMIT 1');
			if($R->num_rows==0 and (!isset($Eleanor->Forums->dump[$f]) or $Eleanor->Forums->dump[$f]['language']!=''))
				return Error();

			$Forum->Subscriptions->SubscribeForum($f,$R->num_rows==0 ? '' : $lang, $Forum->user['id'],$type);
			Result(true);
		break;
		case'subscribe-topic':#Подписка на тему
			$t=isset($_POST['t']) ? (int)$_POST['t'] : 0;
			$type=isset($_POST['type']) ? (string)$_POST['type'] : 'm';
			$R=Eleanor::$Db->Query('SELECT `author_id`,`f`,`status` FROM `'. $Forum->config['ft'].'` WHERE `id`='.$t.' LIMIT 1');
			if(!$topic=$R->fetch_assoc() or !$Forum->CheckTopicAccess($topic) or $Forum->vars['trash']==$topic['f'])
				return Error();
			if($topic['status']!=1)
			{
				$forum= $Forum->Forums->GetForum($topic['f']);
				#В этой переменной хранятся наши права, как модератора
				if($forum['moderators'])
					list(,$moder)=$Forum->Moderator->ByIds($forum['moderators'],array('chstatust','mchstatust'),$Forum->config['n'].'_moders_ta'.$forum['id'].$Forum->language);
				else
					$moder=false;

				$gt=$Forum->GuestSign('t');

				if(($topic['status']==0 or $topic['status']==-1 and $Forum->user['id']!=$topic['author_id'] and !in_array($t,$gt)) and !$Forum->ugr['supermod'] and (!$moder or !in_array(1,$moder['chstatust']) and !in_array(1,$moder['mchstatust'])))
					return Error();
			}
			$Forum->Subscriptions->SubscribeTopic($t, $Forum->user['id'],$type,$topic['status']);
			Result(true);
		break;
		default:
			Error();
	}
else
	Error();