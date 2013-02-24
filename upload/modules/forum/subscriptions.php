<?php
/*
	Copyright © Eleanor CMS
	URL: http://eleanor-cms.ru, http://eleanor-cms.com
	E-mail: support@eleanor-cms.ru
	Developing: Alexander Sunvas*
	Interface: Rumin Sergey
	=====
	*Pseudonym
*/

class ForumSubscriptions extends Forum
{	public function SubscribeTopic($tid,$uid,$type)
	{		if(in_array($type,array('i','d','w','m','y')))
		{
			switch($type)
			{
				case'd':
					$pl='+INTERVAL 1 DAY';
				break;
				case'w':
					$pl='+INTERVAL 1 WEEK';
				break;
				case'm':
					$pl='+INTERVAL 1 MONTH';
				break;
				case'y':
					$pl='+INTERVAL 1 YEAR';
				break;
				default:
					$pl='';
			}
			$a=array('uid'=>$uid,'!lastview'=>'NOW()','intensity'=>$type,'!nextsend'=>'NOW()'.$pl);
			if(is_array($tid))
			{				$cnt=count($tid);
				foreach($a as &$v)
					$v=array_fill(0,$cnt,$v);
			}
			$a['t']=$tid;
			Eleanor::$Db->Query('INSERT INTO `'.$this->config['ts'].'`'.Eleanor::$Db->GenerateInsert($a).' ON DUPLICATE KEY UPDATE `intensity`=\''.$type.'\'');
		}
		else
			Eleanor::$Db->Delete($this->config['ts'],'`uid`='.$uid.' AND 't''.Eleanor::$Db->In($tid).' LIMIT 1');	}

