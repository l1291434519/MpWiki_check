<?php
require_once "./class/class.phpmailer.php";
require_once "./class/class.smtp.php";
require_once "./class/Git.php";

const BASE_URL_MP = 'http://mp.weixin.qq.com/wiki/home/';
const BASE_URL_QY = 'http://qydev.weixin.qq.com/wiki/';
const SESSION_MP = 'mpwiki';
const SESSION_QY = 'qywiki';
const SESSION_NOTICE = 'notice';

defined('WIKI_DIR_MP') or define('WIKI_DIR_MP',isset($_ENV["WIKI_DIR_MP"])?$_ENV["WIKI_DIR_MP"]:'./wiki/mp/');
defined('WIKI_DIR_QY') or define('WIKI_DIR_QY',isset($_ENV["WIKI_DIR_QY"])?$_ENV["WIKI_DIR_QY"]:'./wiki/qy/');
defined('NOTICE_DIR') or define('NOTICE_DIR',isset($_ENV["NOTICE_DIR"])?$_ENV["NOTICE_DIR"]:'./wiki/notice/');

defined('REMOTE_GIT') or define('REMOTE_GIT',isset($_ENV["REMOTE_GIT"])?$_ENV["REMOTE_GIT"]:''); //总库，如果使用这个就不使用分开的
defined('REMOTE_GIT_MP') or define('REMOTE_GIT_MP',isset($_ENV["REMOTE_GIT_MP"])?$_ENV["REMOTE_GIT_MP"]:'');
defined('REMOTE_GIT_QY') or define('REMOTE_GIT_QY',isset($_ENV["REMOTE_GIT_QY"])?$_ENV["REMOTE_GIT_QY"]:'');
defined('REMOTE_GIT_NOTICE') or define('REMOTE_GIT_NOTICE',isset($_ENV["REMOTE_GIT_NOTICE"])?$_ENV["REMOTE_GIT_NOTICE"]:'');

defined('MAIL_LOCK_MP') or define('MAIL_LOCK_MP','./send_mail_mp.lock');
defined('MAIL_LOCK_QY') or define('MAIL_LOCK_QY','./send_mail_qy.lock');
defined('MAIL_LOCK_NOTICE') or define('MAIL_LOCK_NOTICE','./send_mail_notice.lock');
defined('WORK_LOCK_MP') or define('WORK_LOCK_MP','./working_mp.lock');
defined('WORK_LOCK_QY') or define('WORK_LOCK_QY','./working_qy.lock');
defined('WORK_LOCK_NOTICE') or define('WORK_LOCK_NOTICE','./working_notice.lock');

defined('GIT_EMAIL') or define('GIT_EMAIL',isset($_ENV["GIT_EMAIL"])?$_ENV["GIT_EMAIL"]:'binsee@163.com');
defined('GIT_NAME') or define('GIT_NAME',isset($_ENV["GIT_NAME"])?$_ENV["GIT_NAME"]:'binsee');

defined('IS_WIN') or define('IS_WIN',(substr(PHP_OS,0,3)=='WIN'));

?>