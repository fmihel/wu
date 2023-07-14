<?php
namespace wu\utils;

use fmihel\lib\Dir;

require_once __DIR__ . '/Compatible.php';

class Bdr
{
    private $file;
    public $info;
    public $fields;
    public $id;
    public $xml;
    public $count;
    public $table;

    public function __construct($fileName)
    {
        $this->file = null;
        $this->info = array();
        $this->fields = array();
        $this->id = '';
        $this->xml = null;
        $this->count = 0;
        $this->open($fileName);
    }

    public function open($fileName)
    {
        global $Application;
        global $TABLE_INDEX;
        //----------------------------------------------------------------------
        $this->table = Compatible::App_without_ext($fileName);
        //----------------------------------------------------------------------
        $file = Dir::slash(UNPACK_ZIP_PATH, false, true) . $fileName;
        //----------------------------------------------------------------------

        $this->file = @fopen($file, "r");
        //----------------------------------------------------------------------
        $str = fgets($this->file) . fgets($this->file) . fgets($this->file);
        $this->xml = new \SimpleXMLElement($str);
        //----------------------------------------------------------------------
        foreach ($this->xml->METADATA->FIELDS->FIELD as $field) {

            $name = isset($field['attrname']) ? trim($field['attrname']) : '';
            if ($name !== '') {
                $type = isset($field['fieldtype']) ? trim(strtoupper($field['fieldtype'])) : 'STRING';
                $width = isset($field['WIDTH']) ? $field['WIDTH'] : '0';

                $this->info[] =
                    [
                    'NAME' => $name,
                    'TYPE' => $type,
                    'WIDTH' => $width,
                ];

                $this->fields[] = ['name' => $name];
            }
        };

        $this->id = $TABLE_INDEX[$this->table];
        $this->count = $this->xml->COUNT;

    }

    public function close()
    {

        if (!is_null($this->file)) {
            fclose($this->file);
        }

        $this->file = null;
    }

    public function gets()
    {
        return fgets($this->file);
    }

    public function moveTo($index)
    {
        $str = '';
        while (!$this->eof()) {
            $str = $this->gets();

            if (strpos($str, '[<ROW>]') === 0) {
                $index--;
                if ($index < 0) {
                    break;
                }

            }
        };

        return ($index < 0);

    }

    public function eof()
    {
        return feof($this->file);
    }

    public function debug_info($cr = "\n")
    {
        $out = '';
        $out .= '[table] = ' . $this->table . $cr;
        $out .= '[id] = ' . $this->id . $cr;
        $out .= '[fields] = ' . print_r($this->fields, true) . $cr;
        $out .= '[count] = ' . $this->count . $cr;
        //$out.='[xml] = '.print_r($this->xml,true).$cr;

        return $out;
    }
};
