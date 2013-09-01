<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
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
			'move'=>false,#Перемещение сообщений (включая "менять местами")
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

	public function __construct($config=false,$Base)
	{
		parent::__construct($config);
		$this->ReDump($Base->Core->language);
	}

	/**
	 * Пересоздание дампа форумов в зависимости от языка. Например, при рассылке есть необходимость работать с дампом
	 * форумов произвольного языка.
	 * @param $lang Язык
	 * @return array Дамп
	 */
	public function ReDump($lang)
	{
		$r=Eleanor::$Cache->Get($this->Forum->config['n'].'_'.$lang);
		if($r===false)
		{
			$R=Eleanor::$Db->Query('SELECT `id`,`language`,`parent`,`parents`,`pos`,`is_category`,`inc_posts`,`reputation`,`moderators`,`permissions`,`hide_attach`,`prefixes`,`title`,`uri` FROM `'.$this->Forum->config['f'].'` INNER JOIN `'.$this->Forum->config['fl'].'` USING(`id`) WHERE `language` IN (\'\',\''.$lang.'\')');
			$maxlen=0;
			$r=$to2sort=$to1sort=$db=array();
			while($a=$R->fetch_assoc())
			{
				$a['prefixes']=$a['prefixes'] ? explode(',,',trim($a['prefixes'],',')) : array();
			
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
				$r[(int)$db[$k]['id']]=array_slice($db[$k],1);

			foreach($r as &$v)
			{
				$v['permissions']=$v['permissions'] ? (array)unserialize($v['permissions']) : array();
				$v['moderators']=$v['moderators'] ? explode(',,',trim($v['moderators'],',')) : array();
			}
			Eleanor::$Cache->Put($this->Forum->config['n'].'_'.$lang,$r,86400,false);
		}
		return$this->dump=$r;
	}

	/**
	 * Поиск по дампу форумов исходя из переданного ID или последовательности URI
	 * @param int|array $id ID либо последовательность URI
	 */
	public function GetForum($id)
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
					$parent=$k;
				}
		}
		if(is_scalar($id) and isset($this->dump[$id]))
			return$this->dump[$id]+array('id'=>$id);
	}

	/**
	 * Получение списка форумов в виде option-ов, для select-a: <option value="ID" selected>VALUE</option>
	 * @param int|array $sel Пункты, которые будут отмечены
	 * @param int|array $no ИДы исключаемых форумов (не попадут и их дети)
	 * @param bool $actcats Категории так же активны
	 */
	public function SelectOptions($sel=array(),$no=array(),$actcats=true)
	{
		$opts='';
		$sel=(array)$sel;
		$no=(array)$no;

		$nocheck=Eleanor::$service=='admin';
		foreach($this->dump as $k=>&$v)
		{
			if($nocheck)
				$access=true;
			else
			{
				$access=false;
				foreach($this->Core->ug as $g)
				{
					$r=$this->Core->GroupPerms($k,$g);
					if($r['access'])
					{
						$access=true;
						break;
					}
				}
			}

			$p=$v['parents'] ? explode(',',$v['parents']) : array();
			$p[]=$k;
			if(!$access or array_intersect($no,$p))
				continue;
			$opts.=Eleanor::Option(($v['parents'] ? str_repeat('&nbsp;',substr_count($v['parents'],',')+1).'›&nbsp;' : '').$v['title'],$k,in_array($k,$sel),array('disabled'=>!$actcats and $v['is_category']),2);
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

	/**
	 * Получение всех возможных языков форума
	 * @param int $id ID форума
	 */
	public function GetLanguages($id)
	{
		$forum=&$this->dump[$id];
		if(!isset($forum['_languages']))
		{
			if(Eleanor::$vars['multilang'])
			{
				$R=Eleanor::$Db->Query('SELECT `language` FROM `'.$this->Forum->config['fl'].'` WHERE `id`='.$id.' AND `language`'.Eleanor::$Db->In(array_keys(Eleanor::$langs)));
				$forum['_languages']=$R->num_rows>0 ? array() : false;
				while($a=$R->fetch_assoc())
					$forum['_languages'][]=$a['language'];
			}
			else
				$forum['_languages']=false;
		}
		return $forum['_languages'];
	}

	/**
	 * Пометка конкретного форума прочтенным. Каких-либо проверок доступа не осуществляется. Прочитанным помечается весь
	 * форум, включая все его языки. Это сделано специально, чтобы при просмотре одной языковой версии было видно, что,
	 * возможно в другой есть непрочтенные темы.
	 * @param int $id ИД форума, который нужно пометить прочтеннымы
	 * @param int $ts TimeStamp даты, от которой нужно установить прочитанность
	 */
	public function MarkRead($id,$ts=0)
	{
		$Forum=$this->Core;
		$config=$this->Forum->config;

		if($Forum->user)
		{
			if($ts==0)
				Eleanor::$Db->Replace($config['re'],array('uid'=>$Forum->user['id'],'f'=>$id,'!allread'=>'NOW()'));
			else
			{
				$R=Eleanor::$Db->Query('SELECT `topics` FROM `'.$config['re'].'` WHERE `f`='.$id.' AND `uid`='.$Forum->user['id'].' AND `allread`>FROM_UNIXTIME('.$ts.') LIMIT 1');
				if($a=$R->fetch_assoc())
				{
					$a['topics']=$a['topics'] ? (array)unserialize($a['topics']) : array();
					foreach($a['topics'] as $k=>$v)
						if($v<=$ts)
							unset($a['topics'][$k]);
					Eleanor::$Db->Update($config['re'],array('allread'=>date('Y-m-d H:i:s',$ts),'topics'=>$a['topics'] ? serialize($a['topics']) : ''),'`f`='.$id.' AND `uid`='.$Forum->user['id'].' LIMIT 1');
				}
			}
		}
		else
		{
			$allread=(int)Eleanor::GetCookie($config['n'].'-ar');

			$read=array();
			$fr=Eleanor::GetCookie($Forum->config['n'].'-fr');
			$fr=$fr ? explode(',',$fr) : array();
			foreach($fr as $v)
				if(strpos($v,'-')!==false)
				{
					$v=explode('-',$v,2);
					if($v[1]>$allread)
						$read[ $v[0] ]=(int)$v[1];
				}

			if($ts==0)
				$read[$id]=time();
			elseif(isset($read[$id]) and $read[$id]<$ts)
				$read[$id]=$ts;

			arsort($read,SORT_NUMERIC);
			if(count($read)>$config['readslimit'])
				array_splice($read,$config['readslimit']);
			foreach($read as $k=>&$v)
				$v=$k.'-'.$v;
			Eleanor::SetCookie($Forum->config['n'].'-fr',join(',',$read));
		}
	}
}