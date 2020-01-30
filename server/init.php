<?php

if(!isset($Application)){
    $file = '../../wsi/ide/ws/utils/application.php';
    if (file_exists($file))
        require_once $file;
    else
        require_once '../'.$file;
    
    $Application->LOG_ENABLE        = true;
    $Application->LOG_TO_ERROR_LOG  = false; 
    //$Application->LOG_FILENAME = 'update.log';
    require_once UNIT('ws','ws.php');
};


define('RESULT_KEY','<result>-1</result>');
define('RESULT_OK','<result>1</result>');
define('RESULT_ERROR','<result>0</result>');
define('RESULT_PARAM','<result>2</result>');
define('RESULT_FILE_NOT_EXIST','<result>3</result>');
define('RESULT_BASE_REG','<result>4</result>');


// define('HTTP_MEDIA','http://windeco.su/media/');
define('HTTP_MEDIA',WS_CONF::GET('HTTP_MEDIA'));
// путь к папке с обновлениями
// define('UPDATE_ZIP_PATH','../../../../source/update/');
define('UPDATE_ZIP_PATH',WS_CONF::GET('UPDATE_ZIP_PATH'));
// путь куда распаковывается обновление
// define('UNPACK_ZIP_PATH','../tmp/');
define('UNPACK_ZIP_PATH',WS_CONF::GET('UNPACK_ZIP_PATH'));
// путь куда сохраняются файлы созданные из блоб
//define('BIN_STORY_PATH','../../../../media/');
define('BIN_STORY_PATH',WS_CONF::GET('BIN_STORY_PATH'));

//_LOGF($Application->REQUEST,'$Application->REQUEST',__FILE__,__LINE__);
    

if ((!isset($_REQUEST['key']))||($_REQUEST['key'] !== WS_CONF::GET('key') )){
    
    _LOG('key enable',__FILE__,__LINE__);
    echo RESULT_KEY;
    exit;
    
}

require_once UNIT('utils','dir.php');
require_once UNIT('utils','common.php');

require_once './connect.php';
require_once './update_consts.php';



?>