<?php

/**
 * данный модуль выполняет скрипты после обновления.
 * Выполнение идет пошагово.
 * В начале нужно запросить кол-во шагов
 * https://windeco.su/remote_access_api/wu/server/after_update.php?key=kdiun78js&count
 * http://windeco/wu/server/after_update.php?key=test&count
 * Выполнение шага:
 * https://windeco.su/remote_access_api/wu/server/after_update.php?key=kdiun78js&step=NNN
 * windeco/wu/server/after_update.php?key=test&step=NNN
 * windeco/wu/server/after_update.php?key=test&reculcAllTest
 */

require_once 'init.php';

require_once 'createTree.php';
require_once 'createTreeFull.php';
require_once 'tree_generate.php';
require_once 'arch.php';
require_once 'order_test.php';
require_once 'OrdersBlankTree.php';
require_once 'video_utils.php';

use fmihel\config\Config;

$out = array('res' => 0);

//--------------------------------------------------------------------
// кол-во шагов
$COUNT_STEPS = 7;
$ORDER_TEST_START = $COUNT_STEPS;
$ORDER_TEST_COUNT = ORDER_TEST::count();
//--------------------------------------------------------------------
if (isset($_REQUEST['count'])) {
    echo $COUNT_STEPS + $ORDER_TEST_COUNT;
    exit;
}
;
//--------------------------------------------------------------------
// сброс тестов
if (isset($_REQUEST['reculcAllTest'])) {
    ORDER_TEST::reculcAllTest();
    $out['res'] = 1;
}
//--------------------------------------------------------------------
// сброс i-go теста
if (isset($_REQUEST['reculcTest'])) {
    ORDER_TEST::reculc(['step' => $_REQUEST['reculcTest']]);
    $out['res'] = 1;
}
//--------------------------------------------------------------------
// запуск всех тестов
if (isset($_REQUEST['runAllTests'])) {
    ORDER_TEST::runAll();
    exit;
}
//--------------------------------------------------------------------
// включает выключает режим запуска тестов вконце обновления
if (isset($_REQUEST['runOrdersTests'])) {

    $conf = file_get_contents('ws_conf.php');
    $true = "'runOrdersTests'=>true,";
    $false = "'runOrdersTests'=>false,";
    $truePos = strpos($conf, $true);
    $falsePos = strpos($conf, $false);

    if ($_REQUEST['runOrdersTests'] == '0') {
        if ($truePos === false && $falsePos === false) {
            $conf = str_replace('];', $false . "\n];", $conf);
        } elseif ($falsePos === false) {
            $conf = str_replace($true, $false, $conf);
        }

    } else {
        if ($truePos === false && $falsePos === false) {
            $conf = str_replace('];', $true . "\n];", $conf);
        } elseif ($truePos === false) {
            $conf = str_replace($false, $true, $conf);
        }
    }
    ;
    file_put_contents('ws_conf.php', $conf);
    $out['res'] = 1;

}

//--------------------------------------------------------------------
$catalogJsPath = __DIR__ . Config::get('catalogJsPath');
//--------------------------------------------------------------------

if (isset($_REQUEST['step'])) {

    $step = intval($_REQUEST['step']);

    // шаги
    //--------------------------------------------------------------------
    if ($step == 0) {

        $out = CREATE_TREE_UTILS::SAVE_ALL($catalogJsPath);
        //--------------------------------------------------------------------
    } elseif ($step == 1) {

        $file = $catalogJsPath . '/catalog_last_update_date.php';
        file_put_contents($file,
            '<?php
    /*данный файл генерируется автоматически, из скрипта after_update.php*/
    define("CATALOG_LAST_UPDATE_DATE","' . date('d/m/Y H:i') . '");
    define("CATALOG_JS_CACHE","' . md5(file_get_contents($catalogJsPath . '/catalog.js')) . '");
    ?>'
        );
        $out['res'] = 1;

        //--------------------------------------------------------------------
    } elseif ($step == 2) {
        // очистка кеша BUFFER
        $q = 'truncate table BUFFER';
        if (base::query($q, 'deco')) {
            $out['res'] = 1;
        }

        //--------------------------------------------------------------------
    } elseif ($step == 3) {
        try {
            TREE_GENERATE::create($catalogJsPath . '/catalog_new.js', $catalogJsPath . '/full_tree_catalog.php');
        } catch (Exception $e) {
        }
        $out['res'] = 1;
        //--------------------------------------------------------------------
    } elseif ($step == 4) {
        //archAll::$path = '../../archAll/tmp/';
        //archAll::$catalogPath = '../../createTree/catalog.js';
        //archAll::$mediaPath = '../../../../';
        //archAll::$mediaHttp = 'http://windeco.su/';
        //archAll::$zipPath = '../../../../download/catalog.zip';
        arch::$path = Config::get('arch_path');
        arch::$catalogPath = Config::get('arch_catalogPath');
        arch::$mediaPath = Config::get('arch_mediaPath');
        arch::$mediaHttp = Config::get('arch_mediaHttp');
        arch::$zipPath = Config::get('arch_zipPath');
        arch::create();
        //echo arch::debug_info();

        $out['res'] = 1;
        //--------------------------------------------------------------------
    } elseif ($step == 5) {
        // перестройка дерева шаблонов - заказов
        OrdersBlankTree::update();
        $out['res'] = 1;
    } elseif ($step == 6) {
        // удаление неиспользуемых видео
        video_utils::clear();
        $out['res'] = 1;
    } elseif ($step >= ($ORDER_TEST_START) && $step < $ORDER_TEST_START + $ORDER_TEST_COUNT) {

        if (Config::get('runOrdersTests', true))
        // запуск теста
        {
            ORDER_TEST::step($step - $COUNT_STEPS);
        } else {
            // пересчет тестового заказа
            ORDER_TEST::reculc(['step' => $step - $COUNT_STEPS]);
        }
        $out['res'] = 1;
    }
    ;
    //--------------------------------------------------------------------
}
;

echo ($out['res'] === 1 ? RESULT_OK : RESULT_ERROR);
