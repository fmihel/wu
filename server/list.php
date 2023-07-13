<?php
namespace wu;

/**
 * моудль возвращает список уже присутствующих пакетов обновления на сервере
 * @return <list>filename1.zip,filename2.zip,...</list>
 */

use fmihel\base\Base;
use fmihel\console;

require_once 'init.php';

$q = 'select  distinct CFILENAME from UPDATE_LIST order by ID desc';
$ds = Base::ds($q, 'deco');

if ($ds) {
    echo '<list>';
    $bool = false;
    while ($row = base::read($ds)) {
        echo ($bool ? ',' : '') . $row['CFILENAME'];
        $bool = true;

    }
    echo '</list>';
} else {
    console::Error("[$q]");
    echo RESULT_ERROR;
}
