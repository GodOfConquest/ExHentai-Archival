<?php

namespace ExHentai\Pages;

class Archiver extends Base {

    public function getDownloadUrl() {
        $elem = $this->filterorFail('#continue a');
        return $elem->attr('href').'?start=1';
    }

}