<?php
namespace wu\utils;

use fmihel\base\Base;
use fmihel\console;
use fmihel\lib\Common;

function get($var, array $params, $default = null)
{
    return Common::get($var, ...array_merge($params, [$default]));
}

const ALG_INHERITED          = 0; // Смотри родительский (наследуемый)
const ALG_BY_CATEGORY        = 1; // Согласно категории
const ALG_LENGTH_NES_ELEMENT = 2; // Длина несущего элемента
const ALG_LENGTH_TOVAR       = 3; // Подбор товаров по длине
const ALG_OTLET              = 4; // Подбор товаров по отлету
const ALG_FIX                = 5; // Фиксир.
const ALG_MANUAL             = 6; // Ручной ввод

class KarnizTemplateHandler
{

    private static $COMPONENT_UPDATE_FIELDS     = [];
    private static $PROP_UPDATE_FIELDS          = [];
    private static $STR_COMPONENT_UPDATE_FIELDS = '';
    private static $STR_PROP_UPDATE_FIELDS      = '';
    private static $COUNT_MODIF                 = 0;
    private static $COUNT_ALL                   = 0;

    public static function run()
    {
        Base::startTransaction('deco');
        try {
            console::line();
            console::time('KarnizTemplateHandler::run', true);
            self::$COUNT_MODIF             = 0;
            self::$COUNT_ALL               = 0;
            self::$PROP_UPDATE_FIELDS      = self::init_update_prop_fields();
            self::$COMPONENT_UPDATE_FIELDS = self::init_update_component_fields();

            self::$STR_PROP_UPDATE_FIELDS = implode(',', array_map(function ($item) {return 'prop.' . $item;}, self::$PROP_UPDATE_FIELDS));
            self::$STR_COMPONENT_UPDATE_FIELDS = implode(',', array_map(function ($item) {return 'c.' . $item;}, self::$COMPONENT_UPDATE_FIELDS));

            self::recover_comp_prop();

            self::map(function ($item, $parent) {

                $modif = false;
                if (empty($parent)) { // для корневого компонента
                    $alg = get($item, ['ALG_NOM'], 0);
                    if ($alg == 0) {
                        $modif           = true;
                        $alg             = ALG_BY_CATEGORY;
                        $item['ALG_NOM'] = ALG_BY_CATEGORY;
                    }
                } else { // для компонентов наследников
                    $category       = get($item, ['ID_K_COMP_CATEG']);
                    $parentCategory = get($parent, ['ID_K_COMP_CATEG']);
                    $alg            = get($item, ['ALG_NOM'], 0);
                    // $parentAlg      = get($parent, ['ALG_NUM']);

                    if ($parentCategory == '') {
                        console::log(' родительская категория отсутствует ', $parent);
                    }
                    if ($category != $parentCategory && $alg == ALG_INHERITED) {
                        // если категории не совпадают, но требуется наследование, выставляем - "согласно категории"
                        $modif           = true;
                        $alg             = ALG_BY_CATEGORY;
                        $item['ALG_NOM'] = ALG_BY_CATEGORY;

                        // console::once('категории не совпали', $parent, $item);
                    }

                    if ($category == $parentCategory) { // категории совпадают 

                        if ($alg == ALG_INHERITED) {

                            if ($item['ALG_NOM'] != $parentCategory) {
                                $modif           = true;
                                $item['ALG_NOM'] = $parentCategory;
                            }

                            foreach (self::$COMPONENT_UPDATE_FIELDS as $FIELD) {

                                $current = get($item, [$FIELD], 0);
                                $prev    = get($parent, [$FIELD], 0);

                                if (empty($current) && ! empty($prev)) {
                                    $modif        = true;
                                    $item[$FIELD] = $prev;
                                }
                            }

                            foreach (self::$PROP_UPDATE_FIELDS as $FIELD) {

                                $current = get($item, [$FIELD], 0);
                                $prev    = get($parent, [$FIELD], 0);

                                if (empty($current) && ! empty($prev)) {
                                    $item[$FIELD] = $prev;
                                }
                            }
                        }
                    }
                }
                return ['modif' => $modif, 'item' => $item];
            });
            Base::commit('deco');

            console::log('изменено: ' . self::$COUNT_MODIF . '/' . self::$COUNT_ALL . ' строк');
            console::timeEnd('KarnizTemplateHandler::run');

        } catch (\Exception $e) {
            Base::rollback('deco');
            console::timeEnd('KarnizTemplateHandler::run');
            throw $e;
        }

    }

    private static function map($callback)
    {
        $q  = 'select distinct ID_K_TEMPL from K_TEMPL where 1>0';
        $ds = Base::ds($q, 'deco');
        while ($row = Base::read($ds)) {

            $ID_K_TEMPL = $row['ID_K_TEMPL'];
            $q          = "
            select
                    kco.ID_K_COMP_OWNER,
                    c.ID_K_COMP_CATEG,
                    " . self::$STR_COMPONENT_UPDATE_FIELDS . ",
                    " . self::$STR_PROP_UPDATE_FIELDS . ",
                    c.ID_K_COMPONENT `ID_K_COMPONENT`
            from
                K_COMPONENT c
                left outer join K_COMP_OWNER kco on c.ID_K_COMPONENT = kco.ID_K_COMPONENT
                left outer join K_COMP_PROP prop on c.ID_K_COMPONENT = prop.ID_K_COMPONENT
            where
                c.ID_K_TEMPL = $ID_K_TEMPL
                and
                kco.ID_K_OWNER is NULL
            order by
            c.ID_K_COMPONENT";

            $ds2 = Base::ds($q, 'deco', 'utf8');

            while ($root = Base::read($ds2)) {
                $info = $callback($root, []);
                if ($info['modif']) {
                    $root = $info['item'];
                    self::component_save($root);
                }
                self::_map($callback, $root);
                self::$COUNT_ALL++;
            }

        }
    }

