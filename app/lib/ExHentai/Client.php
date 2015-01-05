<?php

namespace ExHentai;

class Client {

    const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36';
    const RATE_LIMIT_SECONDS = 10;

    protected $cookie;

    public function __construct() {
        $cookieArr = \Config::get('exhentai.cookie');
        $this->cookie = $this->buildCookie($cookieArr);
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

        $result = curl_exec($ch);
        curl_close($ch);

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