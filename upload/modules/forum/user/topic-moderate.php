<?php
/*
	Resale is forbidden!
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

/**
 * Модерирование конкретной темы
 * @param array $forum Дамп форума
 * @param array $topic Дамп темы
 * @param array $rights Права пользователя на форуме
 * @return null|string Если возвращается строка - её нужно вывести.
 */
function TopicModerate($forum,$topic,$rights)
{global$Eleanor;
	$Forum = $Eleanor->Forum;

	$do=isset($_POST['moderate']['do']) ? (string)$_POST['moderate']['do'] : '';
	if(strpos($do,'=')===false)
		$sdo=false;
	else
		list($do,$sdo)=explode('=',$do,2);

	#Temp
	return Eleanor::$Template->Message('Функции модерирования темы в разработке...','info');
	#[E] Temp

	/*switch($do)
	{
		case'status':
			if(($Eleanor->Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['chstatust'])) and in_array($sdo,array(-1,0,1)))
			{
				Eleanor::$Db->Transaction();
				$sdo=(int)$sdo;
				$upd=array();
				if($topic['status']!=$sdo)
					switch($sdo)
					{
						case 1:
							Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>1),'`tid`='.$topic['id'].' AND `status`=-2');
							Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>-1),'`tid`='.$topic['id'].' AND `status`=-3');

							$upd=$topic['status']==0 ? array('!queued_posts'=>'`queued_posts`+'.$topic['queued_posts']) : array('!queued_topics'=>'GREATEST(0,`queued_topics`-1)');
							$upd+=array('!topics'=>'`topics`+1','!posts'=>'`posts`+'.$topic['posts']);
							$topic['status']=1;
						break;
						case 0:
							if($topic['status']==1)
							{
								Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>-2),'`tid`='.$topic['id'].' AND `status`=1');
								Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>-3),'`tid`='.$topic['id'].'AND `status`=-1');
							}

							$upd=$topic['status']==1 ? array('!topics'=>'GREATEST(0,`topics`-1)','!posts'=>'GREATEST(0,`posts`-'.$topic['posts'].')') : array('!queued_topics'=>'GREATEST(0,`queued_topics`-1)');
							$upd+=array('!queued_posts'=>'GREATEST(0,`queued_posts`-'.$topic['queued_posts']);
							$topic['status']=0;
						break;
						case -1:
							if($topic['status']==1)
							{
								Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>-2),'`tid`='.$topic['id'].' AND `status`=1');
								Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>-3),'`tid`='.$topic['id'].' AND `status`=-1');
							}

							$upd=$topic['status']==0 ? array('!queued_posts'=>'`queued_posts`+'.$topic['queued_posts']) : array('!topics'=>'GREATEST(0,`topics`-1)','!posts'=>'GREATEST(0,`posts`-'.$topic['posts'].')');
							$upd+=array('!queued_topics'=>'`queued_topics`+1');
							$topic['status']=-1;
					}
				Eleanor::$Db->Update($Eleanor->mconfig['ft'],array('status'=>$sdo,'!last_mod'=>'NOW()'),'`id`='.$topic['id'].' LIMIT 1');
				Eleanor::$Db->Update($Eleanor->mconfig['fl'],$upd+$Eleanor->Forum->Post->NewLp($forum['id'],$forum['language']),'`id`='.$forum['id'].' AND `language`=\''.$forum['language'].'\' LIMIT 1');
				Eleanor::$Db->Commit();
			}
		break;
		case'open':
		case'close':
			if($Eleanor->Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['opcl']) or $topic['_ismy'] and in_array(1,$forum['_rights']['close']))
				Eleanor::$Db->Update($Eleanor->mconfig['ft'],array('state'=>$do=='open' ? 'open' : 'closed','!last_mod'=>'NOW()'),'`id`='.$topic['id'].' AND `state`IN(\'open\',\'closed\',\'\')');
		break;
		case'delete':
			if($Eleanor->Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['deletet']) or $topic['_ismy'] and in_array(1,$forum['_rights']['deletet']))
			{
				$Eleanor->Forum->Moderate->DeleteTopic($topic['id'],array('deltrash'=>true));
				Eleanor::$Db->Update($Eleanor->mconfig['fl'],$Eleanor->Forum->Post->NewLp($forum['id'],$forum['language']),'`id`='.$forum['id'].' AND `language`=\''.$forum['language'].'\' LIMIT 1');
				Go($Eleanor->Forum->Forums->GetUrl($forum['id']),$Eleanor->Url->delimiter);
				die;
			}
		break;
		case'move':
			if($Eleanor->Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['movet']))
			{
				$to=isset($_POST['tmoderate']['to']) ? (int)$_POST['tmoderate']['to'] : 0;
				$to=$Eleanor->Forum->Forums->GetCategory($to);
				if(!$to or $to['is_category'] or !$to['id']==$forum['id'])
					break;
				$rights=$Eleanor->Forum->ForumRights($to['id']);

				#Если нет - мы проверим это сами: если нет доступа к списку тем
				if(!in_array(1,$rights['access']))
					break;

				$me=Eleanor::$Login->GetUserValue(array('name','id'));
				$Eleanor->Forum->Moderate->MoveTopic(
					$topic['id'],
					$to['id'],
					array(
						'moved'=>isset($_POST['tmoderate']['moved']),
						'who_moved'=>$me['name'],
						'who_moved_id'=>$me['id'],
					)
				);
				$R=Eleanor::$Db->Query('SELECT `url` FROM `'.$Eleanor->mconfig['ft'].'` WHERE `id`='.$topic['id'].' LIMIT 1');
				if(!$a=$R->fetch_assoc())
					break;

				$tu=$Eleanor->Url->furl && $a['url'] ? $Eleanor->Forum->Forums->GetUrl($to['id'])+array('t'=>$a['url']) : array(array('t'=>$topic['id']));
				GoAway($tu+array('page'=>array('page'=>$topic['_page']>1 ? $topic['_page'] : false)));
				die;
			}
		break;
		case'unpin':
			$un=true;
		case'pin':
			if($Eleanor->Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['pin']))
			{
				if(isset($un))
				{
					$topic['_pin']=false;
					$topic['pinned']='0000-00-00 00:00:00';
				}
				else
				{
					$p=isset($_POST['_pin']) ? abs((int)$_POST['_pin']) : 0;
					if($p>0 && $p<1000)
					{
						$topic['pinned']=date('Y-m-d H:i:s',strtotime('+ '.$p.' DAY'));
						$topic['_pin']=true;
					}
					else
						break;
				}
				$Eleanor->Forum->Post->SaveTopic(array('id'=>$topic['id'],'pinned'=>$topic['pinned']));
			}
	}*/
}

