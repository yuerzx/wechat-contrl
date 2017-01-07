<?php

class JSSDK_Redis
{
    private $appId;
    private $appSecret;
    private $currentToken;
    private $currentJsAPI;

    public function __construct($appId, $appSecret)
    {
        global $redis;
        $this->redis = $redis;
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->currentToken;
        $this->currentJsAPI;
    }

    public function getSignPackage($url)
    {
        //just to refresh the token in case of expired.
        $this->getAccessToken();
        $jsapiTicket = $this->getJsApiTicket();

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId" => $this->appId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
        );
        return $signPackage;
    }

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function getJsApiTicket()
    {

        $jsapi_token = $this->redis->exists('wx-jsAPI');

        //if the ticket has been expired
        if (!$jsapi_token) {
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$this->currentToken";
            $res = json_decode($this->httpGet($url));

            //if we successfully load the information from server
            if ($res) {
                $this->currentJsAPI = $res->ticket;
                //time to update the database
                if ($res) {
                    $this->redis->setex('wx-jsAPI', 3000, $this->currentJsAPI);
                }
            }
        } else {
            $this->currentJsAPI = $this->redis->get('wx-jsAPI');
        }

        return $this->currentJsAPI;
    }

    public function getAccessToken()
    {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        $access_token = $this->redis->exists('wx-accessToken');

        if (!$access_token) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode($this->httpGet($url));
            if ($res) {
                $this->currentToken = $res->access_token;
                $this->redis -> setex("wx-accessToken", 3000, $this->currentToken);
            }
        } else {
            $this->currentToken = $this->redis->get('wx-accessToken');
        }
        return $this->currentToken;
    }

    private function httpGet($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    public function removeRecord(){
       $this->redis->delete('wx-accessToken', 'wx-jsAPI');
    }
}

