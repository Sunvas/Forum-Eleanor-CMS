ALTER TABLE `lf_forum_files` ADD `date` TIMESTAMP NOT NULL DEFAULT 0 AFTER `file` ;
ALTER TABLE `lf_forum_topics_subscribers` ADD `date` TIMESTAMP NOT NULL DEFAULT 0;
ALTER TABLE `lf_forum_subscribers` ADD `date` TIMESTAMP NOT NULL DEFAULT 0;