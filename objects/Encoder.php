<?php

global $sentImage;
$sentImage = array();
require_once $global['systemRootPath'] . 'objects/Format.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';
require_once $global['systemRootPath'] . 'objects/Upload.php';
require_once $global['systemRootPath'] . 'objects/functions.php';

class Encoder extends ObjectYPT {

    protected $id, $fileURI, $filename, $status, $status_obs, $return_vars, $worker_ppid, $worker_pid, $priority, $created, $modified, $formats_id, $title, $videoDownloadedLink, $downloadedFileName, $streamers_id, $override_status;

    static function getSearchFieldsNames() {
        return array('filename');
    }

    static function getTableName() {
        global $global;
        return $global['tablesPrefix'] . 'encoder_queue';
    }

    function save() {
        global $global;
        if (empty($this->id)) {
            $this->setStatus("queue");
        }
        if (empty($this->worker_ppid)) {
            $this->worker_ppid = 0;
        }
        $this->worker_pid = intval($this->worker_pid);
        $this->setTitle($global['mysqli']->real_escape_string(str_replace('\\\\', '', stripslashes($this->getTitle()))));
        $this->setStatus_obs($global['mysqli']->real_escape_string(str_replace('\\\\', '', stripslashes($this->getStatus_obs()))));
        //error_log("Encoder::save id=(" . $this->getId() . ") title=(" . $this->getTitle() . ")");
        return parent::save();
    }

    static function getAll($onlyMine = false) {
        global $global;
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE 1=1 ";
        if ($onlyMine && !Login::isAdmin()) {
            $sql .= " AND streamers_id = " . Login::getStreamerId() . " ";
        }
        $sql .= self::getSqlFromPost();

        $global['lastQuery'] = $sql;
        $res = $global['mysqli']->query($sql);
        $rows = array();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $rows;
    }

    static function getTotal($onlyMine = false) {
        //will receive
        //current=1&rowCount=10&sort[sender]=asc&searchPhrase=
        global $global;
        $sql = "SELECT id FROM  " . static::getTableName() . " WHERE 1=1  ";
        if ($onlyMine && !Login::isAdmin()) {
            $sql .= " AND streamers_id = " . Login::getStreamerId() . " ";
        }
        $sql .= self::getSqlSearchFromPost();

        $global['lastQuery'] = $sql;
        $res = $global['mysqli']->query($sql);


        return $res->num_rows;
    }

    function getId() {
        return $this->id;
    }

    function getFileURI() {
        return $this->fileURI;
    }

    function getFilename() {
        return $this->filename;
    }

    function getStatus() {
        return $this->status;
    }

    function getStatus_obs() {
        return $this->status_obs;
    }

    function getReturn_vars() {
        error_log("getReturn_vars " . $this->return_vars);
        return $this->return_vars;
    }

    function getWorker_ppid() {
        return intval($this->worker_ppid);
    }

    function getWorker_pid() {
        return intval($this->worker_pid);
    }

    function getPriority() {
        return intval($this->priority);
    }

    function getCreated() {
        return $this->created;
    }

    function getModified() {
        return $this->modified;
    }

    function getFormats_id() {
        return $this->formats_id;
    }

    function setFileURI($fileURI) {
        $this->fileURI = $fileURI;
    }

    function setFilename($filename) {
        $this->filename = $filename;
    }

    function setStatus($status) {

        $this->status = $status;
        //error_log('Encoder::setStatus: '.json_encode(debug_backtrace()));
        switch ($status) {
            case "done":
            case "error":
            case "queue":
                $this->setWorker_ppid(NULL);
                $this->setWorker_pid(NULL);
                break;
            case "downloading":
            case "encoding":
            case "packing":
            case "transferring":
            default:
                $this->setWorker_ppid(getmypid());
                $this->setWorker_pid(NULL);
                break;
        }
    }

    function setStatus_obs($status_obs) {
        $this->status_obs = substr($status_obs, 0, 200);
    }

    function setReturn_vars($return_vars) {
        $this->return_vars = $return_vars;
    }

    function setWorker_ppid($worker_ppid) {
        $this->worker_ppid = $worker_ppid;
    }

    function setWorker_pid($worker_pid) {
        $this->worker_pid = $worker_pid;
    }

    function setReturn_varsVideos_id($videos_id) {
        $videos_id = intval($videos_id);
        if (empty($videos_id)) {
            return false;
        }
        $obj = json_decode($this->return_vars);
        if (empty($obj)) {
            $obj = new stdClass();
        }
        $obj->videos_id = $videos_id;
        $this->setReturn_vars(json_encode($obj));
        $this->id = $this->save();
        return $this->id;
    }

    function setReturn_varsVideo_id_hash($video_id_hash) {
        if (empty($video_id_hash)) {
            return false;
        }
        $obj = json_decode($this->return_vars);
        if (empty($obj)) {
            $obj = new stdClass();
        }
        $obj->video_id_hash = $video_id_hash;
        $this->setReturn_vars(json_encode($obj));
        $this->id = $this->save();
        return $this->id;
    }

    function setPriority($priority) {
        $this->priority = intval($priority);
    }

    function setCreated($created) {
        $this->created = $created;
    }

    function setModified($modified) {
        $this->modified = $modified;
    }

    function getTitle() {
        return $this->title;
    }

    function setTitle($title) {
        $this->title = substr($title, 0, 254);
    }

    function getVideoDownloadedLink() {
        return $this->videoDownloadedLink;
    }

    function setVideoDownloadedLink($videoDownloadedLink) {
        $this->videoDownloadedLink = substr($videoDownloadedLink, 0, 254);
    }

    function getDownloadedFileName() {
        return $this->downloadedFileName;
    }

    function setDownloadedFileName($downloadedFileName) {
        $this->downloadedFileName = substr($downloadedFileName, 0, 254);
    }

    function getStreamers_id() {
        return $this->streamers_id;
    }

    function setStreamers_id($streamers_id) {
        $this->streamers_id = $streamers_id;
    }

    function getOverride_status() {
        return $this->override_status;
    }

    function setOverride_status($override_status) {
        $this->override_status = $override_status;
    }

    function setFormats_id($formats_id) {
        if (!preg_match('/^[0-9]+$/', $formats_id)) {
            $formats_id = Format::createIfNotExists($formats_id);
        }
        $this->formats_id = $formats_id;
    }

    function setFormats_idFromOrder($order) {
        $o = new Format(0);
        $o->loadFromOrder($order);
        $this->setFormats_id($o->getId());
    }

