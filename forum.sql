SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE `el_forums` (
	`id` smallint unsigned NOT NULL auto_increment,
	`parent` smallint unsigned NOT NULL,
	`parents` varchar(50) NOT NULL,
	`pos` smallint unsigned NOT NULL,
	`is_category` tinyint NOT NULL,
	`inc_posts` tinyint NOT NULL,
	`reputation` tinyint NOT NULL,
	`image` varchar(50) NOT NULL,
	`moderators` tinytext NOT NULL,
	`permissions` text NOT NULL,
	`hide_attach` tinyint NOT NULL,
	`prefixes` TINYTEXT NOT NULL,
	`date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY  (`id`),
	KEY `parents` (`parents`),
	KEY `parent` (`parent`)
) ENGINE=InnoDb DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_files` (
	`id` mediumint unsigned NOT NULL auto_increment,
	`f` smallint unsigned NOT NULL,
	`language` enum('ruassian','english','ukrainian') NOT NULL,
	`t` mediumint unsigned NOT NULL,
	`p` mediumint unsigned NOT NULL,
	`downloads` smallint unsigned NOT NULL,
	`size` varchar(10) NOT NULL,
	`name` tinytext NOT NULL COMMENT 'Original file name',
	`file` tinytext NOT NULL COMMENT 'Real file name on hdd',
	`date` timestamp NOT NULL default '0000-00-00 00:00:00',
	`hash` varchar(32) NOT NULL,
	PRIMARY KEY  (`id`),
	KEY `p` (`p`),
	KEY `t` (`t`),
	KEY `f` (`f`,`language`),
	FOREIGN KEY (`f`) REFERENCES `el_forums` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (`t`) REFERENCES `el_forum_topics` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (`p`) REFERENCES `el_forum_posts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDb DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_complaints` (
	`p` mediumint unsigned NOT NULL,
	`uid` mediumint unsigned NOT NULL,
	`date` timestamp NOT NULL default '0000-00-00 00:00:00',
	`comment` tinytext NOT NULL,
	`note` tinytext NOT NULL,
	PRIMARY KEY (`p`,`uid`),
	FOREIGN KEY (`uid`) REFERENCES `el_users_site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`p`) REFERENCES `el_forum_posts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_subscribers` (
	`f` smallint unsigned NOT NULL,
	`uid` mediumint unsigned NOT NULL,
	`language` enum('russian','ukrainian','english') NOT NULL,
	`sent` tinyint NOT NULL,
	`lastview` timestamp NOT NULL default '0000-00-00 00:00:00',
	`lastsend` timestamp NOT NULL default '0000-00-00 00:00:00',
	`nextsend` timestamp NOT NULL default '0000-00-00 00:00:00',
	`intensity` enum('i','d','w','m','y') NOT NULL,
	`date` timestamp NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY  (`f`,`language`,`uid`),
	KEY `uid` (`uid`),
	KEY `tosend` (`sent`,`nextsend`,`language`,`f`),
	FOREIGN KEY (`uid`) REFERENCES `el_users_site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`f`) REFERENCES `el_forums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDb DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_groups` (
	`id` smallint unsigned NOT NULL,
	`image` varchar(50) NOT NULL,
	`grow_to` smallint unsigned NULL default 0,
	`grow_after` smallint unsigned NULL default 0,
	`supermod` tinyint NULL default 0,
	`see_hidden_users` tinyint NULL default 0,
	`permissions` text NOT NULL,
	`moderate` tinyint NULL default 0,
	`moderator` tinytext NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id`) REFERENCES `el_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDb DEFAULT CHARSET=utf8;

CREATE TABLE `el_forums_l` (
	`id` smallint unsigned NOT NULL,
	`language` varchar(15) NOT NULL,
	`uri` varchar(100) default NULL,
	`title` varchar(150) NOT NULL,
	`description` text NOT NULL,
	`meta_title` tinytext NOT NULL,
	`meta_descr` tinytext NOT NULL,
	`rules` text NOT NULL,
	`lp_date` timestamp NOT NULL default '0000-00-00 00:00:00',
	`lp_id` mediumint unsigned NOT NULL,
	`lp_title` tinytext NOT NULL,
	`lp_uri` tinytext,
	`lp_author` varchar(25) NOT NULL,
	`lp_author_id` mediumint unsigned DEFAULT NULL,
	`topics` mediumint unsigned NOT NULL,
	`posts` mediumint unsigned NOT NULL,
	`queued_topics` smallint unsigned NOT NULL,
	`queued_posts` smallint unsigned NOT NULL,
	PRIMARY KEY (`id`,`language`),
	KEY `uri` (`uri`),
	KEY `send_subscription` (`id`,`language`,`lp_date`),
	FOREIGN KEY (`id`) REFERENCES `el_forums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`lp_author_id`) REFERENCES `el_users_site` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDb DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_lastpost` (
	`uid` mediumint unsigned NOT NULL,
	`f` smallint unsigned NOT NULL,
	`language` enum('russian','ukrainian','english') NOT NULL,
	`lp_date` timestamp NOT NULL default '0000-00-00 00:00:00',
	`lp_id` mediumint unsigned NOT NULL,
	`lp_title` tinytext NOT NULL,
	`lp_author` varchar(25) NOT NULL,
	`lp_author_id` mediumint unsigned default NULL,
	PRIMARY KEY  (`uid`,`f`,`language`),
	KEY `lp_id` (`lp_id`),
	FOREIGN KEY (`uid`) REFERENCES `el_users_site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`f`) REFERENCES `el_forums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`lp_id`) REFERENCES `el_forum_topics` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (`lp_author_id`) REFERENCES `el_users_site` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDb DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_moders` (
	`id` smallint unsigned NOT NULL auto_increment,
	`users` tinytext NOT NULL,
	`groups` tinytext NOT NULL,
	`date` timestamp NOT NULL default '0000-00-00 00:00:00',
	`descr` text NOT NULL,
	`forums` tinytext NOT NULL,
	`movet` tinyint NOT NULL,
	`move` tinyint NOT NULL,
	`deletet` tinyint NOT NULL,
	`delete` tinyint NOT NULL,
	`editt` tinyint NOT NULL,
	`edit` tinyint NOT NULL,
	`chstatust` tinyint NOT NULL,
	`chstatus` tinyint NOT NULL,
	`pin` tinyint NOT NULL,
	`mmovet` tinyint NOT NULL,
	`mmove` tinyint NOT NULL,
	`mdeletet` tinyint NOT NULL,
	`mdelete` tinyint NOT NULL,
	`user_warn` tinyint NOT NULL,
	`viewip` tinyint NOT NULL,
	`opcl` tinyint NOT NULL,
	`mopcl` tinyint NOT NULL,
	`mpin` tinyint NOT NULL,
	`merget` tinyint NOT NULL,
	`merge` tinyint NOT NULL,
	`editq` tinyint NOT NULL,
	`mchstatust` tinyint NOT NULL,
	`mchstatus` tinyint NOT NULL,
	`editrep` tinyint NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDb DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_posts` (
	`id` mediumint unsigned NOT NULL auto_increment,
	`f` smallint unsigned NOT NULL,
	`language` enum('russian','english','ukrainian') NOT NULL,
	`t` mediumint unsigned NOT NULL,
	`status` tinyint NOT NULL default '1',
	`author` varchar(25) NOT NULL,
	`author_id` mediumint unsigned default NULL,
	`ip` varchar(39) NOT NULL,
	`created` timestamp NOT NULL default '0000-00-00 00:00:00',
	`sortdate` timestamp NOT NULL default '0000-00-00 00:00:00',
	`edited` timestamp NOT NULL default '0000-00-00 00:00:00',
	`edited_by` varchar(25) NOT NULL,
	`edited_by_id` mediumint unsigned default NULL,
	`edit_reason` tinytext NOT NULL,
	`approved` timestamp NOT NULL default '0000-00-00 00:00:00',
	`approved_by` varchar(25) NOT NULL,
	`approved_by_id` mediumint unsigned default NULL,
	`text` mediumtext NOT NULL,
	`last_mod` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `showtopic` (`t`,`status`,`sortdate`),
	KEY `topics_with_my_posts` (`author_id`,`t`),
	FOREIGN KEY (`t`) REFERENCES `el_forum_topics` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (`f`) REFERENCES `el_forums` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (`author_id`) REFERENCES `el_users_site` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
	FOREIGN KEY (`edited_by_id`) REFERENCES `el_users_site` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
	FOREIGN KEY (`approved_by_id`) REFERENCES `el_users_site` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_prefixes` (
	`id` smallint unsigned NOT NULL AUTO_INCREMENT,
	`forums` tinytext NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_prefixes_l` (
	`id` smallint unsigned NOT NULL,
	`language` varchar(15) NOT NULL,
	`title` varchar(50) NOT NULL,
	PRIMARY KEY (`id`,`language`),
	FOREIGN KEY (`id`) REFERENCES `el_forum_prefixes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_reads` (
	`uid` mediumint unsigned NOT NULL,
	`f` smallint unsigned NOT NULL,
	`allread` timestamp NOT NULL default '0000-00-00 00:00:00',
	`topics` mediumtext NOT NULL,
	PRIMARY KEY (`uid`,`f`),
	FOREIGN KEY (`uid`) REFERENCES `el_users_site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`f`) REFERENCES `el_forums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDb DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_reputation` (
	`id` mediumint unsigned NOT NULL auto_increment,
	`from` mediumint unsigned NOT NULL,
	`from_name` varchar(25) NOT NULL,
	`to` mediumint unsigned NOT NULL,
	`p` mediumint unsigned NOT NULL,
	`t` mediumint unsigned NOT NULL,
	`f` smallint unsigned NOT NULL,
	`language` enum('russian','ukrainian','english') NOT NULL,
	`date` timestamp NOT NULL default '0000-00-00 00:00:00',
	`text` tinytext NOT NULL,
	`comment` tinytext NOT NULL,
	`value` tinyint NOT NULL,
	PRIMARY KEY (`id`),
	KEY `to` (`to`),
	KEY `topic_view` (`from`,`p`),
	KEY `p` (`p`),
	KEY `t` (`t`),
	KEY `f` (`f`,`language`),
	FOREIGN KEY (`to`) REFERENCES `el_users_site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`p`) REFERENCES `el_forum_posts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (`t`) REFERENCES `el_forum_topics` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (`f`) REFERENCES `el_forums` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDb DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_topics` (
	`id` mediumint unsigned NOT NULL auto_increment,
	`uri` varchar(50) default NULL,
	`f` smallint unsigned NOT NULL,
	`prefix` smallint(5) unsigned NOT NULL,
	`status` tinyint NOT NULL,
	`language` enum('russian','english','ukrainian') NOT NULL,
	`lrelated` varchar(30) NOT NULL,
	`created` timestamp NOT NULL default '0000-00-00 00:00:00',
	`author` varchar(25) NOT NULL,
	`author_id` mediumint unsigned default NULL,
	`state` enum('moved','closed','open','merged') NOT NULL default 'open',
	`moved_to` mediumint unsigned NOT NULL,
	`moved_to_forum` mediumint unsigned NOT NULL,
	`who_moved` varchar(25) NOT NULL,
	`who_moved_id` mediumint unsigned NOT NULL,
	`when_moved` timestamp NOT NULL default '0000-00-00 00:00:00',
	`trash` mediumint unsigned NOT NULL,
	`title` tinytext NOT NULL,
	`description` tinytext NOT NULL,
	`posts` mediumint unsigned NOT NULL,
	`queued_posts` mediumint unsigned NOT NULL,
	`views` mediumint unsigned NOT NULL,
	`sortdate` timestamp NOT NULL default '0000-00-00 00:00:00',
	`pinned` timestamp NOT NULL default '0000-00-00 00:00:00',
	`lp_date` timestamp NOT NULL default '0000-00-00 00:00:00',
	`lp_id` mediumint unsigned NOT NULL,
	`lp_author` varchar(25) NOT NULL,
	`lp_author_id` mediumint unsigned default NULL,
	`voting` tinyint NOT NULL,
	`last_mod` timestamp NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY (`id`),
	UNIQUE KEY `uri` (`f`,`language`,`uri`),
	KEY `state` (`moved_to`,`state`),
	KEY `mark_forum_read` (`f`,`status`,`lp_date`),
	KEY `view_forum` (`f`,`language`,`status`,`sortdate`),
	KEY `view_forum_with_prefix` (`f`,`language`,`prefix`,`status`,`sortdate`),
	KEY `send_subscription` (`f`,`language`,`status`,`created`),
	KEY `author` (`author_id`,`status`,`f`),
	FOREIGN KEY (`f`) REFERENCES `el_forums` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (`lp_author_id`) REFERENCES `el_users_site` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_topics_subscribers` (
	`t` mediumint unsigned NOT NULL,
	`uid` mediumint unsigned NOT NULL,
	`status` tinyint NOT NULL,
	`sent` tinyint NOT NULL,
	`lastview` timestamp NOT NULL default '0000-00-00 00:00:00',
	`lastsend` timestamp NOT NULL default '0000-00-00 00:00:00',
	`nextsend` timestamp NOT NULL default '0000-00-00 00:00:00',
	`intensity` enum('i','d','w','m','y') NOT NULL,
	`date` timestamp NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY (`t`,`uid`),
	KEY `uid` (`uid`),
	KEY `tosend` (`status`,`sent`,`nextsend`,`t`),
	FOREIGN KEY (`uid`) REFERENCES `el_users_site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`t`) REFERENCES `el_forum_topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_tasks` (
	`id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`type` VARCHAR( 10 ) NOT NULL ,
	`start` TIMESTAMP NOT NULL DEFAULT 0,
	`date` TIMESTAMP NOT NULL DEFAULT 0,
	`finish` TIMESTAMP NOT NULL DEFAULT 0,
	`status` ENUM(  'wait',  'process',  'done',  'error' ) NOT NULL,
	`options` TEXT NOT NULL,
	`data` TEXT NOT NULL,
	`done` MEDIUMINT UNSIGNED NOT NULL ,
	`total` MEDIUMINT UNSIGNED NOT NULL,
	INDEX (`status`, `date`),
	INDEX (`type`,`date`)
) ENGINE = INNODB DEFAULT CHARSET=utf8;

CREATE TABLE `el_forum_users` (
	`id` mediumint unsigned NOT NULL,
	`posts` smallint unsigned NOT NULL,
	`statustext` varchar(15) NOT NULL,
	`restrict_post` tinyint NOT NULL,
	`restrict_post_to` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`rep` smallint unsigned default NULL,
	`reputation` text NOT NULL,
	`descr` text NOT NULL,
	`allread` datetime NOT NULL,
	`hidden` tinyint NOT NULL,
	`moderate` tinyint NOT NULL,
	`moderator` tinytext NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id`) REFERENCES `el_users_site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDb DEFAULT CHARSET=utf8;

INSERT INTO `el_forums` (`id`,`parent`,`parents`,`pos`,`is_category`,`inc_posts`,`reputation`,`image`,`moderators`,`permissions`,`hide_attach`) VALUES
(1, 0, '', 1, 1, 1, 1, '', '', '', 0),
(2, 1, '1,', 1, 0, 1, 1, 'clients.png', '', '', 0),
(3, 2, '1,2,', 1, 0, 1, 1, 'earth.png', '', '', 0),

(4, 0, '', 2, 0, 1, 1, 'fortress.png', '', '', 0),
(5, 4, '4,', 1, 0, 1, 1, 'gauntlet.png', '', '', 0),
(6, 0, '', 3, 0, 1, 1, '', '', '', 0),
(7, 0, '', 4, 0, 0, 0, 'spam.png', '', 'a:5:{i:3;a:1:{s:6:"access";b:0;}i:6;a:1:{s:6:"access";b:0;}i:5;a:1:{s:6:"access";b:0;}i:4;a:1:{s:6:"access";b:0;}i:2;a:1:{s:6:"access";b:0;}}', 0);

INSERT INTO `el_forums_l` (`id`,`language`,`uri`,`title`,`description`,`meta_title`,`meta_descr`, `rules`,`lp_date`,`lp_id`,`lp_title`,`lp_uri`,`lp_author`,`topics`,`posts`,`queued_topics`,`queued_posts`) VALUES
(1, '', 'основная-категория', 'Основная категория', '', '', '', '', '0000-00-00 00:00:00', 0, '', '', '', 0, 0, 0, 0),
(2, '', 'форум-в-категории', 'Форум в категории', 'Описание форума', '', '', '...Правила форума...', NOW(), 1, 'Первая тема', 'первая-тема', 'Admin', 1, 1, 0, 0),
(3, '', 'подфорум', 'Подфорум', '', '', '', '', '0000-00-00 00:00:00', 0, '', '', '', 0, 0, 0, 0),

(4, '', 'форум-вне-категории', 'Форум вне категории', '', '', '', '', '0000-00-00 00:00:00', 0, '', '', '', 0, 0, 0, 0),
(5, '', 'подфорум-2', 'Подфорум2', '', '', '', '', '0000-00-00 00:00:00', 0, '', '', '', 0, 0, 0, 0),
(6, '', 'без-картинки', 'Форум без картинки', '', '', '', '', '0000-00-00 00:00:00', 0, '', '', '', 0, 0, 0, 0),
(7, '', 'корзина', 'Корзина', 'Мусорник', '', '', '', '0000-00-00 00:00:00', 0, '', '', '', 0, 0, 0, 0);

INSERT INTO `el_forum_lastpost` (`uid`, `f`, `language`, `lp_date`, `lp_id`, `lp_title`, `lp_author`, `lp_author_id`) VALUES
(1, 2, '', NOW(), 4, 'Первая тема', 'Admin', 1);

INSERT INTO `el_forum_posts` (`id`,`f`,`language`,`t`,`status`,`author`,`author_id`,`ip`,`created`,`sortdate`,`text`,`last_mod`) VALUES
(1, '', 2, 1, 1, 'Admin', 1, '127.0.0.1', NOW(), NOW(), 'Это первое сообщение на Вашем новом форуме. Forum <a href="http://eleanor-cms.ru">Eleanor CMS</a>', NOW());

INSERT INTO `el_forum_topics` (`id`,`uri`,`f`,`status`,`language`,`lrelated`,`created`,`author`,`author_id`,`state`,`moved_to`,`moved_to_forum`,`who_moved`, `who_moved_id`,`when_moved`, `trash`,`title`,`description`,`posts`,`queued_posts`,`views`,`pinned`,`lp_date`,`lp_id`,`lp_author`,`lp_author_id`,`voting`) VALUES
(1, 'первая-тема', 2, 1, '', '', NOW(), 'Admin', 1, 'open', 0, 0, '', 0, '0000-00-00 00:00:00', 0, 'Первая тема', '', 0, 0, 0, NULL, NOW(), 1, 'Admin', 1, 0);

INSERT INTO `el_forum_prefixes` (`id`, `forums`) VALUES
(1, ',2,');

INSERT INTO `el_forum_prefixes_l` (`id`, `language`, `title`) VALUES
(1, '', 'Демо-префикс');