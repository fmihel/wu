<?php
namespace wu\utils;

/**
 * утилиты к установке обновлений
 */
require_once __DIR__ . '/Bdr.php';
require_once __DIR__ . '/Compatible.php';

use fmihel\base\Base;
use fmihel\console;
use fmihel\lib\Dir;
use wu\utils\Bdr;
use wu\utils\Compatible;

// включает сохранение обновления
const SAVE_UPDATE_CHANGES = true;

class UPDATE_UTILS
{
    /** возвращает список файлов */
    public static function files()
    {

        $list = [];
        $q = 'select * from UPDATE_LIST order by ID desc';
        $ds = Base::ds($q, 'deco');
        if ($ds) {
            while ($row = Base::read($ds)) {
                $state = $row['CSTATE'];

                if (!file_exists(UPDATE_ZIP_PATH . $row['CFILENAME'])) {
                    $state = -1;
                }

                $list[] = [
                    'ID' => $row['ID'],
                    'CFILENAME' => $row['CFILENAME'],
                    'CDATE' => $row['CDATE'],
                    'STATE' => ($state == 1 ? "выложен" : ($state == -1 ? "отсутствует" : "не обработан")),
                    'CSTATE' => $state,
                ];

            }
        } else {
            console::error($q);
        }

        //$real = DIR::files(UPDATE_ZIP_PATH,'zip');

        return ['res' => 1, 'data' => $list];
    }

    /** возвращает список файлов больше id */
    public static function files_by_max_id($id)
    {

        $list = [];
        $q = 'select * from UPDATE_LIST where ID>' . $id . ' order by ID desc';
        $ds = Base::ds($q, 'deco');
        if ($ds) {
            while ($row = Base::read($ds)) {
                $state = $row['CSTATE'];

                if (!file_exists(UPDATE_ZIP_PATH . $row['CFILENAME'])) {
                    $state = -1;
                }

                $list[] = [
                    'ID' => $row['ID'],
                    'CFILENAME' => $row['CFILENAME'],
                    'CDATE' => $row['CDATE'],
                    'STATE' => ($state == 1 ? "выложен" : ($state == -1 ? "отсутствует" : "не обработан")),
                    'CSTATE' => $state,
                ];

            }
        } else {
            console::error($q);
        }

        //$real = DIR::files(UPDATE_ZIP_PATH,'zip');

        return ['res' => 1, 'data' => $list];
    }

    /** распаковка zip*/
    public static function unpack($file)
    {

        // распапоквывает архив во временную папку
        $path = Dir::slash(Compatible::App_rel_path(Compatible::$PATH, Compatible::$PATH . UNPACK_ZIP_PATH), false, true);

        Dir::clear($path);

        $zip = new \ZipArchive;

        try {

            if ($zip->open(UPDATE_ZIP_PATH . $file) === true) {
                $zip->extractTo($path);
                $zip->close();
            };

        } catch (\Exception $e) {
            console::error($e);
            return false;
        }

        return true;
    }

    /** возвращает информацию о содержимом архива обновления */
    public static function info($file)
    {

        $out = array('res' => 0);

        if (!self::unpack($file)) {
            console::error("unpack [$file]");
            return $out;
        }

        $_files = DIR::files(UNPACK_ZIP_PATH, 'bdr');

        $tables = [];
        for ($i = 0; $i < count($_files); $i++) {
            $bdr = $_files[$i];
            $table = Compatible::App_without_ext($bdr);
            $info = self::info_bdr($bdr);
            $tables[] = ['ID' => $i, 'TABLE' => $table, 'HAVE' => ($info['COUNT'] != 0 ? $info['COUNT'] : ''), 'INFO' => $info];
        }
        return ['res' => 1, 'tables' => $tables];

    }

    /** возвращает информацию из bdr файла */
    public static function info_bdr($file)
    {

        $bdr = new Bdr($file);
        $bdr->close();
        return ["FIELDS" => $bdr->info, "COUNT" => $bdr->count];
    }

    /** возвращает список файлов больше id */
    public static function delete_zip_file($id, $filename)
    {

        try {
            $q = 'delete from UPDATE_LIST where ID =' . $id;
            Base::query($q, 'deco');

            if (!unlink(UPDATE_ZIP_PATH . $filename)) {
                throw new \Exception("delete file " . UPDATE_ZIP_PATH . $filename);
            }

            return ['res' => 1];
        } catch (\Exception $e) {
            console::error($e);
            return ['res' => 0];
        };

    }

