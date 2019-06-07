<?php
/**
 * утилиты к установке обновлений
*/

// включает сохранение обновления
define('SAVE_UPDATE_CHANGES',true);

class Bdr{
    private $file;
    public $info;
    public $fields;
    public $id;
    public $xml;
    public $count;
    public $table;
    
    function __construct($fileName){
        $this->file = NULL;
        $this->info = array();
        $this->fields = array();
        $this->id = '';
        $this->xml = NULL;
        $this->count = 0;
        $this->open($fileName);
    }
    
    public function open($fileName){
        global $Application;
        global $TABLE_INDEX;
        //----------------------------------------------------------------------
        $this->table = APP::without_ext($fileName);
        //----------------------------------------------------------------------
        $file = APP::slash(UNPACK_ZIP_PATH,false,true).$fileName;
        //----------------------------------------------------------------------

        $this->file = @fopen($file, "r");
        //----------------------------------------------------------------------
        $str = fgets($this->file).fgets($this->file).fgets($this->file);
        $this->xml = new SimpleXMLElement($str);
        //----------------------------------------------------------------------
        foreach($this->xml->METADATA->FIELDS->FIELD as $field){
            
            $name   = isset($field['attrname'])?trim($field['attrname']):'';
            if ($name!==''){
                $type   = isset($field['fieldtype'])?trim(strtoupper($field['fieldtype'])):'STRING';
                $width = isset($field['WIDTH'])?$field['WIDTH']:'0';

                array_push($this->info,
                    array(
                        'NAME'=>$name,
                        'TYPE'=>$type,
                        'WIDTH'=>$width
                ));
            
                array_push($this->fields,array(
                        'name'=>$name
                ));
            }    
        };
        

        $this->id = $TABLE_INDEX[$this->table];
        $this->count = $this->xml->COUNT;
        
        
    
        
    }
    
    public function close(){
        
        if (!is_null($this->file))
            fclose($this->file);
        $this->file = NULL;    
    }
    
    public function gets(){
        return fgets($this->file);
    }
    
    public function moveTo($index){
        $str = '';
        while(!$this->eof()){
            $str = $this->gets();

            if(strpos($str,'[<ROW>]')===0){
                $index--;
                if($index<0)
                    break;
            }
        };
        
        return ($index<0);

    }
    
    public function eof(){
        return feof($this->file);
    }
    
    public function debug_info($cr="\n"){
        $out = '';
        $out.='[table] = '.$this->table.$cr;
        $out.='[id] = '.$this->id.$cr;        
        $out.='[fields] = '.print_r($this->fields,true).$cr;
        $out.='[count] = '.$this->count.$cr;
        //$out.='[xml] = '.print_r($this->xml,true).$cr;
        
        return $out;
    }
}

