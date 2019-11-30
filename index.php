<?php
header("Content-type:text/html;charset=utf-8");
date_default_timezone_set('PRC');
include './function.php';
require_once './connect.php';

//获取通过第一关的人数
$result = $isql->prepare("SELECT count('uid') from {$db_name}");
$result->execute();
$result = $result->get_result();
$row = $result->fetch_assoc();
$joinNum = $row["count('uid')"];


if (!empty($_POST['key'])) {
    $key = $_POST['key'];
    $key = preg_replace('/[^a-z0-9 ]/i', '', $key);//简单防一下SQL注入
    echo "<script>console.log('{$key}');</script>";
    $name = '';
    $qq = '';
    $departmentName = '';

    //Log记录
    $sql = "INSERT INTO {$db_name_log}(`key`, `ip`, `city`, `ua`, `sendtime`) VALUES ('{$key}', '".get_real_ip()."', '".get_ip_city()."', '{$_SERVER['HTTP_USER_AGENT']}', '" . date('Y-m-d H:i:s') . "');
  ";
    $result = $isql->prepare($sql);
    $result->execute();

    $strlen = strlen($key);//根据用户提交的Key长度判断是第几题的答案
    //封号检测
    if ($strlen > 9){
      $id = (int)substr($key, 0, 10);
      $result = $isql->prepare("SELECT * FROM {$db_name} WHERE studentId = {$id}");
      $result->execute();
      $result = $result->get_result();
      $row = $result->fetch_assoc();

      if($row && $row['isban']==1) {
        $tip = $row['remarks']?"原因：{$row['remarks']}":'';
        if($tip=='') $tip = "对不起,你的账号已被封禁!";
        else $tip = "对不起,你的账号已被封禁!(".$tip.")";
        
        echo "<script>alert('{$tip}')</script>";
        $strlen = -1;//不再进行答案判断
      }
    }
    
    if ($strlen == 10) { //第一关的Key为选手学号,长度为10位
        $id = (int)$key;//执行SQL语句时id必须是整型,所以这里进行强制类型转换
        //查询是否为计协成员
        $result = $isql->prepare("SELECT * FROM {$db_name_member} WHERE studentId = {$id}");
        $result->execute();
        $result = $result->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            echo "<script>alert('对不起,非计算机协会成员无法完成签到')</script>";
        } else {
            //获取用户基本信息
            $name = $row['name'];
            $qq = $row['QQ'];
            $departmentName = $row['departmentName'];

            //查询已签到列表
            $result = $isql->prepare("SELECT * FROM {$db_name} WHERE studentId = {$id}");
            $result->execute();
            $result = $result->get_result();
            $row = $result->fetch_assoc();

            if (!$row) {
                //该用户还未签到,进行签到
                $sql = "INSERT INTO {$db_name}(`studentId`, `name`, `qq`, `departmentName`, `level`, `t1time`, `t1ua`, `t1ip`, `t1city`) VALUES ({$id}, '{$name}', {$qq}, '{$departmentName}', 1, '" . date('Y-m-d H:i:s') . "', '{$_SERVER['HTTP_USER_AGENT']}', '".get_real_ip()."', '".get_ip_city()."')";
                $result = $isql->prepare($sql);
                $result->execute();
                //更新用户签到信息
                $result = $isql->prepare("SELECT * FROM {$db_name} WHERE studentId = {$id}");
                $result->execute();
                $result = $result->get_result();
                $row = $result->fetch_assoc();

                //签到后还无法查询到签到信息,出现Bug
                if (!$row) {
                    exit("Error,请联系管理员修BUG了!({$id}:{$name})");
                }

                echo "<script>alert('恭喜你,{$name},你是第{$row['uid']}位成功签到的同学!')</script>";
                $txt = "真厉害,这么隐秘的 Key 都被你发现了,那我相信你也肯定能找到其他的 Key" . PHP_EOL . PHP_EOL;
                $txt = $txt . "可是...该去哪找到它呢?" . PHP_EOL;
                $txt = $txt . "不要慌,我可以给你提供一点线索呦~" . PHP_EOL;
                $txt = $txt . "Key的线索:Group、XT_Robot,你去问问TA吧。对了,第二把Key只有15位呦!" . PHP_EOL;
                $txt = $txt . "继续加油呦!(No.{$row['uid']}:{$name},已闯{$row['level']}关)" . PHP_EOL;
            } else if ($row['level'] > 0 && $row['level'] < 4) {
                $level = $row['level'];
                echo "<script>alert('{$row['name']}同学,你已经通过第{$level}关了,不用再来签到了!')</script>";

                if ($level == 1) {
                    $txt = "Key的线索:Group、XT_Robot,你去问问TA吧。对了,第二把Key只有15位呦!" . PHP_EOL;
                } else if ($level == 2) {
                    $txt = "Key的线索:所有Key都是以学号开头呦~" . PHP_EOL;
                } else {
                    $txt = "tip:你已经通关了!请不要泄露Key呦~" . PHP_EOL;
                }

                $txt = $txt . "(No.{$row['uid']}:{$name},已闯{$row['level']}关)" . PHP_EOL;
            } else { //用户level超出预设范围
                exit("Error,请联系管理员修BUG了!");
            } //修改用户第一关的通关信息
        } //判断是否计协成员
    } else if ($strlen == 15) { //第二关的Key为学号+QQ前5位,长度为15位
        $id = (int)substr($key, 0, 10);
        $q = substr($key, 10, 5);

        $result = $isql->prepare("SELECT * FROM {$db_name} WHERE studentId = {$id}");
        $result->execute();
        $result = $result->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            echo "<script>alert('你还没有完成第一关的签到呦~(偷偷告诉你:Key是以学号开头的)')</script>";
        } else if ($row['level'] < 1 || $row['level'] > 3) {
            exit("Error,请联系管理员修BUG了!");
        } else if ($row['level'] == 3) {
            echo "<script>alert('{$row['name']}同学,你已经通关了,不要再来提交Key了!')</script>";
            $txt = "tip:你已经通关了!请不要泄露Key呦~" . PHP_EOL;
            $txt = $txt . "(No.{$row['uid']}:{$name},已通关!)" . PHP_EOL;
        } else if ($row['level'] == 2) {
            echo "<script>alert('{$row['name']}同学,你已经通过第{$row['level']}关了,去寻找下一题的 Key 吧!')</script>";
            $txt = "Key的线索:所有Key都是以学号开头呦~" . PHP_EOL;
            $txt = $txt . "(No.{$row['uid']}:{$name},已闯{$row['level']}关)" . PHP_EOL;
        } else if (substr($row['qq'], 0, 5) != $q) {
            echo "<script>alert('你的Key好像不正确呀,重新检查一下吧!')</script>";
            $txt = "Key的线索:所有Key都是以学号开头呦~" . PHP_EOL;
            $txt = $txt . "(No.{$row['uid']}:{$name},已闯{$row['level']}关)" . PHP_EOL;
        } else {
            //更新用户通关到第二关
            $sql = "UPDATE {$db_name} SET `level` = 2, `t2time` = '" . date('Y-m-d H:i:s') . "', `t2ua` = '{$_SERVER['HTTP_USER_AGENT']}', `t2ip`='".get_real_ip()."', `t2city`='".get_ip_city()."', `t3time` ='', `t3ua` ='' WHERE `studentId` = {$id}";
            $result = $isql->prepare($sql);
            $result->execute();
            //更新用户信息
            $result = $isql->prepare("SELECT * FROM {$db_name} WHERE studentId = {$id}");
            $result->execute();
            $result = $result->get_result();
            $row = $result->fetch_assoc();

            //更新用户信息失败,出现Bug
            if ($row['level'] != 2) {
                exit("Error,请联系管理员修BUG了!({$id}:{$row['name']})");
            }

            echo "<script>alert('恭喜你,{$row['name']}同学,你已成功通过第二关!')</script>";
            $txt = "真棒,你居然找到了第二把 Key ,那还剩下最后一把 Key ,快去寻找它吧" . PHP_EOL . PHP_EOL;
            $txt = $txt . "tip:所有Key都是以学号开头呦~" . PHP_EOL;
            $txt = $txt . "继续加油呦!(No.{$row['uid']}:{$row['name']},已闯{$row['level']}关)" . PHP_EOL;
        } //修改第二关的通关信息
    } else if ($strlen == 16) { //第三关的Key为学号+Veigar,长度为16位, 不要问为什么是Veigar,因为我喜欢:)
        $id = (int)substr($key, 0, 10);
        $q = strtolower(substr($key, 10, 6));

        $result = $isql->prepare("SELECT * FROM {$db_name} WHERE studentId = {$id}");
        $result->execute();
        $result = $result->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            echo "<script>alert('你还没有完成第一关的签到呦~')</script>";
        } else if ($row['level'] < 1 || $row['level'] > 3) {
            exit("Error,请联系管理员修BUG了!");
        } else if ($row['level'] == 3) {
            echo "<script>alert('{$row['name']}同学,你已经通关了,不要再来提交Key了!')</script>";
            $txt = "tip:你已经通关了!请不要泄露Key呦~" . PHP_EOL;
            $txt = $txt . "(No.{$row['uid']}:{$name},已通关!)" . PHP_EOL;
        } else if ($row['level'] == 2 && $q=='veigar') {
            //更新用户通关到第三关
            $sql = "UPDATE {$db_name} SET `level` = 3, `t3time` = '" . date('Y-m-d H:i:s') . "', `t3ua` = '{$_SERVER['HTTP_USER_AGENT']}', `t3ip`='".get_real_ip()."', `t3city`='".get_ip_city()."' WHERE `studentId` = {$id}";
            $result = $isql->prepare($sql);
            $result->execute();
            //更新用户信息
            $result = $isql->prepare("SELECT * FROM {$db_name} WHERE studentId = {$id}");
            $result->execute();
            $result = $result->get_result();
            $row = $result->fetch_assoc();

            //更新用户信息失败,出现Bug
            if ($row['level'] != 3) {
                exit("Error,请联系管理员修BUG了!({$id}:{$row['name']})");
            }

            echo "<script>alert('恭喜你,{$row['name']}同学,你已成功通关!')</script>";
            $txt = "Game Over!(No.{$row['uid']}:{$row['name']},已通关)" . PHP_EOL;
        } else {
            echo "<script>alert('请先通过前两关再来吧!')</script>";
            $txt = "Key的线索:所有Key都是以学号开头呦~" . PHP_EOL;
            $txt = $txt . "(No.{$row['uid']}:{$name},已闯{$row['level']}关)" . PHP_EOL;
        }
    } else if($strlen>0) {
        echo "<script>alert('去仔细找一下 Key 在哪吧!')</script>";
    } //处理Key
} //只有用户提交了Key时才进行数据处理
$isql->close();//关于MySQL连接(我也不太会使用PHP+MySQL操作,所以本程序的所有MySQL都是C&V来的)
?>
<!DOCTYPE html>
<html lang="zh-cn">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Key 在哪?</title>
  <meta name ="keywords" content="计算机协会,商丘学院,学生活动,知识竞赛">
  <meta name="description" content="商丘学院计算机学会趣味竞赛.">
  <link href="static/css/bootstrap.min.css" rel="stylesheet">
  <link href="static/css/style.css" rel="stylesheet">
