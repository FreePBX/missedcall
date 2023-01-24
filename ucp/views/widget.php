<div class="text-center"> 
    <br>
	<div class="mcen parent">
		<div><?php echo _('Enable')?></div>
		<input class="mcenable" type="checkbox" name="notification" data-type="notification" data-toggle="toggle" <?php echo (trim($notification) == "1") ? 'checked' : ''?> data-on="<?php echo _("On")?>" data-off="<?php echo _("Off")?>">
	</div>
	<div class="mcin parent">
		<div><?php echo _('Internal Calls')?></div>
		<input class="mcinternal" type="checkbox" name="internal" data-type="internal" data-toggle="toggle" <?php echo (trim($internal) == "1") ? 'checked' : ''?> data-on="<?php echo _("Enabled")?>" data-off="<?php echo _("Disabled")?>">
	</div>
	<div class="mcex parent">
		<div><?php echo _('External Calls')?></div>
		<input class="mcexternal" type="checkbox" name="external" data-type="external" data-toggle="toggle" <?php echo (trim($external) == "1") ? 'checked' : ''?> data-on="<?php echo _("Enabled")?>" data-off="<?php echo _("Disabled")?>">
	</div>
    <div class="mcrg parent">
		<div><?php echo _('Calls from Ring Groups')?></div>
		<input  class="mcringgroup" type="checkbox" name="ringgroup" data-type="ringgroup" data-toggle="toggle" <?php echo (trim($ringgroup) == "1") ? 'checked' : ''?> data-on="<?php echo _("Enabled")?>" data-off="<?php echo _("Disabled")?>">
	</div>
    <div class="mcqu parent">
		<div><?php echo _('Calls from Queues')?></div>
		<input class="mcqueue" type="checkbox" name="queue" data-type="queue" data-toggle="toggle" <?php echo (trim($queue) == "1") ? 'checked' : ''?> data-on="<?php echo _("Enabled")?>" data-off="<?php echo _("Disabled")?>">
	</div>
</div>