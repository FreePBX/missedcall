<?php 
	if($error){
		?>
		<div class="alert alert-warning" role="alert"><?php echo _("There is no default email address is set. Please, go to: Advanced Settings - User Management Module section and enter an email address.") ?></div>
		<?php
	} 
?>

<div class="table-responsive">
	<div id="toolbar">
		<button id="bulkyes" class="btn btn-success bulk" disabled>
			<i class="fa fa-check-circle"></i> <span><?php echo _('Enable') ?></span>
		</button>
		<button id="bulkno" class="btn btn-danger bulk" disabled>
			<i class="fa fa-times-circle"></i> <span><?php echo _('Disable') ?></span>
		</button>
	</div>
	<table
		id="table" 
		data-url="ajax.php?module=missedcall&amp;command=get_status" 
		data-toolbar="#toolbar" 
		data-show-refresh="true" 
		data-show-columns="true" 
		data-toggle="table" 
		data-pagination="true" 
		data-search="true" 
		class="table table-striped">
		<thead>
			<tr>
				<th data-field="state" data-checkbox="true"></th>
				<th data-width="150" data-formatter="extensionformatter" data-sortable="true" data-field="extension"><?php echo _("Extension")?></th>			
				<th data-field="email"><?php echo _("Email")?></th>
				<th data-width="50" data-align="center" data-field="internal"><?php echo _("Internal")?></th>
				<th data-width="50" data-align="center" data-field="external"><?php echo _("External")?></th>
				<th data-width="50" data-align="center" data-field="queue"><?php echo _("Queue")?></th>
				<th data-width="50" data-align="center" data-field="ringgroup"><?php echo _("Ring Group")?></th>
				<th data-width="150" data-align="center" data-formatter="enabledformatter" data-field="status"><?php echo _("Enabled")?></th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
</div>
