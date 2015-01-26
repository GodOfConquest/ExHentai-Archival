<?php

namespace ExHentai;

class Client {

    const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36';
    const RATE_LIMIT_SECONDS = 10;
    const COOKIE_FILE = 'storage/cookies';

    protected $cookie;
    protected $ctr;

    public function __construct() {
        $cookieArr = \Config::get('client.cookie');
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
        curl_setopt($ch, CURLOPT_COOKIEFILE, self::COOKIE_FILE);
        curl_setopt($ch, CURLOPT_COOKIEJAR, self::COOKIE_FILE);


        $proxy = $this->pickProxy();
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        printf(" (%s)", $proxy);

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

    // TODO: throw out bad proxies automatically
    protected function pickProxy() {
        $proxies = \Config::get('client.proxies');
        if(!$proxies || !is_array($proxies) || count($proxies) === 0) {
            return null;
        }

        if(!in_array(null, $proxies)) { // null entry means don't use a proxy
            $proxies[] = null;
        }

        // round robin
        return $proxies[$this->ctr++ % count($proxies)];
    }

    protected function buildCookie($arr) {
        $cookie = array();
        foreach($arr as $var => $value) {
            $cookie[] = $var.'='.$value;
        }

        return implode('; ', $cookie);
    }

}