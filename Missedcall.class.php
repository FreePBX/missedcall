<?php

namespace FreePBX\modules;
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
use BMO;
use FreePBX_Helpers;
use PDO;
use Exception;

class Missedcall extends FreePBX_Helpers implements BMO {
	
    const EMAIL_TYPE_HTML = 'html';
    const EMAIL_TYPE_TEXT = 'text';
    const EMAIL_SUBJECT = 'Missed call from {{calleridname}}';

	private $licensed = false;

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}

		$this->FreePBX 	= $freepbx;
		$this->db 		= $freepbx->Database;
		$this->userman 	= $freepbx->Userman;
		$this->astman 	= $freepbx->astman;
	}

	public function getRightNav($request) {
		if(!isset($request['view']) || $request['view'] != "form") {
			return false;
		}
		return load_view(__DIR__."/views/rnav.php",array());
	}

	//BMO Methods

	//Required function - Called during module install
	public function install() {
		// Register FeatureCode - Activate
		$fcc = new \featurecode('missedcall', 'missedcall_on');
		$fcc->setDescription('Missed Call Notification Activate');
		$fcc->setDefault('*56');
		$fcc->update();
		unset($fcc);

		// Register FeatureCode - Deactivate
		$fcc = new \featurecode('missedcall', 'missedcall_off');
		$fcc->setDescription('Missed Call Notification Deactivate');
		$fcc->setDefault('*57');
		$fcc->update();
		unset($fcc);

		// Register FeatureCode - Toggle
		$fcc = new \featurecode('missedcall', 'missedcall_toggle');
		$fcc->setDescription('Missed Call Notification Toggle');
		$fcc->setDefault('*58');
		$fcc->update();
		unset($fcc);

		$users = $this->getUsers();
		foreach($users as $id=>$ext){
			if(!empty($ext)){
				$response = $this->FreePBX->astman->database_put("AMPUSER","$ext/missedcall", "disable");
				$this->update($id,$ext,0, 0, 0, 0);
			}			
		}
	}

	// required function - called during module un-install
	public function uninstall() {
		out(_('Removing the database table'));
    	$result = $this->deleteTable();
    	if($result === true){
    		out(_('Table Deleted'));
    	}else{
    		out(_('Something went wrong'));
    		out($result);
    	}
		
		out(_('Removing missedcall keys from the asterisk database'));
		$users = $this->getUsers();
		foreach($users as $ext){
			$this->FreePBX->astman->database_del("AMPUSER","$ext/missedcall");
		}
		// remove userman settings
		$queries[] = "DELETE  FROM userman_groups_settings WHERE `module`= 'missedcall'";
		$queries[] = "DELETE  FROM userman_groups_settings WHERE `module`= 'ucp|Missedcall'";
		$queries[] = "DELETE  FROM userman_users_settings WHERE `module`= 'missedcall'";
		foreach($queries as $query){
			$stmt = $this->db->prepare($query);
			$stmt->execute();
		}
	}

	public function getDeviceUser($ext){
		$query = "select `user` from devices WHERE `id`= '$ext'";
		$stmt = $this->db->prepare($query);
		$stmt->execute();
		$data = $stmt->fetch(\PDO::FETCH_ASSOC);
		return isset($data['user'])?$data['user']:$ext;
	}

	// fetchall call belong to given linkedid
	public function getallcalls($linkedid){
		$query = "SELECT * FROM missedcalllog WHERE linkedid= '$linkedid'";
		$stmt = $this->db->prepare($query);
		$stmt->execute();
		$data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		return $data;
	}
	// Remove all call belong to given linkedid
	public function removeAllCalls($linkedid){
		$query = "DELETE FROM missedcalllog WHERE linkedid= '$linkedid'";
		$stmt = $this->db->prepare($query);
		$stmt->execute();
		return;
	}
	
	public function sendEmail($mc_email,$ext,$mcexten,$mcname="",$calltype){
	// determine from email address for notification
	$fr_email = $this->FreePBX->Config()->get('AMPUSERMANEMAILFROM');
	$fr_name = _("Missed Call Notification");
	if (!$fr_email) {
		$fr_email = 'email@domain.com';
		$fr_name = $this->FreePBX->Config()->get('DASHBOARD_FREEPBX_BRAND');;
	}
		$emailData['brand'] = $this->FreePBX->Config()->get('BRAND_FREEPBX_ALT_LEFT');
		$emailData['extension'] = $ext;
		$emailData['callerid'] = $mcexten;
		$emailData['calleridname'] = $mcname;
		$emailData['datetime'] = date("Y-m-d H:i:s");
		$emailData['calltype'] = $calltype;

		// Get mail template
		$emailTemplate = $this->getMailTemplate('notification_mail');
		$emailType = !empty($emailTemplate['type']) ? $emailTemplate['type'] : self::EMAIL_TYPE_HTML;
		
		$subject = !empty($emailTemplate['subject']) ? $emailTemplate['subject'] : self::EMAIL_SUBJECT;
		$subject = $this->replaceTemplateVariables($subject, $emailData);

		$body = !empty($emailTemplate['body']) ? $emailTemplate['body'] : file_get_contents(__DIR__ . '/views/mail.tpl');
		$body = $this->replaceTemplateVariables($body, $emailData);
		$body = $emailType == self::EMAIL_TYPE_HTML ? html_entity_decode($body, ENT_QUOTES) : $body;

		$em = new \CI_Email();
		$em->from($fr_email, $fr_name);
		$em->to($mc_email);
		$em->subject($subject);
		$em->set_mailtype($emailType);
		$em->message($body);
		$em->send();
		dbug("Sending missed call notification to ".$mc_email);
	}


	//View called by page.misseccall.php
	public function showPage(){
		$subhead = null;
		$email = $this->FreePBX->Config()->get('AMPUSERMANEMAILFROM');
		$error = false;
		if(empty($email)){
			$error = true;
		}
		$content = load_view(__DIR__.'/views/grid.php');
		echo load_view(__DIR__.'/views/default.php', array('subhead' => $subhead, 'content' => $content, "error" => $error));
	}

    //add buttons to your page(s), buttons should not be added in html. Note this is a 13+ method.
	public function getActionBar($request) {
		$buttons = array();
		switch($request['display']) {
			//this is usually your module's rawname
			case 'missedcall':
				$buttons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				//We hide the delete button if we are not editing an item. "id" should be whatever your unique element is.
				if (empty($request['id'])) {
					unset($buttons['delete']);
				}
				//If we are not in the form view lets 86 the buttons
				if (empty($request['view'])){
					unset($buttons);
				}
			break;
		}
		return $buttons;
	}

	public function checkFieldValidationForUserman($uid, $request){
		$noError	= true;
		$message 	= '';
		$notify 	= $this->getStatus($uid);

		# check that missed call is enabled
		if($notify == 1){
			$noError = false;
			$message = _("The user's email address is required. Because missed call notification is enabled. Please disable it and try again.");
		}

		return array("status" => $noError, "type" => $noError ? "" : "danger",  "message" => $message);
	}

	//Ajax methods

	//This method declares which are valid ajax commands...
	public function ajaxRequest($req, &$setting) {
		switch ($req) {
			case "toggleMC":
			case "get_status":
			case "savebulk":
			case "saveEmailSettings":
				return true;
			break;
			default:
				return false;
			break;
		}
	}

	public function ajaxHandler(){
		switch ($_REQUEST['command']) {
			case 'savebulk':
				switch($_REQUEST["status"]){
					case "enable":
						foreach($_REQUEST["extensions"] as $key => $userid){
							$this->updateOne($userid,'notification',1,[],true);
						}
						return ["status" => true, "message" => _("Success.")];
					case "disable":
						foreach($_REQUEST["extensions"] as $key => $userid){
							$this->updateOne($userid,'notification',0,[],true);
						}
						return ["status" => true, "message" => _("Success.")];
					default:
						return ["status" => false, "message" => _("Unknown Status.")];
				}
				return false;
			case 'toggleMC':
				if($_REQUEST['state'] == 'enable'){
					$state = true;
				}

				if($_REQUEST['state'] == 'disable'){
					$state = false;
				}

				if(!isset($state) || !isset($_REQUEST['extdisplay'])){
					return array('toggle' => 'invalid');
				}

				$this->Toggle($_REQUEST['extdisplay']);
				return array('toggle' => 'received');
			break;
			case "get_status":
				$users = $this->getUsers();
				$list = [];
				foreach($users as $id => $ext){
					$mc_params 	= $this->get($id);
					if(empty($mc_params['email'])){
						continue;
					}
					$user 	  	= $this->userman->getUserByDefaultExtension($ext);
					$mcenabled	= $this->userman->getCombinedModuleSettingByID($id,'missedcall','mcenabled', false, true);
					$internal 	= $mc_params['internal'] == "1"  	? '<i class="fa fa-check-circle text-success"></i>' : '<i class="fa fa-times-circle text-danger"></i>' ;
					$external	= $mc_params['external'] == "1" 	? '<i class="fa fa-check-circle text-success"></i>' : '<i class="fa fa-times-circle text-danger"></i>' ;
					$queue 		= $mc_params['queue'] 	 == "1" 	? '<i class="fa fa-check-circle text-success"></i>' : '<i class="fa fa-times-circle text-danger"></i>' ;
					$ringgroup 	= $mc_params['ringgroup']== "1" 	? '<i class="fa fa-check-circle text-success"></i>' : '<i class="fa fa-times-circle text-danger"></i>' ;
					$enabled 	= $mcenabled== "1" 	? '<i class="fa fa-check-circle text-success"></i>' : '<i class="fa fa-times-circle text-danger"></i>' ;
					
					$list[] = [ 
						"userid" =>$id,
						"username" =>$user['username'],
						"extension" => $ext, 
						"email" 	=> $mc_params['email'],
						"internal" 	=> $internal,
						"external" 	=> $external,
						"queue" 	=> $queue,
						"ringgroup" => $ringgroup,
						"notification" =>$enabled
					];
				}
				return $list;
			case "saveEmailSettings":
				return $this->saveEmailSettings($_REQUEST);			
			default:
				return false;
			break;
		}
	}

	public function usermanShowPage() {
		global $version;
		if(isset($_REQUEST['action'])) {
			$error = "";
			switch($_REQUEST['action']) {
				case 'addgroup':
				case 'showgroup':
					$mcenabled	= ($_REQUEST['action'] == "addgroup") ? true : $this->userman->getModuleSettingByGID($_REQUEST['group'],'missedcall','mcenabled');
					$mcrg 		= ($_REQUEST['action'] == "addgroup") ? true : $this->userman->getModuleSettingByGID($_REQUEST['group'],'missedcall','mcrg');
					$mcq 		= ($_REQUEST['action'] == "addgroup") ? true : $this->userman->getModuleSettingByGID($_REQUEST['group'],'missedcall','mcq');
					$mci 		= ($_REQUEST['action'] == "addgroup") ? true : $this->userman->getModuleSettingByGID($_REQUEST['group'],'missedcall','mci');
					$mcx 		= ($_REQUEST['action'] == "addgroup") ? true : $this->userman->getModuleSettingByGID($_REQUEST['group'],'missedcall','mcx');
					
					return array(
						array(
							"title" => _("Missed Call"),
							"rawname" => "missedcall",
							"content" => load_view(__DIR__.'/views/missedcall.php',array("mode" => "group", "error" => $error, "mcenabled" => $mcenabled, "mcrg" => $mcrg, "mcq" => $mcq,"mci" =>$mci,"mcx"=>$mcx))
						)
					);
				case 'adduser':
				case 'showuser':
					if(isset($_REQUEST['user'])) {
						$user 		= $this->userman->getUserByID($_REQUEST['user']);
						$mcenabled	= $this->userman->getModuleSettingByID($user['id'],'missedcall','mcenabled',true);
						$mcrg 		= $this->userman->getModuleSettingByID($user['id'],'missedcall','mcrg',true);
						$mcq 		= $this->userman->getModuleSettingByID($user['id'],'missedcall','mcq',true);
						$mci 		= $this->userman->getModuleSettingByID($user['id'],'missedcall','mci',true);
						$mcx 		= $this->userman->getModuleSettingByID($user['id'],'missedcall','mcx',true);
					
					} 
					return array(
						array(
							"title" => _("Missed Call"),
							"rawname" => "missedcall",
							"content" => load_view(__DIR__.'/views/missedcall.php',array("mode" => "user", "error" => $error, "mcenabled" => $mcenabled, "mcrg" => $mcrg, "mcq" => $mcq,"mci" =>$mci,"mcx"=>$mcx))
						)
                    );
                    default:
                    return [];
			}
		}
	}

	public function usermanDelGroup($id,$display,$data) {
	}

	public function usermanAddGroup($id, $display, $data) {
		$this->usermanUpdateGroup($id,$display,$data);
	}

	/*update user by settings */
	private function updateUserbysettins($users=[],$setting,$value){
		foreach ($users as $id){
			if($setting == 'notification'){
				$mcenabled=	$this->userman->getCombinedModuleSettingByID($id,'missedcall','mcenabled');
				if($mcenabled){
					$this->updateOne($id,'notification',1);
				} else {
					$this->updateOne($id,'notification',0);
				}
			}
			if($setting == 'ringgroup'){
				$mcenabled=	$this->userman->getCombinedModuleSettingByID($id,'missedcall','mcrg');
				if($mcenabled){
					$this->updateOne($id,'ringgroup',1);
				} else {
					$this->updateOne($id,'ringgroup',0);
				}
			}
			if($setting == 'queue'){
				$mcenabled=	$this->userman->getCombinedModuleSettingByID($id,'missedcall','mcq');
				if($mcenabled){
					$this->updateOne($id,'queue',1);
				} else {
					$this->updateOne($id,'queue',0);
				}
			}
			if($setting == 'internal'){
				$mcenabled=	$this->userman->getCombinedModuleSettingByID($id,'missedcall','mci');
				if($mcenabled){
					$this->updateOne($id,'internal',1);
				} else {
					$this->updateOne($id,'internal',0);
				}
			}
			if($setting == 'external'){
				$mcenabled=	$this->userman->getCombinedModuleSettingByID($id,'missedcall','mcx');
				if($mcenabled){
					$this->updateOne($id,'external',1);
				} else {
					$this->updateOne($id,'external',0);
				}
			}
		}
	}

	public function usermanUpdateGroup($id,$display,$data) {
		if($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'group') {
			if(isset($_POST['mcenabled'])) {
				if($_POST['mcenabled'] == "true") {
					$this->userman->setModuleSettingByGID($id,'missedcall','mcenabled',true);
					$this->updateUserbysettins($data['users'],'notification',1);
				} else {
					$this->userman->setModuleSettingByGID($id,'missedcall','mcenabled',false);
					$this->updateUserbysettins($data['users'],'notification',0);
				}
			}

			if(isset($_POST['mcrg'])) {
				if($_POST['mcrg'] == "true") {
					$this->userman->setModuleSettingByGID($id,'missedcall','mcrg',true);
					$this->updateUserbysettins($data['users'],'ringgroup',1);
				} else {
					$this->userman->setModuleSettingByGID($id,'missedcall','mcrg',false);
					$this->updateUserbysettins($data['users'],'ringgroup',0);
				}
			}

			if(isset($_POST['mcq'])) {
				if($_POST['mcq'] == "true") {
					$this->userman->setModuleSettingByGID($id,'missedcall','mcq',true);
					$this->updateUserbysettins($data['users'],'queue',1);
				} else {
					$this->userman->setModuleSettingByGID($id,'missedcall','mcq',false);
					$this->updateUserbysettins($data['users'],'queue',0);
				}
			}
			if(isset($_POST['mci'])) {
				if($_POST['mci'] == "true") {
					$this->userman->setModuleSettingByGID($id,'missedcall','mci',true);
					$this->updateUserbysettins($data['users'],'internal',1);
				} else{
					$this->userman->setModuleSettingByGID($id,'missedcall','mci',false);
					$this->updateUserbysettins($data['users'],'internal',0);
				}
			}
			if(isset($_POST['mcx'])) {
				if($_POST['mcx'] == "true") {
					$this->userman->setModuleSettingByGID($id,'missedcall','mcx',true);
					$this->updateUserbysettins($data['users'],'external',1);
				} else {
					$this->userman->setModuleSettingByGID($id,'missedcall','mcx',false);
					$this->updateUserbysettins($data['users'],'external',0);
				}
			}
		}
	}

	/**
	 * Hook functionality from userman when a user is deleted
	 * @param {int} $id      The userman user id
	 * @param {string} $display The display page name where this was executed
	 * @param {array} $data    Array of data to be able to use
	 */
	public function usermanDelUser($id, $display, $data) {
		$this->deleteUser($id);
	}

	/**
	 * Hook functionality from userman when a user is added
	 * @param {int} $id      The userman user id
	 * @param {string} $display The display page name where this was executed
	 * @param {array} $data    Array of data to be able to use
	 */
	public function usermanAddUser($id, $display, $data) {
		$this->usermanUpdateUser($id, $display, $data);
	}

	/**
	 * Hook functionality from userman when a user is updated
	 * @param {int} $id      The userman user id
	 * @param {string} $display The display page name where this was executed
	 * @param {array} $data    Array of data to be able to use
	 */
	public function usermanUpdateUser($id, $display, $data) {
		if($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'user') {
			if(isset($_POST['mcenabled'])) {
				if($_POST['mcenabled'] == "true") {
					$this->userman->setModuleSettingByID($id,'missedcall','mcenabled',true);
					$this->updateOne($id,'notification',1);
				} elseif($_POST['mcenabled'] == "false") {
					$this->userman->setModuleSettingByID($id,'missedcall','mcenabled',false);
					$this->updateOne($id,'notification',0);
				} else {
					$this->userman->setModuleSettingByID($id,'missedcall','mcenabled',null);
					//getcombined settings
					$mcenabled=	$this->userman->getCombinedModuleSettingByID($id,'missedcall','mcenabled');
					if($mcenabled){
						$this->updateOne($id,'notification',1);
					} else {
						$this->updateOne($id,'notification',0);
					}
				}
			}

			if(isset($_POST['mcrg'])) {
				if($_POST['mcrg'] == "true") {
					$this->userman->setModuleSettingByID($id,'missedcall','mcrg',true);
					$this->updateOne($id,'ringgroup',1);
				} elseif($_POST['mcrg'] == "false") {
					$this->userman->setModuleSettingByID($id,'missedcall','mcrg',false);
					$this->updateOne($id,'ringgroup',0);
				} else {
					$this->userman->setModuleSettingByID($id,'missedcall','mcrg',null);
					//getcombined settings
					$mcrg =	$this->userman->getCombinedModuleSettingByID($id,'missedcall','mcrg');
					if($mcrg){
						$this->updateOne($id,'ringgroup',1);
					} else {
						$this->updateOne($id,'ringgroup',0);
					}
				}
			}

			if(isset($_POST['mcq'])) {
				if($_POST['mcq'] == "true") {
					$this->userman->setModuleSettingByID($id,'missedcall','mcq',true);
					$this->updateOne($id,'queue',1);
				} elseif($_POST['mcq'] == "false") {
					$this->userman->setModuleSettingByID($id,'missedcall','mcq',false);
					$this->updateOne($id,'queue',0);
				} else {
					$this->userman->setModuleSettingByID($id,'missedcall','mcq',null);
					$mcq =	$this->userman->getCombinedModuleSettingByID($id,'missedcall','mcq');
					if($mcq){
						$this->updateOne($id,'queue',1);
					} else {
						$this->updateOne($id,'queue',0);
					}
				}
			}
			if(isset($_POST['mci'])) {
				if($_POST['mci'] == "true") {
					$this->userman->setModuleSettingByID($id,'missedcall','mci',true);
					$this->updateOne($id,'internal',1);
				} elseif($_POST['mci'] == "false") {
					$this->userman->setModuleSettingByID($id,'missedcall','mci',false);
					$this->updateOne($id,'internal',0);
				} else {
					$this->userman->setModuleSettingByID($id,'missedcall','mci',null);
					$mci =	$this->userman->getCombinedModuleSettingByID($id,'missedcall','mci');
					if($mci){
						$this->updateOne($id,'internal',1);
					} else {
						$this->updateOne($id,'internal',0);
					}
				}
			}
			if(isset($_POST['mcx'])) {
				if($_POST['mcx'] == "true") {
					$this->userman->setModuleSettingByID($id,'missedcall','mcx',true);
					$this->updateOne($id,'external',1);
				} elseif($_POST['mcx'] == "false") {
					$this->userman->setModuleSettingByID($id,'missedcall','mcx',false);
					$this->updateOne($id,'external',0);
				} else {
					$this->userman->setModuleSettingByID($id,'missedcall','mcx',null);
					$mcx =	$this->userman->getCombinedModuleSettingByID($id,'missedcall','mcx');
					if($mcx){
						$this->updateOne($id,'external',1);
					} else {
						$this->updateOne($id,'external',0);
					}
				}
			}
		}
	}

	public function ucpDelGroup($id,$display,$data) {
	}

	public function ucpAddGroup($id, $display, $data) {
		$this->ucpUpdateGroup($id,$display,$data);
	}

	public function ucpUpdateGroup($id,$display,$data) {
		if($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'group') {
			if($_POST['mcenabled'] == 'yes') {
				$this->FreePBX->Ucp->setSettingByGID($id,'Missedcall','mcenabled',true);
			} else {
				$this->FreePBX->Ucp->setSettingByGID($id,'Missedcall','mcenabled',false);
			}

			if($_POST['mcrg'] == 'yes') {
				$this->FreePBX->Ucp->setSettingByGID($id,'Missedcall','mcrg',true);
			} else {
				$this->FreePBX->Ucp->setSettingByGID($id,'Missedcall','mcrg',false);
			}

			if($_POST['mcq'] == 'yes') {
				$this->FreePBX->Ucp->setSettingByGID($id,'Missedcall','mcq',true);
			} else {
				$this->FreePBX->Ucp->setSettingByGID($id,'Missedcall','mcq',false);
			}
		}
	}

	/**
	* Hook functionality from userman when a user is deleted
	* @param {int} $id      The userman user id
	* @param {string} $display The display page name where this was executed
	* @param {array} $data    Array of data to be able to use
	*/
	public function ucpDelUser($id, $display, $ucpStatus, $data) {}

	/**
	* Hook functionality from userman when a user is added
	* @param {int} $id      The userman user id
	* @param {string} $display The display page name where this was executed
	* @param {array} $data    Array of data to be able to use
	*/
	public function ucpAddUser($id, $display, $ucpStatus, $data) {
		$this->ucpUpdateUser($id, $display, $ucpStatus, $data);
	}

	/**
	* Hook functionality from userman when a user is updated
	* @param {int} $id      The userman user id
	* @param {string} $display The display page name where this was executed
	* @param {array} $data    Array of data to be able to use
	*/
	public function ucpUpdateUser($id, $display, $ucpStatus, $data) {
		if($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'user') {
			if(isset($_POST['missedcall_enable']) && $_POST['missedcall_enable'] == 'yes') {
				$this->FreePBX->Ucp->setSettingByID($id,'Missedcall','enabled',true);
			} elseif(isset($_POST['missedcall_enable']) && $_POST['missedcall_enable'] == 'no') {
				$this->FreePBX->Ucp->setSettingByID($id,'Missedcall','enabled',false);
			} elseif(isset($_POST['missedcall_enable']) && $_POST['missedcall_enable'] == 'inherit') {
				$this->FreePBX->Ucp->setSettingByID($id,'Missedcall','enabled',null);
			}
		}
	}

	public function ucpConfigPage($mode, $user, $action) {
		if(empty($user)) {
			$enabled = ($mode == 'group') ? true : null;
		} else {
			if($mode == 'group') {
				$enabled = $this->FreePBX->Ucp->getSettingByGID($user['id'],'Missedcall','enabled');
				$enabled = !($enabled) ? false : true;
			} else {
				$enabled = $this->FreePBX->Ucp->getSettingByID($user['id'],'Missedcall','enabled');
			}
		}

		$html = array();
		$html[0] = array(
			"title" => _("Missed Call"),
			"rawname" => "missedcall",
			"content" => load_view(dirname(__FILE__)."/views/ucp_config.php",array("mode" => $mode, "enabled" => $enabled))
		);
		return $html;
	}

	public function doConfigPageInit($page) {
		$userid 	= $_REQUEST['userid'];
		$extension 	= $_REQUEST['extension']?$_REQUEST['extension']:'';
		$internal 	= $_REQUEST['mcinternal']?$_REQUEST['mcinternal']:'';
		$external 	= $_REQUEST['mcexternal']?$_REQUEST['mcexternal']:'';
		$queue 		= $_REQUEST['mcqueue']?$_REQUEST['mcqueue']:'';
		$ringgroup 	= $_REQUEST['mcringgroup']?$_REQUEST['mcringgroup']:'';
		$action 	= $_REQUEST['action']?$_REQUEST['action']:'';
		$view 		= $_REQUEST['view']?$_REQUEST['view']:'';

		//Handle form submissions
		switch ($action) {
			case 'submit':
				$this->update($userid,$extension,$queue,$ringgroup,$internal,$external,'MMP');
			break;
		}
	}

	//Dialplan Methods

	// This method required
	Public function myDialplanHooks(){
		// set priority for doDialplanHook, return true for default of 500 or set;
		return 500;
	}

	// Method 'doDialplanHook' used to generate Asterisk dialplan
	public function doDialplanHook(&$ext, $engine, $priority){
		$modulename = 'missedcall';

		// Retrieve module's feature codes
		$fcc = new \featurecode($modulename, 'missedcall_on');
		$mc_on = $fcc->getCodeActive();
		unset($fcc);
		$fcc = new \featurecode($modulename, 'missedcall_off');
		$mc_off = $fcc->getCodeActive();
		unset($fcc);
		$fcc = new \featurecode($modulename, 'missedcall_toggle');
		$mc_toggle = $fcc->getCodeActive();
		unset($fcc);

		$id = 'app-missedcall';
		$ext->addInclude('from-internal-additional', $id); // Add the include to from-internal
		$ext->add($id, $mc_on, '', new \ext_goto('1', 's', 'app-missedcall-on'));
		$ext->add($id, $mc_off, '', new \ext_goto('1', 's', 'app-missedcall-off'));
		$ext->add($id, $mc_toggle, '', new \ext_goto('1', 's', 'app-missedcall-toggle'));

		$id = 'app-missedcall-on';
		$c = 's';
		$ext->add($id, $c, '', new \ext_macro('user-callerid'));
		$ext->add($id, $c, '', new \ext_agi('missedcallnotify.php,${AMPUSER},enable'));
		$ext->add($id, $c, 'hangup', new \ext_hangup());

		$id = 'app-missedcall-off';
		$c = 's';
		$ext->add($id, $c, '', new \ext_macro('user-callerid'));
		$ext->add($id, $c, '', new \ext_agi('missedcallnotify.php,${AMPUSER},disable'));
		$ext->add($id, $c, 'hangup', new \ext_hangup());

		$id = 'app-missedcall-toggle';
		$c = 's';
		$ext->add($id, $c, '', new \ext_macro('user-callerid'));
		$ext->add($id, $c, '', new \ext_agi('missedcallnotify.php,${AMPUSER},toggle'));
		$ext->add($id, $c, 'hangup', new \ext_hangup());

		$id = 'app-missedcall-hangup';
		$c = '_.';
		$ext->add($id, $c, '', new \ext_noop('Callee: ${EXTEN}'));
		$ext->add($id, $c, '', new \ext_noop('Caller: ${MCEXTEN}'));
		$ext->add($id, $c, '',new \ext_agi('missedcallnotify.php,${MCEXTTOCALL},,${EXTEN},${DB_EXISTS(AMPUSER/${EXTEN}/missedcall)},${DB(AMPUSER/${EXTEN}/missedcall)},${CHANNEL},${DIALSTATUS},${MCQUEUE}'));
		$ext->add($id, $c, 'exit', new \ext_return());

		// need to set an inheritable channel variable so the dialing extension is known at hangup
		$context = "macro-user-callerid";
		$ext->splice($context, 's', "continue", new \ext_set('__MCORGCHAN','${CHANNEL}'),"",3,true);
		$ext->splice($context, 's', "continue", new \ext_set('__MCEXTEN','${AMPUSER}'),"",3,true);
		$ext->splice($context, 's', "continue", new \ext_set('__MCNAME','${CALLERID(name)}'),"",3,true);
		$ext->splice($context, 's', "continue", new \ext_set('__MCNUM','${CALLERID(num)}'),"",3,true);

		// splice hangup handler into dialplan, 'func-apply-sipheaders' gets run on every dial
		$context = 'func-apply-sipheaders';
		$ext->splice($context, "s", "", new \ext_set('CHANNEL(hangup_handler_push)','app-missedcall-hangup,${MCEXTTOCALL},1'),"",1);

		$context = 'macro-dial-one';
		$ext->splice($context, "s", "", new \ext_set('__MCMULTI','${MD5(${DEXTEN}${FROMEXTEN})}'),"",1);
		$ext->splice($context, "s", "", new \ext_set('__MCEXTTOCALL','${EXTTOCALL}'),"",1);

		$context = 'macro-dial';
		$priorities = array("ndloopbegin", "huntstart");
		foreach($priorities as $pri) {
			$ext->splice($context, "s", $pri, new \ext_set('__MCEXTTOCALL','${EXTTOCALL}'),"",1);
		}
		//
		$context = 'macro-hangupcall';
		$exten = 's';
		$ext->splice($context,$exten,'start', new \ext_gosub(1, '${EXTEN}', 'app-missedcall-hangup'));

		// splice inheritable channel variable into each ring group
		$context = 'ext-group';
		$rgroups = $this->getRingGroups();
		if (is_array($rgroups)) {
			foreach ($rgroups as $exten) {
				$ext->splice($context, $exten, 1, new \ext_set('__MCGROUP','${EXTEN}'));
			}
		}
		// splice inheritable channel variable into each queue
		$context = 'ext-queues';
		$queues = $this->getQueues();
		if (is_array($queues)) {
			foreach ($queues as $exten) {
				$ext->splice($context, $exten, 1, new \ext_set('__MCQUEUE','${EXTEN}'));
			}
		}
	}

	// Module specific methods

	public function asm(){
		return $this->astman;
	}

	/**
	 * privvate getRingGroups get a list of all ring groups on the system
	 * @param
	 * @return Returns 1D array of all ring group numbers or null if none.
	 **/
	private function getRingGroups() {
		$ringgroup_list= $this->FreePBX->Ringgroups->listRinggroups(true);
		foreach ($ringgroup_list as $item) {
			$rg[] = $item['grpnum'];
		}
		if (is_array($rg)) {
			return $rg;
		} else {
			return null;
		}
	}

	/**
	 * private getQueues get a list of all queues on the system
	 * @param
	 * @return Returns 1D array of all queue numbers or null if none.
	 */
	private function getQueues() {
		$retval = $this->FreePBX->Queues->search('',$result);
		$queues = array();
		if(!empty($result)){
			foreach ($result as $queue) {
				$pattern = "~^.*\((.*)\).*$~";
				if(preg_match($pattern, $queue['text'], $retval)) {
					$queues[]=$retval[1];
				}
			}
		}
		if (is_array($queues)) {
			return $queues;
		} else {
			return null;
		}
	}

	/**
	 * getUsers get a list of all ampusers on the system
	 * @param
	 * @return Returns 1D array of all system ampusers or null if none.
	 */
	public function getUsers() {
		$users = array();
		$userman = $this->FreePBX->userman->getAllUsers();
		foreach ($userman as $user) {
			$users[$user['id']] = $user['default_extension'];
		}
		if (is_array($users)) {
			return $users;
		} else {
			return null;
		}
	}

	/**
	 * private getEmail returns email address associted with user set for primary extension
	 * @param string $exten
	 * @return Returns string with email address or null if none.
	 */
	private function getEmail($id) {
		if ($id) {
			$details = $this->FreePBX->Userman()->getUserByID($id);
			if(is_array($details) && !empty($details['email'])){
				$email = $details['email'];
			}			
		}
		if (!empty($email)) {
			// should we validate if string is valid email address?
			return $email;
		} else {
			return null;
		}
	}

	/**
	 * getStatus retuns whether missed calls is enabled or disabled for a specific extension
	 * @param string $id
	 * @return Returns true or false
	 */
	public function getStatus($userid) {
		$query = "SELECT * FROM missedcall WHERE userid= ?";
		$stmt = $this->db->prepare($query);
		$stmt->execute(array($userid));
		$data = $stmt->fetch(\PDO::FETCH_ASSOC);
		if(isset($data['notification'])){
			return $data['notification'];
		}
		return 0;
	}

	/**
	 * Enable missed call notification for specific extension
	 * @param string $exten
	 * @return
	 */
	public function misscallEnable($userid,$dbinsert = false,$from = false) {
		$user = $this->userman->getUserByID($userid);
		$exten = $user['default_extension'];
		// disable in DB
		if($dbinsert){
			$sql = 'INSERT INTO `missedcall` (`notification`, `userid`,`extension`) VALUES (:value,:userid, :ext)';
		} else {
			$sql = "UPDATE `missedcall` SET `extension`= :ext ,`notification` = :value WHERE `userid` = :userid";
		}
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':userid'=>$userid,':ext'=>$exten,':value'=>1));
		//update the Userman
		if($from){
			$this->userman->setModuleSettingByID($userid,'missedcall','mcenabled',true);
		}
		$response = $this->FreePBX->astman->database_put("AMPUSER","$exten/missedcall", "enable");
		return $response;
	}

	/**
	 * Disable missed call notification for specific extension
	 * @param string $exten
	 * @return
	 */
	public function misscallDisable($userid,$dbinsert = false,$from = false) {
		$user = $this->userman->getUserByID($userid);
		$exten = $user['default_extension'];
		// disable in DB
		if($dbinsert){
			$sql = 'INSERT INTO `missedcall` (`notification`, `userid`,`extension`) VALUES (:value,:userid, :ext)';
		} else {
			$sql = "UPDATE `missedcall` SET `extension`= :ext ,`notification` = :value WHERE `userid` = :userid";
		}
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':userid'=>$userid,':ext'=>$exten,':value'=>0));
		if($from){
			$this->userman->setModuleSettingByID($userid,'missedcall','mcenabled',false);
		}
		$response = $this->FreePBX->astman->database_put("AMPUSER","$exten/missedcall", "disable");
		return $response;
	}

	/**
	 * Toggle missed call notification for specific extension
	 * @param string $exten
	 * @return the status of the extension after the toggle string 'enable' or 'disable'
	 */
	public function Toggle($id) {
		$status 	= $this->getStatus($id);
		if ($status == 0) {
			$resp = $this->misscallEnable($id,false,true);
			return 'enable';
		} else {
			$resp = $this->misscallDisable($id,false,true);
			return 'disable';
		}
	}

	//Module getters
	/**
	 * get Gets all missed call params for specific extension
	 * @param string $userid
	 * @param getby by userid or extension
	 * @return Returns array of all params.
	 */
	public function get($userid,$getby='userid'){
		if($getby =='userid'){
			$sql = "SELECT * FROM `missedcall` WHERE `userid` = :userid";
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(':userid',$userid, \PDO::PARAM_INT);
		}else {
			$sql = "SELECT * FROM `missedcall` WHERE `extension` = :extension";
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(':extension',$userid, \PDO::PARAM_INT);
		}			
		$stmt->execute();
		$ret = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (! empty($ret))
		{
			$ret['email'] = $this->getEmail($ret['userid']);
			$ret['enable'] = $ret['notification'];
		}
		return $ret;
	}

	/**
	 * getAllUsers : Getting all users with their status from the database.
	 *
	 * @return array
	 */
	public function getAllUsers(){
		$sql = "SELECT * FROM missedcall";
		$stm = $this->db->prepare($sql);
		$stm->execute();
		$ret = $stm->fetchall(\PDO::FETCH_ASSOC);
		return $ret;
	}

	/*  $id : userman userid
		$extension : userman users extension
		$queue : Queue enabled 
		$ringgroup : ringgrroup enabled 
		$internal : internal enabled
		$external : external enabled
		$updatefrom : userman( value based on userman settings), MMS( Missedcall Module Setttins)
						We need to sync the settings from userman to MMP and MMP to Userman
	*/
	public function update($id,$extension,$queue,$ringgroup,$internal,$external,$updatefrom='userman'){
		// change bools to 1/0
		$queue 		= $queue?1:0;
		$ringgroup 	= $ringgroup?1:0;
		$internal 	= $internal?1:0;
		$external 	= $external?1:0;
		if($updatefrom == 'userman') {
			//userman will have enable, ringgroup,queue 
			$mcenabled=	$this->userman->getCombinedModuleSettingByID($id,'missedcall','mcenabled');
			$queue = $this->userman->getCombinedModuleSettingByID($id,'missedcall','mcq');
			$ringgroup = $this->userman->getCombinedModuleSettingByID($id,'missedcall','mcrg');
			$internal = $this->userman->getCombinedModuleSettingByID($id,'missedcall','mci');
			$external = $this->userman->getCombinedModuleSettingByID($id,'missedcall','mcx');
		}
		// check to see if this is a new record or updating old record
		$query = "SELECT * from missedcall where userid = ?";
		$stm = $this->db->prepare($query);
		$stm->execute(array($id));
		$result = $stm->fetch(\PDO::FETCH_ASSOC);

		if (isset($result['userid'])) {
			$sql = 'UPDATE `missedcall` SET `queue` = :queue, `ringgroup` = :ringgroup, `internal` = :internal, `external` = :external, `extension` = :extension WHERE `userid` = :userid';
		} else {
			$sql = 'REPLACE INTO `missedcall` (`userid`,`extension`,`queue`,`ringgroup`,`internal`,`external`) VALUES (:userid, :extension,:queue,:ringgroup,:internal,:external)';
		}
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':userid', $id, \PDO::PARAM_INT);
		$stmt->bindParam(':queue', $queue, \PDO::PARAM_INT);
		$stmt->bindParam(':ringgroup', $ringgroup, \PDO::PARAM_INT);
		$stmt->bindParam(':extension', $extension, \PDO::PARAM_INT);
		$stmt->bindParam(':internal', $internal, \PDO::PARAM_INT);
		$stmt->bindParam(':external', $external, \PDO::PARAM_INT);
		$stmt->execute();
		return;
	}

	public function updateOne($userid,$type,$value,$data=[],$umupdate=false){
		// check to see if this is a new record or updating old record
		$query = "SELECT * from missedcall where userid = ?";
		$stm = $this->db->prepare($query);
		$stm->execute(array($userid));
		$result = $stm->fetch(\PDO::FETCH_ASSOC);
		$dbinsert = false;
		if(!isset($result['userid'])){
			$dbinsert = true;
		}
		if($type == "enable" || $type == "notification" ) {
			if ($value == 1) {
				$this->misscallEnable($userid,$dbinsert,$umupdate);
			} else {
				$this->misscallDisable($userid,$dbinsert,$umupdate);
			}
			return;
		}
		$key = '';
		switch($type) {
			case "queue":
				$key = 'queue';
			break;
			case "ringgroup":
				$key = 'ringgroup';
			break;
			case "internal":
				$key = 'internal';
			break;
			case "external":
				$key = 'external';
			break;
			default:
				return;
			break;
		}
		// change bools to 1/0
		$value =  $value?1:0;
		//get extension number from userman
		$user = $this->userman->getUserByID($userid);
		$extension = $user['default_extension'];
		if($extension == ""){
			// no extension then no need to add any settings
			return;
		}
		if (!$dbinsert) {
			$sql = 'UPDATE `missedcall` SET `'.$key.'` = :value ,`extension`=:extension WHERE `userid` = :userid';
		} else {
			$sql = 'INSERT INTO `missedcall` (`'.$key.'`, `userid`,`extension`) VALUES (:value,:userid, :extension)';
		}

		$stmt = $this->db->prepare($sql);
		$stmt->execute(array("value" => $value,"userid"=>$userid, "extension" => $extension));
		return;
	}

	public function backup(){

	}
	public function restore($backup){

	}
	public function genConfig() {

	}

	public function getAllStatuses() {

	}

	public function getStatusByExtension($extension) {

	}

	public function setStatusByExtension($extension, $state = '') {

	}

	public function sendModuleLicenseInformation($data) {
		return array(
			array(
				"name" => "Missed Call Notify",
				"expires" => true,
				"keyname" => "missedcallnotify_exp",
				"tie_in" => array(),
				"module" => true,
			),
		);
	}

	private function deleteTable(){
		$sql = "DROP TABLE IF EXISTS `missedcall`";

		try {
			$sth = $this->db->prepare($sql);
			return $sth->execute();

		} catch(PDOException $e) {
			return $e->getMessage();
		}
	}

    /**
     * Function to get stored templates
     */
    public function getMailTemplate($templateName, $key = false)
    {
        try {
            $sql = "SELECT templateContent FROM missedcall_email_templates WHERE name = :templateName";
            $sth = $this->db->prepare($sql);
            $sth->bindParam(':templateName', $templateName);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
            $results = !empty($result['templateContent']) ? json_decode($result['templateContent'], true) : array();
            if ($key) {
                return isset($results[$key]) ? $results[$key] : null;
            } else {
                return $results;
            }
        } catch (\PDOException $e) {
            throw new \PDOException($e);
        }
    }

    /**
     * Function to replace templates variables
     */
    public function replaceTemplateVariables($template, $variablesData)
    {
        if ($template) {
            if (preg_match('/{{([\w|\d]*)}}/', $template)) {
                preg_match_all('/{{([\w|\d]*)}}/', $template, $matches);
                foreach ($matches[1] as $match) {
                    $replacement = !empty($variablesData[$match]) ? $variablesData[$match] : '';
                    $template = str_replace('{{' . $match . '}}', $replacement, $template);
                }
            }
        }
        return $template;
    }


    /**
     * Function to update/insert mail template to DB
     *
     * @param String $templateName unique name for the email template
     * @param Array $templateContent is an array which consists of email type, subject, body
     * 
     * @throws PDOException
     * @return Boolean 
     */
    public function setMailTemplate($templateName, $templateContent)
    {
        try {
            // Validating and sanitizing contents

            if (!is_array($templateContent)) {
                return false;
            }

            if (isset($templateContent['type']) && isset($templateContent['body'])) {
                if ($templateContent['type'] == self::EMAIL_TYPE_HTML) {
                    $templateContent['body'] = filter_var(htmlentities($templateContent['body'], ENT_QUOTES), FILTER_SANITIZE_STRING);
                } else {
                    $templateContent['body'] = filter_var($templateContent['body'], FILTER_SANITIZE_STRING);
                }
            }

            if (isset($templateContent['type']) && isset($templateContent['subject'])) {
                if ($templateContent['type'] == self::EMAIL_TYPE_HTML) {
                    $templateContent['subject'] = filter_var(htmlentities($templateContent['subject'], ENT_QUOTES), FILTER_SANITIZE_STRING);
                } else {
                    $templateContent['subject'] = filter_var($templateContent['subject'], FILTER_SANITIZE_STRING);
                }
            }

            $templateContent = json_encode($templateContent);
            $time = time();
            $sql = "INSERT INTO missedcall_email_templates (`name`, `time`, `templateContent`) VALUES (:templateName,:time, :templateContent) ON DUPLICATE KEY UPDATE templateContent = :templateContent, time = :time";
            $sth = $this->db->prepare($sql);
            $sth->bindParam(':templateName', $templateName);
            $sth->bindParam(':time', $time);
            $sth->bindParam(':templateContent', $templateContent);
            return $sth->execute();
        } catch (\PDOException $e) {
            throw new \PDOException($e);
        }
    }
	
    public function saveEmailSettings($request)
    {
		if (!isset($request['emailType']) || empty($request['emailType'])) {
			return array('status' => false, 'message' => _('Email type is required'));
		}

		$emailType = filter_var(isset($request['emailType']) ? $request['emailType'] : self::EMAIL_TYPE_HTML, FILTER_SANITIZE_STRING);

		if (!isset($request['subject']) || empty($request['subject'])) {
			return array('status' => false, 'message' => _('Email subject is required'));
		}

		if (!isset($request['body']) || empty($request['body'])) {
			return array('status' => false, 'message' => _('Email body is required'));
		}

		$subject = filter_var(isset($request['subject']) ? $request['subject'] : self::EMAIL_SUBJECT, FILTER_SANITIZE_STRING);
		$body = isset($_REQUEST['body']) ? $_REQUEST['body'] : file_get_contents(__DIR__ . '/views/mail.tpl');

		$templateName = 'notification_mail';
		$this->setMailTemplate($templateName, [
			'type' => $emailType,
			'subject' => $subject,
			'body' => $body,
		]);
        return array('status' => true, 'message' => _('Email settings saved successfully'));
    }

	public function getMailSettingsForm(){
		
		$mailTemplate = $this->getMailTemplate('notification_mail');
		$emailType = isset($mailTemplate['type']) ? $mailTemplate['type'] : '';
		$subject = isset($mailTemplate['subject']) ? $mailTemplate['subject'] : '';
		$body = isset($mailTemplate['body']) ? $mailTemplate['body'] : '';

		$templateData = [
				[
					'identifier' => 'notification',
					'type' => [
						'defaultValue' => $emailType ?: self::EMAIL_TYPE_HTML,
						'helpText' => _('Mail Type'),
						'label' => _('Mail Type'),
						'display' => true
					],
					'subject' => [
						'defaultValue' => $subject ?: Self::EMAIL_SUBJECT,
					'helpText' => '' . _('Text to be used for the subject of the Missed call notification email. Pre-defined variables are:') . '
											<ul>
												<li><b>calleridname</b>: CallerID Name</li>
											</ul>
											<b>Please enclose these variables with double curly braces {{VARIABLE_NAME}}</b>',
					'label' => _('Mail Subject'),
						'display' => true
					],
					'body' => [
						'defaultValue' => $body ?: nl2br(file_get_contents(__DIR__ . '/views/mail.tpl')),
						'helpText' => '' . _('Text to be used for the body of the Missed call notification email. Pre-defined variables are:') . '
												<ul>
													<li><b>callerid</b>: CallerID Number</li>
													<li><b>calleridname</b>: CallerID Name</li>
													<li><b>datetime</b>: Date/Time</li>
													<li><b>brand</b>: FreePBX</li>
													<li><b>calltype</b>: Call Type</li>
												</ul>
											<b>Please enclose these variables with double curly braces {{VARIABLE_NAME}}</b>',
						'label' => _('Mail Body'),
						'display' => true
					]
				]
			];

		return $this->FreePBX->Mail->getEmailTemplateForm($templateData);
	}

	//BulkHandler hooks
	public function bulkhandlerGetTypes() {
		return array(
			'missedcall' => array(
				'name' => _('missedcall'),
				'description' => _('Import/Export missedcall')
			)
		);
	}
	
	public function bulkhandlerGetHeaders($type) {
		switch($type){
			case 'missedcall':
				$headers = array();
				$headers['username'] = array(
					'required' => true,
					'identifier' => _("username"),
					'description' => _("The user name of missedcall")
				);
				$headers['notification'] = array(
					'required' => false,
					'identifier' => _("notification"),
					'description' => _("notification of missedcall")
				);
				$headers['extension'] = array(
					'required' => false,
					'identifier' => _("extension"),
					'description' => _("extension of missedcall")
				);
				$headers['queue'] = array(
					'required' => false,
					'identifier' => _("queue"),
					'description' => _("queue of missedcall")
				);
				$headers['ringgroup'] = array(
					'required' => false,
					'identifier' => _("ringgroup"),
					'description' => _("ringgroup of missedcall")
				);
				$headers['internal'] = array(
					'required' => false,
					'identifier' => _("internal"),
					'description' => _("internal of missedcall")
				);
				$headers['external'] = array(
					'required' => false,
					'identifier' => _("external"),
					'description' => _("external of missedcall")
				);
			break;
		}
		return $headers;
	}

	public function bulkhandlerExport($type) {
		$data = NULL;
		switch ($type) {
			case 'missedcall':
				$sql = "SELECT m.userid,u.username,m.notification,m.extension,m.queue,m.ringgroup,m.internal,m.external FROM missedcall as m ";
				$sql .="left join userman_users as u ON u.id=m.userid";
				$stm = $this->db->prepare($sql);
				$stm->execute();
				$data = $stm->fetchall(\PDO::FETCH_ASSOC);
			break;
		}
		return $data;
	}

	public function bulkhandlerImport($type, $rawData) {
		$ret = NULL;
		switch ($type) {
			case 'missedcall':
				if (is_array($rawData) && count($rawData) >0) {
					foreach ($rawData as $data) {
						$this->addMissedcallRow($data);
					}
				}
				$ret = array(
					'status' => true,
				);
			break;
		}
		return $ret;
	}

	public function addMissedcallRow($data) {
		$params =array();
		$params['userid'] = isset($data['userid'])? $data['userid'] :0;
		$params['notification'] = isset($data['notification'])? $data['notification'] :NULL;
		$params['extension'] = isset($data['extension'])? $data['extension'] :NULL;
		$params['queue'] = isset($data['queue'])? $data['queue'] :NULL;
		$params['ringgroup'] = isset($data['ringgroup'])? $data['ringgroup'] :NULL;
		$params['internal'] = isset($data['internal'])? $data['internal'] :NULL;
		$params['external'] = isset($data['external'])? $data['external'] :NULL;
		$ret = true;
		$sql = "SELECT COUNT(*) = 0 as is_new FROM missedcall WHERE userid = :userid";
		$sth = $this->db->prepare($sql);
		$sth->bindParam(":userid", $params['userid']);
		$sth->execute();
		$is_new = $sth->fetch();
		$is_new = (bool) $is_new['is_new'];
		if ($is_new)
			$sql = "INSERT INTO missedcall (userid, notification, extension, queue, ringgroup, internal, external)
					VALUES (:userid, :notification, :extension, :queue, :ringgroup, :internal, :external);";
		else
			$sql = "UPDATE missedcall SET userid = :userid, notification = :notification, extension = :extension, queue = :queue, ringgroup = :ringgroup,
						internal = :internal, external = :external WHERE userid = :userid";
		$stmt = $this->db->prepare($sql);
		$ret = $stmt->execute($params);
		return $ret;
	}
}
