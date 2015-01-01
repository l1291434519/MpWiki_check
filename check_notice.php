<?php
require_once "./Base.php";
ob_implicit_flush(true);
echo "微信公众号平台、企业号平台公告更新检测：<br>";

$ret = get_update_notice(SESSION_NOTICE,WORK_LOCK_NOTICE,NOTICE_DIR,MAIL_LOCK_NOTICE,REMOTE_GIT_NOTICE);

if ($ret) {
    echo "<br>已更新，将尝试发送通知邮件.<br>";
    send_mail(NOTICE_DIR,MAIL_LOCK_NOTICE,"监测通知：微信公众号平台、企业号平台公告更新");
}
?>