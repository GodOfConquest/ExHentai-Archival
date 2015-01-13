<?php

namespace ExHentai;

class Client {

    const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36';
    const RATE_LIMIT_SECONDS = 10;

    protected $cookie;
    protected $tor;

    public function __construct() {
        $cookieArr = \Config::get('client.cookie');
        $this->cookie = $this->buildCookie($cookieArr);

        $this->tor = \Config::get('client.tor');
    }

    public function gallery($id, $token) {
        $url = URL::toGallery($id, $token);
        $html = $this->exec($url);
        return new Pages\Gallery($html);
    }

    public function archiver($id, $token, $archiverToken) {
        $url = URL::toArchiver($id, $token, $archiverToken);
        $html = $this->exec($url);
        return new Pages\Archiver($html);
    }

    public function exec($url) {
        $this->rateLimit();

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);

        if($this->tor) {
            curl_setopt($ch, CURLOPT_PROXY, $this->tor);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        if(strpos($result, 'Your IP address has been temporarily banned') === 0) {
            throw new Exceptions\IpBannedException($result);
        }

        if(!$result) {
            throw new \Exception('Failed to get page: '.$url);
        }

        return $result;
    }

    protected function rateLimit() {
        sleep(self::RATE_LIMIT_SECONDS);
    }

    protected function buildCookie($arr) {
        $cookie = array();
        foreach($arr as $var => $value) {
            $cookie[] = $var.'='.$value;
        }

        return implode('; ', $cookie);
    }

}