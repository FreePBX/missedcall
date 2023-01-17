<?php
if (function_exists('proc_nice')) {
	@proc_nice(10);
}
$bootstrap_settings['include_compress'] = false;
$restrict_mods = array('missedcall' => true);
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
	include_once('/etc/asterisk/freepbx.conf');
}
//wait for some time to finish the channel activites 
sleep(3);
$freepbx = \FreePBX::Create();
$json = json_decode(base64_decode($argv[1]),true);
$McObj = $freepbx->Missedcall();
$linkedid = $json['uniqueid'];
	$queue = $json['queue'];
	$ringgroup = $json['ringgroup'];
	$data = $McObj->getallcalls($linkedid);
	// get distinct to number and its status
	$dialarray =  [];
	foreach($data as $dial){
		$ext = $dial['destination'];
		$status = $dial['dialstatus'];
		if($status =="MISSED"){
			$dialarray[$ext]['MISSED'] = true;
			$dialarray[$ext]['CallType'] = $dial['Call_type'];
			$dialarray[$ext]['call_origin'] = $dial['chan_orgin_from'];
			$dialarray[$ext]['callerid'] = $dial['callerid'];
			$dialarray[$ext]['calleridname'] = $dial['calleridname'];
		}
		if($status =="ANSWER"){
			$dialarray[$ext]['ANSWER'] = true;
		}	
	}
	foreach($dialarray as $ext => $dialsts){
		$send_notice = false;
		if(!isset($dialsts['ANSWER']) && isset($dialsts['MISSED']) ){
			// check notifiation enabled or not  and send email
			$mc_params = $McObj->get($ext,'byEXT');
			if($mc_params['enable']){// check only enable extension
				if ($dialsts['call_origin'] == 'Internal' && $mc_params['internal']){
					$send_notice = true;
				}
				// this should be from direct external
				if ($dialsts['call_origin'] =='external' && $mc_params['external'] ){
					$send_notice = true;
				}
				if ($dialsts['call_origin'] == 'ringgroup' && $mc_params['ringgroup'] ){
					$send_notice = true;
				}
				if ($dialsts['call_origin'] == 'queue' && $mc_params['queue']){
					$send_notice = true;
				}
				// call type  
				if($queue && $dialsts['CallType'] =='external') {
					$calltype = 'External(Queue)';
				} elseif ($ringgroup && $dialsts['CallType'] =='external'){
					$calltype = 'External(Ringgroup))';
				} else {
					$calltype = $dialsts['CallType'];
				}
				// Send email now 
				if($send_notice){
					$McObj->sendEmail($mc_params['email'],$ext,$dialsts['callerid'],$dialsts['calleridname'],$calltype);
				}else { 
					dbug(" No Email sent ");
				}
				
			}
		}
	}
	$McObj->removeAllCalls($linkedid);
exit();
