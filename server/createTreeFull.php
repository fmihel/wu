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

$SRCE_KIND = array(
    array('table'=>'','field'=>''),//0
    array('table'=>'K_CHAPTER',     'field'=>'ID_K_CHAPTER','is_chapter'=>true,'media_kind'=>2),//1
    array('table'=>'K_MODEL',       'field'=>'ID_K_MODEL',  'is_chapter'=>false,'media_kind'=>1),//2
    array('table'=>'',              'field'=>''),//3
    array('table'=>'',              'field'=>''),//4
    array('table'=>'',              'field'=>''),//5
    array('table'=>'K_CHAPTER',     'field'=>'ID_K_CHAPTER', 'is_chapter'=>true,'media_kind'=>2),//6
    array('table'=>'K_CHAPTER',     'field'=>'ID_K_CHAPTER', 'is_chapter'=>true,'media_kind'=>2),//7
    array('table'=>'TX_SECTION',    'field'=>'ID_TX_SECTION','media_kind'=>0),//8
    array('table'=>'TX_SET',        'field'=>'ID_TX_SET','media_kind'=>0),//9
    array('table'=>'J_FOLDER',      'field'=>'ID','media_kind'=>0),//10
    array('table'=>'J_SET',         'field'=>'ID','media_kind'=>0),//11
    
);
$ICONS = array(
    'folder',//0
    'folder',//1
    'folder',//2
    'folder',//3
    'folder',//4
    'folder',//5
    'file',//6
    'file',//7
    'file',//8
    'folder',//9
    'folder',//10
    
);
define('SRCE_MODEL',2);
*/

class CREATE_FULL_TREE_UTILS {
    
    public static function SAVE($to,$data){
        $file = __DIR__.'/catalog_full.js';
        $content = file_get_contents($file);

        $json = mb_substr($content,mb_strpos($content,'{'));
        $php = ARR::from_json($json);
            

        $php[$to] = $data;
            
        $json = ARR::to_json($php);

        file_put_contents($file,'var catalog='.$json.';');
        
    }

