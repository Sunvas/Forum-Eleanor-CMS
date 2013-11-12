<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

class ForumModerate extends Forum
{
	public function DeletePost($ids,$data=array())
	{
		/*$data+=array(
			'trash'=>$this->GetOption('trash'),#Трешевый форум
			'language'=>$this->Core->language,
			'makenewtrash'=>false,#Указывает, что при перемещении в корзину, нужно создать новую тему, даже если такая тема уже существует
		);
		$in=Eleanor::$Db->In($ids);
		$repf=$rtids=array();#Repair forums & topics
		if($data['trash'])
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`language` FROM `'.$this->config['fl'].'` WHERE `id`='.$data['trash'].' AND `language`'.($data['language'] ? 'IN(\'\',\''.$data['language'].'\')' : '=\'\'').' LIMIT 1');
			if(!$trash=$R->fetch_assoc())
				throw new EE('NO_FORUM_TRASH',EE::INFO);

			$repf=array($trash['id']=>array($trash['language']));

			$fltp=$ttr=$need=$newtr=array();#Forum-language-topic-post & to-trash % need new trash topic & new trash topic
			$R=Eleanor::$Db->Query('SELECT `p`.`id`,`p`.`f`,`p`.`t`,`p`.`status`,`t`.`language`,`t`.`status` `tstatus`,'.($data['makenewtrash'] ? '0 `trid`,0 `trstatus`' : '`tr`.`id` `trid`,`tr`.`status` `trstatus`').' FROM `'.$this->config['fp'].'` `p` INNER JOIN `'.$this->config['ft'].'` `t` ON `t`.`id`=`p`.`t`'.($data['makenewtrash'] ? '' : ' LEFT JOIN `'.$this->config['ft'].'` `tr` ON `t`.`trash`=`tr`.`id`').' WHERE `p`.`id`'.$in.' ORDER BY `p`.`sortdate` ASC');
			while($a=$R->fetch_assoc())
			{
				$repf[$a['f']][]=$a['language'];
				$rtids[]=$a['t'];

				$fltp[$a['f']][$a['language']][$a['t']][]=array($a['id'],$a['status']);
				if($a['trid'])
					$ttr[$a['t']]=array($a['trid'],$a['trstatus']);
				else
					$need[]=$a['t'];
			}

			Eleanor::$Db->Transaction();
			if($need)
			{
				$R=Eleanor::$Db->Query('SELECT * FROM `'.$this->config['ft'].'` WHERE `id`'.Eleanor::$Db->In($need));
				while($a=$R->fetch_assoc())
				{
					$a['views']=$a['posts']=$a['moved_to']=0;
					$a['trash']=$a['id'];
					$a['f']=$trash['id'];
					$a['language']=$trash['language'];
					unset($a['id'],$a['url']);
					$rtids[]=$ttr[$a['trash']]=array(Eleanor::$Db->Insert($this->config['ft'],$a),$a['status']);
					$newtr[$ttr[$a['trash']][0]]=true;
					Eleanor::$Db->Update($this->config['ft'],array('trash'=>$ttr[$a['trash']][0]),'`id`='.$a['trash'].' LIMIT 1');
				}
			}
			foreach($fltp as $f=>&$lt)
				foreach($lt as $l=>&$tp)
				{
					$fp=$fsp=$fmp=$fmsp=0;
					foreach($tp as $tid=>&$dposts)
					{
						$p=$sp=0;
						$in=array();

						$first=false;
						foreach($dposts as $k=>&$v)
						{
							if(in_array($v[1],array(1,-2)))
								$p++;
							elseif(in_array($v[1],array(-1,-3)))
								$sp++;
							if($first===false)
							{
								if(isset($newtr[$ttr[$tid][0]]))
									if($p+$sp==0 and $ttr[$tid][1]!=0)
										$ttr[$tid][1]=0;
									elseif($ttr[$tid][1]==-1 and $p>0)
										$ttr[$tid][1]=1;
									elseif($ttr[$tid][1]==1 and $sp>0)
										$ttr[$tid][1]=-1;

								if($p>0)
									$first=1;
								elseif($sp>0)
									$first=-1;
								else
									$first=0;
							}
							$in[]=$v[0];
						}
						$fp+=$p;
						$fsp+=$sp;

						$in=Eleanor::$Db->In($in);
						Eleanor::$Db->Update($this->config['fp'],array('f'=>$trash['id'],'language'=>$trash['language'],'!status'=>'(CASE `status` WHEN '.($ttr[$tid][1]==1 ? '-2 THEN 1 WHEN -3 THEN -1' : '1 THEN -2 WHEN -1 THEN -3').' ELSE `status` END)','t'=>$ttr[$tid][0]),'`id`'.$in);
						Eleanor::$Db->Update($this->config['fa'],array('f'=>$trash['id'],'language'=>$trash['language'],'t'=>$ttr[$tid][0]),'`p`'.$in);
						if($p+$sp>0)
							Eleanor::$Db->Update($this->config['ft'],array('!posts'=>'GREATEST(0,`posts`-'.$p.')','!queued_posts'=>'GREATEST(0,`queued_posts`-'.$sp.')'),'`id`='.$tid.' LIMIT 1');

						if(isset($newtr[$ttr[$tid][0]]))
							switch($first)
							{
								case 1:
									$p--;
									$fmp--;
								break;
								case -1:
									$sp--;
									$fmsp--;
								break;
							}

						if($p+$sp>0)
							Eleanor::$Db->Update($this->config['ft'],array('!posts'=>'`posts`+'.$p,'!queued_posts'=>'`queued_posts`+'.$sp),'`id`='.$ttr[$tid][0].' LIMIT 1');
					}
					if($fp+$fsp>0)
						Eleanor::$Db->Update($this->config['fl'],array('!posts'=>'GREATEST(0,`posts`-'.$fp.')','!queued_posts'=>'GREATEST(0,`queued_posts`-'.$fsp.')'),'`id`='.$f.' AND `language`=\''.$l.'\' LIMIT 1');

					if(isset($newtr[$ttr[$tid][0]]) and $ttr[$tid][1])
						if($ttr[$tid][1]==1)
							$add=array('!topics'=>'`topics`+1');
						else
							$add=array('!queued_topics'=>'`queued_topics`+1');
					else
						$add=array();
					Eleanor::$Db->Update($this->config['fl'],array('!posts'=>'`posts`+'.($fp+$fmp),'!queued_posts'=>'`queued_posts`+'.($fsp+$fmsp))+$add,'`id`='.$trash['id'].' AND `language`=\''.$trash['language'].'\' LIMIT 1');
				}
			Eleanor::$Db->Commit();
		}
		else
		{
			$R=Eleanor::$Db->Query('SELECT `p`.`id`,`p`.`f`,`p`.`language`,`p`.`t`,`p`.`status`,`t`.`status` `tstatus`,`t`.`posts`,`t`.`queued_posts` FROM `'.$this->config['fp'].'` `p` INNER JOIN `'.$this->config['ft'].'` `t` ON `p`.`t`=`t`.`id` WHERE `p`.`id`'.$in);
			$fltp=$tposts=array();
			while($a=$R->fetch_assoc())
			{
				$fltp[$a['f']][$a['language']][$a['t']][]=array($a['id'],$a['status']);
				$rtids[]=$a['t'];
				$tposts[$a['t']]=array($a['queued_posts'],$a['posts']);
				$repf[$a['f']][]=$a['language'];
			}
			$this->DeleteAttach($in,'p');
			Eleanor::$Db->Transaction();
			Eleanor::$Db->Delete($this->config['fp'],'`id`'.$in);
			foreach($fltp as $f=>&$lt)
				foreach($lt as $l=>&$tp)
				{
					$fp=$fsp=0;
					foreach($tp as $tid=>&$dposts)
					{
						$p=$sp=0;
						$in=array();
						foreach($dposts as $k=>&$v)
						{
							if(in_array($v[1],array(1,-2)) and $tposts[$tid][1]>0)
							{
								$p+=1;
								$tposts[$tid][1]--;
							}
							elseif(in_array($v[1],array(-1,-3)) and $tposts[$tid][0]>0)
							{
								$sp+=1;
								$tposts[$tid][0]--;
							}
							$in[]=$v[0];
						}
						$fp+=$p;
						$fsp+=$sp;
						if($f+$fp>0)
							Eleanor::$Db->Update($this->config['ft'],array('!posts'=>'GREATEST(0,`posts`-'.$p.')','!queued_posts'=>'GREATEST(0,`queued_posts`-'.$sp.')'),'`id`='.$tid.' LIMIT 1');
					}
					if($fp+$fsp>0)
						Eleanor::$Db->Update($this->config['fl'],array('!posts'=>'GREATEST(0,`posts`-'.$fp.')','!queued_posts'=>'GREATEST(0,`queued_posts`-'.$fsp.')'),'`id`='.$f.' AND `language`=\''.$l.'\' LIMIT 1');
				}
			Eleanor::$Db->Commit();
		}
		if($rtids)
		{
			$rt='`id`'.Eleanor::$Db->In($rtids);
			Eleanor::$Db->Update($this->config['ft'],array('!last_mod'=>'NOW()'),$rt);
			$this->KillEmptyTopics($rt);
			$this->RepairTopics($rt);
		}
		if($repf)
			$this->RepairForums($repf,$rtids);*/
	}

