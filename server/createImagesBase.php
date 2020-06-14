<?php

$debug = true;

if ($debug){
    
    if (isset($_REQUEST['info'])){
        print_r(CreateImagesBase::info());  
        exit;
    };
    if (isset($_REQUEST['step'])){
        print_r(CreateImagesBase::step($_REQUEST['step']));  
        exit;
    };


};

/** пошаговое копирование изображний в папка для локального каталога*/
class CreateImagesBase{
    private static $param=[
        'baseFile'=>__DIR__.'/test/images.php', // имя базы
        'to'=>__DIR__.'/test/media/',           // куда копировать
        'countOnStep'=>1000,                      // кол-во за один шаг

    ];
    /** копирование части базы за шаг */
    public static function step($step,$param=[]){
        try {
            $p = array_merge(self::$param,$param);
            require_once($p['baseFile']);
            
            $keys = array_keys($images);
            $min = $p['countOnStep']*($step-1);
            $max = min($min+$p['countOnStep'],count($keys));

            if (!is_dir($p['to'])){
                mkdir($p['to']);
            };

            for($i=$min;$i<$max;$i++){
                $name = $keys[$i];
                if (!@copy($images[$name],$p['to'].$name)){
                    error_log('warn ['.__FILE__.':'.__LINE__.'] cant copy "'.$images[$name].'"');        
                };
            };

            return true;

        } catch (\Exception $e) {
            error_log('Exception ['.__FILE__.':'.__LINE__.'] '.$e->getMessage());
            throw $e;
        };
    }
    /** расчет кол-ва шагов для копирования 
     * @returns {array} [count=>INT]
    */
    public static function info($param=[]){
        try {
            $p = array_merge(self::$param,$param);
            require_once($p['baseFile']);
            $all = count($images);
            $count = floor( $all/$p['countOnStep']);
            if ($count*$p['countOnStep']<$all)
                $count++;

            return array_merge(self::$param,['count'=>$count,'image_count'=>$all]);
                
        } catch (\Exception $e) {
            error_log('Exception ['.__FILE__.':'.__LINE__.'] '.$e->getMessage());
            throw $e;
        };
       
    }
};



?>