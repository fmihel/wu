<?php
/**
 * мооудль очистки, по окончании процесса обновления
 *
 */
require_once 'init.php';

use fmihel\config\Config;
use fmihel\lib\Dir;

Dir::clear(Config::get('UNPACK_ZIP_PATH'));

echo RESULT_OK;
