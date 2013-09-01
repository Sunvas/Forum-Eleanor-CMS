<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

class ForumCore extends Forum
{
	public
		$vars=array(#Массив настроек
			'trash'=>false,#Идентификатор корзины
			'tpp'=>30,#Количество тем на страницу в форуме
			'ppp'=>20,#Количество постов на страницу в теме
			'r+'=>50,#Число постов, после которого можно увеличивать репутацию
			'r-'=>50,#Число постов, после которого можно уменьшать репутацию
		),
		$ug=array(),#Содержимое Eleanor::GetUserGroups();
		$ugr=array(#User groups rights
			'shu'=>false,#Флаг возможности видеть скрытых пользователей
			'supermod'=>false,#Флаг суппермодератора
			'moderate'=>true,#Флаг обязательности модерирования новых сообщений
		),
		$user,#Смотри функцию LoadUser()
		$language,#Язык, в котором работает форум

		$ps,#Posts guest sign
		$ts,#Topics guest sign

		$rights=array();#Права групп. Не обязательно содержит только группы пользователя.

	public function __construct($config=false)
	{
		parent::__construct($config);
		$this->language=Language::$main;
	}

	/**
	 * Определение прав группы в конкретном форуме
	 * @param int $f Идентификатор форума
	 * @param int $g Идентификатор группы
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
		if(!isset($this->rights[$g]))
			$this->GetGroups($g);
		if(isset($this->rights[$g]))
			$r+=$this->rights[$g];
		return$r+ForumForums::$rights;
	}

	/**
	 * "Загрузка" пользователя в качестве пользователя форума
	 */
	public function LoadUser()
	{
		$this->ug=Eleanor::GetUserGroups();
		if(Eleanor::$Login->IsUser())
		{
			$uid=(int)Eleanor::$Login->GetUserValue('id');
			$R=Eleanor::$Db->Query('SELECT `id`,`posts`,`restrict_post`,`restrict_post_to`,`allread`,`hidden`,`moderate` FROM `'.$this->Forum->config['fu'].'` WHERE `id`='.$uid.' LIMIT 1');
			if(!$this->user=$R->fetch_assoc())
				$this->user=$this->Service->AddUser($uid,true);
			if((int)$this->user['restrict_post_to']>0 and time()<strtotime($this->user['restrict_post_to']))
				$this->user['restrict_post']=true;
			$this->user['allread']=strtotime($this->user['allread']);
		}
		else
			$this->user=false;
		$this->GetGroups($this->ug,true);
	}

