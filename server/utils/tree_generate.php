<?php
namespace wu\utils;

use fmihel\base\Base;
use fmihel\lib\Common;
use fmihel\lib\Dir;

require_once __DIR__ . '/Compatible.php';
require_once __DIR__ . '/consts.php';

class TREE_GENERATE
{
    private static $coding = 'utf8';

    public static function create($saveToFile = false, $saveToPhp = false)
    {
        $out = [];

        $q = 'select * from CTLG_NODE where ID_PARENT = 0 and  ARCH<>1 order by NOM_PP';
        $ds = Base::ds($q, 'deco', self::$coding);

        while ($row = Base::read($ds)) {
            $out[] = self::_create($row, '');
        }

        $out[] = [
            'caption' => 'Личный кабинет',
            'icon' => 'file',

            'id' => 'main_page',
            'viewAs' => 'mainPage',
            'subset' => [0],
            'hash' => 'main',

        ];

        if ($saveToPhp) {
            file_put_contents($saveToPhp, '<?php const FULL_TREE_CATALOG = [' . self::jsToPhp($out) . '];');
        }

        if ($saveToFile) {

            $json = Compatible::Arr_to_json($out);
            file_put_contents($saveToFile, 'var catalog2=' . $json . ';');

        } else {
            return $out;
        }

    }

    private static function _create($node, $parent)
    {

        $ID = $node['SRCE_ID'];

        $kind = SRCE_KIND[$node['SRCE_KIND']];
        $FIELD = $kind['field'];
        $TABLE = $kind['table'];

        $out = [];
        $out['id'] = $node['ID_CTLG_NODE'];
        $out[$FIELD] = $ID;
        $out['table'] = $TABLE;

        $out['caption'] = self::stringCorrect($node['CAPTION']);
        $out['icon'] = ICONS[$node['ICON_IND']];
        $out['SRCE_KIND'] = $node['SRCE_KIND'];
        $out['ID_ORDER_BLANK_TREE'] = $node['ID_ORDER_BLANK_TREE'];
        //$out['orders']=self::getOrderInfo($node['ID_ORDER_BLANK_TREE']);

        $out['hash'] = self::translit($parent, $node);

        $out['media'] = self::_get_media(($ID != '0' ? $ID : $node['ID_CTLG_NODE']), Common::get($kind, 'media_kind', ''));
        //-------------------------------------------------------------------------------------------------------
        $q = 'select distinct ID_CTLG_SUBSET from CTLG_SUBSET_NODE where ID_CTLG_NODE = ' . $node['ID_CTLG_NODE'];
        $ds = Base::ds($q, 'deco');
        $out['subset'] = [];
        while ($row = base::read($ds)) {
            $out['subset'][] = $row['ID_CTLG_SUBSET'];
        }
        //-------------------------------------------------------------------------------------------------------

        $child = [];
        $q = 'select * from CTLG_NODE where ID_PARENT = ' . $node['ID_CTLG_NODE'] . ' and  ARCH<>1 order by NOM_PP';
        $ds = Base::ds($q, 'deco', self::$coding);

        while ($row = Base::read($ds)) {
            $child[] = self::_create($row, $out['hash']);
        }

        if (isset($out['media']['video'])) {
            if (count($child) === 0) {
                $out['viewAs'] = 'video';
            }

        } elseif ($node['SRCE_KIND'] == 0) {
            if (count($child) === 0) {
                $out['viewAs'] = 'gallery';
            }

        } else if (($node['SRCE_KIND'] == 1) || ($node['SRCE_KIND'] == 2) || ($node['SRCE_KIND'] == 6) || ($node['SRCE_KIND'] == 7)) {
            $out['IS_CHAPTER'] = ($kind['is_chapter'] ? 1 : 0);
            $out = array_merge($out, self::karniz($node));
        } else if (($node['SRCE_KIND'] == 8) || ($node['SRCE_KIND'] == 9)) {
            $out = array_merge($out, self::tkani($node));
        } else if (($node['SRCE_KIND'] == 10) || ($node['SRCE_KIND'] == 11)) {
            $out = array_merge($out, self::jaluzi($node));
        }

        if (count($child) > 0) {
            $out['child'] = $child;
        }

        //-------------------------------------------------------------------------------------------------------

        return $out;
    }
    private static function stringCorrect($str)
    {

        $str = htmlspecialchars($str);
        $from = array(
            '"', //0
            "'", //0
        );
        $to = array(
            '', //0
            '', //0
        );
        $res = str_replace($from, $to, $str);
        return $res;

    }
    private static function _get_media($OWNER_ID, $OWNER_KIND)
    {

        if (!is_numeric($OWNER_ID) || (!is_numeric($OWNER_KIND))) {
            return [];
        }

        $view = [];
        $download = [];
        $print = [];
        $video = [];

        $q = "
            select
                ID_C_MEDIA_FILE,CAPTION,PATH_WWW,PROCESSING_KIND
            from
                C_MEDIA_FILE
            where
                OWNER_ID = $OWNER_ID and OWNER_KIND = $OWNER_KIND and ARCH<>1
            order by
                PROCESSING_KIND,NOM_PP ";

        if ($OWNER_ID == '362') {

        }
        $PROCESSING_KIND = -1;
        $ds = Base::ds($q, 'deco', self::$coding);
        if ($ds) {
            $row = [];
            while ($row = Base::read($ds)) {

                $row['CAPTION'] = self::stringCorrect($row['CAPTION']);
                if ($PROCESSING_KIND !== $row['PROCESSING_KIND']) {
                    $PROCESSING_KIND = $row['PROCESSING_KIND'];
                }

                //$row['PATH_WWW'] = HTTP_MEDIA.$row['PATH_WWW'];

                if ($PROCESSING_KIND == 1) {
                    $view[] = Dir::join([HTTP_MEDIA, $row['PATH_WWW']]);
                } elseif ($PROCESSING_KIND == 2) {
                    $row['PATH_WWW'] = Dir::join([HTTP_MEDIA, $row['PATH_WWW']]);
                    $download[] = $row;
                } elseif ($PROCESSING_KIND == 3) {
                    $row['PATH_WWW'] = Dir::join([HTTP_MEDIA, $row['PATH_WWW']]);
                    $print[] = $row;
                } elseif ($PROCESSING_KIND == 4) {
                    $row['PATH_WWW'] = Dir::join([HTTP_VIDEO, $row['PATH_WWW']]);
                    $video[] = $row;
                }
            }
        } else {
            //console::log("Error [$q]",__FILE__,__LINE__);
        }
        $out = [];
        if (count($view) > 0) {
            $out['gallery'] = $view;
        }

        if (count($download) > 0) {
            $out['download'] = $download;
        }

        if (count($print) > 0) {
            $out['print'] = $print;
        }

        if (count($video) > 0) {
            $out['video'] = $video;
        }

        return $out;
    }

