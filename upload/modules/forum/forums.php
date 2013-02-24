<?php
/*
	Copyright © Eleanor CMS
	URL: http://eleanor-cms.ru, http://eleanor-cms.com
	E-mail: support@eleanor-cms.ru
	Developing: Alexander Sunvas*
	Interface: Rumin Sergey
	=====
	*Pseudonym

	Большинство кода сего класса "позаимствовано" из Categories.
*/

class ForumForums extends Forum
{
	public static
		$rights=array(#Права по глобальному умолчанию
			'access'=>true,#Полный доступ к форуму
			'topics'=>true,#Просмотр списка тем
			'atopics'=>true,#Разершить просмотр списка всех тем, а не только своих (AllTopics)
			'read'=>true,#Разрешить чтение тем
			'attach'=>true,#Доступ к аттачам
			'post'=>true,#Разрешить ответ в свои темы
			'apost'=>true,#Разрешить ответ в чужие темы
			'edit'=>true,#Правка сообщений
			'editlimit'=>0,#Временное ограничение на правку сообщений в секундах
			'new'=>true,#Позволить создавать новые темы
			'mod'=>false,#Пользволить править / удалять сообщения в своих темах
			'close'=>false,#Позволить закрывать свои темы
			'deletet'=>false,#Позволить удалять свои темы
			'delete'=>false,#Позволить удалять свои посты
			'editt'=>true,#Позволить править заголовки своих тем
			'complaint'=>true,#Позволить использование кнопки "жалоба".
			'canclose'=>false,#Позволить работать с закрытой темой, как с открытой? (отвечать, править, публиковать)
		),
		$moder=array(#Права модератора глобальному по умолчанию
			'movet'=>false,#Перемещение тем
			'move'=>false,#Перемещение сообщений
			'deletet'=>false,#Удаление тем
			'delete'=>false,#Удаление сообщений
			'editt'=>false,#Правка тем
			'edit'=>false,#Правка сообщений
			'chstatust'=>false,#Изменения статуса тем
			'chstatus'=>false,#Изменение статуса сообщений
			'pin'=>false,#Закреплять / удалять закрепление с тем
			'mmovet'=>false,#Перемещать темы
			'mmove'=>false,#Перемещать сообщения
			'mdeletet'=>false,#Мульти удаление тем
			'mdelete'=>false,#Мультиудаление сообщений
			'user_warn'=>false,#Предупреждение пользователей
			'viewip'=>false,#Открыть просмотр IP адресов сообщений
			'opcl'=>false,#Открывать закрывать темы
			'mopcl'=>false,#Мультиоткрытие / закрытие тем
			'mpin'=>false,#Мульти закрепление / снятие тем
			'merget'=>false,#Объединение тем
			'merge'=>false,#Объединение сообщений
			'editq'=>false,#Редактирование опросов в сообщениях
			'mchstatust'=>false,#Мультиизменение статусов тем
			'mchstatus'=>false,#Мультиизменение статусов сообщений
			'editrep'=>false,#Редактирование репутации
		);

	public
		$dump;#Дамп БД категорий, в удобном упорядоченном виде

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
	 * Функция осуществляет поиск по дампу форумов исходя из переданного ID или последовательности URI конкретного форума
	 *
	 * @param int|array $id Числовой идентификатор категории либо массив последовательности URI
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
	 * Получение списка форумов в виде option-ов, для select-a: <option value="ID" selected>VALUE</option>
	 *
	 * @param int|array $sel Пункты, которые будут отмечены
	 * @param int|array $no ИДы исключаемых форумов (не попадут и их дети)
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
			$opts.=Eleanor::Option(($v['parents'] ? str_repeat('&nbsp;',substr_count($v['parents'],',')+1).'›&nbsp;' : '').$v['title'],$v['id'],in_array($v['id'],$sel),array(),2);
		}
		return$opts;
	}

	/**
	 * Получение массива URI для дальнейшего передачи его в класс URL с последующей генерации ссылки
	 *
	 * @param int $id Числовой идентификатор форума
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