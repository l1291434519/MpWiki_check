<?php
require_once "./Base.php";
ob_implicit_flush(true);
echo "微信公众号平台wiki更新检测：<br>";

$ret = get_update(SESSION_MP,WORK_LOCK_MP,BASE_URL_MP,WIKI_DIR_MP,MAIL_LOCK_MP,REMOTE_GIT_MP);

if ($ret) {
    echo "<br>已更新，将尝试发送通知邮件.<br>";
	send_mail(WIKI_DIR_MP,MAIL_LOCK_MP,"监测通知：微信公众平台WIKI更新");
}
?>