<?php

namespace ExHentai\Pages;

use Symfony\Component\DomCrawler\Crawler;

class Base extends Crawler {

    // throw an exception if the element is not found
    public function filterOrFail($selector) {
        $result = $this->filter($selector);

        if(count($result) === 0) {
            throw new \Exception('Failed to find element for selector: '.$selector);
        }

        return $result;
    }

    protected function wrap($element) {
        return new self($element);
    }

}