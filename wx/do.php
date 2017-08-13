
<?php
/**
 * Created by PhpStorm.
 * User: BO
 * Date: 2017/8/11
 * Time: 10:24
 */
require './wechat.class.php';
$wechat = new wechat();
//$wechat->getAccessToken();
//$wechat->uploadFile();
//$wechat->getFile();
//$wechat->createmenu();
$wechat->showMenu();