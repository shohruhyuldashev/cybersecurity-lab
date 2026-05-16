<?php
class JConfig {
	public $offline = '0';
	public $offline_message = 'This site is down for maintenance.<br />Please check back again soon.';
	public $display_offline_message = '1';
	public $sitename = 'HAAD Corp - Intranet Portal';
	public $editor = 'tinymce';
	public $captcha = '0';
	public $list_limit = '20';
	public $access = '1';
	public $dbtype = 'mysqli';
	public $host = 'db-joomla';
	public $user = 'joomla_user';
	public $password = 'S3cur3J00ml4P4ssw0rd!';
	public $db = 'joomla';
	public $dbprefix = 'jos_';
	public $secret = '8Kj3LpM9nBv4C2x1';
	public $error_reporting = 'default';
	public $helpurl = 'https://help.joomla.org/proxy?keyref=Help{major}{minor}:{alias}';
	public $ftp_enable = '0';
	public $offset = 'UTC';
	public $mailonline = '1';
	public $mailer = 'mail';
	public $mailfrom = 'admin@haad.local';
	public $fromname = 'HAAD Corp';
	public $sendmail = '/usr/sbin/sendmail';
	public $smtpauth = '0';
	public $smtphost = 'localhost';
	public $smtpport = '25';
	public $caching = '0';
	public $cache_handler = 'file';
	public $cachetime = '15';
	public $log_path = '/var/www/html/administrator/logs';
	public $tmp_path = '/var/www/html/tmp';
	public $lifetime = '15';
	public $session_handler = 'database';
}
