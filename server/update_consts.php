<?php
//----------------------------------------------------------------------

// массив индексов таблиц
$TABLE_INDEX = array(
    'AREA_ACT' => 'ID_AREA_ACT',
    'DELETED_LINES' => 'ID_DELETED_LINES',
    'ED_IZM' => 'ID_ED_IZM',
    'K_CHAPTER' => 'ID_K_CHAPTER',
    'K_COLOR' => 'ID_K_COLOR',
    'K_MATERIAL' => 'ID_K_MATERIAL',
    'K_MODEL' => 'ID_K_MODEL',
    'K_MODEL_LOW' => 'ID_K_MODEL',
    'K_MODEL_BIG' => 'ID_K_MODEL',
    'K_MODEL_COLOR' => 'ID_K_MODEL_COLOR',
    'K_MODEL_MATERIAL' => 'ID_K_MODEL_MATERIAL',
    'K_MODEL_TOVAR' => 'ID_K_MODEL_TOVAR',
    'K_MODEL_TOVAR_DETAIL' => 'ID_K_MODEL_TOVAR_DETAIL',
    'K_MOD_SHAB_PROP_KIND' => 'ID_K_MOD_SHAB_PROP_KIND',
    'K_TEMPL' => 'ID_K_TEMPL',
    'K_TEMPL_TOV' => 'ID_K_TEMPL_TOV',
    'K_TEMPL_TOV_ALG' => 'ID_K_TEMPL_TOV_ALG',
    'K_TOVAR' => 'ID_K_TOVAR',
    'K_TOVAR_DETAIL' => 'ID_K_TOVAR_DETAIL',
    'K_WIN_KIND' => 'ID_K_WIN_KIND',
    'PROP' => 'ID_PROP',
    'PROP_KIND' => 'ID_PROP',

    'TX_SECTION' => 'ID_TX_SECTION',
    'TX_TOV_CONNECT' => 'ID_TX_TOV_CONNECT',
    'TX_SET' => 'ID_TX_SET',
    'TEXTILE' => 'ID_TEXTILE',
    'TX_PROP_SET' => 'ID_TX_PROP_SET',
    'TX_COLOR' => 'ID_TX_COLOR',
    'STATE' => 'ID_STATE',
    'TX_ED_IZM' => 'ID_ED_IZM_TX',

    'J_FOLDER' => 'ID',
    'J_SET' => 'ID',
    'J_TOVAR' => 'ID',
    'TOVAR' => 'ID_TOVAR',
    'J_TOV_CONNECT' => 'ID',
    'J_FIELDS_CONNECT' => 'ID',
    'J_SET_FIELDS' => 'ID',
    'J_IMAGES' => 'ID',
    'COD' => 'ID_COD',

    'J_TMPL' => 'ID_J_TMPL',
    'J_TMPL_PAR' => 'ID_J_TMPL_PAR',
    'J_TMPL_PAR_CHOICE' => 'ID_J_TMPL_PAR_CHOICE',
    'J_TMPL_PAR_LIMIT' => 'ID_J_TMPL_PAR_LIMIT',
    'J_TMPL_PAR_VIS_COND' => 'ID_J_TMPL_PAR_VIS_COND',
    'J_ALG' => 'ID',
    'J_BLANK' => 'ID_J_BLANK',
    'J_BLANK_CELL' => 'ID_J_BLANK_CELL',
    'J_COLOR_MAP' => 'ID',

    'K_MODEL_IMAGE' => 'ID_K_MODEL_IMAGE',

    'C_MEDIA_FILE' => 'ID_C_MEDIA_FILE',
    'CTLG_NODE' => 'ID_CTLG_NODE',
    'CTLG_SUBSET' => 'ID_CTLG_SUBSET',
    'CTLG_SUBSET_NODE' => 'ID_CTLG_SUBSET_NODE',

    'D_RIGHT' => 'ID_D_RIGHT',
    'D_ROLE' => 'ID_D_ROLE',
    'D_ROLE_RIGHT' => 'ID_D_ROLE_RIGHT',
    'D_USER' => 'ID_D_USER',
    'D_USER_RIGHT' => 'ID_D_USER_RIGHT',
    'D_USER_ROLE' => 'ID_D_USER_ROLE',

    'K_COMPATIBLE' => 'ID_K_COMPATIBLE',
    'K_COMPONENT' => 'ID_K_COMPONENT',
    'K_COMP_CATEG' => 'ID_K_COMP_CATEG',
    'K_COMP_OWNER' => 'ID_K_COMP_OWNER',
    'K_COMP_PROP' => 'ID_K_COMPONENT',
    'K_M_COMP' => 'ID_K_M_COMP',
    'K_M_COMP_LINE' => 'ID_K_M_COMP_LINE',
    'K_M_VARIANT' => 'ID_K_M_VARIANT',

    'K_BRACKET' => 'ID_K_BRACKET',
    'K_TEMPL' => 'ID_K_TEMPL',
    'K_TEMPL_TOV' => 'ID_K_TEMPL_TOV',
    'K_TEMPL_TOV_ALG' => 'ID_K_TEMPL_TOV_ALG',
    'K_VAR_MISS_COMP' => 'ID_K_VAR_MISS_COMP',

    'ORDERS_BLANK_TREE' => 'ID_ORDER_BLANK_TREE',

);

$KARNIZ_TABLES = array(
    'AREA_ACT',
    'K_CHAPTER',
    'K_MODEL',
    'K_MODEL_TOVAR',
    'K_TOVAR',
    'K_WIN_KIND',
    'K_TOVAR_DETAIL',
    'K_MODEL_TOVAR_DETAIL',
    'K_COLOR',
    'ED_IZM',
    'PROP',
    'PROP_KIND');

$TKANI_TABLES = array(
    'TX_SECTION',
    'TX_TOV_CONNECT',
    'TX_SET',
    'TEXTILE',
    'TX_PROP_SET',
    'TX_COLOR',
    'STATE',
    'TX_ED_IZM',

);
$JALUZI_TABLES = array(
    'J_FOLDER',
    'J_SET',
    'J_TOV_CONNECT',
    'J_TOVAR',
    'TOVAR',
    'J_SET_FIELDS',
    'J_FIELDS_CONNECT',
    'J_IMAGES',
    'COD',
);

$MEDIA_TABLES = array(
    'C_MEDIA_FILE',
    'C_MEDIA_ELEMENT',
);

/**
 * список таблиц, строки из которых будут удаляться,при обработке DELETED_LINES
 *  см. update_utils.UPDATE_UTILS::deleted_lines(...);
 */

$DELETED_TABLES = array(
    'J_IMAGES',
    'J_FIELDS_CONNECT',
    'CTLG_NODE',
    'CTLG_SUBSET',
    'CTLG_SUBSET_NODE',
    'C_MEDIA_FILE',

    'J_TMPL',
    'J_TMPL_PAR',
    'J_TMPL_PAR_CHOICE',
    'J_TMPL_PAR_LIMIT',
    'J_TMPL_PAR_VIS_COND',
    'J_COLOR_MAP',

    'K_COMPATIBLE',
    'K_COMPONENT',
    'K_COMP_CATEG',
    'K_COMP_OWNER',
    'K_COMP_PROP',
    'K_M_COMP',
    'K_M_COMP_LINE',
    'K_M_VARIANT',
    'K_BRACKET',
    'K_TEMPL',
    'K_TEMPL_TOV',
    'K_TEMPL_TOV_ALG',
    'K_VAR_MISS_COMP',
    'ORDERS_BLANK_TREE',

);
