<?php
/**这是一个示例配置文件
 * 请复制到./config文件夹下
 **/
const _IP='0.0.0.0';//主IP地址或127.0.0.1
const _Port='35553';//主端口
const _OutManage=false;//是否允许外部管理路由，启用后其他服务器可操作此路由服务的路由表
const _RefreshTime=30;//刷新路由表的间隔，单位秒
const _GrappleTime=60;//抓取其他路由表的间隔，单位秒
const _StaticRouteName='staticRoute.txt';//静态路由表文件名称
const _GrappleListName='grappleList.txt';//抓钩路由抓取列表文件名称
const _EnableSSL=false;//是否启用ssl
const _CrtFile='';//ssl crt文件路径
const _KeyFile='';//ssl key文件路径
const _DefaultMode='static';//默认路由模式 static dynamics