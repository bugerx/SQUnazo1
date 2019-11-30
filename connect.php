<?php
$db_name = "cs_nazo1";//用于记录选手答题情况
$db_name_log = "cs_nazo1_log";//记录用户提交内容
$db_name_member = "cs";//答题选手信息库

$isql=@new mysqli('localhost:3306','root','password','db');

if ($isql->connect_error) {
    die('Connect Error (' . $isql->connect_errno . ') '
        . $isql->connect_error);
}

$result=$isql->set_charset('utf8');//设置编码为utf8