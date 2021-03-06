<?php
/**
 * модуль распаковки загруженного обновления
 * см. UWindecoUpdate.pas
 * 
 * @param {string} file - имя распаковываемого файла
 */ 
require_once 'init.php';


/*------------------------------------------*/

if(!isset($_REQUEST['file'])){
    
    echo RESULT_PARAM;
    exit;
}
/*------------------------------------------*/
$file = UPDATE_ZIP_PATH.$_REQUEST['file'];
/*------------------------------------------*/
if (!file_exists($file)){
    _LOG('file not exists :'.$file,__FILE__,__LINE__);
    
    echo RESULT_FILE_NOT_EXIST;
    exit;
}

/*------------------------------------------*/
// регистрируем в таблице обновлений

$q = "insert into UPDATE_LIST (CFILENAME,CDATE,CSTATE,CCOMMENT) value ('".$_REQUEST['file']."',CURRENT_TIMESTAMP,1,'')";

if (!\base::query($q,'deco')){
    _LOG(\base::error('deco')."[$q]",__FILE__,__LINE__);
    echo RESULT_BASE_REG;
    exit;
} 

/*------------------------------------------*/

DIR::clear(UNPACK_ZIP_PATH);

/*------------------------------------------*/

$zip = new ZipArchive;
        
try{        
    
    if ($zip->open($file) === TRUE) {
        $zip->extractTo(UNPACK_ZIP_PATH);
            $zip->close();
    }else{
        
    }
            
}catch (Exception $e){
    _LOG("Error: ".$e->getMessage(),__FILE__,__LINE__);
    echo RESULT_ERROR;
    exit;
}    
/*------------------------------------------*/

echo RESULT_OK;
?>
    