	public function SubscribeForum($fid,$lang,$uid,$type)
	{
		if(in_array($type,array('i','d','w','m','y')))
		{
			switch($type)
			{
				case'd':
					$pl='+INTERVAL 1 DAY';
				break;
				case'w':
					$pl='+INTERVAL 1 WEEK';
				break;
				case'm':
					$pl='+INTERVAL 1 MONTH';
				break;
				case'y':
					$pl='+INTERVAL 1 YEAR';
				break;
				default:
					$pl='';
			}
			$a=array('uid'=>$uid,'language'=>$lang,'!lastview'=>'NOW()','intensity'=>$type,'!nextsend'=>'NOW()'.$pl);
			if(is_array($fid))
			{
				$cnt=count($fid);
				foreach($a as &$v)
					$v=array_fill(0,$cnt,$v);
			}
			$a['f']=$fid;
			Eleanor::$Db->Query('INSERT INTO `'.$this->config['fs'].'`'.Eleanor::$Db->GenerateInsert($a).' ON DUPLICATE KEY UPDATE `intensity`=\''.$type.'\'');
		}
		else
			Eleanor::$Db->Delete($this->config['fs'],'`uid`='.$uid.' AND `f`'.Eleanor::$Db->In($fid).' AND `language`=\''.Eleanor::$Db->Escape($lang).'\' LIMIT 1');
	}
	public function SendForums($fids=array(),$slimit=100)
	{		$chtz='';
		$cache=array();
		$Eleanor=Eleanor::getInstance();
		$R=Eleanor::$Db->Query('SELECT `s`.`f`,`s`.`uid`,`s`.`language`,UNIX_TIMESTAMP(`s`.`lastview`) `lastview`,UNIX_TIMESTAMP(`s`.`lastsend`) `lastsend`,`s`.`intensity`,`u`.`email`,`u`.`groups`,`u`.`name`,`u`.`language` `ulanguage`,`u`.`timezone` FROM `'.$this->config['fs'].'` `s` INNER JOIN `'.$this->config['fl'].'` `fl` ON `s`.`f`=`fl`.`id` AND `s`.`language`=`fl`.`language` AND `fl`.`lp_date`>`s`.`lastview` INNER JOIN `'.P.'users_site` `u` ON `u`.`id`=`s`.`uid` WHERE `s`.`sent`=0 AND `s`.`nextsend`<=\''.date('Y-m-d H:i:s').'\''.($fids ? '`s`.`f`'.Eleanor::$Db->In($fids) : '').' LIMIT '.$slimit);		while($a=$R->fetch_assoc())
		{			$lang=$a['ulanguage'] ? $a['ulanguage'] : Language::$main;			if(!isset($l[$lang]))
				$l[$lang]=include dirname(__file__).'/letters-'.$lang.'.php';
			$nl=!$a['language'] || $a['language']==LANGUAGE;
			$uadd=array('lang'=>$nl ? false : Eleanor::$langs[$a['language']]['uri'],'module'=>$Eleanor->module['name']);
			#Ради UserLink
			$Eleanor->Url->special=$nl ? '' : $Eleanor->Url->Construct(array('lang'=>Eleanor::$langs[$a['language']]['uri']),false,false);
			$slimit--;
			if($a['timezone']!=$chtz)
			{				date_default_timezone_set($a['timezone'] ? $a['timezone'] : Eleanor::$vars['time_zone']);
				Eleanor::$Db->SyncTimeZone();
				$chtz=$a['timezone'];			}
			if($this->CheckForumAccess($a['f'],$a['groups'] ? explode(',,',trim($a['groups'],',')) : array()))
			{				$lastview=date('Y-m-d H:i:s');
				$fl=$uadd+$this->Core->Forums->GetUrl($a['f']);
				if($a['intensity']=='i')
				{					$R=Eleanor::$Db->Query('SELECT `id`,`uri`,`title`,`author`,`author_id`,`created` FROM `'.$this->config['ft'].'` WHERE `f`='.$a['f'].' AND `language`=\''.$a['language'].'\' AND `status`=1 AND `created`>=FROM_UNIXTIME(\''.$a['lastview'].'\') AND `author_id`!='.$a['uid'].' ORDER BY `created` ASC LIMIT 1');
					if($temp=$R->fetch_assoc())
					{						$lastview=$temp['created'];
						if(!isset($cache[$temp['id']]))
						{							$R=Eleanor::$Db->Query('SELECT `text` FROM `'.$this->config['fp'].'` WHERE 't'='.$temp['id'].' AND `status`=1 ORDER BY `sortdate` ASC LIMIT 1');
							if($R->num_rows()==0)
								continue;
							list($cache[$temp['id']])=Eleanor::$Db->fetch_row();
							$cache[$temp['id']]=Strings::CutStr(strip_tags(OwnBB::Parse($cache[$temp['id']])),500);
						}
						$repl=array(
							'site'=>Eleanor::$vars['site_name'],
							'sitelink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path,
							'topiclink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($Eleanor->Url->furl && $temp['uri'] ? $fl+array('t'=>$temp['uri']) : $uadd+array('t'=>array('t'=>$temp['id']))),
							'forumlink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($fl,true,'/'),
							'author'=>$temp['author'],
							'authorlink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.Eleanor::$Login->UserLink($temp['author'],$temp['author_id']),
							'created'=>call_user_func(array($lang,'Date'),$temp['created'],'fdt'),
							'lastview'=>$a['lastview'] ? call_user_func(array($lang,'Date'),$a['lastview'],'fdt') : false,
							'lastsend'=>$a['lastsend'] ? call_user_func(array($lang,'Date'),$a['lastsend'],'fdt') : false,
							'forum'=>$this->Core->Forums->dump[$a['f']]['title'],
							'title'=>$temp['title'],
							'text'=>$cache[$temp['id']],
							'name'=>htmlspecialchars($a['name'],ELENT,CHARSET),
							'cancel'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($uadd+array('do'=>'cancelsf','id'=>$a['f']),true,''),
						);
						Eleanor::Mail(
							$a['email'],
							Eleanor::ExecHtmlLogic($l[$lang]['subsfi_t'],$repl),
							Eleanor::ExecHtmlLogic($l[$lang]['subsfi'],$repl)
						);
					}				}
				else
				{					$R=Eleanor::$Db->Query('SELECT COUNT(`f`) `cnt` FROM `'.$this->config['ft'].'` WHERE `f`='.$a['f'].' AND `status`=1 AND `language`=\''.$a['language'].'\' AND `created`>=FROM_UNIXTIME(\''.$a['lastsend'].'\')');
					if($temp=$R->fetch_assoc())
					{
						$repl=array(
							'site'=>Eleanor::$vars['site_name'],
							'sitelink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path,
							'forumlink'=>$fl=PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($fl,true,'/'),
							'cblink'=>$fl.($Eleanor->Url->furl ? '?' : '&amp;').Url::Query(array('filter'=>array('cb'=>date('Y-m-d H:i:s',$a['lastview'])))),
							'forum'=>$this->Core->Forums->dump[$a['f']]['title'],
							'cnt'=>$temp['cnt'],
							'lastview'=>$a['lastview'] ? call_user_func(array($lang,'Date'),$a['lastview'],'fdt') : false,
							'lastsend'=>$a['lastsend'] ? call_user_func(array($lang,'Date'),$a['lastsend'],'fdt') : false,
							'name'=>htmlspecialchars($a['name'],ELENT,CHARSET),
							'cancel'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($uadd+array('do'=>'cancelsf','id'=>$a['f'],'lang'=>$a['language'] ? $a['language'] : false),true,''),
						);
						Eleanor::Mail(
							$a['email'],
							Eleanor::ExecHtmlLogic($l[$lang]['subsf_t'],$repl),
							Eleanor::ExecHtmlLogic($l[$lang]['subsf'],$repl)
						);
					}
				}
				switch($a['intensity'])
				{
					case'd':
						$pl='+ INTERVAL 1 DAY';
					break;
					case'w':
						$pl='+ INTERVAL 1 WEEK';
					break;
					case'm':
						$pl='+ INTERVAL 1 MONTH';
					break;
					case'y':
						$pl='+ INTERVAL 1 YEAR';
					break;
					default:
						$pl='';
				}
				Eleanor::$Db->Update($this->config['fs'],array('sent'=>1,'!lastsend'=>'NOW()','!nextsend'=>'NOW()'.$pl),'`f`='.$a['f'].' AND `uid`='.$a['uid'].' AND `language`=\''.$a['language'].'\' LIMIT 1');
			}
			else
				Eleanor::$Db->Delete($this->config['fs'],'`f`='.$a['f'].' AND `uid`='.$a['uid'].' AND `language`=\''.$a['language'].'\'');		}
		if($chtz)
		{
			date_default_timezone_set(Eleanor::$vars['time_zone']);
			Eleanor::$Db->SyncTimeZone();
		}
		return $slimit>0;
	}

