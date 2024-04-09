<?php
namespace wu\utils;

/*
SRCE_KIND = 1 :    SRCE_ID соотвествует ID_K_CHAPTER из K_CHAPTER
SRCE_KIND = 2 :    SRCE_ID соотвествует ID_K_MODEL из K_MODEL
SRCE_KIND = 6 :    SRCE_ID соотвествует ID_K_CHAPTER из K_CHAPTER
SRCE_KIND = 7 :    SRCE_ID соотвествует ID_K_CHAPTER из K_CHAPTER
SRCE_KIND = 8 :    SRCE_ID соотвествует  ID_TX_SECTION из TX_SECTION
SRCE_KIND = 9 :    SRCE_ID соотвествует  ID_TX_SET из TX_SET
SRCE_KIND = 10 :   SRCE_ID соотвествует  ID из J_FOLDER
SRCE_KIND = 11 :   SRCE_ID соотвествует  ID из J_SET
 */

const SRCE_KIND = [
    /* 0*/['table' => '', 'field' => 'SRCE_ID', 'media_kind' => 0],
    /* 1*/['table' => 'K_CHAPTER', 'field' => 'ID_K_CHAPTER', 'is_chapter' => true, 'media_kind' => 1],
    /* 2*/['table' => 'K_MODEL', 'field' => 'ID_K_MODEL', 'is_chapter' => false, 'media_kind' => 2],
    /* 3*/['table' => '', 'field' => '', 'media_kind' => 3],
    /* 4*/['table' => '', 'field' => '', 'media_kind' => 4],
    /* 5*/['table' => '', 'field' => '', 'media_kind' => 5],
    /* 6*/['table' => 'K_CHAPTER', 'field' => 'ID_K_CHAPTER', 'is_chapter' => true, 'media_kind' => 6],
    /* 7*/['table' => 'K_CHAPTER', 'field' => 'ID_K_CHAPTER', 'is_chapter' => true, 'media_kind' => 7],
    /* 8*/['table' => 'TX_SECTION', 'field' => 'ID_TX_SECTION', 'media_kind' => 8],
    /* 9*/['table' => 'TX_SET', 'field' => 'ID_TX_SET', 'media_kind' => 9],
    /*10*/['table' => 'J_FOLDER', 'field' => 'ID', 'media_kind' => 10],
    /*11*/['table' => 'J_SET', 'field' => 'ID', 'media_kind' => 11],

];

/*
Иконки узлов
0-Карнизы корень
1-карнизы папка
2-Ткани корень
3-Ткани папка
4-Жалюзи корень
5-Жалюзи папка
6-Ткани прайс
7-Карнизы прайс
8-Жалюзи прайс
9-Карнизы модель
10-Изображение
11-Описание
12-Жалюзи модель
13-Карнизы сетка
14-Ткани сетка
15-Жалюзи сетка
 */

const ICONS = [
    'root_karniz', //0
    'folder_karniz', //1
    'root_tkani', //2
    'folder_tkani', //3
    'root_jaluzi', //4
    'folder_jaluzi', //5
    'price_tkani', //6
    'price_karniz', //7
    'price_jaluzi', //8
    'model_karniz', //9
    'page_image', //10
    'page_notes', //11
    'model_jaluzi', //12
    'grid_karniz', //13
    'grid_tkani', //14
    'grid_jaluzi', //15

    'root_electro', //16
    'folder_electro', //17
    'model_electro', //18
    'price_electro', //19
    'grid_electro', //20

    'model_tkani', //21
    '', //22
    'video_karniz', //23
    'video_tkani', //24
    'video_jaluzi', //25
    'video_elektro', //26

];

const RUS_BUK = ['а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я'];
const ENG_BUK = ['a', 'b', 'v', 'g', 'd', 'e', 'e', 'g', 'z', 'i', 'i', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'c', 's', 's', '', '', '', 'e', 'u', 'y'];