    public static function GENERATE(){
        
    
        $out = array();
        $q = 'select * from CTLG_NODE where ID_PARENT = 0 and  ARCH<>1 order by NOM_PP';
        $ds = base::ds($q,'deco','UTF8');
        
    
        if ($ds){
            
            while(base::by($ds,$row)){
                array_push($out,array(
                    'caption'=>$row['CAPTION'],
                    'child'=>self::child($row['ID_CTLG_NODE']),
                    'ICON_IND'=>$row['ICON_IND'],
                    'id'=>$row['ID_CTLG_NODE']
                ));
                
            }
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        return array('res'=>1,'data'=>$out);

    }
    private static function child($id_parent){
        global $ICONS;
        global $SRCE_KIND;
        
        $out = array();
        $q = "select * from CTLG_NODE where ID_PARENT = $id_parent and  ARCH<>1 order by NOM_PP";
        $ds = base::ds($q,'deco','UTF8');
        
        if ($ds){
            while(base::by($ds,$row)){
                
                $kind = $SRCE_KIND[$row['SRCE_KIND']];
                
                $ID     = $row['SRCE_ID'];
                $FIELD  = $kind['field'];
                $TABLE  = $kind['table'];
                
                $node = array();
                $node['id']                 =   $row['ID_CTLG_NODE'];
                $node[$FIELD]                =   $ID;
                
                
                $node['caption']    =   $row['CAPTION'];
                $node['child']      =   self::child($row['ID_CTLG_NODE']);
                //$node['ICON_IND']   =   $row['ICON_IND'];
                $node['icon']       =   $ICONS[$row['ICON_IND']];
                
                $node['media'] = self::_get_media($ID,$kind['media_kind']);
                
                if (($row['SRCE_KIND']==1)||($row['SRCE_KIND']==2)||($row['SRCE_KIND']==6)||($row['SRCE_KIND']==7)){
                    $node['IS_CHAPTER']          =   ($kind['is_chapter']?1:0);
                    $node = array_merge($node,self::karniz($row));
                }else if (($row['SRCE_KIND']==8)||($row['SRCE_KIND']==9)){
                    $node = array_merge($node,self::tkani($row));
                }else if (($row['SRCE_KIND']==10)||($row['SRCE_KIND']==11)){
                    $node = array_merge($node,self::jaluzi($row));                    
                }                    
                    
                array_push($out,$node);

            }
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

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
        $show_as = base::val($q,0,'deco');

        $out['viewAs']          =   self::_viewAs($show_as,$priceType);
        
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

    
    public static function KARNIZ2(){

        $data = array();
        
        XML_CATALOG_FAST::load(CATALOG_XML,-3);

        $q = '
        select 
            ID_K_CHAPTER,
            CAPTION, 
            ID_PARENT 
        from 
            K_CHAPTER 
        where 
            ARCH<>1 
                and 
            ID_AREA_ACT=1 
                and 
            ID_K_MODEL = 0 
                and  
            ID_PARENT = 0 
        order by NOM_PP';
        
        $ds = base::ds($q,'deco','UTF8');

        if ($ds){
            while(base::by($ds,$row)){
                $node = array('caption'=>$row['CAPTION'],'IS_CHAPTER'=>1,'ID'=>$row['ID_K_CHAPTER'],'child'=>array());

                $child = self::_chapters($row['ID_K_CHAPTER']);
                if (count($child)>0)
                        $node['child']=array_merge($node['child'],$child);
                
                $child = self::_models($row['ID_K_CHAPTER']);
                if (count($child)>0)
                        $node['child']=array_merge($node['child'],$child);


                array_push($data,$node);
            }
            
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        
        
        return $data;
        
           
    }
        
    private static function _chapters($id_k_chapter){
        
        //$data = self::_imagesKarnizFromXml($id_k_chapter,1);
        $up = self::_get_media($id_k_chapter,true);
        
        $data = array();
        
        $q = "
        select 
            ID_K_CHAPTER, 
            CAPTION, 
            ID_PARENT,
            SHOW_AS
        from 
            K_CHAPTER 
        where 
            ARCH<>1 
            and 
            ID_PARENT = $id_k_chapter
            
        order by NOM_PP";
        
        $ds = base::ds($q,'deco','UTF8');

        if ($ds){
            while(base::by($ds,$row)){
                
                $media = self::_get_media($row['ID_K_CHAPTER'],true);
                if (count($up['download'])>0)
                    $media = array_merge($up,$media);
                    
                $node    = array(
                    'caption'=>$row['CAPTION'],
                    'IS_CHAPTER'=>1,
                    'ID'=>$row['ID_K_CHAPTER'],
                    'child'=>array(),
                    'media'=>$media
                );
                    

                $child   = self::_chapters($row['ID_K_CHAPTER']);
                if (count($child)>0)
                    $node['child']=array_merge($node['child'],$child);

                $child = self::_models($row['ID_K_CHAPTER']);
                
                if (count($child)>0)
                        $node['child']=array_merge($node['child'],$child);
                

                if (count($node['child'])===0)
                    
                    $typePrice = self::_typePrice($row['ID_K_CHAPTER'],true);
                
                    if ($typePrice ==='B'){
                        $tovars = self::priceBTovarList($row['ID_K_CHAPTER'],true);
                        
                        if ($tovars){
                            while(base::by($tovars,$tovar)){
                                $node['child']=array(array(
                                    'caption'=>'Прайс '.$tovar['NAME'],
                                    'ID_K_TOVAR'=>$tovar['ID_K_TOVAR'],
                                    'IS_CHAPTER'=>1,
                                    'ID'=>$row['ID_K_CHAPTER'],
                                    "icon"=>"file",
                                    "viewAs"=> self::_viewAs($row['SHOW_AS'],$typePrice)
                                ));
                            }
                        }else
                            _LOG("Error [...]",__FILE__,__LINE__);

                    
                        
                    }else{
                        
                         
                        //$node['child']=array(array(
                        //        'caption'=>'_price_',
                        //        "icon"=>"file",
                        //        "viewAs"=> 'karniz'.$typePrice
                        //));
                    }
                                    
                array_push($data,$node);
            }
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        return $data;
    }
    
    private static function _chaptersInModel($id_k_model){
        
        $data = array();
        $up = self::_get_media($id_k_model,false);
        
        $q = "select 
            ID_K_CHAPTER, 
            CAPTION, 
            ID_PARENT,
            ID_K_MODEL,
            SHOW_AS
        from 
            K_CHAPTER 
        where 
            ARCH<>1 
            and 
            ID_K_MODEL = $id_k_model 
        order by NOM_PP";
            
        $ds = base::ds($q,'deco','UTF8');

        if ($ds){

            while(base::by($ds,$row)){

                $media = self::_get_media($row['ID_K_CHAPTER'],true);
                if (count($up['download'])>0)
                    $media = array_merge($up,$media);
                    
                $child   = self::_chapters($row['ID_K_CHAPTER']);
                
                if (count($child)>0){
                    $node    = array(
                        'caption'=>$row['CAPTION'],
                        'IS_CHAPTER'=>1,
                        'ID'=>$row['ID_K_CHAPTER'],
                        'child'=>$child,
                        'media'=>$media
                    );
                    array_push($data,$node);
                    
                }else{
                    
                    $typePrice = self::_typePrice($row['ID_K_CHAPTER'],true);
                
                    
                    if ($typePrice ==='B'){

                        $tovars = self::priceBTovarList($row['ID_K_CHAPTER'],true);
                        
                        if ($tovars){
                            while(base::by($tovars,$tovar)){
                                $node    = array(
                                    'caption'=>$tovar['NAME'],
                                    'IS_CHAPTER'=>1,
                                    'ID'=>$row['ID_K_CHAPTER'],
                                    'child'=>array(),
                                    'ID_K_TOVAR'=>$tovar['ID_K_TOVAR'],
                                    'icon'=>'file',
                                    "viewAs"=> self::_viewAs($row['SHOW_AS'],$typePrice),
                                    'media'=>$media
                                    );
                                array_push($data,$node);
                            }
                        }else
                            _LOG("Error [...]",__FILE__,__LINE__);
                    }else{
                        $node    = array(
                            'caption'=>$row['CAPTION'],
                            'IS_CHAPTER'=>1,
                            'ID'=>$row['ID_K_CHAPTER'],
                            'child'=>array(),
                            'icon'=>'file',
                            'viewAs'=> self::_viewAs($row['SHOW_AS'],$typePrice),
                            'media'=>$media
                        );
                        array_push($data,$node);
                    }    
                    
                    
                }
                
                
                /*
                            
                $node    = array('caption'=>$row['CAPTION'],'IS_CHAPTER'=>1,'ID'=>$row['ID_K_CHAPTER'],'child'=>array());
                $child   = self::_chapters($row['ID_K_CHAPTER']);
                //$child = array();
                
                if (count($child)>0)
                    $node['child']=array_merge($node['child'],$child);
                    

                if (count($node['child'])===0){
                    $typePrice = self::_typePrice($row['ID_K_CHAPTER'],true);
                        
                    
                    $node['icon']='file';
                    $node['viewAs'] = 'karniz'.$typePrice;
                }    
                array_push($data,$node);
                */
            }
            
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        return $data;
    }
    
    private static function _models($id_k_chapter){


        //$data = self::_imagesKarnizFromXml($id_k_chapter,1);
        $up = self::_get_media($id_k_chapter,true);
        
        
        
        
        $data = array();
        $q = "
        select 
            ID_K_MODEL,
            NAME,
            SHOW_AS
        from 
            K_MODEL 
        where 
            ARCH<>1 
            and 
            ID_K_CHAPTER = $id_k_chapter 
        order by NOM_PP";
        
        $ds = base::ds($q,'deco','UTF8');

        if ($ds){
            while(base::by($ds,$row)){
                
                
                $media = self::_get_media($row['ID_K_MODEL'],false);
                
                if (count($up['download'])>0)
                    $media = array_merge($up,$media);
                
                $node    = array(
                        'caption'=>$row['NAME'],
                        'IS_CHAPTER'=>0,
                        'ID'=>$row['ID_K_MODEL'],
                        'child'=>array(),
                        'media'=>$media
                        );

                $child = self::_chaptersInModel($row['ID_K_MODEL']);

                //$images = array();
                //$node['child']=array_merge($node['child'],$images);

                
                if (count($child)>0){

                     if (self::_hasTovar($row['ID_K_MODEL'],false)){
                        array_push($child,array(
                            'caption'=>'Прайс',
                            'IS_CHAPTER'=>0,
                            'ID'=>$row['ID_K_MODEL'],
                            'child'=>array(),
                            'icon'=>'file',      
                            'media'=>$media,
                            "viewAs"=>self::_viewAs(1,self::_typePrice($row['ID_K_MODEL'],false))
                    ));
                        
                    }
                    
                    $node['child'] = array_merge($node['child'],$child);
                    
                    

                }else{
                    

                    $typePrice = self::_typePrice($row['ID_K_MODEL'],false);
                    
                    
                    if ($typePrice ==='B'){
                        $tovars = self::priceBTovarList($row['ID_K_MODEL'],false);
                        
                        if ($tovars){
                            while(base::by($tovars,$tovar)){
                                
                                array_push($node['child'],array(
                                    'caption'=>'Прайс '.$tovar['NAME'],
                                    "icon"=>"file",
                                    'IS_CHAPTER'=>0,
                                    'ID'=>$row['ID_K_MODEL'],
                                    'ID_K_TOVAR'=>$tovar['ID_K_TOVAR'],
                                    "viewAs"=>self::_viewAs($row['SHOW_AS'],$typePrice)
                                ));
                                
                            }
                        }else
                            _LOG("Error [$q]",__FILE__,__LINE__);

                    
                        
                    }else
                        array_push($node['child'],array(
                            'caption'=>'Прайс',
                            "icon"=>"file",
                            'IS_CHAPTER'=>0,
                            'ID'=>$row['ID_K_MODEL'],
                            "viewAs"=>self::_viewAs($row['SHOW_AS'],$typePrice)
                        ));
                        
                    
                    //array_push($node['child'],$add ) ;
                }    
                
                    
                array_push($data,$node);
            };
            
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        return $data;
    }
    private static function _viewAs($SHOW_AS,$typePrice=''){
        
        if (($SHOW_AS==1)&&($typePrice!==''))
            return 'karniz'.$typePrice;
        
        if ($SHOW_AS==2)
            return 'gallery';
        
        return '';
        
    }
    private static function _get_media($id,$OWNER_KIND){
        
        $view = array();
        $download = array();
        $print = array();
        
        $OWNER_ID = $id;
        //$OWNER_KIND = ($is_chapter?2:1);
        
        $q = "select ID_C_MEDIA_FILE,CAPTION,PATH_WWW,PROCESSING_KIND from C_MEDIA_FILE where OWNER_ID = $OWNER_ID and OWNER_KIND = $OWNER_KIND and ARCH<>1 order by PROCESSING_KIND,NOM_PP ";
        if ($id=='362'){
            _LOG("[$q]",__FILE__,__LINE__);
    
        }
        $PROCESSING_KIND = -1;
        $ds = base::ds($q,'deco','UTF8');
        if ($ds){
            while(base::by($ds,$row)){
                
                if ($PROCESSING_KIND!==$row['PROCESSING_KIND'])
                    $PROCESSING_KIND = $row['PROCESSING_KIND'];
                
                $row['PATH_WWW'] = HTTP_MEDIA.$row['PATH_WWW'];
                if ($PROCESSING_KIND == 1)
                    array_push($view,$row['PATH_WWW']);
                elseif ($PROCESSING_KIND == 2)
                    array_push($download,$row);
                elseif ($PROCESSING_KIND == 3)
                    array_push($print,$row);                    
                    
            }
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);
        
        $out = array();
        if (count($view)>0)
            $out['gallery'] = $view;
        
        if (count($download)>0)
            $out['download'] = $download;
            
        if (count($print)>0)
            $out['print'] = $print;
        
        return $out;            
        
    }
    
    private static function _imagesKarnizFromXml($id,$is_chapter){
        
        $data = array();
        $find = XML_CATALOG_FAST::find($id,$is_chapter);
        for($i=0;$i<count($find);$i++){
            $fin=$find[$i];
                    
            //--------------------------------------------------------------
            $path = PDF_CATALOG_PATH.'JPG/'.APP::without_ext($fin['file']);
            $images = DIR::files($path,'jpg',false,true);
            $path = PDF_CATALOG_HTTP_PATH.'JPG/'.APP::without_ext($fin['file']).'/';
            for($j=0;$j<count($images);$j++)
                    $images[$j]=$path.$images[$j];
            //--------------------------------------------------------------
                        
            array_push($data,array(
                'caption'=>$fin['name'],
                "icon"=>"file",
                "file"=>PDF_HTTP_PATH.$fin["file"],
                        
                'IS_CHAPTER'=>$is_chapter,
                'ID'=>$id,
                        
                "viewAs"=>'gallery',
                "images"=>$images

            ));
        }
        
        return $data;   
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
        
        
        
        return (base::value($q,'PRICELIST_KIND',1,'deco')==2?'B':'A');
        /*
        if (MYSQL::Assigned($ds))
            return ($ds->FieldByName('PRICELIST_KIND')==2);
        return 'A';


        return self::priceBTovarList($id,$is_chapter,true)>0?'B':'A';
        */
    }
    /** проевряет, есть литовары привязаные к узлу */
    private static function _hasTovar($id,$is_chapter){
        $q = 'select count(ID_K_MODEL_TOVAR) C from K_MODEL_TOVAR where ARCH<>1 and '.($is_chapter?'ID_K_CHAPTER':'ID_K_MODEL').'='.$id;
        
        return (base::value($q,'C',0,'deco')>0);    
    }
    private static function priceBTovarList($id,$is_chapter,$onlyCount=false){
        // возвращает список товаров по которым будут создаваться матричные прайс-листы
            
        if ($onlyCount)
            $q = 'select count(distinct t.ID_K_TOVAR) C ';
        else
            $q = 'select distinct t.ID_K_TOVAR,t.NAME ';
        
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
        //_LOG("[$q]",__FILE__,__LINE__);

        //if (!base::ds($q,'deco'))
        //    _LOG("ERROR[$q]",__FILE__,__LINE__);
        
        
        if ($onlyCount) 
            return intval(base::value($q,'C',0,'deco'));
        else 
            return base::ds($q,'deco','UTF8');
        

    }

    private static function JALUZ2I(){
        $data =  array();
        
        $q = 'SELECT * FROM `J_FOLDER` WHERE ID_PARENT = 0 and ARCH <> 1 order by ORD';
        $ds = base::ds($q,'deco','UTF8');
        if ($ds){
            while(base::by($ds,$row)){
                $node = array('caption'=>$row['NAME'],'ID_J_FOLDER'=>$row['ID'],'child'=>array());
                
                $child = self::_j_set($row['ID']);
                if (count($child)>0)
                        $node['child']=array_merge($node['child'],$child);

                $child = self::_j_folder($row['ID']);
                if (count($child)>0)
                        $node['child']=array_merge($node['child'],$child);

                
                array_push($data,$node);
            }
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        return $data;
    }
    
    private static function _j_folder($id_j_folder){
        $data = array();

        $q = "select * from J_FOLDER where ID_PARENT=$id_j_folder and ARCH<>1 order by ORD";
        $ds = base::ds($q,'deco','UTF8');

        if ($ds){
            while(base::by($ds,$row)){
                
                $node    = array('caption'=>$row['NAME'],'ID_J_FOLDER'=>$row['ID'],'child'=>array(),'icon'=>$row['VIEW_AS']==0?'folder':'file');
                
                if ($row['VIEW_AS']==0){
                    $child = self::_j_set($row['ID']);
                    if (count($child)>0)
                            $node['child']=array_merge($node['child'],$child);
                

                    $child = self::_j_folder($row['ID']);
                    if (count($child)>0)
                            $node['child']=array_merge($node['child'],$child);
                }else            
                    $node['viewAs']='jaluzi';


                
                array_push($data,$node);
            }
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        return $data;
    }

    private static function _j_set($id_j_folder){
        $data = array();

        $q = "select * from J_SET where ID_J_FOLDER=$id_j_folder and PRINT=1 and ARCH<>1 order by ORD";
        $ds = base::ds($q,'deco','UTF8');

        if ($ds){
            while(base::by($ds,$row)){
                
                $node    = array('caption'=>$row['NAME'],'ID_J_SET'=>$row['ID'],'icon'=>'file','child'=>array(),'viewAs'=>'jaluzi');

                //$child   = self::_chapters($row['ID_K_CHAPTER']);
                //if (count($child)>0)
                //    $node['child']=array_merge($node['child'],$child);

                array_push($data,$node);
            }
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        return $data;
    }
    
    private static function TKANI2(){
        $data =  array();
        
        $q = 'select * FROM `TX_SECTION` where  ARCH<>1 order by ORDER_NUM';
        $ds = base::ds($q,'deco','UTF8');
        if ($ds){
            while(base::by($ds,$row)){
                $node = array('caption'=>$row['CAPTION'],'ID_TX_SECTION'=>$row['ID_TX_SECTION'],'child'=>array());
                
                $child = self::_tx_set($row['ID_TX_SECTION']);
                if (count($child)>0)
                        $node['child']=array_merge($node['child'],$child);
                /*
                $child = self::_j_set($row['ID']);
                if (count($child)>0)
                        $node['child']=array_merge($node['child'],$child);
                */
                array_push($data,$node);
            }
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        return $data;
    }    
    
    private static function _tx_set($id_tx_section){
        $data = array();

        $q = "select * FROM `TX_SET` where ID_TX_SECTION = $id_tx_section and  ARCH<>1 order by ID_TX_SET";
        $ds = base::ds($q,'deco','UTF8');

        if ($ds){
            while(base::by($ds,$row)){
                
                $node    = array(
                    'caption'=>$row['CAPTION'],
                    'id'=>'tx'.$row['ID_TX_SET'],
                    'ID_TX_SET'=>$row['ID_TX_SET'],
                    'icon'=>'file','child'=>array(),
                    "viewAs"=> 'tkani'
                    );

                //$child   = self::_chapters($row['ID_K_CHAPTER']);
                //if (count($child)>0)
                //    $node['child']=array_merge($node['child'],$child);

                array_push($data,$node);
            }
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        return $data;
    }
    
    
}

class XML_CATALOG_FAST{
    static $XML_CATALOG_STR = '';
    
    private static function getParams($str,$tag){
        // GetParams - Получение параметров из тега XML        
        $res = array();        

        // выделяем строку параметров        
        $str = str_replace(chr(13).chr(10),'',$str);
         
        // /^<msg]*<msg([^<]*)>(.*)/'
        $templ = '/[^<'.$tag.']*<'.$tag.'([^<]*)>(.*)/';        

        if (preg_match($templ,$str,$match))
        {
            // выделяем параметры и значения в хеш
            $params = $match[1];
            $templ = '/[ ]*([^=\"]*)[ ]*=[ ]*"([^=\"]*)"/';            
            if (preg_match_all($templ,$params,$match))
            {
                $len = count($match[0]);
                for($i = 0;$i<$len;$i++)
                {
                    $mean = $match[1][$i];
                    $value = $match[2][$i];
                    $res[$mean] = $value;                     
                };                             
            };            
        };
        return $res;                    
    }
    /** 
     * предварительная загрузка и обрезка текста XML саталога 
     */
    public static function load($xmlFileName,$catalog_id){
        $str =  file_get_contents($xmlFileName); 
        
        $begin = '<catalog id="'.$catalog_id.'"';
        $end = '</catalog>';
        
        
    
        $pos = strpos($str,$begin);
        

        
        if ($pos!==false){
            $str = substr($str,$pos);

            $pos = mb_strpos($str,$end);
            if ($pos!==false)
                $str = substr($str,0,$pos+mb_strlen($end));
        }
        
        
        self::$XML_CATALOG_STR = $str;
        
        
    }
    /** 
     * поиск дополнительных узлов в xml каталоге по id и is_chapter:numer
    */
    public static function find($id,$is_chapter){
        
        $str = self::$XML_CATALOG_STR;
        $off = 0;
        $res = array();
        for($loop=0;$loop<100;$loop++){
            
            $matches = null;
            if (preg_match('/<node.*is_chapter="'.$is_chapter.'"\\sid="'.$id.'"/',$str, $matches,PREG_OFFSET_CAPTURE,$off)===1){
                
                $node = $matches[0][0].'/>';
                $prm = self::getParams($node,'node');
                
                if ($prm['file']!==''){
                    
                    $cbool = true;
                    for($j =0;$j<count($res);$j++){
                        
                        if ($prm['file'] === $res[$j]['FILE']){
                            $cbool=false;
                            break;
                        };
                        
                    };
                    
                    if ($cbool)
                        array_push($res,$prm); //;$prm = array(name:string,file:string)
                }
            
                $off = $matches[0][1]+1;
            }else
                break;
        };
        
        return $res;
    }
    
}
?>