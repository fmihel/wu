<?php

if(!isset($Application)){
    require_once '../../../../wsi/ide/ws/utils/application.php';
    
    $Application->LOG_ENABLE        = true;
    $Application->LOG_TO_ERROR_LOG  = false; 
    $Application->LOG_FILENAME = 'update.log';
    require_once UNIT('ws','ws.php');
};


define('RESULT_KEY','<result>-1</result>');
define('RESULT_OK','<result>1</result>');
define('RESULT_ERROR','<result>0</result>');
define('RESULT_PARAM','<result>2</result>');
define('RESULT_FILE_NOT_EXIST','<result>3</result>');
define('RESULT_BASE_REG','<result>4</result>');


define('HTTP_MEDIA','http://windeco.su/media/');
// путь к папке с обновлениями
define('UPDATE_ZIP_PATH','../../../../source/update/');
// путь куда распаковывается обновление
define('UNPACK_ZIP_PATH','../tmp/');
// путь куда сохраняются файлы созданные из блоб
//define('BIN_STORY_PATH','../bin_tmp/');
define('BIN_STORY_PATH','../../../../media/');

_LOG($Application->REQUEST,__FILE__,__LINE__);

if ((!isset($_REQUEST['key']))||($_REQUEST['key'] !== WS_CONF::GET('key') )){
    
    _LOG('key enable',__FILE__,__LINE__);
    echo RESULT_KEY;
    exit;
    
}

require_once UNIT('utils','dir.php');
require_once UNIT('utils','common.php');

require_once 'connect.php';
require_once 'update_consts.php';



?>