<?php
/**
 * Created by PhpStorm.
 * User: yuerzx
 * Date: 5/01/2017
 * Time: 12:49 PM
 */

require "functions.php";
require "jssdk.php";

global $jssdk;
$jssdk = new JSSDK("wx2d39a6c422ad663c", "e339b975f47c4a16b2b4b41f10fb5ef1");

if(isset($_GET['action']) && !empty($_GET['action'])){
    $action = $_GET['action'];
    switch ($action){
        case 'accessCode':
            accessCode();
            break;
        case 'refreshToken':
            refreshToken();
            break;
        case 'getSigned':
            if(isset($_GET['url']) && !empty($_GET['url'])){
                getSigned($_GET['url']);
            }
            break;
    }
}

function accessCode(){

}

function refreshToken(){
    global $jssdk;
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