    static function getNext() {
        global $global;
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE status = 'queue' ";
        $sql .= " ORDER BY priority ASC, id ASC LIMIT 1";

        $res = $global['mysqli']->query($sql);
        if ($res) {
            return $res->fetch_assoc();
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return false;
    }

    static function downloadFile($queue_id) {
        global $global;
        $obj = new stdClass();
        $q = new Encoder($queue_id);
        $url = $q->getFileURI();

        //$ext = pathinfo($value, PATHINFO_EXTENSION);

        $f = new Format($q->getFormats_id());
        $ext = $f->getExtension_from();

        if (!empty($ext)) {
            $ext = ".{$ext}";
        }

        $dstFilepath = $global['systemRootPath'] . "videos/";
        $filename = "{$queue_id}_tmpFile" . $ext;
        if (!is_dir($dstFilepath)) {
            mkdir($dstFilepath);
        }

        $obj->error = true;
        $obj->filename = $filename;
        $obj->pathFileName = $dstFilepath . $filename;

        if (file_exists($obj->pathFileName)) {
            $obj->error = false;
            //error_log("downloadFile: file already exists queue_id = {$queue_id}  url = {$url} pathFileName = {$obj->pathFileName}");
            return $obj;
        }

        error_log("downloadFile: start queue_id = {$queue_id}  url = {$url} pathFileName = {$obj->pathFileName}");


        $e = Encoder::getFromFileURI($url);
        if (!empty($e['downloadedFileName'])) {
            $obj->pathFileName = $e['downloadedFileName'];
            $q->setDownloadedFileName($obj->pathFileName);
            $q->save();
            $obj->error = false;
            error_log("downloadFile: e['downloadedFileName'] = {$e['downloadedFileName']}");
            return $obj;
        }

        if (!empty($q->getVideoDownloadedLink())) {
            //begin youtube-dl downloading and symlink it to the video temp file
            $response = static::getYoutubeDl($q->getVideoDownloadedLink(), $queue_id, $obj->pathFileName);
            error_log("downloadFile:getYoutubeDl queue_id = {$queue_id}");
            $obj->error = false;
        } else {
            error_log("downloadFile: not using getYoutubeDl");
            //symlink the downloaded file to the video temp file ($obj-pathFileName)
            if (strpos($url, "http") !== false) {
                //error_log("downloadFile:strpos global['webSiteRootURL'] = {$global['webSiteRootURL']}");
                if (strpos($url, $global['webSiteRootURL']) === false) {
                    error_log("downloadFile: keep the same URL");
                    $downloadedFile = $url;
                } else {
                    error_log("downloadFile: this file was uploaded from file and thus is in the videos");
                    //this file was uploaded "from file" and thus is in the videos directory
                    $downloadedFile = substr($url, strrpos($url, '/') + 1);
                    $downloadedFile = $dstFilepath . $downloadedFile;
                }
            } else {
                error_log("downloadFile: this file was a bulk encode and thus is on a local directory");
                //this file was a "bulk encode" and thus is on a local directory
                $downloadedFile = $url;
            }
            error_log("downloadFile: downloadedFile = {$downloadedFile} | url = {$url}");

            $response = static::getVideoFile($url, $queue_id, $downloadedFile, $obj->pathFileName);
            $obj->error = false;
        }
        if ($obj->error == false && file_exists($obj->pathFileName)) {
            //error_log("downloadFile: success");
            $obj->msg = "We downloaded the file with success";
            $q->setDownloadedFileName($obj->pathFileName);
            $q->save();
        } else {
            $obj->error = true;
        }
        if ($response || $obj->error) {
            //error_log("downloadFile: error");
            $obj->msg = "Could not save file {$url} in {$dstFilepath}{$filename}";
            $response = static::getYoutubeDl($url, $queue_id, "{$dstFilepath}{$filename}");
            error_log("downloadFile: trying getYoutubeDl queue_id = {$queue_id}");
            $obj->error = false;
        }
        error_log("downloadFile: " . json_encode($obj));
        return $obj;
    }

    static function getYoutubeDl($videoURL, $queue_id, $destinationFile) {
        global $global;
        $tmpfname = tempnam(sys_get_temp_dir(), 'youtubeDl');
        //$cmd = "youtube-dl -o {$tmpfname}.mp4 -f 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/mp4' {$videoURL}";
        $cmd = self::getYouTubeDLCommand() . "  --no-check-certificate --force-ipv4 --no-playlist -k -o {$tmpfname}.mp4 -f 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/mp4' \"{$videoURL}\"";
        //echo "\n**Trying Youtube DL **".$cmd;
        error_log("getYoutubeDl: Getting from Youtube DL {$cmd}");
        exec($cmd . "  1> {$global['systemRootPath']}videos/{$queue_id}_tmpFile_downloadProgress.txt  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            //echo "\n**ERROR Youtube DL **".$code . "\n" . print_r($output, true);
            error_log($cmd . "\n" . print_r($output, true));
            $cmd = self::getYouTubeDLCommand() . "  --no-check-certificate --force-ipv4 --no-playlist -k -o {$tmpfname}.mp4 \"{$videoURL}\"";
            //echo "\n**Trying Youtube DL **".$cmd;
            error_log("getYoutubeDl: Getting from Youtube other option DL {$cmd}");
            exec($cmd . "  1> {$global['systemRootPath']}videos/{$queue_id}_tmpFile_downloadProgress.txt  2>&1", $output, $return_val);
            if ($return_val !== 0) {
                //echo "\n**ERROR Youtube DL **".$code . "\n" . print_r($output, true);
                error_log($cmd . "\n" . print_r($output, true));
                $cmd = self::getYouTubeDLCommand() . "  --no-check-certificate --no-playlist -k -o {$tmpfname}.mp4 \"{$videoURL}\"";
                //echo "\n**Trying Youtube DL **".$cmd;
                error_log("getYoutubeDl: Getting from Youtube other option DL {$cmd}");
                exec($cmd . "  1> {$global['systemRootPath']}videos/{$queue_id}_tmpFile_downloadProgress.txt  2>&1", $output, $return_val);
                if ($return_val !== 0) {
                    //echo "\n**ERROR Youtube DL **".$code . "\n" . print_r($output, true);
                    error_log($cmd . "\n" . print_r($output, true));
                    return false;
                }
            }
        }
        $file = $tmpfname . ".mp4";
        if (!file_exists($file)) {
            error_log("getYoutubeDl: ERROR MP4 NOT FOUND {$file} ");
            $mkvFile = $tmpfname . ".mkv";
            if (file_exists($mkvFile)) {
                $file = $mkvFile;
            } else {
                error_log("getYoutubeDl: ERROR MKV NOT FOUND {$mkvFile} ");
                $dl = static::getYoutubeDlProgress($queue_id);
                $file = $dl->filename;
            }
        }
        error_log("getYoutubeDl: Copying [$file] to [$destinationFile] ");
        // instead of loading the whole file into memory to dump it into a new filename
        // the file is just symlinked
        //////symlink($file, $destinationFile);
        ////// symlink not allowed without apache configuration
        copy($file, $destinationFile);
        return url_set_file_context($file);
    }

    static function getYoutubeDlProgress($queue_id) {
        global $global;
        $obj = new stdClass();
        $obj->filename = "";
        $obj->progress = 0;
        $file = "{$global['systemRootPath']}videos/{$queue_id}_tmpFile_downloadProgress.txt";
        if (!file_exists($file) || filesize($file) > 5000000) {
            return $obj;
        }
        try {
            $text = url_get_contents($file);
        } catch (Exception $exc) {
            error_log($exc->getMessage());
        }

        if (!empty($text)) {
            preg_match('/Merging formats into "([\/a-z0-9._]+)"/i', $text, $matches);
            if (!empty($matches[1])) {
                $obj->filename = $matches[1];
            }
            preg_match_all('/\[download\] +([0-9.]+)% of/', $text, $matches, PREG_SET_ORDER);
            //$m = end($matches);
            //$obj->progress = empty($m[1]) ? 0 : intval($m[1]);
            foreach ($matches as $m) {
                $obj->progress = empty($m[1]) ? 0 : intval($m[1]);
                if ($obj->progress == 100) {
                    break;
                }
            }
        }
        return $obj;
    }

    static function getVideoFile($videoURL, $queue_id, $downloadedFile, $destinationFile) {
        // the file has already been downloaded
        // all that is needed to do is create a tempfile reference to the original
        // symlink($downloadedFile, $destinationFile);
        global $global;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
                "allow_self_signed" => true
            ),
        );
        $global['queue_id'] = $queue_id;
        $ctx = stream_context_create($arrContextOptions);
        /////whoops! apache has to be taught to use symlinks so this won't work
        /////trying copy instead
        copy($downloadedFile, $destinationFile, $ctx);
        //copied from stream_contenxt_set_params
        // the file is already 100% downloaded by now
        $txt = "[download]  100% of all Bytes";
        // save this progress file
        $myfile = file_put_contents($global['systemRootPath'] . 'videos/' . $global['queue_id'] . '_tmpFile_downloadProgress.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);
        //url_get_contents is what drives the red bar and causes the memory failure
        $return = url_set_file_context($videoURL, $ctx);

        //And OhioVR also did this:
        $return = url_set_file_context($videoURL, $ctx);
        if (!$return) {
            $fixedEncodedUrl = utf8_encode($videoURL);
            //error_log("Try to get UTF8 URL {$fixedEncodedUrl}");
            $return = url_set_file_context($videoURL, $ctx);
            if (!$return) {
                $fixedEncodedUrl = utf8_decode($videoURL);
                error_log("Try to get UTF8 decode URL {$fixedEncodedUrl}");
                $return = url_set_file_context($videoURL, $ctx);
            }
        }
        return $return;
    }

    static function areEncoding() {
        global $global;
        $sql = "SELECT f.*, e.* FROM  " . static::getTableName() . " e "
                . " LEFT JOIN {$global['tablesPrefix']}formats f ON f.id = formats_id WHERE status = 'encoding' OR  status = 'downloading' ORDER BY priority ASC, e.id ASC ";

        $res = $global['mysqli']->query($sql);
        $results = array();
        if ($res) {
            while ($result = $res->fetch_assoc()) {
                $encoder = new Encoder($result['id']);
                /* Do not auto add to queue here, it was causing an reencode on refresh page
                  if (!$encoder->isWorkerRunning()) {
                  $encoder->setStatus("queue");
                  $encoder->setStatus_obs("Worker died");
                  $encoder->save();
                  continue;
                  }
                 * 
                 */
                $result['return_vars'] = json_decode($result['return_vars']);
                $s = new Streamer($result['streamers_id']);
                $result['streamer_site'] = $s->getSiteURL();
                $result['streamer_priority'] = $s->getPriority();
                $results[] = $result;
            }
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $results;
    }

    /*
      static function isTransferring() {
      global $global;
      $sql = "SELECT f.*, e.* FROM  " . static::getTableName() . " e "
      . " LEFT JOIN {$global['tablesPrefix']}formats f ON f.id = formats_id WHERE status = 'transferring' ";

      $res = $global['mysqli']->query($sql);

      $sql .= " ORDER BY priority ASC, e.id ASC LIMIT 1";

      if ($res) {
      $result = $res->fetch_assoc();
      if (!empty($result)) {
      $result['return_vars'] = json_decode($result['return_vars']);
      $s = new Streamer($result['streamers_id']);
      $result['streamer_site'] = $s->getSiteURL();
      $result['streamer_priority'] = $s->getPriority();
      return $result;
      } else {
      return false;
      }
      } else {
      die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
      }
      return false;
      }
     *
     */

    static function getAllQueue() {
        global $global;
        $sql = "SELECT f.*, e.* FROM  " . static::getTableName() . " e "
                . " LEFT JOIN {$global['tablesPrefix']}formats f ON f.id = formats_id WHERE (status = 'encoding' OR  status = 'downloading' OR status = 'queue' OR status = 'error') ";

        $sql .= " ORDER BY priority ASC, e.id ASC ";
        $res = $global['mysqli']->query($sql);
        $rows = array();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['return_vars'] = json_decode($row['return_vars']);
                $s = new Streamer($row['streamers_id']);
                $row['streamer_site'] = $s->getSiteURL();
                $row['streamer_priority'] = $s->getPriority();
                $rows[] = $row;
            }
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $rows;
    }

