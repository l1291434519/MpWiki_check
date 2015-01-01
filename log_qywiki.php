<?php
require_once "./Base.php";
echo "微信企业号平台wiki更新检测日志：<br>";

$git_dir = WIKI_DIR_QY;
if (!file_exists($git_dir.'.git')) {
    echo date("Y-m-d H:i:s") . " 未创建git库。";
    exit;
}
$ret = get_lastlog($git_dir,3);
echo "前3次git的提交日志内容如下：<br><hr>".nl2br(htmlspecialchars($ret));

?>