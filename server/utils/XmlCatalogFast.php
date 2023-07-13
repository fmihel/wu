<?php
namespace wu\utils;

class XmlCatalogFast
{
    static $XML_CATALOG_STR = '';

    private static function getParams($str, $tag)
    {
        // GetParams - Получение параметров из тега XML
        $res = array();

        // выделяем строку параметров
        $str = str_replace(chr(13) . chr(10), '', $str);

        // /^<msg]*<msg([^<]*)>(.*)/'
        $templ = '/[^<' . $tag . ']*<' . $tag . '([^<]*)>(.*)/';

        if (preg_match($templ, $str, $match)) {
            // выделяем параметры и значения в хеш
            $params = $match[1];
            $templ = '/[ ]*([^=\"]*)[ ]*=[ ]*"([^=\"]*)"/';
            if (preg_match_all($templ, $params, $match)) {
                $len = count($match[0]);
                for ($i = 0; $i < $len; $i++) {
                    $mean = $match[1][$i];
                    $value = $match[2][$i];
                    $res[$mean] = $value;
                };
            };
        };
        return $res;
    }
    /**
     * предварительная загрузка и обрезка текста XML саталога
     */
    public static function load($xmlFileName, $catalog_id)
    {
        $str = file_get_contents($xmlFileName);

        $begin = '<catalog id="' . $catalog_id . '"';
        $end = '</catalog>';

        $pos = strpos($str, $begin);

        if ($pos !== false) {
            $str = substr($str, $pos);

            $pos = mb_strpos($str, $end);
            if ($pos !== false) {
                $str = substr($str, 0, $pos + mb_strlen($end));
            }

        }

        self::$XML_CATALOG_STR = $str;

    }
    /**
     * поиск дополнительных узлов в xml каталоге по id и is_chapter:numer
     */
    public static function find($id, $is_chapter)
    {

        $str = self::$XML_CATALOG_STR;
        $off = 0;
        $res = array();
        for ($loop = 0; $loop < 100; $loop++) {

            $matches = null;
            if (preg_match('/<node.*is_chapter="' . $is_chapter . '"\\sid="' . $id . '"/', $str, $matches, PREG_OFFSET_CAPTURE, $off) === 1) {

                $node = $matches[0][0] . '/>';
                $prm = self::getParams($node, 'node');

                if ($prm['file'] !== '') {

                    $cbool = true;
                    for ($j = 0; $j < count($res); $j++) {

                        if ($prm['file'] === $res[$j]['FILE']) {
                            $cbool = false;
                            break;
                        };

                    };

                    if ($cbool) {
                        $res[] = $prm;
                    }
                    //;$prm = array(name:string,file:string)
                }

                $off = $matches[0][1] + 1;
            } else {
                break;
            }

        };

        return $res;
    }

}
