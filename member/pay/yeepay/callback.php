﻿<?php

/*
 * @Description 易宝支付B2C在线支付接口范例 
 * @V3.0
 * @Author rui.xin
 */
 
include 'yeepayCommon.php';	
include_once("../moneyconfig.php");
include_once("../../../include/mysqli.php");
include_once("../config.php");
$p1_MerId = $pay_mid;
$merchantKey = $pay_mkey;
	
#	只有支付成功时易宝支付才会通知商户.
##支付成功回调有两次，都会通知到在线支付请求参数中的p8_Url上：浏览器重定向;服务器点对点通讯.

#	解析返回参数.
$return = getCallBackValue($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac);

#	判断返回签名是否正确（True/False）
$bRet = CheckHmac($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac);
#	以上代码和变量不需要修改.
	 	
#	校验码正确.
if($bRet){
	if($r1_Code=="1"){
		
	#	需要比较返回的金额与商家数据库中订单的金额是否相等，只有相等的情况下才认为是交易成功.
	#	并且需要对返回的处理进行事务控制，进行记录的排它性处理，在接收到支付结果通知后，判断是否进行过业务逻辑处理，不要重复进行业务逻辑处理，防止对同一条交易重复发货的情况发生.      	  	
		
		if($r9_BType=="1"){
			echo "交易成功";
			echo  "<br />在线支付页面返回";
		}elseif($r9_BType=="2"){
			#如果需要应答机制则必须回写流,以success开头,大小写不敏感.
			echo "success";
			echo "<br />交易成功";
			echo  "<br />在线支付服务器返回";      			 
		}
		
		/* 会员入款 开始 */
		$sql="select uid,username,money from k_user where username='$r8_MP' limit 1";
		$query	=	$mysqli->query($sql);
		$rows	=	$query->fetch_array();
		$cou	=	$query->num_rows;
		if($cou<=0){
			echo "返回信息错误!";
	    	exit;
		}
		$assets	 =	$rows['money'];
		$uid	 =	$rows['uid'];
		$username=	$rows['username'];
        echo "订单号=".$r6_Order.'<br>';
        echo "金额=".$r3_Amt.'<br>';
        echo "币种=".$r4_Cur.'<br>';
        $sql="select * from k_money where m_order = '".$r6_Order."'";
        $query	=	$mysqli->query($sql);
        $cou	=	$query->num_rows;
        if ($cou==0){
            $sql		=	"insert into k_money(uid,m_value,m_order,status,assets,balance,type) values($uid,$r3_Amt,'$r6_Order',2,$assets,$assets+$r3_Amt,1)";
            $mysqli->query($sql);
            $m_id = $mysqli->insert_id;
            $sql	=	"update k_money,k_user set k_money.status=1,k_money.update_time=now(),k_user.money=k_user.money+k_money.m_value,k_money.about='该订单在线冲值操作成功',k_money.sxf=k_money.m_value/100,k_money.balance=k_user.money+k_money.m_value where k_money.uid=k_user.uid and k_money.m_id=$m_id and k_money.`status`=2";
            $mysqli->query($sql);
            
            echo "<Script language=javascript>alert('交易成功.');</script>";
        }
		/* 会员入款 结束 */
	}
	
}else{
	echo "交易信息被篡改";
}
   
?>
<html>
<head>
<title>Return from YeePay Page</title>
</head>
<body>
</body>
</html>