/**
 * Модерирование постов в конкретной теме
 * @param array $forum Дамп форума
 * @param array $topic Дамп темы
 * @param array $rights Права на форуме
 * @param array $posts Идентификаторы постов темы
 * @return null|string Если возвращается строка - её нужно вывести.
 */
function PostsModerate($forum,$topic,$rights,array$posts)
{global$Eleanor;
	$Forum = $Eleanor->Forum;
	if($posts)
	{
		$R=Eleanor::$Db->Query('SELECT `id` FROM `'. $Forum->config['fp'].'` WHERE `t`='.$topic['id'].' AND `id`'.Eleanor::$Db->In($posts));
		$posts=array();
		while($a=$R->fetch_assoc())
			$posts[]=$a['id'];
	}

	if(!$posts)
		return;

	$do=isset($_POST['mm']['do']) ? (string)$_POST['mm']['do'] : '';
	if(strpos($do,'=')===false)
		$sdo=false;
	else
		list($do,$sdo)=explode('=',$do,2);

	#Temp
	return Eleanor::$Template->Message('Функции мультимодерирования постов в разработке...','info');
	#[E] Temp

	/*switch($do)
	{
		case'status':
			if(($Eleanor->Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['mchstatus'])) and in_array($sdo,array(-1,0,1)))
			{
				$in=Eleanor::$Db->In($posts);
				Eleanor::$Db->Transaction();
				$R=Eleanor::$Db->Query('SELECT `id`,`status` FROM `'.$Eleanor->mconfig['fp'].'` WHERE `tid`='.$topic['id'].' ORDER BY `sortdate` ASC LIMIT 1');
				list($fpid,$fpst)=$R->fetch_row();
				$fp=in_array($fpid,$posts);

				$ps=array(-1=>0,0,0);
				$R2=Eleanor::$Db->Query('SELECT `status`,COUNT(`status`) `cnt` FROM `'.$Eleanor->mconfig['fp'].'` WHERE `tid`='.$topic['id'].' AND `id`'.$in.' GROUP BY `status`');
				while($a=$R2->fetch_assoc())
				{
					if($a['status']==-3)
						$st=-1;
					elseif($a['status']==-2)
						$st=1;
					else
						$st=$a['status'];
					$ps[$st]+=$a['cnt'];
				}
				$updf=$updt=array();
				switch((int)$sdo)
				{
					case 1:
						if($ps[-1]>0)
						{
							$where='`tid`='.$topic['id'].' AND `id`'.$in.' AND `status`='.($topic['status']==1 ? -1 : -3);
							$R=Eleanor::$Db->Query('SELECT UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(MIN(`sortdate`)) FROM `'.$Eleanor->mconfig['fp'].'` WHERE '.$where);
							list($md)=$R->fetch_row();
							Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('!sortdate'=>'`sortdate`+INTERVAL '.$md.' SECOND','status'=>1),$where);
						}
						Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>1),'`tid`='.$topic['id'].' AND `id`'.$in);

						$plact=$ps[0]+$ps[-1];
						$minq=$ps[-1];
						$tplact=$tminq=0;

						$updt=array();
						if($fp and $topic['status']!=1)
						{
							Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>1),'`tid`='.$topic['id'].' AND `status`=-2');
							Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>-2),'`tid`='.$topic['id'].' AND `status`=-3');

							$tplact++;
							if($topic['status']==-1)
								$tminq++;
							if($fpst!=1 and $fpst!=-2)
								$plact=max($plact-1,0);
							$topic['status']=$updt['status']=1;
						}
						$updf=array('!topics'=>'`topics`+'.$tplact,'!posts'=>'`posts`+'.$plact,'!queued_posts'=>'GREATEST(0,`queued_posts`-'.$minq.')','!queued_topics'=>'GREATEST(0,`queued_topics`-'.$tminq.')');
						$updt+=array('!posts'=>'`posts`+'.$plact,'!queued_posts'=>'GREATEST(0,`queued_posts`-'.$minq.')');
					break;
					case 0:
						Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>0),'`tid`='.$topic['id'].' AND `id`'.$in);

						$mina=$ps[1];
						$minq=$ps[-1];
						$tmina=$tminq=0;

						$updt=array();
						if($fp and $topic['status']!=0)
						{
							Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>-2),'`tid`='.$topic['id'].' AND `status`=1');
							Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>-3),'`tid`='.$topic['id'].' AND `status`=-1');

							if($topic['status']==-1)
							{
								if($fpst==-1 or $fpst==-3)
									$minq=max($minq-1,0);
								$tminq++;
							}
							else
							{
								$mina=max($mina-1,0);
								$tmina++;
							}
							$topic['status']=$updt['status']=0;
						}

						$updf=array('!topics'=>'GREATEST(0,`topics`-'.$tmina.')','!posts'=>'GREATEST(0,`posts`-'.$mina.')','!queued_posts'=>'GREATEST(0,`queued_posts`-'.$minq.')','!queued_topics'=>'GREATEST(0,`queued_topics`-'.$tminq.')');
						$updt+=array('!posts'=>'GREATEST(0,`posts`-'.$mina.')','!queued_posts'=>'GREATEST(0,`queued_posts`-'.$minq.')');
					break;
					case -1:
						$mina=$ps[1];
						$plq=$ps[1]+$ps[0];
						$tmina=$tplq=0;

						$updt=array();
						if($fp)
						{
							if($topic['status']!=-1)
								$tplq++;
							if($fpst!=-1 and $fpst!=-3)
								$plq=max($plq-1,0);
							if($topic['status']==1)
							{
								Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>-2),'`tid`='.$topic['id'].' AND `status`=1');
								Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>-3),'`tid`='.$topic['id'].' AND `status`=-1');
								$tmina++;
								$mina=max($mina-1,0);
							}
							Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>-3),'`tid`='.$topic['id'].' AND `id`'.$in);
							Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>-2),'`id`='.$fpid.' LIMIT 1');
							$topic['status']=$updt['status']=-1;
						}
						else
							Eleanor::$Db->Update($Eleanor->mconfig['fp'],array('status'=>$topic['status']==1 ? -1 : -3),'`tid`='.$topic['id'].' AND `id`'.$in);
						$updf=array('!queued_topics'=>'`queued_topics`+'.$tplq,'!queued_posts'=>'`queued_posts`+'.$plq,'!posts'=>'GREATEST(0,`posts`-'.$mina.')','!topics'=>'GREATEST(0,`topics`-'.$tmina.')');
						$updt+=array('!queued_posts'=>'`queued_posts`+'.$plq,'!posts'=>'GREATEST(0,`posts`-'.$mina.')');
				}
				$R=Eleanor::$Db->Query('SELECT `id` `lp_id`,`created` `lp_date`,`author` `lp_author`,`author_id` `lp_author_id` FROM `'.$Eleanor->mconfig['fp'].'` WHERE `tid`=\''.$topic['id'].'\' AND `status`='.($topic['status']==1 ? 1 : -2).' ORDER BY `sortdate` DESC LIMIT 1');
				if($tlp=$R->fetch_assoc())
					$updt+=$tlp;
				Eleanor::$Db->Update($Eleanor->mconfig['ft'],$updt,'`id`='.$topic['id'].' LIMIT 1');
				Eleanor::$Db->Update($Eleanor->mconfig['fl'],$updf+$Eleanor->Forum->Post->NewLp($forum['id'],$forum['language']),'`id`='.$forum['id'].' AND `language`=\''.$forum['language'].'\' LIMIT 1');
				Eleanor::$Db->Commit();
			}
		break;
		case'delete':
			if($Eleanor->Forum->ugr['supermod'] or $forum['_moderator'] and in_array(1,$forum['_moderator']['delete']))
			{
				$Eleanor->Forum->Moderate->DeletePost($posts);
				if($topic['posts']+$topic['queued_posts']<count($posts))
				{
					Eleanor::$Db->Update($Eleanor->mconfig['fl'],$Eleanor->Forum->Post->NewLp($forum['id'],$forum['language']),'`id`='.$forum['id'].' AND `language`=\''.$forum['language'].'\' LIMIT 1');
					Go($Eleanor->Forum->Forums->GetUrl($forum['id']),$Eleanor->Url->delimiter);
					die;
				}
			}
	}*/
}