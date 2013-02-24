<?php
/*
	Copyright � Eleanor CMS
	URL: http://eleanor-cms.ru, http://eleanor-cms.com
	E-mail: support@eleanor-cms.ru
	Developing: Alexander Sunvas*
	Interface: Rumin Sergey
	=====
	*Pseudonym
*/

class ForumService extends Forum
{
	/**
	 * ������������� ����� ������� � �������� ������.
	 *
	 * @return array �����:
	 * int done ���������� ���������������� �����
	 * int total ���������� �����, ����������� � �������������
	 * ���� ��� ����� ���������, �� � ������, ���� ������������� ����� ����� ������ ��� 100 � ����� ����� (��� ������������, �� ������ ��������), �������� �������� ����� ��������� ���.
	 */
	public function SyncGroups()
	{
		$toins=$todel=array();
		$R=Eleanor::$Db->Query('SELECT `g`.`id` FROM `'.P.'groups` `g` LEFT JOIN `'.$this->Forum->config['fg'].'` `f` USING (`id`) WHERE `f`.`id` IS NULL');
		while($t=$R->fetch_row())
			$toins[]=$t[0];

		$R=Eleanor::$Db->Query('SELECT `f`.`id` FROM `'.$this->Forum->config['fg'].'` `f` LEFT JOIN `'.P.'groups` `g` USING (`id`) WHERE `g`.`id` IS NULL');
		while($t=$R->fetch_row())
			$todel[]=$t[0];

		if($toins)
			Eleanor::$Db->Insert($this->Forum->config['fg'],array('id'=>$toins));
		if($todel)
			Eleanor::$Db->Delete($this->Forum->config['fg'],'`id`'.Eleanor::$Db->In($todel));

		#�� �����, ��� ���� ����� ������� ������� ����� �������, �� ����� �����
		$r=array(
			'done'=>0,#�������
			'total'=>0,#�����
		);
		$r['done']=$r['total']=count($todel)+count($toins);
		return$r;
	}

	/**
	 * ������������� ������������� ������� � �������������� ������
	 *
	 * @param array $opts ���������, �����:
	 * int id ID ������������, � �������� ����� ������ ������������� (���� ������������ ����� ������ � ���� ���������)
	 * string date ���� � ������� ����� ������ �������������
	 * int limit ���������� ������������� �������������� �� ���
	 * @return array �����:
	 * int id ID ������������, �� ������� ��������� �������������
	 * string date ����, �� ������� ��������� �������������
	 * int done ���������� ���������������� �������������
	 * int total ���������� �������������, ����������� � �������������
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
	 * ���������� ������������ � �� ������
	 *
	 * @param array|int $user ������ ������������. ����������� ������� ����� ID � ������ �������
	 */
	public function AddUser($user)
	{
		if(!is_array($user))
			$user=array('id'=>$user);
		$user+=array(
			'restrict_post'=>false,
			'restrict_post_to'=>null,
			'allread'=>date('Y-m-d H:i:s'),
			'hidden'=>false,
			'moderate'=>false,
		);
		Eleanor::$Db->Insert($this->Forum->config['fu'],$user);
		return$user;
	}

	/**
	 * �������� ������������� ������������ � �� ������. ���� ������������ �� ����������, �� ����� ��������, ���� ������ �� ������� - ����� ������
	 *
	 * @param int|array $ids �������������(�) ������������
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
	 * ���������� �������������
	 *
	 * @param array $data ������ ������ ��� ���������
	 * @param int|array|FALSE �������������� �������������, ������� ����� ��������
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
	 * ����� �������� ������������
	 *
	 * @param array|int $ids ������������� ���������� ������������
	 */
	public function DeleteUser($ids)
	{
		$in=Eleanor::$Db->In($ids);
		Eleanor::$Db->Delete($this->Forum->config['re'],'`uid`'.$in);
		Eleanor::$Db->Delete($this->Forum->config['fs'],'`uid`'.$in);
		Eleanor::$Db->Delete($this->Forum->config['ts'],'`uid`'.$in);
		Eleanor::$Db->Delete($this->Forum->config['lp'],'`uid`'.$in);
		Eleanor::$Db->Delete($this->Forum->config['fu'],'`id`'.$in);
	}

