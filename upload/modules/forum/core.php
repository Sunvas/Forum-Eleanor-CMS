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

class ForumCore extends Forum
{
	public
		$module,#��������� ������
		$vars,#������ ��������
		$ug,#���������� Eleanor::GetUserGroups();
		$ugr=array(#User groups rights
			'shu'=>false,#���� ����������� ������ ������� �������������
			'supermod'=>false,#���� ����������������
			'moderate'=>true,#���� �������������� ������������� ����� ���������
		),
		$user,#������ ������� LoadUser()

		$ps,#Posts guest sign
		$ts,#Topics guest sign

		$grights=array();#����� ������. �� ����������� �������� ������ ������ ������������.

	public function __construct()
	{
		$this->ug=Eleanor::GetUserGroups();
		$this->vars=Eleanor::LoadOptions($this->Forum->config['opts'],true);
	}

	/**
	 * ����������� ���� ������ � ���������� ������
	 *
	 * @param int $f ������������� ������
	 * @param int $g ������������� ������
	 */
	public function GroupPerms($f,$g)
	{
		$r=array();
		if(isset($this->Forums->dump[$f]))
		{
			$f=$this->Forums->dump[$f];
			if(isset($f['permissions'][$g]))
				$r=$f['permissions'][$g];
			if($f['parent']>0)
				return$r+$this->GroupPerms($f['parent'],$g);
		}
		if(!isset($this->grights[$g]))
			$this->GetGroups($g);
		if(isset($this->grights[$g]))
			$r+=$this->grights[$g];
		return$r+ForumForums::$rights;;
	}

	/**
	 * "��������" ������������ � �������� ������������ ������
	 */
	public function LoadUser()
	{
		if(Eleanor::$Login->IsUser())
		{
			$uid=(int)Eleanor::$Login->GetUserValue('id');
			$R=Eleanor::$Db->Query('SELECT `id`,`restrict_post`,`restrict_post_to`,`allread`,`hidden`,`moderate` FROM `'.$this->Forum->config['fu'].'` WHERE `id`='.$uid.' LIMIT 1');
			if(!$this->user=$R->fetch_assoc())
				$this->user=$this->Service->AddUser($uid);
			if($this->user['restrict_post_to'] and time()<strtotime($this->user['restrict_post_to']))
				$this->user['restrict_post']=true;
			$this->user['allread']=strtotime($this->user['allread']);
		}
		else
			$this->user=false;
		$this->GetGroups($this->ug,true);
	}

	/**
	 * ��������� �������� ��� ������
	 *
	 * @param array|int $g �������������(�) �����
	 * @param bool $ug ���� �������������� ������ �������� ������������ (����� ��������� �������������)
	 */
	protected function GetGroups($g,$ug=false)
	{
		$g=(array)$g;

		#��� ����, ����� �� ������� � ����� ���� ���� �����, � ������ ������.
		$cn=$this->Forum->config['n'].'_groups'.join('g',$g);
		$grs=Eleanor::$Cache->Get($cn);
		if($grs===false)
		{
			$ps=Eleanor::Permissions($g,'parents');
			foreach($ps as $k=>&$v)
				if($v)
					$g=array_merge($g,$v);
			$grs=array();
			$R=Eleanor::$Db->Query('SELECT `id`,`supermod`,`see_hidden_users`,`moderate`,`permissions` FROM `'.$this->Forum->config['fg'].'` WHERE `id`'.Eleanor::$Db->In($g));
			while($a=$R->fetch_assoc())
			{
				$a['permissions']=$a['permissions'] ? (array)unserialize($a['permissions']) : array();
				$grs[$a['id']]=array_slice($a,1);
			}

			foreach($grs as $k=>&$v)
			{
				$r=array();
				foreach($v as $name=>$value)
				{
					$r[$name]=$value;
					if($value===null and isset($ps[$k]))
						foreach($ps[$k] as $pv)
							if(isset($grs[$pv][$value]))
								$r[$name]=$grs[$pv][$value];
				}

				if(isset($ps[$k]))
					foreach($ps[$k] as $pv)
						if(isset($grs[$pv]['permissions']))
							$r['permissions']+=$grs[$pv]['permissions'];

				$v=$r;
			}
			Eleanor::$Cache->Put($cn,$grs,86400);
		}

		foreach($grs as $k=>&$v)
		{
			$this->grights[$k]=$v['permissions'];
			if($ug)
			{
				if($this->user)
				{
					if($v['supermod'])
						$this->ugr['supermod']=true;
					if($v['see_hidden_users'])
						$this->ugr['shu']=true;
				}
				if(!$v['moderate'])
					$this->ugr['moderate']=false;
			}
		}
	}

	/**
	 * ��������� �������� ���� ��� ������������� ������ (��� �������� ������������)
	 *
	 * @param int $f ������������� ������
	 */
	public function ForumRights($f)
	{
		$r=array();
		foreach($this->ug as &$g)
		{
			$fr=$this->GroupPerms($f,$g);
			foreach($fr as $k=>&$v)
				$r[$k][]=$v;
		}
		return$r;
	}

