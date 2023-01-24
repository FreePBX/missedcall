var MissedcallC = UCPMC.extend({
	/**
	 * This function is similar to PHP's __construct
	 * class variables are declared in this method using 'this.variable'
	 */
	init: function(){
		this.socket = null;
		this.stopPropagation = {};
	},
	/**
	 * Display Widget
	 * This method is executed when the side bar widget has finished loading.
	 * @method displayWidget
	 * @link https://wiki.freepbx.org/pages/viewpage.action?pageId=71271742#DevelopingforUCP14+-displayWidget
	 * @param  {string}      widget_id    The widget ID on the dashboard
	 * @param  {string}      dashboard_id The dashboard ID the widget has been placed on
	 */
	displayWidget: function(widget_id,dashboard_id) {
		var self = this;
		$(".grid-stack-item[data-id='"+widget_id+"'][data-rawname=missedcall] .widget-content input[name='notification']").change(function() {
			var extension = $(".grid-stack-item[data-id='"+widget_id+"'][data-rawname=missedcall]").data("widget_type_id"),
				sidebar = $(".widget-extra-menu[data-module='missedcall'][data-widget_type_id='"+extension+"']:visible input[name='notification']"),
				checked = $(this).is(':checked'),
				data = {};
			if(sidebar.length && sidebar.is(":checked") !== checked) {
				var state = checked ? "on" : "off";
				sidebar.bootstrapToggle(state);
			}
			data["notification"] = checked ? 1 : 0;
			self.saveSettings(data);
		});

		$(".grid-stack-item[data-id='"+widget_id+"'][data-rawname=missedcall] .widget-content input[name='internal']").change(function() {
			var extension = $(".grid-stack-item[data-id='"+widget_id+"'][data-rawname=missedcall]").data("widget_type_id"),
				sidebar = $(".widget-extra-menu[data-module='missedcall'][data-widget_type_id='"+extension+"']:visible input[name='internal']"),
				checked = $(this).is(':checked'),
				data = {};
			if(sidebar.length && sidebar.is(":checked") !== checked) {
				var state = checked ? "on" : "off";
				sidebar.bootstrapToggle(state);
			}
			data["internal"] = checked ? 1 : 0;
			self.saveSettings(data);
		});

		$(".grid-stack-item[data-id='"+widget_id+"'][data-rawname=missedcall] .widget-content input[name='external']").change(function() {
			var extension = $(".grid-stack-item[data-id='"+widget_id+"'][data-rawname=missedcall]").data("widget_type_id"),
				sidebar = $(".widget-extra-menu[data-module='missedcall'][data-widget_type_id='"+extension+"']:visible input[name='external']"),
				checked = $(this).is(':checked'),
				data = {};
			if(sidebar.length && sidebar.is(":checked") !== checked) {
				var state = checked ? "on" : "off";
				sidebar.bootstrapToggle(state);
			}
			data["external"] = checked ? 1 : 0;
			self.saveSettings(data);
		});

		$(".grid-stack-item[data-id='"+widget_id+"'][data-rawname=missedcall] .widget-content input[name='ringgroup']").change(function() {
			var extension = $(".grid-stack-item[data-id='"+widget_id+"'][data-rawname=missedcall]").data("widget_type_id"),
				sidebar = $(".widget-extra-menu[data-module='missedcall'][data-widget_type_id='"+extension+"']:visible input[name='ringgroup']"),
				checked = $(this).is(':checked'),
				data = {};
			if(sidebar.length && sidebar.is(":checked") !== checked) {
				var state = checked ? "on" : "off";
				sidebar.bootstrapToggle(state);
			}
			data["ringgroup"] = checked ? 1 : 0;
			self.saveSettings(data);
		});

		$(".grid-stack-item[data-id='"+widget_id+"'][data-rawname=missedcall] .widget-content input[name='queue']").change(function() {
			var extension = $(".grid-stack-item[data-id='"+widget_id+"'][data-rawname=missedcall]").data("widget_type_id"),
				sidebar = $(".widget-extra-menu[data-module='missedcall'][data-widget_type_id='"+extension+"']:visible input[name='queue']"),
				checked = $(this).is(':checked'),
				data = {};
			if(sidebar.length && sidebar.is(":checked") !== checked) {
				var state = checked ? "on" : "off";
				sidebar.bootstrapToggle(state);
			}
			data["queue"] = checked ? 1 : 0;
			self.saveSettings(data);
		});
	},
	/**
	 * Display Side Bar Widget
	 * This method is executed after the side bar widget has been clicked and the window has fully extended has finished loading.
	 * @method displaySimpleWidget
	 * @link https://wiki.freepbx.org/pages/viewpage.action?pageId=71271742#DevelopingforUCP14+-displaySimpleWidget
	 * @param  {string}            widget_id The widget id in the sidebar
	 */
	displaySimpleWidget: function(widget_id) {
		var self = this;
		$(".widget-extra-menu[data-id='"+widget_id+"'] input[name='enable']").change(function(e) {
			var extension = $(".widget-extra-menu[data-id='"+widget_id+"']").data("widget_type_id"),
				checked = $(this).is(':checked'),
				el = $(".grid-stack-item[data-rawname=missedcall][data-widget_type_id='"+extension+"']:visible input[name='enable']"),
				data = {};
			if(el.length) {
				if(el.is(":checked") !== checked) {
					var state = checked ? "on" : "off";
					el.bootstrapToggle(state);
				}
			} else {
				data["enable"] = checked ? 1 : 0;
				self.saveSettings(data);
			}
		});

		$(".widget-extra-menu[data-id='"+widget_id+"'] input[name='internal']").change(function(e) {
			var extension = $(".widget-extra-menu[data-id='"+widget_id+"']").data("widget_type_id"),
				checked = $(this).is(':checked'),
				el = $(".grid-stack-item[data-rawname=missedcall][data-widget_type_id='"+extension+"']:visible input[name='internal']"),
				data = {};
			if(el.length) {
				if(el.is(":checked") !== checked) {
					var state = checked ? "on" : "off";
					el.bootstrapToggle(state);
				}
			} else {
				data["internal"] = checked ? 1 : 0;
				self.saveSettings(data);
			}
		});

		$(".widget-extra-menu[data-id='"+widget_id+"'] input[name='external']").change(function(e) {
			var extension = $(".widget-extra-menu[data-id='"+widget_id+"']").data("widget_type_id"),
				checked = $(this).is(':checked'),
				el = $(".grid-stack-item[data-rawname=missedcall][data-widget_type_id='"+extension+"']:visible input[name='external']"),
				data = {};
			if(el.length) {
				if(el.is(":checked") !== checked) {
					var state = checked ? "on" : "off";
					el.bootstrapToggle(state);
				}
			} else {
				data["external"] = checked ? 1 : 0;
				self.saveSettings(data);
			}
		});

		$(".widget-extra-menu[data-id='"+widget_id+"'] input[name='ringgroup']").change(function(e) {
			var extension = $(".widget-extra-menu[data-id='"+widget_id+"']").data("widget_type_id"),
				checked = $(this).is(':checked'),
				el = $(".grid-stack-item[data-rawname=missedcall][data-widget_type_id='"+extension+"']:visible input[name='ringgroup']"),
				data = {};
			if(el.length) {
				if(el.is(":checked") !== checked) {
					var state = checked ? "on" : "off";
					el.bootstrapToggle(state);
				}
			} else {
				data["ringgroup"] = checked ? 1 : 0;
				self.saveSettings(data);
			}
		});

		$(".widget-extra-menu[data-id='"+widget_id+"'] input[name='queue']").change(function(e) {
			var extension = $(".widget-extra-menu[data-id='"+widget_id+"']").data("widget_type_id"),
				checked = $(this).is(':checked'),
				el = $(".grid-stack-item[data-rawname=missedcall][data-widget_type_id='"+extension+"']:visible input[name='queue']"),
				data = {};
			if(el.length) {
				if(el.is(":checked") !== checked) {
					var state = checked ? "on" : "off";
					el.bootstrapToggle(state);
				}
			} else {
				data["queue"] = checked ? 1 : 0;
				self.saveSettings(data);
			}
		});
	},
	/**
	 * Pre Poll (Before the poll)
	 * This method is used to populate data to send to the PHP poll function for this module
	 * @method prepoll
	 * @link https://wiki.freepbx.org/pages/viewpage.action?pageId=71271742#DevelopingforUCP14+-prepoll
	 * @return  {mixed}      Data to send back to the PHP poll function for this module
	 */
	prepoll: function() {
		var exts = [];
		$(".grid-stack-item[data-rawname=missedcall]").each(function() {
			exts.push($(this).data("widget_type_id"));
		});
		return exts;
	},
	/**
	 * Poll
	 * This method is used to process data returned from the PHP poll function for this module
	 * @method poll
	 * @link https://wiki.freepbx.org/pages/viewpage.action?pageId=71271742#DevelopingforUCP14+-poll(Javascript)
	 * @param  {mixed}      data    Data returned from the PHP poll function for this module
	 */
	poll: function(data){
		var self = this;
		$.each(data.mc, function(id, value){
			if(typeof self.stopPropagation["missedcall"] !== "undefined" && self.stopPropagation["missedcall"]) {
				return true;
			}

			checked = (value == "1") ? true : false;
			var widget = $(".grid-stack-item[data-rawname=missedcall][data-widget_type_id='missedcall']:visible input[name='"+id+"']"),
				sidebar = $(".widget-extra-menu[data-module='missedcall'][data-widget_type_id='missedcall']:visible input[name='"+id+"']"),
				sstate = value == "1" ? "on" : "off";
			if(widget.length && (widget.is(":checked") !== (value == "1") ? true : false)) {
				self.stopPropagation["missedcall"] = true;
				widget.bootstrapToggle(sstate);
				self.stopPropagation["missedcall"] = false;
			} else if(sidebar.length && (sidebar.is(":checked") !== (value == "1") ? true : false)) {
				self.stopPropagation["missedcall"] = true;
				sidebar.bootstrapToggle(sstate);
				self.stopPropagation["missedcall"] = false;
			}
		});
	},
	/**
	 * saveSettings
	 * @param {array} data 
	 * @param {array} callback 
	 */
	saveSettings: function(data) {
		var self = this;
		data.module = "missedcall";
		data.command = "mcsave";
		this.stopPropagation["missedcall"] = true;
		$.post( UCP.ajaxUrl, data).always(function() {
			self.stopPropagation["missedcall"] = false;
		});
	},
});