</head>

<body class="style-default">
  <div class="container">
    <div class="row">
      <div class="col-sm-8 col-sm-offset-2 col-xs-12"><br/>
        <h1 class="title"><span class="codename">Find Key</span></h1>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-8 col-sm-offset-2 col-xs-12">
        <div class="contentbox">
          <p>欢迎来到 Find Key Game</p>
          <p>你能闯到第几关？<br/><small>(不要把Key告诉其他人呦~)</small></p>
          <p>当前活动已参与人数：<?php echo $joinNum; ?></p>
        </div>
        <div class="row pwdinput">
          <div class="col-lg-8 col-lg-offset-2 col-sm-10 col-sm-offset-1">
            <form class="form-horizontal" method="post" action="">
              <div class="input-group input-group-lg">
                <input class="form-control" type="text" name="key" autocomplete="off"
                  placeholder="这里该输入点什么呢?" maxlength="16" required="required">
                <span class="input-group-btn">
                  <button class="btn btn-primary" type="submit">提交</button>
                </span>
              </div>
            </form>
            <div id="tip" style="opacity: 0.03;"><small>不妨试试从学号开始!</small></div>
          </div>
        </div>
      </div>
    </div>
    <div class="row nazofooter">
      <div class="col-sm-8 col-sm-offset-2 col-xs-12">
        <p><span>商丘学院 <a href="https://insqu.cn" style="color:#333" target="_blank">计算机协会</a></span></p>
      </div>
    </div>
  </div>

  <!-- 输出提示内容 -->
  <?php if (!empty($txt)) { echo "<pre style='height: 230px;'>{$txt}</pre>"; } ?>

<!-- 百度统计代码 -->
<script>
var _hmt = _hmt || [];
(function() {
  var hm = document.createElement("script");
  hm.src = "https://hm.baidu.com/hm.js?1d3b7100b9b738e1f333ea473a67c64c";
  var s = document.getElementsByTagName("script")[0]; 
  s.parentNode.insertBefore(hm, s);
})();
</script>

</body>

</html>