ALTER TABLE {{%irc_notice}} ADD COLUMN ino_target_usr_id int(11) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE {{%irc_notice}} ADD COLUMN ino_category varchar(25) NULL DEFAULT NULL;
