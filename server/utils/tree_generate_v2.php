<?php
namespace wu\utils;

use fmihel\base\Base;
use fmihel\console;
use fmihel\lib\Common;

require_once __DIR__ . '/Compatible.php';
require_once __DIR__ . '/consts.php';

const F_CAPTION = 'caption';
const F_ID = 'id';
const F_CHILDS = 'childs';
const F_DATA = 'data';
const F_TYPE = 'type';
const F_VIEW_AS = F_TYPE;
const F_ICON = 'icon';
const F_SUBSET = 'access';
const F_LIST = 'list';
const F_PRINT = 'print';
const F_DOWNLOAD = 'download';
/*
const F_CAPTION = 'n';
const F_ID = 'id';
const F_CHILDS = 'c';
const F_DATA = 'd';
const F_TYPE = 't';
const F_ICON = 'i';
const F_SUBSET = 'a';
 */
class TREE_GENERATE_V2
{

    private static $id = 0;
    private static $custom_id = [];

    public static function create($saveToFile = false, $param = [])
    {
        $param = array_merge([
            'varname' => 'CATALOG',
        ], $param);

        $out = [];

        $q = 'select * from CTLG_NODE where ID_PARENT = 0 and  ARCH<>1 order by NOM_PP';
        $ds = Base::ds($q, 'deco', 'utf8');

        while ($row = Base::read($ds)) {
            $out[] = self::_create($row);
        }

        if ($saveToFile) {

            $json = Compatible::Arr_to_json($out, true);
            file_put_contents($saveToFile, 'const ' . $param['varname'] . '=' . $json . ';');

        } else {
            return $out;
        }

    }