	/**
	 * �������� ����������� ������� �������� ������������ � ������������ ����
	 *
	 * @param array $t ������ ����, ����������� ������� ������: id - ������������� ����, fid - ������������� ������, author_id - ������������� ������
	 */
	public function CheckTopicAccess(array$t)
	{
		$ra=$rt=$rta=array();
		foreach($this->ug as &$g)
		{
			$r=$this->GroupPerms($t['f'],$g);
			$ra[]=$r['access'];
			$rt[]=$r['topics'];
			$rta[]=$r['atopics'];
		}
		if(!$this->user)
			$gt=$this->GuestSign('t');
		if(in_array(1,$ra) and in_array(1,$rt) and (in_array(1,$rta) or $this->user and $this->user['id']==$t['author_id'] or !$this->user and in_array($t['id'],$gt)))
			return true;
	}

	/**
	 * ��������� ������ �������������, �������� ����, �����, ����...
	 *
	 * @param string $s ������ ������� -fID ��� ������, -fID-tID ��� ����, -pID ��� �����
	 */
	public function GetOnline($s='')
	{
		$h=($this->user and $this->user['hidden']);
		$uid=$this->user ? $this->user['id'] : 0;
		Eleanor::$sessaddon=$this->Forum->config['n'].$s.($h ? '-h' : '-');
		$online[]=array('user_id'=>$uid,'enter'=>time(),'user'=>reset($this->ug),'name'=>Eleanor::$Login->GetUserValue('name'),'_hidden'=>$h);
		$R=Eleanor::$Db->Query('SELECT `s`.`type`,`s`.`user_id`,`s`.`enter`,`s`.`name` `botname`,`s`.`addon`,`us`.`groups`,`us`.`name` FROM `'.P.'sessions` `s` INNER JOIN `'.P.'users_site` `us` ON `s`.`user_id`=`us`.`id` WHERE `s`.`addon` LIKE \''.$this->Forum->config['n'].$s.'-%\' AND `s`.`expire`>\''.date('Y-m-d H:i:s').'\' AND `s`.`service`=\''.Eleanor::$service.'\' ORDER BY `s`.`expire` DESC');
		while($a=$R->fetch_assoc())
		{
			$a['enter']=strtotime($a['enter']);
			if($a['type']=='user' and $a['groups'])
			{
				$gs=array((int)ltrim($a['groups'],','));
				$a['_pref']=join(Eleanor::Permissions($gs,'html_pref'));
				$a['_end']=join(Eleanor::Permissions($gs,'html_end'));
			}
			else
				$a['_end']=$a['_pref']='';

			if($a['user_id']!=$uid)
				$online[]=$a+array('_hidden'=>substr($a['addon'],-2)=='-h');
			elseif($uid==0)
				$uid=-1;
		}
		return$online;
	}

	/**
	 * ����� ��� "�������" ��� � ������ �����
	 *
	 * @param string $t ������������� �������: p ��� �����, t ��� ����
	 * @param int|FALSE $add ������������� ���� ��� �����, ������� ����� �������� � �������
	 */
	public function GuestSign($t='p',$add=false)
	{
		if($t=='t')
		{
			if(!isset($this->ts))
			{
				$gt=Eleanor::GetCookie($this->Forum->config['n'].'-gt');
				$gts=Eleanor::GetCookie($this->Forum->config['n'].'-gts');
				$this->ts=$gt && $gts && $gts===md5($gt.$this->Forum->config['tsign']) ? explode(',',$gt) : array();
			}
			if($add and !in_array($add,$this->ts))
			{
				$this->ts[]=$add;
				$this->ts=array_slice($this->ts,-30);

				sort($this->ts,SORT_NUMERIC);
				$gt=join(',',$this->ts);
				Eleanor::SetCookie($this->Forum->config['n'].'-gt',$gt);
				Eleanor::SetCookie($this->Forum->config['n'].'-gts',md5($gt.$this->Forum->config['tsign']));
			}
			return$this->ts;
		}

		if(!isset($this->ps))
		{
			$gp=Eleanor::GetCookie($this->Forum->config['n'].'-gp');
			$gps=Eleanor::GetCookie($this->Forum->config['n'].'-gps');
			$this->ps=$gp && $gps && $gps===md5($gp.$this->Forum->config['psign']) ? explode(',',$gp) : array();
		}
		if($add and !in_array($add,$this->ps))
		{
			$this->ps[]=$add;
			$this->ps=array_slice($this->ps,-30);

			sort($this->ps,SORT_NUMERIC);
			$gp=join(',',$this->ps);
			Eleanor::SetCookie($this->Forum->config['n'].'-gp',$gp);
			Eleanor::SetCookie($this->Forum->config['n'].'-gps',md5($gp.$this->Forum->config['psign']));
		}
		return$this->ps;
	}
}