	/*
		Функция починки темы. Позволяет скорректировать первое и последнее сообщение.
		Функция не пересчитывет посты в теме!
	*/
	protected function RepairTopics($int)
	{
		/*$tids=$fids=array();
		$R=Eleanor::$Db->Query('SELECT `id`,`f`,`status`,`language`,`created`,`author`,`author_id`,`pinned`,`lp_id` FROM `'.$this->config['ft'].'` WHERE '.$int.' AND `state` IN (\'open\',\'closed\')');
		while($a=$R->fetch_assoc())
		{
			#Первый пост
			$R=Eleanor::$Db->Query('SELECT `status`,`author`,`author_id`,`created` FROM `'.$this->config['fp'].'` WHERE `t`='.$a['id'].' ORDER BY `sortdate` ASC LIMIT 1');
			if($post=$R->fetch_assoc() and (($post['status']<0 xor $a['status']<0) or $a['author']!=$post['author'] or $a['author_id']!=$post['author_id'] or $a['created']!=$post['created']))
			{
				if($post['status']<0)
					$post['status']=-1;
				Eleanor::$Db->Update($this->config['ft'],$post,'`id`='.$a['id'].' LIMIT 1');
				if($post['status']!=$a['status'])
				{
					$upd=array();
					if($a['status']==1)
						$upd['!topics']='GREATEST(0,`topics`-1)';
					elseif($a['status']==-1)
						$upd['!queued_topics']='GREATEST(0,`queued_topics`-1)';

					if($post['status']==1)
						$upd['!topics']='`topics`+1';
					elseif($post['status']==-1)
						$upd['!queued_topics']='`queued_topics`+1';
					Eleanor::$Db->Update($this->config['fl'],$upd,'`id`='.$a['f'].' AND `language`=\''.$a['language'].'\' LIMIT 1');
					$a['status']=$post['status'];
					$fids[$a['f']][]=$a['language'];
					$tids[]=$a['id'];
				}
			}

			#Последние пост
			$R=Eleanor::$Db->Query('SELECT `id`,`author`,`author_id`,`created` FROM `'.$this->config['fp'].'` WHERE `t`='.$a['id'].' AND `status`='.($a['status']==1 ? 1 : -2).' ORDER BY `sortdate` DESC LIMIT 1');
			if($post=$R->fetch_assoc() and $a['lp_id']!=$post['id'])
			{
				Eleanor::$Db->Update($this->config['ft'],array('lp_date'=>$post['created'],'lp_id'=>$post['id'],'lp_author'=>$post['author'],'lp_author_id'=>$post['author_id'],'!sortdate'=>'GREATEST(`lp_date`,`pinned`)'),'`id`='.$a['id'].' LIMIT 1');
				$fids[$a['f']][]=$a['language'];
				$tids[]=$a['id'];
			}
		}
		if($fids)
			$this->RepairForums($fids,$tids);*/
	}