    private static function _create($node)
    {
        $out = [];
        $data = [];
        $print = [];
        $download = [];

        $ID = $node['SRCE_ID'];
        $kind = SRCE_KIND[$node['SRCE_KIND']];
        //$FIELD = $kind['field'];
        //$TABLE = $kind['table'];

        $out[F_ID] = $node['ID_CTLG_NODE'];
        //$out[$FIELD] = $ID;
        //$out['table'] = $TABLE;

        $out[F_CAPTION] = self::stringCorrect($node['CAPTION']);
        $out[F_ICON] = ICONS[$node['ICON_IND']];
        //$out['SRCE_KIND'] = $node['SRCE_KIND'];
        //$out['ID_ORDER_BLANK_TREE'] = $node['ID_ORDER_BLANK_TREE'];

        $media = self::_get_media(($ID != '0' ? $ID : $node['ID_CTLG_NODE']), Common::get($kind, 'media_kind', ''));
        if (isset($media['gallery'])) {
            foreach ($media['gallery'] as $item) {
                $data[F_LIST][] = ['type' => 'img', 'url' => $item];
            }
        }
        if (isset($media['video'])) {
            foreach ($media['video'] as $item) {
                $data[F_LIST][] = ['type' => 'video', 'url' => $item['PATH_WWW']];
            }
        }
        if (isset($media['print'])) {
            foreach ($media['print'] as $item) {
                $print[] = ['caption' => $item['CAPTION'], 'url' => $item['PATH_WWW']];
            }
        }

        if (isset($media['download'])) {
            foreach ($media['download'] as $item) {
                $download[] = ['caption' => $item['CAPTION'], 'url' => $item['PATH_WWW']];
            }

        }

        //$out['media'] = self::_get_media(($ID != '0' ? $ID : $node['ID_CTLG_NODE']), Common::get($kind, 'media_kind', ''));
        //-------------------------------------------------------------------------------------------------------
        $q = 'select distinct ID_CTLG_SUBSET from CTLG_SUBSET_NODE where ID_CTLG_NODE = ' . $node['ID_CTLG_NODE'];
        $ds = Base::ds($q, 'deco');
        $out[F_SUBSET] = [];
        while ($row = base::read($ds)) {
            $out[F_SUBSET][] = $row['ID_CTLG_SUBSET'];
        }
        //console::logf($out, function ($out) {
        //    return count($out[F_SUBSET]) > 1;
        //});
        //-------------------------------------------------------------------------------------------------------
        $child = [];
        $q = 'select * from CTLG_NODE where ID_PARENT = ' . $node['ID_CTLG_NODE'] . ' and  ARCH<>1 order by NOM_PP';
        $ds = Base::ds($q, 'deco', 'utf8');

        while ($row = Base::read($ds)) {
            $child[] = self::_create($row);
        }

        $isLastNode = (count($child) === 0);
        //-------------------------------------------------------------------------------------------------------

        if (isset($media['video'])) {
            if ($isLastNode) {
                $out[F_TYPE] = 'viewer';
            }

        } elseif ($node['SRCE_KIND'] == 0) {
            if ($isLastNode) {
                $out[F_TYPE] = 'viewer';
            }

        } elseif (($node['SRCE_KIND'] == 1) || ($node['SRCE_KIND'] == 2) || ($node['SRCE_KIND'] == 6) || ($node['SRCE_KIND'] == 7)) {
            $karniz = self::karniz($node);
            $data = array_merge_recursive($data, $karniz[F_DATA]);
            unset($karniz[F_DATA]);
            $out = array_merge($out, $karniz);
            if (isset($karniz[F_TYPE]) && $karniz[F_TYPE] === 'karnizA') {
                $print = array_merge($print,
                    [
                        ['caption' => 'Прайс-лист', 'url' => '#report/karnizA'],
                    ]
                );
            }
        } elseif (($node['SRCE_KIND'] == 8) || ($node['SRCE_KIND'] == 9)) {
            if ($isLastNode) {
                $out[F_TYPE] = 'tkani';
                $data[$kind['field']] = $ID;
            };
        } elseif (($node['SRCE_KIND'] == 10) || ($node['SRCE_KIND'] == 11)) {
            if ($isLastNode) {
                $out[F_TYPE] = 'jaluzi';
                $data[$kind['field']] = $ID;
                $data['IS_FOLDER'] = ($kind['table'] === 'J_FOLDER' ? 1 : 0);
                $data[F_LIST] = self::jaluzi($ID, $kind['table'] === 'J_FOLDER');
            };
        }

        if ($isLastNode) {
            $data = array_merge_recursive((isset($out[F_DATA]) ? $out[F_DATA] : []), $data);
            if (count(array_keys($data)) > 0) {
                $out[F_DATA] = $data;
            }
        }

        if (count($print)) {
            $out[F_PRINT] = $print;
        }
        if (count($download)) {
            $out[F_DOWNLOAD] = $download;
        }

        if (!$isLastNode) {
            $out[F_CHILDS] = $child;
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
            '&quot;',
            '&amp;',
        );
        $to = array(
            '', //0
            '', //0
            '',
            '',
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
        $ds = Base::ds($q, 'deco', 'utf8');
        if ($ds) {
            $row = [];
            while ($row = Base::read($ds)) {

                $row['CAPTION'] = self::stringCorrect($row['CAPTION']);
                if ($PROCESSING_KIND !== $row['PROCESSING_KIND']) {
                    $PROCESSING_KIND = $row['PROCESSING_KIND'];
                }

                //$row['PATH_WWW'] = HTTP_MEDIA.$row['PATH_WWW'];

                if ($PROCESSING_KIND == 1) {
                    //$view[] = Dir::join([HTTP_MEDIA, $row['PATH_WWW']]);
                    $view[] = $row['PATH_WWW'];
                } elseif ($PROCESSING_KIND == 2) {
                    //$row['PATH_WWW'] = Dir::join([HTTP_MEDIA, $row['PATH_WWW']]);
                    $download[] = $row;
                } elseif ($PROCESSING_KIND == 3) {
                    //$row['PATH_WWW'] = Dir::join([HTTP_MEDIA, $row['PATH_WWW']]);
                    $print[] = $row;
                } elseif ($PROCESSING_KIND == 4) {
                    //$row['PATH_WWW'] = Dir::join([HTTP_VIDEO, $row['PATH_WWW']]);
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
        $out = [
            F_DATA => [],
        ];
        $kind = SRCE_KIND[$row['SRCE_KIND']];

        $ID = $row['SRCE_ID'];
        $FIELD = $kind['field'];
        $TABLE = $kind['table'];
        $IS_CHAPTER = $kind['is_chapter'];
        $priceType = self::_typePrice($ID, $IS_CHAPTER);
        $out[F_DATA]['IS_CHAPTER'] = $IS_CHAPTER ? 1 : 0;
        $out[F_DATA][$FIELD] = $ID;

        $q = "SELECT SHOW_AS FROM $TABLE WHERE $FIELD=$ID";
        $show_as = Base::value($q, 'deco', ['default' => 0]);

        $viewAs = self::_viewAs($show_as, $priceType);
        if ($viewAs) {
            $out[F_VIEW_AS] = $viewAs;

            if ($viewAs === 'karnizB') {
                $out[F_DATA]['ID_K_TOVAR'] = self::idTovarPriceB($ID, $IS_CHAPTER);
            }
        }
        return $out;
    }

    private static function jaluzi($id, $is_folder, $param = [])
    {

        $data = [];

        $param = array_merge([
            'PRINT' => 1,
            'BLOCK' => -1, /* [dataIndex,blockIndex]*/
            'CAN_GROUP_ENABLE' => true,
            'kind' => -1,
            'format' => false,
        ], $param);

        // -----------------------------------------------------------
        if ($is_folder) {
            $q = "select * from J_SET where ID_J_FOLDER=" . $id;
        } else {
            $q = "select * from J_SET where ID=" . $id;
        }

        if ($param['PRINT'] >= 0) {
            $q .= " and PRINT=" . $param['PRINT'];
        }

        $q .= ' and ARCH<>1 order by ORD';
        $ds = Base::ds($q, 'deco', 'utf8');

        while ($row = Base::read($ds)) {

            $ID_J_SET = $row['ID'];
            // показывать остатки
            //$param['SHOW_QUANTITY'] = $row['SHOW_QUANTITY'] == 1 ? true : false;

            //$row['NOTE'] = self::noteFormat($row['NOTE']);

            $q = "select * from J_IMAGES where ID_J_SET=$ID_J_SET and ARCH<>1 order by ORD";
            $imgs = Base::ds($q, 'deco', 'utf8');
            /**
             * IMG.POS =
             * 0 - Не отображать
             * 1 - Перед таблицей
             * 2 - После таблицы
             * 3 - После примечания
             */
            // -----------------------------------------------------------------
            // добавление в начало изображений
            if ($imgs) {
                while ($img = Base::read($imgs)) {
                    if ($img['POS'] == 1) {
                        $data[] = ['type' => 'img', 'ID_J_IMAGE' => $img['ID']];
                    }
                }
            }

            // -----------------------------------------------------------------
            // добавление прайс-листов

            // определяем тип отображения
            $KIND_SHOW = ($param['kind'] == -1 ? $row['KIND_SHOW'] : $param['kind']);

            switch ($KIND_SHOW) {
                case '1':$data[] = ['type' => 'tab', 'ID_J_SET' => $ID_J_SET];
                    break;
                case '2':$data[] = ['type' => 'grid', 'ID_J_SET' => $ID_J_SET];
                    break;
                case '3':$data[] = ['type' => 'cell', 'ID_J_SET' => $ID_J_SET];
                    break;
            }
            // -----------------------------------------------------------------
            // добавление в конец изображений
            if ($imgs) {
                Base::first($imgs);
                while ($img = Base::read($imgs)) {
                    if ($img['POS'] >= 2) {
                        $data[] = ['type' => 'img', 'ID_J_IMAGE' => $img['ID']];
                    }
                }
            }

        } //while

        return $data;
    }
    private static function _viewAs($SHOW_AS, $typePrice = '')
    {

        if (($SHOW_AS == 1) && ($typePrice !== '')) {
            return 'karniz' . $typePrice;
        }

        if ($SHOW_AS == 2) {
            return 'viewer'; //gallery
        }

        if ($SHOW_AS == 3) {
            return 'viewer'; //video
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
    /** возырвщает уникальный id */
    private static function getId($custom = false)
    {
        if ($custom === false) {
            self::$id += 1;
            return self::$id;
        } else {
            if (array_search($custom, self::$custom_id) !== false) {
                console::error('duplicate custom id ', $custom);
            } else {
                self::$custom_id[] = $custom;
            }
            return $custom;
        }
    }
}
