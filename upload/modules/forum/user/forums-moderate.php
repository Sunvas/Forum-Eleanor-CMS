<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

/**
 * Отдельный участок кода, отвечающий за мультимодерацию тем. Вынесен в отдельный файл с целью уменьшения мертвого кода.
 * Описание обязательных параметров POST запроса мультимодерации:
 *  mm
 *   t Массив с идентификаторами тем
 *   do Идентификатор действия
 * @param array $forum Форум из функции ShowForum
 * @param array $rights Права на форуме
 * @param array $topics Идентификаторы тем
 * @return null|string Если возвращается строка - её нужно вывести.
 */
function TopicsModerate(array$forum,$rights,array$topics)
{global$Eleanor;
	$Forum = $Eleanor->Forum;
	if($topics)
	{
		$R=Eleanor::$Db->Query('SELECT `id` FROM `'. $Forum->config['ft'].'` WHERE `f`='.$forum['id'].' AND `id`'.Eleanor::$Db->In($topics));
		$topics=array();
		while($a=$R->fetch_assoc())
			$topics[]=$a['id'];
	}

	if(!$topics)
		return;

	$do=isset($_POST['mm']['do']) ? (string)$_POST['mm']['do'] : '';
	if(strpos($do,'=')===false)
		$sdo=false;
	else
		list($do,$sdo)=explode('=',$do,2);

	#Temp
	return Eleanor::$Template->Message('Функции мультимодерирования тем в разработке...','info');
	#[E] Temp

	#Результаты переноса
	$status=array();
	//	Eleanor::$Template->queue[]='ForumModerate';
	switch($do)
	{
		#ToDo!
		/*case'status':
			if(($Eleanor->Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['mchstatust'])) and in_array($sdo,array(-1,0,1)))
			{
				$int=Eleanor::$Db->In($topics);
				Eleanor::$Db->Transaction();
				switch((int)$sdo)
				{
					case 1:
						Eleanor::$Db->Update($Eleanor->Forum->config['fp'],array('status'=>1),''t''.$int.' AND `status`=-2');
						Eleanor::$Db->Update($Eleanor->Forum->config['fp'],array('status'=>-1),''t''.$int.' AND `status`=-3');

						$R=Eleanor::$Db->Query('SELECT COUNT(`id`) `cnt`,SUM(`posts`) `p`,SUM(`queued_posts`) `qp` FROM `'.$Eleanor->Forum->config['ft'].'` WHERE `id`'.$int.' AND `status`=0 AND `state`IN(\'open\',\'closed\')');
						$zero=$R->fetch_assoc();

						$R2=Eleanor::$Db->Query('SELECT COUNT(`id`) `cnt`,SUM(`posts`) `p` FROM `'.$Eleanor->Forum->config['ft'].'` WHERE `id`'.$int.' AND `status`=-1 AND `state`IN(\'open\',\'closed\')');
						$wait=$R2->fetch_assoc();

						$plact=$plq=$tplact=$tminq=0;
						if($zero['cnt']>0)
						{
							$plact+=$zero['p'];
							$plq+=$zero['qp'];
							$tplact+=$zero['cnt'];
						}
						if($wait['cnt']>0)
						{
							$tplact+=$wait['cnt'];
							$tminq+=$wait['cnt'];
						}
						$upd=array('!topics'=>'`topics`+'.$tplact,'!posts'=>'`posts`+'.$plact,'!queued_posts'=>'`queued_posts`+'.$plq,'!queued_topics'=>'GREATEST(0,`queued_topics`-'.$tminq.')');
					break;
					case 0:
						Eleanor::$Db->Update($Eleanor->Forum->config['fp'],array('status'=>-2),''t''.$int.' AND `status`=1');
						Eleanor::$Db->Update($Eleanor->Forum->config['fp'],array('status'=>-3),''t''.$int.' AND `status`=-1');

						$R=Eleanor::$Db->Query('SELECT COUNT(`id`) `cnt`,SUM(`posts`) `p`,SUM(`queued_posts`) `qp` FROM `'.$Eleanor->Forum->config['ft'].'` WHERE `id`'.$int.' AND `status`=1 AND `state`IN(\'open\',\'closed\')');
						$act=$R->fetch_assoc();

						$R2=Eleanor::$Db->Query('SELECT COUNT(`id`) `cnt`,SUM(`queued_posts`) `qp` FROM `'.$Eleanor->Forum->config['ft'].'` WHERE `id`'.$int.' AND `status`=-1 AND `state`IN(\'open\',\'closed\')');
						$wait=$R2->fetch_assoc();

						$minact=$minq=$tminact=$tminq=0;
						if($act['cnt']>0)
						{
							$minact+=$act['p'];
							$minq+=$act['qp'];
							$tminact+=$act['cnt'];
						}
						if($wait['cnt']>0)
						{
							$minq+=$wait['qp'];
							$tminq+=$wait['cnt'];
						}
						$upd=array('!topics'=>'GREATEST(0,`topics`-'.$tminact.')','!posts'=>'GREATEST(0,`posts`-'.$minact.')','!queued_posts'=>'GREATEST(0,`queued_posts`-'.$minq.')','!queued_topics'=>'GREATEST(0,`queued_topics`-'.$tminq.')');
					break;
					case -1:
						Eleanor::$Db->Update($Eleanor->Forum->config['fp'],array('status'=>-2),''t''.$int.' AND `status`=1');
						Eleanor::$Db->Update($Eleanor->Forum->config['fp'],array('status'=>-3),''t''.$int.' AND `status`=-1');

						$R=Eleanor::$Db->Query('SELECT COUNT(`id`) `cnt`,SUM(`posts`) `p` FROM `'.$Eleanor->Forum->config['ft'].'` WHERE `id`'.$int.' AND `status`=1 AND `state`IN(\'open\',\'closed\')');
						$act=$R->fetch_assoc();

						$R2=Eleanor::$Db->Query('SELECT COUNT(`id`) `cnt`,SUM(`queued_posts`) `qp` FROM `'.$Eleanor->Forum->config['ft'].'` WHERE `id`'.$int.' AND `status`=0 AND `state`IN(\'open\',\'closed\')');
						$zero=$R2->fetch_assoc();

						$minact=$plq=$tminact=$tplq=0;
						if($act['cnt']>0)
						{
							$minact+=$act['p'];
							$tminact+=$act['cnt'];
							$tplq+=$act['cnt'];
						}
						if($zero['cnt']>0)
						{
							$plq+=$zero['qp'];
							$tplq+=$zero['cnt'];
						}
						$upd=array('!topics'=>'GREATEST(0,`topics`-'.$tminact.')','!posts'=>'GREATEST(0,`posts`-'.$minact.')','!queued_posts'=>'`queued_posts`+'.$plq,'!queued_topics'=>'`queued_topics`+'.$tplq);
				}
				Eleanor::$Db->Update($Eleanor->Forum->config['fl'],$upd+$Eleanor->Forum->Post->NewLp($forum['id'],$forum['language']),'`id`='.$forum['id'].' AND `language`=\''.$forum['language'].'\' LIMIT 1');
				Eleanor::$Db->Update($Eleanor->Forum->config['ft'],array('status'=>$sdo,'!last_mod'=>'NOW()'),'`id`'.$int);
				Eleanor::$Db->Commit();
			}
		break;
		case'open':
		case'close':
			if($Eleanor->Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['mopcl']))
				Eleanor::$Db->Update($Eleanor->Forum->config['ft'],array('state'=>$do=='open' ? 'open' : 'closed','!last_mod'=>'NOW()'),'`id`'.Eleanor::$Db->In($topics).' AND `state`IN(\'open\',\'closed\',\'\')');
		break;
		case'delete':
			if($Eleanor->Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['mdeletet']))
				$Eleanor->Forum->Moderate->DeleteTopic($topics,array('deltrash'=>true));
		break;
		case'move':
			if($Eleanor->Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['mmovet']))
				do
				{
					$to=isset($_POST['mm']['to']) ? (int)$_POST['mm']['to'] : 0;
					$to=$Eleanor->Forum->Forums->GetForum($to);
					if(!$to or $to['is_category'] or !$to['id']==$forum['id'])
						break;
					$rights=$Eleanor->Forum->ForumRights($to['id']);

					#Если нет - мы проверим это сами: если нет доступа к списку тем
					if(!in_array(1,$rights['access']))
						break;

					$me=Eleanor::$Login->GetUserValue(array('name','id'));
					$Eleanor->Forum->Moderate->MoveTopic(
						$topics,
						$to['id'],
						array(
							'moved'=>isset($_POST['mm']['moved']),
							'who_moved'=>$me['name'],
							'who_moved_id'=>$me['id'],
						)
					);
				}while(false);
		*/
	}
	return$status;
}