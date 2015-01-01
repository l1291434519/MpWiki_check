<?php
require_once "./Base.php";
ob_implicit_flush(true);
echo "微信企业号平台wiki更新检测：<br>";

$ret = get_update(SESSION_QY,WORK_LOCK_QY,BASE_URL_QY,WIKI_DIR_QY,MAIL_LOCK_QY,(REMOTE_GIT_QY?REMOTE_GIT_QY:REMOTE_GIT),(REMOTE_GIT_QY?'':SESSION_QY));
//如果没有设置独立git库，则使用共用git库，同时使用独立分支进行提交

if ($ret) {
    echo "<br>已更新，将尝试发送通知邮件.<br>";
	send_mail(WIKI_DIR_QY,MAIL_LOCK_QY,"监测通知：微信企业号平台WIKI更新");
}
?>