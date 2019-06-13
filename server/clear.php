<?php
/** 
 * мооудль очистки, по окончании процесса обновления
 * 
 */ 
require_once 'init.php';


DIR::clear(WS_CONF::GET('UNPACK_ZIP_PATH'));


echo RESULT_OK;
?>
    
