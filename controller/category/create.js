angular.module('App').controller('AddCategoryController', function ($rootScope, $scope, $http, $mdToast, $mdDialog, $route, $timeout, request) {

	// define variable
	var self 		= $scope, root = $rootScope;
	var is_new 		= ( root.getCurCategoryId() == null );
	var original 	= null;
	var now 		= new Date().getTime();
	var dir 		= "/uploads/category/";

	root.search_enable 		= false;
	root.toolbar_menu 		= null;
	root.pagetitle 			= (is_new) ? 'اضافه کردن' : 'ویرایش';
	self.button_text 		= (is_new) ? 'ذخیره' : 'بروزرسانی';
	self.submit_loading 	= false;
	self.icon 				= {};
	root.closeAndDisableSearch();
	
	/* check edit or add new*/
	if (is_new) {
		self.icon.valid = false;
		original = { name: null, icon: null, draft: 1, brief: "", color: "#4db151", priority: null, created_at: now, last_update: now };
		self.category = angular.copy(original);
	} else {
		self.icon.valid = true;
		request.getOneCategory(root.getCurCategoryId()).then(function(resp){ 
			original = resp.data;
			self.category = angular.copy(original);
		});
	}

	/* for selecting icon file */
	self.onFileSelect = function (files) {
		self.icon.valid = false;
        self.icon.file = files[0];
		if (root.constrainFilePng(files[0])) {
			self.icon.valid = true;
		}
		$mdToast.show($mdToast.simple().content("Selected file").position('bottom right'));
	};


	/* method for submit action */
	self.done_arr = [false, false, false];
	self.submit = function(c) {
		
		self.submit_loading = true;
		self.resp_submit    = null;		
		self.submit_done    = false;
		self.done_arr       = [false, false];
		
		if(is_new){ // create
			c.icon = now + root.getExtension(self.icon.file);
			request.insertOneCategory(c).then(function(resp){
				self.resp_submit = resp;
				if(resp.status == 'success'){
					self.done_arr[0] = true;
					request.uploadFileToUrl(self.icon.file, dir, c.icon, "").then(function(){  // upload icon image
						self.done_arr[1] = true; 
					});
				}else{
					self.done_arr[0] = true;
					self.submit_done = true;
				}
			});
		} else {  // update
			c.last_update = now;
			var oldname = angular.copy(c.icon);
			c.icon = (self.icon.file != null) ? now + root.getExtension(self.icon.file) : c.icon;
			request.updateOneCategory(c.id, c).then(function(resp){
				self.resp_submit = resp;
				if(resp.status == 'success'){
					self.done_arr[0] = true;
					if(self.icon.file != null){ // upload icon image
						request.uploadFileToUrl(self.icon.file, dir, c.icon, oldname).then(function(){ 
							self.done_arr[1] = true; 
						}); 
					} else { 
						self.done_arr[1] = true; 
					}
				} else {
					self.done_arr[0] = true;
					self.submit_done = true;
				}
			});
		}

	};
  
	/* Submit watch onFinish Checker */
	self.$watchCollection('done_arr', function(new_val, old_val) {
		if(self.submit_done || (new_val[0] && new_val[1])){
			loop_run = false;
			$timeout(function(){ // give delay for good UI
				if(self.resp_submit.status == 'success'){
				    root.showConfirmDialogSimple('', self.resp_submit.msg, function(){
				        window.location.href = '#category';
				    });
				}else{
                    root.showInfoDialogSimple('', self.resp_submit.msg);
				}
				self.submit_loading = false;
			}, 1000);
		}
	});

	/* checker when all data ready to submit */
	self.isReadySubmit = function () {
		self.is_clean = angular.equals(original, self.category);
		if (is_new) {
			return (!self.is_clean && self.icon.valid);
		} else {
			if (self.icon.file != null) return (self.icon.valid);
			return (!self.is_clean);
		}
	};
	self.isImagesValid = function () {
		for (var i = 0; i < self.images.length; i++) {
			if (self.images[i].valid != null && !self.images[i].valid) return false;
		}
		return true;
	};


	/* for gcm notification */

	self.cancel = function () { window.location.href = '#category'; };
	self.isNewEntry = function () { return is_new; };

	/* dialog View Image*/
	self.viewImage = function (ev, f) {
		$mdDialog.show({
			controller : ViewImageDialogController,
			parent: angular.element(document.body), targetEvent: ev, clickOutsideToClose: true, file_url: f,
			template: '<md-dialog ng-cloak aria-label="viewImage">' +
			'  <md-dialog-content style="max-width:800px;max-height:810px;" >' +
			'   <img style="margin: auto; max-width: 100%; max-height= 100%;" ng-src="{{file_url}}">' +
			'  </md-dialog-content>' +
			'</md-dialog>'
			
		})
	};

});

function ViewImageDialogController($scope, $mdDialog, $mdToast, file_url) {
	$scope.file_url = file_url;
}
