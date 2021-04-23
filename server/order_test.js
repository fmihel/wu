$(()=>{
    const $info = $('#info');
    const $result = $('#result');
    var _step = ORDER_TEST_START;
    $('#start').on('click',()=>{
        _step = ORDER_TEST_START;
        $result.text('старт тестирования');
        step();
    });
    $('#reculc').on('click',()=>{
        _step =  ORDER_TEST_START;
        $result.text('сброс тестов');
        reculc();
        
    })

    
    $info.text('countTest='+ORDER_TEST_COUNT);
    const url = window.location.origin+'/'+window.location.pathname+'?key='+key+'&';


    
    //step();

    async function step(){
        if (_step<ORDER_TEST_START+ORDER_TEST_COUNT){
            let response = await fetch(url+'step='+_step);
            if (response.ok){
                let text = await response.text();
                $result.append('<div>'+(_step-ORDER_TEST_START)+'/'+ORDER_TEST_COUNT+': '+text+'</div>');
                _step++;
                await step();
            }else{
                console.error(response.status);
            }
            
        }
    };

    async function reculc(){
        if (_step<ORDER_TEST_START+ORDER_TEST_COUNT){
            let response = await fetch(url+'reculcTest='+(_step-ORDER_TEST_START));
            if (response.ok){
                let text = await response.text();
                $result.append('<div>'+(_step-ORDER_TEST_START)+'/'+ORDER_TEST_COUNT+': '+text+'</div>');
                _step++;
                await reculc();
            }else{
                console.error(response.status);
            }
            
        }
    };

});