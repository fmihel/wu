<?php
namespace wu\utils;

use fmihel\base\Base;
use fmihel\console;
use fmihel\lib\Common;

require_once __DIR__ . '/XmlCatalogFast.php';
require_once __DIR__ . '/Compatible.php';
require_once __DIR__ . '/consts.php';

class CreateTreeFull
{

    public static function SAVE($to, $data)
    {
        $file = __DIR__ . '/catalog_full.js';
        $content = file_get_contents($file);

        $json = mb_substr($content, mb_strpos($content, '{'));

        $php = Compatible::Arr_from_json($json);

        $php[$to] = $data;

        $json = Compatible::Arr_to_json($php);

        file_put_contents($file, 'var catalog=' . $json . ';');

    }

    public static function GENERATE()
    {

        $out = [];
        $q = 'select * from CTLG_NODE where ID_PARENT = 0 and  ARCH<>1 order by NOM_PP';
        $ds = Base::ds($q, 'deco', 'utf8');

        if ($ds) {

            while ($row = Base::read($ds)) {
                $out[] = [
                    'caption' => $row['CAPTION'],
                    'child' => self::child($row['ID_CTLG_NODE']),
                    'ICON_IND' => $row['ICON_IND'],
                    'id' => $row['ID_CTLG_NODE'],
                ];

            }
        } else {
            console::error($q);
        }

        return ['res' => 1, 'data' => $out];

    }
    private static function child($id_parent)
    {

        $out = [];
        $q = "select * from CTLG_NODE where ID_PARENT = $id_parent and  ARCH<>1 order by NOM_PP";
        $ds = Base::ds($q, 'deco', 'utf8');

        if ($ds) {
            while ($row = Base::read($ds)) {

                $kind = SRCE_KIND[$row['SRCE_KIND']];

                $ID = $row['SRCE_ID'];
                $FIELD = $kind['field'];
                $TABLE = $kind['table'];

                $node = [];
                $node['id'] = $row['ID_CTLG_NODE'];
                $node[$FIELD] = $ID;

                $node['caption'] = $row['CAPTION'];
                $node['child'] = self::child($row['ID_CTLG_NODE']);
                //$node['ICON_IND']   =   $row['ICON_IND'];
                $node['icon'] = ICONS[$row['ICON_IND']];

                $node['media'] = self::_get_media($ID, Common::get($kind, 'media_kind', ''));

                if (($row['SRCE_KIND'] == 1) || ($row['SRCE_KIND'] == 2) || ($row['SRCE_KIND'] == 6) || ($row['SRCE_KIND'] == 7)) {
                    $node['IS_CHAPTER'] = ($kind['is_chapter'] ? 1 : 0);
                    $node = array_merge($node, self::karniz($row));
                } else if (($row['SRCE_KIND'] == 8) || ($row['SRCE_KIND'] == 9)) {
                    $node = array_merge($node, self::tkani($row));
                } else if (($row['SRCE_KIND'] == 10) || ($row['SRCE_KIND'] == 11)) {
                    $node = array_merge($node, self::jaluzi($row));
                }

                $out[] = $node;

            }
        } else {
            console::error($q);
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

    private static function _chapters($id_k_chapter)
    {

        //$data = self::_imagesKarnizFromXml($id_k_chapter,1);
        $up = self::_get_media($id_k_chapter, true);

        $data = [];

        $q =
            "SELECT
                ID_K_CHAPTER,
                CAPTION,
                ID_PARENT,
                SHOW_AS
            from
                K_CHAPTER
            where
                ARCH<>1
                and
                ID_PARENT = $id_k_chapter
            order by
                NOM_PP";

        $ds = Base::ds($q, 'deco', 'utf8');

        if ($ds) {
            while ($row = Base::read($ds)) {

                $media = self::_get_media($row['ID_K_CHAPTER'], true);
                if (count($up['download']) > 0) {
                    $media = array_merge($up, $media);
                }

                $node = array(
                    'caption' => $row['CAPTION'],
                    'IS_CHAPTER' => 1,
                    'ID' => $row['ID_K_CHAPTER'],
                    'child' => [],
                    'media' => $media,
                );

                $child = self::_chapters($row['ID_K_CHAPTER']);
                if (count($child) > 0) {
                    $node['child'] = array_merge($node['child'], $child);
                }

                $child = self::_models($row['ID_K_CHAPTER']);

                if (count($child) > 0) {
                    $node['child'] = array_merge($node['child'], $child);
                }

                if (count($node['child']) === 0) {
                    $typePrice = self::_typePrice($row['ID_K_CHAPTER'], true);
                }

                if ($typePrice === 'B') {
                    $tovars = self::priceBTovarList($row['ID_K_CHAPTER'], true);

                    if ($tovars) {
                        while ($tovar = Base::read($tovars)) {
                            $node['child'] = array(array(
                                'caption' => 'Прайс ' . $tovar['NAME'],
                                'ID_K_TOVAR' => $tovar['ID_K_TOVAR'],
                                'IS_CHAPTER' => 1,
                                'ID' => $row['ID_K_CHAPTER'],
                                "icon" => "file",
                                "viewAs" => self::_viewAs($row['SHOW_AS'], $typePrice),
                            ));
                        }
                    } else {
                        console::error("tovars is empty");
                    }

                } else {

                    //$node['child']=array(array(
                    //        'caption'=>'_price_',
                    //        "icon"=>"file",
                    //        "viewAs"=> 'karniz'.$typePrice
                    //));
                }

                $data[] = $node;
            }
        } else {
            console::error($q);
        }

        return $data;
    }

    private static function _chaptersInModel($id_k_model)
    {

        $data = [];
        $up = self::_get_media($id_k_model, false);

        $q =
            "SELECT
                ID_K_CHAPTER,
                CAPTION,
                ID_PARENT,
                ID_K_MODEL,
                SHOW_AS
            from
                K_CHAPTER
            where
                ARCH<>1
                and
                ID_K_MODEL = $id_k_model
            order by
                NOM_PP
            ";

        $ds = Base::ds($q, 'deco', 'utf8');

        if ($ds) {

            while ($row = Base::read($ds)) {

                $media = self::_get_media($row['ID_K_CHAPTER'], true);
                if (count($up['download']) > 0) {
                    $media = array_merge($up, $media);
                }

                $child = self::_chapters($row['ID_K_CHAPTER']);

                if (count($child) > 0) {
                    $node = array(
                        'caption' => $row['CAPTION'],
                        'IS_CHAPTER' => 1,
                        'ID' => $row['ID_K_CHAPTER'],
                        'child' => $child,
                        'media' => $media,
                    );
                    $data[] = $node;

                } else {

                    $typePrice = self::_typePrice($row['ID_K_CHAPTER'], true);

                    if ($typePrice === 'B') {

                        $tovars = self::priceBTovarList($row['ID_K_CHAPTER'], true);

                        if ($tovars) {
                            while ($tovar = Base::read($tovars)) {
                                $node = array(
                                    'caption' => $tovar['NAME'],
                                    'IS_CHAPTER' => 1,
                                    'ID' => $row['ID_K_CHAPTER'],
                                    'child' => [],
                                    'ID_K_TOVAR' => $tovar['ID_K_TOVAR'],
                                    'icon' => 'file',
                                    "viewAs" => self::_viewAs($row['SHOW_AS'], $typePrice),
                                    'media' => $media,
                                );
                                $data[] = $node;
                            }
                        } else {
                            console::log("Error [...]", __FILE__, __LINE__);
                        }

                    } else {
                        $node = [
                            'caption' => $row['CAPTION'],
                            'IS_CHAPTER' => 1,
                            'ID' => $row['ID_K_CHAPTER'],
                            'child' => [],
                            'icon' => 'file',
                            'viewAs' => self::_viewAs($row['SHOW_AS'], $typePrice),
                            'media' => $media,
                        ];
                        $data[] = $node;
                    }

                }

                /*

            $node    = array('caption'=>$row['CAPTION'],'IS_CHAPTER'=>1,'ID'=>$row['ID_K_CHAPTER'],'child'=>[]);
            $child   = self::_chapters($row['ID_K_CHAPTER']);
            //$child = [];

            if (count($child)>0)
            $node['child']=array_merge($node['child'],$child);

            if (count($node['child'])===0){
            $typePrice = self::_typePrice($row['ID_K_CHAPTER'],true);

            $node['icon']='file';
            $node['viewAs'] = 'karniz'.$typePrice;
            }
            $data[]= $node;
             */
            }

        } else {
            console::error($q);
        }

        return $data;
    }

    private static function _models($id_k_chapter)
    {

        //$data = self::_imagesKarnizFromXml($id_k_chapter,1);
        $up = self::_get_media($id_k_chapter, true);

        $data = [];
        $q =
            "SELECT
                ID_K_MODEL,
                NAME,
                SHOW_AS
            from
                K_MODEL
            where
                ARCH<>1
                and
                ID_K_CHAPTER = $id_k_chapter
            order by
                NOM_PP
            ";

        $ds = Base::ds($q, 'deco', 'utf8');

        if ($ds) {
            while ($row = Base::read($ds)) {

                $media = self::_get_media($row['ID_K_MODEL'], false);

                if (count($up['download']) > 0) {
                    $media = array_merge($up, $media);
                }

                $node = [
                    'caption' => $row['NAME'],
                    'IS_CHAPTER' => 0,
                    'ID' => $row['ID_K_MODEL'],
                    'child' => [],
                    'media' => $media,
                ];

                $child = self::_chaptersInModel($row['ID_K_MODEL']);

                //$images = [];
                //$node['child']=array_merge($node['child'],$images);

                if (count($child) > 0) {

                    if (self::_hasTovar($row['ID_K_MODEL'], false)) {
                        $child[] = [
                            'caption' => 'Прайс',
                            'IS_CHAPTER' => 0,
                            'ID' => $row['ID_K_MODEL'],
                            'child' => [],
                            'icon' => 'file',
                            'media' => $media,
                            "viewAs" => self::_viewAs(1, self::_typePrice($row['ID_K_MODEL'], false)),
                        ];

                    }

                    $node['child'] = array_merge($node['child'], $child);

                } else {

                    $typePrice = self::_typePrice($row['ID_K_MODEL'], false);

                    if ($typePrice === 'B') {
                        $tovars = self::priceBTovarList($row['ID_K_MODEL'], false);

                        if ($tovars) {
                            while ($tovar = Base::read($tovars)) {
                                $node['child'][] = [
                                    'caption' => 'Прайс ' . $tovar['NAME'],
                                    "icon" => "file",
                                    'IS_CHAPTER' => 0,
                                    'ID' => $row['ID_K_MODEL'],
                                    'ID_K_TOVAR' => $tovar['ID_K_TOVAR'],
                                    "viewAs" => self::_viewAs($row['SHOW_AS'], $typePrice),
                                ];

                            }
                        } else {
                            console::error($q);
                        }

                    } else {
                        $node['child'][] = [
                            'caption' => 'Прайс',
                            "icon" => "file",
                            'IS_CHAPTER' => 0,
                            'ID' => $row['ID_K_MODEL'],
                            "viewAs" => self::_viewAs($row['SHOW_AS'], $typePrice),
                        ];
                    }

                }

                $data[] = $node;
            };

        } else {
            console::error($q);
        }

        return $data;
    }
    private static function _viewAs($SHOW_AS, $typePrice = '')
    {

        if (($SHOW_AS == 1) && ($typePrice !== '')) {
            return 'karniz' . $typePrice;
        }

        if ($SHOW_AS == 2) {
            return 'gallery';
        }

        return '';

    }
    private static function _get_media($id, $OWNER_KIND)
    {

        $view = [];
        $download = [];
        $print = [];

        $OWNER_ID = $id;
        //$OWNER_KIND = ($is_chapter?2:1);

        $q = "select ID_C_MEDIA_FILE,CAPTION,PATH_WWW,PROCESSING_KIND from C_MEDIA_FILE where OWNER_ID = $OWNER_ID and OWNER_KIND = $OWNER_KIND and ARCH<>1 order by PROCESSING_KIND,NOM_PP ";
        if ($id == '362') {
            //console::log("[$q]", __FILE__, __LINE__);
        }
        $PROCESSING_KIND = -1;
        $ds = Base::ds($q, 'deco', 'utf8');
        if ($ds) {
            while ($row = Base::read($ds)) {

                if ($PROCESSING_KIND !== $row['PROCESSING_KIND']) {
                    $PROCESSING_KIND = $row['PROCESSING_KIND'];
                }

                $row['PATH_WWW'] = HTTP_MEDIA . $row['PATH_WWW'];
                if ($PROCESSING_KIND == 1) {
                    $view[] = $row['PATH_WWW'];
                } elseif ($PROCESSING_KIND == 2) {
                    $download[] = $row;
                } elseif ($PROCESSING_KIND == 3) {
                    $print[] = $row;
                }

            }
        } else {
            console::error($q);
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

        return $out;

    }

    /** возвращает букву определяющую тип прайса карнизов A или B */
    private static function _typePrice($id, $is_chapter)
    {

        $q =
            "SELECT distinct
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
            where ";

        if ($is_chapter) {
            $q .= 'mt.ID_K_CHAPTER=' . $id;
        } else {
            $q .= 'mt.ID_K_MODEL=' . $id;
        }

        return (Base::value($q, 'deco', ['default' => 1]) == 2 ? 'B' : 'A');
    }
    /** проевряет, есть литовары привязаные к узлу */
    private static function _hasTovar($id, $is_chapter)
    {
        $q = 'select count(ID_K_MODEL_TOVAR) C from K_MODEL_TOVAR where ARCH<>1 and ' . ($is_chapter ? 'ID_K_CHAPTER' : 'ID_K_MODEL') . '=' . $id;

        return (Base::value($q, 'deco', ['default' => 0]) > 0);
    }

    private static function priceBTovarList($id, $is_chapter, $onlyCount = false)
    {
        // возвращает список товаров по которым будут создаваться матричные прайс-листы

        if ($onlyCount) {
            $q = 'select count(distinct t.ID_K_TOVAR) C ';
        } else {
            $q = 'select distinct t.ID_K_TOVAR,t.NAME ';
        }

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

        if ($onlyCount) {
            return intval(Base::value($q, 'C', 0, 'deco'));
        } else {
            return Base::ds($q, 'deco', 'utf8');
        }

    }

}
