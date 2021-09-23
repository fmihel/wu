<?php
namespace wu\server\zip\drivers;
use wu\server\zip\drivers\ZipDriver;
use ZipStream\ZipStream;


/** драйвер стороннего проекта maennchen/zipstream-php */

class ZipStreamDriver extends ZipDriver{
    private $zip;
    private $options;
    private $stream;
    public function create($zipFileName,$param=[]):bool{
        parent::create($zipFileName,$param);
        try {
            
            $this->options = new \ZipStream\Option\Archive();
            //$this->options->setSendHttpHeaders(true);
            $this->stream = fopen($zipFileName, 'wb');
            $this->options->setOutputStream($this->stream);
            
            $this->zip = new ZipStream($zipFileName,$this->options);
            return true;

        } catch (\Exception $e) {
        
        };
        return false;
    }    

    public function close($param=[]){
        $this->zip->finish();
        fclose($this->stream);
        parent::close($param);
    }

    public function add(string $from,string $to,$param=[]){
        parent::add($from,$to,$param);
        $this->zip->addFileFromPath($to,$from);
    }
 
    
}

?>