<?php

use Illuminate\Database\Eloquent\Model;

class Tag extends Model {

    public static function getCreateByName($name) {
        $tag = Tag::whereName($name)->first();
        if(!$tag) {
            $tag = new Tag();
            $tag->name = $name;
            $tag->save();
        }

        return $tag;
    }

}