	public function SendTopics($tids=array(),$slimit=100)
	{
		$chtz='';
		$cache=array();
		$Eleanor=Eleanor::getInstance();
		$R=Eleanor::$Db->Query('SELECT `s`.'t',`s`.`uid`,UNIX_TIMESTAMP(`s`.`lastview`) `lastview`,`s`.`lastsend`,`s`.`intensity`,`u`.`email`,`u`.`groups`,`u`.`name`,`u`.`language`,`u`.`timezone`,`ft`.`f`,`ft`.`uri`,`ft`.`status`,`ft`.`author_id`,`ft`.`title` FROM `'.$this->config['ts'].'` `s` INNER JOIN `'.$this->config['ft'].'` `ft` ON `s`.'t'=`ft`.`id` AND `ft`.`lp_date`>`s`.`lastview` INNER JOIN `'.P.'users_site` `u` ON `u`.`id`=`s`.`uid` WHERE (`lp_date`<\''.date('Y-m-d H:i:s').'\' OR `ft`.`pinned`>`s`.`lastview`) AND `s`.`sent`=0 AND `s`.`nextsend`<=\''.date('Y-m-d H:i:s').'\''.($tids ? '`s`.'t''.Eleanor::$Db->In($tids) : '').' LIMIT '.$slimit);
		while($a=$R->fetch_assoc())
		{
			$lang=$a['language'] ? $a['language'] : Language::$main;
			if(!isset($l[$lang]))
				$l[$lang]=include dirname(__file__).'/letters-'.$lang.'.php';
			$nl=!$a['language'] || $a['language']==LANGUAGE;
			$uadd=array('lang'=>$nl ? false : Eleanor::$langs[$a['language']]['uri'],'module'=>$Eleanor->module['name']);
			#Ради UserLink
			$Eleanor->Url->special=$nl ? '' : $Eleanor->Url->Construct(array('lang'=>Eleanor::$langs[$a['language']]['uri']),false,false);
			$slimit--;
			if($a['timezone']!=$chtz)
			{
				date_default_timezone_set($a['timezone'] ? $a['timezone'] : Eleanor::$vars['time_zone']);
				Eleanor::$Db->SyncTimeZone();
				$chtz=$a['timezone'];
			}
			if($this->CheckTopicAccess($a))
			{
				$lastview=date('Y-m-d H:i:s');
				$fl=$uadd+$this->Core->Forums->GetUrl($a['f']);
				$R=Eleanor::$Db->Query('SELECT `id`,`author`,`author_id`,`created`,`text` FROM `'.$this->config['fp'].'` WHERE 't'='.$a['t'].' AND `status`=1 AND `sortdate`>FROM_UNIXTIME(\''.$a['lastview'].'\') AND `author_id`!='.$a['uid'].' ORDER BY `sortdate` ASC LIMIT 1');
				if($post=$R->fetch_assoc())
					if($a['intensity']=='i')
					{
						$lastview=$post['created'];
						if(!isset($cache[$post['id']]))
							$cache[$post['id']]=Strings::CutStr(strip_tags(OwnBB::Parse($post['text'])),500);
						$repl=array(
							'site'=>Eleanor::$vars['site_name'],
							'sitelink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path,
							'topiclink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($Eleanor->Url->furl && $a['uri'] ? $fl+array('t'=>$a['uri']) : $uadd+array('t'=>array('t'=>$a['t']))),
							'forumlink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($fl,true,'/'),
							'postlink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($uadd+array('do'=>'findpost','id'=>$post['id']),true,''),
							'author'=>$post['author'],
							'authorlink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.Eleanor::$Login->UserLink($post['author'],$post['author_id']),
							'created'=>call_user_func(array($lang,'Date'),$post['created'],'fdt'),
							'lastview'=>$a['lastview'] ? call_user_func(array($lang,'Date'),$a['lastview'],'fdt') : false,
							'lastsend'=>$a['lastsend'] ? call_user_func(array($lang,'Date'),$a['lastsend'],'fdt') : false,
							'forum'=>$this->Core->Forums->dump[$a['f']]['title'],
							'title'=>$a['title'],
							'text'=>$cache[$post['id']],
							'name'=>htmlspecialchars($a['name'],ELENT,CHARSET),
							'cancel'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($uadd+array('do'=>'cancelst','id'=>$a['t']),true,''),
						);
						Eleanor::Mail(
							$a['email'],
							Eleanor::ExecHtmlLogic($l[$lang]['substi_t'],$repl),
							Eleanor::ExecHtmlLogic($l[$lang]['substi'],$repl)
						);
					}
					else
					{
						$R=Eleanor::$Db->Query('SELECT COUNT('t') `cnt` FROM `'.$this->config['fp'].'` WHERE 't'='.$a['t'].' AND `status`=1 AND `sortdate`>FROM_UNIXTIME(\''.$a['lastsend'].'\')');
						if($temp=$R->fetch_assoc())
						{
							$repl=array(
								'site'=>Eleanor::$vars['site_name'],
								'sitelink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path,
								'topiclink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($Eleanor->Url->furl && $a['uri'] ? $fl+array('t'=>$a['uri']) : $uadd+array('t'=>array('t'=>$a['t']))),
								'forumlink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($fl,true,'/'),
								'postlink'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($uadd+array('do'=>'findpost','id'=>$post['id']),true,''),
								'forum'=>$this->Core->Forums->dump[$a['f']]['title'],
								'cnt'=>$temp['cnt'],
								'lastview'=>$a['lastview'] ? call_user_func(array($lang,'Date'),$a['lastview'],'fdt') : false,
								'lastsend'=>$a['lastsend'] ? call_user_func(array($lang,'Date'),$a['lastsend'],'fdt') : false,
								'name'=>htmlspecialchars($a['name'],ELENT,CHARSET),
								'title'=>$a['title'],
								'cancel'=>PROTOCOL.Eleanor::$domain.Eleanor::$site_path.$Eleanor->Url->Construct($uadd+array('do'=>'cancelsf','id'=>$a['id']),true,''),
							);
							Eleanor::Mail(
								$a['email'],
								Eleanor::ExecHtmlLogic($l[$lang]['subst_t'],$repl),
								Eleanor::ExecHtmlLogic($l[$lang]['subst'],$repl)
							);
						}
					}
				switch($a['intensity'])
				{
					case'd':
						$pl='+ INTERVAL 1 DAY';
					break;
					case'w':
						$pl='+ INTERVAL 1 WEEK';
					break;
					case'm':
						$pl='+ INTERVAL 1 MONTH';
					break;
					case'y':
						$pl='+ INTERVAL 1 YEAR';
					break;
					default:
						$pl='';
				}
				Eleanor::$Db->Update($this->config['ts'],array('sent'=>1,'!lastsend'=>'NOW()','!nextsend'=>'NOW()'.$pl),''t'='.$a['t'].' AND `uid`='.$a['uid'].' LIMIT 1');
			}
			else
				Eleanor::$Db->Delete($this->config['ts'],'`f`='.$a['f'].' AND `uid`='.$a['uid']);
		}
		if($chtz)
		{
			date_default_timezone_set(Eleanor::$vars['time_zone']);
			Eleanor::$Db->SyncTimeZone();
		}
		return $slimit>0;
	}

	public function ForumAccess($f,$groups)
	{		$rights=array();
		foreach($groups as &$g)
		{
			$fr=$this->Core->GroupPerms($f,$g);
			foreach($fr as $k=>&$v)
				$rights[$k][]=$v;
		}
		if(isset($this->Forums->dump[$f]))
			return$rights;
	}

	public function CheckForumAccess($f,$groups)
	{		$r=$this->ForumAccess($f,$groups);
		return $r && in_array(1,$r['access']);
	}

	public function CheckTopicAccess($a)
	{		$r=$this->ForumAccess($a['f'],$a['groups'] ? explode(',,',trim($a['groups'],',')) : array());
		return$r && in_array(1,$r['access']) && in_array(1,$r['read']) && (in_array(1,$r['topics']) && $a['uid']==$a['author_id'] && $a['status']!=0 || in_array(1,$r['atopics']) && $a['uid']!=$a['author_id'] && $a['status']==1);	}
}