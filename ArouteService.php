<?php
/**
引入文件
 */

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Worker;
use Workerman\Timer;
use Workerman\Protocols\Http\Response;
require 'Workerman/Autoloader.php';
require 'config/serverConfig.php';//服务器配置
/**
引入文件End
*/
/**
全局变量
 */
$the=[
    "StaticRoute"=>[],//静态路由表
    "DynamicsRoute"=>[],//动态路由表
    "GrappleList"=>[],//抓取其他路由的抓取URL列表
    "Mode"=>null,//路由模式
];
$logFile=null;
$staticRouteFile=null;
$grappleListFile=null;
/**
全局变量End
*/

if(!file_exists('config')){
    mkdir('config',0777,true);echo "文件夹 'config' 创建成功;";
}
$logFile=fopen('./config/z-'.date('Y-m-j').'.txt','a+');
$staticRouteFile=fopen('./config/'._StaticRouteName,'a+');//如果没有路由表则创建
fclose($staticRouteFile);
$grappleListFile=fopen('./config/'._GrappleListName,'a+');//如果没有抓取表则创建
fclose($grappleListFile);


/**
函数
 */
function startSetting(){
    global $the;
    $the["Mode"]=_DefaultMode;
    getStaticRoute();
    //grappleRoute();
}

function sendEmptyString($connection){
    $response = new Response();// 创建一个新的Response对象
    $response->withStatus(200);// 设置HTTP状态码
    $response->withHeader('Access-Control-Allow-Origin', '*');// 设置响应头
    $response->withBody('');// 设置响应体内容
    $connection->send($response);
}

/**通过服务器key获取服务地址信息
 * @param $key
 * @return false|array
 */
function getAddressFromKey($key){
    global $the;
    if($the['Mode']==='static'){
        $routeList=$the['StaticRoute'];
    }else{
        $routeList=$the['DynamicsRoute'];
    }
    if(array_key_exists($key,$routeList)){
        return $routeList[$key];
    } else {
        return false;
    }
}
/**校验并解析json格式
 * @param string $value
 * @return false|array
 */
function checkJsonData($value) {
    $res = json_decode($value, true);
    $error = json_last_error();
    if (!empty($error)) {
        return false;
    }else{
        return $res;
    }
}

/**检测传入是否为包含type键的数组
 * @param $data
 * @return bool
 */
function isInstructObj($data) {
    return (is_array($data) && array_key_exists('type',$data));
}
function getStaticRoute(){//获取静态路由
    global $the;
    $FileContent=file_get_contents('./config/'._StaticRouteName);//读取文件内容
    $Data=checkJsonData($FileContent);
    if($FileContent==='' || $Data===false){
        $the["StaticRoute"]=[];
    }else{
        $the["StaticRoute"]=$Data;
    }
    echo<<<ETX
静态路由表：
ETX;
    print_r($the["StaticRoute"]);
    return $Data;
}
function grappleRoute(){//抓取其他路由的路由并更新到此路由
    global $the;
    //$the["GrappleList"]=fopen('./config/'._GrappleListName,'a+');
    echo<<<ETX
抓取路由表：
ETX;
    return true;
}
function handle_message(TcpConnection $connection,Request $request){//响应HTTP请求
    global $the;
    $jsonSource=$request->rawBody();
    $jsonData=checkJsonData($jsonSource);
    if(isInstructObj($jsonData)){
        $type=$jsonData['type'];
        switch ($type){
            case 'get_routeList':{
                $response=new Response();//创建一个新的Response对象
                $response->withStatus(200);//设置HTTP状态码
                $response->withHeader('Access-Control-Allow-Origin', '*');//设置响应头
                if($the["Mode"]==='static'){
                    $instructObj=[
                        'type'=>'send_routeList',
                        'data'=>$the['StaticRoute']
                    ];
                    $response->withBody(json_encode($instructObj));//返回路由表指令
                    $connection->send($response);
                }elseif ($the["Mode"]==='dynamics'){
                    $instructObj=[
                        'type'=>'send_routeList',
                        'data'=>$the['DynamicsRoute']
                    ];
                    $response->withBody(json_encode($instructObj));//返回动态路由
                    $connection->send($response);
                }
                break;
            }
            case 'get_route':{
                if(!array_key_exists('data',$jsonData)){//如果指令不包含data则返回空
                    sendEmptyString($connection);
                    return false;
                }else{
                    if(!is_array($jsonData['data'])){//果指令data不是数组则返回空
                        sendEmptyString($connection);
                        return false;
                    }
                    if(!array_key_exists('key',$jsonData['data'])){//如果指令data不包含key则返回空
                        sendEmptyString($connection);
                        return false;
                    }
                    if(!is_string($jsonData['data']['key'])){//如果key不是字符串则返回空
                        sendEmptyString($connection);
                        return false;
                    }
                    $serverAddress=getAddressFromKey($jsonData['data']['key']);
                    if($serverAddress!==false){
                        $instructObj=[
                            'type'=>'send_route',
                            'data'=>[
                                'url'=>$serverAddress
                            ]
                        ];
                        $response=new Response();//创建一个新的Response对象
                        $response->withStatus(200);//设置HTTP状态码
                        $response->withHeader('Access-Control-Allow-Origin', '*');//设置响应头
                        $response->withBody(json_encode($instructObj));//返回路由表指令
                        $connection->send($response);
                    }else{
                        $instructObj=[
                            'type'=>'send_route',
                            'data'=>[
                                'url'=>''
                            ]
                        ];
                        $response=new Response();//创建一个新的Response对象
                        $response->withStatus(200);//设置HTTP状态码
                        $response->withHeader('Access-Control-Allow-Origin', '*');//设置响应头
                        $response->withBody(json_encode($instructObj));//返回路由表指令
                        $connection->send($response);
                    }
                }
                break;
            }
        }
    }else{
        sendEmptyString($connection);
    }
}
/**
函数End
 */
/**

 */
if(_EnableSSL){
    $context=array('ssl'=>array('local_cert'=>_CrtFile,'local_pk'=>_KeyFile,'verify_peer'=>false));
    $httpWorker=new Worker('http://'._IP.':'._Port,$context);
    $httpWorker->transport='ssl';
}else{
    $httpWorker=new Worker('http://'._IP.':'._Port);
}
$httpWorker->count=4;//设置运行参数
$httpWorker->onMessage='handle_message';
$httpWorker->onWorkerStart=function(){
    Timer::add(
        _RefreshTime,
        function (){
            getStaticRoute();
        }
    );
};
startSetting();
Worker::runAll();
/**
End
 */