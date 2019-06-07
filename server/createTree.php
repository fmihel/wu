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


define('SRCE_MODEL',2);


class CREATE_TREE_UTILS {
    
    
    public static function SAVE_ALL($path=false){
        
        if (!$path) $path = __DIR__;
        
        $catalog = array();
        $q = 'select * from CTLG_SUBSET where ARCH<>1';
        $ds = base::ds($q,'deco');
        
        if ($ds){
            while(base::by($ds,$row)){
                
                $data = self::GENERATE($row['ID_CTLG_SUBSET']);
                $data = $data['data'];
                
                $name = 'other_'.$row['ID_CTLG_SUBSET'];
                if ($row['ACCESS_KIND']==0)
                    $name = 'site';
                else if ($row['ACCESS_KIND']==1)
                    $name = 'dealers';
                
                $catalog[$name] = array('ID_CTLG_SUBSET'=>$row['ID_CTLG_SUBSET'],'data'=>$data);
                //array_push($catalog,array($name=>array('ID_CTLG_SUBSET'=>$row['ID_CTLG_SUBSET'],'data'=>$data)));
                
            }
            
            $karniz = self::CREATE_SEPARATELY('karniz');
            $catalog['karniz'] = array('data'=>$karniz['data']);
            
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);
            
        $file = $path.'/catalog.js';
    
        $json = ARR::to_json($catalog);
        file_put_contents($file,'var catalog='.$json.';');

        
        
        
        $full = CREATE_FULL_TREE_UTILS::GENERATE();
        $file = $path.'/full_catalog.js';
        $json = ARR::to_json($full['data']);
        file_put_contents($file,'var full_catalog='.$json.';');
        
        /*
        $file = __DIR__.'/catalog_tree.php';
        $php = ARR::to_php_code($catalog,true);
        file_put_contents($file,'<?php $CATALOG_TREE='.$php.'; ?>');
        */
        return array('res'=>1);
        
    }
    
    public static function SAVE($to,$data){
        $file = __DIR__.'/data.js';
        $content = file_get_contents($file);

        $json = mb_substr($content,mb_strpos($content,'{'));
        $php = ARR::from_json($json);
            

        $php[$to] = $data;
            
        $json = ARR::to_json($php);

        file_put_contents($file,'var catalog='.$json.';');
        
    }

