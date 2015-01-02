<?php

namespace ExHentai\Pages;

use ExHentai\URL;

class Gallery extends Base {
    
    // i.e Wani galleries
    public function isRemoved() {
        $result = $this->filter('.d #continue');
        return (count($result) > 0);
    }

    public function getTitle() {
        return utf8_decode($this->filterOrFail('h1#gn')->text());
    }

    public function getJpTitle() {
        return utf8_decode($this->filterOrFail('h1#gj')->text());
    }

    public function getType() {
        $img = $this->filterOrFail('img.ic');
        return $img->attr('alt');
    }

    public function getUploader() {
        return utf8_decode($this->filterOrFail('div#gdn')->text());
    }

    public function getParent() {
        $prop = $this->getPropertyCell('parent');
        $link = $prop->filter('a');

        if(count($link) > 0) {
            $url = $link->attr('href');
            return URL::fromGallery($url);
        }
        else {
            return null;
        }
    }

    public function getPostedDate() {
        $prop = $this->getPropertyCell('posted');
        return $prop->text();
    }

    public function isHidden() {
        $prop = $this->getPropertyCell('visible');
        return ($prop->text() !== 'Yes');
    }

    public function getHiddenReason() {
        $prop = $this->getPropertyCell('visible');
        preg_match('~No \((.+)\)~', $prop->text(), $matches);
        return strtolower($matches[1]);
    }

    public function getPropertyCell($propName) {
        $rows = $this->filterOrFail('#gdd table tr');

        foreach($rows as $rowNode) {
            $row = $this->wrap($rowNode);
            $nameCell = $row->filterOrFail('td.gdt1');
            $valueCell = $row->filterOrFail('td.gdt2');

            if(count($nameCell) === 0 || count($valueCell) === 0) {
                throw new \Exception('Malformed property row');
            }

            $name = $nameCell->text();
            $name = rtrim($name, ':');
            $name = strtolower($name);

            if($name === $propName) {
                return $valueCell;
            }
        }

        return null;
    }

    public function getTags() {
        $ret = array();

        // find rows in tag table
        $result = $this->filter('#taglist tr');
        foreach($result as $i => $tagRowNode) {
            $tagRow = $this->wrap($tagRowNode);

            // get the namespace for these tags
            $nsElem = $tagRow->filter('td');
            if(count($nsElem) === 0) {
                throw new \Exception('Failed to find tag namespace');
            }

            /*
                The selector should really use td:fist-child, however this
                isn't supported by the css -> xpath translator.
                Use the first item in the returned result set instead
            */
            $ns = trim($nsElem->text(), ':');

            // grab all tags in this namespace
            $tags = array();
            $tagLinks = $tagRow->filter('a');
            foreach($tagLinks as $x => $tagLink) {
                $tags[] = $tagLink->textContent;
            }

            $ret[$ns] = $tags;
        }

        return $ret;
    }

    public function getNewerVersions() {
        $versions = array();

        $links = $this->filter('div#gnd a');
        foreach($links as $linkNode) {
            $link = $this->wrap($linkNode);

            $url = $link->attr('href');
            $versions[] = URL::fromGallery($url);
        }

        return $versions;
    }

    public function getArchiverToken() {
        $link = $this->filterOrFail('.g2 a[onclick*="archiver"]');
        $onclick = $link->attr('onclick');
        preg_match("~(http://exhentai.org/archiver.php.*)'~", $onclick, $matches);
        return \ExHentai\URL::fromArchiver($matches[1]);
    }

}