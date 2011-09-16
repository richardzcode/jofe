DROP TABLE IF EXISTS `jos_blog_posts`;
CREATE TABLE `jos_blog_posts` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`author_id` int(10) unsigned NOT NULL,
	`title` varchar(255) NOT NULL,
	`content` text NOT NULL DEFAULT '',
	`created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);

DROP TABLE IF EXISTS `jos_blog_comments`;
CREATE TABLE `jos_blog_comments` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`post_id` int(10) unsigned NOT NULL,
	`name` varchar(255) NOT NULL,
	`content` text NOT NULL DEFAULT '',
	`created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);