    static function getFromFilename($filename) {
        global $global;
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE filename = '$filename' LIMIT 1 ";

        $res = $global['mysqli']->query($sql);
        if ($res) {
            return $res->fetch_assoc();
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return false;
    }

    static function getFromFileURI($fileURI) {
        global $global;
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE fileURI = '$fileURI' LIMIT 1 ";

        $res = $global['mysqli']->query($sql);
        if ($res) {
            return $res->fetch_assoc();
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return false;
    }

    function isWorkerRunning() {
        $ppid = $this->getWorker_ppid();
        if (empty($ppid))
            return false;

        exec("kill -0 " . $ppid, $output, $ppid_retval);
        if ($ppid_retval != 0)
            return false;

        $pid = $this->getWorker_pid();
        if (!is_numeric($pid))
            return false;

        /*
         * We have a parent ($ppid != 0) but no child ($pid == 0)
         * when are between two formats.
         */
        if ($pid != 0) {
            exec("kill -0 " . $pid, $output, $pid_retval);
            if ($pid_retval != 0)
                return false;
        }
        return true;
    }

    function exec($cmd, &$output = array(), &$return_val = 0) {
        if (function_exists("pcntl_fork")) {
            if (($status = $this->getStatus()) != "encoding") {
                error_log("id(" . $this->getId() . ") status(" . $status . ") abort");
                $return_val = 1;
                return;
            }
            switch ($pid = pcntl_fork()) {
                case -1:
                    $msg = "fork failed";
                    error_log("id(" . $this->getId() . ") " . $msg);
                    $this->setStatus("error");
                    $this->setStatus_obs($msg);
                    $this->save();
                    break;
                default:
                    $this->setWorker_pid($pid);
                    $this->save();
                    pcntl_wait($status);
                    if (pcntl_wifexited($status)) {
                        $return_val = pcntl_wexitstatus($status);
                    } else {
                        $return_val = 1;
                    }
                    if (pcntl_wifsignaled($status))
                        error_log("id=(" . $this->getId() . "), process " . $pid . " got signal " . pcntl_wtermsig($status));
                    $this->setWorker_pid(NULL);
                    $this->save();
                    break;
                case 0:
                    $argv = array("-c", $cmd);
                    $envp = array(
                        "PATH=" . getenv("PATH"),
                        "LD_LIBRARY_PATH=" . getenv("LD_LIBRARY_PATH")
                    );
                    pcntl_exec("/bin/sh", $argv, $envp);
                    error_log("id=(" . $this->getId() . "), " . $cmd . " failed: " . pnctl_strerror(pnctl_get_last_error()));
                    exit(1);
                    break;
            }
        } else {
            exec(replaceFFMPEG($cmd), $output, $return_val);
        }

        return;
    }

    function deleteQueue($notifyStreamer = false) {
        $worker_pid = $this->getWorker_pid();
        $worker_ppid = $this->getWorker_ppid();
        $this->setStatus("error");
        $this->setStatus_obs("deleted from queue");
        if (!empty($global['killWorkerOnDelete'])) {
            if (is_numeric($worker_pid) && $worker_pid > 0) {
                exec("kill " . $worker_pid); // ignore result
            }
            if (is_numeric($worker_ppid) && $worker_ppid > 0) {
                exec("kill " . $worker_ppid); // ignore result
            }
        }
        if ($notifyStreamer) {
            $this->notifyVideoIsDone(1);
        }
    }

    static function run($try = 0) {
        global $global;
        $maxTries = 3;

        if ($try > $maxTries) {
            return false;
        }

        $concurrent = isset($global['concurrent']) ? intval($global['concurrent']) : 1;
        if (empty($concurrent) || $concurrent < 0) {
            $concurrent = 1;
        }
        $try++;
        $obj = new stdClass();
        $obj->error = true;
        // check if is encoding something
        //error_log("Encoder::run: try=($try)");
        $rows = static::areEncoding();
        if (count($rows) < $concurrent) {
            $row = static::getNext();
            if (empty($row)) {
                $obj->msg = "There is no file on queue";
            } else {
                $encoder = new Encoder($row['id']);
                $return_vars = json_decode($encoder->getReturn_vars());
                $encoder->setStatus("downloading");
                $encoder->setStatus_obs("Start in " . date("Y-m-d H:i:s"));
                $encoder->save();
                $objFile = static::downloadFile($encoder->getId());
                if ($objFile->error) {
                    if ($try <= $maxTries) {
                        $msg = "Encoder::run: Trying again: [$try] => Could not download the file ";
                        error_log($msg);
                        $encoder->setStatus("queue");
                        $encoder->setStatus_obs($msg);
                        $encoder->save();
                        return self::run($try);
                    } else {
                        $msg = "Encoder::run: Max tries reached";
                        error_log($msg);
                        $obj->msg = $objFile->msg;
                        $encoder->setStatus("error");
                        $encoder->setStatus_obs($msg);
                        $encoder->save();
                        return false;
                    }
                } else if (!empty($return_vars->videos_id)) {
                    $encoder->setStatus("encoding");
                    $encoder->save();
                    self::sendImages($objFile->pathFileName, $return_vars, $encoder);
                    // get the encode code and convert it
                    $code = new Format($encoder->getFormats_id());
                    $resp = $code->run($objFile->pathFileName, $encoder->getId());
                    if (!empty($resp->error)) {
                        if ($resp->error === -1) {
                            return false;
                        } else if ($try < 4) {
                            $msg = "Encoder::run: Trying again: [$try] => Execute code error 1 " . json_encode($resp->msg) . " \n Code: {$resp->code}";
                            error_log($msg);
                            $encoder->setStatus("queue");
                            $encoder->setStatus_obs($msg);
                            $encoder->save();
                            return static::run($try);
                        } else {
                            $obj->msg = "Execute code error 2 " . json_encode($resp->msg) . " \n Code: {$resp->code}";
                            error_log("Encoder::run: Encoder Run: " . json_encode($obj));
                            $encoder->setStatus("error");
                            $encoder->setStatus_obs($obj->msg);
                            $encoder->save();
                            return false;
                        }
                    } else {
                        // if is audio send the spectrum image as well
                        if ($encoder->getFormats_id() == 6) {
                            self::sendSpectrumFromMP3($objFile->pathFileName, $return_vars, $encoder);
                        }
                        $obj->error = false;
                        $obj->msg = $resp->code;
                        $videos_id = 0;
                        if (!empty($return_vars->videos_id)) {
                            $videos_id = $return_vars->videos_id;
                        }
                        // notify AVideo it is done
                        $response = $encoder->send();
                        if (!$response->error) {
                            // update queue status
                            $encoder->setStatus("done");
                            $config = new Configuration();
                            if (!empty($config->getAutodelete())) {
                                $encoder->delete();
                            } else {
                                error_log("Encoder::run: Autodelete Not active");
                            }
                            $encoder->notifyVideoIsDone();
                            $encoder->save();
                        } else {
                            $msg = "Encoder::run: Send message error = " . $response->msg;
                            error_log($msg);
                            $encoder->setStatus("error");
                            $encoder->setStatus_obs($msg);
                            $encoder->notifyVideoIsDone(1);
                            $encoder->save();
                            return false;
                        }
                    }
                } else {
                    error_log("try [{$try}] return_vars->videos_id is empty " . json_encode($return_vars));
                    $encoder->setStatus("error");
                    $encoder->setStatus_obs("try [{$try}] Error on return_vars->videos_id");
                    $encoder->notifyVideoIsDone(1);
                    $encoder->save();
                    return false;
                }
                return static::run($try);
            }
        } else {
            $msg = (count($rows) == 1) ? "The file " : "The files ";
            for ($i = 0; $i < count($rows); $i++) {
                $row = $rows[$i];
                $msg .= "[{$row['id']}] {$row['filename']}";
                if (count($rows) > 1 && $i < count($rows) - 1)
                    $msg .= ", ";
            }
            $msg .= (count($rows) == 1) ? " is encoding" : " are encoding";
            $obj->msg = $msg;
        }
        return $obj;
    }

    private function notifyVideoIsDone($fail = 0) {
        global $global;
        $obj = new stdClass();
        $return_vars = json_decode($this->getReturn_vars());
        if (!empty($return_vars->videos_id)) {
            $videos_id = $return_vars->videos_id;
            $video_id_hash = $return_vars->video_id_hash;
            $obj->error = true;

            $streamers_id = $this->getStreamers_id();
            $s = new Streamer($streamers_id);
            $user = $s->getUser();
            $pass = $s->getPass();

            $s = new Streamer($streamers_id);
            $aVideoURL = $s->getSiteURL();

            $target = $aVideoURL . "objects/aVideoEncoderNotifyIsDone.json.php";
            $obj->target = $target;
            error_log("AVideo-Encoder sending confirmation to {$target}");
            $postFields = array(
                'videos_id' => $videos_id,
                'video_id_hash' => $video_id_hash,
                'user' => $user,
                'password' => $pass,
                'fail' => $fail
            );

            if (!empty($this->override_status))
                $postFields['overrideStatus'] = $this->override_status;

            $obj->postFields = $postFields;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $target);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            //curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            $r = curl_exec($curl);
            error_log("AVideo-Streamer confirmation answer {$r}");
            $obj->response_raw = $r;
            $obj->response = json_decode($r);
            if ($errno = curl_errno($curl)) {
                $error_message = curl_strerror($errno);
                //echo "cURL error ({$errno}):\n {$error_message}";
                $obj->msg = "cURL error ({$errno}):\n {$error_message} ";
            } else {
                $obj->error = false;
            }
            curl_close($curl);
        }
        error_log(json_encode($obj));
        return $obj;
    }

    private function multiResolutionSend($resolution, $format, $return_vars) {
        global $global;
        error_log("Encoder::multiResolutionSend($resolution, $format, {$return_vars->videos_id})");
        $file = self::getTmpFileName($this->id, $format, $resolution);
        $r = static::sendFileChunk($file, $return_vars, $format, $this, $resolution);
        return $r;
    }

    public static function getTmpFileName($encoder_queue_id, $format, $resolution = '') {
        global $global;

        $encoder = new Encoder($encoder_queue_id);
        $streamers_id = $encoder->getStreamers_id();

        if (!empty($resolution)) {
            $resolution = "_{$resolution}";
        }

        $file = $global['systemRootPath'] . "videos/avideoTmpFile_{$encoder_queue_id}_streamers_id_{$streamers_id}_{$resolution}.{$format}";
        return $file;
    }

    public static function getTmpFiles($encoder_queue_id) {
        global $global;

        $encoder = new Encoder($encoder_queue_id);
        $streamers_id = $encoder->getStreamers_id();
        $file = $global['systemRootPath'] . "videos/avideoTmpFile_{$encoder_queue_id}_streamers_id_{$streamers_id}_";

        $files = glob("{$file}*");

        $hlsZipFile = Encoder::getTmpFileName($encoder_queue_id, 'zip', "converted");
        //$hlsZipFile = $global['systemRootPath'] . "videos/{$encoder_queue_id}_tmpFile_converted.zip";
        if (file_exists($hlsZipFile)) {
            $files[] = $hlsZipFile;
        }
        return $files;
    }

    public static function getAllFilesInfo($encoder_queue_id) {
        $files = Encoder::getTmpFiles($encoder_queue_id);
        $info = array();
        foreach ($files as $file) {
            $info[] = getFileInfo($file);
        }
        return $info;
    }

    function verify() {
        $streamers_id = $this->getStreamers_id();
        if(empty($streamers_id)){
            error_log("encoder:verify streamer id is empty");
            return false;
        }

        $streamer = new Streamer($streamers_id);

        if(empty($streamer->getSiteURL())){
            error_log("encoder:verify sire URL is empty streamers_id={$streamers_id}");
            return false;
        }

        return $streamer->verify();
    }

    function send() {
        global $global;
        $formatId = $this->getFormats_id();
        $f = new Format($formatId);
        $order_id = $f->getOrder();
        $return_vars = json_decode($this->getReturn_vars());
        $videos_id = (!empty($return_vars->videos_id) ? $return_vars->videos_id : 0);
        $return = new stdClass();
        $return->sends = array();
        $return->formats_id = $this->getFormats_id();
        $return->error = false;
        $return->original_videos_id = $videos_id;
        $return->videos_id = 0;

        $this->setStatus("transferring");
        $this->save();
        error_log("Encoder::send() order_id=$order_id");
        if (in_array($order_id, $global['multiResolutionOrder'])) {
            //error_log("Encoder::send() multiResolutionOrder");
            if (in_array($order_id, $global['sendAll'])) {
                $files = self::getTmpFiles($this->id);
                //error_log("Encoder::send() multiResolutionOrder sendAll found (" . count($files) . ") files");
                foreach ($files as $file) {
                    if (is_dir($file)) {
                        continue;
                    }
                    $format = pathinfo($file, PATHINFO_EXTENSION);
                    preg_match('/([^_]+).' . $format . '$/', $file, $matches);
                    $resolution = @$matches[1];
                    if ($resolution == 'converted') {
                        $resolution = '';
                    }
                    //error_log("Encoder::send() multiResolutionOrder sendAll resolution($resolution) ($file)");
                    $return->sends[] = self::sendFileChunk($file, $return_vars, $format, $this, $resolution);
                }
            } else {
                if (in_array($order_id, $global['hasHDOrder'])) {
                    $return->sends[] = $this->multiResolutionSend("HD", "mp4", $return_vars);
                    if (in_array($order_id, $global['bothVideosOrder'])) { // make the webm too
                        $return->sends[] = $this->multiResolutionSend("HD", "webm", $return_vars);
                    }
                }
                if (in_array($order_id, $global['hasSDOrder'])) {
                    $return->sends[] = $this->multiResolutionSend("SD", "mp4", $return_vars);
                    if (in_array($order_id, $global['bothVideosOrder'])) { // make the webm too
                        $return->sends[] = $this->multiResolutionSend("SD", "webm", $return_vars);
                    }
                }
                if (in_array($order_id, $global['hasLowOrder'])) {
                    $return->sends[] = $this->multiResolutionSend("Low", "mp4", $return_vars);
                    if (in_array($order_id, $global['bothVideosOrder'])) { // make the webm too
                        $return->sends[] = $this->multiResolutionSend("Low", "webm", $return_vars);
                    }
                }
            }
        } else {
            //error_log("Encoder::send() NOT multiResolutionOrder");
            $extension = $f->getExtension();
            if ($formatId == 29 || $formatId == 30) { // if it is HLS send the compacted file
                $extension = "zip";
            }
            if (empty($global['webmOnly'])) {
                //error_log("Encoder::send webmOnly");
                $file = self::getTmpFileName($this->id, $extension);
                $r = static::sendFileChunk($file, $return_vars, $extension, $this);
                error_log("Encoder::send() response " . json_encode($r));
                $return->videos_id = $r->response->video_id;
                $return->video_id_hash = $r->response->video_id_hash;
                $this->setReturn_varsVideos_id($return->videos_id);
                $this->setReturn_varsVideo_id_hash($return->video_id_hash);
            }
            if ($order_id == 70 || $order_id == 50) { // if it is Spectrum send the webm also
                $extension = "webm";
                //error_log("Encoder::send Spectrum send the web");
                $file = self::getTmpFileName($this->id, $extension);
                $r = static::sendFileChunk($file, $return_vars, $extension, $this);
                error_log("Encoder::send() response " . json_encode($r));
            }

            if ($r->error) {
                $return->error = true;
                $return->msg = $r->msg;
            }
            $return->sends[] = $r;
        }
        $this->setStatus("done");
        // check if autodelete is enabled
        $config = new Configuration();
        if (!empty($config->getAutodelete())) {
            $this->delete();
        } else {
            //error_log("Encoder::send: Autodelete Not active");
        }
        $this->save();
        return $return;
    }

    static function sendFile($file, $return_vars, $format, $encoder = null, $resolution = "", $chunkFile = "") {
        global $global;
        global $sentImage;

        $obj = new stdClass();
        $obj->error = true;
        $obj->format = $format;
        $obj->file = $file;
        $obj->resolution = $resolution;
        $obj->videoDownloadedLink = $encoder->getVideoDownloadedLink();
        $videos_id = $return_vars->videos_id;
        
        if(is_object($return_vars) && !empty($_REQUEST['callback'])){
            $return_vars->callback = $_REQUEST['callback'];
        }
        
        if (!empty($global['progressiveUpload']) && isset($encoder)) {
            $encoder_id = $encoder->getId();
            if(empty($encoder_id)){
                $obj->msg = "encoder_id is empty";
                error_log("Encoder::sendFile {$obj->msg} ".json_encode($encoder));
                return $obj;
            }
            $u = Upload::loadFromEncoder($encoder_id, $resolution, $format);
            if ($u !== false && $u->getStatus() == "done") {
                $obj->error = false;
                $obj->msg = "Already sent";
                error_log("Encoder::sendFile already sent videos_id={$videos_id}, format=$format");
                return $obj;
            }
        }

        error_log("Encoder::sendFile videos_id={$videos_id}, format=$format");

        $duration = static::getDurationFromFile($file);
        if ($duration == "EE:EE:EE" && $file != "") {
            if (isset($u) && $u !== false && $obj->error == false) {
                $u->setStatus("error");
                $u->save();
            }

            $obj->error = true;
            $obj->msg = "Corrupted output";
            error_log("Encoder::sendFile videos_id={$videos_id}, format=$format: discard corrupted output file");
            return $obj;
        }

        if (empty($_POST['title'])) {
            $title = $encoder->getTitle();
        } else {
            $title = $_POST['title'];
        }
        if (empty($_POST['description'])) {
            if (!empty($obj->videoDownloadedLink)) {
                $description = $encoder->getDescriptionFromLink($obj->videoDownloadedLink);
            } else {
                $description = "";
            }
        } else {
            $description = $_POST['description'];
        }
        if (empty($_POST['categories_id'])) {
            $categories_id = 0;
        } else {
            $categories_id = $_POST['categories_id'];
        }
        if (empty($_POST['usergroups_id'])) {
            $usergroups_id = array();
        } else {
            $usergroups_id = $_POST['usergroups_id'];
        }

        $streamers_id = $encoder->getStreamers_id();
        $s = new Streamer($streamers_id);
        $aVideoURL = $s->getSiteURL();
        $user = $s->getUser();
        $pass = $s->getPass();

        $keep_encoding = !empty($global['progressiveUpload']);

        $target = trim($aVideoURL . "aVideoEncoder.json");
        $obj->target = $target;
        error_log("Encoder::sendFile sending file to {$target} from {$file}");
        if (!is_object($return_vars)) {
            error_log('$return_vars is empty -[' . json_encode($return_vars) . ']- ' . json_encode(debug_backtrace()));
        }

        if (empty($return_vars->video_id_hash) || empty($videos_id)) {
            error_log("Encoder::sendFile 1 " . json_encode($return_vars));
            error_log("Encoder::sendFile 2 " . json_encode(debug_backtrace()));
        }

        $downloadURL = '';
        $dfile = str_replace($global['systemRootPath'], "", $file);
        if (!empty($dfile)) {
            $downloadURL = "{$global['webSiteRootURL']}{$dfile}";
        }

        $postFields = array(
            'duration' => $duration,
            'title' => $title,
            'videos_id' => $videos_id,
            'video_id_hash' => @$return_vars->video_id_hash,
            'first_request' => 1,
            'categories_id' => $categories_id,
            'format' => $format,
            'resolution' => $resolution,
            'videoDownloadedLink' => $obj->videoDownloadedLink,
            'description' => $description,
            'user' => $user,
            'password' => $pass,
            'downloadURL' => $downloadURL,
            'chunkFile' => $chunkFile,
            'encoderURL' => $global['webSiteRootURL'],
            'keepEncoding' => $keep_encoding ? "1" : "0",
            'return_vars' => json_encode($return_vars),
        );

        if (!empty($encoder->override_status)) {
            $override_status = $encoder->override_status;

            // If unfinished progressive upload, status is
            // active and coding (k) if active (a) was requested
            // or encoding (e) otherwise.
            if ($keep_encoding)
                $override_status = $override_status == 'a' ? 'k' : 'e';

            $postFields['overrideStatus'] = $override_status;
        }

        $count = 0;
        foreach ($usergroups_id as $value) {
            $postFields["usergroups_id[{$count}]"] = $value;
            $count++;
        }
        $obj->postFields = $postFields;

        if (!empty($file)) {
            $postFields['video'] = new CURLFile($file);
            if ($format == "mp4" && !in_array($videos_id, $sentImage)) {
                // do not send image twice
                $sentImage[] = $videos_id;
                //$postFields['image'] = new CURLFile(static::getImage($file, intval(static::parseDurationToSeconds($duration) / 2)));
                //$postFields['gifimage'] = new CURLFile(static::getGifImage($file, intval(static::parseDurationToSeconds($duration) / 2), 3));
            }
            $obj->videoFileSize = humanFileSize(filesize($file));
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $target);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $r = remove_utf8_bom(curl_exec($curl));
        error_log("AVideo-Streamer answer 1 {$r}");
        $obj->postFields = count($postFields);
        $obj->response_raw = $r;
        $obj->response = json_decode($r);
        if ($errno = curl_errno($curl) || empty($obj->response) || !is_object($obj->response)) {
            $error_message = curl_strerror($errno);
            //echo "cURL error ({$errno}):\n {$error_message}";
            $obj->msg = "cURL error ({$errno}):\n {$error_message} \n {$file} \n ({$target})\n {$chunkFile} ";
        } else {
            $obj->error = false;
        }
        curl_close($curl);
        error_log(json_encode($obj));
        if (is_object($obj->response) && !empty($obj->response->video_id)) {
            $encoder->setReturn_varsVideos_id($obj->response->video_id);
        }
        if (is_object($obj->response) && !empty($obj->response->video_id_hash)) {
            $encoder->setReturn_varsVideo_id_hash($obj->response->video_id_hash);
        }
        //var_dump($obj);exit;

        if (isset($u) && $u !== false && $obj->error == false) {
            $u->setStatus("done");
            $u->save();
        }
        return $obj;
    }

    static function sendFileChunk($file, $return_vars, $format, $encoder = null, $resolution = "", $try = 0) {

        $obj = new stdClass();
        $obj->error = true;
        $obj->file = $file;
        $obj->filesize = filesize($file);
        if (empty($file) || !file_exists($file)) {
            $msg = "sendFileChunk: file ({$file}) is empty or do not exist";
            error_log($msg);
            $obj->response = $msg;
            return $obj;
        }

        if (!preg_match('/\.zip$/', $file) && Format::videoFileHasErrors($file)) {
            $msg = "sendFileChunk: we found errors on video file ({$file}) we will not transfer it";
            error_log($msg);
            $obj->response = $msg;
            return $obj;
        }

        $obj = self::sendFileToDownload($file, $return_vars, $format, $encoder, $resolution);
        if (empty($obj->error)) {
            error_log("Encoder:sendFileChunk no need, we could download");
            return $obj;
        }

        error_log("Encoder:sendFileChunk($file,{$return_vars->videos_id}, $format, object, $resolution, $try)");
        $try++;
        $obj = new stdClass();
        $obj->error = true;
        $obj->file = $file;
        $obj->filesize = filesize($file);
        $obj->response = "";

        error_log("Encoder::sendFileChunk file=$file");

        $stream = fopen($file, 'r');
        $streamers_id = $encoder->getStreamers_id();
        $s = new Streamer($streamers_id);
        $aVideoURL = $s->getSiteURL();

        $target = trim($aVideoURL . "aVideoEncoderChunk.json");
        $obj->target = $target;
        // Create a curl handle to upload to the file server
        $ch = curl_init($target);
        // Send a PUT request
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        // Let curl know that we are sending an entity body
        curl_setopt($ch, CURLOPT_UPLOAD, true);
        // Let curl know that we are using a chunked transfer encoding
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Transfer-Encoding: chunked'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // Use a callback to provide curl with data to transmit from the stream
        global $countCURLOPT_READFUNCTION;
        $countCURLOPT_READFUNCTION = 0;
        curl_setopt($ch, CURLOPT_READFUNCTION, function($ch, $fd, $length) use ($stream) {
            global $countCURLOPT_READFUNCTION;
            $countCURLOPT_READFUNCTION++;
            return fread($stream, 1024);
        });
        $r = curl_exec($ch);
        $errno = curl_errno($ch);
        $error_message = curl_strerror($errno);
        //var_dump($r, $errno, $error_message);
        curl_close($ch);

        $r = remove_utf8_bom($r);
        error_log("AVideo-Streamer countCURLOPT_READFUNCTION = ($countCURLOPT_READFUNCTION) chunk answer {$r}");
        $obj->response_raw = $r;
        $obj->response = json_decode($r);
        if ($errno || empty($obj->response->filesize) || ($obj->response->filesize < $obj->filesize)) {
            if (is_object($obj->response) && $obj->response->filesize < $obj->filesize) {
                error_log("cURL error, file size is smaller, trying again ($try) ({$errno}):\n {$error_message} \n {$file} \n {$target} streamer filesize = " . humanFileSize($obj->response->filesize) . " local Encoder file size =  " . humanFileSize($obj->filesize));
            } else {
                error_log("cURL error, trying again ($try) ({$errno}):\n {$error_message} \n {$file} \n ({$target})");
            }
            if ($try <= 3) {
                sleep($try);
                return self::sendFileChunk($file, $return_vars, $format, $encoder, $resolution, $try);
            } else {
                //echo "cURL error ({$errno}):\n {$error_message}";
                $obj->msg = "cURL error ({$errno}):\n {$error_message} \n {$file} \n ({$target})";
                error_log(json_encode($obj));
                return self::sendFile($file, $return_vars, $format, $encoder, $resolution, $try);
            }
        } else {
            error_log("cURL success, Local file: " . humanFileSize($obj->filesize) . " => Transferred file " . humanFileSize($obj->response->filesize));
            $obj->error = false;
            error_log(json_encode($obj));
            return self::sendFile(false, $return_vars, $format, $encoder, $resolution, $obj->response->file);
        }
    }

    static function sendFileToDownload($file, $return_vars, $format, $encoder = null, $resolution = "", $try = 0) {
        global $global;
        global $sentImage;

        $obj = new stdClass();
        $obj->error = true;
        $obj->format = $format;
        $obj->file = $file;
        $obj->resolution = $resolution;
        $obj->videoDownloadedLink = $encoder->getVideoDownloadedLink();
        //error_log("Encoder::sendFileToDownload videos_id=$videos_id, format=$format");
        if (empty($duration)) {
            $duration = static::getDurationFromFile($file);
        }
        if (empty($_POST['title'])) {
            $title = $encoder->getTitle();
        } else {
            $title = $_POST['title'];
        }
        if (empty($_POST['description'])) {
            if (!empty($obj->videoDownloadedLink)) {
                $description = $encoder->getDescriptionFromLink($obj->videoDownloadedLink);
            } else {
                $description = "";
            }
        } else {
            $description = $_POST['description'];
        }
        if (empty($_POST['categories_id'])) {
            $categories_id = 0;
        } else {
            $categories_id = $_POST['categories_id'];
        }
        if (empty($_POST['usergroups_id'])) {
            $usergroups_id = array();
        } else {
            $usergroups_id = $_POST['usergroups_id'];
        }

        $streamers_id = $encoder->getStreamers_id();
        $s = new Streamer($streamers_id);
        $aVideoURL = $s->getSiteURL();
        $user = $s->getUser();
        $pass = $s->getPass();

        $target = trim($aVideoURL . "aVideoEncoder.json");
        $obj->target = $target;
        //error_log("Encoder::sendFileToDownload sending file to {$target} from {$file}");

        $downloadURL = '';
        $dfile = str_replace($global['systemRootPath'], "", $file);
        if (!empty($dfile)) {
            $downloadURL = "{$global['webSiteRootURL']}{$dfile}";
        }
        error_log("Encoder::sendFileToDownload [file=$file], [download=$downloadURL]");
        $postFields = array(
            'duration' => $duration,
            'title' => $title,
            'videos_id' => $return_vars->videos_id,
            'video_id_hash' => @$return_vars->video_id_hash,
            'categories_id' => $categories_id,
            'format' => $format,
            'resolution' => $resolution,
            'videoDownloadedLink' => $obj->videoDownloadedLink,
            'description' => $description,
            'user' => $user,
            'password' => $pass,
            'downloadURL' => $downloadURL,
            'encoderURL' => $global['webSiteRootURL']
        );
        $count = 0;
        foreach ($usergroups_id as $value) {
            $postFields["usergroups_id[{$count}]"] = $value;
            $count++;
        }
        $obj->postFields = $postFields;
        if (!empty($file)) {
            if ($format == "mp4" && !in_array($return_vars->videos_id, $sentImage)) {
                // do not send image twice
                $sentImage[] = $return_vars->videos_id;
                //$postFields['image'] = new CURLFile(static::getImage($file, intval(static::parseDurationToSeconds($duration) / 2)));
                //$postFields['gifimage'] = new CURLFile(static::getGifImage($file, intval(static::parseDurationToSeconds($duration) / 2), 3));
            }
            $obj->videoFileSize = humanFileSize(filesize($file));
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $target);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $r = remove_utf8_bom(curl_exec($curl));
        //error_log("AVideo-Streamer answer 2 {$r}");
        $obj->postFields = count($postFields);
        $obj->response_raw = $r;
        $obj->response = json_decode($r);
        if ($errno = curl_errno($curl) || empty($obj->response)) {
            $error_message = curl_strerror($errno);
            //echo "cURL error ({$errno}):\n {$error_message}";
            $obj->msg = "cURL error ({$errno}):\n {$error_message} \n {$file} \n ({$target})\n ";
        } else {
            $obj->error = false;
        }
        curl_close($curl);
        error_log("Encoder::sendFileToDownload " . json_encode($obj));
        if (empty($obj->response) || !is_object($obj->response)) {
            error_log("Encoder::sendFileToDownload response fail " . $obj->response_raw . " " . json_encode(debug_backtrace()));
        } else {
            $encoder->setReturn_varsVideos_id($obj->response->video_id);
            $encoder->setReturn_varsVideo_id_hash($obj->response->video_id_hash);
        }
        //var_dump($obj);exit;
        return $obj;
    }

    static function sendImages($file, $return_vars, $encoder) {
        global $global;

        $obj = new stdClass();
        $obj->error = true;
        $obj->file = $file;
        //error_log("sendImages: Sending image to [$return_vars->videos_id]");
        $duration = static::getDurationFromFile($file);
        $streamers_id = $encoder->getStreamers_id();
        $s = new Streamer($streamers_id);
        $aVideoURL = $s->getSiteURL();
        $user = $s->getUser();
        $pass = $s->getPass();

        $target = $aVideoURL . "objects/aVideoEncoderReceiveImage.json.php";
        $obj->target = $target;
        //error_log("sendImages: Sending image to videos_id=[$return_vars->videos_id] {$target} reading file from {$file}");
        $postFields = array(
            'duration' => $duration,
            'videos_id' => $return_vars->videos_id,
            'video_id_hash' => @$return_vars->video_id_hash,
            'user' => $user,
            'password' => $pass
        );
        $obj->postFields = $postFields;
        // check if you can get the image from youtube
        $downloadLink = $encoder->getVideoDownloadedLink();
        if (!empty($downloadLink)) {
            $destinationFile = self::getThumbsFromLink($downloadLink, true);
            if (!empty($destinationFile) && file_exists($destinationFile)) {
                $postFields['image'] = new CURLFile($destinationFile);
            }
        }
        if (!empty($file)) {
            $seconds = intval(static::parseDurationToSeconds($duration) / 2);
            if (empty($postFields['image'])) {
                $destinationImage = static::getImage($file, $seconds);
                if (file_exists($destinationImage)) {
                    $postFields['image'] = new CURLFile($destinationImage);
                    $postFields['downloadURL_image'] = str_replace(array('\\', $global['systemRootPath']), array('/', $global['webSiteRootURL']), $destinationImage);
                }
            }
            $destinationImage = static::getGifImage($file, $seconds);
            if (file_exists($destinationImage)) {
                $postFields['gifimage'] = new CURLFile($destinationImage);
                $postFields['downloadURL_gifimage'] = str_replace(array('\\', $global['systemRootPath']), array('/', $global['webSiteRootURL']), $destinationImage);
            }
            $destinationImage = static::getWebpImage($file, $seconds);
            if (file_exists($destinationImage)) {
                $postFields['webpimage'] = new CURLFile($destinationImage);
                $postFields['downloadURL_webpimage'] = str_replace(array('\\', $global['systemRootPath']), array('/', $global['webSiteRootURL']), $destinationImage);
            }
        } else {
            $obj->msg = "sendImages: File is empty {$file} ";
            error_log(json_encode($obj));
            return $obj;
        }
        //error_log("sendImages: curl_init");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $target);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        //error_log("sendImages: curl_exec");
        $r = curl_exec($curl);
        //error_log("sendImages: AVideo-Streamer answer {$r}");
        $obj->postFields = count($postFields);
        $obj->response_raw = $r;
        $obj->response = json_decode($r);

        if ($errno = curl_errno($curl)) {
            $error_message = curl_strerror($errno);
            //echo "cURL error ({$errno}):\n {$error_message}";
            $obj->msg = "cURL error ({$errno}):\n {$error_message} \n {$file} \n {$target}";
        } else {
            $obj->error = false;
        }
        curl_close($curl);
        error_log("sendImages: " . json_encode($obj));
        $encoder->setReturn_varsVideos_id($obj->response->video_id);
        if (!empty($obj->response->video_id_hash)) {
            $encoder->setReturn_varsVideo_id_hash($obj->response->video_id_hash);
        } else {
            error_log("sendImages empty video_id_hash: " . json_encode(debug_backtrace()));
        }
        //var_dump($obj);exit;
        return $obj;
    }

    static function sendSpectrumFromMP3($file, $return_vars, $encoder) {
        global $global;

        $obj = new stdClass();
        $obj->error = true;
        $obj->file = $file;
        $duration = static::getDurationFromFile($file);
        $streamers_id = $encoder->getStreamers_id();
        $s = new Streamer($streamers_id);
        $aVideoURL = $s->getSiteURL();
        $user = $s->getUser();
        $pass = $s->getPass();

        $target = $aVideoURL . "objects/aVideoEncoderReceiveImage.json.php";
        $obj->target = $target;
        error_log("sendSpectrumFromMP3: Sending image to videos_id=[$return_vars->videos_id] {$target} reading file from {$file}");
        $postFields = array(
            'duration' => $duration,
            'videos_id' => $return_vars->videos_id,
            'video_id_hash' => $return_vars->video_id_hash,
            'user' => $user,
            'password' => $pass
        );

        $obj->postFields = $postFields;
        if (!empty($file)) {
            $postFields['spectrumimage'] = new CURLFile(static::getSpectrum($file));
            $postFields['downloadURL_spectrumimage'] = str_replace(array('\\', $global['systemRootPath']), array('/', $global['webSiteRootURL']), $file);
        } else {
            $obj->msg = "SpectrumFromMP3: File is empty {$file} ";
            error_log(json_encode($obj));
            return $obj;
        }
        //error_log("SpectrumFromMP3: curl_init");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $target);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        //error_log("SpectrumFromMP3: curl_exec");
        $r = curl_exec($curl);
        error_log("SpectrumFromMP3: AVideo-Streamer answer {$r}");
        $obj->postFields = count($postFields);
        $obj->response_raw = $r;
        $obj->response = json_decode($r);

        if ($errno = curl_errno($curl)) {
            $error_message = curl_strerror($errno);
            //echo "cURL error ({$errno}):\n {$error_message}";
            $obj->msg = "cURL error ({$errno}):\n {$error_message} \n {$file} \n {$target}";
        } else {
            $obj->error = false;
        }
        curl_close($curl);
        error_log(json_encode($obj));
        $encoder->setReturn_varsVideos_id($obj->response->video_id);
        $encoder->setReturn_varsVideo_id_hash($obj->response->video_id_hash);
        //var_dump($obj);exit;
        return $obj;
    }

    static function getVideoConversionStatus($encoder_queue_id) {
        global $global;
        $progressFilename = "{$global['systemRootPath']}videos/{$encoder_queue_id}_tmpFile_progress.txt";
        $content = url_get_contents($progressFilename);
        if (!empty($content)) {
            return self::parseProgress($content);
        }

        return false;
    }

    static private function parseProgress($content) {
        //get duration of source

        $obj = new stdClass();

        $obj->duration = 0;
        $obj->currentTime = 0;
        $obj->progress = 0;
        $obj->from = '';
        $obj->to = '';
        //var_dump($content);exit;
        preg_match("/Duration: (.*?), start:/", $content, $matches);
        if (!empty($matches[1])) {

            $rawDuration = $matches[1];

            //rawDuration is in 00:00:00.00 format. This converts it to seconds.
            $ar = array_reverse(explode(":", $rawDuration));
            $duration = floatval($ar[0]);
            if (!empty($ar[1])) {
                $duration += intval($ar[1]) * 60;
            }
            if (!empty($ar[2])) {
                $duration += intval($ar[2]) * 60 * 60;
            }

            //get the time in the file that is already encoded
            preg_match_all("/time=(.*?) bitrate/", $content, $matches);

            $rawTime = array_pop($matches);

            //this is needed if there is more than one match
            if (is_array($rawTime)) {
                $rawTime = array_pop($rawTime);
            }

            //rawTime is in 00:00:00.00 format. This converts it to seconds.
            $ar = array_reverse(explode(":", $rawTime));
            $time = floatval($ar[0]);
            if (!empty($ar[1])) {
                $time += intval($ar[1]) * 60;
            }
            if (!empty($ar[2])) {
                $time += intval($ar[2]) * 60 * 60;
            }

            if (!empty($duration)) {
                //calculate the progress
                $progress = round(($time / $duration) * 100);
            } else {
                $progress = 'undefined';
            }
            $obj->duration = $duration;
            $obj->currentTime = $time;
            $obj->remainTime = ($obj->duration - $time);
            $obj->remainTimeHuman = secondsToVideoTime($obj->remainTime);
            $obj->progress = $progress;
        }

        preg_match("/Input[a-z0-9 #,]+from '(.*avideoTmpFile_.*)'/", $content, $matches);
        if (!empty($matches[1])) {
            $path_parts = pathinfo($matches[1]);
            $obj->from = $path_parts['extension'];
        }

        preg_match("/Output[a-z0-9 #,]+to '(.*avideoTmpFile_.*)'/", $content, $matches);
        if (!empty($matches[1])) {
            $path_parts = pathinfo($matches[1]);
            $obj->to = $path_parts['extension'];
        }

        return $obj;
    }

    static function getDurationFromFile($file) {
        global $config, $getDurationFromFile;
        if (empty($file)) {
            return "EE:EE:EE";
        }

        if (!isset($getDurationFromFile)) {
            $getDurationFromFile = array();
        }

        if (!empty($getDurationFromFile[$file])) {
            return $getDurationFromFile[$file];
        }

        $hls = str_replace(".zip", "/index.m3u8", $file);
        $file = str_replace(".zip", ".mp4", $file);

        // get movie duration HOURS:MM:SS.MICROSECONDS
        $videoFile = $file;
        if (!file_exists($videoFile)) {
            $file_headers = @get_headers($videoFile);
            if (!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
                error_log('getDurationFromFile try 1, File (' . $videoFile . ') Not Found');
                $videoFile = $hls;
            }
        }
        if (!file_exists($videoFile)) {
            $file_headers = @get_headers($videoFile);
            if (!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
                error_log('getDurationFromFile try 2, File (' . $videoFile . ') Not Found');
                $videoFile = '';
            }
        }
        if (empty($videoFile)) {
            return "EE:EE:EE";
        }
        //$cmd = 'ffprobe -i ' . $file . ' -sexagesimal -show_entries  format=duration -v quiet -of csv="p=0"';
        eval('$cmd=get_ffprobe()." -i \"{$videoFile}\" -sexagesimal -show_entries  format=duration -v quiet -of csv=\\"p=0\\"";');
        exec($cmd . ' 2>&1', $output, $return_val);
        if ($return_val !== 0) {
            error_log('{"status":"error", "msg":' . json_encode($output) . ' ,"return_val":' . json_encode($return_val) . ', "where":"getDuration", "cmd":"' . $cmd . '"}');
            // fix ffprobe
            $duration = "EE:EE:EE";
        } else {
            preg_match("/([0-9]+:[0-9]+:[0-9]{2})/", $output[0], $match);
            if (!empty($match[1])) {
                $duration = $match[1];
            } else {
                error_log('{"status":"error", "msg":' . json_encode($output) . ' ,"match_not_found":' . json_encode($match) . ' ,"return_val":' . json_encode($return_val) . ', "where":"getDuration", "cmd":"' . $cmd . '"}');
                $duration = "EE:EE:EE";
            }
        }
        error_log("Duration found: {$duration}");
        if ($duration !== 'EE:EE:EE') {
            $getDurationFromFile[$file] = $duration;
        }
        return $duration;
    }

    static function getImage($pathFileName, $seconds = 5) {
        global $global;
        if(preg_match('/\.mp3$/', $pathFileName)){
            error_log("getImage: do not create files from MP3 ".$pathFileName);
            return false;
        }
        $destinationFile = "{$pathFileName}.jpg";
        // do not encode again
        if (file_exists($destinationFile)) {
            error_log("getImage: file exists {$destinationFile}");
            return $destinationFile;
        }
        //eval('$ffmpeg =get_ffmpeg()." -ss {$seconds} -i {$pathFileName} -qscale:v 2 -vframes 1 -y {$destinationFile}";');
        if ($seconds > 600) {
            $seconds = 600;
        }
        $duration = static::parseSecondsToDuration($seconds);
        $time_start = microtime(true);
        // placing ss before the input is faster https://stackoverflow.com/a/27573049
        eval('$ffmpeg =get_ffmpeg(true)." -ss {$duration} -i \"{$pathFileName}\" -vframes 1 -y \"{$destinationFile}\"";');
        error_log("getImage: {$ffmpeg}");
        exec($ffmpeg . " 2>&1", $output, $return_val);
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);
        error_log("getImage: takes {$execution_time} sec to complete");
        if ($return_val !== 0 && !file_exists($destinationFile)) {
            error_log("Create Image error: {$ffmpeg} " . json_encode($output));
            return $global['systemRootPath'] . "view/img/notfound.jpg";
        } else {
            return $destinationFile;
        }
    }

