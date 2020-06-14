<?php
/*
SRCE_KIND = 1 :    SRCE_ID соотвествует ID_K_CHAPTER из K_CHAPTER
SRCE_KIND = 2 :    SRCE_ID соотвествует ID_K_MODEL из K_MODEL
SRCE_KIND = 6 :    SRCE_ID соотвествует ID_K_CHAPTER из K_CHAPTER
SRCE_KIND = 7 :    SRCE_ID соотвествует ID_K_CHAPTER из K_CHAPTER
SRCE_KIND = 8 :    SRCE_ID соотвествует  ID_TX_SECTION из TX_SECTION
SRCE_KIND = 9 :    SRCE_ID соотвествует  ID_TX_SET из TX_SET
SRCE_KIND = 10 :   SRCE_ID соотвествует  ID из J_FOLDER
SRCE_KIND = 11 :   SRCE_ID соотвествует  ID из J_SET
*/

$SRCE_KIND = array(
    array('table'=>'','field'=>'SRCE_ID'),//0
    array('table'=>'K_CHAPTER',     'field'=>'ID_K_CHAPTER','is_chapter'=>true,'media_kind'=>2),//1
    array('table'=>'K_MODEL',       'field'=>'ID_K_MODEL',  'is_chapter'=>false,'media_kind'=>1),//2
    array('table'=>'',              'field'=>''),//3
    array('table'=>'',              'field'=>''),//4
    array('table'=>'',              'field'=>''),//5
    array('table'=>'K_CHAPTER',     'field'=>'ID_K_CHAPTER', 'is_chapter'=>true,'media_kind'=>2),//6
    array('table'=>'K_CHAPTER',     'field'=>'ID_K_CHAPTER', 'is_chapter'=>true,'media_kind'=>2),//7
    array('table'=>'TX_SECTION',    'field'=>'ID_TX_SECTION','media_kind'=>3),//8
    array('table'=>'TX_SET',        'field'=>'ID_TX_SET','media_kind'=>3),//9
    array('table'=>'J_FOLDER',      'field'=>'ID','media_kind'=>3),//10
    array('table'=>'J_SET',         'field'=>'ID','media_kind'=>3),//11
    
);
/*
Иконки узлов
0-Карнизы корень
1-карнизы папка
2-Ткани корень
3-Ткани папка
4-Жалюзи корень
5-Жалюзи папка
6-Ткани прайс
7-Карнизы прайс
8-Жалюзи прайс
9-Карнизы модель
10-Изображение
11-Описание
12-Жалюзи модель
13-Карнизы сетка
14-Ткани сетка
15-Жалюзи сетка
*/

$ICONS = array(
    'root_karniz',//0
    'folder_karniz',//1
    'root_tkani',//2
    'folder_tkani',//3
    'root_jaluzi',//4
    'folder_jaluzi',//5
    'price_tkani',//6
    'price_karniz',//7
    'price_jaluzi',//8
    'model_karniz',//9
    'page_image',//10
    'page_notes',//11
    'model_jaluzi',//12
    'grid_karniz',//13
    'grid_tkani',//14
    'grid_jaluzi',//15

    'root_electro',//16
    'folder_electro',//17
    'model_electro',//18
    'price_electro',//19
    'grid_electro',//20
    
    'model_tkani',//21

);

$RUS_BUK = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ъ','э','ю','я'];
$RUS_BUK_UP = ['А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ь','Ы','Ъ','Э','Ю','Я'];

$ENG_BUK = ['a','b','v','g','d','e','e','g','z','i','i','k','l','m','n','o','p','r','s','t','u','f','h','c','c','s','s','' ,'' ,'' ,'e','u','y'];

class TREE_GENERATE{
    private static $coding = 'utf8';
    private static $print = [];

