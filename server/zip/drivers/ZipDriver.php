<?php
namespace wu\server\zip\drivers; 
/** интерфейс для драйвера */
class ZipDriver{
    private $zipFileName;

    public function create($zipFileName,$param=[]):bool{
        $this->zipFileName = $zipFileName;
        return true;
    }

    public function close($param=[]){

    }
    public function add(string $from,string $to,$param=[]){
        
    }
}

?>