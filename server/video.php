<?php
/** обработчик для загружаемых видео */

use fmihel\lib\Common;
use fmihel\lib\Dir;

require_once 'init.php';

$videoPath = WS_CONF::GET('videoPath');
$videoUrl =  WS_CONF::GET('videoUrl');
$updatePath = WS_CONF::GET('UPDATE_ZIP_PATH');

try{
    /** проверка существования записи */
    if (Common::issets($_REQUEST,'exists','ID_C_MEDIA_FILE')){
        
        $q = "select count(ID_C_MEDIA_FILE) cnt from C_MEDIA_FILE where ID_C_MEDIA_FILE = $ID_C_MEDIA_FILE";
        $cnt = base::value($q,'cnt',0,'base');
        echo $cnt>0 ? RESULT_OK : RESULT_ERROR;
        exit;
        
    }else
    /** перемещение файла и запись его текущего пути в таблицу */
    if (Common::issets($_REQUEST,'reg','path','file','ID_C_MEDIA_FILE')){
        
        if ($ID_C_MEDIA_FILE>0){
            $q = "select count(ID_C_MEDIA_FILE) cnt from C_MEDIA_FILE where ID_C_MEDIA_FILE = $ID_C_MEDIA_FILE";
            $cnt = base::value($q,'cnt',0,'base');
            if (base::value($q,'cnt',0,'base')==0)
                throw new \Exception('not exists ID_C_MEDIA_FILE = '.$ID_C_MEDIA_FILE);
        };
        
        $file = $_REQUEST['file'];
        $path = str_replace('//','/',Dir::join([$videoPath,$_REQUEST['path']]));
        $ID_C_MEDIA_FILE = $_REQUEST['ID_C_MEDIA_FILE'];

        $copyFrom = Dir::join([$updatePath,$file]);
        $copyTo = Dir::join([$path,$file]);
        
        if (!file_exists($path))
            mkdir($path,0777,true);

        if (!file_exists($path))
            throw new \Exception('not exists path '.$path);

        if (!rename($copyFrom,$copyTo))
            throw new \Exception('copy from "'.$copyFrom.'" to "'.$copyTo.'"');
        
        if ($ID_C_MEDIA_FILE>0){
            $PATH_WWW = str_replace('//','/',Dir::join([$_REQUEST['path'],$_REQUEST['file']]));
            $q = "insert into C_MEDIA_FILE (ID_C_MEDIA_FILE,PATH_WWW,PROCESSING_KIND,CAPTION) values ($ID_C_MEDIA_FILE,'$PATH_WWW',4,'$file') on duplicate key update PATH_WWW='$PATH_WWW'";
            base::queryE($q,'deco','utf8');
        };

        echo RESULT_OK;
        exit;
    };

} catch (\Exception $e) {
    error_log('ERROR '.$e->getMessage());
};

echo RESULT_ERROR;