    public static function create($saveToFile = false,$param=[]){
        $beautyFormat = true;
        $param = array_merge([
            'enableOffline'     =>true,
            'pathOffline'       =>false    
        ],$param);
        
        if ( ($param['pathOffline']===false) 
            &&
             ($saveToFile) 
            && 
             ($param['enableOffline'])
        ){
            $param['pathOffline'] = APP::get_path($saveToFile);
        }
        
        try{
            $out = [];
            $offline = [];
            $offlineBase = [];
            $images = [];
            $q = 'select * from CTLG_NODE where ID_PARENT = 0 and  ARCH<>1 order by NOM_PP';
            $ds = base::dsE($q,'deco',self::$coding);
            
            $row = [];
            while(base::by($ds,$row)){
                $name = self::translit('',$row);
                $res = self::_create($row,'');
                $out[] = $res['all'];
                $offline[$name] = [
                    'caption'=>self::ucfirst(trim(mb_strtolower($row['CAPTION']))),
                    'data'=>$res['offline']
                ];
                $offlineBase[$name] = $res['base'];
                $images = $images+$res['images'];
                
            }
    
            $out[]=[
                'caption'=>'Личный кабинет',
                'icon'=>'file',
                 
                'id'=>'main_page',
                'viewAs'=>'mainPage',
                'subset'=>[0],
                'hash'=>'main'
            
            ];  
            
            if ($saveToFile){
                $cr = $beautyFormat?chr(13).chr(10):'';
                $json = ARR::to_json($out,$beautyFormat);
                file_put_contents($saveToFile,'var catalog2='.$json.';');

                if ($param['enableOffline']){
                    $code = 'var base = { menu :[ { id:"main",caption:"Windeco",link:"/",exact:true},';

                    foreach($offline as $name=>$item){
                        $code.='{'.$cr;
                        $code.='id:"'.$name.'",'.$cr;
                        $code.='caption:"'.$item['caption'].'",'.$cr;
                        $code.='link:"/'.$name.'",'.$cr;
                        $code.='},'.$cr;

                        //$json = ARR::to_json($data,true);
                        //file_put_contents($param['pathOffline'].$name.'.js',"var $name=".$json.';');
                    }
                    $code.='],';//menu
                    $code.='tree:{';
                    foreach($offline as $name=>$item){
                        $code.=$name.':';
                        $code.= ARR::to_json($item['data']['childs'],$beautyFormat);
                        $code.=',';

                        //$json = ARR::to_json($data,true);
                        //file_put_contents($param['pathOffline'].$name.'.js',"var $name=".$json.';');
                    }
                    $code.='},';//tree
                    $code.='};';//base
                    file_put_contents($param['pathOffline'].'index.js',$code);

                    foreach($offlineBase as $name=>$data){
                        
                        $json = ARR::to_json($data,$beautyFormat);
                        file_put_contents($param['pathOffline'].$name.'-base.js',"var ".$name."_base=".$json.';');
                    }
                    
                    $php = '';
                    $count = 100000;
                    foreach($images as $name=>$image){
                        //$php.='"'.$name.'"=>"'.quotemeta($image).'",'.$cr;
                        $count--;
                        if ($count>0)
                        $php.="'".$name."'=>'".$image."',".$cr;
                    }
                    file_put_contents($param['pathOffline'].'images.php','<?php $images=['.$php.']?>');
                    
                }
            
            }else
                return $out;
        }catch(Exception $e){
            
            throw new Exception($e->getMessage());

        }
        
    }
    
