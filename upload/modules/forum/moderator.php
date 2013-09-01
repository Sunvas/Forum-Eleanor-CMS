<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

class ForumModerator extends Forum
{
	/**
	 * Получение модераторов форума и права пользователя, как модератора на форуме
	 * @param int|array $ids Идентификаторы форумов
	 * @param array $fields Извлекаемые поля модераторских прав из DB
	 * @pram string $cache Идентификатор кэша
	 */
	public function ByIds($ids,array$fields,$cache)
	{
		$moders=$cache ? Eleanor::$Cache->Get($cache) : false;
		if($moders===false)
		{
			$moders=array(
				'rights'=>array(),
				'users'=>array(),
				'groups'=>array(),
			);
			$users=$groups=array();
			$R=Eleanor::$Db->Query('SELECT `id`,`groups`,`users`'.($fields ? ',`'.join('`,`',$fields).'`' : '').' FROM `'.$this->Forum->config['fm'].'` WHERE `id`'.Eleanor::$Db->In($ids));
			while($a=$R->fetch_assoc())
			{
				$a['users']=$a['users'] ? explode(',,',trim($a['users'],',')) : array();
				$a['groups']=$a['groups'] ? explode(',,',trim($a['groups'],',')) : array();
				foreach($a['users'] as &$v)
					$users[$v][]=$a['id'];
				foreach($a['groups'] as &$v)
					$groups[$v][]=$a['id'];
				$moders['rights'][ $a['id'] ]=array_slice($a,3);
			}

			if($users)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`name`,`groups` `_group` FROM `'.P.'users_site` WHERE `id`'.Eleanor::$Db->In(array_keys($users)));
				while($a=$R->fetch_assoc())
				{
					$a['_group']=(int)ltrim($a['_group'],',');
					$a['_rights']=$users[ $a['id'] ];
					$moders['users'][ $a['id'] ]=array_slice($a,1);
				}
			}

			if($groups)
			{
				$R=Eleanor::$Db->Query('SELECT `id`,`title_l` `title`,`html_pref`,`html_end` FROM `'.P.'groups` WHERE `id`'.Eleanor::$Db->In(array_keys($groups)));
				while($a=$R->fetch_assoc())
				{
					$a['title']=$a['title'] ? Eleanor::FilterLangValues((array)unserialize($a['title'])) : '';
					$a['_rights']=$groups[ $a['id'] ];
					$moders['groups'][ $a['id'] ]=array_slice($a,1);
				}
			}

			if($cache)
				Eleanor::$Cache->Put($cache,$moders,86400);
		}

		$moderator=array();
		if($this->Core->user and $moders)
		{
			$u=$this->Core->user['id'];
			if(isset($moders['users'][$u]))
				foreach($moders['users'][$u]['_rights'] as $v)
					foreach($moders['rights'][$v] as $vk=>$vr)
						$moderator[$vk][]=$vr;

			foreach($this->Core->ug as $g)
				if(isset($moders['groups'][$g]))
					foreach($moders['groups'][$g]['_rights'] as $v)
						foreach($moders['rights'][$v] as $vk=>$vr)
							$moderator[$vk][]=$vr;
		}

		return array($moders,$moderator);
	}
}