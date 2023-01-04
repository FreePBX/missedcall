<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//

function missedcall_addItem ($extension, $email, $enabled){
	$foo = \FreePBX::Missedcall()->addItem($extension, $email, $enabled);
	return ($foo);
}
