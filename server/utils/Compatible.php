<?php
namespace wu\utils;

use fmihel\lib\Dir;
use fmihel\lib\Type;

const COMMON_TYPE_STRING = "[@str13]:";
const COMMON_TYPE_STRING_LEN = "9";
const UC_FROM = ['\\'];
const UC_TO = ["\u005C"];

class Compatible
{
    public static $PATH = '';
    public static $ROOT = '';

    static function Str_replace_loop($search, $replace, $text)
    {
        // заменяет $search на $replace пока они существуют в $text
        $loop = 1000000;
        while (1 > 0) {
            $loop--;
            if ($loop < 0) {
                $msg = 'LOOP in STR::replace_loop ' . __FILE__;
                error_log($msg);
                echo $msg;
                exit;
            };

            $need = false;
            if (is_array($search)) {
                for ($i = 0; $i < count($search); $i++) {
                    $need = (mb_strpos($text, $search[$i]) !== false);
                    if ($need) {
                        break;
                    }

                };

            } else {
                $need = (mb_strpos($text, $search) !== false);
            }

            if ($need) {
                $text = str_replace($search, $replace, $text);
            } else {
                return $text;
            }

        }

    }
    static function App_pathinfo($file)
    {
        $out = array('file' => $file, 'dirname' => '', 'basename' => '', 'extension' => '', 'filename' => '');
        $slash = '/';
        //------------------------------------------------
        $have_oslash = (mb_strpos($file, '\\') !== false);
        if ($have_oslash) {
            $file = str_replace('\\', $slash, $file);
        }

        //------------------------------------------------

        $lim = mb_strrpos($file, $slash);
        if ($lim !== false) {
            $left = mb_substr($file, 0, $lim);
            $right = mb_substr($file, $lim + 1);

            $out['dirname'] = $left;
            $out['basename'] = $right;
            $out['filename'] = $right;

            $pos_ext = mb_strrpos($right, '.');
            if ($pos_ext !== false) {
                $out['extension'] = mb_substr($right, $pos_ext + 1);
                $out['filename'] = mb_substr($right, 0, $pos_ext);
            }

        } else {
            $out['basename'] = $file;
            $out['filename'] = $file;

            $pos_ext = mb_strrpos($file, '.');
            if ($pos_ext !== false) {
                $out['extension'] = mb_substr($file, $pos_ext + 1);
                $out['filename'] = mb_substr($file, 0, $pos_ext);
            };

        }

        //------------------------------------------------
        if ($have_oslash) {
            foreach ($out as $k => $v) {
                $out[$k] = str_replace($slash, '\\', $v);
            }
        }

        //------------------------------------------------

        return $out;
    }

    static function App_get_file($file)
    {
        //S: возвращает имя файла
        $path = self::App_pathinfo($file);
        return $path['filename'] . '.' . $path['extension'];
    }
    static function App_without_ext($file)
    {
        //S: возвращает имя файла без расширения
        $path = self::App_pathinfo($file);
        return $path['filename'];
    }
    public static function Type_is_assoc($arr)
    {
        //S: Проверка на то, что массив ассоциативный
        //R: TRUE если массив $array ассоциативный
        $a1 = is_array($arr);
        if ($a1) {
            $a2 = array_keys($arr);
            $a2 = is_numeric(array_shift($a2));
            return ($a1 && !$a2);
        }
        return false;
    }