    private static function _create($node,$parent,$param=[]){
        global $SRCE_KIND;
        global $ICONS;
        
        $ID     = $node['SRCE_ID'];
        
        $kind   = $SRCE_KIND[$node['SRCE_KIND']];
        $FIELD  = $kind['field'];
        $TABLE  = $kind['table'];
        
        //-------------------------------------------------------------------------------------------------------
        $images = [];
        //-------------------------------------------------------------------------------------------------------
        $out            = [];
        
        $out['id']      =   $node['ID_CTLG_NODE'];
        $out[$FIELD]    =   $ID;
        $out['table']   =   $TABLE;
                
        $out['caption']    =   self::stringCorrect($node['CAPTION']);
        $out['icon']       =   isset($ICONS[$node['ICON_IND']])?$ICONS[$node['ICON_IND']]:$ICONS[11];
        $out['SRCE_KIND']  =   $node['SRCE_KIND'];
        
        $out['hash']       =   self::translit($parent,$node);
                
        $out['media'] = self::_get_media($ID,COMMON::get($kind,'media_kind',''));
        //-------------------------------------------------------------------------------------------------------
        $offline  = [
            'id'=>$node['ID_CTLG_NODE'],
            'caption'=>str_replace(['&quot;'],[],$out['caption']),
        ];
        //-------------------------------------------------------------------------------------------------------
        $dataParam = [
            $FIELD=>$ID,
            'table'=>$TABLE,
            'type'=>'',
            'media'=>$out['media']
        ];

        //-------------------------------------------------------------------------------------------------------
        $currentBaseId = 'id-'.$node['ID_CTLG_NODE'];
        $base = [
            $currentBaseId=>[
                'caption'=>$offline['caption'],
            ]
        ];
        //-------------------------------------------------------------------------------------------------------
        $q = 'select distinct ID_CTLG_SUBSET from CTLG_SUBSET_NODE where ID_CTLG_NODE = '.$node['ID_CTLG_NODE'];
        $ds = base::dsE($q,'deco');
        $row=[];
        $out['subset'] = [];
        while(base::by($ds,$row)){
            $out['subset'][]=$row['ID_CTLG_SUBSET'];        
        }
        //-------------------------------------------------------------------------------------------------------

        $child = [];
        $childOffline = [];
        $childBase = [];
        $q = 'select * from CTLG_NODE where ID_PARENT = '.$node['ID_CTLG_NODE'].' and  ARCH<>1 order by NOM_PP';
        $ds = base::dsE($q,'deco',self::$coding);
        $row = [];
        while(base::by($ds,$row)){
            $res        = self::_create($row,$out['hash'],$param);
            $child[]    = $res['all'];
            $childOffline[]  = $res['offline'];
            $childBase = array_merge($childBase,$res['base']);
            $images = array_merge($images,$res['images']);
        }

        $addition = [];
        if ($node['SRCE_KIND']==0){
            if (count($child)===0)
                $out['viewAs']='gallery';      
        }else if (($node['SRCE_KIND']==1)||($node['SRCE_KIND']==2)||($node['SRCE_KIND']==6)||($node['SRCE_KIND']==7)){
            $out['IS_CHAPTER']          =   ($kind['is_chapter']?1:0);
            //$offline['IS_CHAPTER'] = $out['IS_CHAPTER'];
            $dataParam['IS_CHAPTER'] = $out['IS_CHAPTER'];
            $addition = self::karniz($node);
            $out = array_merge($out,$addition); 
            //$offline = array_merge($offline,$addition);
            $dataParam['type']='karniz';
            
        }else if (($node['SRCE_KIND']==8)||($node['SRCE_KIND']==9)){
            $addition = self::tkani($node);
            $out = array_merge($out,$addition);
            $dataParam['type']='tkani';
            
        }else if (($node['SRCE_KIND']==10)||($node['SRCE_KIND']==11)){
            $addition = self::jaluzi($node);
            $out = array_merge($out,$addition);
            $dataParam['type']='jaluzi';                    
        }                    

        
        $offlineData = self::getOfflineData(array_merge($dataParam,$addition));
        $images = array_merge($images,$offlineData['images']);

        $base[$currentBaseId]['data'] = $offlineData['out'];

        if (count($childOffline)>0)
            $offline['childs'] = $childOffline;

        if (count($child)>0)
            $out['child'] = $child;
        //-------------------------------------------------------------------------------------------------------
        
        return [
            'all'       =>  $out,
            'offline'   =>  $offline,
            'base'      =>  array_merge($base,$childBase),
            'images'    =>  $images
        ];
    }
    private static function stringCorrect($str){
        
        $str = htmlspecialchars($str);
        $from   = array(
                '"',                          //0
                "'",                          //0
        );
        $to     = array(
                '' , //0
                '' , //0
        );
        $res =  str_replace($from,$to,$str);
        return $res;
        
    }    
    private static function _get_media($OWNER_ID,$OWNER_KIND){
        
        if (!is_numeric($OWNER_ID) || (!is_numeric($OWNER_KIND)))
            return [];

        $view     = [];
        $download = [];
        $print    = [];
        
        $q = "
            select 
                ID_C_MEDIA_FILE,CAPTION,PATH_WWW,PROCESSING_KIND 
            from 
                C_MEDIA_FILE 
            where
                OWNER_ID = $OWNER_ID and OWNER_KIND = $OWNER_KIND and ARCH<>1
            order by 
                PROCESSING_KIND,NOM_PP ";

        if ($OWNER_ID=='362'){
    
        }
        $PROCESSING_KIND = -1;
        $ds = base::dsE($q,'deco',self::$coding);
        if ($ds){
            $row = [];
            while(base::by($ds,$row)){
                
                $row['CAPTION'] = self::stringCorrect($row['CAPTION']);
                if ($PROCESSING_KIND!==$row['PROCESSING_KIND'])
                    $PROCESSING_KIND = $row['PROCESSING_KIND'];
                
                $row['PATH_WWW'] = HTTP_MEDIA.$row['PATH_WWW'];

                if ($PROCESSING_KIND == 1)
                    $view[] = $row['PATH_WWW'];
                elseif ($PROCESSING_KIND == 2)
                    $download[] = $row;
                elseif ($PROCESSING_KIND == 3)
                    $print[] =$row;                    
                    
            }
        }else{
            //_LOG("Error [$q]",__FILE__,__LINE__);
        }
        $out = [];
        if (count($view)>0)
            $out['gallery'] = $view;
        
        if (count($download)>0)
            $out['download'] = $download;
            
        if (count($print)>0)
            $out['print'] = $print;
        
        return $out;            
    }

