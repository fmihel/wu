<?php

$config = [

    'mode' => 'development',
    'optimizeJS' => 0,

    'bases' => [
        'exweb' => [
            'server' => 'localhost',
            'baseName' => 'exweb',
            'user' => 'mihel',
            'pass' => 'sdk_8Z',
            'alias' => 'exweb',
        ],
        'deco' => [
            'server' => 'localhost',
            'baseName' => '_wd_test',
            'user' => 'mihel',
            'pass' => 'sdk_8Z',
            'alias' => 'deco',
        ],
    ],

    'COMPANY' => 'Windeco',
    'PICTURE_URL' => 'https://windeco.su/dev/source/common/pic.php',
    'APPLICATION_URL' => 'https://windeco.su',
    'EMAIL_ADMIN' => 'info@windeco.su',

    'key' => 'test',

    //'HTTP_MEDIA'=>'http://windeco/wu/server/test/media/',
    'HTTP_MEDIA' => 'https://windeco.su/media/',
    // путь к папке с обновлениями
    'UPDATE_ZIP_PATH' => './test/update/',
    // путь куда распаковывается обновление
    'UNPACK_ZIP_PATH' => './test/tmp/',
    // путь куда сохраняются файлы созданные из блоб
    'BIN_STORY_PATH' => './test/media/',

    //'catalogJsPath'     => '/../../createTree',
    //'arch_path'         => '../../archAll/tmp/',
    //'arch_catalogPath'  => '../../createTree/catalog.js',
    //'arch_mediaPath'    => '../../../../',
    //'arch_mediaHttp'    => 'https://windeco.su/',
    //'arch_zipPath'      => '../../../../download/catalog.zip',

    'catalogJsPath' => '/test/js',
    'arch_path' => './test/tmp/',
    'arch_catalogPath' => './test/js/catalog.js',
    'arch_mediaPath' => '/test/media/',
    'arch_mediaHttp' => 'http://windeco/wu/test/media/',
    'arch_zipPath' => './test/zip/catalog.zip',
    'php_catalogPath' => './test/js/catalog.js',

    'tkani.php' => '../../dev/source/common/tkani.php',
    'jaluzi.php' => '../../dev/source/common/jaluzi.php',
    'common.php' => '../../dev/source/common/common.php',
    'pathOffline' => 'offline/',
    'runOrdersTests' => true,
    'videoPath' => '/home/h003301453/windeco.su/docs/video/',
    'videoUrl' => 'https://windeco.su/video/',
    'url:after_update.php' => 'https://windeco.su/remote_access_api/wu/server/after_update.php',
    'orders-blank-tree' => 'D:\work\windeco\wu\server\test\js\orders_blank_tree.js',

];