	/**
	 * Восстановление форумных lp_* полей
	 * @param array $fids ИД форумов, формат: 'ID'=>array('lang1','lang2'..)
	 * @param int|array|false $tids ИД тем. Ускорение запроса: обрабатываются только те форумы, lp_id которых совпадет с перечисленными темами.
	 */
	public function RepairForums(array$fids,$tids=false)
	{
		$config = $this->Forum->config;
		$R=Eleanor::$Db->Query('SELECT `id`,`language` FROM `'. $config['fl'].'` WHERE `id`'.Eleanor::$Db->In(array_keys($fids)).($tids ? ' AND `lp_id`'.Eleanor::$Db->In($tids) : ''));
		while($a=$R->fetch_assoc())
			if(in_array($a['language'],$fids[ $a['id'] ]))
			{
				$R2=Eleanor::$Db->Query('SELECT `id` `lp_id`,`title` `lp_title`,`lp_date`,`lp_author`,`lp_author_id` FROM `'. $config['ft'].'` WHERE `f`=\''.$a['id'].'\' AND `language`=\''.$a['language'].'\' AND `status`=1 AND `state` IN (\'open\',\'closed\') ORDER BY `lp_date` DESC LIMIT 1');
				if(!$upd=$R2->fetch_assoc())
					$upd=array(
						'lp_date'=>'0000-00-00 00:00:00',
						'lp_id'=>0,
						'lp_title'=>'',
						'lp_author'=>'',
						'lp_author_id'=>0,
					);
				Eleanor::$Db->Update($config['fl'],$upd,'`id`='.$a['id'].' AND `language`=\''.$a['language'].'\' LIMIT 1');
			}
	}

