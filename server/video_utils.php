<?php

use fmihel\console;
use fmihel\lib\Dir;


class video_utils{
    /** удаление неспользуемых видео */
    public static function clear(){
        $videoPath = WS_CONF::GET('videoPath');
        // строим относительный путь к папке с видео (добавляем в конец слеш)
        $path = Dir::slash(Dir::rel_path(__DIR__,$videoPath),false,true);

        try {
            // список всех нужных видео (из таблицы)
            $q = 'select PATH_WWW from C_MEDIA_FILE where PROCESSING_KIND = 4 and ARCH<>1';
            $list = base::rowsE($q,'deco');
            
            // список всех файлов на диске
            $exists = Dir::files($path,'',true,false);

            foreach($exists as $file){

                $file = Dir::slash(str_replace($path,'',$file),false,false); // файл имеет относительный путь, относительно текущей папки, поэтому удаляем из пути $path 
                
                // поиск файла в существующем списке
                $search = false;
                foreach($list as $need){
                    $need = Dir::slash($need['PATH_WWW'],false,false);
                    if ($need === $file){
                        $search = true;
                        break;
                    };
                };

                // если файл не найден в таблице, его удаляем
                if (!$search){

                    $file = Dir::join([$videoPath,$file],'unix');
                    if (!@unlink($file)){
                        console::error('delete ',$file);
                    }else{
                        console::log('delete ',$file);
                    };
                };
                
            }

        } catch (\Exception $e) {
            console::error($e);
        };
    }
}