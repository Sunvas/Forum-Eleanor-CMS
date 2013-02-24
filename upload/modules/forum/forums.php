<?php
/*
	Copyright � Eleanor CMS
	URL: http://eleanor-cms.ru, http://eleanor-cms.com
	E-mail: support@eleanor-cms.ru
	Developing: Alexander Sunvas*
	Interface: Rumin Sergey
	=====
	*Pseudonym

	����������� ���� ���� ������ "��������������" �� Categories.
*/

class ForumForums extends Forum
{
	public static
		$rights=array(#����� �� ����������� ���������
			'access'=>true,#������ ������ � ������
			'topics'=>true,#�������� ������ ���
			'atopics'=>true,#��������� �������� ������ ���� ���, � �� ������ ����� (AllTopics)
			'read'=>true,#��������� ������ ���
			'attach'=>true,#������ � �������
			'post'=>true,#��������� ����� � ���� ����
			'apost'=>true,#��������� ����� � ����� ����
			'edit'=>true,#������ ���������
			'editlimit'=>0,#��������� ����������� �� ������ ��������� � ��������
			'new'=>true,#��������� ��������� ����� ����
			'mod'=>false,#����������� ������� / ������� ��������� � ����� �����
			'close'=>false,#��������� ��������� ���� ����
			'deletet'=>false,#��������� ������� ���� ����
			'delete'=>false,#��������� ������� ���� �����
			'editt'=>true,#��������� ������� ��������� ����� ���
			'complaint'=>true,#��������� ������������� ������ "������".
			'canclose'=>false,#��������� �������� � �������� �����, ��� � ��������? (��������, �������, �����������)
		),
		$moder=array(#����� ���������� ����������� �� ���������
			'movet'=>false,#����������� ���
			'move'=>false,#����������� ���������
			'deletet'=>false,#�������� ���
			'delete'=>false,#�������� ���������
			'editt'=>false,#������ ���
			'edit'=>false,#������ ���������
			'chstatust'=>false,#��������� ������� ���
			'chstatus'=>false,#��������� ������� ���������
			'pin'=>false,#���������� / ������� ����������� � ���
			'mmovet'=>false,#���������� ����
			'mmove'=>false,#���������� ���������
			'mdeletet'=>false,#������ �������� ���
			'mdelete'=>false,#�������������� ���������
			'user_warn'=>false,#�������������� �������������
			'viewip'=>false,#������� �������� IP ������� ���������
			'opcl'=>false,#��������� ��������� ����
			'mopcl'=>false,#�������������� / �������� ���
			'mpin'=>false,#������ ����������� / ������ ���
			'merget'=>false,#����������� ���
			'merge'=>false,#����������� ���������
			'editq'=>false,#�������������� ������� � ����������
			'mchstatust'=>false,#��������������� �������� ���
			'mchstatus'=>false,#��������������� �������� ���������
			'editrep'=>false,#�������������� ���������
		);

	public
		$dump;#���� �� ���������, � ������� ������������� ����

