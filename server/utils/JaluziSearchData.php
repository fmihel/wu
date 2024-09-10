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

        $q = "SELECT distinct
                sf.ID,
                sf.FIELD_NAME,
                jc.ID_J_SET
            from
                J_FIELDS_CONNECT jc
                join
                J_SET_FIELDS sf on jc.ID_J_SET_FIELD = sf.ID
            where
                sf.FIELD_NAME  = 'ART'
                and
                sf.FOR_GRID <> 1
            ";

        $ds = Base::ds($q, 'deco', 'utf8');

        $jaluzi_search_data = [];
        while ($row = Base::read($ds)) {

            $price = Jaluzi::getTab(-1, -1, $row['ID_J_SET']);
            $data = $price['data'];

            $ID_CTLG_NODE = self::get_ID_CTLG_NODE(FULL_TREE_CATALOG, $row['ID_J_SET']);

            if ($ID_CTLG_NODE !== false) {
                $arts = [];
                foreach ($data as $item) {
                    $arts[] = ['ID' => '', 'ART' => $item['ART']];
                };
                if (!empty($arts)) {
                    $jaluzi_search_data[$ID_CTLG_NODE] = $arts;
                }

            }
        }

        $out = '<?php const jaluzi_search_data=[' . Converter::toPhp($jaluzi_search_data) . '];';

        file_put_contents($config['to'], $out);
    }

    public static function get_ID_CTLG_NODE($full, $ID_J_SET)
    {
        $ID_CTLG_NODE = false;
        mapFullTreeCatalog($full, function ($it) use ($ID_J_SET, &$ID_CTLG_NODE) {
            if (isset($it['table'])) {
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

        $q = "select ID from J_SET where ID_J_FOLDER = $ID_J_FOLDER and ARCH<>1";
        $ds = Base::ds($q, 'deco', 'utf8');

        while ($row = Base::read($ds)) {
            $out[] = $row['ID'];
        };
        return $out;
    }

}