    public static function Common_pre_json($str)
    {
        //S:Подготавливает utf8 строку для парсинга json
        //R: string
        return str_replace(UC_FROM, UC_TO, $str);
    }
    /**
     * расширенный вариант для парсинга json в строке
     * json может содержать комментарии /* //
     * в значениях строк могут присутствовать пробелы и переносы
     * максимально приближен к ECMAScript 5
     *
     * ВНИМАНИЕ! При сильном увеличении размера парсируемого текста может на порядок работать медленнее чем from_json
     *
     * Ex:
     * from_json_ex('a:10');
     * >>  array('a'=>'10')
     *
     * from_json_ex('{
     *     a:10
     * }');
     * >>  array('a'=>'10')
     *
     * from_json_ex('{
     *     a:10
     * }');
     *
     *
     */
    public static function Arr_from_json_ex($str)
    {
        $s = '';
        $i = 0;
        // на всякий случай ели нет, то обернем в фигурные скобки
        $str = ltrim($str);
        if ($str === '') {
            $str = '{}';
        }

        if (($str[0] !== '{') && ($str[0] !== '[')) {
            $str = '{' . $str . '}';
        }

        $count = strlen($str);

        $root = []; // верхний уровень создаваемого массива
        $current =  &$root; // текущий заполняемый уровень
        $parent = null; // родительский уровень
        $buffer = []; // дерево уровней и параметров к ним

        $key = false; // текущий ключь (имя переменной)
        $val = false; // текущее значение переменной
        $in = ''; // текущий сформированныйотрезок строки от последнего служебного символа до текущей позиции в парсируемой строке
        $needVal = true; // признак, что значение еще не вводилось (нужно для случаев, когда встречается замыкающая скобка } или ] )
        $string = ''; // формруемое значение найденной стоки (значение заключенное в ковычки )
        $isString = false; // признакк того, что данные ля заполнения значением нужно брать из $string, а не из $in
        $bstring = false; // признак того, что текущая позиция в строке находится внутри строкового значения (значение заключенное в ковычки ) и => служебные символы игнорируются до момента пока строка не будет закрыта
        $kov = '';
        $comment = false;

        $object = $str[0];

        for ($i = 0; $i < $count; $i++) {

            $s = $str[$i];

            if (($bstring === false) && (($s === '{') || ($s === '['))) { // найдено начало блока

                //_LOGF($object,'object',__FILE__,__LINE__);

                // сохраняем текущее состояние в буффере
                $buffer[] = array(
                    'current' => &$current,
                    'pos' => $i + 1,
                    'object' => $object,
                );
                $object = $s;

                $parent = $buffer[count($buffer) - 1];

                // создаем новый дочерний элемент
                if ($key !== false) {

                    $current[$key] = [];
                    $current =  &$current[$key];

                } else {
                    $current[] = [];
                    $current =  &$current[count($current) - 1];
                }

                $in = '';
                $key = false;
                $val = false;

            } elseif (($bstring === false) && (($s === '}') || ($s === ']'))) { // найден конец блока

                // если есть не введенные элементы, введем их
                $val = ($isString ? $string : trim($in));
                // дополнительное услови, на необходимость вставки последнего значения (проверяем, что значение либо явно указано как строка, либо была задана в ковычках)
                $needVal = ($needVal) && (($key !== false) || (($isString) || ($val !== '')));

                if (($val !== false) && ($needVal)) {

                    if ($key !== false) {
                        $current[$key] = $val;
                    } elseif ($object === '{') {
                        $current[$val] = "";
                    } else {
                        $current[] = $val;
                    }

                }

                // откатим состояние на уровень выше
                $p =  &$buffer[count($buffer) - 1];
                $current =  &$p['current'];
                $object = $p['object'];

                array_pop($buffer);

                $in = '';
                $key = false;
                $val = false;
                $needVal = false;
                $isString = false;

            } else {

                if ($bstring === false) {

                    if ($s === ':') {

                        $key = trim($isString ? $string : $in);
                        $in = '';
                        $string = '';
                        $isString = false;

                    } elseif ($s === ',') {

                        if ($needVal) {

                            $val = ($isString ? $string : trim($in));
                            if ($key !== false) {
                                $current[$key] = $val;
                            } elseif ($object === '{') {
                                $current[$val] = "";
                            } else {
                                $current[] = $val;
                            }

                        }

                        $in = '';
                        $string = '';
                        $needVal = true;
                        $key = false;
                        $isString = false;

                    } elseif (($s === '"') || ($s === "'")) {

                        $string = '';
                        $in = '';
                        $bstring = true;
                        $isString = true;
                        $kov = $s;

                    } elseif (($s === '/') && ($i < $count - 1)) {
                        $ss = $str[$i + 1];

                        if (($ss === '*') || ($ss === '/')) {
                            $bstring = true;
                            $comment = $ss;
                            $i++;
                        }

                    } else {
                        $in .= $s;
                    }

                } else {

                    if ($comment) { // обработка замыкающего тега для комментария
                        if (
                            (($comment === '*') && ($s === '*') && ($i < $count - 1) && ($str[$i + 1] === '/'))
                            ||
                            (($comment === '/') && (ord($s) === 10))
                        ) {
                            if ($comment === '*') {
                                $i++;
                            }

                            $bstring = false;
                            $comment = false;

                        }
                    } else {

                        // обработка замыкающей ковычки

                        if ($s !== $kov) {
                            $string .= $s;
                        } else {
                            $bstring = false;
                        }

                    }

                }

            }

        }
        return $root[0];
    }
    public static function Arr_to_json($arr, $refactoring = false, $level = 0, $param = [])
    {
        //SHORT:Преобразует PHP массив в строку, которую можно парсить JSON
        /*DOC: Преобразует PHP массив в строку, для возможности парсить ее JSON на стороне клиента. При этом строки будут кодироваться посредством JUTILS::JSON_CODE.
        Для правильной раскодировки используйте ф-цию javascript [code]JUTILS.JSON_DECODE(str)[/code] Так же bool значения, после парсинга,правильней проверять с помощью ф-ции [code]JUTILS.AsBool(mean)[/code]
         */
        $left = '';
        $cr = '';
        if ($refactoring) {
            $param = array_merge([
                'left' => '    ',
                'cr' => chr(13) . chr(10),
            ], $param);

            $left = str_repeat($param['left'], $level);
            $cr = $param['cr'];
        };

        if (self::Type_is_assoc($arr)) {
            $res = '{';
            foreach ($arr as $Name => $Value) {
                if ($res !== '{') {
                    $res .= ',';
                }

                $res .= $cr . $left;

                if (is_array($Value)) {
                    $res .= '"' . $Name . '":' . self::Arr_to_json($Value, $refactoring, $level + 1, $param) . '';
                } else {
                    if (is_bool($Value)) {
                        if ($Value) {
                            $res .= '"' . $Name . '":true';
                        } else {
                            $res .= '"' . $Name . '":false';
                        }

                    } else {
                        if (Type::is_numeric($Value, true)) {
                            $res .= '"' . $Name . '":' . $Value;
                        } else {

                            $strpos = mb_strpos($Value, COMMON_TYPE_STRING);
                            if ($strpos === 0) {
                                $Value = substr($Value, COMMON_TYPE_STRING_LEN);
                            }

                            $res .= '"' . $Name . '":"' . self::Common_pre_json($Value) . '"';

                        }
                    };
                }
            };
            $res .= '}';
        } else {

            $res = '[';
            if (is_array($arr)) {
                for ($i = 0; $i < count($arr); $i++) {
                    if ($res !== '[') {
                        $res .= ',';
                    }

                    $res .= $cr . $left;

                    if (is_array($arr[$i])) {
                        $res .= self::Arr_to_json($arr[$i], $refactoring, $level + 1, $param);
                    } else {
                        if (is_bool($arr[$i])) {
                            $res .= ($arr[$i] ? 'true' : 'false');
                        } else if (Type::is_numeric($arr[$i], true)) {
                            $res .= $arr[$i];
                        } else {
                            $res .= '"' . self::Common_pre_json($arr[$i]) . '"';
                        }

                    }
                };
            } else {
                $res .= '"' . self::Common_pre_json($arr) . '"';
            }
            $res .= ']';
        }
        return $res;

    }
    static function App_rel_path($from, $to, $slash = DIRECTORY_SEPARATOR)
    {
        //S: Получение относительного пути
        //from = '/home/decoinf3/public_html/test/myproject/';
        //to = '/home/decoinf3/public_html/rest/a
        //result ../../rest/a
        $ps = $slash;
        $arFrom = explode($ps, rtrim($from, $ps));
        $arTo = explode($ps, rtrim($to, $ps));
        while (count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0])) {
            array_shift($arFrom);
            array_shift($arTo);
        }
        return str_pad("", count($arFrom) * 3, '..' . $ps) . implode($ps, $arTo);
    }

    static function App_get_path($file)
    {
        //S: Выделяет путь файла
        $path = self::App_pathinfo($file);
        $out = $path['dirname'];
        if ($out == '.') {
            $out = '';
        }
        return Dir::slash($out, false, true);
    }
    static function App_ext($file)
    {
        //S: получите расширение файла
        $path = self::App_pathinfo($file);
        return $path['extension'];
    }

    public static function Arr_from_json($json)
    {

        //---------------------------------------------------
        // ВНИМАНИЕ!  значения свойств массивов не должны содержать { " ' , и пробелы
        //  (все это экранируем)
        //---------------------------------------------------

        //---------------------------------------------------
        /*убрали все пробелы*/
        //---------------------------------------------------
        $json = str_replace("\n", '', $json);
        while (mb_strpos($json, ' ') !== false) {
            $json = str_replace(' ', '', $json);
        }

        //---------------------------------------------------
        /*убрали все ковычки*/
        //---------------------------------------------------
        $json = str_replace(array('"', "'"), '', $json);

        $json = self::Str_replace_last(';', '', $json);

        //---------------------------------------------------
        /* добавили ковычки для выполнения стандарта JSON*/
        //---------------------------------------------------
        //$json = preg_replace('/[[:word:]\#\.]+/','"\\0"',$json);
        //echo '<xmp>'.$json.'</xmp>';
        $json = preg_replace('/[^\{\}\:\,\[\]]+/', '"\\0"', $json);

        //echo ''.$json.'';
        //exit;
        //---------------------------------------------------
        //---------------------------------------------------

        $res = json_decode($json, true);
        if (is_array($res)) {
            self::Common_json_id($res, $STR);
        } else {
            $res = [];
        }

        return $res;

    }
    private static function Common_json_id(&$json, &$STR)
    {
        foreach ($json as $k => $v) {
            if (is_array($v)) {
                self::Common_json_id($v, $STR);
                $json[$k] = $v;
            } else {
                if (isset($STR[$v])) {
                    $json[$k] = str_replace('"', '', $STR[$v]);
                }
            }
        }
    }
    static function Str_replace_last($search, $replace, $text)
    {
        /*перезаписывает $search если он находится в конце строки*/

        $_text = trim($text);
        $pos = mb_strrpos($_text, $search);

        if (($pos !== false) && (($pos + mb_strlen($search)) == mb_strlen($_text))) {
            return mb_substr($_text, 0, $pos) . $replace;
        } else {
            return $text;
        }

    }

}

Compatible::$PATH = Dir::slash(dirname($_SERVER['SCRIPT_FILENAME']), false, true);
Compatible::$ROOT = Dir::slash($_SERVER['DOCUMENT_ROOT'], false, true);
