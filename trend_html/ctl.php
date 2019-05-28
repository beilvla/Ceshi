<?php 
//设置时区
date_default_timezone_set('PRC');
//加载基础配置文件
require '../../constants.php';
//加置数据库
require '../common/class.MySQLi.php';
if(CON_ENVIRONMENT == 'online'){
//	$db_config = 'mysqli://apixymens_01:xymens2015qazwsxedc@xymensofweb.mysql.rds.aliyuncs.com:3306/xymens';
	$db_config = CON_DB_DSN;
} else {
	$db_config = CON_DB_DSN;
}
$db_arr1 = explode('/', $db_config);
$config['DB_NAME'] = $db_arr1[3];
$db_arr2 = explode('@', $db_arr1[2]);
$db_arr3 = explode(':', $db_arr2[0]);
$config['DB_USER'] = $db_arr3[0];
$config['DB_PWD'] = $db_arr3[1];
$db_arr4 = explode(':', $db_arr2[1]);
$config['DB_HOST'] = $db_arr4[0];
$config['DB_PORT'] = $db_arr4[1];
//var_dump($config);die;
$DB =  new MySQL($config['DB_NAME'], $config['DB_USER'], $config['DB_PWD'], $config['DB_HOST'],$config['DB_PORT']);
//global $DB
global $DB;

$act = isset($_REQUEST['act'])?$_REQUEST['act']:'';
if($act == 'scan'){
	$channel_id = isset($_POST['channel_id'])?$_POST['channel_id']:'';
	$device_type = isset($_POST['device_type'])?$_POST['device_type']:'';
    $oid = isset($_POST['oid'])?$_POST['oid']:0;
	$date_time = date('Y-m-d');
	if(empty($device_type)){
		ajax_response(-1, '缺少参数');
	}
	$sql = "select * from ecs_weixin_download_log where date_time='$date_time' and oid='$oid' and channel_id='$channel_id' and device_type='$device_type' limit 1";
	$res = $DB->executeSQL($sql);
//	var_dump($res);die;
	$log_id = '';
	if(isset($res['log_id']) && $res['log_id']>0){
		$sql1 = "update ecs_weixin_download_log set scan_total=scan_total+1 where log_id=".$res['log_id'];
		$DB->executeSQL($sql1);
		$log_id = $res['log_id'];
	} else {
		$sql1 = "insert into ecs_weixin_download_log (date_time,device_type,channel_id,oid,scan_total) values ('$date_time','$device_type','$channel_id','$oid','1')";
		$DB->executeSQL($sql1);
		$log_id = $DB->last_id();
	}
    //android 下载地址
    $android_url = '';
    $tongji_url = '';
    /*
    if(!empty($channel_id)){
        $channel_info = $DB->executeSQL("select * from ecs_weixin_channel where channel_id='$channel_id' limit 1");
        if(!empty($channel_info['channel_download'])){
            $tongji_url = $channel_info['channel_download'];
        }
    }
    if(empty($tongji_url)){
        if(!empty($device_type) && $device_type=='android'){
            $res = $DB->executeSQL("select * from ecs_app_version where type='android' order by id desc limit 1");
            $android_url = $res['url'];
        }
    }*/

	//response
	ajax_response(0, 'success', array('log_id'=>$log_id,'android'=>$android_url, 'tongji'=>$tongji_url));
}
else if($act == 'click'){
	$log_id = isset($_POST['log_id'])?$_POST['log_id']:'';
	if(!empty($log_id)){
		$sql = "update ecs_weixin_download_log set download_total=download_total+1 where log_id=$log_id";
		$DB->executeSQL($sql);
	}
	ajax_response(0, 'success');
}	 
else {
	ajax_response(-2, 'no action');
}

function ajax_response($code, $msg='success', $data=array()){
	global $DB;
	//关闭数据库连接
	$DB->closeConnection();
	echo json_encode(array('status'=>$code, 'msg'=>$msg, 'data'=>$data));exit;
}


?>