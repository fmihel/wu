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
    /* 0*/array('table'=>'','field'=>'SRCE_ID','media_kind'=>3),
    /* 1*/array('table'=>'K_CHAPTER',     'field'=>'ID_K_CHAPTER','is_chapter'=>true,'media_kind'=>2),
    /* 2*/array('table'=>'K_MODEL',       'field'=>'ID_K_MODEL',  'is_chapter'=>false,'media_kind'=>1),
    /* 3*/array('table'=>'',              'field'=>''),
    /* 4*/array('table'=>'',              'field'=>''),
    /* 5*/array('table'=>'',              'field'=>''),
    /* 6*/array('table'=>'K_CHAPTER',     'field'=>'ID_K_CHAPTER', 'is_chapter'=>true,'media_kind'=>2),
    /* 7*/array('table'=>'K_CHAPTER',     'field'=>'ID_K_CHAPTER', 'is_chapter'=>true,'media_kind'=>2),
    /* 8*/array('table'=>'TX_SECTION',    'field'=>'ID_TX_SECTION','media_kind'=>3),
    /* 9*/array('table'=>'TX_SET',        'field'=>'ID_TX_SET','media_kind'=>3),
    /*10*/array('table'=>'J_FOLDER',      'field'=>'ID','media_kind'=>3),
    /*11*/array('table'=>'J_SET',         'field'=>'ID','media_kind'=>3),
    
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

$RUS_BUK = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я'];
$ENG_BUK = ['a','b','v','g','d','e','e','g','z','i','i','k','l','m','n','o','p','r','s','t','u','f','h','c','c','s','s','' ,'' ,'' ,'e','u','y'];

class TREE_GENERATE{
    private static $coding = 'utf8';

    public static function create($saveToFile = false){
        try{
            $out = [];
            
            $q = 'select * from CTLG_NODE where ID_PARENT = 0 and  ARCH<>1 order by NOM_PP';
            $ds = base::dsE($q,'deco',self::$coding);
            
            $row = [];
            while(base::by($ds,$row)){
                $out[] = self::_create($row,'');
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
            
                $json = ARR::to_json($out);
                file_put_contents($saveToFile,'var catalog2='.$json.';');
            
            }else
                return $out;
        }catch(Exception $e){
            
            throw new Exception($e->getMessage());

        }
        
    }
    
    private static function _create($node,$parent){
        global $SRCE_KIND;
        global $ICONS;
        
        $ID     = $node['SRCE_ID'];
        
        $kind   = $SRCE_KIND[$node['SRCE_KIND']];
        $FIELD  = $kind['field'];
        $TABLE  = $kind['table'];
                
        $out           = [];
        $out['id']     =   $node['ID_CTLG_NODE'];
        $out[$FIELD]   =   $ID;
        $out['table']  =   $TABLE;
                
        $out['caption']    =   self::stringCorrect($node['CAPTION']);
        $out['icon']       =   $ICONS[$node['ICON_IND']];
        $out['SRCE_KIND']  =   $node['SRCE_KIND'];
        
        $out['hash']       =   self::translit($parent,$node);
                
        $out['media'] = self::_get_media( ($ID!='0'?$ID:$node['ID_CTLG_NODE']) ,COMMON::get($kind,'media_kind',''));
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
        $q = 'select * from CTLG_NODE where ID_PARENT = '.$node['ID_CTLG_NODE'].' and  ARCH<>1 order by NOM_PP';
        $ds = base::dsE($q,'deco',self::$coding);
        $row = [];
        while(base::by($ds,$row)){
            $child[]=self::_create($row,$out['hash']);
        }
        
        if ($node['SRCE_KIND']==0){
            if (count($child)===0)
                $out['viewAs']='gallery';      
        }else if (($node['SRCE_KIND']==1)||($node['SRCE_KIND']==2)||($node['SRCE_KIND']==6)||($node['SRCE_KIND']==7)){
            $out['IS_CHAPTER']          =   ($kind['is_chapter']?1:0);
            $out = array_merge($out,self::karniz($node));
        }else if (($node['SRCE_KIND']==8)||($node['SRCE_KIND']==9)){
            $out = array_merge($out,self::tkani($node));
        }else if (($node['SRCE_KIND']==10)||($node['SRCE_KIND']==11)){
            $out = array_merge($out,self::jaluzi($node));                    
        }                    

        if (count($child)>0)
            $out['child'] = $child;
        //-------------------------------------------------------------------------------------------------------
        
        return $out;
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
}
?>