    public static function get_update_info($file)
    {
        //console::log("$file",__FILE__,__LINE__);

        $info = self::info($file);
        if ($info['res'] == 0) {
            return $info;
        }

        $tab = $info['tables'];

        //console::log('['.print_r($tab,true).']',__FILE__,__LINE__);

        $out = [];
        $delete_lines = false;

        for ($i = 0; $i < count($tab); $i++) {

            if ($tab[$i]['HAVE'] !== '') {

                if ($tab[$i]['TABLE'] !== 'DELETED_LINES') {
                    $out[] = ['TABLE' => $tab[$i]['TABLE'], 'COUNT' => $tab[$i]['HAVE']];
                } else {
                    $delete_lines = ['TABLE' => 'DELETED_LINES', 'COUNT' => $tab[$i]['HAVE']];
                }

            }
        }
        if ($delete_lines !== false) {
            array_unshift($out, $delete_lines);
        }

        return ['res' => 1, 'data' => $out];

    }

    private static function decode_bin($mean)
    {
        $out = '';

        for ($i = 0; $i < ((strlen($mean) - 3) / 2); $i++) {

            $hex = $mean[$i * 2 + 3] . $mean[$i * 2 + 3 + 1];
            $out .= chr(hexdec($hex));

        };
        return $out;
    }

    public static function mean_by_type($mean, $type)
    {
        if ($type === 'STRING') {
            //$mean = mb_convert_encoding($mean,'CP1251','ASCII');
            $mean = str_replace('[<CR>]', chr(13) . chr(10), $mean);
            $out = Base::real_escape($mean, 'deco');
            return "'$out'";
        }

        if ($type === 'BIN.HEX') {
            return '"' . (Base::real_escape(self::decode_bin($mean), 'deco')) . '"';
        }

        if ($type === 'BIN') {
            return self::decode_bin($mean);
        }

        if (($type === 'R8') || ($type === 'I4')) {
            if ($mean === '') {
                $mean = 0;
            }

            return str_replace(',', '.', $mean);
        }

        if ($type === 'DATETIME') {
            return "'" . $mean . "'";
        }

    }

    /** обработка шага удаления (в архив) строк из таблиц */
    public static function deleted_lines($index, $count_recs)
    {
        $res = 1;
        $msg = '';
        //----------------------------------------------------------------------
        global $DELETED_TABLES;

        //----------------------------------------------------------------------
        $bdr = new Bdr('DELETED_LINES.bdr');
        //----------------------------------------------------------------------
        $TABLES_WEB = Base::tables('deco');
        //----------------------------------------------------------------------
        // Поиск нужной записи
        if (!$bdr->moveTo($index)) {
            $bdr->close();
            return array('res' => 0, 'msg' => 'index is overflow');
        };
        //----------------------------------------------------------------------
        while ($count_recs > 0) {

            $str = $bdr->gets();
            while (strpos($str, '[</ROW>]') === false) {

                $field = trim(substr($str, strlen('[<FIELD>]'), strlen($str)));
                $str = $bdr->gets();
                $data = trim(substr($str, strlen('[<DATA>]'), strlen($str)));
                $str = $bdr->gets();

                $IDS = [];
                $IDS[$field] = $data;

                $field = trim(substr($str, strlen('[<FIELD>]'), strlen($str)));
                $str = $bdr->gets();
                $data = trim(substr($str, strlen('[<DATA>]'), strlen($str)));
                $str = $bdr->gets();

                $TABLE = $data;

                $field = trim(substr($str, strlen('[<FIELD>]'), strlen($str)));
                $str = $bdr->gets();
                $id = trim(substr($str, strlen('[<DATA>]'), strlen($str)));
                $str = $bdr->gets();

                $ID_FIELD = $id;

                $field = trim(substr($str, strlen('[<FIELD>]'), strlen($str)));
                $str = $bdr->gets();
                $val = trim(substr($str, strlen('[<DATA>]'), strlen($str)));
                $str = $bdr->gets();

                $ID = $val;

                $field = trim(substr($str, strlen('[<FIELD>]'), strlen($str)));
                $str = $bdr->gets();
                $val = trim(substr($str, strlen('[<DATA>]'), strlen($str)));
                $str = $bdr->gets();

                $DATE = $val;
            };

            if (array_search($TABLE, $TABLES_WEB) !== false) {

                if (array_search($TABLE, $DELETED_TABLES) !== false) {
                    $q = "delete from `$TABLE` where $ID_FIELD=$ID";
                } else {
                    $q = "update $TABLE set ARCH=1 where $ID_FIELD=$ID";
                }

                if ($TABLE === 'C_MEDIA_FILE') {
                    self::clearMediaFile($ID);
                }

                if (SAVE_UPDATE_CHANGES) {
                    try {
                        Base::query($q, 'deco');
                    } catch (\Exception $e) {
                        console::error($e);
                    };
                } else {
                    if (rand(1, 10) === 2) {
                        $res = 0;
                        $msg .= $q . "<br>";
                    }
                };

            };

            $count_recs--;

            $str = $bdr->gets();
            if (strpos($str, '[</ROWDATA>]') === 0) {
                break;
            }

        };

        $bdr->close();
        return ['res' => $res, 'msg' => $msg];
    }
    /**
     * осуществляет поиск файла в папке media и удаляет его
     * путь берется из C_MEDIA_FILE.PATH_WWW
     * @param {int} $ID_C_MEDIA_FILE
     * @return {boolean}
     */
    public static function clearMediaFile($ID_C_MEDIA_FILE)
    {

        try {

            $q = 'select `PATH_WWW` from C_MEDIA_FILE where ID_C_MEDIA_FILE = ' . $ID_C_MEDIA_FILE;

            $file = Base::value($q, 'deco', ['default' => '']);

            if ($file != '') {

                if (self::haveInBlanks($file)) {
                    throw new \Exception("not delete [$file] C_MEDIA_FILE.ID_C_MEDIA_FILE === $ID_C_MEDIA_FILE, file use in order blank !");
                }

                // выстраиваем относительный путь
                $path = Compatible::App_get_path($file);
                $path = Dir::slash(Compatible::$ROOT, false, true) . 'media' . Dir::slash($path, true, false);
                $path = Compatible::App_rel_path(Compatible::$PATH, $path);
                $file = Dir::slash($path, false, true) . Compatible::App_get_file($file);

                if (file_exists($file)) {
                    if (!unlink($file)) {
                        throw new \Exception("can`t delete [$file] C_MEDIA_FILE.ID_C_MEDIA_FILE === $ID_C_MEDIA_FILE");
                    }
                }

            }
            return true;

        } catch (\Exception $e) {
            console::error($e);
        }

        return false;
    }

