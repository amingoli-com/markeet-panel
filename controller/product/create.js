angular.module('App').controller('AddProductController', function ($rootScope, $scope, $http, $mdToast, $mdDialog, $route, $timeout, request) {

	// define variable
	var self 		= $scope, root = $rootScope;
	var is_new 		= ( root.getCurProductId() == null );
	var original 	= null;
	var now 		= new Date().getTime();
	var dir 		= "/uploads/product/";

	root.search_enable 		= false;
	root.toolbar_menu 		= null;
	root.pagetitle 			= (is_new) ? 'اضافه کردن' : 'ویرایش';
	self.button_text 		= (is_new) ? 'ذخیره' : 'بروزرسانی';
	self.submit_loading 	= false;
	self.image 				= {};
	self.images 			= [{}, {}, {}, {}];
	self.images_obj 		= [null, null, null, null];
	self.category_selected 	= [];
	root.closeAndDisableSearch();
	
	self.status_array 		= ["READY STOCK", "OUT OF STOCK", "SUSPEND"];
	request.getAllCategory().then(function(resp){ self.categories_data = resp.data; });
	
	/* check edit or add new*/
	if (is_new) {
		self.category_valid = false;
		self.image.valid = false;
		original = {
			name: null, image: null, price: null, price_discount: 0, stock: null, draft: 1,
			description: "", status: null, created_at: now, last_update: now
		};
		self.product = angular.copy(original);
	} else {
		self.image.valid 			= true;
		self.category_valid  		= true;
		self.original_category 		= [];
		self.original_images 		= angular.copy(self.images);
		request.getOneProduct(root.getCurProductId()).then(function(resp){ 
			original = resp.data;
			self.product = angular.copy(original);
		});
		request.getAllCategoryByProductId(root.getCurProductId()).then(function(resp){
			for (var i = 0; i < resp.data.length; i++) {
				self.original_category.push(resp.data[i].id);
			}
			root.sortArrayOfInt(self.original_category);
			self.category_selected = angular.copy(self.original_category);
		});
		request.getAllProductImageByProductId(root.getCurProductId()).then(function(resp){
			for (var i = 0; i < 4; i++) {
				self.images_obj[i] = (resp.data[i]==null || resp.data[i]=="") ? null : resp.data[i];
			}
		});		
	}

	/* for selecting category */
	self.toggleCategory = function (i, list) {
		var idx = list.indexOf(i.id);
		if (idx > -1) {
			list.splice(idx, 1);
		} else {
			list.push(i.id);
		}
		root.sortArrayOfInt(list);
		self.category_valid = (self.category_selected.length > 0);
	};
	self.isCategorySelected = function (i, list) { return list.indexOf(i.id) > -1; };

	/* for selecting primary image file */
	self.onFileSelect = function (files) {
		self.image.valid = false;
		self.image.file = files[0];
		if (root.constrainFile(files[0])) {
			self.image.valid = true;
		}
		$mdToast.show($mdToast.simple().content("Selected file").position('bottom right'));
	};

	/* for selecting optional images file */
	self.onFileSelectImages = function (files, idx) {
		self.images[idx].valid = false;
        self.images[idx].file = files[0];
		if (root.constrainFile(files[0])) {
			self.images[idx].valid = true;
		}
		$mdToast.show($mdToast.simple().content("Selected file").position('bottom right'));
	};

	/* method for submit action */
	/* [0] product_category done, [1] primary image done, [2] optional images done */
	self.done_arr = [false, false, false];
	self.submit = function(p) {

		self.submit_loading = true;
		self.resp_submit    = null;		
		self.submit_done    = false;
		self.done_arr       = [false, false, false];
		
		if(is_new){ // create
			p.image = now + root.getExtension(self.image.file);
			self.images_obj = [null, null, null, null];
			request.insertOneProduct(p).then(function(resp){
				self.resp_submit = resp;
				if(resp.status == 'success'){
					self.prepareProductCategory(resp.data.id);
					request.insertAllProductCategory(self.product_category).then(function(){ self.done_arr[0] = true; }); // insert table relation
					request.uploadFileToUrl(self.image.file, dir, p.image, "").then(function(){ self.done_arr[1] = true; }); // upload primary image	
					self.uploadOptionalImages(resp.data.id, 0, 0); // upload optional image
				}else{
					self.done_arr[0] = true;
					self.submit_done = true;
				}
			});
		} else {  // update
			p.last_update = now;
			var oldname = angular.copy(p.image);
			p.image = (self.image.file != null) ? now + root.getExtension(self.image.file) : p.image;
			request.updateOneProduct(p.id, p).then(function(resp){
				self.resp_submit = resp;
				if(resp.status == 'success'){
					self.prepareProductCategory(resp.data.id);
					request.insertAllProductCategory(self.product_category).then(function(){ self.done_arr[0] = true; }); // insert table relation
					if(self.image.file != null){
						request.uploadFileToUrl(self.image.file, dir, p.image, oldname).then(function(){ self.done_arr[1] = true; }); // upload primary image
					} else { self.done_arr[1] = true; }
					self.uploadOptionalImages(resp.data.id, 0, 0); // upload optional image
				} else {
					self.done_arr[0] = true;
					self.submit_done = true;
				}
			});
		}

	};
  
	/* Submit watch onFinish Checker */
	self.$watchCollection('done_arr', function(new_val, old_val) {
		if(self.submit_done || (new_val[0] && new_val[1] && new_val[2])){
			loop_run = false;
			$timeout(function(){ // give delay for good UI
				if(self.resp_submit.status == 'success'){
				    if(self.send_notif) {
				        self.sendNotification(is_new ? self.resp_submit.data : self.resp_submit.data.product);
				    }
                    root.showConfirmDialogSimple('', self.resp_submit.msg, function(){
                        window.location.href = '#product';
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
		if (is_new) {
			self.is_clean = angular.equals(original, self.product);
			return (!self.is_clean && self.image.valid && self.category_valid && self.isImagesValid());
		} else {
			self.is_clean = ( angular.equals(original, self.product) && angular.equals(self.original_category, self.category_selected) && angular.equals(self.original_images, self.images));
			if (self.image.file != null) {
				return (self.category_valid && self.image.valid && self.isImagesValid());
			} else {
				return (!self.is_clean && self.category_valid && self.isImagesValid());
			}
		}
	};
	self.isImagesValid = function () {
		for (var i = 0; i < self.images.length; i++) {
			if (self.images[i].file != null && self.images[i].valid != null && !self.images[i].valid) return false;
		}
		return true;
	};

	/* normalize product_category object by adding product id */
	self.prepareProductCategory = function (id){
		self.product_category = [];
		for (var i = 0; i < self.category_selected.length; i++) {
			var item = {product_id: id, category_id: self.category_selected[i]};
			self.product_category.push(item);
		}
	};
	
	/* uploader for optional images, using recursive method*/
	self.uploadOptionalImages = function(p_id, n, idx){
		if(n < self.images.length){
			var nfile = null;
			if(self.images[n] && self.images[n].file) { nfile = self.images[n].file };
			if(nfile && self.images[n].valid){
				var name  	= n + "_" + new Date().getTime() + root.getExtension(nfile);
				var oldname = ( self.images_obj[n] != null && self.images_obj[n].name != null ) ? self.images_obj[n].name : "";
				request.uploadFileToUrl(nfile, dir, name, oldname).then(function(resp){
					if(resp.status == 'success'){
						self.images_obj[n] = { product_id:p_id, name:name };
						self.uploadOptionalImages(p_id, (n+1), idx+1);
					}else{ 
						self.uploadOptionalImages(p_id, (n+1), idx);
					}
				});
			} else { 
				self.uploadOptionalImages(p_id, (n + 1), idx);
			}
		} else {
			var _index = 0;
			for (var i = 0; i < 4; i++) {
				if(_index < self.images_obj.length && self.images_obj[_index] == null){
					self.images_obj.splice(_index, 1);
				} else { 
					_index++; 
				}
			}
			if(self.images_obj.length > 0){ 
				request.insertAllProductImage(self.images_obj).then(function(resp){ self.done_arr[2] = true; });
			} else {
				self.done_arr[2] = true;
			}
		}
	}

	/* for notification */
	self.sendNotification = function(obj){
	    var title  = (is_new) ? root.PRODUCT_NEW : root.PRODUCT_UPDATE;
	    var body = root.getNotificationBody('PRODUCT', obj, title, obj.name, null);
        root.requestPostNotification(body, function(resp){});
    }

	self.cancel = function () { window.location.href = '#product'; };
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

	/* delete optional image dialog */
	self.deleteImage = function (ev, img) {
		var confirm = $mdDialog.confirm().title('Delete Confirmation').content('Are you sure want to delete this image ?').targetEvent(ev).ok('OK').cancel('CANCEL');
		$mdDialog.show(confirm).then(function () {
			request.deleteProductImageByName(img.name).then(function (res) {
				if (res.status == 'success') {
					$mdToast.show($mdToast.simple().hideDelay(1000).content('Delete this image Success!').position('bottom right')).then(function () {
						window.location.reload();
					});
				} else {
					$mdToast.show($mdToast.simple().hideDelay(6000).action('CLOSE').content('Opps , Failed delete this image').position('bottom right')).then(function (response) {});
				}
			});
		}, function () {});
	};

});

function ViewImageDialogController($scope, $mdDialog, $mdToast, file_url) {
	$scope.file_url = file_url;
}
