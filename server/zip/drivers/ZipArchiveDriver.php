<?php
namespace wu\server\zip\drivers;
use wu\server\zip\drivers\ZipDriver;


/** драйвер стандартного ZipArchive (использует старый алгоритм запаковки, не поддерживается в Win10*/
class ZipArchiveDriver extends ZipDriver{
    private $zip;
    public function create($zipFileName,$param=[]):bool{
        parent::create($zipFileName,$param);
        $this->zip = new \ZipArchive;
        return ( $this->zip->open($zipFileName,\ZipArchive::CREATE)?true:false );
        
    }

    public function close($param=[]){
        $this->zip->close();
        parent::close($param);
    }
    public function add(string $from,string $to,$param=[]){
        parent::add($from,$to,$param);
        $this->zip->addFile($from,$to);
    }
}


?>