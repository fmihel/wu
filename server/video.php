<?php
namespace wu;

/** обработчик для загружаемых видео */

use fmihel\base\Base;
use fmihel\config\Config;
use fmihel\console;
use fmihel\lib\Common;
use fmihel\lib\Dir;

require_once 'init.php';

$videoPath  = Config::get('videoPath');
$videoUrl   = Config::get('videoUrl');
$updatePath = Config::get('UPDATE_ZIP_PATH');

$after_scripts = [
    Common::join(Config::get('url:after_update.php'), ['key' => Config::get('key'), 'step' => 3]),
    Common::join(Config::get('url:after_update.php'), ['key' => Config::get('key'), 'step' => 7]),
];

try {
    /** проверка существования записи */
    if (Common::issets($_REQUEST, 'exists', 'ID_C_MEDIA_FILE')) {

        $ID_C_MEDIA_FILE = $_REQUEST['ID_C_MEDIA_FILE'];
        $q               = "select count(ID_C_MEDIA_FILE) cnt from C_MEDIA_FILE where ID_C_MEDIA_FILE = $ID_C_MEDIA_FILE";
        $cnt             = Base::value($q, 'deco', ['default' => 0]);
        echo $cnt > 0 ? RESULT_OK : RESULT_ERROR;
        exit;

    } else
    /** перемещение файла и запись его текущего пути в таблицу */
    if (Common::issets($_REQUEST, 'reg', 'path', 'file', 'ID_C_MEDIA_FILE')) {

        $ID_C_MEDIA_FILE = $_REQUEST['ID_C_MEDIA_FILE'];
        if ($ID_C_MEDIA_FILE > 0) {
            $q   = "select count(ID_C_MEDIA_FILE) cnt from C_MEDIA_FILE where ID_C_MEDIA_FILE = $ID_C_MEDIA_FILE";
            $cnt = Base::value($q, 'deco', ['default' => 0]);
            if ($cnt == 0) {
                throw new \Exception('not exists ID_C_MEDIA_FILE = ' . $ID_C_MEDIA_FILE);
            }

        }

        $file            = $_REQUEST['file'];
        $file_with_id    = strtolower(str_replace('.', "_$ID_C_MEDIA_FILE.", $_REQUEST['file']));
        $path            = str_replace('//', '/', Dir::join([$videoPath, $_REQUEST['path']]));
        $ID_C_MEDIA_FILE = $_REQUEST['ID_C_MEDIA_FILE'];

        $copyFrom = Dir::join([$updatePath, $file]);
        $copyTo   = strtolower(Dir::join([$path, $file_with_id]));

        if (! file_exists($path)) {
            mkdir($path, 0777, true);
        }

        if (! file_exists($path)) {
            throw new \Exception('not exists path ' . $path);
        }

        if (! rename($copyFrom, $copyTo)) {
            throw new \Exception('copy from "' . $copyFrom . '" to "' . $copyTo . '"');
        }

        if ($ID_C_MEDIA_FILE > 0) {
            $PATH_WWW = str_replace('//', '/', Dir::join([$_REQUEST['path'], $file_with_id]));
            $q        = "insert into C_MEDIA_FILE (ID_C_MEDIA_FILE,PATH_WWW,PROCESSING_KIND,CAPTION) values ($ID_C_MEDIA_FILE,'$PATH_WWW',4,'$file') on duplicate key update PATH_WWW='$PATH_WWW'";
            Base::query($q, 'deco', 'utf8');
        }

        //------------------------------------------------------
        // запуск скриптов, на пересоздание дерева catalog_new.js и catalog_v2
        foreach ($after_scripts as $script) {
            $ch = curl_init($script);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $str = curl_exec($ch);
            curl_close($ch);
        }
        //------------------------------------------------------

        echo RESULT_OK;
        exit;
    }

} catch (\Exception $e) {
    console::error($e);
}

echo RESULT_ERROR;
