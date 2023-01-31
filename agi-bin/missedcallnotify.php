#!/usr/bin/php -q
<?php
	/* Usage:
	*
	* AGI(missedcallnotify.php,extension,[enable/disable/toggle],[email_address])
	*
	* ARG1: required, ringing extension number, use 's' if extension is unknown and script will attempt to determine
	* ARG2: optional, acceptable values are 'enable', 'disable' or 'toggle'
	*
	*
	* If ARG1 is supplied alone, a missed call notification is triggered. If supplied with other arguments
	* no notification is triggered, just database changes are made
	*/

	/**********************************************************************
	 *            Sangoma Technologies Missed Call Notifications          *
	 *                      Copyright (C) 2023                            *
	 *                      Sangoma Technologies                          *
	 *                                                                    *
	 **********************************************************************/

	// set to true for verbose agi output
	$mc_debug 	= true;

	// get script arguments
	$extension = $argv['1'];
	$enabled = isset($argv['2'])?$argv['2']:'';
	$dialplanext = isset($argv['3'])?$argv['3']:'';// if it 's' then it could be the master channel hanging up
	$dialplanextdbexit =  isset($argv['4'])?$argv['4']:'';
	$dialplanextdbvalue =  isset($argv['5'])?$argv['5']:'';
	$curchannel =  isset($argv['6'])?$argv['6']:'';
	$channeldialstatus =  isset($argv['7'])?$argv['7']:'';
	$queuecall =  isset($argv['8'])?$argv['8']:'';
	// Load FreePBX bootstrap environment
	$restrict_mods = array('missedcall' => true);
	if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
		include_once('/etc/asterisk/freepbx.conf');
	}
	$queue = false;
	$ringgroup = false;
	$freepbx = \FreePBX::Create();
	$mc	 = $freepbx->Missedcall();
	$asm 	 = $mc->asm();
	$um      = $freepbx->Userman();
	$root = $freepbx->Config()->get("AMPWEBROOT");
	// set up AGI class
	$agidir =  $freepbx->Config()->get('ASTAGIDIR');
	require_once $agidir."/phpagi.php";
	$agi = new AGI();

	// dump all channel data for debug
	/*
	$channel_data = $agi->request;
	foreach ($channel_data as $key => $value) {
		log_write($key.": ".$value);
	}
	*/

	if (!$extension &&  $queuecall == "" ) {
		log_write("As a minimum this script requires extension or 's' as argument");
		exit;
	}

	// initialize variables
	$terminate 		= false;
	$unanswered 	= false;
	$send_notice 	= false;
	$internal 		= false;
	$external 		= false;
	$user			= $um->getUserByDefaultExtension($extension);
	$userid  		= $user['id'];
	$mc_params 		= $mc->get($userid);
	$mcenabled		= $um->getCombinedModuleSettingByID($user['id'],'missedcall','mcenabled',false, true);
	
	if (empty($mc_params['email']) && $enabled != '') {
		$agi->answer();
		$agi->stream_file("access-denied");
		exit;
	}

	if ($enabled && $extension != 's') {
		switch ($enabled) {
			case "enable":
				$foo = $mc->misscallEnable($userid,false,true);
				$agi->answer();
				$agi->stream_file("missed");
				$agi->stream_file("call");
				$agi->stream_file("alert");	
				$agi->stream_file("activated");	
				log_write("Missed call notify for $extension set to enable");
			break;
			case "disable":
				$foo = $mc->misscallDisable($userid,false,true);
				$agi->answer();
				$agi->stream_file("missed");
				$agi->stream_file("call");
				$agi->stream_file("alert");
				$agi->stream_file("de-activated");	
				log_write("Missed call notify for $extension set to disable");
			break;
			case "toggle":
				$foo = $mc->Toggle($userid);
				$agi->answer();
				$agi->stream_file("missed");
				$agi->stream_file("call");
				$agi->stream_file("alert");	
				if($foo == "enable"){
					$agi->stream_file("activated");
				}
				else{
					$agi->stream_file("de-activated");	
				}
				log_write("Missed call notify for $extension set to $foo");

			break;
			default:
				log_write("Acceptable values for Arg 2 are enable, disable, or toggle");
				exit;
			break;
		}
		$terminate = true;
	}

	// if script is provided with arg2 do not send notifiation
	if ($terminate) {
		exit;
	}

	// get missed call params for ringing extension, array of enable, queue, ringgroup, internal, external, email
	$mc_params = $mc->get($extension,'byEXT');
	$mcgroup = get_var($agi,"MCGROUP");
	// if notifications are disabled for ringing extension, can exit immediately
	if (($extension !="" && $mc_params['notification'] == 0)  && ( $queuecall == "" || $mcgroup == "")) {
		log_write("Notifications disabled for $extension, exiting");
		exit;
	}

	
	// if script is running in ringing channel must use CHANNEL(STATE) to determine if call has been answered
	$channel_state = get_var($agi,"CHANNEL(STATE)");
	if ($channel_state && !$unanswered) {
		if ($channel_state != "Up") {
			$unanswered = true;
		}
	}

	// get name/number of calling extension based on inheritable channel variables set in dialplan
	$mcexten = get_var($agi,"MCEXTEN");
	$mcname = get_var($agi,"MCNAME");
	$mcnum = get_var($agi,"MCNUM");

	// in case MC* channel variables are not set, attempt to get calling extension from channel CID values
	// or FROMEXTEN variable this will only work in simple cases
	if (!$mcexten) {
		$fromexten = get_var($agi,"FROMEXTEN");
		$cid = get_var($agi,"CALLERID(num)");
		$cnam = get_var($agi,"CALLERID(name)");
		if ($cid && $cid != $extension) {
			$mcexten = $cid;
			$mcname = $cnam;
		} elseif ($fromexten && $fromexten != $extension) {
			$mcexten = $fromexten;
		} elseif ($mcnum && $mcnum != $extension) {
			$mcexten = $mcnum;
			$mcname = $mcname;
		} else {
			log_write("Cannot determine calling extension, exiting");
			exit;
		}
	}

	// determine if call is to a ring group, check for value of channel

	if ($mcgroup) {
		$call_type = "ringgroup";
		// read the extension from channel 
		preg_match('/(?<=\/)(\d*)(?=(@|-))/', $curchannel, $match);
		log_write("checking chan: $curchannel matches ". print_r($match,true));
		if(isset($match[0]) && is_numeric($match[0])){
			$extension = $match[0];
		}
		$ringgroup=true;
	}

	// determine if call is to a from a queue, check for value of channel
	$mcqueue = get_var($agi,"MCQUEUE");
	if ($mcqueue) {
		$call_type = "queue";
		$queue=true;
		// read the extension from channel 
		preg_match('/(?<=\/)(\d*)(?=(@|-))/', $curchannel, $match);
		log_write("checking chan: $curchannel matches ". print_r($match,true));
		if(isset($match[0]) && is_numeric($match[0])){
			$extension = $match[0];
		}
	}

	// determine if call is to a followme group, check for value of channel
	$mcfmfm = get_var($agi,"MCFMFM");
	if ($mcfmfm) {
		$call_type = "findmefollow";
		$followme=true;
	}

	// determine if call is internal
	$ampusers = $mc->getUsers();
	if (in_array($mcexten, $ampusers)) {
		$call_type = "internal";
		$internal = true;
	}

	// determine if call is external
	if (!$internal) {
		$call_type = "external";
		$external = true;
	}

	$linkedid = get_var($agi,'CHANNEL(LINKEDID)');
	$uniqueid = get_var($agi,'CHANNEL(UNIQUEID)');
	$mcorginchan = get_var($agi,'MCORGCHAN');

	// asterisk log output summary
	log_write("********* Missed Call Summary *********");
	log_write("Orginator channel : $mcorginchan");
	log_write("unanswered: $unanswered");
	log_write("Linked Channel ID: ".$linkedid);
	log_write("Unique Channel ID: ".$uniqueid);
	log_write("Calling extension: ".$mcexten);
	log_write("Calling ext name: ".$mcname);
	log_write("Ringing extension: ".$extension);
	$uid = md5($linkedid.$mcexten.$mcname);
	log_write("Missed Call UID: ".$uid);
	log_write("Notification enabled?: ".$mc_params['enable']);
	log_write("Is Internal call?: ".($internal ? 'Yes' : 'No'));
	log_write("Send Internal Notification?: ".($mc_params['internal'] ? 'Yes' : 'No'));
	log_write("Is External call?: ".($external ? 'Yes' : 'No'));
	log_write("Send External Notification?: ".($mc_params['external'] ? 'Yes' : 'No'));
	log_write("Is Ring Group call?: ".($ringgroup ? 'Yes' : 'No'));
	log_write("Send Ring Group Notification?: ".($mc_params['ringgroup'] ? 'Yes' : 'No'));
	log_write("Is Queue call?: ".($queue ? 'Yes' : 'No'));
	log_write("Send Queue Notification?: ".($mc_params['queue'] ? 'Yes' : 'No'));
	//log_write("FMFM call: $followme");
	log_write("To email: ".$mc_params['email']);
	log_write("From email: ".$fr_email);
	log_write("From name: ".$fr_name);
	$agi->set_variable('MASTER_CHANNEL(MC-'.$uid.')',1);

	if ($internal){
		$chan_orgin_from = "Internal";
	}
	if ($external){
		$chan_orgin_from = "external";
	}
	if ($ringgroup){
		$chan_orgin_from = "ringgroup";
	}
	if ($queue){
		$chan_orgin_from = "queue";
	}


	if($linkedid != $uniqueid){
		if($channeldialstatus ==""){// No dial status then it considered as missed
			$channeldialstatus ="ANSWER";
			if($unanswered){
				$channeldialstatus ="MISSED";
			}
		}
		$extension = $mc->getDeviceUser($extension);
		$q = "INSERT INTO missedcalllog (`callerid`,`calleridname`,`destination`,`call_type`,`uniqueid`,`linkedid`,`channel`,`dialstatus`,`chan_orgin_from`) VALUES('$mcexten','$mcname','$extension','$call_type','$uniqueid','$linkedid','$curchannel','$channeldialstatus','$chan_orgin_from')";
		$db->query($q);
	}

	// if the linkedid and uniqueid are same then its the channel who orginated the call. So we can sent the finaly missed call report
	if($linkedid == $uniqueid){
		log_write("Uniqueid and Linkedid  Processing  uniqueid = $uniqueid");
		$input['ringgroup'] = $ringgroup;
		$input['queue'] = $queue;
		$input['uniqueid'] = $uniqueid;
		$arg = escapeshellarg(base64_encode(json_encode($input)));
		dbug($root."/admin/modules/missedcall/callhangupprocess.php ".$arg." > /dev/null 2>&1 &");
		exec("php ".$root."/admin/modules/missedcall/callhangupprocess.php ".$arg." > /dev/null 2>&1 &");
	}

	// helper functions
	function get_var( $agi, $value) {
		$r = $agi->get_variable( $value );

		if ($r['result'] == 1) {
			$result = $r['data'];
			return $result;
		}
		return '';
	}

	function log_write($string, $level=3) {
		global $agi, $mc_debug;
		if ($mc_debug){
			$agi->verbose($string, $level);
		}
	}
?>
