<?php

/**
 * данный модуль выполняет скрипты после обновления.
 * Выполнение идет пошагово. 
 * В начале нужно запросить кол-во шагов
 * http://windeco.su/admin/modules/update/remote/after_update.php?key=wjhedwkje&count
 * Выполнение шага:
 * http://windeco.su/admin/modules/update/remote/after_update.php?key=wjhedwkje&step=NNN
 * 
 */

    
 
require_once 'init.php';

require_once 'createTree.php';
require_once 'createTreeFull.php';
require_once 'arch.php';

$out = array('res'=>0);
// кол-во шагов
$COUNT_STEPS = 4;

    

if (isset($_REQUEST['count'])){
    echo $COUNT_STEPS;
    exit;
};

//if (!$connect_to_test_base){
//    echo RESULT_OK;
//    exit;
//}    

    

$catalogJsPath = __DIR__.WS_CONF::GET('catalogJsPath');

if (isset($_REQUEST['step'])){
    $step = intval($_REQUEST['step']);
    
    //шаги 
    switch ($step){
        case 0:
            $out = CREATE_TREE_UTILS::SAVE_ALL($catalogJsPath);
            break;
        case 1:
            $file = $catalogJsPath.'/catalog_last_update_date.php';

            file_put_contents($file,
'<?php 
    /*данный файл генерируется автоматически, из скрипта after_update.php*/
    define("CATALOG_LAST_UPDATE_DATE","'.date('d/m/Y h:i').'");
    define("CATALOG_JS_CACHE","'.md5(file_get_contents($catalogJsPath.'/catalog.js')).'");
?>'
            );
            $out['res'] = 1;
            break;
        case 2:

            //archAll::$path = '../../archAll/tmp/';
            //archAll::$catalogPath = '../../createTree/catalog.js';
            //archAll::$mediaPath = '../../../../';  
            //archAll::$mediaHttp = 'http://windeco.su/';  
            //archAll::$zipPath = '../../../../download/catalog.zip';  
            arch::$path          = WS_CONF::GET('arch_path');
            arch::$catalogPath   = WS_CONF::GET('arch_catalogPath');
            arch::$mediaPath     = WS_CONF::GET('arch_mediaPath');  
            arch::$mediaHttp     = WS_CONF::GET('arch_mediaHttp');  
            arch::$zipPath       = WS_CONF::GET('arch_zipPath');  

            arch::create();
            
            
            $out['res'] = 1;
            break;
        case 3:
            // очистка кеша BUFFER
            $q = 'truncate table BUFFER';
            if (base::query($q,'deco'))
                $out['res'] = 1;
            break;
    }
    
};


if ($out['res'] === 1)
    echo RESULT_OK;
else
    echo RESULT_ERROR;

?>