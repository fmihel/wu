<?php
namespace wu;

use fmihel\console;
use wu\utils\UPDATE_UTILS;

/**
 * модуль пошагового применения обнолвения
 * используется в UWindecoUpdat.pas::TWindecoUpdate
 * @param {string} table - имя обновляемой таблицы
 * @param {integer} pos - текущая позиция в обновлении
 * @param {integer} delta - кол-во обновлений за раз
 * @return void
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/utils/update_utils.php';

if ((!isset($_REQUEST['table'])) || (!isset($_REQUEST['pos'])) || (!isset($_REQUEST['delta']))) {

    echo RESULT_PARAM;
    exit;
}

$table = $_REQUEST['table'];
$pos = $_REQUEST['pos'];
$delta = $_REQUEST['delta'];

/** перед первым шагом, если таблица в списке  FULL_TREE_CATALOG очищаем ее полностью*/

if ($pos == 0 && array_search($table, FULL_CLEAR_TABLES) !== false) {
    UPDATE_UTILS::clearTable($table);
}

if ($table === 'DELETED_LINES') {
    $response = UPDATE_UTILS::deleted_lines($pos, $delta);
}

/** в данной части обрабатывается сохранение блоб в фaйл*/
else if (($table === 'C_MEDIA_FILE') || (preg_match_all('/C_MEDIA_FILE_\S+/m', $table) > 0)) {
    $response = UPDATE_UTILS::media($pos, $delta, $table);
} else {
    $response = UPDATE_UTILS::update_step($table, $pos, $delta);
}

if ($response['res'] == 1) {
    echo RESULT_OK;
} else {

    console::log('result: ERROR', __FILE__, __LINE__);
    echo RESULT_ERROR;
    //echo '<errors>'.$response.'</errors>';

}
