<?php
/**
 * @Author 张超.
 * @Copyright http://www.zhangchao.name
 * @Email 416716328@qq.com
 * @DateTime 2018/5/20 15:53
 * @Yes-Admin 安装引导
 */
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
ini_set('display_errors', '1');
//定义目录分隔符
define("DS", DIRECTORY_SEPARATOR);
//定义项目目录
define('APP_PATH', dirname(dirname(__FILE__)) . DS . 'application' . DS);
//定义web根目录
define('WWW_ROOT', dirname(__FILE__) . DS);
//定义CMS名称
$sitename = "EON";
$lockFile = "install.lock";
if (is_file($lockFile)) {
    die("<script>window.location.href = '/'</script>");
}
if ($_GET['c'] = 'start' && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    //执行安装
    $host = isset($_POST['hostname']) ? $_POST['hostname'] : '127.0.0.1';
    $port = isset($_POST['port']) ? $_POST['port'] : '3306';
    //判断是否在主机头后面加上了端口号
    $hostData = explode(":", $host);
    if (isset($hostData) && $hostData && is_array($hostData) && count($hostData) > 1) {
        $host = $hostData[0];
        $port = $hostData[1];
    }
    //mysql的账户相关
    $mysqlUserName = isset($_POST['username']) ? $_POST['username'] : 'root';
    $mysqlPassword = isset($_POST['password']) ? $_POST['password'] : '';
    $mysqlDataBase = isset($_POST['database']) ? $_POST['database'] : 'qlsscan';
    $mysqlPreFix = isset($_POST['prefix']) ? $_POST['prefix'] : 'qls_';
    $mysqlPreFix = rtrim($mysqlPreFix, "_") . "_";
    $adminUserName = isset($_POST['adminUserName']) ? $_POST['adminUserName'] : 'admin';
    $adminPassword = isset($_POST['adminPassword']) ? $_POST['adminPassword'] : '123456';
    $rePassword = isset($_POST['rePassword']) ? $_POST['rePassword'] : '123456';
    $email = isset($_POST['email']) ? $_POST['email'] : 'admin@admin.com';
    $serverName = isset($_POST['server_name']) ? $_POST['server_name'] : '';
    $serverDescription = isset($_POST['server_description']) ? $_POST['server_description'] : '';
    $serverTimezone = isset($_POST['server_timezone']) ? $_POST['server_timezone'] : '';
    $serverUrl = isset($_POST['server_url']) ? $_POST['server_url'] : '';
    $serverVersion = isset($_POST['server_version']) ? $_POST['server_version'] : '';

    //判断两次输入是否一致
    if ($adminPassword != $rePassword) {
        die("<script>alert('The two passwords are inconsistent!');history.go(-1)</script>");
    }
    if (!preg_match("/^[\S]+$/", $adminPassword)) {
        die("<script>alert('The password can not contain space!');history.go(-1)</script>");
    }
    if (!preg_match("/^\w+$/", $adminUserName)) {
        die("<script>alert('The user name can only input letters,numbers and underscores!');history.go(-1)</script>");
    }
    if (strlen($adminUserName) < 3 || strlen($adminUserName) > 12) {
        die("<script>alert('Please enter 3-12 characters for user name!');history.go(-1)</script>");
    }
    if (strlen($adminPassword) < 5 || strlen($adminPassword) > 16) {
        die("<script>alert('Please input 5-16 characters for password!');history.go(-1)</script>");
    }
    //检测能否读取安装文件
    $sql = @file_get_contents(WWW_ROOT . DS .'sql'.DS. 'qsscan.sql');
    if (!$sql) {
        die("<script>alert('Please check if /public/install/sql/qsscan.sql have read/write permission!');</script>");
    }
    //替换表前缀
    $sql = str_replace("`qls_", "`{$mysqlPreFix}", $sql);
    //链接数据库
    $pdo = new PDO("mysql:host={$host};port={$port}", $mysqlUserName, $mysqlPassword, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ));
    // 连接数据库