    private static function karniz($row){
        global $SRCE_KIND;
        $out = array();
        $kind = $SRCE_KIND[$row['SRCE_KIND']];
        
        $ID     = $row['SRCE_ID'];
        $FIELD  = $kind['field'];
        $TABLE  = $kind['table'];
        $is_chapter     = $kind['is_chapter'];
        $priceType = self::_typePrice($ID,$is_chapter);
        
        $q = "select SHOW_AS from $TABLE where $FIELD=$ID";
        $show_as = base::valE($q,0,'deco');
        
        $out['viewAs']          =   self::_viewAs($show_as,$priceType);
        
        if ($out['viewAs']==='karnizB')
            $out['ID_K_TOVAR'] = self::idTovarPriceB($ID,$is_chapter);
        
        
        return $out;
    }
    
    private static function jaluzi($row){
        $out = array();
        $out['viewAs'] = 'jaluzi';

        return $out;
    }
    
    private static function tkani($row){
        $out = array();
        $out['viewAs'] = 'tkani';
        return $out;
    }
    
    private static function _viewAs($SHOW_AS,$typePrice=''){
        
        if (($SHOW_AS==1)&&($typePrice!==''))
            return 'karniz'.$typePrice;
        
        if ($SHOW_AS==2)
            return 'gallery';
        
        return '';
        
    }
    
    /** возвращает букву определяющую тип прайса карнизов A или B */
    private static function _typePrice($id,$is_chapter){

        $q='        
            select distinct
                t.PRICELIST_KIND
            from 
                K_MODEL_TOVAR mt
            join
                K_MODEL_TOVAR_DETAIL mtd
                on mt.ID_K_MODEL_TOVAR = mtd.ID_K_MODEL_TOVAR
            join 
                K_TOVAR_DETAIL td
                on td.ID_K_TOVAR_DETAIL = mtd.ID_K_TOVAR_DETAIL
            join
                K_TOVAR t
                on td.ID_K_TOVAR = t.ID_K_TOVAR
            where ';

        if ($is_chapter)
            $q.='mt.ID_K_CHAPTER='.$id;
        else
            $q.='mt.ID_K_MODEL='.$id;
        
        
        
        return (base::valueE($q,'PRICELIST_KIND',1,'deco')==2?'B':'A');
        /*
        if (MYSQL::Assigned($ds))
            return ($ds->FieldByName('PRICELIST_KIND')==2);
        return 'A';


        return self::priceBTovarList($id,$is_chapter,true)>0?'B':'A';
        */
    }
    private static function idTovarPriceB($id,$is_chapter){
        // возвращает список товаров по которым будут создаваться матричные прайс-листы
            
        $q = 'select distinct t.ID_K_TOVAR ';
        
        $q.='from 
                K_MODEL_TOVAR mt
                join 
                K_MODEL_TOVAR_DETAIL mtd 
                    on mt.id_k_model_tovar = mtd.id_k_model_tovar
                join 
                K_TOVAR_DETAIL td 
                    on td.id_k_tovar_detail = mtd.id_k_tovar_detail
                join 
                K_TOVAR t 
                    on t.id_k_tovar = td.id_k_tovar
            where
                td.SIZE_BORDER>0
                and
                td.ARCH<>1
                and
                t.ARCH<>1
                and
                mt.ARCH<>1
                and ';
        if ($is_chapter)
            $q.='mt.id_k_chapter='.$id;
        else
            $q.='mt.id_k_model='.$id;
            
        return base::valueE($q,'ID_K_TOVAR','-1','deco',self::$coding);
        

    }
    
