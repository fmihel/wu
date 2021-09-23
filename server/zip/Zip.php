<?php
namespace wu\server\zip; 
require_once __DIR__.'/drivers/ZipDriver.php';
require_once __DIR__.'/drivers/ZipStreamDriver.php';


/** класс интерфейс для использования различных мехаизмов упаковки */
class Zip{
    private $driver;
    private $zipFileName;
    public function __construct($driver)
    {
        $this->driver = $driver;
        
    }
    public function create(string $file,$param=[]){
        $this->zipFileName = $file;
        $this->driver->create($file,$param);
    }

    public function close($param=[]){
        $this->driver->close($param);
    }
    public function add(string $from,string $to,$param=[]){
        $d = $this->driver;
        $d->add($from,$to,$param);
    }
};

/*
//$driver = new Driver();
$driver = new ZipStreamDriver();
$arch = new Arch($driver);
$arch->create(__DIR__.'/test.zip');
$arch->add(__DIR__.'/data/test.xlsx','tool/test.xlsx');
$arch->close();
*/
?>