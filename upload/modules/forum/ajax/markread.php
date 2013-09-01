<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/
defined('CMS')||die;
switch($ev)
{
	case'all-read':#Пометка всего прочитанным
		if($Forum->user)
		{
			Eleanor::$Db->Update($Forum->config['fu'],array('!allread'=>'NOW()'),'`id`='.$Forum->user['id'].' LIMIT 1');
			Eleanor::$Db->Delete($Forum->config['re'],'`uid`='.$Forum->user['id']);
		}
		else
		{
			Eleanor::SetCookie($Forum->config['n'].'-ar',time());
			Eleanor::SetCookie($Forum->config['n'].'-fr',false);
			Eleanor::SetCookie($Forum->config['n'].'-tr',false);
		}
		Result(true);
	break;
	case'forum-read':#Пометка конкретного форума прочитанным
		$f=isset($_POST['f']) ? (int)$_POST['f'] : 0;
		if($Forum->CheckForumAccess($f))
		{
			$Forum->Forums->MarkRead($f);
			Result(true);
		}
		else
			Error();
	break;
	case'topic-read':#Пометка конкретной темы прочитанной
		$t=isset($_POST['t']) ? (int)$_POST['t'] : 0;
		$R=Eleanor::$Db->Query('SELECT `id`,`author_id`,`f`,`status` FROM `'.$Forum->config['ft'].'` WHERE `id`='.$t.' LIMIT 1');
		if(!$topic=$R->fetch_assoc() or !$Forum->CheckTopicAccess($topic))
			return Error();
		if($topic['status']!=1)
		{
			$forum=$Forum->Forums->GetForum($topic['f']);
			#В этой переменной хранятся наши права, как модератора
			if($forum['moderators'])
				list(,$moder)=$Forum->Moderator->ByIds($forum['moderators'],array('chstatust','mchstatust'),$Forum->config['n'].'_moders_ta'.$forum['id'].$Forum->language);
			else
				$moder=false;

			if(!$Forum->user)
				$gt=$Forum->GuestSign('t');

			if(($topic['status']==0 or $Forum->user and $Forum->user['id']!=$topic['author_id'] or !$Forum->user and !in_array($t,$gt)) and (!$moder or !in_array(1,$moder['chstatust']) and !in_array(1,$moder['mchstatust'])))
				return Error();
		}
		$Forum->Topic->MarkRead($t,$topic['f']);
		Result(true);
	break;
	default:
		Error();
}