	/*
		Внутренее удаление опустевших тем
	*/
	protected function KillEmptyTopics($int)
	{
		/*$delt=$updft=$updfqt=array();
		$R=Eleanor::$Db->Query('SELECT `t`.`id`,`t`.`f`,`t`.`language`,`t`.`status` FROM `'.$this->config['ft'].'` `t` LEFT JOIN `'.$this->config['fp'].'` `p` ON `p`.`t`,`t`.`id` WHERE `t`.'.$int.' AND `t`.`state` IN (\'open\',\'closed\') AND `p`.`t` IS NULL');
		while($a=$R->fetch_assoc())
		{
			if($a['status']>0)
				$updft[$a['f']][$a['language']]=isset($updft[$a['f']][$a['language']]) ? $updft[$a['f']][$a['language']]+1 : 1;
			elseif($a['status']<0)
				$updfqt[$a['f']][$a['language']]=isset($updfqt[$a['f']][$a['language']]) ? $updfqt[$a['f']][$a['language']]+1 : 1;
			$delt[]=$a['id'];
		}
		if($delt)
		{
			$in=Eleanor::$Db->In($delt);
			Eleanor::$Db->Delete($this->config['ts'],'`t`'.$in);
			Eleanor::$Db->Delete($this->config['ft'],'`id`'.$in);
			Eleanor::$Db->Delete($this->config['ft'],'`moved_to`'.$in);
			foreach($updft as $f=>&$langs)
				foreach($langs as $lang=>&$cnt)
				{
					if(isset($updfqt[$f][$lang]))
					{
						$inact=array('!queued_topics'=>'GREATEST(0,`queued_topics`-'.$updfqt[$f][$lang].')');
						unset($updfqt[$f][$lang]);
					}
					else
						$inact=array();
					Eleanor::$Db->Update($this->config['fl'],array('!topics'=>'GREATEST(0,`topics`-'.$cnt.')')+$inact,'`id`='.$f.' AND `language`=\''.$lang.'\' LIMIT 1');
				}

			foreach($updfqt as $f=>&$langs)
				foreach($langs as $lang=>&$cnt)
					Eleanor::$Db->Update($this->config['fl'],array('!queued_topics'=>'GREATEST(0,`queued_topics`-'.$cnt.')'),'`id`='.$f.' AND `language`=\''.$lang.'\' LIMIT 1');
		}*/
	}

