<?php

namespace ExHentai;

class URL {

    const BASE_URL = 'http://exhentai.org/';

    public static function toGallery($id, $token) {
        $url = sprintf('%s/g/%d/%s/', self::BASE_URL, $id, $token);
        return $url;
    }

    public static function fromGallery($url) {
        preg_match('~/g/(\d+)/([0-9a-f]+)~', $url, $matches);
        
        $ret = array(
            'id' => (int)$matches[1],
            'token' => $matches[2]
        );

        return $ret;
    }

    public static function toArchiver($id, $token, $archiverToken) {
        $params = array(
            'gid' => $id,
            'token' => $token,
            'or' => $archiverToken
        );

        $query = http_build_query($params);

        return self::BASE_URL.'archiver.php?'.$query;
    }

    // return archiver token
    public static function fromArchiver($url) {
        $query = parse_url($url, PHP_URL_QUERY);
        
        // return value in args.. gotta love consistency
        parse_str($query, $arr);
        
        return $arr['or'];
    }

}