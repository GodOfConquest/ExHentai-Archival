<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class ImportCommand extends ConsoleKit\Command {

    public function execute(array $args, array $options = array()) {
        
        // galleries
        $this->writeln('Importing');
        $this->writeln("\t* galleries");

        $sql = 
            "replace into exhentai_archival.galleries (id, token, title, title_jp, type, posted_at, created_at) (
                select exhenid, hash, gallery.name, origtitle, type, added, now() from exhen.gallery
                inner join exhen.galleryproperty on galleryproperty.gallery_id = gallery.id
                where galleryproperty.value = 'English (T)'
            )";

        Capsule::statement($sql);
    }

}