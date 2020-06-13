angular.module('App').controller('SettingController', function ($rootScope, $scope, $http, $mdToast, $mdDialog, $route, $timeout, request) {

	var self = $scope;
	var root = $rootScope;

	root.closeAndDisableSearch();
	root.toolbar_menu = null;
	$rootScope.pagetitle = 'تنظیمات';
	
	/* Script controller for : Application Setting*/
	
	var original_conf = null;
	var shipping = null;
	var email_receiver = null;
	self.config = [];
	self.conf_currency = null;
	self.conf_tax = null;
	self.conf_featured_news = null;
	self.selected_currency;
	self.conf_shipping = [];
	self.conf_email_notif_on_order = true;
	self.conf_email_notif_on_order_process = true;
	self.conf_email_reply_to = null;
	self.conf_email_receiver = [];

	request.getAllConfig().then(function (resp) {
		self.config = resp.data;
		original_conf = angular.copy(self.config);
		self.conf_currency = root.findValue(self.config, 'CURRENCY');
		self.conf_tax = root.findValue(self.config, 'TAX');
		self.conf_featured_news = root.findValue(self.config, 'FEATURED_NEWS');
		self.selected_currency = angular.copy(self.conf_currency);

		shipping = root.findValue(self.config, 'SHIPPING');
		self.conf_shipping = JSON.parse(shipping);

		self.conf_email_notif_on_order = root.findValue(self.config, 'EMAIL_NOTIF_ON_ORDER') == 'TRUE';
		self.conf_email_notif_on_order_process = root.findValue(self.config, 'EMAIL_NOTIF_ON_ORDER_PROCESS') == 'TRUE';
        self.conf_email_reply_to = root.findValue(self.config, 'EMAIL_REPLY_TO');
        email_receiver = root.findValue(self.config, 'EMAIL_BCC_RECEIVER');
        self.conf_email_receiver = JSON.parse(email_receiver);

	});
	
	request.getAllCurrency().then(function (resp) {
		self.arr_currency = resp.data;
		//console.log(JSON.stringify(self.arr_currency));
	});
	
	self.setValue = function (code, value) {
		for (var i = 0; i < self.config.length; ++i) {
			var obj = self.config[i];
			if(obj.code == code) {
				self.config[i].value = value;
			}
		}
	};

    self.$watchCollection('conf_shipping', function(new_val, old_val) {
        if(new_val.length > 5) self.conf_shipping.splice(5, 1);
    });

	self.resetShipping = function() { self.conf_shipping = JSON.parse(shipping); };

	self.resetEmailReceiver = function() { self.conf_email_receiver = JSON.parse(email_receiver); };

	/* checker when all data ready to submit */
	self.isReadySubmitConf = function () {
		self.setValue('CURRENCY', self.selected_currency);
		self.setValue('TAX', self.conf_tax);
		self.setValue('FEATURED_NEWS', self.conf_featured_news);
		self.setValue('SHIPPING', JSON.stringify(self.conf_shipping));
		self.setValue('EMAIL_NOTIF_ON_ORDER', self.conf_email_notif_on_order ? 'TRUE' : 'FALSE');
		self.setValue('EMAIL_NOTIF_ON_ORDER_PROCESS', self.conf_email_notif_on_order_process ? 'TRUE' : 'FALSE');
		self.setValue('EMAIL_REPLY_TO', self.conf_email_reply_to);
		self.setValue('EMAIL_BCC_RECEIVER', JSON.stringify(self.conf_email_receiver));

		var is_clean = angular.equals(original_conf, self.config);
		return !is_clean;
	};
	
	self.submitConf = function() {	
		self.submit_loading_conf = true;
		//console.log(JSON.stringify(self.config));
		request.updateAllConfig(self.config).then(function(resp){ 
			if(resp.status == 'success'){
				root.showConfirmDialogSimple('', resp.msg, function(){
					window.location.reload();
				});
			}else{
				root.showInfoDialogSimple('', resp.msg);
			}
		});
	};
	
	
	/* Script controller for : User Panel Setting*/
	
	var cur_id = root.getSessionUid();
	self.submit_loading = false;
	self.re_passwordValid = true;
	var original;

	request.getOneUser(cur_id).then(function (data) {
		self.userdata = data.data;
		self.userdata.password = '*****';
		original = angular.copy(self.userdata);
		//console.log(JSON.stringify(self.userdata));
	});

	self.isClean = function () {
		return angular.equals(original, self.userdata);
	}

	self.isPasswordMatch = function () {
		if (self.re_password == null || self.re_password == '') {
			return true;
		} else {
			if (self.re_password == self.userdata.password) {
				return true;
			} else {
				return false;
			}
		}
	}

	self.submit = function (is_new) {
		self.submit_loading = true;
		if (!is_new) {
			//console.log(JSON.stringify(self.userdata));
			request.updateOneUser(cur_id, self.userdata).then(function (resp) {
				if (resp.status == 'success') {
					// saving session
					root.saveCookies(resp.data.user.id, resp.data.user.name, resp.data.user.email, resp.data.user.password);
				}
				self.afterSubmit(resp);
			});
		} else {
			if (self.userdata.password === '*****') {
				self.userdata.password = "";
				self.submit_loading = false;
				return;
			}
			self.re_passwordValid = true;
			if (self.re_password != self.userdata.password) {
				self.re_passwordValid = false;
				self.submit_loading = false;
				return;
			}
			self.userdata.id = null;
			request.insertOneUser(self.userdata).then(function (resp) {
				self.afterSubmit(resp);
			});
		}

	}

	self.afterSubmit = function (resp) {
		$timeout(function () { // give delay for good UI
			self.submit_loading = false;
			if(resp.status == 'success'){
				root.showConfirmDialogSimple('', resp.msg, function(){
					window.location.reload();
				});
			}else{
				root.showInfoDialogSimple('', resp.msg);
			}
		}, 1000);
	};
	
});
