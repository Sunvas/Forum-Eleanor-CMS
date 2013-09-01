<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

class ForumService extends Forum
{
	/**
	 * Синхронизация групп системы с группами форума.
	 * @return array Ключи:
	 * int done Количество синхронизованных групп
	 * int total Количество групп, нуждающихся в синхронизации
	 * Пока эти числа одинаковы, но в случае, если синхронизацию нужно будет делать для 100 и более групп (что маловероятно, но вполне возможно), придется вызывать метод несколько раз.
	 */
	public function SyncGroups()
	{
		$toins=$todel=array();
		$config = $this->Forum->config;
		$R=Eleanor::$Db->Query('SELECT `g`.`id` FROM `'.P.'groups` `g` LEFT JOIN `'. $config['fg'].'` `f` USING (`id`) WHERE `f`.`id` IS NULL');
		while($t=$R->fetch_row())
			$toins[]=$t[0];

		$R=Eleanor::$Db->Query('SELECT `f`.`id` FROM `'. $config['fg'].'` `f` LEFT JOIN `'.P.'groups` `g` USING (`id`) WHERE `g`.`id` IS NULL');
		while($t=$R->fetch_row())
			$todel[]=$t[0];

		if($toins)
			Eleanor::$Db->Insert($config['fg'],array('id'=>$toins));
		if($todel)
			Eleanor::$Db->Delete($config['fg'],'`id`'.Eleanor::$Db->In($todel));

		#Не думаю, что есть смысл считать сколько всего сделано, но пусть будет
		$r=array(
			'done'=>0,#Сделано
			'total'=>0,#Всего
		);
		$r['done']=$r['total']=count($todel)+count($toins);
		return$r;
	}

	/**
	 * Синхронизация пользователей системы с пользователями форума
	 * @param array $opts Настройки, ключи:
	 * int id ID пользователя, с которого нужно начать синхронизацию (этот пользователь будет прощен и взят следующий)
	 * string date дата с которой нужно начать синхронизацию
	 * int limit количество пользователей синхронизуемых за раз
	 * @return array Ключи:
	 * int id ID пользователя, на котором завершена синхронизация
	 * string date дата, на которой завершена синхронизация
	 * int done Количество синхронизованных пользователей
	 * int total Количество пользователей, нуждающихся в синхронизации
	 */
	public function SyncUsers(array$opts=array())
	{
		$opts+=array(
			'id'=>0,
			'date'=>date('Y-m-d H:i:s',strtotime('-1 MONTH')),
			'limit'=>100
		);
		$r=array(
			'id'=>0,
			'date'=>$opts['date'],
			'done'=>0,
			'total'=>0,
		);

		$where=$ids=$exists=array();
		if($opts['date'])
			$where[]='`date`>='.Eleanor::$Db->Escape($opts['date']);
		if($opts['id'])
			$where[]='`id`>'.(int)$opts['id'];
		$where=$where ? ' WHERE '.join(' AND ',$where) : '';

		$R=Eleanor::$Db->Query('SELECT COUNT(`'.($opts['id'] ? 'id' : 'date').'`) FROM `'.P.'users_updated`'.$where);
		list($r['total'])=$R->fetch_row();

		$R=Eleanor::$Db->Query('SELECT `id`,`date` FROM `'.P.'users_updated`'.$where.' ORDER BY `'.($opts['id'] ? 'id' : 'date').'` ASC LIMIT '.$opts['limit']);
		while($a=$R->fetch_assoc())
		{
			$ids[]=$r['id']=$a['id'];
			$r['date']=$a['date'];
			$r['done']++;
		}

		if($ids)
		{
			$R=Eleanor::$UsersDb->Query('SELECT `id` FROM `'.USERS_TABLE.'` WHERE `id`'.Eleanor::$UsersDb->In($ids));
			while($a=$R->fetch_assoc())
				$exists[]=$a['id'];

			$delete=array_diff($ids,$exists);
			if($delete)
				$this->DeleteUser($delete);

			$exists=array();
			$R=Eleanor::$Db->Query('SELECT `id` FROM `'.$this->Forum->config['fu'].'` WHERE `id`'.Eleanor::$Db->In($ids));
			while($a=$R->fetch_assoc())
				$exists[]=$a['id'];

			$add=array_diff($ids,$exists);
			foreach($add as &$v)
				$this->AddUser(array('id'=>$v));
		}

		return$r;
	}

