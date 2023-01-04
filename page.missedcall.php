<?php
	/**
	 * This guy calls the initial page through showPage function in Missedcall.class.php
	 * echo FreePBX::create()->Missedcall->showPage();
	 */
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	$freepbx 	= \FreePBX::Create();
	$mcn 		= $freepbx->Missedcall();
	$um 		= $freepbx->Userman();

	/**
	 * License for all code of this FreePBX module can be found in the license file inside the module directory
	 * Copyright 2013-2015 Schmooze Com Inc.
	 */
	$request	= $_REQUEST;
	$tabindex 	= 0;
	$dispnum 	= 'missedcall'; //used for switch on config.php
	$heading 	= '<i class="fa fa-envelope"></i> '._("Missed Call Notification");
	$view 		= isset($request['view']) ? $request['view'] : '';

	switch($view){
		case "form":
			$border = "full";
			if($request['extdisplay'] != ''){
				$heading 		.= ": Edit ".ltrim($request['extdisplay'],'GRP-');
				$user 	  		 = $um->getUserByDefaultExtension($request["extdisplay"]);
				$request["mcrg"] = $um->getCombinedModuleSettingByID($user['id'],'missedcall','mcrg',false, true);
				$request["mcq"]  = $um->getCombinedModuleSettingByID($user['id'],'missedcall','mcq', false, true);

				$content = load_view(__DIR__.'/views/form.php', array('request' => $request));
			}else{
				$content = load_view(__DIR__.'/views/grid.php', array('error' => $error));
			}
		break;
		default:
			$border 	= "no";
			$content	= load_view(__DIR__.'/views/grid.php', array('error' => $error));
		break;
	}
	
	$emailLayout = $mcn->getMailSettingsForm();

?>

<div class="container-fluid">
	<h1><?php echo $heading ?></h1>
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">
				<div class="display <?php echo $border?>-border">
					<div role="tabpanel">
						<ul class="nav nav-tabs" role="tablist">
							<li role="presentation" class="active">
								<a href="#notiExtensions" aria-controls="notiExtensions" role="tab" data-toggle="tab" aria-expanded="true">
									<?php echo _('Extensions'); ?>
								</a>
							</li>
							<li role="presentation" class="">
								<a href="#emailSettings" aria-controls="emailSettings" role="tab" data-toggle="tab"
									aria-expanded="false">
									<?php echo _('Email Settings'); ?>
								</a>
							</li>
						</ul>
						<div class="tab-content">
							<div role="tabpanel" id="notiExtensions" class="tab-pane display active">
								<?php echo $content ?>
							</div>
							<div role="tabpanel" id="emailSettings" class="tab-pane display">
								<form>
									<?php echo $emailLayout ?>

									<!-- Start: Submit Button  -->
									<div class="row" id="submitEmailSettings">
										<div class="col-md-12 text-right">
											<br />
											<button type="submit" class="btn btn-primary"><?php echo _('Save Email Settings'); ?></button>
											<br />
										</div>
									</div>
									<!-- End: Submit Button  -->
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
