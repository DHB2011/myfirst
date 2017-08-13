<?php
/**
 * Created by PhpStorm.
 * User: BO
 * Date: 2017/8/10
 * Time: 14:48
 */
require './wechat.class.php';
$wechat = new wechat();
if($_GET['echostr']){
    $wechat->valid();
}else{
    //消息管理方法
    $wechat->responseMsg();
}
