class UPDATE_UTILS  {
    /** возвращает список файлов */
    public static function files(){
        
        $list = array();
        $q = 'select * from UPDATE_LIST order by ID desc';
        $ds = base::ds($q,'deco');
        if ($ds){
            while(base::by($ds,$row)){
                $state = $row['CSTATE'];
                
                if (!file_exists(UPDATE_ZIP_PATH.$row['CFILENAME']))
                    $state = -1;
                
                array_push($list,array(
                    'ID'=>$row['ID'],
                    'CFILENAME'=>$row['CFILENAME'],
                    'CDATE'=>$row['CDATE'],
                    'STATE'=>($state == 1?"выложен":($state==-1?"отсутствует":"не обработан")),
                    'CSTATE'=>$state
                ));
                
                
            }
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        
        //$real = DIR::files(UPDATE_ZIP_PATH,'zip');    
        
        
        return array('res'=>1,'data'=>$list);    
    }

    /** возвращает список файлов больше id */
    public static function files_by_max_id($id){
        
        $list = array();
        $q = 'select * from UPDATE_LIST where ID>'.$id.' order by ID desc';
        $ds = base::ds($q,'deco');
        if ($ds){
            while(base::by($ds,$row)){
                $state = $row['CSTATE'];
                
                if (!file_exists(UPDATE_ZIP_PATH.$row['CFILENAME']))
                    $state = -1;
                
                array_push($list,array(
                    'ID'=>$row['ID'],
                    'CFILENAME'=>$row['CFILENAME'],
                    'CDATE'=>$row['CDATE'],
                    'STATE'=>($state == 1?"выложен":($state==-1?"отсутствует":"не обработан")),
                    'CSTATE'=>$state
                ));
                
                
            }
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        
        //$real = DIR::files(UPDATE_ZIP_PATH,'zip');    
        
        
        return array('res'=>1,'data'=>$list);    
    }
    
    
    
    /** распаковка zip*/     
    public static function unpack($file){
        global $Application;
        // распапоквывает архив во временную папку
        $path = APP::slash(APP::rel_path($Application->PATH,$Application->PATH.UNPACK_ZIP_PATH),false,true);

        DIR::clear($path);
        
        $zip = new ZipArchive;
        
        try{        
        
            if ($zip->open(UPDATE_ZIP_PATH.$file) === TRUE) {
                $zip->extractTo($path);
                $zip->close();
            };
            
        }catch (Exception $e){
            _LOG("Error: ".$e->getMessage(),__FILE__,__LINE__);
            return false;
        }    
        
        return true;
    }

    /** возвращает информацию о содержимом архива обновления */
    public static function info($file){
        $out = array('res'=>0);
        
        if (!self::unpack($file)){
            _LOG("Error unpack [$file]",__FILE__,__LINE__);
            return $out;
        }
        
        $_files = DIR::files($Application->PATH.UNPACK_ZIP_PATH,'bdr');
        $tables = array();
        for($i=0;$i<count($_files);$i++){
            $bdr = $_files[$i];
            $table = APP::without_ext($bdr);
            $info = self::info_bdr($bdr);
            array_push($tables,array('ID'=>$i,'TABLE'=>$table,'HAVE'=>($info['COUNT']!=0?$info['COUNT']:''),'INFO'=>$info));
        }    
        return array('res'=>1,'tables'=>$tables);
            

    }

    /** возвращает информацию из bdr файла */
    public static function info_bdr($file){
    
        $bdr = new Bdr($file);
        $bdr->close();        
        return array("FIELDS"=>$bdr->info,"COUNT"=>$bdr->count);
    }
    
    /** возвращает список файлов больше id */
    public static function delete_zip_file($id,$filename){
            
        $q = 'delete from UPDATE_LIST where ID ='.$id;
        if (!base::query($q,'deco')){
            _LOG("Error [$q]",__FILE__,__LINE__);
            return array('res'=>0);
        }
        if (!unlink(UPDATE_ZIP_PATH.$filename)){
            _LOG("Error delete file ".UPDATE_ZIP_PATH.$filename,__FILE__,__LINE__);
            return array('res'=>0);
        }    
        
        return array('res'=>1);
    
    }
    
    public static function get_update_info($file){
        //_LOG("$file",__FILE__,__LINE__);
    
        $info = self::info($file);
        if ($info['res']==0) 
            return $info;
        
        $tab = $info['tables'];    
        
        //_LOG('['.print_r($tab,true).']',__FILE__,__LINE__);
    
        $out = array();
        $delete_lines = false;
        
        for($i=0;$i<count($tab);$i++){
            
            if ($tab[$i]['HAVE']!==''){
                
                if ($tab[$i]['TABLE']!=='DELETED_LINES')
                    array_push($out,array('TABLE'=>$tab[$i]['TABLE'],'COUNT'=>$tab[$i]['HAVE']));
                else
                    $delete_lines = array('TABLE'=>'DELETED_LINES','COUNT'=>$tab[$i]['HAVE']);
                    
            }
        }
        if ($delete_lines!==false)
            array_unshift($out,$delete_lines);
        //_LOG('['.print_r($out,true).']',__FILE__,__LINE__);
    
        return array('res'=>1,'data'=>$out);
            
    }

    private static function decode_bin($mean){
        $out = '';
        
        for($i=0;$i<((strlen($mean)-3)/2);$i++){
            
            $hex = $mean[$i*2+3].$mean[$i*2+3+1];
            $out.=chr(hexdec($hex));
            
        };
        return $out;
    }

    private static function mean_by_type($mean,$type){
        if ($type === 'STRING'){
            //$mean = mb_convert_encoding($mean,'CP1251','ASCII');
            $mean = str_replace('[<CR>]',chr(13).chr(10),$mean);
            return "'".mysql_escape_string($mean)."'";
        }

        if ($type === 'BIN.HEX')
            return '"'.mysql_escape_string(UPDATE_UTILS::decode_bin($mean)).'"';

        if ($type === 'BIN')
            return UPDATE_UTILS::decode_bin($mean);

        if (($type === 'R8') ||($type === 'I4'))
        {
            if ($mean ==='')
                $mean = 0;
                
            return str_replace(',','.',$mean);
        }
            
        if ($type === 'DATETIME')
            return "'".$mean."'";
            
    }
    
    /** обработка шага удаления (в архив) строк из таблиц */
    public static function deleted_lines($index,$count_recs){
        $res = 1;
        $msg= '';
        //----------------------------------------------------------------------
        global $base;
        global $TABLE_INDEX;
        global $DELETED_TABLES;
        
        //----------------------------------------------------------------------
        $bdr = new Bdr('DELETED_LINES.bdr');
        //----------------------------------------------------------------------
        $TABLES_WEB = base::tables('deco');
        //----------------------------------------------------------------------
        // Поиск нужной записи
        if (!$bdr->moveTo($index)){
            $bdr->close();
            return array('res'=>0,'msg'=>'index is overflow');
        };
        //----------------------------------------------------------------------
        while ($count_recs>0){
            
            $VALUES = array();
            $str = $bdr->gets();
            while(strpos($str,'[</ROW>]')===false){
                
                $field = trim(substr($str,strlen('[<FIELD>]'),strlen($str)));
                $str = $bdr->gets();
                $data = trim(substr($str,strlen('[<DATA>]'),strlen($str)));
                $str = $bdr->gets();

                $IDS = array();
                $IDS[$field] = $data;
                
                $field = trim(substr($str,strlen('[<FIELD>]'),strlen($str)));
                $str = $bdr->gets();
                $data = trim(substr($str,strlen('[<DATA>]'),strlen($str)));
                $str = $bdr->gets();
                
                $TABLE = $data;
                
                $field = trim(substr($str,strlen('[<FIELD>]'),strlen($str)));
                $str = $bdr->gets();
                $id = trim(substr($str,strlen('[<DATA>]'),strlen($str)));
                $str = $bdr->gets();
                
                $ID_FIELD = $id;
                
                $field = trim(substr($str,strlen('[<FIELD>]'),strlen($str)));
                $str = $bdr->gets();
                $val = trim(substr($str,strlen('[<DATA>]'),strlen($str)));
                $str = $bdr->gets();
                
                $ID = $val;

                $field = trim(substr($str,strlen('[<FIELD>]'),strlen($str)));
                $str = $bdr->gets();
                $val = trim(substr($str,strlen('[<DATA>]'),strlen($str)));
                $str = $bdr->gets();
                
                $DATE = $val;
            };
            
            if (array_search($TABLE,$TABLES_WEB)!==false){
                
                if (array_search($TABLE,$DELETED_TABLES)!==false){
                    $q = "delete from `$TABLE` where $ID_FIELD=$ID";
                _LOG('['.print_r($q,true).']',__FILE__,__LINE__);
    
                }else
                    $q = "update $TABLE set ARCH=1 where $ID_FIELD=$ID";
                    
                    
                if (SAVE_UPDATE_CHANGES){            
                    if (!base::query($q,'deco')){
                        //$res = 0;
                        //$msg.=$q."<br>";
                        _LOG("Error[$q]",__FILE__,__LINE__);
                    }
                }else{
                    if (rand(1,10) === 2){
                        $res = 0;
                        $msg.=$q."<br>";
                    }    
                };
                
    
            };
            
            $count_recs--;

            $str = $bdr->gets();
            if(strpos($str,'[</ROWDATA>]')===0)
                    break;
        };
        
        $bdr->close();
        return array('res'=>$res,'msg'=>$msg);
    }    
    /** шаг обновления */
    public static function update_step($table,$index,$count_recs = 1){
        //----------------------------------------------------------------------
        $bdr = new Bdr($table.'.bdr');
        $errors = array();
        //----------------------------------------------------------------------
        // масив существующих полей
        $FIELDS_WEB = base::fieldsInfo($bdr->table,true,'deco');
        //----------------------------------------------------------------------
        // Поиск нужной записи
        if (!$bdr->moveTo($index)){
            $bdr->close();
            return array('res'=>0,'msg'=>'index is overflow');
        };
        //----------------------------------------------------------------------
        $res = 1;
        $msg = '';
        while ($count_recs>0){        
            
            $VALUES = array();
            $str = $bdr->gets();
            //-------------------------------------------------------------------------------------
            /* считываем данные и пишем их в $VALUES = array('fieldName'=>value,...) */
            while(strpos($str,'[</ROW>]')===false){
                $field = trim(substr($str,strlen('[<FIELD>]'),strlen($str)));
                $str = $bdr->gets();
                $value = trim(substr($str,strlen('[<DATA>]'),strlen($str)));
                
                //$field = mb_convert_encoding($field,'UTF-8','ASCII');
                $VALUES[$field] = $value;   
                $str = $bdr->gets();
            };
            //-------------------------------------------------------------------------------------
            $ID = $VALUES[$bdr->id];
            //-------------------------------------------------------------------------------------
            // проверка существования записи
            $q = 'select count('.$bdr->id.')>0 HAVE from '.$bdr->table.' where '.$bdr->id.'='.$ID;
            //_LOG("$q",__FILE__,__LINE__);
    
            //-------------------------------------------------------------------------------------
            
            if (base::value($q,'HAVE',0,'deco') == 1){// если существует, то формируем запрос на обновление

                $q = 'update '.$bdr->table.' set ';
                $body = '';
                
    
                for($k=0;$k<count($bdr->info);$k++){
                    
                    $name = $bdr->info[$k]['NAME'];
    
                    if (($name!==$bdr->id)&&(isset($VALUES[$name]))&&(in_array($name,$FIELDS_WEB))){

                        $mean = UPDATE_UTILS::mean_by_type($VALUES[$name],$bdr->info[$k]['TYPE']);
                        
                        $body.=($body!==''?',':'').'`'.$name.'`='.$mean;
                    };    
                };
                $q.=$body.' where '.$bdr->id.'='.$ID;
                
            }else{
                
                $q = 'insert into '.$bdr->table.' ';
                $fld = '';
                $body = '';
                for($k=0;$k<count($bdr->info);$k++){
                    
                    $name = $bdr->info[$k]['NAME'];
                    if ((isset($VALUES[$name]))&&(in_array($name,$FIELDS_WEB))){
                        
                        if ($fld!=='') $fld.=',';
                        
                        $fld.='`'.$name.'`';
                            
                        $mean = UPDATE_UTILS::mean_by_type($VALUES[$name],$bdr->info[$k]['TYPE']);
                        if ($body !=='') $body.=',';
                            $body.=$mean;
                    };    
                };
            
                $q.='('.$fld.') values ('.$body.')';
            };
            
            if (SAVE_UPDATE_CHANGES){            
                if (!base::query($q,'deco')){
                    $q = mb_convert_encoding($q,'utf-8','cp1251');
                    _LOG("Error[".substr($q,0,100)."..]",__FILE__,__LINE__);
                    $res = 0;
                    $msg.=$q."<br>";
                }    
            }else{
                // для отладки имитируем отказ
                //if (rand(1,10) === 2){
                    $res = 0;
                    $q = mb_convert_encoding($q,'utf-8','cp1251');
                    $msg.=substr($q,0,200)."<br>";
                    _LOG("Error[".substr($q,0,100)."..]",__FILE__,__LINE__);
                    
                //};
                
            }        

            $count_recs--;
            $str = $bdr->gets();
            
            if(strpos($str,'[</ROWDATA>]')===0)
                    break;

        };
        
        $bdr->close();
        return array('res'=>$res,'msg'=>$msg);
        
    }

    /**
     * получение строк с данными, для вывода в MOD_UPDATE
     */ 
    public static function bdrRow($file,$index,$count_recs = 1,$crop=0){
        //----------------------------------------------------------------------
        $bdr = new Bdr($file);
        //----------------------------------------------------------------------
        // масив существующих полей
        $FIELDS_WEB = base::fieldsInfo($bdr->table,true,'deco');
        //----------------------------------------------------------------------
        // Поиск нужной записи
        if (!$bdr->moveTo($index)){
            $bdr->close();
            return array('res'=>0,'msg'=>'index is overflow');
        };
        //----------------------------------------------------------------------
        // считываем данные
        $data = array();        
        while ($count_recs>0){        
            
            $VALUES = array();
            $str = $bdr->gets();
            
            while(strpos($str,'[</ROW>]')===false){

                $field = trim(substr($str,strlen('[<FIELD>]'),strlen($str)));
                $str = $bdr->gets();
                $value = trim(substr($str,strlen('[<DATA>]'),strlen($str)));
                
                
                $VALUES[$field] = mb_convert_encoding($value,'UTF-8','CP1251');
                if ($crop>0)
                    if (mb_strlen($VALUES[$field],'UTF8')>$crop){
                        $VALUES[$field] = mb_substr($VALUES[$field],0,$crop).' ..';
                    }    
                
                $str = $bdr->gets();
            };
            
            array_push($data,$VALUES);
            $count_recs--;
            $str = $bdr->gets();
            
            if(strpos($str,'[</ROWDATA>]')===0)
                    break;
        };
        //----------------------------------------------------------------------
        $bdr->close();
        //----------------------------------------------------------------------
        return array('res'=>1,'indexField'=>$bdr->id,'fields'=>$bdr->fields,'data'=>$data);
        
    }
    
    public static function media($index,$count_recs=1){
        $res = 1;
        $msg= '';
        //----------------------------------------------------------------------
        global $base;
        global $TABLE_INDEX;
        $TABLE = 'C_MEDIA_FILE';
        $FILENAME = 'PATH_WWW';
        $INDEX_NAME = $TABLE_INDEX[$TABLE];
        $FIELDS_WEB = base::fieldsInfo($TABLE,true,'deco');
        
        //----------------------------------------------------------------------
        $bdr = new Bdr($TABLE.'.bdr');
        //----------------------------------------------------------------------
        
        // Поиск нужной записи
        if (!$bdr->moveTo($index)){
            $bdr->close();
            return array('res'=>0,'msg'=>'index is overflow');
        };
        //----------------------------------------------------------------------
        
        while ($count_recs>0){        
            
            $VALUES = array();
            $str = $bdr->gets();
            //-------------------------------------------------------------------------------------
            /* считываем данные и пишем их в $VALUES = array('fieldName'=>value,...) */
            
            while(strpos($str,'[</ROW>]')===false){
                
    
                $field = trim(substr($str,strlen('[<FIELD>]'),strlen($str)));
                $str = $bdr->gets();
                $value = trim(substr($str,strlen('[<DATA>]'),strlen($str)));
                
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
            
            for($k=0;$k<count($bdr->info);$k++){
                    
                $name = $bdr->info[$k]['NAME'];
                
                if (in_array($name,$FIELDS_WEB)){
                    if (isset($VALUES[$name])){
                        
                        $mean = $VALUES[$name];
                        
                        if ($name===$FILENAME){
                            $file = BIN_STORY_PATH.APP::slash(str_replace("\\",'/',$mean),false,false);
                        
                            $ext = APP::ext($file);
                            $path = APP::get_path($file);
                            $file = $path.APP::without_ext($file).'_'.$VALUES[$INDEX_NAME].'.'.$ext;
                            $mean = str_replace(BIN_STORY_PATH,'',$file);
                        };
                        
                        $mean = UPDATE_UTILS::mean_by_type($mean,$bdr->info[$k]['TYPE']);
                        // формируем тело update
                        if ($name!==$bdr->id)
                            $update.=($update!==''?',':'').'`'.$name.'`='.$mean;
                            
                        // формируем тело insert
                        if ($fld!=='') $fld.=',';
                            $fld.='`'.$name.'`';
                        if ($insert !=='') $insert.=',';
                                $insert.=$mean;
                                
                    };            
                };    
                
            };//for
            
            $q = 'insert into '.$TABLE.' ('.$fld.') values ('.$insert.') on duplicate key update '.$update ;
            // -----------------------------------------------------------------------------------
            
            if (!base::query($q,'deco')){
                _LOG('Error['.$q.']',__FILE__,__LINE__);                
                $res = 0;
            }else{
                if (!$file){
                    _LOG('undefined file ['.$VALUES[$FILENAME].']',__FILE__,__LINE__);
                    $res = 0;
                }else{    
                
                    if (($path!=='')&&(!file_exists($path))&&(!mkdir($path, 0777, true))){
                        _LOG('Error create path ['.$path.']',__FILE__,__LINE__);
                        $res = 0;
                    }else{
    
                        if (!self::saveBinToFile($VALUES['CONTENT'],$file)){
                            $res = 0;
                            _LOG($file.' story error',__FILE__,__LINE__);
                        }    
                    }
                }
            };
            

            $count_recs--;
            $str = $bdr->gets();
            
            if(strpos($str,'[</ROWDATA>]')===0)
                    break;

        };
        $bdr->close();

        
        return array('res'=>$res);
    }    
    /** сохраним бинарные данные из bdr в файл 
     * ВНИМАНИЕ! Функция не проверялась
    */
    public static function saveBinToFile($bin,$filename){
        $data = UPDATE_UTILS::mean_by_type($bin,'BIN');
        
        if (file_put_contents($filename,$data)!==false) 
            return true;
            
        return false;
    }
    
}


?>