//    $link = @new mysqli("{$host}:{$port}", $mysqlUserName, $mysqlPassword);
    $link = @new mysqli("{$host}", $mysqlUserName, $mysqlPassword);
    // 获取错误信息
    $error = $link->connect_error;
    if (!is_null($error)) {
        // 转义防止和alert中的引号冲突
        $error = addslashes($error);
        die("<script>alert('Connect database failed:$error');history.go(-1)</script>");
    }
    // 设置字符集
    $link->query("SET NAMES 'utf8'");
    $link->server_info > 5.0 or die("<script>alert('Please upgrade your mysql to version 5.0 or above!');history.go(-1)</script>");
    // 创建数据库并选中
    if (!$link->select_db($mysqlDataBase)) {
        $create_sql = 'CREATE DATABASE IF NOT EXISTS ' . $mysqlDataBase . ' DEFAULT CHARACTER SET utf8;';
        $link->query($create_sql) or die('Create databse fail');
        $link->select_db($mysqlDataBase);
    }
    $sqlArr = explode(';', $sql);
    foreach ($sqlArr as $key => $val) {
        if ($val) {
            $link->query($val);
        }
    }
//    $password = password_hash($adminPassword, PASSWORD_BCRYPT);
    $sysAuthConfig = include "../../config" . DS . "sys_auth.php";