	/*
		$ids - ИДы постов
		$to - ИД темы, куда переместить
	*/
	public function MovePost($ids,$to)
	{
		/*$in=Eleanor::$Db->In($ids);
		$inp='`id`'.$in;

		$R=Eleanor::$Db->Query('SELECT `id`,`f`,`language`,`status` FROM `'.$this->config['ft'].'` WHERE `id`='.$to.' LIMIT 1');
		if(!$topic=$R->fetch_assoc())
			throw new EE('NO_TOPIC',EE::INFO);

		$uforums=$utopics=array();
		#Узнаем количество переносимых постов из форума
		$R=Eleanor::$Db->Query('SELECT `f`,`language`,`status`,COUNT(`status`) `cnt` FROM `'.$this->config['fp'].'` WHERE '.$inp.' AND `status`!=0 AND (`f`!='.$topic['f'].' OR `language`!=\''.$topic['language'].'\') GROUP BY `f`,`language`');
		while($a=$R->fetch_assoc())
			$uforums[$a['f']][$a['language']]=array(in_array($a['status'],array(-2,1)) ? 1 : -1,$a['cnt']);

		#Узнаем количество переносимых постов из тем
		$R=Eleanor::$Db->Query('SELECT `t`,`status`,COUNT(`status`) `cnt` FROM `'.$this->config['fp'].'` WHERE '.$inp.' AND `status`!=0 AND `t`!='.$topic['id'].' GROUP BY `t`');
		while($a=$R->fetch_assoc())
			$utopics[$a['t']]=array(in_array($a['status'],array(-2,1)) ? 1 : -1,$a['cnt']);

		Eleanor::$Db->Transaction();
		Eleanor::$Db->Update($this->config['fp'],array('f'=>$topic['f'],'language'=>$topic['language'],'t'=>$topic['id']),$inp);
		Eleanor::$Db->Update($this->config['fa'],array('f'=>$topic['f'],'language'=>$topic['language'],'t'=>$topic['id']),'`p`'.$in);

		#Обновляем количество постов в форумах
		$p=$sp=0;
		foreach($uforums as $fid=>&$langs)
			foreach($langs as $lang=>&$data)
			{
				if($data[0]==1)
					$p+=$data[1];
				else
					$sp+=$data[1];#suspended
				Eleanor::$Db->Update($this->config['fl'],$data[0]==1 ? array('!posts'=>'GREATEST(0,`posts`-'.$data[1].')') : array('!queued_posts'=>'GREATEST(0,`queued_posts`-'.$data[1].')'),'`f`='.$fid.' AND `language`=\''.$lang.'\' LIMIT 1');
			}
		Eleanor::$Db->Update($this->config['fl'],array('!posts'=>'`posts`+'.$p,'!queued_posts'=>'`queued_posts`+'.$sp),'`f`='.$topic['f'].' AND `language`=\''.$topic['language'].'\' LIMIT 1');

		#Обновляем количество постов в темах
		$p=$sp=0;
		foreach($utopics as $tid=>&$data)
		{
			if($data[0]==1)
				$p+=$data[1];
			else
				$sp+=$data[1];
			Eleanor::$Db->Update($this->config['ft'],$data[0]==1 ? array('!posts'=>'GREATEST(0,`posts`-'.$data[1].')') : array('!queued_posts'=>'GREATEST(0,`queued_posts`-'.$data[1].')'),'`id`='.$tid.' LIMIT 1');
		}
		Eleanor::$Db->Update($this->config['ft'],array('!posts'=>'`posts`+'.$p,'!queued_posts'=>'`queued_posts`+'.$sp),'`id`='.$to.' LIMIT 1');
		Eleanor::$Db->Commit();

		if($utopics)
		{
			#Удаляем "пустые темы"
			$int='`id`'.Eleanor::$Db->In(array_keys($utopics));
			Eleanor::$Db->Update($this->config['ft'],array('!last_mod'=>'NOW()'),$int);
			$this->KillEmptyTopics($int);
			$this->RepairTopics($int);
		}*/
	}

