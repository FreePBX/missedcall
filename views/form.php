<?php
	$mc_ext =  $_GET['extdisplay'];
	$mc_params = FreePBX::Missedcall()->get($mc_ext);

	$rgstatus = "";
	if($request["mcrg"] == "0" || empty($request["mcrg"]) ){
		$mc_params['ringgroup'] = false;		
		$rgstatus = "disabled";
	}

	$qstatus = "";
	if($request["mcq"] == "0" || empty($request["mcq"]) ){	
		$mc_params['queue'] = false;
		$qstatus = "disabled";
	}

	$internal = '<span class="radioset">';
	$internal .= '<input type="radio" name="mcinternal" id="mcinternal'.$mc_ext.'yes" '.($mc_params['internal'] == true?'CHECKED':'').' value="1">';
	$internal .= '<label for="mcinternal'.$mc_ext.'yes">'._("Yes").'</label>';
	$internal .= '<input type="radio" name="mcinternal" id="mcinternal'.$mc_ext.'no" '.($mc_params['internal'] == true?'':'CHECKED' ).' value="0">';
	$internal .= '<label for="mcinternal'.$mc_ext.'no">'._("No").'</label>';
	$internal .= '</span>';

	$external = '<span class="radioset">';
	$external .= '<input type="radio" name="mcexternal" id="mcexternal'.$mc_ext.'yes" '.($mc_params['external'] == true?'CHECKED':'').' value="1">';
	$external .= '<label for="mcexternal'.$mc_ext.'yes">'._("Yes").'</label>';
	$external .= '<input type="radio" name="mcexternal" id="mcexternal'.$mc_ext.'no" '.($mc_params['external'] == true?'':'CHECKED' ).' value="0">';
	$external .= '<label for="mcexternal'.$mc_ext.'no">'._("No").'</label>';
	$external .= '</span>';

	$queue = '<span class="radioset">';
	$queue .= '<input '.$qstatus.' type="radio" name="mcqueue" id="mcqueue'.$mc_ext.'yes" '.($mc_params['queue'] == true?'CHECKED':'').' value="1">';
	$queue .= '<label for="mcqueue'.$mc_ext.'yes">'._("Yes").'</label>';
	$queue .= '<input '.$qstatus.' type="radio" name="mcqueue" id="mcqueue'.$mc_ext.'no" '.($mc_params['queue'] == true?'':'CHECKED' ).' value="0">';
	$queue .= '<label for="mcqueue'.$mc_ext.'no">'._("No").'</label>';
	$queue .= '</span>';

	$ringgroup = '<span class="radioset">';
	$ringgroup .= '<input '.$rgstatus.' type="radio" name="mcringgroup" id="mcringgroup'.$mc_ext.'yes" '.($mc_params['ringgroup'] == true?'CHECKED':'').' value="1" >';
	$ringgroup .= '<label for="mcringgroup'.$mc_ext.'yes">'._("Yes").'</label>';
	$ringgroup .= '<input '.$rgstatus.' type="radio" name="mcringgroup" id="mcringgroup'.$mc_ext.'no" '.($mc_params['ringgroup'] == true?'':'CHECKED' ).' value="0" >';
	$ringgroup .= '<label for="mcringgroup'.$mc_ext.'no">'._("No").'</label>';
	$ringgroup .= '</span>';
?>

<form action="?display=missedcall" method="post" class="fpbx-submit" id="missedcallform" name="missedcallform" >
	<input type="hidden" name='extension' value="<?php echo $mc_ext ?>">
	<input type="hidden" name='action' value="submit">

	<!--internal-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="internal"><?php echo _("Internal Calls") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="internal"></i>
						</div>
						<div class="col-md-9">
							<?php echo $internal?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="internal-help" class="help-block fpbx-help-block"><?php echo _("Enable missed call notifications for missed internal calls")?></span>
			</div>
		</div>
	</div>
	<!--end internal-->

	<!--external-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="external"><?php echo _("External Calls") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="external"></i>
						</div>
						<div class="col-md-9">
							<?php echo $external?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="external-help" class="help-block fpbx-help-block"><?php echo _("Enable missed call notifications for missed external calls")?></span>
			</div>
		</div>
	</div>
	<!--end external -->

	<!--queue-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="queue"><?php echo _("Queue Calls") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="queue"></i>
						</div>
						<div class="col-md-9">
							<?php echo $queue ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="queue-help" class="help-block fpbx-help-block"><?php echo _("Enable missed call notifications for missed calls from queues")?></span>
			</div>
		</div>
	</div>
	<!--end queue -->

	<!-- ringgroup -->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="ringgroup"><?php echo _("Ring Group Calls") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="ringgroup"></i>
						</div>
						<div class="col-md-9">
							<?php echo $ringgroup ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="ringgroup-help" class="help-block fpbx-help-block"><?php echo _("Enable missed call notifications for missed calls from ring groups")?></span>
			</div>
		</div>
	</div>
	<!--end ringgroup -->

	<button type="button" id="back" class="btn btn-primary"><i class="fa fa-reply"></i> <?php echo _("Back") ?></button>
	<!--END Body-->
</form>