    public static function GENERATE($id_ctlg_subset){
        global $ICONS;
        
        $out = array();
        
        $q = '  
        select 
            * 
        from
            CTLG_SUBSET_NODE csn
            join 
            CTLG_NODE cn
                on csn.ID_CTLG_NODE = cn.ID_CTLG_NODE
        where 
            csn.ID_CTLG_SUBSET = '.$id_ctlg_subset.'
            and
            cn.LEVEL_NODE = 0
            and
            csn.ARCH<>1
            and
            cn.ARCH<>1
        order by 
            cn.NOM_PP
        ';
        

        $ds = base::ds($q,'deco','UTF8');
        
    
        if ($ds){
            
            while(base::by($ds,$row)){
                
                $child = self::child($row['ID_CTLG_NODE'],$id_ctlg_subset);
                $data = array(
                    'caption'=>self::stringCorrect($row['CAPTION']),
                    //'ICON_IND'=>$row['ICON_IND'],
                    'icon'=>$ICONS[$row['ICON_IND']],
                    'id'=>$row['ID_CTLG_NODE']
                );  
                
                if (count($child)>0)
                    $data['child']=$child;
                    
                array_push($out,$data);
                
            }
            
        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        //---------------------------------------
        
        $data = array(
            'caption'=>'Личный кабинет',
            'icon'=>'file',
                 
            'id'=>'main_page',
            'viewAs'=>'mainPage',
            
        );  
        array_push($out,$data);
        
        //---------------------------------------
        


        return array('res'=>1,'data'=>$out);

    }
    
    public static function CREATE_SEPARATELY($kind){
        
        global $ICONS;
        $out = array();
        
        //$kind = 'tkani';
        $id = self::getRootIdByKind($kind);
        $id_ctlg_subset = 2;
        
        $q = '  
        select 
            * 
        from
            CTLG_SUBSET_NODE csn
            join 
            CTLG_NODE cn
                on csn.ID_CTLG_NODE = cn.ID_CTLG_NODE
        where 
            csn.ID_CTLG_SUBSET = '.$id_ctlg_subset.'
            and
            cn.LEVEL_NODE = 0
            and
            csn.ARCH<>1
            and
            cn.ARCH<>1
            and
            cn.ID_CTLG_NODE = '.$id.'
        order by 
            cn.NOM_PP
        ';
        
        //_LOG("id = $id ",__FILE__,__LINE__);
    
        $ds = base::ds($q,'deco','UTF8');
        
    
        if ($ds){
            $row = base::row($ds);
            $out = self::child2($row['ID_CTLG_NODE'],$id_ctlg_subset,$kind);

        }else
            _LOG("Error [$q]",__FILE__,__LINE__);

        
        
         return array('res'=>1,'data'=>$out);
    }
    
    public static function stringCorrect($str){
        
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
    
    private static function child($id_parent,$id_ctlg_subset){
        global $ICONS;
        global $SRCE_KIND;
        
        $out = array();
        
        $q = '  
        select 
            * 
        from
            CTLG_SUBSET_NODE csn
            join 
            CTLG_NODE cn
                on csn.ID_CTLG_NODE = cn.ID_CTLG_NODE
        where 
            csn.ID_CTLG_SUBSET = '.$id_ctlg_subset.'
            and
            cn.ID_PARENT = '.$id_parent.'
            and
            csn.ARCH<>1
            and
            cn.ARCH<>1
        order by 
            cn.NOM_PP
        ';        
        
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
                $node['table']                =   $TABLE;
                
                $node['caption']    =   self::stringCorrect($row['CAPTION']);
    
                $child = self::child($row['ID_CTLG_NODE'],$id_ctlg_subset);
                if (count($child)>0)
                    $node['child'] = $child;
                
                //$node['ICON_IND']   =   $row['ICON_IND'];
                $node['icon']       =   $ICONS[$row['ICON_IND']];
                $node['SRCE_KIND']    =   $row['SRCE_KIND'];
                
                $node['media'] = self::_get_media($ID,$kind['media_kind']);
                
                
                if ($row['SRCE_KIND']==0){
                        if (count($child)===0)
                            $node['viewAs']='gallery';      
                }else if (($row['SRCE_KIND']==1)||($row['SRCE_KIND']==2)||($row['SRCE_KIND']==6)||($row['SRCE_KIND']==7)){
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
    
    private static function child2($id_parent,$id_ctlg_subset,$akind){
        
        global $ICONS;
        global $SRCE_KIND;
        
        $out = array();
        
        $q = '  
        select 
            * 
        from
            CTLG_SUBSET_NODE csn
            join 
            CTLG_NODE cn
                on csn.ID_CTLG_NODE = cn.ID_CTLG_NODE
        where 
            csn.ID_CTLG_SUBSET = '.$id_ctlg_subset.'
            and
            cn.ID_PARENT = '.$id_parent.'
            and
            csn.ARCH<>1
            and
            cn.ARCH<>1
        order by 
            cn.NOM_PP
        ';        
        
        $ds = base::ds($q,'deco','UTF8');
        
        if ($ds){
            while(base::by($ds,$row)){
                
                $kind = $SRCE_KIND[$row['SRCE_KIND']];
                
                $ID     = $row['SRCE_ID'];
                $FIELD  = $kind['field'];
                $TABLE  = $kind['table'];
                
                $node = array();
                $node['id']         =   $row['ID_CTLG_NODE'];
                $node[$FIELD]       =   $ID;
                $node['table']      =   $TABLE;
                
                $node['caption']    =   self::stringCorrect($row['CAPTION']);
    
                $child = self::child2($row['ID_CTLG_NODE'],$id_ctlg_subset,$akind);
                
                    if (count($child)>0)
                        $node['child'] = $child;
                
                    //$node['ICON_IND']   =   $row['ICON_IND'];
                    $node['icon']       =   $ICONS[$row['ICON_IND']];
                    $node['SRCE_KIND']    =   $row['SRCE_KIND'];
                
                    //$node['media'] = self::_get_media($ID,$kind['media_kind']);
                
                
                    if ($row['SRCE_KIND']==0){
                            if (count($child)===0)
                                $node['viewAs']='gallery';      
                    }else if (($row['SRCE_KIND']==1)||($row['SRCE_KIND']==2)||($row['SRCE_KIND']==6)||($row['SRCE_KIND']==7)){
                        $node['IS_CHAPTER']          =   ($kind['is_chapter']?1:0);
                        $node = array_merge($node,self::karniz($row));
                    }else if (($row['SRCE_KIND']==8)||($row['SRCE_KIND']==9)){
                        $node = array_merge($node,self::tkani($row));
                    }else if (($row['SRCE_KIND']==10)||($row['SRCE_KIND']==11)){
                        $node = array_merge($node,self::jaluzi($row));                    
                    }                    
                    if (self::srceKindToKind($row['SRCE_KIND'])===$akind)
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
        
        
        
        return (base::value($q,'PRICELIST_KIND',1,'deco')==2?'B':'A');
        /*
        if (MYSQL::Assigned($ds))
            return ($ds->FieldByName('PRICELIST_KIND')==2);
        return 'A';


        return self::priceBTovarList($id,$is_chapter,true)>0?'B':'A';
        */
    }
    
    private static function _get_media($id,$OWNER_KIND){
        
        $view = array();
        $download = array();
        $print = array();
        
        $OWNER_ID = $id;
        //$OWNER_KIND = ($is_chapter?2:1);
        
        $q = "select ID_C_MEDIA_FILE,CAPTION,PATH_WWW,PROCESSING_KIND from C_MEDIA_FILE where OWNER_ID = $OWNER_ID and OWNER_KIND = $OWNER_KIND and ARCH<>1 order by PROCESSING_KIND,NOM_PP ";
        if ($id=='362'){
            //_LOG("[$q]",__FILE__,__LINE__);
    
        }
        $PROCESSING_KIND = -1;
        $ds = base::ds($q,'deco','UTF8');
        if ($ds){
            while(base::by($ds,$row)){
                
                $row['CAPTION'] = self::stringCorrect($row['CAPTION']);
                if ($PROCESSING_KIND!==$row['PROCESSING_KIND'])
                    $PROCESSING_KIND = $row['PROCESSING_KIND'];
                
                $row['PATH_WWW'] = HTTP_MEDIA.$row['PATH_WWW'];
                //$row['PATH_WWW'] = 'path_ww';
                if ($PROCESSING_KIND == 1)
                    array_push($view,$row['PATH_WWW']);
                elseif ($PROCESSING_KIND == 2)
                    array_push($download,$row);
                elseif ($PROCESSING_KIND == 3)
                    array_push($print,$row);                    
                    
            }
        }else{
            //_LOG("Error [$q]",__FILE__,__LINE__);
        }
        $out = array();
        if (count($view)>0)
            $out['gallery'] = $view;
        
        if (count($download)>0)
            $out['download'] = $download;
            
        if (count($print)>0)
            $out['print'] = $print;
        
        return $out;            
        
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
            
        return base::value($q,'ID_K_TOVAR','-1','deco','UTF8');
        

    }
    /** 
    * возвращает ID_CTLG_NODE для определенного типа каталога
    * kind = karniz|jaluzi|tkani
    */ 
    private static function getRootIdByKind($kind){
        $q = '
        select distinct 
            cn1.ID_CTLG_NODE
        from 
                CTLG_NODE cn1 
            join 
                CTLG_NODE cn2
            on 
                cn1.ID_CTLG_NODE = cn2.ID_PARENT
        where
            cn1.LEVEL_NODE = 0
            and
            cn1.ARCH<>1
            and
            cn2.ARCH<>1
            and
            cn2.SRCE_KIND IN ';
            
        if (($kind==='karniz') || ($kind==1))
            $q.='(1,2,6,7)';
            
        if (($kind==='tkani') || ($kind==2))
            $q.='(8,9)';
            
        if (($kind==='jaluzi') || ($kind==3))
            $q.='(10,11)';
            
        return base::val($q,-1,'deco');       

    
    }
    

    private static function srceKindToKind($srce_kind){
        
        $karniz = array(1,2,6,7);
        $tkani  = array(8,9);
        $jaluzi = array(10,11);
        
        if (array_search($srce_kind,$karniz)!==false)  return 'karniz';
        if (array_search($srce_kind,$tkani)!==false)   return 'tkani';
        if (array_search($srce_kind,$jaluzi)!==false)  return 'jaluzi';
        
        return -1;
        
    }
    
}


?>