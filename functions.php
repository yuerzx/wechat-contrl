<?php
/**
 * Created by PhpStorm.
 * User: yuerzx
 * Date: 5/01/2017
 * Time: 1:20 PM
 */

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){ //windows is \ unix is /

    //This is for testing
    $adds = explode("\\", __FILE__, -3);
    $adds = implode("\\", $adds).'\\';
    $sessionPath = __DIR__.'\tmp';
}else{
    $adds = explode("/", __FILE__, -3);
    $adds = implode("/", $adds).'/';
    $sessionPath = __DIR__.'/tmp';
}
require ($adds.'wp-config.php');



