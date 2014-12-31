<?php

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model {

    public function tags() {
        return $this->belongsToMany('Tag')->withPivot('namespace');
    }

    public function getZipPath() {
        return $this->getZipDirectory().'/'.$this->id.'.zip';
    }

    public function getHtmlPath() {
        return $this->getHtmlDirectory().'/'.$this->id.'.html';
    }

    public function getZipDirectory() {
        $archivePath = Config::get('archive_path');
        return $archivePath.'/galleries/'.$this->getStaggeredIdPath();
    }

    public function getHtmlDirectory() {
        $archivePath = Config::get('archive_path');
        return $archivePath.'/html/'.$this->getStaggeredIdPath();
    }

    public function getStaggeredIdPath() {
        $a = str_pad($this->id - ($this->id % 10000), 6, '0', STR_PAD_LEFT);
        $b = str_pad($this->id - ($this->id % 1000), 6, '0', STR_PAD_LEFT);
        return $a.'/'.$b;
    }

    public function addTagByName($tagName, $namespace) {
        $tag = Tag::getCreateByName($tagName);
        $this->tags()->attach($tag->id, array('namespace' => $namespace));
    }

    public function addTags($allTags) {
        foreach($allTags as $ns => $tags) {
            foreach($tags as $tagName) {
                $this->addTagByName($tagName, $ns);
            }
        }
    }

}