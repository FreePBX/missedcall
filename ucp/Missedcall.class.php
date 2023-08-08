<?php
/**
 * This is the User Control Panel Object.
 *
 * Copyright (C) 2016 Sangoma Communications
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package  FreePBX UCP BMO
 * @author  	Franck Danard <fdanard@sangoma.com>
 * @license  Commercial
 */

namespace UCP\Modules;

use \UCP\Modules as Modules;

class Missedcall extends Modules {

	protected $module = 'Missedcall';

	public function __construct($Modules) {
		$this->mc      = $this->UCP->FreePBX->Missedcall;
		$this->userman = $this->UCP->FreePBX->Userman;
		//User information. Returned as an array. See:
		$this->user = $this->UCP->User->getUser();
		//Access any UCP Function.
		$ucp = $this->UCP;
		//Access any UCP module
		$modules = $this->Modules = $Modules;
		//Setting retrieved from the UCP Interface in User Manager in Admin
		$this->enabled = $this->UCP->getCombinedSettingByID($this->user['id'] ?? '', $this->module, 'enabled');
	}

	/**
	 * Get Simple Widget List
	 * @method getSimpleWidgetList
	 * @link https://wiki.freepbx.org/pages/viewpage.action?pageId=71271742#DevelopingforUCP14+-getSimpleWidgetList
	 * @return array               Array of information
	 */
	public function getSimpleWidgetList() {
		//Category for the widgets
		$user      = $this->user;
		$ext       = !empty($user["default_extension"]) ? $user["default_extension"] : Null;
		$mc_params = $this->mc->get($user['id'] ?? '');

		if (!$this->enabled) {
			dbug(sprintf(_("Missedcall -- User account '%s' Not allowed to use this UCP widget."), $user["username"] ?? ''));
			return false;
		}

		if (empty($mc_params["email"])) {
			// The code below is useful. 
			dbug(sprintf(_("Missedcall -- User account '%s' doesn't have a valid email address!"), $user["username"]));
			return false;
		}

		$widget = [
			"rawname" => "missedcall",
			//Module Rawname
			"display" => _("Missed Call"),
			//The Widget Main Title
			"icon"    => "fa fa-eye-slash",
			//The Widget Icon from http://fontawesome.io/icons/
			"list"    => [],
		];

		//Individual Widgets
		$widget['list']["missedcall"] = [
			"display"     => _("Missed call"),
			//Widget Subtitle
			"description" => _("Receive an email for any missed call."),
			//Widget description
			"hasSettings" => false,
			//Set to true if this widget has settings. This will make the cog (gear) icon display on the widget display
			"icon"        => "fa fa-envelope-square",
			//If set the widget in on the side bar will use this icon instead of the category icon,
			"dynamic"     => false,
			//If set to true then this widget can be added multiple times, if false then this widget can only be added once per dashboard!
			"defaultsize" => [ "height" => 9, "width" => 2 ],
		];
		return $widget;
	}

	/**
	 * Get Widget List
	 * @method getWidgetList
	 * @link https://wiki.freepbx.org/pages/viewpage.action?pageId=71271742#DevelopingforUCP14+-getWidgetList
	 * @return array               Array of information
	 */
	public function getWidgetList() {
		return $this->getSimpleWidgetList();
	}

	/**
	 * Get Simple Widget Display
	 * @method getWidgetDisplay
	 * @link https://wiki.freepbx.org/pages/viewpage.action?pageId=71271742#DevelopingforUCP14+-getSimpleWidgetDisplay
	 * @param  string           $id The widget id. This is the key of the 'list' array in getSimpleWidgetList
	 * @param  string           $uuid The generated UUID of the widget on this dashboard
	 * @return array               Array of information
	 */
	public function getSimpleWidgetDisplay($id, $uuid) {
		$widget = [];
		switch ($id) {
			case "missedcall":
				$user = $this->user;
				$ext = !empty($user["default_extension"]) ? $user["default_extension"] : Null;
				$mc_params = $this->mc->get($user['id']);

				$displayvars = [ "notification" => $mc_params["notification"], "internal" => $mc_params["internal"], "external" => $mc_params["external"], "ringgroup" => $mc_params["ringgroup"], "queue" => $mc_params["queue"] ];

				$widget = [ 'title' => _("Missed Call"), 'html' => $this->load_view(__DIR__ . '/views/widget.php', $displayvars) ];
				break;
		}
		return $widget;
	}

