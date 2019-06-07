<?php
    
    /**
     * моудль возвращает список уже присутствующих пакетов обновления на сервере
     * @return <list>filename1.zip,filename2.zip,...</list>
     */ 
    
    
    require_once 'consts.php';  
    
    $q = 'select  distinct CFILENAME from UPDATE_LIST order by ID desc';
    $ds = base::ds($q,'deco');
    
    if ($ds){
        echo '<list>';
        $bool = false;
        while(base::by($ds,$row)){
            echo ($bool?',':'').$row['CFILENAME'];
            $bool = true;
            
        }
        echo '</list>';
    }else{
        _LOG("Error [$q]",__FILE__,__LINE__);
        echo RESULT_ERROR;
    }    

    
?>