	/**
	 * Добавление пользователя в БД форума
	 * @param array|int $user Данные пользователя. Обязательно наличие ключа ID в случае массива
	 */
	public function AddUser($user,$me=false)
	{
		if(!is_array($user))
			$user=array('id'=>$user);
		$config = $this->Forum->config;
		if($me)
		{
			$ar=(int)Eleanor::GetCookie($config['n'].'-ar');
			$ar=$ar>0 && time()-$ar>86400 * 365 ? date('Y-m-d H:i:s',$ar) : date('Y-m-d H:i:s');
			$t=time();
			Eleanor::SetCookie('ar',false);
		}
		else
			$ar=date('Y-m-d H:i:s');
		$user+=array(
			'restrict_post'=>false,
			'restrict_post_to'=>'0000-00-00 00:00:00',
			'allread'=>$ar,
			'hidden'=>false,
			'moderate'=>false,
		);
		Eleanor::$Db->Insert($config['fu'],$user);
		if($me)
		{
			#Перенос "читанности форума" и "читанности тем" из кук в базу
			$tr=(string)Eleanor::GetCookie($config['n'].'-tr');
			$fr=(string)Eleanor::GetCookie($config['n'].'-fr');
			$gt=$this->Core->GuestSign('t');

			$forums=$todb=$topics=array();
			$tr=$tr ? explode(',',$tr) : array();
			$fr=$fr ? explode(',',$fr) : array();

			foreach($tr as $v)
				if(strpos($v,'-')!==false)
				{
					$v=explode('-',$v,2);
					$v[0]=(int)$v[0];
					$v[1]=(int)$v[1];
					if($v[0]>0 and $v[1]>0)
						$topics[ $v[0] ]=$v[1];
				}

			foreach($fr as $v)
				if(strpos($v,'-')!==false)
				{
					$v=explode('-',$v,2);
					$v[0]=(int)$v[0];
					$v[1]=(int)$v[1];
					if($v[0]>0 and $v[1]>0 and isset($this->Forum->Forums->dump[ $v[0] ]) and $this->Core->CheckForumAccess( $v[0] ))
						$forums[ $v[0] ]=array('allread'=>$v[1],'topics'=>array());
				}

			if($topics)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`f`,`status` FROM `'. $config['ft'].'` WHERE `id`'.Eleanor::$Db->In(array_keys($topics)).' AND `status`IN(1,-1) LIMIT 1000');
				while($a=$R->fetch_assoc())
					if((isset($forums[ $a['f'] ]) and $topics[ $a['id'] ]>$forums[ $a['f'] ]['allread'] or !isset($forums[ $a['f'] ]) and $this->Core->CheckForumAccess($a['f'])) and ($a['status']==1 or $a['status']==-1 and in_array($a['id'],$gt)))
						$forums[ $a['f'] ]['topics'][ $a['id'] ]=$topics[ $a['id'] ];
			}

			if($forums)
			{
				foreach($forums as $k=>$v)
				{
					arsort($v['topics'],SORT_NUMERIC);
					$todb[]=array(
						'uid'=>$user['id'],
						'f'=>$k,
						'allread'=>$v['allread'],
						'topics'=>$v['topics'] ? serialize($v['topics']) : '',
					);
				}
				Eleanor::$Db->Insert($config['re'],$todb);
			}

			#Удаление кук
			Eleanor::SetCookie($config['n'].'-tr',false);
			Eleanor::SetCookie($config['n'].'-fr',false);
		}
		return$user;
	}

	/**
	 * Проверка существования пользователя в БД форума. Если пользователь не существует, он будет добавлен, если удален из системы - будет удален
	 * @param int|array $ids Идентификатор(ы) пользователя
	 */
	public function CheckUser($ids)
	{
		$exists=array();
		$R=Eleanor::$Db->Query('SELECT `id` FROM `'.$this->Forum->config['fu'].'` WHERE `id`'.Eleanor::$Db->In($ids));
		while($a=$R->fetch_assoc())
			$exists[]=$a['id'];
		$toins=array_diff((array)$ids,$exists);

		if($toins)
		{
			$exists=array();
			$R=Eleanor::$UsersDb->Query('SELECT `id` FROM `'.USERS_TABLE.'` WHERE `id`'.Eleanor::$Db->In($toins));
			while($a=$R->fetch_assoc())
			{
				$exists[]=$a['id'];
				$this->AddUser($a['id']);
			}

			$todel=array_diff($toins,$exists);
			if($todel)
				$this->DeleteUser($todel);
		}
	}

	/**
	 * Обновление пользователей
	 * @param array $data Массив данных для изменения
	 * @param int|array|FALSE Идентификаторы пользователей, которых нужно изменить
	 */
	public function UpdateUser(array$data,$ids=false)
	{
		if(!$ids)
		{
			if(!$this->Core->user)
				return
			$ids=$this->Core->user['id'];
		}
		Eleanor::$Db->Update($this->Forum->config['fu'],$data,'`id`'.Eleanor::$Db->In($ids));
	}

	/**
	 * Метод удаление пользователя
	 * @param array|int $ids Идентификатор удаляемого пользователя
	 */
	public function DeleteUser($ids)
	{
		$in=Eleanor::$Db->In($ids);
		$config = $this->Forum->config;
		Eleanor::$Db->Delete($config['re'],'`uid`'.$in);
		Eleanor::$Db->Delete($config['fs'],'`uid`'.$in);
		Eleanor::$Db->Delete($config['ts'],'`uid`'.$in);
		Eleanor::$Db->Delete($config['lp'],'`uid`'.$in);
		Eleanor::$Db->Delete($config['fu'],'`id`'.$in);
	}

	/**
	 * Удаление форума
	 * @param int|array $ids Идентификатор удалямого форума
	 * @param array $opts Опции удалени, массив с ключами:
	 * array langs Языковые версии форума, которые удаляются. Если false - удаляются ВСЕ языковые версии. Если же будут указаны все языковые версии, из языковой таблицы они будут удалены, а из основной - нет. Учитывайте это.
	 * int|FALSE trash Идентификатор форума, куда будут перемещены темы
	 * array tolang Названия языка, в который будут преобразованы темы в случае перемещения
	 * @return array Ключи:
	 * int done Количество удаленных (перемещенных) элементов
	 * int total Количество элементов, нуждающихся в удалении (перемещении)
	 */
	public function DeleteForum($ids,$opts=array())
	{
		$ids=(array)$ids;
		$r=array(
			'done'=>0,#Сделано
			'total'=>0,#Всего
		);

		$opts+=array(
			'langs'=>false,#Языки форума, которые удаляем
			'pt'=>1000,#Количество тем перемещаемых/удаляемых за раз
			'pp'=>1000,#Количество постов перемещаемых/удаляемых за раз
			'pa'=>1000,#Количество аттачей перемещаемых/удаляемых за раз
			'pts'=>1000,#Количество подписок на темы перемещаемых/удаляемых за раз
			'pfs'=>1000,#Количество подписок на форумы удаляемых за раз
			'plp'=>1000,#Количество последних постов удаляемых за раз
			'pre'=>1000,#Количество идентификаторов чтений удаляемых за раз
			'pfr'=>1000,#Количество репутаций, удаляемых за раз
			'trash'=>$this->Core->vars['trash'],#ИД форума, куда переместить топики
			'trashlangs'=>array(),#Массив "новых" языков для треша (для случая, когда все языки форума сливаем в один)
			'tolang'=>false,#Язык, в который удаляем
		);
		if(in_array($opts['trash'],$ids) and ($opts['langs']===false or $opts['tolang']===false or in_array($opts['tolang'],$opts['langs'])))
			throw new EE('MOVE_INTO_DELETE',EE::USER);

		$ps=array();
		$Forum = $this->Forum;
		$R=Eleanor::$Db->Query('SELECT `id`,`parents` FROM `'. $Forum->config['f'].'` INNER JOIN `'. $Forum->config['fl'].'` USING(`id`) WHERE `id`'.Eleanor::$Db->In($ids).($opts['langs']===false ? '' : ' AND `language`'.Eleanor::$Db->In($opts['langs'])));
		while($a=$R->fetch_assoc())
			$ps[ $a['id'] ]=$a['parents'];

		foreach($ps as $k=>$v)
		{
			$R=Eleanor::$Db->Query('SELECT `id` FROM `'. $Forum->config['f'].'` WHERE `parents` LIKE \''.$v.$k.',%\'');
			while($a=$R->fetch_assoc())
				$ids[]=$a['id'];
		}

		if(in_array($this->Core->vars['trash'],$ids))
			throw new EE('DELETING_TRASH',EE::USER);

		$lin=$in=Eleanor::$Db->In($ids);
		if($opts['langs']!==false)
		{
			$nin=array();
			$R=Eleanor::$Db->Query('SELECT `id` FROM `'. $Forum->config['fl'].'` WHERE `id`'.$in.' AND `language`'.Eleanor::$Db->In($opts['langs'],true));
			while($a=$R->fetch_assoc())
				$nin[]=$a['id'];
			if($nin)
				$in=Eleanor::$Db->In(array_diff($ids,$nin));
			$lin.=' AND `language`'.Eleanor::$Db->In($opts['langs']);
		}

		$samelang=$opts['tolang']===false;
		if($opts['trash'])
		{
			$newforum=!in_array($opts['trash'],$ids);
			if($newforum)
			{
				$langs=array();
				$R=Eleanor::$Db->Query('SELECT `language` FROM `'. $Forum->config['fl'].'` WHERE `id`='.$opts['trash']);
				while($a=$R->fetch_assoc())
					$langs[]=$a['language'];
			}

			if($opts['trashlangs'])
			{
				$langs=array_intersect($opts['trashlangs'],$langs);
				if(!$langs)
					throw new EE('NO_DEST_LANG',EE::USER);
			}

			if($samelang)
				$samelang='IF(`language`'.Eleanor::$Db->In($langs).',`language`,\''.(in_array($this->Core->language,$langs) ? $this->Core->language : reset($langs)).'\')';
			elseif($newforum and !in_array($opts['tolang'],$langs))
				throw new EE('NO_DEST_LANG',EE::USER);
		}

		$R=Eleanor::$Db->Query('SELECT SUM(`s`.`cnt`) FROM (
			(SELECT COUNT(`f`) `cnt` FROM `'. $Forum->config['ft'].'` WHERE `f`'.$lin.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'. $Forum->config['fp'].'` WHERE `f`'.$lin.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'. $Forum->config['fa'].'` WHERE `f`'.$lin.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'. $Forum->config['fs'].'` WHERE `f`'.$lin.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'. $Forum->config['lp'].'` WHERE `f`'.$lin.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'. $Forum->config['re'].'` WHERE `f`'.$in.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'. $Forum->config['fr'].'` WHERE `f`'.$in.')
		) `s`');
#(SELECT COUNT(`f`) `cnt` FROM `'.$this->Forum->config['ts'].'` WHERE `f`'.$lin.')UNION ALL
		list($r['total'])=$R->fetch_row();

		Eleanor::$Db->Transaction();
		if($opts['trash'])
		{
			if($samelang)
				$r['done']+=Eleanor::$Db->Update($Forum->config['fa'],array('f'=>$opts['trash'],'!language'=>$samelang),'`f`'.$lin.' LIMIT '.$opts['pa'])
					+Eleanor::$Db->Update($Forum->config['fp'],array('f'=>$opts['trash'],'!language'=>$samelang),'`f`'.$lin.' LIMIT '.$opts['pp'])
					+Eleanor::$Db->Update($Forum->config['ft'],array('f'=>$opts['trash'],'!language'=>$samelang),'`f`'.$lin.' LIMIT '.$opts['pt'])
					#+Eleanor::$Db->Update($this->Forum->config['ts'],array('f'=>$opts['trash'],'!language'=>$samelang),'`f`'.$lin.' LIMIT '.$opts['pts'])
					+Eleanor::$Db->Update($Forum->config['fr'],array('f'=>$opts['trash'],'!language'=>$samelang),'`f`'.$lin.' LIMIT '.$opts['pfr']);
			else
				$r['done']+=Eleanor::$Db->Update($Forum->config['fa'],array('f'=>$opts['trash'],'language'=>$opts['tolang']),'`f`'.$lin.' LIMIT '.$opts['pa'])
					+Eleanor::$Db->Update($Forum->config['fp'],array('f'=>$opts['trash'],'language'=>$opts['tolang']),'`f`'.$lin.' LIMIT '.$opts['pp'])
					+Eleanor::$Db->Update($Forum->config['ft'],array('f'=>$opts['trash'],'language'=>$opts['tolang']),'`f`'.$lin.' LIMIT '.$opts['pt'])
					#+Eleanor::$Db->Update($this->Forum->config['ts'],array('f'=>$opts['trash'],'language'=>$opts['tolang']),'`f`'.$lin.' LIMIT '.$opts['pts'])
					+Eleanor::$Db->Update($Forum->config['fr'],array('f'=>$opts['trash'],'language'=>$opts['tolang']),'`f`'.$lin.' LIMIT '.$opts['pfr']);
		}
		else
		{
			$R=Eleanor::$Db->Query('SELECT `p` FROM `'. $Forum->config['fa'].'` WHERE `f`'.$lin.' GROUP BY `p` LIMIT '.$opts['pa']);
			while($a=$R->fetch_assoc())
				Files::Delete(Eleanor::$root.Eleanor::$uploads.DIRECTORY_SEPARATOR. $Forum->config['n'].'/p'.$a['p']);
			$r['done']+=Eleanor::$Db->Delete($Forum->config['fa'],'`f`'.$lin.' LIMIT '.$opts['pa'])
				+Eleanor::$Db->Delete($Forum->config['fp'],'`f`'.$lin.' LIMIT '.$opts['pp'])
				+Eleanor::$Db->Delete($Forum->config['ft'],'`f`'.$lin.' LIMIT '.$opts['pt'])
				#+Eleanor::$Db->Delete($this->Forum->config['ts'],'`f`'.$lin.' LIMIT '.$opts['pts'])
				+Eleanor::$Db->Delete($Forum->config['fr'],'`f`'.$lin.' LIMIT '.$opts['pfr']);
		}
		$r['done']+=Eleanor::$Db->Delete($Forum->config['fs'],'`f`'.$lin.' LIMIT '.$opts['pfs'])
			+Eleanor::$Db->Delete($Forum->config['lp'],'`f`'.$lin.' LIMIT '.$opts['plp'])
			+Eleanor::$Db->Delete($Forum->config['re'],'`f`'.$in.' LIMIT '.$opts['pre']);
		Eleanor::$Db->Commit();

		if($r['done']>=$r['total'])
		{
			if($opts['trash'])
			{
				$upd=array();
				$R=Eleanor::$Db->Query('SELECT `language`,`topics`,`posts`,`queued_topics`,`queued_posts` FROM `'. $Forum->config['fl'].'` WHERE `id`'.$lin);
				while($a=$R->fetch_assoc())
				{
					$l=$a['language'];
					array_splice($a,0,1);
					foreach($a as $k=>&$v)
						if($samelang)
							$upd[$l][$k]=isset($upd[$l][$k]) ? $upd[$l][$k]+$v : $v;
						else
							$upd[$k][$l]=isset($upd[$k][$l]) ? $upd[$k][$l]+$v : $v;
				}
				if($samelang)
				{
					foreach($upd as $lang=>$up)
						if($lang=='' or !in_array($lang,$langs))
							Eleanor::$Db->Update($Forum->config['fl'],array('!topics'=>'`topics`+'.$up['topics'],'!posts'=>'`posts`+'.$up['posts'],'!queued_topics'=>'`queued_topics`+'.$up['queued_topics'],'!queued_posts'=>'`queued_posts`+'.$up['queued_posts']),'`id`='.$opts['trash']);
						else
							Eleanor::$Db->Update($Forum->config['fl'],array('!topics'=>'`topics`+'.$up['topics'],'!posts'=>'`posts`+'.$up['posts'],'!queued_topics'=>'`queued_topics`+'.$up['queued_topics'],'!queued_posts'=>'`queued_posts`+'.$up['queued_posts']),'`id`='.$opts['trash'].' AND `language`=\''.$lang.'\' LIMIT 1');
				}
				else
					Eleanor::$Db->Update($this->config['fl'],array('!topics'=>'`topics`+'.array_sum($upd['topics']),'!posts'=>'`posts`+'.array_sum($upd['posts']),'!queued_topics'=>'`queued_topics`+'.array_sum($upd['queued_topics']),'!queued_posts'=>'`queued_posts`+'.array_sum($upd['queued_posts'])),'`id`='.$opts['trash'].' AND `language`=\''.$opts['tolang'].'\' LIMIT 1');

				$this->Moderate->RepairForums(array($opts['trash']=>$samelang ? $langs : array($opts['tolang'])));
			}

			if($opts['langs']===false)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`prefixes` FROM `'. $Forum->config['f'].'` WHERE `id`'.$in);
				while($a=$R->fetch_assoc())
					if($a['prefixes'])
						Eleanor::$Db->Update($Forum->config['f'],array('!forums'=>'REPLACE(`forums`,\','.$a['id'].',\',\'\')'),'`id`'.Eleanor::$Db->In(explode(',,',trim($a['prefixes'],','))));
				Eleanor::$Db->Delete($Forum->config['f'],'`id`'.$in);
			}
			Eleanor::$Db->Delete($Forum->config['fl'],'`id`'.$lin);
		}

		return$r;
	}

	/**
	 * Пересчет репутации пользователю
	 * @param int|array $ids ID пользователей, у которых будет пересчитана репутация
	 */
	public function RecountReputation($ids)
	{
		$R=Eleanor::$Db->Query('SELECT `id`,`name` FROM `'.P.'users_site` WHERE `id`'.Eleanor::$Db->In($ids));
		$ids=array();
		while($a=$R->fetch_assoc())
		{
			$R=Eleanor::$Db->Query('SELECT SUM(`value`) FROM `'.$this->Forum->config['fr'].'` WHERE `to_id`='.$a['id']);
			list($total)=$R->fetch_row();
			$rep=array();
			if($total!==null)
			{
				$R2=Eleanor::$Db->Query('SELECT `p`.`f`,SUM(`r`.`value`) `sum` FROM `'.$this->Forum->config['fr'].'` `r` INNER JOIN `'.$this->Forum->config['fp'].'` `p` ON `p`.`id`=`r`.`p` WHERE `r`.`to_id`'.$a['id'].' GROUP BY `p`.`f`');
				while($a=$R2->fetch_assoc())
					$rep[$a['f']]=$a['sum'];
			}
			Eleanor::$Db->Update($this->Forum->config['fu'],array('rep'=>$total,'reputation'=>$rep ? serialize($rep) : ''),'`id`='.$a['id'].' LIMIT 1');
		}
		return$ids;
	}
}