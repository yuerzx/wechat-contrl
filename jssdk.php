<?php

class JSSDK
{
    private $appId;
    private $appSecret;
    private $currentToken;
    private $currentJsAPI;

    public function __construct($appId, $appSecret)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->database = $this->wpdb->prefix . 'oneuni_wechat_access';
        $this->currentToken;
        $this->currentJsAPI;
    }

    public function getSignPackage($url)
    {
        $jsapiTicket = $this->getJsApiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        // $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        // $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

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

        $jsapi_token = $this->wpdb->get_row("SELECT access_key, access_value, exp_time FROM $this->database WHERE access_key = 'jsAPI'", ARRAY_A);

        //if the ticket has been expired
        if ($jsapi_token['exp_time'] < time()) {
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$this->currentToken";
            var_dump("JSAPI" + $url);
            $res = json_decode($this->httpGet($url));

            //if we successfully load the information from server
            if ($res) {
                $this->currentJsAPI = $res->ticket;
                //time to update the database
                if ($res) {
                    $this->wpdb->update(
                        $this->database,
                        array(
                            'access_value' => $this->currentJsAPI,
                            'exp_time' => time() + 3000
                        ),
                        array('access_key' => 'jsAPI'),
                        array(
                            '%s',
                            '%d'
                        ),
                        array(
                            '%s'
                        )
                    );
                }
            }
        } else {
            $this->currentJsAPI = $jsapi_token['access_value'];
        }

        return $this->currentJsAPI;
    }

    public function getAccessToken()
    {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        $access_token = $this->wpdb->get_row("SELECT access_key, access_value, exp_time FROM $this->database WHERE access_key = 'accessToken'", ARRAY_A);

        if (time() > $access_token['exp_time']) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode($this->httpGet($url));
            if ($res) {
                $this->currentToken = $res->access_token;
                if ($access_token) {
                    $this->wpdb->update(
                        $this->database,
                        array(
                            'access_value' => $this->currentToken,
                            'exp_time' => time() + 3000
                        ),
                        array('access_key' => 'accessToken'),
                        array(
                            '%s',
                            '%d'
                        ),
                        array(
                            '%s'
                        )
                    );
                }
            }
        } else {
            $this->currentToken = $access_token['access_value'];
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

    private function get_php_file($filename)
    {
        return trim(substr(file_get_contents($filename), 15));
    }

    private function set_php_file($filename, $content)
    {
        $fp = fopen($filename, "w");
        fwrite($fp, "<?php exit();?>" . $content);
        fclose($fp);
    }
}