	/*
		Перемещение тем
		$ids - ИДы тем
		$to - ИД форума,куда переместить
	*/
	public function MoveTopic($ids,$to,$data=array())
	{
		/*if(!$ids)
			return;
		$data+=array(
			'trash'=>$this->GetOption('trash'),#ИД корзины
			'makenewtrash'=>false,#Указывает, что при перемещении в корзину, нужно создать новую тему, даже если такая тема уже существует
			'moved'=>false,#Оставить старую ссылку на тему
			'language'=>$this->Core->language,#Язык форума, куда перемещаем темы
			'who_moved'=>'',#Автор перемещения
			'who_moved_id'=>0,
			'when_moved'=>date('Y-m-d H:i:s'),
		);
		$R=Eleanor::$Db->Query('SELECT `id`,`language`,`lp_id` FROM `'.$this->config['fl'].'` WHERE `id`='.$to.' AND `language`'.($data['language'] ? 'IN(\'\',\''.$data['language'].'\')' : '=\'\'').' LIMIT 1');
		if(!$dest=$R->fetch_assoc())
			throw new EE('NO_FORUM',EE::INFO);

		$btotr=$dest['id']==$data['trash'];

		$R=Eleanor::$Db->Query('SELECT `id`,`f`,`status`,`language`,`state`,`trash`,`posts`,`queued_posts` FROM `'.$this->config['ft'].'` WHERE `id`'.Eleanor::$Db->In($ids).' AND `f`!='.$dest['id']);
		$restore=$fids=$ids=$uforums=$totr=array();
		while($a=$R->fetch_assoc())
		{
			$fids[$a['f']][]=$a['language'];
			if($a['f']==$data['trash'])
				$restore[$a['id']]=$a['trash'];
			elseif($btotr and $a['trash'])
				$totr[$a['trash']][$a['id']]=array('status'=>$a['status'],'posts'=>$a['posts'],'queued_posts'=>$a['queued_posts'],'language'=>$a['language'],'f'=>$a['f']);
			$ids[]=$a['id'];

			if($a['status']!=0)
			{
				if(!isset($uforums[$a['f']][$a['language']]))
					$uforums[$a['f']][$a['language']]=array(0,0,0,0,0,0,0,0);#queued posts, posts, topics, queued topics
				$uforums[$a['f']][$a['language']][0]+=$a['queued_posts'];
				$uforums[$a['f']][$a['language']][1]+=$a['posts'];
				if($a['status']==1)
					$uforums[$a['f']][$a['language']][2]+=1;
				else
					$uforums[$a['f']][$a['language']][3]+=1;
			}
		}

		Eleanor::$Db->Transaction();
		if($restore)
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`f`,`language` FROM `'.$this->config['ft'].'` WHERE `id`'.Eleanor::$Db->In($restore));
			while($a=$R->fetch_assoc())
			{
				$tids=array_keys($restore,$a['id']);
				if($a['f']==$dest['id'] and $dest['language']==$a['language'])
				{
					$in=Eleanor::$Db->In($tids);

					$R=Eleanor::$Db->Query('SELECT SUM(`posts`),SUM(`queued_posts`) FROM `'.$this->config['ft'].'` WHERE `id`'.$in);
					list($p,$sp)=$R->fetch_row();

					Eleanor::$Db->Update($this->config['fp'],array('f'=>$a['f'],'language'=>$a['language'],'t'=>$a['id']),'`t`'.$in);
					Eleanor::$Db->Update($this->config['fa'],array('f'=>$a['f'],'language'=>$a['language'],'t'=>$a['id']),'`t`'.$in);
					Eleanor::$Db->Delete($this->config['ft'],'`id`'.$in);
					Eleanor::$Db->Delete($this->config['ft'],'`moved_to`'.$in);

					Eleanor::$Db->Update($this->config['ft'],array('trash'=>0,'!posts'=>'`posts`+'.$p,'!queued_posts'=>'`queued_posts`+'.$sp),'`id`='.$a['id'].' LIMIT 1');
				}
				else
					$ids=array_merge($ids,$tids);
			}
		}

		if($btotr)
		{
			$data['moved']=false;
			foreach($totr as $k=>&$v)
			{
				if($data['makenewtrash'])
				{
					$ids=array_merge($ids,array_keys($v));
					continue;
				}
				$R=Eleanor::$Db->Query('SELECT `id` FROM `'.$this->config['ft'].'` WHERE `id`='.$k.' AND `f`='.$dest['id'].' AND `language`=\''.$dest['language'].'\' LIMIT 1');
				if($R->num_rows()>0)
				{
					$p=$sp=0;
					foreach($v as &$topic)
					{
						$p+=$topic['posts'];
						$sp+=$topic['queued_posts'];
						#Первое сообщение текущие темы - становится сообщением тоже.
						if($topic['status']==1)
						{
							$p++;
							$uforums[$topic['f']][$topic['language']][5]++;
							$uforums[$topic['f']][$topic['language']][6]--;
						}
						elseif($topic['status']==-1)
						{
							$sp++;
							$uforums[$topic['f']][$topic['language']][4]++;
							$uforums[$topic['f']][$topic['language']][7]--;
						}
					}
					$in=Eleanor::$Db->In(array_keys($v));
					Eleanor::$Db->Update($this->config['ft'],array('!posts'=>'`posts`+'.$p,'!queued_posts'=>$sp),'`id`='.$k.' LIMIT 1');
					Eleanor::$Db->Update($this->config['fp'],array('f'=>$dest['id'],'language'=>$dest['language'],'t'=>$k),'`t`'.$in);
					Eleanor::$Db->Update($this->config['fa'],array('f'=>$dest['id'],'language'=>$dest['language'],'t'=>$k),'`t`'.$in);
					Eleanor::$Db->Delete($this->config['ts'],'`t`'.$in);
					Eleanor::$Db->Delete($this->config['ft'],'`id`'.$in);
					Eleanor::$Db->Delete($this->config['ft'],'`moved_to`'.$in);
				}
				else
					$ids=array_merge($ids,array_keys($v));
			}
		}
		if($ids)
		{
			$in=Eleanor::$Db->In($ids);
			Eleanor::$Db->Delete($this->config['ft'],'`moved_to`'.$in.' AND `f`='.$dest['id'].' AND `language`=\''.$dest['language'].'\'');
			if($data['moved'])
			{
				$movedt=array();
				$R=Eleanor::$Db->Query('SELECT * FROM `'.$this->config['ft'].'` WHERE `id`'.$in);
				while($a=$R->fetch_assoc())
				{
					$a['moved_to']=$a['id'];
					$a['state']='moved';
					$a['who_moved']=$data['who_moved'];
					$a['who_moved_id']=$data['who_moved_id'];
					$a['when_moved']=$data['when_moved'];
					$a['sortdate']=$a['lp_date'];
					unset($a['id'],$a['url'],$a['pinned']);
					$movedt[]=$a;
				}
				if($movedt)
					Eleanor::$Db->Insert($this->config['ft'],$movedt);
			}
			Eleanor::$Db->Update($this->config['ft'],array('f'=>$dest['id'],'language'=>$dest['language']),'`id`'.$in);
			Eleanor::$Db->Update($this->config['fp'],array('f'=>$dest['id'],'language'=>$dest['language']),'`t`'.$in);
			Eleanor::$Db->Update($this->config['fa'],array('f'=>$dest['id'],'language'=>$dest['language']),'`t`'.$in);
		}

		$p=$sp=$t=$st=0;
		foreach($uforums as $fid=>&$langs)
			foreach($langs as $l=>&$dat)
			{
				$sp+=$dat[0]+$dat[4];#suspended
				$p+=$dat[1]+$dat[5];
				$t+=$dat[2]+$dat[6];
				$st+=$dat[3]+$dat[7];#suspended
				Eleanor::$Db->Update($this->config['fl'],array('!posts'=>'GREATEST(0,`posts`-'.$dat[1].')','!queued_posts'=>'GREATEST(0,`queued_posts`-'.$dat[0].')','!topics'=>'GREATEST(0,`topics`-'.$dat[2].')','!queued_topics'=>'GREATEST(0,`queued_topics`-'.$dat[3].')'),'`id`='.$fid.' AND `language`=\''.$l.'\' LIMIT 1');
			}
		Eleanor::$Db->Update($this->config['fl'],array('!posts'=>'`posts`+'.$p,'!queued_posts'=>'`queued_posts`+'.$sp,'!topics'=>'`topics`+'.$t,'!queued_topics'=>'`queued_topics`+'.$st),'`id`='.$dest['id'].' AND `language`=\''.$dest['language'].'\' LIMIT 1');
		Eleanor::$Db->Commit();

		if($fids)
		{
			$fids[$dest['id']][]=$dest['language'];
			$ids[]=$dest['lp_id'];
			$this->RepairForums($fids,$ids);
		}*/
	}

