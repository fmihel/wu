<?php

namespace wu\utils;

use fmihel\console;
use fmihel\lib\Common;
use fmihel\lib\Dir;
use fmihel\lib\Str;
use wu\server\zip\drivers\ZipStreamDriver;
use wu\server\zip\Zip;

require_once __DIR__ . '/Compatible.php';
require_once __DIR__ . '/../zip/Zip.php';
class arch
{
    /** Относительный путь к временной папке, куда будет создаваться структура каталога */
    static $path;
    /** Относительный путь к файлу каталога catalog.js */
    static $catalogPath;
    /** Относительный путь к папке, в которой лежит папка media */
    static $mediaPath;
    /** http путь к папке media */
    static $mediaHttp;
    /** каталог внутри $path куда будут помещена стркутура */
    static $catalog = '/catalog';
    /** Относительный путь к файлу, куда будет помещен архив (по умолчанию $path.$catalog.'.zip' )*/
    static $zipPath = '';

    public static function create()
    {

        if (file_exists(self::$zipPath)) {
            @unlink(self::$zipPath);
        };

        if (!self::clearTmp()) {
            return false;
        }

        if (!self::createStruct()) {
            return false;
        }

        if (!self::zip()) {
            return false;
        }

        if (!self::clearTmp()) {
            return false;
        }

        return true;

    }

    public static function debug_info($cr = '<br>')
    {
        $space = '&nbsp;&nbsp;&nbsp;&nbsp;';
        $res = 'arch{' . $cr;
        $res .= $space . 'path = [' . self::$path . ']' . $cr;
        $res .= $space . 'catalogPath = [' . self::$catalogPath . ']' . $cr;
        $res .= $space . 'mediaPath = [' . self::$mediaPath . ']' . $cr;
        $res .= $space . 'mediaHttp = [' . self::$mediaHttp . ']' . $cr;
        $res .= $space . 'catalog = [' . self::$catalog . ']' . $cr;
        $res .= '}' . $cr;
        return $res;
    }

    /** очистка папки с маршрутом */
    public static function clearTmp()
    {

        $dir = Dir::slash(self::$path, false, true);
        if (Dir::exist($dir)) {
            Dir::clear($dir);
        }

        return true;
    }

    private static function trans($textcyr)
    {

        $cyr = array(
            'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п',
            'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я',
            'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П',
            'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я',
        );
        $lat = array(
            'a', 'b', 'v', 'g', 'd', 'e', 'e', 'j', 'z', 'i', 'i', 'k', 'l', 'm', 'n', 'o', 'p',
            'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sh', '', 'i', '', 'e', 'u', 'ya',
            'A', 'B', 'V', 'G', 'D', 'E', 'E', 'J', 'Z', 'I', 'I', 'K', 'L', 'M', 'N', 'O', 'P',
            'R', 'S', 'T', 'U', 'F', 'H', 'C', 'CH', 'SH', 'SH', '', 'I', '', 'E', 'U', 'YA',
        );
        return str_replace($cyr, $lat, $textcyr);
    }

    private static function preTrans($text)
    {

        $text = Compatible::Str_replace_loop('  ', ' ', $text);

        $text = str_replace(
            array('&quot;', '.', ',', '-', ' '),
            array('_', '_', '_', '_', '_'),
            $text
        );

        $text = Compatible::Str_replace_loop('__', '_', $text);
        return $text;
    }

    private static function _createStruct($toPath, $catalog, $level = 0, $path = '/')
    {

        //if ($level===4) return '';
        if (gettype($catalog) === 'array') {
            for ($i = 0; $i < count($catalog); $i++) {
                $item = $catalog[$i];

                $child = Common::get($item, 'child', null);

                $name = self::preTrans($item['caption']);
                $name = self::trans($name);
                $download = ((isset($item['media'])) && (isset($item['media']['download'])) && (gettype($item['media']['download']) === 'array')) ? $item['media']['download'] : array();

                if (count($download) > 0) {

                    $createPath = $toPath . $path . $name;
                    $can = true;
                    if (!file_exists($createPath)) {

                        if (!mkdir($createPath, 0777, true)) {
                            $can = false;
                            //console::log('mkdir("'.$createPath.'")','error',__FILE__,__LINE__);
                        }
                    }

                    $cntK = count($download);
                    if ($can) {
                        for ($k = 0; $k < $cntK; $k++) {

                            $file = $download[$k]['PATH_WWW'];
                            $from = self::$mediaPath . str_replace(self::$mediaHttp, '', $file);
                            $file = self::crop($file, 20);
                            $to = Dir::slash($createPath, false, true) . Compatible::App_get_file($file);

                            if (!@copy($from, $to)) {
                                console::error('copy from:' . $from . ' to:' . $to);
                            }

                        }
                    }

                }
                self::_createStruct($toPath, $child, $level + 1, $path . $name . '/');
            }
        }

    }

    /** создание структуры каталога */
    public static function createStruct()
    {
        self::$path = Dir::slash(self::$path, false, false);

        if (!Dir::exist(self::$path)) {
            mkdir(self::$path);
        }

        $json = file_get_contents(self::$catalogPath);
        $json = str_replace('var catalog2=', '', $json);

        $catalog = Compatible::Arr_from_json_ex($json);
        //$catalog = $catalog['dealers']['data'];

        //console::log($catalog[0]['child'][0],'catalog',__FILE__,__LINE__,'arr:2,deep:8,str:0');

        self::_createStruct(self::$path . self::$catalog, $catalog);

        return true;
    }

    /** архивирование */
    public static function zip()
    {
        $file = (self::$zipPath === '' ? Dir::slash(self::$path, false, true) . Dir::slash(self::$catalog, false, false) . '.zip' : self::$zipPath);

        $catalogPath = Dir::slash(self::$path, false, true);
        $files = Dir::files($catalogPath, 'xls,xlsx', false, false);

        $driver = new ZipStreamDriver();
        $zip = new Zip($driver);
        if ($zip->create($file)) {
            for ($i = 0; $i < count($files); $i++) {
                $from = $files[$i];
                $to = str_replace(self::$path, '', $from);

                $from = str_replace('//', '/', $from);
                $to = str_replace('//', '/', $to);

                $zip->add($from, $to);
            }
            $zip->close();
        } else {
            console::error('zip->create("' . $file . '")');
            return false;
        }
        return true;
    }
    /**
     * обрезка имени (папки или файла)
     */
    private static function crop($value, $maxlen = 20)
    {
        if (strlen($value) > $maxlen + 5) {
            $info = Compatible::App_pathinfo($value);
            //$value = substr($info['filename'],0,$maxlen).STR::random(5).$info['extension'];
            $value = substr($info['filename'], 0, $maxlen) . '_' . Str::random(4) . '.' . $info['extension'];
        }
        return $value;
    }

}
