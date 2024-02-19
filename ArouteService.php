<?php
/**
引入文件
 */

use Workerman\Protocols\Http\Request;
use Workerman\Worker;
use Workerman\Timer;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Http;
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
function startSetting(){
    global $the;
    $the["Mode"]=_DefaultMode;
    getStaticRoute();
    //grappleRoute();
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
function handle_message($connection,Request $request){//响应HTTP请求
    global $the;
    $response = new Response();// 创建一个新的Response对象
    $response->withStatus(200);// 设置HTTP状态码
    $response->withHeader('Access-Control-Allow-Origin', '*');// 设置响应头
    if($the["Mode"]==='static'){
        $response->withBody(json_encode($the['StaticRoute']));// 设置响应体内容
        $connection->send($response);
    }elseif ($the["Mode"]==='dynamics'){
        $response->withBody(json_encode($the['DynamicsRoute']));// 设置响应体内容
        $connection->send($response);
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