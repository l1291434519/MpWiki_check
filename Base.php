<?php
require_once "./conf.php";

function logg($text,$file='./log.txt'){
    file_put_contents($file,$text."\r\n",FILE_APPEND);
};

function write($file,$text){
    file_put_contents(IS_WIN?mb_convert_encoding($file,'GBK','UTF-8'):$file,$text);
};

function mk_dir($dir)
{
    if (is_dir($dir) || @mkdir($dir,0777)) return true;
    if (!mk_dir(dirname($dir))) return false;
    return @mkdir($dir,0777);
}

function ls_file($dir)
{
    if (!is_dir($dir)) return false;
    return @scandir($dir);
}

function http_get($url){
    $oCurl = curl_init();
    if(stripos($url,"https://")!==FALSE){
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($oCurl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($oCurl, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($oCurl, CURLOPT_AUTOREFERER, true);
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    curl_close($oCurl);
    if(intval($aStatus["http_code"])==200){
        return $sContent;
    }else{
        return false;
    }
}


function json($arr) {
    $parts = array ();
    $is_list = false;
    //Find out if the given array is a numerical array
    $keys = array_keys ( $arr );
    $max_length = count ( $arr ) - 1;
    if (($keys [0] === 0) && ($keys [$max_length] === $max_length )) { //See if the first key is 0 and last key is length - 1
        $is_list = true;
        for($i = 0; $i < count ( $keys ); $i ++) { //See if each key correspondes to its position
            if ($i != $keys [$i]) { //A key fails at position check.
                $is_list = false; //It is an associative array.
                break;
            }
        }
    }
    foreach ( $arr as $key => $value ) {
        if (is_array ( $value )) { //Custom handling for arrays
            if ($is_list)
                $parts [] = json ( $value ); /* :RECURSION: */
            else
                $parts [] = '"' . $key . '":' . json ( $value ); /* :RECURSION: */
        } else {
            $str = '';
            if (! $is_list)
                $str = '"' . $key . '":';
            //Custom handling for multiple data types
            if (!is_string ( $value ) && is_numeric ( $value ) && $value<2000000000)
                $str .= $value; //Numbers
            elseif ($value === false)
            $str .= 'false'; //The booleans
            elseif ($value === true)
            $str .= 'true';
            else
                $str .= '"' . addslashes ( $value ) . '"'; //All other things
            // :TODO: Is there any more datatype we should be in the lookout for? (Object?)
            $parts [] = $str;
        }
    }
    $json = implode ( ",\n", $parts );
    if ($is_list)
        return "[\n" . $json . "\n]"; //Return numerical JSON
    return "{\n" . $json . "\n}"; //Return associative JSON
}

function get_list($url) {
    $data = http_get($url);
    $list=array();
    $s1 = '/class=\"portal\"[^>]*id=\'([^\']*)\'[^>]*>.*<\/span>(.*)<\/h5>.*<ul>(.*)<\/ul>/s';    //菜单主项解析正则
    $s2 = '/<li id=\"(.*)\"><a href=\"(.*)\">(.*)<\/a><\/li>/';  //菜单子项解析正则

    /*
     <div class="portal" id='p-.E6.96.B0.E6.89.8B.E6.8E.A5.E5.85.A5'>
    <h5><span class="portal_arrow"></span>新手接入</h5>
    <div class="body">
    <ul>
    <li id="n-.E8.8E.B7.E5.8F.96access_token"><a href="../11/0e4b294685f817b95cbed85ba5e82b8f.html">获取access_token</a></li>
    </ul>
    </div>
    </div>
    */

    $search='/<div id=\"mw-panel\"[^>]*>(.*)<div id=\"(footer|content)\"/s';
    preg_match($search,$data,$arr);                         //获取左菜单列
    $str=isset($arr[1])?$arr[1]:'';
    $arr = explode('</div>',$str);
    foreach ($arr as $str) {
        if (!stripos($str,'portal')) continue;
        $sq=preg_match_all($s1,$str,$arr,PREG_SET_ORDER);                              //解析菜单主项
        $tmp=array();
        if (isset($arr[0]) && count($arr[0])>3) {
            $tmp['p']=$arr[0][1];
            $tmp['title']=trim($arr[0][2]);
            preg_match_all($s2,$arr[0][3],$arr2,PREG_SET_ORDER);           //解析菜单子项
            foreach ($arr2 as $li) {
                if (count($li)>2)
                    $tmp['ul'][]=array(
                            'n' => $li[1],
                            'url' => $li[2],
                            'title' => $li[3]
                    );
            }
            $list[]=$tmp;
        }
    }
    unset($data);
    unset($arr);
    unset($arr2);
    if (isset($list) && count($list)>0)
    	return $list;
    return false;
}

function get_url($base_url,$url) {
    if (stripos($url,'http://')===0 || stripos($url,'https://')===0 ) {
    	return $url;
    }
    $arr = parse_url($base_url);
    $url_base = $arr['scheme'].'://'.$arr['host'];
    switch ($substr($base_url,0,1)) {
    	case '/':
    	   return $url_base.$url;
    	   break;
    	case '.':
    	   return $url_base.$arr['path'].$url;
    	   break;
    	default:
    	   return $url_base.$arr['path'].$url;
    	   break;
    }
}

function get_mp_notice($url = 'https://mp.weixin.qq.com/cgi-bin/announce?action=getannouncementlist&lang=zh_CN') {
    set_time_limit(300);
    $next_page = true;
    $page_num = 1;
    $list = array();
    $tmp_arr = parse_url($url);
    $url_base = $tmp_arr['scheme'].'://'.$tmp_arr['host'];
    $search = '/<div class=\"main_bd\">(.*)<div class=\"pagination_wrp/s';
    $ss = '/<li class=\"mp_news_item\".*href=\"(.*)\" target.*<strong>(.*)     .*<\/strong>.*"read_more">(.*)<\/span>.*<\/li>/';
    //while ($next_page) {
        $data = http_get($url);//.'&start='.$page_num
        preg_match($search,$data,$arr);
        $str = isset($arr[1])?$arr[1]:'';
        if (!stripos($str,'</li>') || !isset($arr[1])) {
            $next_page = false;
        }
        $str = str_replace("</li>","</li>\r\n",$str);
        preg_match_all($ss,$str,$arr2,PREG_SET_ORDER);
        foreach ($arr2 as $li) {
            if (count($li)>2)
                $list[]=array(
                        'date' => $li[3],
                        'title' => trim(str_replace('&nbsp;',' ',htmlspecialchars_decode(substr($li[2],0,stripos($li[2],'     ')+1)))),
                        'url' => ((substr($li[1],0,1)=='/')?$url_base:((substr($li[1],0,1)=='.')?$url_base.$tmp_arr['path']:'')).$li[1]
                );
        }
        //unset($data);
        unset($str);
        unset($arr);
        unset($arr2);
        //echo "访问了".$url.'&start='.$page_num."  增加到".count($list)."个<br>";
        $page_num++;
    //}
    if (isset($list) && count($list)>0)
        return $list;
    echo "MP平台公告页面好像又读取失败了 = .=<br>";
    return false;
}

function get_qy_notice($url = 'https://qy.weixin.qq.com/cgi-bin/homepagenotify?action=list') {
    set_time_limit(300);
    $next_page = true;
    $page_num = 1;
    $list = array();
    $tmp_arr = parse_url($url);
    $url_base = $tmp_arr['scheme'].'://'.$tmp_arr['host'];
    $search = '/(<ul class=\"mod-board-list\">.*<\/ul>)/s';
    $ss = '/<li class=\"mod-board-list__li\".*href=\"(.*)\" class=\"mod-board-list__link\">(.*)<span class=\"mod-board-list__board-tag.*right-text\">(.*)<\/span>.*<\/li>/';
    /*
<li class="mod-board-list__li">
<a href="/cgi-bin/homepagenotify?action=get&amp;id=11" class="mod-board-list__link">企业号降低接入门槛，为认证提速
<span class="mod-board-list__board-tag" style="">new</span></a>
<span class="mod-board-list__right-text">2014-12-17</span></li>
     */
    //while ($next_page) {
        $data = http_get($url);//.'&start='.$page_num
        preg_match($search,$data,$arr);
        $str = isset($arr[1])?$arr[1]:'';
        $str = str_replace("</li>","</li>\r\n",$str);
        preg_match_all($ss,$str,$arr2,PREG_SET_ORDER);
        foreach ($arr2 as $li) {
            if (count($li)>2)
                $list[]=array(
                        'date' => trim($li[3]),
                        'title' => trim(str_replace('&nbsp;',' ',htmlspecialchars_decode($li[2]))),
                        'url' => ((substr($li[1],0,1)=='/')?$url_base:((substr($li[1],0,1)=='.')?$url_base.$tmp_arr['path']:'')).$li[1]
                );
        }
        unset($data);
        unset($str);
        unset($arr);
        unset($arr2);
        //echo "访问了".$url.'&start='.$page_num."  增加到".count($list)."个<br>";
        $page_num++;
    //}
    if (isset($list) && count($list)>0)
        return $list;
    return false;
}

function get_content($url,$search = '/class=\"bodyContent\">(.*)<div class=\"printfooter\">/s') {
    $data = http_get($url);
    preg_match($search,$data,$arr);                        //获取内容
    $str = isset($arr[1])?$arr[1]:'';
    $str_arr = array();
    $start = 0;
    $end = 0;
    $last = 0;
    do {
        $start = strpos($str,'<!--',$last);
        if ($start !== false) {
            $str_arr[] = substr($str, $last, $start - $last);
            $end = strpos($str,'-->',$start);
            if ($end !== false) {
                $end += 3;
                $last = $end;
                $find = true;
            }
        } else {
            $str_arr[] = substr($str, $last, strlen($str) - $last);
            $find = false;
        }
    } while ($find);
    if (count($str_arr) > 0) {
        $str = implode('',$str_arr);
    }
    unset($str_arr);
    unset($data);
    unset($arr);
    if ($str)
        return $str;
    return false;
}

function get_lastlog($git_dir,$num=1) {
    if (IS_WIN) {
        Git::windows_mode();
    }
    $repo = Git::open($git_dir);
    $repo->run('config --global core.quotepath false'); //配置git显示中文不转码
    $ret = $repo->run('log --stat -p -'.($num>0?$num:1) );
    return $ret;
    /*
    if (!file_exists($git_dir.'.git')) {
        echo date("Y-m-d H:i:s") . " 未创建git库。";
        exit;
    }
    echo "前3次的git日志内容如下：<br><hr>".nl2br(htmlspecialchars($ret));
    */
}

function get_update_notice($sname,$file_lock,$path,$mail_lock,$remote_git='',$remote_branch='') {
    session_name($sname);
    session_start();
    set_time_limit(300);
    ignore_user_abort(true);

    $stime = time();
    $work = false;

    //检测、设置工作标志，存在session里（同一个session_name在一个页面未结束前会保持读写锁状态）
    if (empty($_SESSION['working'])) {
        $work = true;
    }
    if (file_exists($file_lock)) {
        $last_work_time = filemtime($file_lock);
        if (($last_work_time > 0) && ((time()- $last_work_time) < 120)) { //上次更新至今有120秒
            echo "检测到正在进行工作中，本页面停止载入，请稍后再次访问。";
            return false;
            //exit;
        }
        $work = true;
        unlink($file_lock);
    } elseif (empty($_SESSION['work_time']) || ($stime - $_SESSION['work_time']) > LOCK_TIME){ //保证 LOCK_TIME 秒内只访问一次
        $work = true;
    }
    if ($work && !file_exists($file_lock)) {
        $_SESSION['working'] = true;
        $_SESSION['work_time'] = $stime;
        file_put_contents($file_lock,$stime);
    } else {
        echo "距离上次获取请求时间间隔多短，请稍后再次访问。";
        return false;
        //exit;
    }
    session_write_close(); //解除session，防止使其他访问页面一直等待session

    echo date("Y-m-d H:i:s") . " 准备中...<br>";
    mk_dir($path);
    if (IS_WIN) {
        Git::windows_mode();
    }
    if (!file_exists($path.'.git')) {
        echo date("Y-m-d H:i:s") . " 没有git库，尝试创建...<br>";
        if ($remote_git){            //是否设置了远程仓库
            echo "尝试从远程仓库克隆数据...<br>";
            $ret=Git::clone_remote($path,$remote_git,$remote_branch); //从远程仓库clone(可指定分支)
            if (!Git::is_repo($ret) || !file_exists($path.'.git') || !$ret){
                echo "从远程仓库克隆失败，本地创建...<br>";
                $ret=Git::create($path); //如果clone失败，则本地创建
            }
        } else
            $ret=Git::create($path); //直接本地创建
        echo date("Y-m-d H:i:s") . " 创建结果：".(Git::is_repo($ret)?'成功':'失败')."<br>";
    }
    mk_dir($path . 'mp/');
    mk_dir($path . 'qy/');
    $files  =ls_file($path);
    foreach ($files as $file) {
        if (!is_dir($path . $file))
            unlink($path . $file);
    }
    $count = 0;
    $ccount = 0;
    $ret_mp = get_mp_notice();
    if ($ret_mp) {
        write($path.'mp_notice.txt',json($ret_mp));
        $count++;
    }
    $ret_qy = get_qy_notice();
    if ($ret_qy) {
        write($path.'qy_notice.txt',json($ret_qy));
        $count++;
    }
    echo date("Y-m-d H:i:s") . " 读取公告列表完毕，开始读取公告内容页...<br>";

    $cover_count = 0;
    if ($ret_mp) { //写出mp平台公告
        foreach ($ret_mp as $arr) {
            $file = (strpos($arr['date'],'-') ? $arr['date'] : date('Y-m-d',$arr['date']));
            $file .=  '#' . $arr['title'] . '.html';
            $file = $path . 'mp/' . preg_replace('/[\/\|*?\\\:<>]/i','_',$file);
            $file_exist = file_exists($file);
            if ($file_exist)
                $cover_count++; //
            if (!$file_exist || $cover_count <= 3) { //只抓取未记录的公告 或已有记录的前3个
                $ret = http_get($arr['url']);
                if ($ret) {
                    write($file, $ret);
                    $ccount++;
                }
            }
        }
    }
    $cover_count = 0;
    if ($ret_qy) { //写出qy平台公告
        foreach ($ret_qy as $arr) {
            $file = (strpos($arr['date'],'-') ? $arr['date'] : date('Y-m-d',$arr['date']));
            $file .=  '#' . $arr['title'] . '.html';
            $file = $path . 'qy/' . preg_replace('/[\/\|*?\\\:<>]/i','_',$file);
            $file_exist = file_exists($file);
            if ($file_exist)
                $cover_count++; //
            if (!$file_exist || $cover_count <= 3) { //只抓取未记录的公告 或已有记录的前3个
                $ret = http_get($arr['url']);
                if ($ret) {
                    write($file, $ret);
                    $ccount++;
                }
            }
        }
    }

    echo date("Y-m-d H:i:s") . " 读取页面" . $count . "个，抓取公告" . $ccount . "篇<br>";
    $repo = Git::open($path);
    $ret=$repo->status(true);
    $no_commit=preg_match('/nothing to commit, working directory clean/',$ret);

    if ($no_commit) {
        echo " 未检测到更新，共计用时：".(time()-$stime)."秒<br>";
    } else {
        if ($count<2) {
            echo "由于公告页面可能读取失败，等待下次检测。<br>";
            @unlink($file_lock);
            $repo->checkout(".");      //撤销所有修改
            return false;
        }
        echo "待更新内容：<hr>".$ret."<hr>";
        $ret0 = $repo->add();
        $repo->run('config --global user.email "'.GIT_EMAIL.'"');//git config --global user.email "you@example.com"
        $repo->run('config --global user.name "'.GIT_NAME.'"');//git config --global user.name "Your Name"
        $repo->run('config --global core.quotepath false'); //配置git显示中文不转码
        $ret = $repo->commit('check time: '.date("Y-m-d H:i:s"));
        echo time() . " 已进行git提交，共计用时：".(time()-$stime)."秒<br><br>";
        if ($remote_git) {
            $branch = $repo->active_branch();
            echo "检测到远程仓库参数，提交到远程仓库...<br>";
            $repo->run("remote add $stime ".$remote_git); //添加远程仓库
            $repo->run("push -f $stime $branch:".(empty($remote_branch)?'master':"$remote_branch"));  //强制覆盖远程仓库(可指定分支)
            $repo->run("remote remove $stime"); //删除远程仓库
        }
        echo "提交git日志内容如下：<hr>".nl2br(htmlspecialchars($ret));
        $ret2 = $repo->run('log --stat -p -1');
        $ret2 = nl2br(htmlspecialchars(substr($ret2,0,stripos($ret2,"\ndiff --git"))));
        echo "<hr>其他日志：<br>".nl2br(htmlspecialchars($ret0)).$ret2;
        if (!stripos($ret2,'_notice.txt') && $ccount>0) //如果公告列表没有更新，则认为没有更新公告。避免公告内容页不紧要的排版更新。
            $no_commit = false;
        @unlink($mail_lock);
    }
    @unlink($file_lock);
    return !$no_commit;
}

function get_update($sname,$file_lock,$base_url,$path,$mail_lock,$remote_git='',$remote_branch='') {
    session_name($sname);
    session_start();
    set_time_limit(300);
    ignore_user_abort(true);

    $stime = time();
    $work = false;

    //检测、设置工作标志，存在session里（同一个session_name在一个页面未结束前会保持读写锁状态）
    if (empty($_SESSION['working'])) {
        $work = true;
    }
    if (file_exists($file_lock)) {
        $last_work_time = filemtime($file_lock);
        if (($last_work_time > 0) && ((time()- $last_work_time) < 120)) { //上次更新至今有120秒
            echo "检测到正在进行工作中，本页面停止载入，请稍后再次访问。";
            return false;
            //exit;
        }
        $work = true;
        unlink($file_lock);
    } elseif (empty($_SESSION['work_time']) || ($stime - $_SESSION['work_time']) > LOCK_TIME){ //保证 LOCK_TIME 秒内只访问一次
        $work = true;
    }
    if ($work && !file_exists($file_lock)) {
        $_SESSION['working'] = true;
        $_SESSION['work_time'] = $stime;
        file_put_contents($file_lock,$stime);
    } else {
        echo "距离上次获取请求时间间隔多短，请稍后再次访问。";
        return false;
        //exit;
    }
    session_write_close(); //解除session，防止使其他访问页面一直等待session

    echo date("Y-m-d H:i:s") . " 读取列表中...<br>";
    mk_dir($path);
    if (IS_WIN) {
        Git::windows_mode();
    }
    if (!file_exists($path.'.git')) {
        echo date("Y-m-d H:i:s") . " 没有git库，尝试创建...<br>";
        if ($remote_git){            //是否设置了远程仓库
            echo "尝试从远程仓库克隆数据...<br>";
            $ret=Git::clone_remote($path,$remote_git,$remote_branch); //从远程仓库clone(可指定分支)
            if (!Git::is_repo($ret) || !file_exists($path.'.git') || !$ret){
                echo "从远程仓库克隆失败，本地创建...<br>";
                $ret=Git::create($path); //如果clone失败，则本地创建
            }
        } else
            $ret=Git::create($path); //直接本地创建
        echo date("Y-m-d H:i:s") . " 创建结果：".(Git::is_repo($ret)?'成功':'失败')."<br>";
    }

    $files  =ls_file($path);
    foreach ($files as $file) {
        if (!is_dir($path . $file))
            unlink($path . $file);
    }
    $tmp_arr = parse_url($base_url);
    $url_base = $tmp_arr['scheme'].'://'.$tmp_arr['host'];

    $list=get_list($base_url);  //获取列表
    write($path . 'list.txt',json($list));  //写出列表
    echo date("Y-m-d H:i:s") . " 读取列表完毕，开始读取内容页...<br>";
    $count=0;
    $content=get_content($base_url);
    write($path . 'index.txt',$content);
    $count++;
    foreach ($list as $arr) {
        if (isset($arr['ul'])) {
            foreach ($arr['ul'] as $a) {
                if (substr($a['url'],0,1)=='.' || substr($a['url'],0,1)=='/') { //地址以.或/开头，则为wiki文档
                    $content=get_content(((substr($a['url'],0,1)=='/')?$url_base:((substr($a['url'],0,1)=='.')?$url_base.$tmp_arr['path']:'')).$a['url']);
                    if ($content) {
                        write($path . $a['title'] . '.txt',$content);
                        $count++;
                    }
                }
            }
        }
    }
    echo date("Y-m-d H:i:s") . " 获取操作完成，读取页面数量：".$count."<br>";
    $repo = Git::open($path);
    $ret=$repo->status(true);
    $no_commit=preg_match('/nothing to commit, working directory clean/',$ret);

    if ($no_commit) {
        echo " 未检测到更新，共计用时：".(time()-$stime)."秒<br>";
    } else {
        echo "待更新内容：<hr>".$ret."<hr>";
        $ret0 = $repo->add();
        $repo->run('config --global user.email "'.GIT_EMAIL.'"');//git config --global user.email "you@example.com"
        $repo->run('config --global user.name "'.GIT_NAME.'"');//git config --global user.name "Your Name"
        $repo->run('config --global core.quotepath false'); //配置git显示中文不转码
        $ret = $repo->commit('check time: '.date("Y-m-d H:i:s"));
        echo time() . " 已进行git提交，共计用时：".(time()-$stime)."秒<br><br>";
        if ($remote_git) {
            $branch = $repo->active_branch();
            echo "检测到远程仓库参数，提交到远程仓库...<br>";
            $repo->run("remote add $stime ".$remote_git); //添加远程仓库
            $repo->run("push -f $stime $branch:".(empty($remote_branch)?'master':"$remote_branch"));  //强制覆盖远程仓库(可指定分支)
            $repo->run("remote remove $stime"); //删除远程仓库
        }
        echo "提交git日志内容如下：<hr>".nl2br(htmlspecialchars($ret));
        $ret2 = $repo->run('log --stat -p -1');
        echo "<hr>其他日志：<br>".nl2br(htmlspecialchars($ret0)).nl2br(htmlspecialchars($ret2));
        @unlink($mail_lock);
    }
    @unlink($file_lock);
    return !$no_commit;
}

function send_mail($path,$mail_lock,$subject,$to=array()) {
    if (!file_exists($mail_lock)) { //从getwiki.php中得到的更新标志

        $tmp_file = './update_'.time().'.txt';
        $ret_text = get_lastlog($path,1);

        //##########################################
        $smtpserver = isset($_ENV["SMTP_SERVER"])?$_ENV["SMTP_SERVER"]:"smtp.163.com";//SMTP服务器
        $smtpserverport = isset($_ENV["SMTP_SERVER_PORT"])?$_ENV["SMTP_SERVER_PORT"]:"465";//SMTP服务器端口
        $smtpusermail = isset($_ENV["SMTP_USER_MAIL"])?$_ENV["SMTP_USER_MAIL"]:"";//SMTP服务器的用户邮箱
        $smtpemailto = isset($_ENV["SMTP_MAIL_TO"])?$_ENV["SMTP_MAIL_TO"]:"";//收件人
        $smtpuser = isset($_ENV["SMTP_USER"])?$_ENV["SMTP_USER"]:"";//SMTP服务器的用户帐号
        $smtppass = isset($_ENV["SMTP_PASS"])?$_ENV["SMTP_PASS"]:"";//SMTP服务器的用户密码
        $mailtype = "HTML";//邮件格式（HTML/TXT）,TXT为文本邮件
        $mailsubject = "监测通知：微信公众平台WIKI更新";//邮件主题
        $mailbody = "<h1> 更新内容为： </h1>"."git日志内容如下：<br><hr>".
                    nl2br(htmlspecialchars(substr($ret_text,0,stripos($ret_text,"\ndiff --git")))).
                    "<br><hr>更多内容，请参看附件".$tmp_file;//邮件内容
        ##########################################

        if (!$smtpuser) {
            echo "由于邮箱SMTP账号信息等未配置，邮件未能发送<br />";
            return false; //未设置参数则返回
        }
        if (!empty(MAIL_REMOTE_URL)) {
        	$mailbody .= "<br>或者点击查看远程仓库页面：<a href=\"".REMOTE_URL."\">".REMOTE_URL."</a>";
        }
        $mailbody .= "<hr>收到此邮件说明曾经向我发消息订阅此通知，如不是您本人操作或不想再接收到此通知请回复邮件告诉我，我会进行处理。谢谢";

        if (empty($to)) {
            $to = isset($_ENV["SMTP_MAIL_TO2"])?$_ENV["SMTP_MAIL_TO2"]:"";//附加收件人
        }
        $to_arr = preg_split('/[,;\/\\\|]/',$smtpemailto);
        if (is_string($to)) {
            $to = preg_split('/[,;\/\\\|]/',$to);
        }
        $to_arr = array_merge($to_arr,$to);
        $to_count = count($to_arr);
        $to_arr = array_chunk($to_arr,20);

        write($tmp_file,$ret_text);

        $mail = new PHPMailer();
        $mail->IsSMTP();                // send via SMTP
        //$mail->SMTPDebug  = 1;
        $mail->SMTPAuth = true;         // turn on SMTP authentication
        $mail->SMTPSecure = "ssl";
        $mail->CharSet = "utf-8";            // 这里指定字符集！
        $mail->Encoding = "base64";

        $mail->Host = $smtpserver; // SMTP servers
        $mail->Port = $smtpserverport;
        $mail->Username = $smtpuser;   // SMTP username  注意：普通邮件认证不需要加 @域名
        $mail->Password = $smtppass;        // SMTP password

        $mail->setFrom($smtpusermail,"Auto Robot"); //设置发件人信息
        $mail->AddReplyTo($smtpusermail); //回复地址和姓名
        $mail->WordWrap = 50; // set word wrap
        $mail->IsHTML(true);  // send as HTML
        $mail->Subject = $subject?$subject:$mailsubject;  // 邮件主题
        $mail->Body = $mailbody;
        $mail->AltBody ="text/html";
        $mail->addAttachment($tmp_file);
        $sendRet = true;
        foreach ($to_arr as $to_arr_lite) {
            foreach ($to_arr_lite as $to_str) {
                $mail->AddAddress($to_str);  // 收件人邮箱和姓名
            }
            $sendRet = $mail->Send() & $sendRet;
            $mail->clearCCs();
        }
        if($sendRet){
            @unlink($tmp_file);
            echo "邮件发送失败,";
            echo "邮件错误信息: " . $mail->ErrorInfo. "<br />";
        } else {
            @unlink($tmp_file);
            echo "邮件已发送到邮箱 <br />";
            file_put_contents($mail_lock,'ok');
        }
    } else
        echo "未更新，不发送通知邮件。";
}
?>