<?php
	/**
	 * This guy calls the initial page through showPage function in Missedcall.class.php
	 * echo FreePBX::create()->Missedcall->showPage();
	 */
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	$freepbx 	= \FreePBX::Create();
	$mcn 		= $freepbx->Missedcall();
	$um 		= $freepbx->Userman;

	/**
	 * License for all code of this FreePBX module can be found in the license file inside the module directory
	 * Copyright 2013-2015 Schmooze Com Inc.
	 */
	$request	= $_REQUEST;
	$tabindex 	= 0;
	$dispnum 	= 'missedcall'; //used for switch on config.php
	$heading 	= '<i class="fa fa-envelope"></i> '._("Missed Call Notification");
	$view 		= isset($request['view']) ? $request['view'] : '';
	$emailLayout= false;
	switch($view){
		case "form":
			$border = "full";
			if($request['userid'] != ''){
				$heading 		.= ": Edit ".ltrim($request['userid'],'GRP-');
				$user 	  		 = $um->getUserByID($request["userid"]);
				$request["mcrg"] = $um->getCombinedModuleSettingByID($user['id'],'missedcall','mcrg',false, true);
				$request["mcq"]  = $um->getCombinedModuleSettingByID($user['id'],'missedcall','mcq', false, true);
				$request['internal'] = $um->getCombinedModuleSettingByID($user['id'],'missedcall','mci');
				$request['external'] = $um->getCombinedModuleSettingByID($user['id'],'missedcall','mcx');
		
				$content = load_view(__DIR__.'/views/form.php', array('request' => $request));
			}else{
				$content = load_view(__DIR__.'/views/grid.php', array('error' => $error));
			}
		break;
		default:
			$border 	= "no";
			$content	= load_view(__DIR__.'/views/grid.php', array('error' => $error));
			$emailLayout = $mcn->getMailSettingsForm();
		break;
	}

?>

<div class="container-fluid">
	<h1 class='mb-3'><?php echo $heading ?></h1>
	<?php
    // At some point we can probably kill this... Maybe make is a 1 time panel that may be dismissed
    $box_info_description = "<p style='margin-bottom:0px;'>" . sprintf(_("A missed call notification feature that alerts the user when a call has been made to their device / extension but was not answered. This feature typically sends an alert to the user via email, indicating that a call was missed and providing the caller's phone number or contact information. This feature is designed to help users quickly and easily return calls they may have missed.")) . "</p>";
    $box_info_description .= "<p>" . _("For more information see: ") . "<a href='https://wiki.freepbx.org/display/FPG/MissedCall+Notification' target='_blank'>https://wiki.freepbx.org/display/FPG/MissedCall+Notification</a> </p>";
    echo show_help($box_info_description, sprintf(_('What is Missed Call Notification ?')), false, true, "info");
    unset($box_info_description);
	if($view != 'form'){
    ?>
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
								<div class="alert alert-warning text-center" role="alert" style="margin-bottom: 10px;margin-top: 10px;">
									<?php
									$link = '<a href="config.php?display=userman"> Userman </a>';
									echo sprintf( _("Note : Email address is mandatory to enable the missed call notification. If extension is not listed here then please edit extension settings in %s and update the email address"),$link) ?>
								</div>
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
	<?php }else{
	?>
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">
				<div class="display <?php echo $border?>-border">
					<?= $content; ?>
				</div>
			</div>
		</div>
	</div>
	<?php
	} ?>
</div>