	public function __construct()
	{
		$r=Eleanor::$Cache->Get($this->Forum->config['n'].'_'.Language::$main);
		if($r===false)
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`language`,`parent`,`parents`,`pos`,`is_category`,`moderators`,`permissions`,`title`,`uri`,`hide_attach`,`reputation` FROM `'.$this->Forum->config['f'].'` INNER JOIN `'.$this->Forum->config['fl'].'` USING(`id`) WHERE `language` IN (\'\',\''.Language::$main.'\')');
			$maxlen=0;
			$r=$to2sort=$to1sort=$db=array();
			while($a=$R->fetch_assoc())
			{
				if($a['parents'])
				{
					$cnt=substr_count($a['parents'],',');
					$to1sort[$a['id']]=$cnt;
					$maxlen=max($cnt,$maxlen);
				}
				$db[$a['id']]=$a;
				$to2sort[$a['id']]=$a['pos'];
			}
			asort($to1sort,SORT_NUMERIC);

			foreach($to1sort as $k=>&$v)
				if($db[$k]['parents'])
					if(isset($to2sort[$db[$k]['parent']]))
						$to2sort[$k]=$to2sort[$db[$k]['parent']].','.$to2sort[$k];
					else
						unset($to2sort[$db[$k]['parent']]);

			foreach($to2sort as $k=>&$v)
				$v.=str_repeat(',0',$maxlen-substr_count($db[$k]['parents'],','));

			natsort($to2sort);
			foreach($to2sort as $k=>&$v)
			{
				$db[$k]['parents']=rtrim($db[$k]['parents'],',');
				$r[(int)$db[$k]['id']]=$db[$k];
			}
			foreach($r as &$v)
			{
				$v['permissions']=$v['permissions'] ? (array)unserialize($v['permissions']) : array();
				$v['moderators']=$v['moderators'] ? explode(',,',trim($v['moderators'],',')) : array();
			}
			Eleanor::$Cache->Put($this->Forum->config['n'].'_'.Language::$main,$r,86400,false);
		}
		return$this->dump=$r;
	}

	/**
	 * ������� ������������ ����� �� ����� ������� ������ �� ����������� ID ��� ������������������ URI ����������� ������
	 *
	 * @param int|array $id �������� ������������� ��������� ���� ������ ������������������ URI
	 */
	public function GetCategory($id)
	{
		if(is_array($id))
		{
			$cnt=count($id)-1;
			$parent=0;
			$curr=array_shift($id);
			foreach($this->dump as $k=>&$v)
				if($v['parent']==$parent and strcasecmp($v['uri'],$curr)==0)
				{
					if($cnt--==0)
					{
						$id=$k;
						break;
					}
					$curr=array_shift($id);
					$parent=$v['id'];
				}
		}
		if(is_scalar($id) and isset($this->dump[$id]))
		{
			$this->dump[$id]['description']=OwnBB::Parse($this->dump[$id]['description']);
			return$this->dump[$id];
		}
	}

	/**
	 * ��������� ������ ������� � ���� option-��, ��� select-a: <option value="ID" selected>VALUE</option>
	 *
	 * @param int|array $sel ������, ������� ����� ��������
	 * @param int|array $no ��� ����������� ������� (�� ������� � �� ����)
	 */
	public function SelectOptions($sel=array(),$no=array())
	{
		$opts='';
		$sel=(array)$sel;
		$no=(array)$no;
		foreach($this->dump as $k=>&$v)
		{
			$access=false;
			foreach($this->Core->ug as &$g)
			{
				$r=$this->Core->GroupPerms($k,$g);
				if($r['access'])
				{
					$access=true;
					break;
				}
			}

			$p=$v['parents'] ? explode(',',$v['parents']) : array();
			$p[]=$v['id'];
			if(!$access or array_intersect($no,$p))
				continue;
			$opts.=Eleanor::Option(($v['parents'] ? str_repeat('&nbsp;',substr_count($v['parents'],',')+1).'�&nbsp;' : '').$v['title'],$v['id'],in_array($v['id'],$sel),array(),2);
		}
		return$opts;
	}

	/**
	 * ��������� ������� URI ��� ����������� �������� ��� � ����� URL � ����������� ��������� ������
	 *
	 * @param int $id �������� ������������� ������
	 */
	public function GetUri($id)
	{
		if(!isset($this->dump[$id]))
			return array();
		$params=array();
		$lastu=$this->dump[$id]['uri'];
		if($this->dump[$id]['parents'] and $lastu)
		{
			foreach(explode(',',$this->dump[$id]['parents']) as $v)
				if(isset($this->dump[$v]))
					if($this->dump[$v]['uri'])
						$params[]=array($this->dump[$v]['uri']);
					else
					{
						$params=array();
						$lastu='';
						break;
					}
		}
		$params[]=array($lastu,'f'=>$id);
		return$params;
	}
}