    private static function karniz($row)
    {
        $out = [];
        $kind = SRCE_KIND[$row['SRCE_KIND']];

        $ID = $row['SRCE_ID'];
        $FIELD = $kind['field'];
        $TABLE = $kind['table'];
        $is_chapter = $kind['is_chapter'];
        $priceType = self::_typePrice($ID, $is_chapter);

        $q = "select SHOW_AS from $TABLE where $FIELD=$ID";
        $show_as = Base::value($q, 'deco', ['default' => 0]);

        $out['viewAs'] = self::_viewAs($show_as, $priceType);

        if ($out['viewAs'] === 'karnizB') {
            $out['ID_K_TOVAR'] = self::idTovarPriceB($ID, $is_chapter);
        }

        return $out;
    }

    private static function jaluzi($row)
    {
        $out = [];
        $out['viewAs'] = 'jaluzi';

        return $out;
    }

    private static function tkani($row)
    {
        $out = [];
        $out['viewAs'] = 'tkani';
        return $out;
    }

    private static function _viewAs($SHOW_AS, $typePrice = '')
    {

        if (($SHOW_AS == 1) && ($typePrice !== '')) {
            return 'karniz' . $typePrice;
        }

        if ($SHOW_AS == 2) {
            return 'gallery';
        }

        if ($SHOW_AS == 3) {
            return 'video';
        }

        return '';

    }

