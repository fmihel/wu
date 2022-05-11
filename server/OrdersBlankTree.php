<?php
const NOT_USE_FIELD = [
    'LAST_MODIFY','DATE_CREATE','CAPTION','ID_CTLG_NODE'
];
class OrdersBlankTree{
    /** обновление файла  */
    public static function update(){
        
        $out = '';
        //--------------------------------------------------------------
        //$q = 'select * from ORDERS_BLANK_TREE where ID_PARENT = 0 and DEBUG_MODE<>1 ORDER BY NOM_PP';
        $q = 'SELECT * from ORDERS_BLANK_TREE where ID_PARENT = 0 ORDER BY NOM_PP';
        $ds = base::ds($q,'deco','utf8');
        if ($ds){
            while($row = base::read($ds)){
                $props = self::propToJs('id',$row['ID_ORDER_BLANK_TREE']);
                $props.= self::propToJs('caption',$row['CAPTION']);

                foreach($row as $name=>$value){
                    if (array_search($name,NOT_USE_FIELD) === false)
                        $props.=self::propToJs($name,$value);
                };
                
                $child = self::child($row['ID_ORDER_BLANK_TREE']);
                $props.=self::propToJs('icon',self::icon($row, ( $child ? true : false ) ));
                if ($child)
                    $props.='child:'.$child;

                $out.="{".$props."},";
            }
        }
        $out = '['.$out.']';
        //--------------------------------------------------------------
        
        $filename = WS_CONF::GET('orders-blank-tree');
        file_put_contents($filename,'var orders_blank_tree='.$out.';');
        //--------------------------------------------------------------

    }
    private static function child($ID_ORDER_BLANK_TREE):string{
        $out = '';
        $q = 'SELECT * from ORDERS_BLANK_TREE where ID_PARENT = '.$ID_ORDER_BLANK_TREE.' ORDER BY NOM_PP';
        //$q = 'select * from ORDERS_BLANK_TREE where ID_PARENT = '.$ID_ORDER_BLANK_TREE.'  and DEBUG_MODE<>1 ORDER BY NOM_PP';
        $ds = base::ds($q,'deco','utf8');
        if ($ds){
            while($row = base::read($ds)){
                $props = self::propToJs('id',$row['ID_ORDER_BLANK_TREE']);
                $props.= self::propToJs('caption',$row['CAPTION']);
                foreach($row as $name=>$value){
                    if (array_search($name,NOT_USE_FIELD) === false)
                        $props.=self::propToJs($name,$value);
                };
                
                $child = self::child($row['ID_ORDER_BLANK_TREE']);
                $props.=self::propToJs('icon',self::icon($row, ( $child ? true : false ) ));

                if ($child)
                    $props.='child:'.$child;
                

                $out.="{".$props."},";
            }
        }
        return $out ? '['.$out.']':'';
    }

    
    private static function propToJs($name,$value){
        if (is_numeric($value))
            return $name.':'.$value.','; 
        return $name.':'.'"'.$value.'",';
    }
    private static function icon($row,$last){
        return '';
    }
}

?>