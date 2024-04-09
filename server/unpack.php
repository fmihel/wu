<?php
namespace wu;

/**
 * модуль распаковки загруженного обновления
 * см. UWindecoUpdate.pas
 *
 * @param {string} file - имя распаковываемого файла
 */

require_once 'init.php';

use fmihel\base\Base;
use fmihel\console;
use fmihel\lib\Dir;

function stop($result)
{
    echo $result;
    exit;
}

/*------------------------------------------*/
if (!isset($_REQUEST['file'])) {
    stop(RESULT_PARAM);
}
/*------------------------------------------*/
$file = UPDATE_ZIP_PATH . $_REQUEST['file'];
/*------------------------------------------*/
if (!file_exists($file)) {
    console::error('not exists :' . $file);
    stop(RESULT_FILE_NOT_EXIST);
}
/*------------------------------------------*/
// регистрируем в таблице обновлений
try {
    $q = "insert into UPDATE_LIST (CFILENAME,CDATE,CSTATE,CCOMMENT) value ('" . $_REQUEST['file'] . "',CURRENT_TIMESTAMP,1,'')";
    Base::query($q, 'deco');
} catch (\Exception $e) {
    console::log($e);
    stop(RESULT_BASE_REG);
};

/*------------------------------------------*/
Dir::clear(UNPACK_ZIP_PATH);
/*------------------------------------------*/

$zip = new \ZipArchive;
try {

    if ($zip->open($file) === true) {
        $zip->extractTo(UNPACK_ZIP_PATH);
        $zip->close();
    } else {

    }

} catch (\Exception $e) {
    console::log($e);
    stop(RESULT_ERROR);
}
/*------------------------------------------*/

stop(RESULT_OK);