	/**
	 * Get Widget Display
	 * @method getWidgetDisplay
	 * @link https://wiki.freepbx.org/pages/viewpage.action?pageId=71271742#DevelopingforUCP14+-getSimpleWidgetDisplay
	 * @param  string           $id The widget id. This is the key of the 'list' array in getWidgetList
	 * @param  string           $uuid The UUID of the widget
	 * @return array               Array of information
	 */
	public function getWidgetDisplay($id, $uuid) {
		return $this->getSimpleWidgetDisplay($id, $uuid);
	}

	/**
	 * Poll for information
	 * @method poll
	 * @link https://wiki.freepbx.org/pages/viewpage.action?pageId=71271742#DevelopingforUCP14+-poll(PHP)
	 * @param $data               Data from Javascript prepoll function (if any). See: https://wiki.freepbx.org/pages/viewpage.action?pageId=71271742#DevelopingforUCP14+-prepoll
	 * @return mixed              Data you'd like to send back to the javascript for this module. See: https://wiki.freepbx.org/pages/viewpage.action?pageId=71271742#DevelopingforUCP14+-poll(Javascript)
	 */
	public function poll($data) {
		$user      = $this->user;
		$mc_params = [];
		$ext       = !empty($user["default_extension"]) ? $user["default_extension"] : Null;
		if (!empty($ext)) {
			$mc_params = $this->mc->get($user['id']);
		}
		return [ "status" => true, "mc" => $mc_params ];
	}

	/**
	 * Ajax Request
	 * @method ajaxRequest
	 * @link https://wiki.freepbx.org/display/FOP/BMO+Ajax+Calls#BMOAjaxCalls-ajaxRequest
	 * @param  string      $command  The command name
	 * @param  array      $settings Returned array settings
	 * @return boolean                True if allowed or false if not allowed
	 */
	public function ajaxRequest($command, $settings) {
		return match ($command) {
			'mcsave' => true,
			default => false,
		};
	}

	/**
	 * Ajax Handler
	 * @method ajaxHandler
	 * @link https://wiki.freepbx.org/display/FOP/BMO+Ajax+Calls#BMOAjaxCalls-ajaxHandler
	 * @return mixed      Data to return to Javascript
	 */
	public function ajaxHandler() {
		switch ($_REQUEST['command']) {
			case 'mcsave':
				$data = $_REQUEST;
				$user = $this->user;
				$ext = !empty($user["default_extension"]) ? $user["default_extension"] : Null;
				if (!empty($ext)) {
					foreach ($data as $key => $value) {
						$value = htmlentities((string) $value);
						switch ($key) {
							case "queue":
								$umkey = 'mcq';
								$type = $key;
								$val = $value;
								break;
							case "ringgroup":
								$umkey = 'mcrg';
								$type = $key;
								$val = $value;
								break;

							case "internal":
								$umkey = 'mci';
								$type = $key;
								$val = $value;
								break;
							case "external":
								$umkey = 'mcx';
								$type = $key;
								$val = $value;
								break;
							case "notification":
								$umkey = 'mcenabled';
								$type = $key;
								$val = $value;
								break;
							default:
								break;
						}
					}

					$this->mc->updateOne($user['id'], $type, $val);
					if ($val == 1) {
						$umval = true;
					}
					else {
						$umval = false;
					}
					$this->userman->setModuleSettingByID($user['id'], 'missedcall', $umkey, $val);
					return [ "status" => true, "alert" => "success", "message" => _('Saved') ];
				}
				return [ "status" => false, "alert" => "Error", "message" => sprintf(_("Bad extension: '%s'!"), $ext) ];
				break;
			default:
				return false;
				break;
		}
	}
}