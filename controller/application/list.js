angular.module('App').controller('ApplicationController', function ($rootScope, $scope, $http, $mdToast, $mdDialog, $cookies, request) {
	var self = $scope;
	var root = $rootScope;

	root.search_enable = true;
	root.toolbar_menu = { title: 'افزودن نسخه جدید' }
	root.pagetitle = 'اپلیکیشن';
	
	// receiver barAction from rootScope
	self.$on('barAction', function (event, data) {
		self.addVersion(event, null);
	});
	
	// receiver submitSearch from rootScope
	self.$on('submitSearch', function (event, data) {
		self.q = data;
		self.loadPages();
	});
	
	self.loadPages = function () {
		$_q = self.q ? self.q : '';
		request.getAllAppVersionCount($_q).then(function (resp) {
			self.paging.total = Math.ceil(resp.data / self.paging.limit);
			self.paging.modulo_item = resp.data % self.paging.limit;
		});
		$limit = self.paging.limit;
		$current = (self.paging.current * self.paging.limit) - self.paging.limit + 1;
		if (self.paging.current == self.paging.total && self.paging.modulo_item > 0) {
			self.limit = self.paging.modulo_item;
		}
		request.getAllAppVersionByPage($current, $limit, $_q).then(function (resp) {
			self.app = resp.data;
			self.loading = false;
			//console.log(JSON.stringify(resp.data));
		});
	};

	//pagination property
	self.paging = {
		total: 0, // total whole item
		current: 1, // start page
		step: 3, // count number display
		limit: 20, // max item per page
		modulo_item: 0,
		onPageChanged: self.loadPages,
	};

	self.deleteVersion = function(ev, a) {
		var confirm = $mdDialog.confirm().title('');
			confirm.content('نسخه '+a.version_code+' اپلیکیشن حذف شود؟');
			confirm.targetEvent(ev).ok('بله').cancel('خیر');

		$mdDialog.show(confirm).then(function() {
			request.deleteOneAppVersion(a.id).then(function(resp){
				if(resp.status == 'success'){
					root.showConfirmDialogSimple('', 'نسخه '+a.version_code+' <b>حذف شد</b> ', function(){
					    window.location.reload();
					});
				}else{
				    var failed_txt = '';
                    if(resp.msg != null) failed_txt += '<br>' + resp.msg;
				    root.showInfoDialogSimple('حذف نشد!', failed_txt);
				}
			});
		});
	};
	
	
	self.addVersion = function(ev, a) {
		$mdDialog.show({
			controller          : VersionControllerDialog,
			templateUrl         : 'view/application/create.html',
			parent              : angular.element(document.body),
			targetEvent         : ev,
			clickOutsideToClose : true,
			version            	: a
		})
	};
	
});


function VersionControllerDialog($rootScope, $scope, $mdDialog, request, $mdToast, $route, $timeout, version) {
	var root    = $rootScope;
	var self    = $scope;
	var is_new  = (version == null);
	var now     = new Date().getTime();
	var original ;
	self.app = (!is_new) ? angular.copy(version) : {version_code:null, version_name:null, active:1, created_at:now, last_update:now};
	self.title = (is_new) ? 'افزودن نسخه جدید' : 'ویرایش';
	self.buttonText = (is_new) ? 'ذخیره' : 'بروزرسانی';
	original = angular.copy(self.app);
	self.is_new  = is_new;
	
	self.isClean = function() {
		return angular.equals(original, self.app);
	}
	
	self.submit = function(a) {
		self.submit_loading = true;
		if(is_new){
		  	request.insertOneAppVersion(a).then(function(resp){
				self.afterSubmit(resp);
			});
		} else {
			a.last_update = now;
			request.updateOneAppVersion(a.id, a).then(function(resp){
				self.afterSubmit(resp);
			});
		}
	};
	
	self.afterSubmit = function(resp) {
		$timeout(function(){ // give delay for good UI
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
	
	self.hide = function() { $mdDialog.hide(); };
	self.cancel = function() { $mdDialog.cancel(); };
}
