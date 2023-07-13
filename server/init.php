<?php
namespace wu;

use fmihel\base\Base;
use fmihel\config\Config;
use fmihel\console;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/consts.php';

ini_set('memory_limit', '128M');

// -------------------------------------------
if ((!isset($_REQUEST['key'])) || ($_REQUEST['key'] !== Config::get('key'))) {

    console::log('key enable', __FILE__, __LINE__);
    echo RESULT_KEY;
    exit;
}

// -------------------------------------------
$bases = Config::get('bases');
Base::connect($bases['exweb']);
Base::connect($bases['deco']);
// -------------------------------------------