    static function getSpectrum($pathFileName) {
        global $global;
        $pathFileName = str_replace(array('"', "'"), array('', ''), $pathFileName);
        $destinationFile = "{$pathFileName}_spectrum.jpg";
        // do not encode again
        if (file_exists($destinationFile)) {
            error_log("getImage: file exists {$destinationFile}");
            return $destinationFile;
        }
        $ffmpeg = get_ffmpeg() . " -i \"{$pathFileName}\" -filter_complex \"compand,showwavespic=s=1280x720:colors=FFFFFF\" {$destinationFile}";
        $time_start = microtime(true);
        error_log("getSpectrum: {$ffmpeg}");
        exec($ffmpeg . " 2>&1", $output, $return_val);
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);
        error_log("getSpectrum: takes {$execution_time} sec to complete");
        if ($return_val !== 0 && !file_exists($destinationFile)) {
            error_log("Create spectrum error: {$ffmpeg} " . json_encode($output));
            return $global['systemRootPath'] . "view/img/notfound.jpg";
        } else {
            return $destinationFile;
        }
    }

    static function getGifImage($pathFileName, $seconds = 5, $howLong = 3) {
        //error_log("getGifImage");
        
        if(preg_match('/\.mp3$/', $pathFileName)){
            error_log("getGifImage: do not create files from MP3 ".$pathFileName);
            return false;
        }
        
        global $global;
        $destinationFile = "{$pathFileName}.gif";
        // do not encode again
        if (file_exists($destinationFile)) {
            return $destinationFile;
        }
        if ($seconds > 600) {
            $seconds = 600;
        }
        $duration = static::parseSecondsToDuration($seconds);
        $time_start = microtime(true);
        //error_log("getGif: Starts");
        //generate a palette:
        $palleteFile = "{$pathFileName}palette.png";
        eval('$ffmpeg =get_ffmpeg(true)." -y -ss {$duration} -t {$howLong} -i {$pathFileName} -vf fps=10,scale=320:-1:flags=lanczos,palettegen {$palleteFile}";');
        exec($ffmpeg . " 2>&1", $output, $return_val);
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);
        error_log("getGif: takes {$execution_time} sec to complete");
        if ($return_val !== 0 && !file_exists($palleteFile)) {
            error_log("Create Pallete Gif Image error: {$ffmpeg} " . json_encode($output));
            return $global['systemRootPath'] . "view/img/notfound.gif";
        } else {
            // I've discovered that if the ss parameter comes before the input flag, a tremendous time penalty is avoided.
            // Also I've developed this ffmpeg line to allow unusual aspect videos to be letter boxed
            // so that they don't get rendered incorrectly on the avideo site. https://superuser.com/a/891478

            eval('$ffmpeg =get_ffmpeg()." -ss {$duration} -t {$howLong} -i {$pathFileName} -i {$palleteFile} -filter_complex \"fps=10,scale=(iw*sar)*min(320/(iw*sar)\,180/ih):ih*min(320/(iw*sar)\,180/ih):flags=lanczos[x];[x][1:v]paletteuse, pad=320:180:(320-iw*min(320/iw\,180/ih))/2:(180-ih*min(320/iw\,180/ih))/2\" {$destinationFile}";');
            //eval('$ffmpeg =get_ffmpeg()." -ss {$duration} -t {$howLong} -i {$pathFileName} -i {$pathFileName}palette.png -filter_complex \"fps=10,scale=320:-1:flags=lanczos[x];[x][1:v]paletteuse\" {$destinationFile}";');
            exec($ffmpeg . " 2>&1", $output, $return_val);
            if ($return_val !== 0 && !file_exists($destinationFile)) {
                error_log("Create Gif Image error 1: {$ffmpeg} " . json_encode($output));
                eval('$ffmpeg =get_ffmpeg()." -ss {$duration} -t {$howLong} -i {$pathFileName} -i {$pathFileName}palette.png -filter_complex \"fps=10,scale=320:-1:flags=lanczos[x];[x][1:v]paletteuse\" {$destinationFile}";');
                exec($ffmpeg . " 2>&1", $output, $return_val);
                if ($return_val !== 0 && !file_exists($destinationFile)) {
                    error_log("Create Gif Image error 2: {$ffmpeg} " . json_encode($output));
                    return $global['systemRootPath'] . "view/img/notfound.gif";
                } else {
                    return $destinationFile;
                }
            } else {
                return $destinationFile;
            }
        }
    }

    static function getWebpImage($pathFileName, $seconds = 5, $howLong = 3) {
        //error_log("getWebpImage");
        if(preg_match('/\.mp3$/', $pathFileName)){
            error_log("getWebpImage: do not create files from MP3 ".$pathFileName);
            return false;
        }
        global $global;
        $destinationFile = "{$pathFileName}.webp";
        // do not encode again
        if (file_exists($destinationFile)) {
            return $destinationFile;
        }
        if ($seconds > 600) {
            $seconds = 600;
        }
        $duration = static::parseSecondsToDuration($seconds);
        $time_start = microtime(true);
        //error_log("getWebpImage: Starts");
        //generate a palette:
        eval('$ffmpeg =get_ffmpeg()." -y -ss {$duration} -t {$howLong} -i {$pathFileName} -vcodec libwebp -lossless 1 -vf fps=10,scale=640:-1 -q 60 -preset default -loop 0 -an -vsync 0 {$destinationFile}";');
        exec($ffmpeg . " 2>&1", $output, $return_val);
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);
        error_log("getWebpImage: takes {$execution_time} sec to complete");
        if ($return_val !== 0 && !file_exists($destinationFile)) {
            error_log("getWebpImage:  Image error : {$ffmpeg} " . json_encode($output));
            return $global['systemRootPath'] . "view/img/notfound.gif";
        } else {
            return $destinationFile;
        }
    }

    function delete() {
        global $global;
        if (empty($this->id)) {
            return false;
        }
        $files = glob("{$global['systemRootPath']}videos/{$this->id}_tmpFile*"); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file))
                unlink($file); // delete file
            else {
                rrmdir($file);
            }
        }
        $files = glob("{$global['systemRootPath']}videos/avideoTmpFile_{$this->id}*"); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file))
                unlink($file); // delete file
            else {
                rrmdir($file);
            }
        }
        $this->deleteOriginal();

        if (!empty($global['progressiveUpload'])) {
            Upload::deleteFile($this->id);
        }

        return parent::delete();
    }

    private function deleteOriginal() {
        global $global;
        if (empty($this->id)) {
            return false;
        }
        $files = glob("{$global['systemRootPath']}videos/original_" . $this->getFilename() . "*"); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file))
                unlink($file); // delete file
        }
        return true;
    }

    static function checkList() {
        // is videos writeble
    }

    static function parseDurationToSeconds($str) {
        $durationParts = explode(":", $str);
        if (empty($durationParts[1]))
            return 0;
        $minutes = intval(intval($durationParts[0]) * 60) + intval($durationParts[1]);
        return intval($durationParts[2]) + ($minutes * 60);
    }

    static function formatDuration($str) {
        $seconds = 0;
        if (preg_match('/^[0-9]+$/', $str)) {// seconds only
            $seconds = $str;
        } else if (preg_match('/^[0-9]+:[0-9]+$/', $str)) {// seconds and minutes
            $durationParts = explode(":", $str);
            $seconds = intval(($durationParts[0]) * 60) + intval($durationParts[1]);
        } else if (preg_match('/^[0-9]+:[0-9]+:[0-9]+$/', $str)) {// seconds and minutes
            $durationParts = explode(":", $str);
            $seconds = intval(($durationParts[0]) * 60 * 60) + (($durationParts[1]) * 60) + intval($durationParts[2]);
        }
        return self::parseSecondsToDuration($seconds);
    }

    static function parseSecondsToDuration($int) {
        $seconds = $int % 60;
        if ($seconds < 10) {
            $seconds = "0{$seconds}";
        }
        $minutes = floor(($int / 60) % 60);
        if ($minutes < 10) {
            $minutes = "0{$minutes}";
        }
        $hours = floor($int / (60 * 60));
        if ($hours < 10) {
            $hours = "0{$hours}";
        }

        return "{$hours}:{$minutes}:{$seconds}";
    }

    /**
     *
     * @param type $link channel link
     * @return Array {"url": "DeHSfLqwqxg", "_type": "url", "ie_key": "Youtube", "id": "DeHSfLqwqxg", "title": "COMMERCIALS IN REAL LIFE"}
     */
    static function getReverseVideosJsonListFromLink($link) {
        $cmd = self::getYouTubeDLCommand() . "  --no-check-certificate --force-ipv4 --skip-download  --playlist-reverse --flat-playlist -j  \"{$link}\"";
        error_log("Get ReverseVideosJsonListFromLink List $cmd \n");
        exec($cmd . "  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            error_log("Get ReverseVideosJsonListFromLink List Error: $cmd \n" . print_r($output, true));
            return false;
        } else {
            $list = array();
            foreach ($output as $value) {
                $list[] = json_decode($value);
            }
            return $list;
        }
    }

    static function getTitleFromLink($link) {
        $prepend = '';
        if (!isWindows()) {
            $prepend = 'LC_ALL=en_US.UTF-8 ';
        }
        $response = array('error'=>true, 'output'=>array());
        $cmd = $prepend . self::getYouTubeDLCommand() . "  --no-check-certificate --no-playlist --force-ipv4 --skip-download -e \"{$link}\"";
        exec($cmd . "  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            error_log("getTitleFromLink: Get Title Error: $cmd \n" . print_r($output, true));
            $response['output'] = $output;
        } else {
            error_log("getTitleFromLink: Get Title: $cmd \n" . print_r($output, true));
            $response['output'] = end($output);
            $response['error'] = false;
        }
        return $response;
    }

    static function getDurationFromLink($link) {
        $cmd = self::getYouTubeDLCommand() . "  --no-check-certificate --no-playlist --force-ipv4 --get-duration --skip-download \"{$link}\"";
        exec($cmd . "  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            return false;
        } else {
            $line = end($output);
            if (preg_match('/^[0-9:]+$/', $line)) {
                return self::formatDuration($line);
            } else {
                error_log("Could not get duration {$cmd} " . json_encode($output));
                return "EE:EE:EE";
            }
        }
    }

    static function getThumbsFromLink($link, $returnFileName = false) {
        $link = str_replace(array('"', "'"), array('', ''), $link);
        $tmpfname = tempnam(sys_get_temp_dir(), 'thumbs');
        $cmd = self::getYouTubeDLCommand() . "  --no-check-certificate --no-playlist --force-ipv4 --write-thumbnail --skip-download  -o \"{$tmpfname}.jpg\" \"{$link}\"";
        exec($cmd . "  2>&1", $output, $return_val);
        error_log("getThumbsFromLink: {$cmd}");

        if ($return_val !== 0 || !file_exists("{$tmpfname}.jpg")) {
            error_log("getThumbsFromLink: Error: " . json_encode($output));
        }

        $returnTmpfname = $tmpfname . ".jpg";
        if (!@filesize($returnTmpfname)) {
            if (@filesize($returnTmpfname . '.webp')) {
                $returnTmpfname = $returnTmpfname . '.webp';
            } else
            if (@filesize($returnTmpfname . '.jpg')) {
                $returnTmpfname = $returnTmpfname . '.jpg';
            }
        }

        if ($returnFileName) {
            return $returnTmpfname;
        } else {
            $content = url_get_contents($returnTmpfname);
            unlink($returnTmpfname);
            return $content;
        }
    }

    static function getDescriptionFromLink($link) {
        if (empty($link)) {
            return '';
        }
        $tmpfname = tempnam(sys_get_temp_dir(), 'thumbs');
        $cmd = self::getYouTubeDLCommand() . "  --no-check-certificate --no-playlist --force-ipv4 --write-description --skip-download  -o \"{$tmpfname}\" \"{$link}\"";
        exec($cmd . "  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            return false;
        } else {
            $content = url_get_contents($tmpfname . ".description");
            unlink($tmpfname . ".description");
            return $content;
        }
    }

    static function getYouTubeDLCommand() {
        global $global;
        if (!empty($global['youtube-dl'])) {
            return $global['youtube-dl'] . ' ';
        } else if (file_exists("/usr/local/bin/yt-dlp")) {
            return "/usr/local/bin/yt-dlp ";
        } else if (file_exists("/usr/local/bin/youtube-dl")) {
            return "/usr/local/bin/youtube-dl ";
        } else {
            return "youtube-dl ";
        }
    }

}

