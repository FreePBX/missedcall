//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//
//

$(document).ready(function(){
	$("#changecid").change(function(){
			state = (this.value == "fixed" || this.value == "extern") ? "" : "disabled";
		if (state == "disabled") {
			$("#fixedcid").attr("disabled",state);
		} else {
			$("#fixedcid").removeAttr("disabled");
		}
	});
});

$('#table').bootstrapTable({
	onCheck: function (row, element) {
		$(".bulk").prop("disabled", false);
	},
	onCheckAll: function (rows) {
		$(".bulk").prop("disabled", false);
	},
	onUncheckAll: function (rows) {
		$(".bulk").prop("disabled", true);
	},
	onUncheck: function (rows) {
		$(".bulk").prop("disabled", true);
	}
})

$("#bulkyes").click(function (){
	var chosen = $('#table').bootstrapTable("getSelections");
	let notEnabled = 0;
	Object.keys(chosen).forEach(key => {
		if (!chosen[key].status.userid) {
			notEnabled++;
		}
	});
	if (notEnabled > 0) {
		// Open confirmation modal
		fpbxConfirm(
			sprintf(_('Are you sure you wish to enable missed call notification for %s users?'), notEnabled),
			_("Yes"), _("No"),
			function () {
				saveSelected("enable");
			}
		);
	} else {
		fpbxToast(_('Missed call notification is already enabled'), _('Error'), 'error');
	}
	$(".bulk").prop("disabled", true);
})

$("#bulkno").click(function (){
	var chosen = $('#table').bootstrapTable("getSelections");
	let enabledUsers = 0;
	Object.keys(chosen).forEach(key => {
		if (chosen[key].status.enabled) {
			enabledUsers++;
		}
	});
	if (enabledUsers > 0) {
		// Open confirmation modal
		fpbxConfirm(
			sprintf(_('Are you sure you wish to disable missed call notification for %s users?'), enabledUsers),
			_("Yes"), _("No"),
			function () {
				saveSelected("disable");
			}
		);
	} else {
		fpbxToast(_('Missed call notification is already disabled'), _('Error'), 'error');
	}
	$(".bulk").prop("disabled", true);
})

function saveSelected(status) {
	selected = $("#table").bootstrapTable('getSelections');
	sel={};
	ext={};
	$.each(selected, function(index, value){
		sel[index]=value.userid;
	})

	ext["extensions"]= sel;
	$.ajax({
		url: sprintf("%s?module=missedcall&command=savebulk&status=%s", window.FreePBX.ajaxurl, status),
		dataType:"json",
		async: false,
		data: ext,
		success: function (json) {
			$("#table").bootstrapTable("refresh");
		},
		error: function(d) {
			d.suppresserrors = true;
		}
	});	
}

$("#back").click( function(){
	window.location.href = "?display=missedcall";
})

$("input[name=needsconf]").click(function() {
	if($("input[name=needsconf]:checked").val() == "CHECKED") {
		$(".fmfm_remotealert_id").prop("disabled",false);
	} else {
		$(".fmfm_remotealert_id").prop("disabled",true);
	}
})

//Agent Quick Select
$("[id^='qsagents']").on('change',function(){
	var taelm = $(this).data('for');
	var cval = $('#'+taelm).val();
	if(cval.length === 0){
		$('#'+taelm).val($(this).val());
		$(this).children('option[value="'+$(this).val()+'"]').remove();
	}else{
		$('#'+taelm).val(cval+"\n"+$(this).val());
		$(this).children('option[value="'+$(this).val()+'"]').remove();
	}
});

//FixedCID
$("#changecid").change(function(){
	if($(this).val() == 'fixed'){
		$("#fixedcid").attr('disabled',false);
	}else{
		$("#fixedcid").attr('disabled',true);
	}
});

//Below are functions moved here from page.findmefollow.php

function insertExten() {
	exten = document.getElementById('insexten').value;

	grpList=document.getElementById('grplist');
	if (grpList.value[ grpList.value.length - 1 ] == "\n") {
		grpList.value = grpList.value + exten;
	} else {
		grpList.value = grpList.value + '\n' + exten;
	}

	// reset element
	document.getElementById('insexten').value = '';
}

function mctoggle(ext){
	if($("#mctoggle"+ext+"yes").prop('checked')){
		mcstate = "enable";
	}else{
		mcstate = "disable";
	}
	// Open confirmation modal
	fpbxConfirm(
		sprintf(_('Are you sure you wish to %s missed call notification for this users?'), mcstate),
		_("Yes"), _("No"),
		function () {
			$.get(sprintf("%s?module=missedcall&command=toggleMC&extdisplay=%s&state=%s", window.FreePBX.ajaxurl, ext, mcstate))
				.done(function () {
					$("#table").bootstrapTable("refresh");
					fpbxToast('Notification for ' + ext + ' ' + mcstate + 'd');
				});
		}
	);
	$("#table").bootstrapTable("refresh");
}

