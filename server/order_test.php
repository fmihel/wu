<?php
/** ORDER_TEST - тестирование заказов. 
 *  
 */
//-------------------------------
/** куда отправлять, если ошибка (пока в заказе жалюзи) */
//WS_CONF::DEF('emails-for-system-report',['fmihel76@gmail.com']);
WS_CONF::DEF('emails-for-system-report',[]);
//-------------------------------
/** подключение доп модулей */
WS_CONF::DEF('require_order_test',[
    '../../dev/source/common/order_const.php',
    '../../dev/source/tables/tables.php',
    '../../dev/source/common/common.php',
    '../../dev/source/common/order.php',
    '../../dev/source/common/orders.php',
    '../../dev/source/common/order_jaluzi.php',
    '../../dev/source/common/jaluzi.php',
]);
$require_order_test = WS_CONF::GET('require_order_test');
foreach($require_order_test as $file)
    require_once $file;
//-------------------------------
/** иммитация сессии */
class session {
    static $data = [
        'ID_USER'=>-1,
        'PRICE_VIEW_TYPE'=>1,
        'KOEF_JALUZI'=>1,
        'ID_DEALER'=>21039,
    ];
    static $enable = true;
}
//-------------------------------

class ORDER_TEST{
    /**  кол-во заказов для тестирования */
    public static function count(){
        try {
            return base::valE('select count(ID_ORDER) from ORDERS where FOR_TEST=1 and DELETED=0',0,'deco');
        } catch (\Exception $e) {
            error_log('Exception ['.__FILE__.':'.__LINE__.'] '.$e->getMessage());
        };
        return 0;
    }
    /** тестирование i-го заказа, результаты уйдут на почту указанную в конфиге в emails-for-system-report */
    public static function step($i){

        try {

            $orders = base::rowsE('select * from ORDERS where FOR_TEST =1  and DELETED=0 order by ID_ORDER','deco');
            $order = $orders[$i];
            $test = self::test($order);
            
            if (COMMON::get($test,'result','msg','') != '' ){

                $test=array_merge(['ID_ORDER'=>$order['ID_ORDER']],$test['result']);
                $msg = $test['msg'];
                unset($test['msg']);
                
                $out = ARR::to_json($test,true,0,['left'=>'&nbsp;&nbsp;&nbsp;&nbsp;','cr'=>'<br>']);
                
                COMMON_UTILS::sendReportToAdmin([
                    'msg'=>'Заказа N '.$order['NOM_ORDER'].' не прошел тест.<br>'.$msg.'<br>'.$out
                ]);
            };
       

        } catch (\Exception $e) {
            error_log('Exception ['.__FILE__.':'.__LINE__.'] '.$e->getMessage());
        };
    }
    /** запуск теста*/
    private static function test($param=[]){
        try {
            
            $out = [];

            $p = array_merge([  
                'ID_ORDER'=>6992,
                'ID_ORDER_KIND'=>3
            ],$param);
            
            if (!$p['ID_ORDER']){
                throw new Exception("need ID_ORDER in ");
            }

            // создаем копию заказа ---------------------
            $copy = ORDER::crCopy($p['ID_ORDER']);
            if ($copy['res']==0)
                throw new Exception("ошибка создания копии");

            // пометим заказа к удалению, на случай если что-то пойдет не так, то в в след разах его можно будет удалить
            ORDER::delete($copy['ID_ORDER'],['only-select'=>true]);

            // пересчитываем копию -----------------------    
            if ($p['ID_ORDER_KIND'] == 3){ // для жалюзи
                $original = ORDER::load($p['ID_ORDER'])['products'];
                $data = ORDER::load($copy['ID_ORDER']);
                $reculc = ORDER::update($data,['reculc'=>1])['data'];
                // сравниваем оригинал и копию ---------------
                $eqResult = self::jeqProducts($original,$reculc);
                $out = ['original'=>$original[0]['ctrl'],'reculc'=>$reculc[0]['ctrl'],'result'=>$eqResult];
            };
            // удаляем копию -----------------------------
            ORDER::delete($copy['ID_ORDER']);

            return array_merge(['res'=>1],$out);

        } catch (\Exception $e) {
            error_log('Exception ['.__FILE__.':'.__LINE__.'] in ORDER_TEST::test('.print_r($param,true).') '.$e->getMessage());

            return ['res'=>0,'msg'=>$e->getMessage()];
        };
        return ['res'=>0];
        
    }
    /** обновление всех тестов (сброс предыдущих состояний и фиксация новых) */
    public static function reculcAllTest(){
        try {
            $ds = base::dsE('select * from ORDERS where FOR_TEST =1  and DELETED=0 order by ID_ORDER','deco');
            while($order = base::read($ds))
                self::reculc($order);
            
        } catch (\Exception $e) {
            error_log('Exception ['.__FILE__.':'.__LINE__.'] '.$e->getMessage());
        };
    }
    /** пересчет заказа */
    private static function reculc($param=[]){
        $param = array_merge([
            'ID_ORDER'=>1
        ],$param);
        try {
            //$order = base::rowE('select * from ORDERS where ID_ORDER = '.$param['ID_ORDER'],'deco');
            $data = ORDER::load($param['ID_ORDER']);
            ORDER::update($data,['reculc'=>1,'reculcSum'=>1,'enableLock'=>0]);


        } catch (\Exception $e) {
            error_log('Exception ['.__FILE__.':'.__LINE__.'] '.$e->getMessage());
        };
    }
    /**  сравнение изделий */
    private static function jeqProducts($from,$to){
        
            $res = [];
            $msg = '';
            try {
                for($i=0;$i<count($from);$i++){
                    
                    $stepMsg = '';
                    $product = [
                        'TEMPLATE_NAME' =>base::valE('select NAME from J_FOLDER where ID='.$from[$i]['panel']['ID_J_FOLDER'],'undef','deco','UTF-8'),  
                        'ID_J_PRODUCT'  =>$from[$i]['panel']['ID_J_PRODUCT'],
                        'ID_J_FOLDER'   =>$from[$i]['panel']['ID_J_FOLDER'],
                    ];
                    //error_log(print_r($from,true));
                    $out = self::jeqProduct($from[$i],$to[$i]);
                    if ($out!==[]) {
                        if (count($out['out'])){
                            if (!isset($product['ctrls']))
                                $product['ctrls']=[];
                            $product['ctrls']= array_merge($product['ctrls'],$out['out']);
                        }
                        $stepMsg.=$out['msg'];
                    }
    
                    $tovarFrom = ORDER_JALUZI::getProductTovars($from[$i]['panel']['ID_J_PRODUCT']);
                    $tovarTo = ORDER_JALUZI::getProductTovars($to[$i]['panel']['ID_J_PRODUCT']);
                    $eqTov = self::jeqTovars($tovarFrom['data'],$tovarTo['data']);
    
                    if ( count($eqTov['out'])>0 || $eqTov['msg']!='' ){
                        if (!isset($product['tovars']))
                                $product['tovars']=[];
                        $product['tovars']=array_merge($product['tovars'],$eqTov['out']);
                        $stepMsg.=$eqTov['msg'];
                    }
                    if ($stepMsg!=''){
                        $res[]=$product;
                        $msg.=$stepMsg;
                    }
                }
                return ['products'=>$res,'msg'=>$msg];
            } catch (\Exception $e) {
                error_log('Exception ['.__FILE__.':'.__LINE__.'] '.$e->getMessage());
                return ['products'=>$res,'msg'=>$e->getMessage()];
            };
            
            
    }
    /**  сравнение двух изделий */
    private static function jeqProduct($from,$to){
            $out = [];
            $msg = '';
            try {
                // сравниваем по панелям
                if ($from['panel']['PROD_ITOG']['value']!=$to['panel']['PROD_ITOG']['value']){
                    $msg.='конечная стоимость PROD_ITOG ('.$from['panel']['PROD_ITOG']['value'].'!='.$to['panel']['PROD_ITOG']['value'].'), ';
                }
                // сравниваем по компонентам
                for($i=0;$i<count($from['ctrl']);$i++){
                    $tc = false;
                    for($j=0;count($to['ctrl']);$j++){
                        if ($to['ctrl'][$j]['id'] == $from['ctrl'][$i]['id']){
                            $tc = $to['ctrl'][$j];
                            break;
                        }
                    }
                    
                    if ($tc){
                        $res = self::jeqCtrl($from['ctrl'][$i],$tc);
    
                        if ($res){
                            $out[]=['from'=>$res['from'],'to'=>$res['to']];
                            $msg .= $res['msg'];
                        }
                    }
                }
                return ['out'=>$out,'msg'=>$msg];
            } catch (\Exception $e) {
                error_log('Exception ['.__FILE__.':'.__LINE__.'] '.$e->getMessage());
                return ['out'=>$out,'msg'=>$e->getMessage()];
            };
            
    }
    /** сравнение компонентов */
    private static function jeqCtrl($from,$to){
            $format = function ($paramName,$msg='') use ($from,$to) {
                return [
                    'msg'=>$from['caption'].' ID_J_ITEM('.$from['ID_J_ITEM'].'/'.$to['ID_J_ITEM'].') '.$msg.' '.$paramName.'('.$from[$paramName].'!='.$to[$paramName].'), ',
                    'from'=>$from,
                    'to'=>$to
                ];
            };
    
            if ( $from['visible'] == $to['visible'] ){
                if ( $from['define']!=='price' )
                    return false;
            }else
                return $format('visible');   
    
            if ($from['id']!==$to['id']){
                return $format('id','разные компоненты');
            }
    
            if ($from['type']!==$to['type']){
                return $format('type','разные типы компонентов');
            }
    
            if ($from['type']=='combo'){
                return false;
            }else{
                if ($from['value']!==$to['value'])
                    return $format('value','значение');
            };
            return false;
    }
    /** сравнение по товарам */
    private static function jeqTovars($from,$to){
            $out = [];
            $msg = '';

            $check = function ($table,$ft,$find,$paramName,$text='') use ($from,$to,&$out,&$msg) {
                $fromValue  = $table::val($ft,$paramName);
                $toValue = $table::val($find,$paramName);
                if ($fromValue != $toValue){
                    $out[] = [
                        'from'=>$from,
                        'to'=>$to
                    ];
                    $msg.= $text.' ID_J_RESULT='.$table::val($ft,'ID_J_RESULT').' '.$paramName.'('.$fromValue.'!='.$toValue.'), ';
                };
            };

            try {
                if (count($from)!=count($to)){
                    return ['out'=>['from'=>$from,'to'=>$to],'msg'=>'разное кол-во товаров,'];
                }
        
                for($i = 0;$i<count($from);$i++){
                    $ft = $from[$i];
                    $find = false;
                    for($j = 0;$j<count($to);$j++){
                        $tt = $to[$j];
                        if ($tt['ID_TOVAR'] == $ft['ID_TOVAR']){
                            $find=$tt;
                            break;
                        }
                    }
                    if (!$find)
                        return ['out'=>['from'=>$from,'to'=>$to],'msg'=>'товары не совпадают,'];
                    
                    $check('J_RESULT_TABLE',$ft,$find,'CNT');
                    $check('TOVAR_TABLE',$ft,$find,'NAME');
                    $check('TOVAR_TABLE',$ft,$find,'COLOR');
                    $check('TOVAR_TABLE',$ft,$find,'ART');
                    $check('COD_TABLE',$ft,$find,'COD');
                    $check('COD_TABLE',$ft,$find,'FULL_NAME');
                    
                }
                return ['out'=>$out,'msg'=>$msg];
                    
            } catch (\Exception $e) {
                error_log('Exception ['.__FILE__.':'.__LINE__.'] '.$e->getMessage());
                return ['out'=>[],'msg'=>$e->getMessage()];
            };
            
    }
}
?>