function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
    global $global;
    static $filesize = null;
    switch ($notification_code) {
        case STREAM_NOTIFY_RESOLVE:
        case STREAM_NOTIFY_AUTH_REQUIRED:
        case STREAM_NOTIFY_COMPLETED:
        case STREAM_NOTIFY_FAILURE:
        case STREAM_NOTIFY_AUTH_RESULT:
            //var_dump($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max);
            //$txt = "Found the mime-type: ". $message;
            /* Ignore */
            break;
        case STREAM_NOTIFY_REDIRECTED:
            //echo "Being redirected to: ", $message;
            $txt = "Being redirected to: " . $message;
            break;

        case STREAM_NOTIFY_CONNECT:
            //echo "Connected...";
            $txt = "Connected...";
            break;

        case STREAM_NOTIFY_FILE_SIZE_IS:
            //echo "Got the filesize: ", $bytes_max;
            $txt = "Got the filesize: " . $bytes_max;
            break;

        case STREAM_NOTIFY_MIME_TYPE_IS:
            //echo "Found the mime-type: ", $message;
            $txt = "Found the mime-type: " . $message;
            break;

        case STREAM_NOTIFY_PROGRESS:
            //echo "Made some progress, downloaded ", $bytes_transferred, " so far";
            $p = number_format(($bytes_transferred / $bytes_max) * 100, 2);
            $txt = "[download]  {$p}% of {$bytes_max}Bytes";
            break;
    }
    $myfile = file_put_contents($global['systemRootPath'] . 'videos/' . $global['queue_id'] . '_tmpFile_downloadProgress.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);
}
