<?php
namespace wu\utils;

use fmihel\base\Base;
use fmihel\config\Config;
use windeco\catalog\Jaluzi;

require_once __DIR__ . '/Converter.php';
require_once __DIR__ . '/mapFullTreeCatalog.php';

foreach (Config::get('jaluzi-search-data', ['require' => []])['require'] as $module) {
    require_once $module;
}

class JaluziSearchData
{

    /** генерирует файл common_data/jaluzi_search_data.php
     *  с константой
     *  const jaluzi_search_data = [...]
     *  содержащей информацию для быстрого поиска по артикулу в
     *  разделаж жалюзи
     *
     * структура jaluzi_search_data
     * [
     *      ID_CTLG_NODE=>
     *          [
     *              [ ID_ROW , ART ],
     *              [ ID_ROW , ART ],
     *              ...
     *          ]
     *      ...
     * ]
     *
     */
    public static function create(array $params = [])
    {

        $config = Config::get('jaluzi-search-data', $params);

        require_once $config['full_tree_catalog.php'];

        $q = 'TRUNCATE `SEARCH_CACHE`';
        Base::query($q, 'deco');

        $q = "INSERT into SEARCH_CACHE (`VALUE`,ID_CTLG_NODE,CAPTION,ID_ORDER_KIND,REQUEST) value (?,?,?,?,?)";
        $template = Base::prepare($q, 'deco');
        /**
         * находим все которые видны PRINT=1, в которых есть столбец ART или есть подколонка с остатками SHOW_QUANTITY =1
         */
        $q = "SELECT
                distinct sf.ID,
                sf.FIELD_NAME,
                jc.ID_J_SET,
                js.KIND_SHOW
            from
                J_FIELDS_CONNECT jc
                join J_SET_FIELDS sf on jc.ID_J_SET_FIELD = sf.ID
                join j_set js on js.ID = jc.ID_J_SET
            where
                (   sf.FIELD_NAME = 'ART'
                    or
                    js.SHOW_QUANTITY = 1
                )
                and sf.FOR_GRID <> 1
                and js.PRINT = 1
                and js.KIND_SHOW IN (1,2)
            ";

        $ds = Base::ds($q, 'deco', 'utf8');

        $inserted = [];
        while ($row = Base::read($ds)) {
            $price = false;
            if ($row['KIND_SHOW'] == 1) {
                $price = Jaluzi::getTab(-1, -1, $row['ID_J_SET']);
            } else {
                $price = Jaluzi::getGrid(-1, -1, $row['ID_J_SET']);

            }
            if ($price) {
                $data = $price['data'];

                $ID_CTLG_NODE = self::get_ID_CTLG_NODE(FULL_TREE_CATALOG, $row['ID_J_SET']);

                if ($ID_CTLG_NODE !== false) {

                    foreach ($data as $item) {
                        $CAPTION = isset($item['J_NAME']) ? $item['J_NAME'] : 'арт';
                        $REQUEST = $ID_CTLG_NODE . '#' . $item['key'];
                        $ID_ORDER_KIND = 3;

                        /** когда есть столбец ART */
                        if (isset($item['ART'])) {
                            $VALUE = $item['ART'];

                            if (array_search($VALUE . $REQUEST, $inserted) === false) {
                                $template->bind_param('sisis', $VALUE, $ID_CTLG_NODE, $CAPTION, $ID_ORDER_KIND, $REQUEST);
                                $template->execute();
                                $inserted[] = $VALUE . $REQUEST;
                            };
                        }
                        /** когда артикулы сгруппированы в оттдельный список */
                        if (isset($item['-list-']['ART'])) {
                            foreach ($item['-list-']['ART'] as $color) {
                                if (isset($color['ART'])) {
                                    $VALUE = $color['ART'];

                                    if (array_search($VALUE . $REQUEST, $inserted) === false) {
                                        $template->bind_param('sisis', $VALUE, $ID_CTLG_NODE, $CAPTION, $ID_ORDER_KIND, $REQUEST);
                                        $template->execute();
                                        $inserted[] = $VALUE . $REQUEST;
                                    };
                                }
                            }
                        }
                        /** когда есть остатки см SHOW_QUANTITY=1 */
                        if (isset($item['RESTS']) && !empty($item['RESTS'])) {
                            foreach ($item['RESTS'] as $rest) {
                                if (isset($rest['ART'])) {
                                    $VALUE = $rest['ART'];
                                    $REQUEST_2 = $REQUEST . '-' . $rest['ID_J_TOVAR'];
                                    if (array_search($VALUE . $REQUEST_2, $inserted) === false) {
                                        $template->bind_param('sisis', $VALUE, $ID_CTLG_NODE, $CAPTION, $ID_ORDER_KIND, $REQUEST_2);
                                        $template->execute();
                                        $inserted[] = $VALUE . $REQUEST_2;
                                    };
                                }
                            }
                        }

                    };

                }
            }
        }

    }

    private static function get_ID_CTLG_NODE($full, $ID_J_SET)
    {
        $ID_CTLG_NODE = false;
        mapFullTreeCatalog($full, function ($it) use ($ID_J_SET, &$ID_CTLG_NODE) {
            if (isset($it['table']) && (!isset($it['child']) || empty($it['child']))) {
                if ($it['table'] === 'J_SET' && $it['ID'] == $ID_J_SET) {
                    $ID_CTLG_NODE = $it['id'];
                    return false;
                }
                if ($it['table'] === 'J_FOLDER') {
                    $sets = self::J_FOLDER_to_J_SET($it['ID']);
                    if (array_search($ID_J_SET, $sets) !== false) {
                        $ID_CTLG_NODE = $it['id'];
                        return false;
                    }
                }
            };
        });

        return $ID_CTLG_NODE;
    }

    private static function J_FOLDER_to_J_SET($ID_J_FOLDER): array
    {
        $out = [];

        $q = "select ID from J_SET where ID_J_FOLDER = $ID_J_FOLDER and ARCH<>1 and PRINT = 1";
        $ds = Base::ds($q, 'deco', 'utf8');

        while ($row = Base::read($ds)) {
            $out[] = $row['ID'];
        };
        return $out;
    }

}