    /** шаг обновления */
    public static function update_step($table, $index, $count_recs = 1)
    {
        //----------------------------------------------------------------------
        $bdr = new Bdr($table . '.bdr');
        $errors = [];
        $space = '{%$space%}';
        //----------------------------------------------------------------------
        // масив существующих полей
        $FIELDS_WEB = Base::fieldsInfo($bdr->table, true, 'deco');
        //----------------------------------------------------------------------
        // Поиск нужной записи
        if (!$bdr->moveTo($index)) {
            $bdr->close();
            return array('res' => 0, 'msg' => 'index is overflow');
        };
        //----------------------------------------------------------------------
        $res = 1;
        $msg = '';
        while ($count_recs > 0) {

            $VALUES = [];
            $str = $bdr->gets();
            //-------------------------------------------------------------------------------------
            /* считываем данные и пишем их в $VALUES = array('fieldName'=>value,...) */
            while (strpos($str, '[</ROW>]') === false) {
                $field = trim(substr($str, strlen('[<FIELD>]'), strlen($str)));
                $str = $bdr->gets();
                //$value = trim(substr($str,strlen('[<DATA>]'),strlen($str)));

                $value = substr($str, strlen('[<DATA>]'), strlen($str));
                $value = Compatible::Str_replace_loop(' ', $space, $value);
                $value = trim($value);
                $value = Compatible::Str_replace_loop($space, ' ', $value);

                //$field = mb_convert_encoding($field,'UTF-8','ASCII');
                $VALUES[$field] = $value;
                $str = $bdr->gets();
            };
            //-------------------------------------------------------------------------------------
            $ID = $VALUES[$bdr->id];
            //-------------------------------------------------------------------------------------
            // проверка существования записи
            $q = 'select count(' . $bdr->id . ')>0 HAVE from ' . $bdr->table . ' where ' . $bdr->id . '=' . $ID;
            //console::log("$q",__FILE__,__LINE__);

            //-------------------------------------------------------------------------------------

            if (Base::value($q, 'deco', ['default' => 0]) > 0) { // если существует, то формируем запрос на обновление

                $q = 'update ' . $bdr->table . ' set ';
                $body = '';

                for ($k = 0; $k < count($bdr->info); $k++) {

                    $name = $bdr->info[$k]['NAME'];

                    if (($name !== $bdr->id) && (isset($VALUES[$name])) && (in_array($name, $FIELDS_WEB))) {

                        $mean = self::mean_by_type($VALUES[$name], $bdr->info[$k]['TYPE']);

                        $body .= ($body !== '' ? ',' : '') . '`' . $name . '`=' . $mean;
                    };
                };
                $q .= $body . ' where ' . $bdr->id . '=' . $ID;

            } else {

                $q = 'insert into ' . $bdr->table . ' ';
                $fld = '';
                $body = '';
                for ($k = 0; $k < count($bdr->info); $k++) {

                    $name = $bdr->info[$k]['NAME'];
                    if ((isset($VALUES[$name])) && (in_array($name, $FIELDS_WEB))) {

                        if ($fld !== '') {
                            $fld .= ',';
                        }

                        $fld .= '`' . $name . '`';

                        $mean = self::mean_by_type($VALUES[$name], $bdr->info[$k]['TYPE']);
                        if ($body !== '') {
                            $body .= ',';
                        }

                        $body .= $mean;
                    };
                };

                $q .= '(' . $fld . ') values (' . $body . ')';
            };

            if (SAVE_UPDATE_CHANGES) {

                try {
                    Base::query($q, 'deco');
                } catch (\Exception $e) {
                    $q = mb_convert_encoding($q, 'utf-8', 'cp1251');
                    console::error($e);
                    console::error("[" . substr($q, 0, 100) . "..]");
                    $res = 0;
                    $msg .= $q . "<br>";
                };

            } else {
                // для отладки имитируем отказ
                //if (rand(1,10) === 2){
                $res = 0;
                $q = mb_convert_encoding($q, 'utf-8', 'cp1251');
                $msg .= substr($q, 0, 200) . "<br>";
                console::error("[" . substr($q, 0, 100) . "..]");

                //};

            }

            $count_recs--;
            $str = $bdr->gets();

            if (strpos($str, '[</ROWDATA>]') === 0) {
                break;
            }

        };

        $bdr->close();
        return array('res' => $res, 'msg' => $msg);

    }