	/*
		Удаление тем
		$ids - ИДы тем.
	*/
	public function DeleteTopic($ids,$data=array())
	{
		/*$data+=array(
			'trash'=>$this->GetOption('trash'),#ID форума
			'deltrash'=>false,#Удалить тему, если она уже в корзине
			'language'=>$this->Core->language,#Язык форума, куда перемещаем темы
		);
		$R=Eleanor::$Db->Query('SELECT `id`,`f`,`status`,`language`,`state`,`posts`,`queued_posts` FROM `'.$this->config['ft'].'` WHERE `id`'.Eleanor::$Db->In($ids));
		$ids=$del=$uforums=array();
		while($a=$R->fetch_assoc())
		{
			$act=in_array($a['state'],array('open','closed'));
			if($data['trash'] and $a['f']!=$data['trash'])
				$ids[]=$a['id'];
			else
			{
				if(!$data['deltrash'] and $a['f']==$data['trash'])
					continue;
				$del[]=$a['id'];
				if($a['status']!=0 and $act)
				{
					if(!isset($uforums[$a['f']][$a['language']]))
						$uforums[$a['f']][$a['language']]=array(0,0,0,0);#queued posts, posts, topics, queued topics
					$uforums[$a['f']][$a['language']][0]+=$a['queued_posts'];
					$uforums[$a['f']][$a['language']][1]+=$a['posts'];
					if($a['status']==1)
						$uforums[$a['f']][$a['language']][2]++;
					elseif($a['status']==-1)
						$uforums[$a['f']][$a['language']][3]++;
				}
			}
		}
		if($data['trash'])
			$this->MoveTopic($ids,$data['trash'],$data);
		if($del)
		{
			$in=Eleanor::$Db->In($del);
			$this->DeleteAttach($in,'t');
			Eleanor::$Db->Transaction();
			Eleanor::$Db->Delete($this->config['ts'],'`t`'.$in);
			Eleanor::$Db->Delete($this->config['fp'],'`t`'.$in);
			Eleanor::$Db->Delete($this->config['ft'],'`id`'.$in);
			Eleanor::$Db->Delete($this->config['ft'],'`moved_to`'.$in);
			$fids=array();
			foreach($uforums as $fid=>&$langs)
			{
				foreach($langs as $lang=>&$dat)
					Eleanor::$Db->Update($this->config['fl'],array('!posts'=>'GREATEST(0,`posts`-'.$dat[1].')','!queued_posts'=>'GREATEST(0,`queued_posts`-'.$dat[0].')','!topics'=>'GREATEST(0,`topics`-'.$dat[2].')','!queued_topics'=>'GREATEST(0,`queued_topics`-'.$dat[3].')'),'`id`='.$fid.' AND `language`=\''.$lang.'\' LIMIT 1');
				$fids[$fid]=array_keys($langs);
			}
			Eleanor::$Db->Commit();
			$this->RepairForums($fids,$ids);
		}*/
	}