	/**
	 * �������� ������
	 *
	 * @param int|array $ids ������������� ��������� ������
	 * @param array $opts ����� �������, ������ � �������:
	 * array langs �������� ������ ������, ������� ���������. ���� false - ��������� ��� �������� ������. ���� �� ����� ������� ��� �������� ������, �� �������� ������� ��� ����� �������, � �� �������� - ���. ���������� ���.
	 * int|FALSE trash ������������� ������, ���� ����� ���������� ����
	 * array tolang �������� �����, � ������� ����� ������������� ���� � ������ �����������
	 * @return array �����:
	 * int done ���������� ��������� (������������) ���������
	 * int total ���������� ���������, ����������� � �������� (�����������)
	 */
	public function DeleteForum($ids,$opts=array())
	{
		$ids=(array)$ids;
		$r=array(
			'done'=>0,#�������
			'total'=>0,#�����
		);

		$opts+=array(
			'langs'=>false,#����� ������, ������� �������
			'pt'=>1000,#���������� ��� ������������/��������� �� ���
			'pp'=>1000,#���������� ������ ������������/��������� �� ���
			'pa'=>1000,#���������� ������� ������������/��������� �� ���
			'pts'=>1000,#���������� �������� �� ���� ������������/��������� �� ���
			'pfs'=>1000,#���������� �������� �� ������ ��������� �� ���
			'plp'=>1000,#���������� ��������� ������ ��������� �� ���
			'pre'=>1000,#���������� ��������������� ������ ��������� �� ���
			'ptr'=>1000,#���������� ���������, ��������� �� ���
			'trash'=>$this->Core->vars['trash'],#�� ������, ���� ����������� ������
			'trashlangs'=>array(),#������ "�����" ������ ��� ������ (��� ������, ����� ��� ����� ������ ������� � ����)
			'tolang'=>false,#����, � ������� �������
		);
		if(in_array($opts['trash'],$ids) and ($opts['langs']===false or $opts['tolang']===false or in_array($opts['tolang'],$opts['langs'])))
			throw new EE('MOVE_INTO_DELETE',EE::USER);

		$ps=array();
		$R=Eleanor::$Db->Query('SELECT `id`,`parents` FROM `'.$this->Forum->config['f'].'` INNER JOIN `'.$this->Forum->config['fl'].'` USING(`id`) WHERE `id`'.Eleanor::$Db->In($ids).($opts['langs']===false ? '' : ' AND `language`'.Eleanor::$Db->In($opts['langs'])));
		while($a=$R->fetch_assoc())
			$ps[$a['id']]=$a['parents'];

		foreach($ps as $k=>&$v)
		{
			$R=Eleanor::$Db->Query('SELECT `id` FROM `'.$this->Forum->config['f'].'` WHERE `parents` LIKE \''.$v.$k.',%\'');
			while($a=$R->fetch_assoc())
				$ids[]=$a['id'];
		}

		if(in_array($this->Core->vars['trash'],$ids))
			throw new EE('DELETING_TRASH',EE::USER);

		$lin=$in=Eleanor::$Db->In($ids);
		if($opts['langs']!==false)
			$lin.=' AND `language`'.Eleanor::$Db->In($opts['langs']);

		$samelang=$opts['tolang']===false;
		if($opts['trash'])
		{
			$langs=array();
			$R=Eleanor::$Db->Query('SELECT `language` FROM `'.$this->Forum->config['fl'].'` WHERE `id`='.$opts['trash']);
			while($a=$R->fetch_assoc())
				$langs[]=$a['language'];

			if($opts['trashlangs'])
				$langs=in_array('',$opts['trashlangs']) ? array('') : array_intersect($opts['trashlangs'],$langs);

			if($samelang)
				$samelang='IF(`language`'.Eleanor::$Db->In($langs).',`language`,\'\')';
			elseif(!in_array($opts['tolang'],$langs))
				throw new EE('NO_DEST_LANG',EE::USER);
		}

		$R=Eleanor::$Db->Query('SELECT SUM(`s`.`cnt`) FROM (
			(SELECT COUNT(`f`) `cnt` FROM `'.$this->Forum->config['ft'].'` WHERE `f`'.$lin.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'.$this->Forum->config['fp'].'` WHERE `f`'.$lin.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'.$this->Forum->config['fa'].'` WHERE `f`'.$lin.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'.$this->Forum->config['fs'].'` WHERE `f`'.$lin.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'.$this->Forum->config['ts'].'` WHERE `f`'.$lin.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'.$this->Forum->config['lp'].'` WHERE `f`'.$lin.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'.$this->Forum->config['re'].'` WHERE `f`'.$in.')UNION ALL
			(SELECT COUNT(`f`) `cnt` FROM `'.$this->Forum->config['fr'].'` WHERE `f`'.$in.')
		) `s`');
		list($r['total'])=$R->fetch_row();

		Eleanor::$Db->Transaction();
		if($opts['trash'])
		{
			if($samelang)
				$r['done']+=Eleanor::$Db->Update($this->Forum->config['fa'],array('f'=>$opts['trash'],'!language'=>$samelang),'`f`'.$lin.' LIMIT '.$opts['pa'])
					+Eleanor::$Db->Update($this->Forum->config['fp'],array('f'=>$opts['trash'],'!language'=>$samelang),'`f`'.$lin.' LIMIT '.$opts['pp'])
					+Eleanor::$Db->Update($this->Forum->config['ft'],array('f'=>$opts['trash'],'!language'=>$samelang),'`f`'.$lin.' LIMIT '.$opts['pt'])
					+Eleanor::$Db->Update($this->Forum->config['ts'],array('f'=>$opts['trash'],'!language'=>$samelang),'`f`'.$lin.' LIMIT '.$opts['pts'])
					+Eleanor::$Db->Update($this->Forum->config['tr'],array('f'=>$opts['trash'],'!language'=>$samelang),'`f`'.$lin.' LIMIT '.$opts['ptr']);
			else
				$r['done']+=Eleanor::$Db->Update($this->Forum->config['fa'],array('f'=>$opts['trash'],'language'=>$opts['tolang']),'`f`'.$lin.' LIMIT '.$opts['pa'])
					+Eleanor::$Db->Update($this->Forum->config['fp'],array('f'=>$opts['trash'],'language'=>$opts['tolang']),'`f`'.$lin.' LIMIT '.$opts['pp'])
					+Eleanor::$Db->Update($this->Forum->config['ft'],array('f'=>$opts['trash'],'language'=>$opts['tolang']),'`f`'.$lin.' LIMIT '.$opts['pt'])
					+Eleanor::$Db->Update($this->Forum->config['ts'],array('f'=>$opts['trash'],'language'=>$opts['tolang']),'`f`'.$lin.' LIMIT '.$opts['pts'])
					+Eleanor::$Db->Update($this->Forum->config['tr'],array('f'=>$opts['trash'],'language'=>$opts['tolang']),'`f`'.$lin.' LIMIT '.$opts['ptr']);
		}
		else
		{
			$R=Eleanor::$Db->Query('SELECT `p` FROM `'.$this->Forum->config['fa'].'` WHERE `f`'.$lin.' GROUP BY `p` LIMIT '.$opts['pa']);
			while($a=$R->fetch_assoc())
				Files::Delete(Eleanor::$root.Eleanor::$uploads.DIRECTORY_SEPARATOR.$this->Forum->config['n'].'/p'.$a['p']);
			$r['done']+=Eleanor::$Db->Delete($this->Forum->config['fa'],'`f`'.$lin.' LIMIT '.$opts['pa'])
				+Eleanor::$Db->Delete($this->Forum->config['fp'],'`f`'.$lin.' LIMIT '.$opts['pp'])
				+Eleanor::$Db->Delete($this->Forum->config['ft'],'`f`'.$lin.' LIMIT '.$opts['pt'])
				+Eleanor::$Db->Delete($this->Forum->config['ts'],'`f`'.$lin.' LIMIT '.$opts['pts'])
				+Eleanor::$Db->Delete($this->Forum->config['tr'],'`f`'.$lin.' LIMIT '.$opts['ptr']);
		}
		$r['done']+=Eleanor::$Db->Delete($this->Forum->config['fs'],'`f`'.$lin.' LIMIT '.$opts['pfs'])
			+Eleanor::$Db->Delete($this->Forum->config['lp'],'`f`'.$lin.' LIMIT '.$opts['plp'])
			+Eleanor::$Db->Delete($this->Forum->config['re'],'`f`'.$in.' LIMIT '.$opts['pre']);
		Eleanor::$Db->Commit();

		if($r['done']>=$r['total'])
		{
			if($opts['trash'])
			{
				$upd=array();
				$R=Eleanor::$Db->Query('SELECT `language`,`topics`,`posts`,`queued_topics`,`queued_posts` FROM `'.$this->Forum->config['fl'].'` WHERE `id`'.$lin);
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
							Eleanor::$Db->Update($this->Forum->config['fl'],array('!topics'=>'`topics`+'.$up['topics'],'!posts'=>'`posts`+'.$up['posts'],'!queued_topics'=>'`queued_topics`+'.$up['queued_topics'],'!queued_posts'=>'`queued_posts`+'.$up['queued_posts']),'`id`='.$opts['trash']);
						else
							Eleanor::$Db->Update($this->Forum->config['fl'],array('!topics'=>'`topics`+'.$up['topics'],'!posts'=>'`posts`+'.$up['posts'],'!queued_topics'=>'`queued_topics`+'.$up['queued_topics'],'!queued_posts'=>'`queued_posts`+'.$up['queued_posts']),'`id`='.$opts['trash'].' AND `language`=\''.$lang.'\' LIMIT 1');
				}
				else
					Eleanor::$Db->Update($this->config['fl'],array('!topics'=>'`topics`+'.array_sum($upd['topics']),'!posts'=>'`posts`+'.array_sum($upd['posts']),'!queued_topics'=>'`queued_topics`+'.array_sum($upd['queued_topics']),'!queued_posts'=>'`queued_posts`+'.array_sum($upd['queued_posts'])),'`id`='.$opts['trash'].' AND `language`=\''.$opts['tolang'].'\' LIMIT 1');

				$this->Moderate->RepairForums(array($opts['trash']=>$samelang ? $langs : array($opts['tolang'])));
			}

			if($opts['langs']===false)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`prefixes` FROM `'.$this->Forum->config['f'].'` WHERE `id`'.$in);
				while($a=$R->fetch_assoc())
					if($a['prefixes'])
						Eleanor::$Db->Update($this->Forum->config['f'],array('!forums'=>'REPLACE(`forums`,\','.$a['id'].',\',\'\')'),'`id`'.Eleanor::$Db->In(explode(',,',trim($a['prefixes'],','))));
				Eleanor::$Db->Delete($this->Forum->config['f'],'`id`'.$in);
			}
			Eleanor::$Db->Delete($this->Forum->config['fl'],'`id`'.$lin);
		}

		return$r;
	}

	/**
	 * �������� ��������� ������������
	 *
	 * @param int|array $ids ID �������������, � ������� ����� ����������� ���������
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