    /**
     * получение строк с данными, для вывода в MOD_UPDATE
     */
    public static function bdrRow($file, $index, $count_recs = 1, $crop = 0)
    {
        //----------------------------------------------------------------------
        $bdr = new Bdr($file);
        //----------------------------------------------------------------------
        //----------------------------------------------------------------------
        // Поиск нужной записи
        if (!$bdr->moveTo($index)) {
            $bdr->close();
            return ['res' => 0, 'msg' => 'index is overflow'];
        };
        //----------------------------------------------------------------------
        // считываем данные
        $data = [];
        while ($count_recs > 0) {

            $VALUES = [];
            $str = $bdr->gets();

            while (strpos($str, '[</ROW>]') === false) {

                $field = trim(substr($str, strlen('[<FIELD>]'), strlen($str)));
                $str = $bdr->gets();
                $value = trim(substr($str, strlen('[<DATA>]'), strlen($str)));

                $VALUES[$field] = mb_convert_encoding($value, 'UTF-8', 'CP1251');
                if ($crop > 0) {
                    if (mb_strlen($VALUES[$field], 'UTF8') > $crop) {
                        $VALUES[$field] = mb_substr($VALUES[$field], 0, $crop) . ' ..';
                    }
                }

                $str = $bdr->gets();
            };

            $data[] = $VALUES;
            $count_recs--;
            $str = $bdr->gets();

            if (strpos($str, '[</ROWDATA>]') === 0) {
                break;
            }

        };
        //----------------------------------------------------------------------
        $bdr->close();
        //----------------------------------------------------------------------
        return ['res' => 1, 'indexField' => $bdr->id, 'fields' => $bdr->fields, 'data' => $data];

    }