    /** возвращает букву определяющую тип прайса карнизов A или B */
    private static function _typePrice($id, $is_chapter)
    {
        $q = '
            select distinct
                t.PRICELIST_KIND
            from
                K_MODEL_TOVAR mt
            join
                K_MODEL_TOVAR_DETAIL mtd
                on mt.ID_K_MODEL_TOVAR = mtd.ID_K_MODEL_TOVAR
            join
                K_TOVAR_DETAIL td
                on td.ID_K_TOVAR_DETAIL = mtd.ID_K_TOVAR_DETAIL
            join
                K_TOVAR t
                on td.ID_K_TOVAR = t.ID_K_TOVAR
            where ';

        if ($is_chapter) {
            $q .= 'mt.ID_K_CHAPTER=' . $id;
        } else {
            $q .= 'mt.ID_K_MODEL=' . $id;
        }

        return (Base::value($q, 'deco', ['default' => 1]) == 2 ? 'B' : 'A');

    }
    private static function idTovarPriceB($id, $is_chapter)
    {
        // возвращает список товаров по которым будут создаваться матричные прайс-листы

        $q = 'select distinct t.ID_K_TOVAR ';

        $q .= 'from
                K_MODEL_TOVAR mt
                join
                K_MODEL_TOVAR_DETAIL mtd
                    on mt.id_k_model_tovar = mtd.id_k_model_tovar
                join
                K_TOVAR_DETAIL td
                    on td.id_k_tovar_detail = mtd.id_k_tovar_detail
                join
                K_TOVAR t
                    on t.id_k_tovar = td.id_k_tovar
            where
                td.SIZE_BORDER>0
                and
                td.ARCH<>1
                and
                t.ARCH<>1
                and
                mt.ARCH<>1
                and ';
        if ($is_chapter) {
            $q .= 'mt.id_k_chapter=' . $id;
        } else {
            $q .= 'mt.id_k_model=' . $id;
        }

        return Base::value($q, 'deco', ['default' => -1]);

    }

    private static function translit($pref, $node)
    {

        return $pref . ($pref !== '' ? "/" : "") . self::_translit($node['CAPTION']);
    }

    private static function _translit($str): string
    {

        $out = trim(mb_strtolower($str));
        while (strpos($out, '  ') !== false) {
            $out = str_replace(['  '], [' '], $out);
        }

        $out = str_replace([' '], ['_'], $out);
        $out = preg_replace('/[^a-zA-Zа-яА-Я0-9\_]/ui', '', $out);
        $out = str_replace(RUS_BUK, ENG_BUK, $out);

        return $out;
    }
    /** можно использовать для записи информации о вариантых заказов в узле...пока без нее обхожусь */
    private static function getOrderInfo($ID_ORDER_BLANK_TREE)
    {
        $out = [];
        if ($ID_ORDER_BLANK_TREE > 0) {
            $q =
                "SELECT *
                    from
                        ORDERS_BLANK_TREE obt
                        join
                        ORDERS_KINDS ok on obt.ID_ORDER_KIND=ok.ID_ORDER_KIND
                    where
                        obt.ID_ORDER_BLANK_TREE=$ID_ORDER_BLANK_TREE
                ";

            $row = Base::row($q, 'deco', 'utf8');
            //$caption = $row['NAME_FULL'].'/'.$row['CAPTION'];
            $kind = $row['ID_ORDER_KIND'];

            $id = 1;
            if ($row['ID_B_BLANK'] > 0) {
                $out[] = ['id' => $id, 'caption' => 'Вручную (бланк)', 'ID_B_BLANK' => $row['ID_B_BLANK'], 'type' => 'blank', 'kind' => $kind];
            }

            $id++;
            if ($row['ID_J_TMPL'] > 0) {
                $out[] = ['id' => $id, 'caption' => 'Автоматически', 'ID_J_TMPL' => $row['ID_J_TMPL'], 'type' => 'auto', 'kind' => $kind];
            }

            $id++;
            if ($row['ID_K_TEMPL'] > 0) {
                $out[] = ['id' => $id, 'caption' => 'Автоматически', 'ID_K_TEMPL' => $row['ID_K_TEMPL'], 'type' => 'auto', 'kind' => $kind];
            }

        }
        return $out;
    }

    private static function _typing($value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        if ($value === 'true' || $value === 'false') {
            return $value;
        }

        return '"' . $value . '"';
    }
    private static function jsToPhp(array $js, $cr = "\n", $level = 0)
    {
        $php = '';
        $gap = '  ';
        $off = str_repeat($gap, $level);

        foreach ($js as $key => $value) {
            $type = gettype($value);
            if ($type === 'array') {
                $childs = self::jsToPhp($value, $cr, $level + 1);
                if ($childs !== '') {
                    $php .= $off . '"' . $key . '"=>[' . $cr . $childs . $off . '],' . $cr;
                }
            } else {
                $php .= $off . '"' . $key . '"=>' . self::_typing($value) . ',' . $cr;
            }

        }
        return $php;
    }
}
