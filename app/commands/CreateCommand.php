<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateCommand extends ConsoleKit\Command {

    public function execute(array $args, array $options = array()) {
        $schema = Capsule::schema();

        $this->writeln('Creating tables');

        // drop FKs
        if($schema->hasTable('gallery_tag')) {
            $schema->table('gallery_tag', function($table) {
                $table->dropForeign('gallery_tag_gallery_id_foreign');
                $table->dropForeign('gallery_tag_tag_id_foreign');
            });
        }

        // galleries table
        $schema->dropIfExists('galleries');
        $schema->create('galleries', function($table) {
            $table->integer('id')->unsigned()->primary();
            $table->string('token');
            $table->string('title');
            $table->string('title_jp')->nullable();
            $table->enum('type', array('artistcg', 'cosplay', 'doujinshi', 'gamecg', 'manga', 'misc', 'non-h'))->nullable();
            $table->dateTime('posted_at');
            $table->tinyInteger('hidden')->default(0);
            $table->string('hidden_reason')->nullable();
            $table->tinyInteger('removed')->default(0); // not visible at all by normal users (i.e Wani purge)
            $table->integer('parent_gallery')->unsigned()->nullable();
            $table->string('uploader');
            $table->bigInteger('filesize')->nullable();
            $table->integer('images_count')->nullable();
            $table->tinyInteger('processed')->default(0);
            $table->tinyInteger('downloaded')->default(0);
            $table->timestamps();
        });

        $this->writeln("\t* galleries");

        // tags
        $schema->dropIfExists('tags');
        $schema->create('tags', function($table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        $this->writeln("\t* tags");

        // gallery_tag
        $schema->dropIfExists('gallery_tag');
        $schema->create('gallery_tag', function($table) {
            $table->integer('gallery_id')->unsigned();
            $table->integer('tag_id')->unsigned();
            $table->primary(array('gallery_id', 'tag_id'));
            $table->enum('namespace', array('language', 'artist', 'male', 'female', 'reclass', 'misc', 'group', 'parody', 'character'));

            $table->foreign('gallery_id')->references('id')->on('galleries')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade')->onUpdate('cascade');
        });

        $this->writeln("\t* gallery_tag");
    }

}