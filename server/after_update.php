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
 */

    
 
require_once 'init.php';

    
require_once 'createTree.php';
require_once 'createTreeFull.php';
require_once 'tree_generate.php';
require_once 'arch.php';
require_once 'order_test.php';

$out = array('res'=>0);

//--------------------------------------------------------------------
// кол-во шагов
$COUNT_STEPS = 5;
//--------------------------------------------------------------------
if (isset($_REQUEST['count'])){
    echo $COUNT_STEPS + ORDER_TEST::count();
    exit;
};
//--------------------------------------------------------------------
$catalogJsPath = __DIR__.WS_CONF::GET('catalogJsPath');
//--------------------------------------------------------------------
        
if (isset($_REQUEST['step'])){
    
    $step = intval($_REQUEST['step']);
    
    // шаги 
    //--------------------------------------------------------------------    
    if ($step == 0){
        
        $out = CREATE_TREE_UTILS::SAVE_ALL($catalogJsPath);
    //--------------------------------------------------------------------    
    }elseif($step == 1){
        
        $file = $catalogJsPath.'/catalog_last_update_date.php';
        file_put_contents($file,
    '<?php 
    /*данный файл генерируется автоматически, из скрипта after_update.php*/
    define("CATALOG_LAST_UPDATE_DATE","'.date('d/m/Y H:i').'");
    define("CATALOG_JS_CACHE","'.md5(file_get_contents($catalogJsPath.'/catalog.js')).'");
    ?>'
    );
        $out['res'] = 1;
    
    //--------------------------------------------------------------------
    }elseif($step == 2){
        // очистка кеша BUFFER
        $q = 'truncate table BUFFER';
        if (base::query($q,'deco'))
            $out['res'] = 1;
    //--------------------------------------------------------------------
    }elseif($step == 3){
        try{
            TREE_GENERATE::create($catalogJsPath.'/catalog_new.js');
        }catch(Exception $e){
        }
        $out['res'] = 1;
    //--------------------------------------------------------------------
    }elseif($step == 4){
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
        //echo arch::debug_info();
        
        $out['res'] = 1;
    //--------------------------------------------------------------------
    }elseif($step>($COUNT_STEPS-1)){
        ORDER_TEST::step($step-$COUNT_STEPS);
        $out['res'] = 1;
    };
    //--------------------------------------------------------------------
};

echo ( $out['res'] === 1 ? RESULT_OK : RESULT_ERROR );
    
?>