//    $pws_pre_halt = $sysAuthConfig['PWD_PRE_HALT'];
//    $password = strrev(md5(base64_encode($adminPassword).$pws_pre_halt));
    $publicKey = aesEncrypt($adminUserName, md5($adminPassword), 'qsxxqsxxqsxxqsxx');
    $privateKey = randCode();
    $authenticateKey = aesEncrypt($publicKey, $privateKey, $sysAuthConfig['AES_IV']);
    $result = $link->query("UPDATE {$mysqlPreFix}xadmins SET user_name = '{$adminUserName}',private_key = '{$privateKey}'"
    . ",authenticate_key = '{$authenticateKey}',email='{$email}' WHERE user_name = 'admin'");
    if (!$result) {
        die("<script>alert('install failed!:$error');history.go(-1)</script>");
    }
    //保存服务器相关配置
    $serverGuid = create_guid();
    $serverKey = randCode(32,0);
    $result = $link->query("INSERT INTO {$mysqlPreFix}xservers (id,server_name,server_description"
    . ",server_timezone,server_url,server_key,server_version) values('{$serverGuid}','{$serverName}','{$serverDescription}','{$serverTimezone}'"
    . ",'{$serverUrl}','{$serverKey}','{$serverVersion}')");
    if (!$result) {
        $error = $link->connect_error;
        if (!is_null($error)) {
            // 转义防止和alert中的引号冲突
            $error = addslashes($error);
        }
        die("<script>alert('save server details failed:$error');history.go(-1)</script>");
    }
    $databaseConfig = include "../../config" . DS . "database.php";
    //替换数据库相关配置
    $databaseConfig['hostname'] = $host;
    $databaseConfig['database'] = $mysqlDataBase;
    $databaseConfig['username'] = $mysqlUserName;
    $databaseConfig['password'] = $mysqlPassword;
    $databaseConfig['hostport'] = $port;
    $databaseConfig['prefix'] = $mysqlPreFix;
    $databaseConfig['debug'] = true;
    $databaseConfig['deploy'] = 0;
    $databaseConfig['rw_separate'] = false;
    $databaseConfig['master_num'] = 1;
    $databaseConfig['slave_no'] = '';
    $databaseConfig['fields_strict'] = true;
    $databaseConfig['resultset_type'] = 'array';
    $databaseConfig['auto_timestamp'] = true;
    $databaseConfig['datetime_format'] = 'Y-m-d H:i:s';
    $databaseConfig['sql_explain'] = false;
    $databaseConfig['query'] = '\\think\\db\\Query';
    $configPath = "../config" . DS . "database.php";
    $putConfig = @file_put_contents("../../config" . DS . "database.php", "<?php\nreturn \n" . var_export($databaseConfig, true) . "\n;");
    if (!$putConfig) {
        die("<script>alert('Install failed,please confirm if database.php have read/write permission!:$error');history.go(-1)</script>");
    }
    $result = @file_put_contents($lockFile, 1);
    if (!$result) {
        die("<script>alert('Install failed,please confirm if install.lock have read/write permission!:$error');history.go(-1)</script>");
    }
    die("<script>alert('Install success,click to start');window.location.href = '/'</script>");
}
function create_guid(){
    $charid = strtoupper(md5(uniqid(mt_rand(),TRUE)));
    $hyphen = chr(45);// "-" 
    $uuid =  
    substr($charid, 0, 8).$hyphen 
    .substr($charid, 8, 4).$hyphen 
    .substr($charid,12, 4).$hyphen 
    .substr($charid,16, 4).$hyphen 
    .substr($charid,20,12) ; 
    return $uuid; 
}
function randCode($length = 32, $type = -1) {
    $arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
    if ($type == 0) {
      array_pop($arr);
      $string = implode("", $arr);
    } elseif ($type == "-1") {
       $string = implode("", $arr);
    } else {
      $string = $arr[$type];
    }
     $count = strlen($string) - 1;
     $code = '';
    for ($i = 0; $i < $length; $i++) {
      $code .= $string[rand(0, $count)];
    }
    return $code;
}
function aesEncrypt($input,$key,$iv) {
    $data = openssl_encrypt($input, 'AES-256-CBC', $key, OPENSSL_RAW_DATA,$iv);
    $data = base64_encode($data);
    return $data;
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Install <?php echo $sitename; ?></title>
    <meta name="renderer" content="webkit">
    <link rel="stylesheet" href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <style type="text/css">
        html, body {
            height: 100%;
            background-image: url("./install/img/installbg.jpg");
            background-size: cover;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div style="margin: 0 auto;text-align: center;margin-top: 20px;">
                <img src="/install/img/logo.png" style="border-radius: 50%;width:200px;height:200px">
            </div>
        </div>
        <div class="col-md-6 col-md-offset-3"
             style="margin-top: 20px;background-color: rgba(255,255,255,.5);padding: 30px;border-radius: 5px">
            <div id="cms-box">
                <form class="form-horizontal" action="/install/index.php?c=start" method="post">
                    <p style="font-size: 28px;font-weight: bolder;text-align: center;color: #000;"><?= $sitename ?> Installation</p>
                    <div class="panel panel-default">
                        <div class="panel-heading">Server Details</div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Server Version</label>
                                <div class="col-sm-8">
                                    <input type="text" name="server_version" class="form-control" placeholder="1.0.0">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Server Installation URL</label>
                                <div class="col-sm-8">
                                    <input type="text" name="server_url" class="form-control" placeholder="http(s)://domain:port">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Server Name</label>
                                <div class="col-sm-8">
                                    <input type="text" name="server name" class="form-control" placeholder="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Server Description</label>
                                <div class="col-sm-8">
                                    <textarea name="server_description" class="form-control"></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Server Time Zone</label>
                                <div class="col-sm-8">
                                    <select name="server_timezone" class="form-control" id="timezone"></select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading">Admin Account</div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Admin Username</label>
                                <div class="col-sm-8">
                                    <input type="text" name="adminUserName" class="form-control" placeholder="Please input admin username">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Admin Password</label>
                                <div class="col-sm-8">
                                    <input type="password" name="adminPassword" class="form-control" placeholder="Please input password">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Admin Password Confirm</label>
                                <div class="col-sm-8">
                                    <input type="password" name="rePassword" class="form-control" placeholder="Please input passwod again">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Admin Email</label>
                                <div class="col-sm-8">
                                    <input type="text" name="email" class="form-control" placeholder="Please input email">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading">Database Details</div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Database Address</label>
                                <div class="col-sm-8">
                                    <input type="text" name="hostname" class="form-control" placeholder="127.0.0.1:3306">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Database Name</label>
                                <div class="col-sm-8">
                                    <input type="text" name="database" class="form-control" placeholder="qlsscan">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Database Username</label>
                                <div class="col-sm-8">
                                    <input type="text" name="username" class="form-control" placeholder="root">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Database Password</label>
                                <div class="col-sm-8">
                                    <input type="password" name="password" class="form-control" placeholder="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Table Prefix</label>
                                <div class="col-sm-8">
                                    <input type="text" name="prefix" class="form-control" placeholder="qsscan_">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-success" style="width: 80%;">Install</button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<script type="text/javascript" src="/install/js/jquery.min.js"></script>
<script type="text/javascript" src="/install/js/jquery.ripples-min.js"></script>
<script type="text/javascript" src="/install/js/timezone.js"></script>
<script type="text/javascript">
    $(function () {
        $('body').ripples({
            resolution: 512,
            dropRadius: 20, //px
            perturbance: 0.04,
        });
        var timezoneStr = "";
        $.each(timezone,function(i,item){
                timezoneStr += "<option val='"+item+"'>"+item+"</option>";
        });
        $("#timezone").html(timezoneStr );
    });
</script>
</body>
</html>