<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="missedcall_enable"><?php echo _("Enable Missed Call")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="missedcall_enable"></i>
					</div>
					<div class="col-md-9">
						<span class="radioset">
							<input type="radio" name="missedcall_enable" id="missedcall_enable_yes" value="yes" <?php echo ($enabled) ? 'checked' : ''?>>
							<label for="missedcall_enable_yes"><?php echo _('Yes')?></label>
							<input type="radio" name="missedcall_enable" id="missedcall_enable_no" value="no" <?php echo (!is_null($enabled) && !$enabled) ? 'checked' : ''?>>
							<label for="missedcall_enable_no"><?php echo _('No')?></label>
							<?php if($mode == "user") {?>
								<input type="radio" id="missedcall_enable_inherit" name="missedcall_enable" value='inherit' <?php echo is_null($enabled) ? 'checked' : ''?>>
								<label for="missedcall_enable_inherit"><?php echo _('Inherit')?></label>
							<?php } ?>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="missedcall_enable-help" class="help-block fpbx-help-block"><?php echo _("Allow this user to receive an email for any missed call.")?></span>
		</div>
	</div>
</div>