	public function DeleteAttach($ids,$t='a')
	{
		/*if(!$ids)
			return;
		$in=is_string($ids) ? Eleanor::$Db->In($ids) : $ids;
		$delp=true;
		switch($t)
		{
			case'f':
				$in='`f`'.$in;
			break;
			case'p':
				$in='`p`'.$in;
			break;
			case't':
				$in='`t`'.$in;
			break;
			default:
				$in='`id`'.$in;
				$delp=false;
		}
		if($delp)
		{
			$R=Eleanor::$Db->Query('SELECT DISTINCT `p` FROM `'.$this->config['fa'].'` WHERE '.$in);
			while($a=$R->fetch_assoc())
				Files::Delete(Eleanor::$root.Eleanor::$uploads.DIRECTORY_SEPARATOR.$this->config['n'].'/p'.$a['p']);
		}
		else
		{
			$root=Eleanor::$root.Eleanor::$uploads.DIRECTORY_SEPARATOR.$this->config['n'];
			$R=Eleanor::$Db->Query('SELECT `p`,`file`,`preview` FROM `'.$this->config['fa'].'` WHERE '.$in);
			while($a=$R->fetch_assoc())
			{
				if($a['file'])
					Files::Delete($root.'/p'.$a['p'].DIRECTORY_SEPARATOR.$a['file']);
				if($a['preview'])
					Files::Delete($root.'/p'.$a['p'].DIRECTORY_SEPARATOR.$a['preview']);
			}
		}
		Eleanor::$Db->Delete($this->config['fa'],$in);*/
	}
#####

	/*
		Обновления темы
	*/
	public function UpdateTopic($ids,$data)
	{
		#ToDo! Необходимо учесть возможность смены автора, статуса темы
	}

	/*
		Обновление сообщения и для перемещения тоже
	*/
	public function UpdatePost($ids,$data)
	{
		#ToDo! Необходимо учесть возможность смены автора, статуса сообщения
	}

	/*
		Слияние двух и более тем
		Первый ИД, передаваемый в $ids - ИД темы, в которую сольются все остальные
	*/
	public function MergeTopics(array $ids,$data=array())
	{
		$data+=array(
			'per_attach'=>10000,#Число аттачей, перемещенных за раз
			'movesubs'=>false,#Переместить подписки на темы. Может быть массивом UID=>true (перемещать или нет подписки)
		);
		#ToDo! удалить все подписки на прошлые темы
	}

	/*
		Слияние двух и более сообщений
	*/
	public function MergePosts(array$ids,$data=array())
	{
		#ToDo!
	}

	/*
		Удаление репутации по ID
	*/
	public function DeleteReputation($ids)
	{

	}
}