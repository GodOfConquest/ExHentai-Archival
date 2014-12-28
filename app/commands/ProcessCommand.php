<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class ProcessCommand extends ConsoleKit\Command {

    public function execute(array $args, array $options = array()) {
        $totalToProcess = Gallery::whereProcessed(false)->count();
        $this->writeln(sprintf('%d galleries to process', $totalToProcess));

        while(true) {
            $galleries = Gallery::whereProcessed(false)->get();
            if($galleries->count() === 0) {
                break;
            }

            foreach($galleries as $gallery) {
                $this->processGallery($gallery);
            }
        }
    }

    public function processGallery(Gallery $gallery) {
        
    }

}