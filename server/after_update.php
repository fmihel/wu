<?php
namespace wu;

/**
 * данный модуль выполняет скрипты после обновления.
 * Выполнение идет пошагово.
 * В начале нужно запросить кол-во шагов
 * https://windeco.su/remote_access_api/wu/server/after_update.php?key=kdiun78js&count
 * http://windeco/wu/server/after_update.php?key=test&count
 * Выполнение шага:
 * https://windeco.su/remote_access_api/wu/server/after_update.php?key=kdiun78js&step=NNN
 * http://windeco/wu/server/after_update.php?key=test&step=NNN
 * windeco/wu/server/after_update.php?key=test&reculcAllTest
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/utils/CreateTree.php';
require_once __DIR__ . '/utils/CreateTreeFull.php';
require_once __DIR__ . '/utils/tree_generate.php';
require_once __DIR__ . '/utils/arch.php';
require_once __DIR__ . '/utils/video_utils.php';
require_once __DIR__ . '/utils/OrdersBlankTree.php';
require_once __DIR__ . '/utils/tree_generate_v2.php';

use fmihel\base\Base;
use fmihel\config\Config;
use fmihel\console;
use fmihel\lib\Dir;
use wu\utils\arch;
use wu\utils\CreateTree;
use wu\utils\OrdersBlankTree;
use wu\utils\TREE_GENERATE;
use wu\utils\TREE_GENERATE_V2;
use wu\utils\video_utils;

$out = ['res' => 0];

//--------------------------------------------------------------------
// кол-во шагов
$COUNT_STEPS = 8;
//--------------------------------------------------------------------
if (isset($_REQUEST['count'])) {
    echo $COUNT_STEPS;
    exit;
}
//--------------------------------------------------------------------
$catalogJsPath = __DIR__ . Config::get('catalogJsPath');
//--------------------------------------------------------------------

if (isset($_REQUEST['step'])) {

    $step = intval($_REQUEST['step']);
    // шаги
    if ($step == 0) { //--------------------------------------------------------------------------
        $out = CreateTree::SAVE_ALL($catalogJsPath);

    } elseif ($step == 1) { //--------------------------------------------------------------------
        $file = Dir::join([$catalogJsPath, 'catalog_last_update_date.php']);
        file_put_contents($file,
            '<?php
    /*данный файл генерируется автоматически, из скрипта after_update.php*/
    define("CATALOG_LAST_UPDATE_DATE","' . date('d/m/Y H:i') . '");
    define("CATALOG_JS_CACHE","' . md5(file_get_contents(Dir::join([$catalogJsPath, 'catalog.js']))) . '");'
        );

        $out['res'] = 1;

    } elseif ($step == 2) { //--------------------------------------------------------------------
        // очистка кеша BUFFER
        if (Base::query('truncate table BUFFER', 'deco')) {
            $out['res'] = 1;
        }
    } elseif ($step == 3) { //--------------------------------------------------------------------
        try {
            TREE_GENERATE::create(Dir::join([$catalogJsPath, 'catalog_new.js']), Dir::join([$catalogJsPath, 'full_tree_catalog.php']));
        } catch (\Exception $e) {
            console::error($e);
        }
        $out['res'] = 1;

    } elseif ($step == 4) { //--------------------------------------------------------------------

        arch::$path = Config::get('arch_path');
        arch::$catalogPath = Config::get('arch_catalogPath');
        arch::$mediaPath = Config::get('arch_mediaPath');
        arch::$mediaHttp = Config::get('arch_mediaHttp');
        arch::$zipPath = Config::get('arch_zipPath');
        arch::create();

        $out['res'] = 1;

    } elseif ($step == 5) { //--------------------------------------------------------------------
        // перестройка дерева шаблонов - заказов
        OrdersBlankTree::update();
        $out['res'] = 1;

    } elseif ($step == 6) { //--------------------------------------------------------------------
        // удаление неиспользуемых видео
        video_utils::clear();
        $out['res'] = 1;

    } elseif ($step == 7) { //--------------------------------------------------------------------
        try {
            TREE_GENERATE_V2::create(Dir::join([$catalogJsPath, 'catalog_v2.js']));
        } catch (\Exception $e) {
            console::error($e);
        }
        $out['res'] = 1;
    }

}

echo ($out['res'] === 1 ? RESULT_OK : RESULT_ERROR);
