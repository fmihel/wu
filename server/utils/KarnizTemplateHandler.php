<?php
namespace wu\utils;

use fmihel\base\Base;
use fmihel\console;

class KarnizTemplateHandler
{

    private static $COMPONENT_UPDATE_FIELDS     = [];
    private static $PROP_UPDATE_FIELDS          = [];
    private static $STR_COMPONENT_UPDATE_FIELDS = '';
    private static $STR_PROP_UPDATE_FIELDS      = '';

    public static function run()
    {
        Base::startTransaction('deco');
        try {
            console::time('KarnizTemplateHandler::run', false);

            self::$PROP_UPDATE_FIELDS      = self::init_update_prop_fields();
            self::$COMPONENT_UPDATE_FIELDS = self::init_update_component_fields();

            self::$STR_PROP_UPDATE_FIELDS = implode(',', array_map(function ($item) {return 'prop.' . $item;}, self::$PROP_UPDATE_FIELDS));
            self::$STR_COMPONENT_UPDATE_FIELDS = implode(',', array_map(function ($item) {return 'c.' . $item;}, self::$COMPONENT_UPDATE_FIELDS));

            self::map(function ($item, $parent) {

                $category       = $item['ID_K_COMP_CATEG'];
                $parentCategory = $parent['ID_K_COMP_CATEG'];
                $alg            = $item['ALG_NUM'];
                $parentAlg      = $parent['ALG_NUM'];

                if ($category === $parentCategory) {
                    /// 
                }

                return $item;
            });
            Base::commit('deco');
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
                $root = $callback($root, []);
                self::component_save($root);
                self::_map($callback, $root);
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
                $component = $callback($component, $parent);
                self::component_save($component);
                self::_map($callback, $component);
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
        // console::log($q);
        // Base::query($q, 'deco', 'utf8');
        //--------------------------------------------------------------------------------------------
        $data = '';
        foreach (self::$PROP_UPDATE_FIELDS as $field) {
            $data .= ($data ? ',' : ' ') . $field . '="' . $component[$field] . '"';
        }

        $q = 'update K_COMP_PROP set ' . $data . ' where ID_K_COMPONENT=' . $component['ID_K_COMPONENT'];
        // console::log($q);
        // Base::query($q, 'deco', 'utf8');

    }

    private static function init_update_component_fields(): array
    {
        $exclude = [
            'ID_K_COMPONENT',
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

}