    public static function media($index, $count_recs = 1, $fileName = 'C_MEDIA_FILE')
    {
        $res = 1;
        //----------------------------------------------------------------------
        global $TABLE_INDEX;
        $TABLE = 'C_MEDIA_FILE';
        $FILENAME = 'PATH_WWW';
        $INDEX_NAME = $TABLE_INDEX[$TABLE];
        $FIELDS_WEB = Base::fieldsInfo($TABLE, true, 'deco');

        //----------------------------------------------------------------------
        $bdr = new Bdr($fileName . '.bdr');
        //----------------------------------------------------------------------

        // Поиск нужной записи
        if (!$bdr->moveTo($index)) {
            $bdr->close();
            return array('res' => 0, 'msg' => 'index is overflow');
        };
        //----------------------------------------------------------------------

        while ($count_recs > 0) {

            $VALUES = [];
            $str = $bdr->gets();
            //-------------------------------------------------------------------------------------
            /* считываем данные и пишем их в $VALUES = array('fieldName'=>value,...) */

            while (strpos($str, '[</ROW>]') === false) {

                $field = trim(substr($str, strlen('[<FIELD>]'), strlen($str)));
                $str = $bdr->gets();
                $value = trim(substr($str, strlen('[<DATA>]'), strlen($str)));

                $VALUES[$field] = $value;
                $str = $bdr->gets();
            };

            //-------------------------------------------------------------------------------------
            //$ID = $VALUES[$INDEX_NAME];
            //$FILE_NAME = $VALUES['FILE_NAME'];
            // -----------------------------------------------------------------------------------
            // обновляем таблицу
            $insert = '';
            $update = '';
            $fld = '';
            $file = false;
            $path = '';

            for ($k = 0; $k < count($bdr->info); $k++) {

                $name = $bdr->info[$k]['NAME'];

                if (in_array($name, $FIELDS_WEB)) {
                    if (isset($VALUES[$name])) {

                        $mean = $VALUES[$name];

                        if ($name === $FILENAME) {
                            if (trim($mean) !== '') {
                                $file = BIN_STORY_PATH . Dir::slash(str_replace("\\", '/', $mean), false, false);
                                $ext = Compatible::App_ext($file);
                                $path = Compatible::App_get_path($file);
                                $file = $path . Compatible::App_without_ext($file) . '_' . $VALUES[$INDEX_NAME] . '.' . $ext;
                                $mean = str_replace(BIN_STORY_PATH, '', $file);
                            }
                        };

                        $mean = self::mean_by_type($mean, $bdr->info[$k]['TYPE']);
                        // формируем тело update
                        if ($name !== $bdr->id) {
                            $update .= ($update !== '' ? ',' : '') . '`' . $name . '`=' . $mean;
                        }

                        // формируем тело insert
                        if ($fld !== '') {
                            $fld .= ',';
                        }

                        $fld .= '`' . $name . '`';
                        if ($insert !== '') {
                            $insert .= ',';
                        }

                        $insert .= $mean;

                    };
                };

            }; //for

            // -----------------------------------------------------------------------------------
            $q = 'insert into ' . $TABLE . ' (' . $fld . ') values (' . $insert . ') on duplicate key update ' . $update;
            Base::query($q, 'deco');
            // -----------------------------------------------------------------------------------

            if ($file) {
                if (($path !== '') && (!file_exists($path)) && (!mkdir($path, 0777, true))) {
                    console::error('create path [' . $path . ']');
                    $res = 0;
                } else {
                    if (!self::saveBinToFile($VALUES['CONTENT'], $file)) {
                        console::error($file . ' story ');
                        $res = 0;
                    }
                }
            }

            $count_recs--;
            $str = $bdr->gets();

            if (strpos($str, '[</ROWDATA>]') === 0) {
                break;
            }
        };
        $bdr->close();

        return ['res' => $res];
    }
    /** сохраним бинарные данные из bdr в файл
     * ВНИМАНИЕ! Функция не проверялась
     */
    public static function saveBinToFile($bin, $filename)
    {
        $data = self::mean_by_type($bin, 'BIN');

        if (file_put_contents($filename, $data) !== false) {
            return true;
        }

        return false;
    }

    /** проверка использования рисунка в бланках заказов */
    private static function haveInBlanks($file): bool
    {
        // проверка наличия фала в уже сформированных, но не удаленных заказах
        $q = "SELECT 1 `exists`
                FROM `B_ORDERS` bo join `ORDERS` o on bo.ID_ORDER = o.ID_ORDER
                WHERE o.DELETED<>1 and bo.JSON_DATA LIKE '%$file%'
                LIMIT 1
        ";

        $exists = (Base::value($q, 'deco', ['default' => '']) == 1) ? true : false;

        if (!$exists) {
            // проверка наличия файла в шаблоне бланке
            $q = "SELECT 1 `exists` FROM `B_BLANKS` WHERE JSON_DATA LIKE '%$file%' LIMIT 1";
            $exists = (Base::value($q, 'deco', ['default' => '']) == 1) ? true : false;
        }

        return $exists;

    }

};