    private static function translit($pref,$node){
        global $RUS_BUK;
        global $ENG_BUK;

        $out = trim(mb_strtolower($node['CAPTION']));
        while(strpos($out,'  ')!==false)
            $out = str_replace(['  '],[' '],$out);

        $out = str_replace([' '],['_'],$out);
        $out = preg_replace('/[^a-zA-Zа-яА-Я0-9\_]/ui', '',$out);
        $out = str_replace($RUS_BUK,$ENG_BUK,$out);
        
        return $pref.($pref!==''?"/":"").$out;
    }

    private static function getOfflineData($param=[]){
        $out = [];
        $images = [];

        $originalImages = COMMON::get($param,'media','gallery',[]);
        foreach($originalImages as $image){
            $name =  str_replace(['.jpg'],[''],basename($image));
            $out[]=['image'=>$name];
            $images[$name] = $image;
        }


        $from = ['out'=>[],'images'=>[]];
        if ($param['type'] === 'karniz'){
            $from = self::getOfflineDataKarniz($param);
        }
        if ($param['type'] === 'jaluzi'){
            $from =self::getOfflineDataJaluzi($param);
        }
        if ($param['type']==='tkani'){
            $from = self::getOfflineDataTkani($param);
        }
        $out = array_merge($from['out'],$out);
        

        foreach($from['images'] as $image){
            $name =  str_replace(['pic.php?','='],['','_'],basename($image));
            $images[$name] = $image;
        }
        

        return ['out'=>$out,'images'=>$images];
    }
    private static function getOfflineDataKarniz($param){
        $out=['out'=>[],'images'=>[]];
        if ($param['viewAs'] === 'karnizA'){
            $q = self::sql($param['viewAs'],$param);
            $ds = base::ds($q,'deco','utf8');
            /*$func = function($v){
                return ['name'=>$v];
            };
            $fields = array_map($func,base::fields($ds,true));
            */
            $fields=[
                ['name'=>'TOVAR','caption'=>'Товар'],
                ['name'=>'PROP','width'=>100,'caption'=>'св-во'],
                ['name'=>'ART','width'=>100,'caption'=>'артикул'],
                ['name'=>'COLOR','width'=>150,'caption'=>'цвет'],
                ['name'=>'PRICE','width'=>100,'caption'=>'цена'],
                ['name'=>'ED_IZM','width'=>50,'caption'=>'ед.']
            ];
            $out['out']=[[
                'table'=>[
                    'fields'    =>  $fields,
                    'rows'      =>  base::rows($ds),
                ]
            ]];
        
        } elseif ($param['viewAs'] === 'karnizB') {
            
        } elseif ($param['viewAs'] === 'gallery'){

        }
        
        return $out;
    }
    private static function getOfflineDataJaluzi($param){
        $out    = ['out'=>[],'images'=>[]];
        $ID     = $param['ID'];
        $TABLE  = $param['table'];
        $is_folder = $TABLE==='J_FOLDER'?true:false; 
        

        //if ( self::prin('param',$ID,$TABLE) ){
            
            $price = jaluzi::price($ID,$is_folder);
            $caption = '';
            foreach($price['data'] as $data){
                if ($caption==='') 
                    $caption = $data['SET']['NAME'];
                $blocks = $data['BLOCKS'];
                foreach($blocks as $block){
                    if ($block['TYPE'] === 'GRID'){
                        $out['out'][]=[
                            'table'=>[
                                'fields'    =>  array_map(function($field){ $field['caption']=strip_tags($field['caption']); return $field;  },$block['FIELDS']),
                                'rows'      =>  $block['ROWS'],
                                'kind_show' =>  $block['KIND_SHOW'],
                                'param'     =>  $block['PARAM']
                            ]
                        ];
                    }elseif ($block['TYPE'] === 'IMAGE'){
                        $out['out'][]=[
                            'image'=> str_replace(['pic.php?','='],['','_'],basename($block['URL']))
                        ];
                        $out['images'][]=$block['URL'];
                    }
                }
                    
            }
           
        //}
        //self::print(['ID'=>$ID,'table'=>$TABLE]);
//        }
        
        return $out;
    }
    private static function getOfflineDataTkani($param){
        //echo '<xmp>';
        //print_r($param);
        //echo '</xmp>';
        $out = ['out'=>[],'images'=>[]];
        if (isset($param['ID_TX_SET'])){
            $ID_TX_SET = $param['ID_TX_SET'];
                $table = TKANI::price($ID_TX_SET,-1,['enableCache'=>false]);
                if (isset($table['fields']) && isset($table['data'])){
                    $out['out']=[[
                        'table'=>[
                            'fields'    =>  $table['fields'],
                            'rows'      =>  $table['data'],
                        ]
                    ]];                
                }
                //echo '<xmp>';
                //print_r($out);
                //echo '</xmp>';
               
        }
        return $out;
    }
    private static function ucfirst($str){
        global $RUS_BUK;
        global $RUS_BUK_UP;
        $first = ucfirst(mb_substr($str,0,1));
        
        $index = array_search($first,$RUS_BUK);
        if ($index!==false){
            $first = $RUS_BUK_UP[$index];
        }
        
        return $first.mb_substr($str,1);
    }

