<?php if(!empty($error)) {?>
<div class="alert alert-danger" role="alert">
	<?php echo $error ?>
</div>
<?php } ?>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-4 control-label">
						<label for="mcenabled"><?php echo _('Enabled')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="mcenabled"></i>
					</div>
					<div class="col-md-8">
						<span class="radioset">
							<input type="radio" name="mcenabled" class="form-control " id="mcenabled0" value="true" <?php echo ($mcenabled) ? 'checked' : ''?>><label for="mcenabled0"><?php echo _('Yes')?></label>
							<input type="radio" name="mcenabled" class="form-control " id="mcenabled1" value="false" <?php echo (!is_null($mcenabled) && !$mcenabled) ? 'checked' : ''?>><label for="mcenabled1"><?php echo _('No')?></label>
							<?php if($mode == "user") {?>
								<input type="radio" id="mcenabled2" name="mcenabled" value='inherit' <?php echo is_null($mcenabled) ? 'checked' : ''?>>
								<label for="mcenabled2"><?php echo _('Inherit')?></label>
							<?php } ?>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="mcenabled-help" class="help-block fpbx-help-block"><?php echo _('Enable the Missed Call Notifications.')?></span>
		</div>
	</div>
</div>

<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-4 control-label">
						<label for="mcrg"><?php echo _('Call from Ring Group')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="mcrg"></i>
					</div>
					<div class="col-md-8">
						<span class="radioset">
							<input type="radio" name="mcrg" class="form-control " id="mcrg0" value="true" <?php echo ($mcrg) ? 'checked' : ''?>><label for="mcrg0"><?php echo _('Yes')?></label>
							<input type="radio" name="mcrg" class="form-control " id="mcrg1" value="false" <?php echo (!is_null($mcrg) && !$mcrg) ? 'checked' : ''?>><label for="mcrg1"><?php echo _('No')?></label>
							<?php if($mode == "user") {?>
								<input type="radio" id="mcrg2" name="mcrg" value='inherit' <?php echo is_null($mcrg) ? 'checked' : ''?>>
								<label for="mcrg2"><?php echo _('Inherit')?></label>
							<?php } ?>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="mcrg-help" class="help-block fpbx-help-block"><?php echo _('Enable the Missed Call from Ring Group. And Premission to Change the Ring Group call settings')?></span>
		</div>
	</div>
</div>

<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-4 control-label">
						<label for="mcq"><?php echo _('Call from Queue')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="mcq"></i>
					</div>
					<div class="col-md-8">
						<span class="radioset">
							<input type="radio" name="mcq" class="form-control " id="mcq0" value="true" <?php echo ($mcq) ? 'checked' : ''?>><label for="mcq0"><?php echo _('Yes')?></label>
							<input type="radio" name="mcq" class="form-control " id="mcq1" value="false" <?php echo (!is_null($mcq) && !$mcq) ? 'checked' : ''?>><label for="mcq1"><?php echo _('No')?></label>
							<?php if($mode == "user") {?>
								<input type="radio" id="mcq2" name="mcq" value='inherit' <?php echo is_null($mcq) ? 'checked' : ''?>>
								<label for="mcq2"><?php echo _('Inherit')?></label>
							<?php } ?>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="mcq-help" class="help-block fpbx-help-block"><?php echo _('Enable the Missed Call from Queue. And Premission to Change the Queue call settings')?></span>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-4 control-label">
						<label for="mci"><?php echo _('Call from Internal')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="mci"></i>
					</div>
					<div class="col-md-8">
						<span class="radioset">
							<input type="radio" name="mci" class="form-control " id="mci0" value="true" <?php echo ($mci) ? 'checked' : ''?>><label for="mci0"><?php echo _('Yes')?></label>
							<input type="radio" name="mci" class="form-control " id="mci1" value="false" <?php echo (!is_null($mci) && !$mci) ? 'checked' : ''?>><label for="mci1"><?php echo _('No')?></label>
							<?php if($mode == "user") {?>
								<input type="radio" id="mci2" name="mci" value='inherit' <?php echo is_null($mci) ? 'checked' : ''?>>
								<label for="mci2"><?php echo _('Inherit')?></label>
							<?php } ?>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="mci-help" class="help-block fpbx-help-block"><?php echo _('Enable the Missed Call from Internal.And Premission to Change the Internal call settings')?></span>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-4 control-label">
						<label for="mcx"><?php echo _('Call from External')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="mcx"></i>
					</div>
					<div class="col-md-8">
						<span class="radioset">
							<input type="radio" name="mcx" class="form-control " id="mcx0" value="true" <?php echo ($mcx) ? 'checked' : ''?>><label for="mcx0"><?php echo _('Yes')?></label>
							<input type="radio" name="mcx" class="form-control " id="mcx1" value="false" <?php echo (!is_null($mcx) && !$mcx) ? 'checked' : ''?>><label for="mcx1"><?php echo _('No')?></label>
							<?php if($mode == "user") {?>
								<input type="radio" id="mcx2" name="mcx" value='inherit' <?php echo is_null($mcx) ? 'checked' : ''?>>
								<label for="mcx2"><?php echo _('Inherit')?></label>
							<?php } ?>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="mcx-help" class="help-block fpbx-help-block"><?php echo _('Enable the Missed Call from Ring Group. And Premission to Change the external call settings')?></span>
		</div>
	</div>
</div>