	/**
	 * Получение настроек для группы
	 * @param array|int $g Идентификатор(ы) групп
	 * @param bool $ug Флаг принадлежности группы текущему пользователю (будут применены спецнастройки)
	 */
	protected function GetGroups($g,$ug=false)
	{
		$g=(array)$g;

		#Для того, чтобы не таскать с собой весь дамп групп, а только нужные.
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
			$this->rights[$k]=$v['permissions'];
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
	 * Получение значений прав для определенного форума (для текущего пользователя)
	 * @param int $f Идентификатор форума
	 * @param array|null $groups Переопределение групп пользователя
	 */
	public function ForumRights($f,$groups=null)
	{
		if($groups===null)
			$groups=$this->ug;

		$r=array();
		foreach($groups as $g)
		{
			$fr=$this->GroupPerms($f,$g);
			foreach($fr as $k=>$v)
				$r[$k][]=$v;
		}
		return$r;
	}

	/**
	 * Проверка возможности доступа текущего пользователя к определенной теме (учитывая права на форуме). Статус темы не учитывается.
	 * @param array $t Массив темы, обязательно наличие ключей: id - идентификатор темы, f - идентификатор форума, author_id - идентификатор автора
	 * @param array|null $user Пользователя
	 */
	public function CheckTopicAccess(array$t,$user=null)
	{
		if($user===null)
		{
			$user=$this->user;
			$groups=$this->ug;
		}
		else
			$groups=isset($user['groups']) ? (array)$user['groups'] : $this->ug;

		$ra=$rt=$rta=array();
		foreach($groups as $g)
		{
			$r=$this->GroupPerms($t['f'],$g);
			$ra[]=$r['access'];
			$rt[]=$r['topics'];
			$rta[]=$r['atopics'];
		}
		if(!$user)
			$gt=$this->GuestSign('t');
		if(in_array(1,$ra) and in_array(1,$rt) and (in_array(1,$rta) or $user and $user['id']==$t['author_id'] or !$user and in_array($t['id'],$gt)))
			return true;
	}

	/**
	 * Проверка возможности доступа к форуму
	 * @param int $f ID форума
	 * @param array|null $groups Переопределение групп пользователя
	 */
	public function CheckForumAccess($f,$groups=null)
	{
		if($groups===null)
			$groups=$this->ug;

		$ra=array();
		foreach($groups as $g)
		{
			$r=$this->GroupPerms($f,$g);
			$ra[]=$r['access'];
		}
		return isset($this->Forums->dump[$f]) && in_array(1,$ra);
	}

	/**
	 * Получение списка пользователей, читающих тему, форум, пост...
	 * @param string $s Строка поискаЖ -fID для форума, -fID-tID для темы, -pID для поста
	 */
	public function GetOnline($s='')
	{
		Eleanor::$sessextra=$this->Forum->config['n'].$s;

		$online=array();
		if($this->user)
		{
			$uid=$this->user['id'];
			$name=Eleanor::$Login->GetUserValue('name',false);
			$online[]=array('user_id'=>$uid,'enter'=>time(),'name'=>$name,'group'=>reset($this->ug),'_hidden'=>$this->user['hidden'],'_a'=>Eleanor::$Login->UserLink($name,$uid));

			if($this->user['hidden'])
				Eleanor::$sessextra.='-h';
		}
		else
			$uid=-1;

		$R=Eleanor::$Db->Query('SELECT `s`.`type`,`s`.`user_id`,`s`.`enter`,`s`.`name` `botname`,`s`.`extra`,`us`.`groups` `group`,`us`.`name` FROM `'.P.'sessions` `s` INNER JOIN `'.P.'users_site` `us` ON `s`.`user_id`=`us`.`id` WHERE `s`.`extra` LIKE \''.$this->Forum->config['n'].$s.'-%\' AND `s`.`expire`>\''.date('Y-m-d H:i:s').'\' AND `s`.`service`=\''.Eleanor::$service.'\' ORDER BY `s`.`expire` DESC');
		while($a=$R->fetch_assoc())
		{
			$a['enter']=strtotime($a['enter']);
			if($a['type']=='user' and $a['group'])
				$a['group']=(int)ltrim($a['group'],',');

			if($a['user_id']!=$uid)
			{
				if($a['type']=='user')
					$a['_a']=Eleanor::$Login->UserLink($a['name'],$a['user_id']);
				$online[]=$a+array('_hidden'=>substr($a['extra'],-2)=='-h');
			}
		}
		return$online;
	}

	/**
	 * Метод для "подписи" тем и постов гостя
	 * @param string $t Идентификатор подписи: p для поста, t для темы
	 * @param array|int|FALSE $add Идентификатор темы или поста, который нужно добавить в подпись
	 */
	public function GuestSign($t='p',$add=false)
	{
		$config = $this->Forum->config;
		$isa=is_array($add);
		if($t=='t')
		{
			if(!isset($this->ts))
			{
				$gt=Eleanor::GetCookie($config['n'].'-gt');
				$gts=Eleanor::GetCookie($config['n'].'-gts');
				$this->ts=$gt && $gts && $gts===md5($gt. $config['tsign']) ? explode(',',$gt) : array();
			}
			if($add and ($isa and array_diff($add,$this->ts) or !$isa and !in_array($add,$this->ts)))
			{
				if($isa)
					$this->ts=array_unique(array_merge($this->ts,$add));
				else
					$this->ts[]=$add;
				$this->ts=array_slice($this->ts,-30);

				sort($this->ts,SORT_NUMERIC);
				$gt=join(',',$this->ts);
				Eleanor::SetCookie($config['n'].'-gt',$gt);
				Eleanor::SetCookie($config['n'].'-gts',md5($gt. $config['tsign']));
			}
			return$this->ts;
		}

		if(!isset($this->ps))
		{
			$gp=Eleanor::GetCookie($config['n'].'-gp');
			$gps=Eleanor::GetCookie($config['n'].'-gps');
			$this->ps=$gp && $gps && $gps===md5($gp. $config['psign']) ? explode(',',$gp) : array();
		}
		if($add and ($isa and array_diff($add,$this->ps) or !$isa and !in_array($add,$this->ps)))
		{
			if($isa)
				$this->ps=array_unique(array_merge($this->ps,$add));
			else
				$this->ps[]=$add;
			$this->ps=array_slice($this->ps,-30);

			sort($this->ps,SORT_NUMERIC);
			$gp=join(',',$this->ps);
			Eleanor::SetCookie($config['n'].'-gp',$gp);
			Eleanor::SetCookie($config['n'].'-gps',md5($gp. $config['psign']));
		}
		return$this->ps;
	}
}