angular.module('App').controller('AddNewsController', function ($rootScope, $scope, $http, $mdToast, $mdDialog, $route, $timeout, request) {

	// define variable
	var self 		= $scope, root = $rootScope;
	var is_new 		= ( root.getCurNewsInfoId() == null );
	var original 	= null;
	var now 		= new Date().getTime();
	var dir 		= "/uploads/news/";

	root.search_enable 		= false;
	root.toolbar_menu 		= null;
	root.pagetitle 			= (is_new) ? 'خبر جدید' : 'ویرایش خبر';
	self.button_text 		= (is_new) ? 'ذخیره' : 'بروزرسانی';
	self.submit_loading 	= false;
	self.image 				= {};
	self.status_array 		= ["NORMAL", "FEATURED"];
	self.send_notif         = false;
	root.closeAndDisableSearch();
	
	/* check edit or add new*/
	if (is_new) {
		self.image.valid = false;
		original = {title: null, brief_content: null, full_content: null, image: null, draft: 1, status: null, created_at: now, last_update: now};
		self.news = angular.copy(original);
	} else {
		self.image.valid = true;
		request.getOneNewsInfo(root.getCurNewsInfoId()).then(function(resp){ 
			original = resp.data;
			self.news = angular.copy(original);
		});
	}
	/* check exceed featured news*/
	request.isFeaturedNewsExceed().then(function(resp){ self.featured_exceed = resp; });

	/* for selecting image file */
	self.onFileSelect = function (files) {
		self.image.valid = false;
        self.image.file = files[0];
		if (root.constrainFile(files[0])) {
			self.image.valid = true;
		}
		$mdToast.show($mdToast.simple().content("Selected file").position('bottom right'));
	};


	/* method for submit action */
	self.done_arr = [false, false, false];
	self.submit = function(n) {
		self.submit_loading = true;
		self.resp_submit    = null;		
		self.submit_done    = false;
		self.done_arr       = [false, false];
		
		if(is_new){ // create
			n.image = now + root.getExtension(self.image.file);
			request.insertOneNewsInfo(n).then(function(resp){
				self.resp_submit = resp;
				if(resp.status == 'success'){
					self.done_arr[0] = true;
					request.uploadFileToUrl(self.image.file, dir, n.image, "").then(function(){  // upload image image
						self.done_arr[1] = true; 
					});
				}else{
					self.done_arr[0] = true;
					self.submit_done = true;
				}
			});
		} else {  // update
			n.last_update = now;
			var oldname = angular.copy(n.image);
			n.image = (self.image.file != null) ? n.title.replace(/[^\w\s]/gi, '') + root.getExtension(self.image.file) : n.image;
			request.updateOneNewsInfo(n.id, n).then(function(resp){
				self.resp_submit = resp;
				if(resp.status == 'success'){
					self.done_arr[0] = true;
					if(self.image.file != null){ // upload image image
						request.uploadFileToUrl(self.image.file, dir, n.image, oldname).then(function(){ 
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
				    if(self.send_notif) {
				        self.sendNotification(is_new ? self.resp_submit.data : self.resp_submit.data.news_info);
				    }
				    root.showConfirmDialogSimple('', self.resp_submit.msg, function(){
				        window.location.href = '#news';
				    });
				}else{
				    root.showInfoDialogSimple('اطلاعات ذخیره نشد!', self.resp_submit.msg);
				}
				self.submit_loading = false;
			}, 1000);
		}
	});

	/* checker when all data ready to submit */
	self.isReadySubmit = function () {
		self.is_clean = angular.equals(original, self.news);
		if (is_new) {
			return (!self.is_clean && self.image.valid);
		} else {
			if (self.image.file != null) return (self.image.valid);
			return (!self.is_clean);
		}
	};


    /* for notification */
    self.sendNotification = function(obj){
        var title  = obj.title;
        var content  = obj.brief_content;
        var body = root.getNotificationBody('NEWS_INFO', obj, title, content, null);
        root.requestPostNotification(body, function(resp){});
    }

	self.cancel = function () { window.location.href = '#news'; };
	self.isNewEntry = function () { return is_new; };
	self.draftChanged = function (draft) { if(draft==1) self.send_notif=false; };

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