    private static function sql($name,$init=[]){
        if ($name === 'karnizA'){
            $init = array_merge([
                        'koef'=>1
                    ],$init);

            $q = '
            SELECT
            DISTINCT mtd.ID_K_MODEL_TOVAR_DETAIL ID,
            t.NAME TOVAR,
            (
            SELECT
                GROUP_CONCAT(concat(pk.TITLE,
                p.VAL)) AS PROP
            FROM
                PROP p,
                PROP_KIND pk
            WHERE
                td.ID_K_TOVAR_DETAIL = p.ID_OWNER
                AND p.ID_PROP_KIND = pk.ID_PROP_KIND
                AND p.ARCH <> 1 ) PROP,
            td.ART,
            c.NAME_FULL COLOR,
            /*wk.CAPTION ,*/
            mtd.PRICE_CATALOG PRICE,
            ez.NAME_SHORT ED_IZM
        FROM
            K_MODEL_TOVAR mt
        JOIN K_MODEL_TOVAR_DETAIL mtd ON
            mt.ID_K_MODEL_TOVAR = mtd.ID_K_MODEL_TOVAR
        JOIN K_TOVAR_DETAIL td ON
            td.ID_K_TOVAR_DETAIL = mtd.ID_K_TOVAR_DETAIL
        JOIN K_TOVAR t ON
            td.ID_K_TOVAR = t.ID_K_TOVAR
        JOIN K_WIN_KIND wk ON
            t.ID_K_WIN_KIND = wk.ID_K_WIN_KIND
        LEFT OUTER JOIN K_COLOR c ON
            td.ID_K_COLOR = c.ID_K_COLOR
        LEFT OUTER JOIN ED_IZM ez ON
            t.ID_ED_IZM = ez.ID_ED_IZM
            ';
            
            if ($init['IS_CHAPTER']=='1')
                $q.= ' where mt.ID_K_CHAPTER='.$init['ID_K_CHAPTER'];
            else
                $q.= ' where mt.ID_K_MODEL='.$init['ID_K_MODEL'];
            
            $q.=' and t.ARCH<>1 and td.ARCH<>1 and mtd.ARCH<>1 ';    
            $q.='  order by mt.NOM_PP, t.NAME,PROP, mtd.PRICE_CATALOG ';
            return $q;
        }
    }
    /**вывод на экоан для льадки */
    private static function print(...$a){
        echo '<xmp>';
        foreach($a as $item){
            print_r($item);
        }
        echo '</xmp>';
    }
    /**вывод на экран только один раз или указанное число раз */
    private static function prin($var,...$a){
        if (!is_array($var)){
             $var = [$var,1];   
        }
        if (!isset(self::$print[$var[0]]))
            self::$print[$var[0]]=$var[1];

        if (self::$print[$var[0]]>0){
            self::$print[$var[0]]--;
            self::print(...$a);
            return true;
        }
        return false;
    }
}
?>