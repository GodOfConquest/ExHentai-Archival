<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class ProcessCommand extends ConsoleKit\Command {

    protected $client;
    protected $archivePath;
    protected $legacyPath;

    public function execute(array $args, array $options = array()) {
        $this->client = new ExHentai\Client();
        $this->archivePath = Config::get('archive_path');
        $this->legacyPath = Config::get('legacy_path');

        $totalToProcess = Gallery::whereRaw('processed = 0')->count();
        $currentCount = 0;

        while(true) {
            $galleries = Gallery::whereRaw('processed = 0')->get();
            if($galleries->count() === 0) {
                break;
            }

            foreach($galleries as $index => $gallery) {
                printf("%d: #%d", ($currentCount + $index), $gallery->id);

                try {
                    $this->processGallery($gallery);
                }
                catch(ExHentai\Exceptions\IpBannedException $e) {
                    $this->writeln('Failed to archive gallery #'.$gallery->id);
                    $this->writeln((string)$e);

                    sleep(60 * 60); // 1 hr
                }
                catch(Exception $e) {
                    $this->writeln('Failed to archive gallery #'.$gallery->id);
                    $this->writeln((string)$e);
                }

                print("\n");
            }

            $currentCount += $galleries->count();

            if($currentCount >= $totalToProcess) {
                break;
            }
        }
    }

    public function processGallery(Gallery $gallery) {
        $page = $this->client->gallery($gallery->id, $gallery->token);

        // check if the gallery was purged
        if($page->isRemoved()) {
            $gallery->removed = true;

            // see if a previous HTML version of the page exists
            $legacyHtml = $this->legacyPath.'/pages/'.$gallery->id.'.html';
            if(file_exists($legacyHtml)) {
                $html = file_get_contents($legacyHtml);
                $page = new \ExHentai\Pages\Gallery($html);
            }
            else {
                // nothing can be done
                $gallery->processed = true;
                $gallery->save();
                return;
            }
        }

        $gallery->title = $page->getTitle();
        $gallery->title_jp = $page->getJpTitle();
        $gallery->type = $page->getType();
        $gallery->posted_at = $page->getPostedDate();
        $gallery->uploader = $page->getUploader();

        print(" - ".$gallery->title);

        $tags = $page->getTags();
        $gallery->tags()->detach();
        $gallery->addTags($tags);

        $parent = $page->getParent();
        if($parent) {
            $gallery->parent_gallery = $parent['id'];
        }

        $gallery->hidden = $page->isHidden();
        if($gallery->hidden) {
            $gallery->hidden_reason = $page->getHiddenReason();
        }

        $versions = $page->getNewerVersions();
        foreach($versions as $version) {
            $this->addGallery($version['id'], $version['token']);
        }

        $htmlDir = $gallery->getHtmlDirectory();
        if(!is_dir($htmlDir)) {
            mkdir($htmlDir, 0777, true);
        }

        $zipDir = $gallery->getZipDirectory();
        if(!is_dir($zipDir)) {
            mkdir($zipDir, 0777, true);
        }

        // write HTML
        if(file_put_contents($gallery->getHtmlPath(), $page->html()) === false) {
            throw new \Exception('Failed to write html: '.$gallery->getHtmlPath());
        }

        $legacyZip = $this->legacyPath.'/galleries/'.$gallery->id.'.zip';
        if(file_exists($legacyZip)) {
            if(!$this->loadZip($legacyZip, $gallery)) {
                throw new \Exception('Failed to open zip for gallery: #'.$gallery->id);
            }

            if(!copy($legacyZip, $gallery->getZipPath())) {
                throw new \Exception('Failed to copy zip to destination');
            }

            print(" (using legacy)");
        }
        elseif(!$gallery->removed) {
            // load archiver page
            $archiverToken = $page->getArchiverToken();
            $archiver = $this->client->archiver($gallery->id, $gallery->token, $archiverToken);


            $downloadUrl = $archiver->getDownloadUrl();
            $downloadStream = fopen($downloadUrl, 'r');
            if(!$downloadStream) {
                throw new \Exception('Failed to open download URL: '.$downloadUrl);
            }

            $tempStream = tmpfile();
            stream_copy_to_stream($downloadStream, $tempStream);
            fclose($downloadStream);

            $meta = stream_get_meta_data($tempStream);
            $tempPath = $meta['uri'];

            if(!$this->loadZip($tempPath, $gallery)) {
                throw new \Exception('Failed to open zip for gallery: #'.$gallery->id);
            }

            if(!copy($tempPath, $gallery->getZipPath())) {
                throw new \Exception('Failed to copy zip to destination');
            }

            print(" (downloaded)");
        }
        else {
            print(" (removed)");
        }

        $gallery->processed = true;
        $gallery->save();
    }

    protected function addGallery($id, $token) {
        $gallery = Gallery::find($id);
        if(!$gallery) {
            $gallery = new Gallery();
            $gallery->id = $id;
            $gallery->token = $token;
            $gallery->save();

            print(" +#".$id);
        }
    }

    protected function loadZip($zipPath, Gallery $gallery) {
        $archive = new ZipArchive();
        $result = $archive->open($zipPath);
        if($result === true && $archive->status == ZipArchive::ER_OK) {
            $gallery->images_count = $archive->numFiles;
            $gallery->filesize = filesize($zipPath);
            $gallery->downloaded = true;

            return true;
        }
        else {
            return false;
        }
    }

}
