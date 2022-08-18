<?php
/** обработчик для загружаемых видео */

use fmihel\lib\Common;
use fmihel\lib\Dir;

require_once 'init.php';

$videoPath = WS_CONF::GET('videoPath');
$videoUrl =  WS_CONF::GET('videoUrl');
$updatePath = WS_CONF::GET('UPDATE_ZIP_PATH');


if (Common::issets($_REQUEST,'reg','path','file','ID_C_MEDIA_FILE')){
    try {
        $file = $_REQUEST['file'];
        $path = str_replace('//','/',Dir::join([$videoPath,$_REQUEST['path']]));
        $copyFrom = Dir::join([$updatePath,$file]);
        $copyTo = Dir::join([$path,$file]);

        error_log('from '.$copyFrom);
        error_log('to '.$copyTo);

        if (!file_exists($path))
            mkdir($path,0777,true);

        if (!file_exists($path))
            throw new \Exception('not exists path '.$path);

        if (!rename($copyFrom,$copyTo))
            throw new \Exception('copy from "'.$copyFrom.'" to "'.$copyTo.'"');

        echo RESULT_OK;
        exit;

    } catch (\Exception $e) {
        error_log('ERROR '.$e->getMessage());
    };

};

echo RESULT_ERROR;