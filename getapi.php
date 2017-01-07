<?php
/**
 * Created by PhpStorm.
 * User: yuerzx
 * Date: 5/01/2017
 * Time: 12:49 PM
 */

require "functions.php";
require "jssdk-mysql.php";
require "jssdk-redis.php";

$app_key = "wx2d39a6c422ad663c";
$app_pass = "e339b975f47c4a16b2b4b41f10fb5ef1";

global $redis;
$redis = new Redis();

//try to connect to the redis server
$connect = $redis->connect(
    'redis',
    6379,
    0.8
);

if($connect){
    // if we are able to use redis surely going to use redis, as it was fast and easy to handle
    global $jssdk;
    $jssdk = new JSSDK_Redis($app_key, $app_pass);
}else{
    // we use MySql as a safety net for the emergency
    global $jssdk;
    $jssdk = new JSSDK_MySQL($app_key, $app_pass);
}


if(isset($_GET['action']) && !empty($_GET['action'])){
    $action = $_GET['action'];
    switch ($action){
        case 'refreshToken':
            refreshToken();
            break;
        case 'getSigned':
            if(isset($_GET['url']) && !empty($_GET['url'])){
                getSigned($_GET['url']);
            }
            break;
        case 'testRedis':
            echo $redis->get('wx-accessToken');
            echo $redis->get('wx-jsAPI');
            break;
    }
}



function refreshToken(){
    global $jssdk;
    $jssdk->removeRecord();
    $res['token'] = $jssdk->getAccessToken();
    $res['js'] = $jssdk->getJsApiTicket();
    if($res){
        echo "{'status' : true}";
    }
}

function getSigned($url){
    global  $jssdk;
    $res = $jssdk -> getSignPackage($url);
    echo json_encode($res);
}