    private static function _map($callback, $parent)
    {
        $ID_K_COMPONENT = $parent['ID_K_COMPONENT'];
        if ($ID_K_COMPONENT) {
            $q = "
                    select
                            kco.ID_K_COMP_OWNER,
                            c.ID_K_COMP_CATEG,
                                " . self::$STR_COMPONENT_UPDATE_FIELDS . ",
                                " . self::$STR_PROP_UPDATE_FIELDS . ",
                            c.ID_K_COMPONENT `ID_K_COMPONENT`
                    from
                        K_COMPONENT c
                        left outer join K_COMP_OWNER kco on c.ID_K_COMPONENT = kco.ID_K_COMPONENT
                        left outer join K_COMP_PROP prop on c.ID_K_COMPONENT = prop.ID_K_COMPONENT
                    where
                        kco.ID_K_OWNER = $ID_K_COMPONENT
                    order by
                    c.ID_K_COMPONENT";

            $ds = Base::ds($q, 'deco', 'utf8');

            while ($component = Base::read($ds)) {

                $info = $callback($component, $parent);
                if ($info['modif']) {
                    $component = $info['item'];
                    self::component_save($component);
                }
                self::_map($callback, $component);

                self::$COUNT_ALL++;
            }
        } else {
            console::log('empty ID_K_COMPONENT', $parent);
        }
    }

    private static function component_save($component)
    {
        $data = '';
        foreach (self::$COMPONENT_UPDATE_FIELDS as $field) {
            $data .= ($data ? ',' : ' ') . $field . '="' . $component[$field] . '"';

        }

        $q = 'update K_COMPONENT set ' . $data . ' where ID_K_COMPONENT=' . $component['ID_K_COMPONENT'];
        //console::once($q);
        Base::query($q, 'deco', 'utf8');
        //--------------------------------------------------------------------------------------------
        $data = '';
        foreach (self::$PROP_UPDATE_FIELDS as $field) {
            $data .= ($data ? ',' : ' ') . $field . '="' . $component[$field] . '"';
        }

        $q = 'update K_COMP_PROP set ' . $data . ' where ID_K_COMPONENT=' . $component['ID_K_COMPONENT'];
        // console::once($q);
        Base::query($q, 'deco', 'utf8');

        self::$COUNT_MODIF++;

    }

    private static function init_update_component_fields(): array
    {
        $exclude = [
            'ID_K_COMPONENT',
            'CONTENT_KIND',
            'ID_K_COMP_CATEG',
            'CAPTION', 'NAME_DESIGN', 'NOTE', 'GROUP_TITLE',
            'DOP_CALC', 'ID_K_TEMPL', 'IND_IN_TREE',
            'ID_K_MODEL_TOVAR_DETAIL', 'ALWAYS_SHOW_CAPTION',
            'HINT_TXT', 'USE_IN_LINE_CAPTION', 'CAN_HIDDEN',
            'ID_ED_IZM',

        ];
        $out    = [];
        $fields = Base::fieldsInfo('K_COMPONENT', 'deco', false);
        foreach ($fields as $field) {
            if ($field['Type'] !== 'blob' && $field['Type'] !== 'datetime' && array_search($field['Field'], $exclude) === false) {
                $out[] = $field['Field'];
            }
        }
        return $out;
    }
    private static function init_update_comp_owner_fields(): array
    {
        $out    = [];
        $fields = Base::fieldsInfo('K_COMP_OWNER', 'deco', false);
        foreach ($fields as $field) {
            if ($field['Type'] !== 'blob' && $field['Type'] !== 'datetime' && $field['Field'] !== 'ID_K_COMPONENT' && $field['Field'] !== 'ID_K_COMP_OWNER' && $field['Field'] !== 'ID_K_OWNER') {
                $out[] = $field['Field'];
            }
        }
        return $out;
    }

    private static function init_update_prop_fields(): array
    {
        $out    = [];
        $fields = Base::fieldsInfo('K_COMP_PROP', 'deco', false);
        foreach ($fields as $field) {
            if ($field['Type'] !== 'blob' && $field['Type'] !== 'datetime' && $field['Field'] !== 'ID_K_COMPONENT') {
                $out[] = $field['Field'];
            }
        }
        return $out;
    }

    /** добавляем недостающие записи в таблицу comp_prop */
    private static function recover_comp_prop()
    {

        try {

            $q  = 'select distinct c.ID_K_COMPONENT from K_COMPONENT c left outer join K_COMP_PROP cp on c.ID_K_COMPONENT = cp.ID_K_COMPONENT where cp.ID_K_COMPONENT is null';
            $ds = Base::ds($q, 'deco');

            Base::startTransaction('deco');
            $count = 0;
            while ($row = Base::read($ds)) {
                $q = 'insert into K_COMP_PROP  (ID_K_COMPONENT) values (' . $row['ID_K_COMPONENT'] . ')';
                Base::query($q, 'deco');
                $count++;
            }
            Base::commit('deco');

            console::log("добавлено $count записей в K_COMP_PROP");
        } catch (\Exception $e) {
            Base::rollback('deco');
            throw $e;
        }
    }

}
