<?php
/*
	Resale is forbidden!
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

class TaskSpecial_Forum extends BaseClass implements Task
{	const
		PATH='modules/forum/';
	public function Run($data)
	{		include_once Eleanor::$root.self::PATH.'forum.php';
		include_once Eleanor::$root.self::PATH.'core.php';
		$mc=include Eleanor::$root.self::PATH.'config.php';
		$done=true;
		$Forum=new ForumCore($mc);

		$q='SELECT `id`,`type`,`options`,`data`,`done`,`total` FROM `'.$mc['ta'].'` WHERE `status`=';
		$R=Eleanor::$Db->Query('('.$q.'\'wait\' ORDER BY `date` ASC LIMIT 1)UNION ALL('.$q.'\'process\' ORDER BY `date` ASC LIMIT 1)');
		if($a=$R->fetch_assoc())
		{			$a['options']=$a['options'] ? (array)unserialize($a['options']) : array();			$a['data']=$a['data'] ? (array)unserialize($a['data']) : array();
			switch($a['type'])
			{				case'syncusers':
					$data=$Forum->Service->SyncUsers(isset($a['options']['date']) ? $a['options'] : $a['data']);
					$tdone=$data['done']>=$data['total'];
					$upd=array(
						'!date'=>'NOW()',
						'status'=>$tdone ? 'done' : 'wait',
						'data'=>serialize(array('date'=>$data['date'])),
						'done'=>$data['done'],
						'total'=>$data['total'],
					);
					if($tdone)
						$upd['!finish']='NOW()';
				break;
				default:
					$upd=array(
						'data'=>serialize(array(
							'error'=>'Unknown task',
						)),
						'status'=>'error',
					);			}
			Eleanor::$Db->Update($mc['ta'],$upd,'`id`='.$a['id'].' LIMIT 1');			$done=false;		}

		return$done;	}

	public function GetNextRunInfo()
	{		return'';	}
}