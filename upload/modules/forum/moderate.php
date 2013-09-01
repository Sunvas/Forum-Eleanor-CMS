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
		#ToDo!
	}

	/*
		Функция починки темы. Позволяет скорректировать первое и последнее сообщение.
		Функция не пересчитывет посты в теме!
	*/
	protected function RepairTopics($int)
	{
		#ToDo!
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
		#ToDo!
	}

	/*
		$ids - ИДы постов
		$to - ИД темы, куда переместить
	*/
	public function MovePost($ids,$to)
	{
		#ToDo!
	}

	/*
		Перемещение тем
		$ids - ИДы тем
		$to - ИД форума,куда переместить
	*/
	public function MoveTopic($ids,$to,$data=array())
	{
		#ToDo!
	}

	/*
		Удаление тем
		$ids - ИДы тем.
	*/
	public function DeleteTopic($ids,$data=array())
	{
		#ToDo!
	}

	public function DeleteAttach($ids,$t='a')
	{
		#ToDo!
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