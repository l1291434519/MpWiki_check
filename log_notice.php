<?php
require_once "./Base.php";

$git_dir = NOTICE_DIR;
if (!file_exists($git_dir.'.git')) {
    echo date("Y-m-d H:i:s") . " 未创建git库。";
    exit;
}
$ret = get_lastlog($git_dir);
echo "前3次的git日志内容如下：<br><hr>".nl2br(htmlspecialchars($ret));

?>