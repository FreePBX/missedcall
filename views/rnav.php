<?php
$list = FreePBX::Missedcall()->getUsers();
foreach($list as $mc_ext){

	$mc_status = FreePBX::Missedcall()->getStatus($mc_ext);
	$rows .= '<tr>';
	$rows .= '<td><a href="/admin/config.php?display=missedcall&amp;view=form&amp;extdisplay='.urlencode($mc_ext).'"><i class="fa fa-edit"></i>&nbsp;'.$mc_ext.'</a></td>';
	$rows .= '<td>';
	$rows .= '<span class="radioset">';
	$rows .= '<input type="radio" name="mctoggle'.$mc_ext.'" id="mctoggle'.$mc_ext.'yes" data-for="'.$mc_ext.'" '.($mc_status == true?'CHECKED':'').'>';
	$rows .= '<label for="mctoggle'.$mc_ext.'yes">'._("Yes").'</label>';
	$rows .= '<input type="radio" name="mctoggle'.$mc_ext.'" id="mctoggle'.$mc_ext.'no" data-for="'.$mc_ext.'" '.($mc_status == true?'':'CHECKED' ).' value="CHECKED">';
	$rows .= '<label for="mctoggle'.$mc_ext.'no">'._("No").'</label>';
	$rows .= '</span>';
}
?>
<div id="toolbar-all">
	<a href="?display=missedcall" class="btn btn-default"><i class="fa fa-list"></i>&nbsp;<?php echo _("List Notifications")?></a>

</div>
<table data-toolbar="#toolbar-all" data-show-columns="true" data-toggle="table" data-pagination="false" data-search="true" class="table table-striped">
<thead>
	<tr>
		<th data-sortable="true"><?php echo _("Extension")?></th>
		<th><?php echo _("Enabled")?></th>
	</tr>
</thead>
<tbody>
	<?php echo $rows ?>
</tbody>
</table>