function checkGRP(theForm) {
	var msgInvalidExtList = _('Please enter an extension list.');
	var msgInvalidTime = _('Invalid time specified');
	var msgInvalidGrpTimeRange = _('Time must be between 1 and 60 seconds');
	var msgInvalidRingStrategy = _('Only ringall, ringallv2, hunt and the respective -prim versions are supported when confirmation is checked');
	var msgInvalidCID =  _('Invalid CID Number. Must be in a format of digits only with an option of E164 format using a leading "+"');

	// set up the Destination stuff
	setDestinations(theForm, 1);

	// form validation
	defaultEmptyOK = false;
	if (isEmpty(theForm.grplist.value))
		return warnInvalid(theForm.grplist, msgInvalidExtList);

	if (!theForm.fixedcid.disabled) {
		fixedcid = $.trim(theForm.fixedcid.value);
		if (!fixedcid.match('^[+]{0,1}[0-9]+$')) {
			return warnInvalid(theForm.fixedcid, msgInvalidCID);
		}
	}

	if (!isInteger(theForm.grptime.value)) {
		return warnInvalid(theForm.grptime, msgInvalidTime);
	} else {
		var grptimeVal = theForm.grptime.value;
		if (grptimeVal < 1 || grptimeVal > 60)
			return warnInvalid(theForm.grptime, msgInvalidGrpTimeRange);
	}

	if (theForm.needsconf.checked && (theForm.strategy.value.substring(0,7) != "ringall" && theForm.strategy.value.substring(0,4) != "hunt")) {
		return warnInvalid(theForm.needsconf, msgInvalidRingStrategy);
	}

	defaultEmptyOK = true;

	if (!validateDestinations(theForm, 1, true))
		return false;

	return true;
}

function is_email(email){
	regex 		= /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	disabled 	= "";
	if(!regex.test(email)){
		disabled = "disabled";
	}
	return disabled;
}

function editformatter(v,r) {
	return '<a  id="action'+r.userid+'" class="button btn" href="/admin/config.php?display=userman&action=showuser&user='+r.userid+'&#usermanhookmissedcall"><i class="fa fa-edit"></i>&nbsp;'+r.extension+'</a>';
}

function enabledformatter(v,r) {
	email = is_email(r.email);
	rows = '<span class="radioset">';
	rows += sprintf('<input %s type="radio" name="mctoggle%s" id="mctoggle%syes" onclick="mctoggle(%s)" data-for="%s" %s>', disabled, r.status.ext, r.status.ext, r.userid, r.userid, (r.status.enabled == 1 ? 'CHECKED' : ''));
	rows += '<label for="mctoggle'+r.status.ext+'yes">'+_("Yes")+'</label>';
	rows += sprintf('<input %s type="radio" name="mctoggle%s" id="mctoggle%sno" onclick="mctoggle(%s)" data-for="%s" %s value="CHECKED">', disabled, r.status.ext, r.status.ext, r.userid, r.userid, (r.status.enabled == 1 ? '' : 'CHECKED'));
	rows += '<label for="mctoggle'+r.status.ext+'no">'+_("No")+'</label>';
	rows += '</span>';
	return rows;
}


$('#submitEmailSettings button[type=submit]').on('click', function (e) {
	e.preventDefault();

	let emailType = $('input[name="notificationEmailType"]:checked').val();

	var formData = new FormData();
	formData.append('emailType', emailType);
	formData.append('subject', $('input[name="notificationEmailSubject"]').val().trim());
	if (emailType == 'text') {
		formData.append('body', $('#notificationTextEmailBody').val());
	} else {
		formData.append('body', $('#notificationHtmlEmailBody').Editor('getText'));
	}

	$.ajax({
		type: "POST",
		enctype: 'multipart/form-data',
		url: sprintf("%s?module=missedcall&command=saveEmailSettings", window.FreePBX.ajaxurl),
		data: formData,
		cache: false,
		contentType: false,
		processData: false,
		success: function (data) {
			if (data.status) {
				fpbxToast(_(data.message));
				setTimeout(() => {
					window.location.reload();
				}, 1000);
			} else {
				fpbxToast(_(data.message), _('Error'), 'error');
			}
		},
		error: function (data) {
			fpbxToast(_('There was an error updating setting'), _('Error'), 'error');
		}
	});

});