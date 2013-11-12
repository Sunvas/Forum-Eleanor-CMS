<?php
/*
	Copyright © Alexander Sunvas*
	http://eleanor-cms.ru
	a@eleanor-cms.ru
	*Pseudonym
*/

class ForumTopic extends Forum
{
	/**
	 * Метод пометки темы прочитанной. Метод не проверяет корректность соответствия форума
	 * теме и не проводит проверок на возможность доступа к теме
	 * @param int $t ID темы, либо массив ID тем
	 * @param int $f ID форума
	 * @param int $ts TimeStamp
	 */
	public function MarkRead($t,$f=false,$ts=0)
	{
		$Forum = $this->Forum;
		if(!$f)
		{
			$R=Eleanor::$Db->Query('SELECT `f` FROM `'. $Forum->config['ft'].'` WHERE `id`='.$t.' LIMIT 1');
			if(!list($f)=$R->fetch_row())
				return;
		}

		if($this->Core->user)
		{
			$upd=array(
				'uid'=>$this->Core->user['id'],
				'f'=>$f,
				'allread'=>0,
				'topics'=>array(),
			);

			$R=Eleanor::$Db->Query('SELECT `allread`,`topics` FROM `'. $Forum->config['re'].'` WHERE `f`='.$f.' AND `uid`='.$upd['uid'].' LIMIT 1');
			if($a=$R->fetch_assoc())
			{
				$upd['allread']=strtotime($a['allread']);
				if($a['topics'])
					$upd['topics']=(array)unserialize($a['topics']);
			}

			if($ts==0)
				$upd['topics'][$t]=time();
			elseif(isset($upd['topics'][$t]))
			{
				if($upd['topics'][$t]>$ts)
					return;
				if($upd['allread']>$ts)
					unset($upd['topics'][$t]);
				else
					$upd['topics'][$t]=$ts;
			}
			elseif($upd['allread']>$ts)
				return;
			else
				$upd['topics'][$t]=$ts;

			if($this->Core->user['allread']>$upd['allread'])
				$upd['allread']=$this->Core->user['allread'];
			foreach($upd['topics'] as $k=>&$v)
				if($v<=$upd['allread'])
					unset($upd['topics'][$k]);
			arsort($upd['topics'],SORT_NUMERIC);
			if(count($upd['topics'])> $Forum->config['readslimit'])
			{
				$upd['allread']=max($upd['allread'],min($upd['topics']));
				array_splice($upd['topics'], $Forum->config['readslimit']);
			}
			$upd['allread']=$upd['allread']>0 ? date('Y-m-d H:i:s',$upd['allread']) : '0';
			$upd['topics']=$upd['topics'] ? serialize($upd['topics']) : '';
			Eleanor::$Db->Replace($Forum->config['re'],$upd);
		}
		else
		{
			$allread=(int)Eleanor::GetCookie($Forum->config['n'].'-ar');

			$fread=array();
			if($fr=Eleanor::GetCookie($Forum->config['n'].'-fr'))
			{
				$fr=explode(',',$fr);
				foreach($fr as $v)
					if(strpos($v,'-')!==false)
					{
						$v=explode('-',$v,2);
						if($v[1]>$allread)
							$fread[ $v[0] ]=$v[1];
					}
			}

			if(isset($fread[$f]) and $fread[$f]>$allread)
				$allread=$fread[$f];

			$tr=Eleanor::GetCookie($Forum->config['n'].'-tr');
			$tr=$tr ? explode(',',$tr) : array();
			foreach($tr as $v)
				if(strpos($v,'-')!==false)
				{
					$v=explode('-',$v,2);
					if($v[1]>$allread)
						$read[ $v[0] ]=(int)$v[1];
				}

			$read[$t]=time();
			arsort($read,SORT_NUMERIC);
			if(count($read)> $Forum->config['readslimit'])
			{
				$fread[$f]=isset($fread[$f]) ? max($fread[$f],min($read)) :min($read);
				array_splice($read, $Forum->config['readslimit']);
			}

			foreach($read as $k=>&$v)
				$v=$k.'-'.$v;
			Eleanor::SetCookie($Forum->config['n'].'-tr',join(',',$read));

			foreach($fread as $k=>&$v)
				$v=$k.'-'.$v;
			Eleanor::SetCookie($Forum->config['n'].'-fr',join(',',$fread));
		}
	}

	/**
	 * Получение прав взаимодействия с конкретной темой с точки зрения пользователя. Спецификой данного метода
	 * является определение тех прав, который нельзя проверить банальным in_array('value',$rights).
	 * @param array $topic Дамп темы, массив с ключами:
	 *  int id ID темы
	 *  string state Состояние темы
	 *  int author_id ID автора темы
	 *  string created Дата создания темы
	 * @param array $rights Права пользователя на форуме, массив с ключами:
	 * @param array $moder Права модератора на форуме, массив с ключами:
	 * @return array:
	 *  bool read Возможность просматривать тему
	 *  bool post Возможность публиковать посты в тему
	 *  bool edit Возможность править любой (!) пост
	 *  bool delete Возможность удалить любой (!) пост
	 *  bool editt Возможно править заголовки темы
	 *  bool deletet Возможность удалить тему
	 *  bool close Возможность открывать/закрывать тему
	 */
	public function Rights(array$topic,array$rights,array$moder=array())
	{
		$Core=$this->Core;
		$r['read']=$r['post']=$r['edit']=$r['delete']=$r['editt']=$r['deletet']=$r['close']=$Core->ugr['supermod'];
		if(!$r['read'] and in_array(1,$rights['read']))
		{
			$my=$Core->user && $topic['author_id']==$Core->user['id'] || in_array($topic['id'],$Core->GuestSign('t'));
			if($topic['status']==1 or $topic['status']==-1 and $my or in_array(1,$moder['chstatust']))
			{
				if($topic['state']=='open' || $topic['state']=='close' && in_array(1,$rights['canclose']))
				{
					if($my)
					{
						$r['close']=in_array(1,$rights['close']);
						$r['read']=in_array(1,$rights['topics']);
						$r['post']=$r['read'] ? in_array(1,$rights['post']) : false;

						if(in_array(0,$rights['editlimit']) or time()-strtotime($topic['created'])<=max($rights['editlimit']))
						{
							$r['editt']=in_array(1,$rights['editt']);
							$r['deletet']=in_array(1,$rights['deletet']);
						}

						if(in_array(1,$rights['mod']))
							$r['edit']=$r['delete']=true;
					}
					else
					{
						$r['read']=in_array(1,$rights['atopics']);
						$r['post']=$r['read'] ? in_array(1,$rights['apost']) : false;
					}#Вполне логичная ситуация: если мы не можем просмотреть тему, то и отвечать мы в неё не можем.
				}
				else
					$r['read']=$my ? in_array(1,$rights['topics']) : in_array(1,$rights['atopics']);

				if($moder)
				{
					$r['edit']|=in_array(1,$moder['edit']);
					$r['delete']|=in_array(1,$moder['delete']);
					$r['editt']|=in_array(1,$moder['editt']);
					$r['deletet']|=in_array(1,$moder['deletet']);
				}
			}
		}
		return$r;
	}
}