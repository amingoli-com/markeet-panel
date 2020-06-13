angular.module('App').controller('ProductController', function ($rootScope, $scope, $http, $mdToast, $mdDialog, $route, request) {
	var self = $scope;
	var root = $rootScope;

	root.pagetitle = 'لیست محصولات';
	self.loading = true;
	self.category_id = -1;
	self.max_item = 20;
	self.max_item_array = [];

    self.sort_by = root.sort_by;
    self.sort_by_selected = 0;

	$scope.$watch("max_item", function(val, old) { if(val != old){ loadPages(); } });
    $scope.$watch("category_id", function(val, old) { if(val != old){ loadPages(); } });
    $scope.$watch("sort_by_selected", function(val, old) { if(val != old){ loadPages(); } });

	for(var i = 1; i<5; i++){
	    var _value = 20*i;
	    var _text = "نمایش "+_value;
	    self.max_item_array.push({value:_value, text:_text});
	}

	root.search_enable = true;
	root.toolbar_menu = { title: 'اضافه کردن' };

	// receiver barAction from rootScope
	self.$on('barAction', function (event, data) {
		root.setCurProductId("");
		window.location.href = '#create_product';
	});

	// receiver submitSearch from rootScope
	self.$on('submitSearch', function (event, data) {
		self.q = data;
		loadPages();
	});

	request.getAllCategory().then(function(resp){
		var temp_category = {id:-1, name:'همه دسته بندی ها'};
		self.categories_data = resp.data;
		self.categories_data.unshift(temp_category);
	});

	// load pages from database and display
    function loadPages() {
		$_q = self.q ? self.q : '';
		self.paging.limit = self.max_item;
		request.getAllProductCount($_q, self.category_id).then(function (resp) {
			self.paging.total = Math.ceil(resp.data / self.paging.limit);
			self.paging.modulo_item = resp.data % self.paging.limit;
		});
		$limit = self.paging.limit;
		$current = (self.paging.current * self.paging.limit) - self.paging.limit + 1;
		if (self.paging.current == self.paging.total && self.paging.modulo_item > 0) {
			self.limit = self.paging.modulo_item;
		}
		var sort = self.sort_by[self.sort_by_selected];
		request.getAllProductByPage($current, $limit, $_q, self.category_id, sort).then(function (resp) {
			self.product = resp.data;
			self.loading = false;
		});

	};

	// pagination property
	self.paging = {
		total: 0, // total whole item
		current: 1, // start page
		step: 3, // count number display
		limit: self.max_item, // max item per page
		modulo_item: 0,
		onPageChanged: loadPages
	};

	self.editProduct = function(ev, p) {
		root.setCurProductId(p.id);
		window.location.href = '#create_product';
	};

	self.detailsProduct = function(ev, p) {
		$mdDialog.show({
			controller          : DetailsProductControllerDialog,
			templateUrl         : 'view/product/details.html',
			parent              : angular.element(document.body),
			targetEvent         : ev,
			clickOutsideToClose : true,
			product             : p
		})
	};

	self.deleteProduct = function(ev, p) {
		var confirm = $mdDialog.confirm().title('حذف محصول');
			confirm.content('مطمئن هستید که میخواهید '+p.name+' را حذف کنید؟');
			confirm.targetEvent(ev).ok('حذف').cancel('لغو');

		var dir = "/uploads/product/";
		var images_obj = new Array();
		images_obj.push(p.image);
		request.getAllProductImageByProductId(p.id).then(function(resp){
			for (var i = 0; i < resp.data.length; i++) {
				images_obj.push(resp.data[i].name);
			}
		});

		$mdDialog.show(confirm).then(function() {
			request.deleteOneProduct(p.id).then(function(res){
				if(res.status == 'success'){
					request.deleteFiles(dir, images_obj).then(function(res){ });
                    root.showConfirmDialogSimple('', p.name+' <b>با موفقیت حذف شد</b>!', function(){
                        window.location.reload();
                    });
				}else{
                    root.showInfoDialogSimple('', 'متاسفیم! حذف '+p.name+' <b>با شکست مواجه شد</b>');
				}
			});
		});

	};

    /* dialog Publish confirmation*/
    self.publishDialog = function (ev, o) {
        $mdDialog.show({
            controller : PublishProductDialogCtl,
            parent: angular.element(document.body), targetEvent: ev, clickOutsideToClose: true, obj: o,
            template:
            '<md-dialog ng-cloak aria-label="publishData">' +
            '  <md-dialog-content>' +
            '   <h2 class="md-title">انتشار محصول</h2> ' +
            '   <p> مطمئن هستید که میخواهید <b>{{obj.name}}</b> را منتشر کنید؟ </p><br>' +
            '   <md-checkbox ng-model="send_notif">ارسال نوتیفیکیشن به کاربران</md-checkbox>' +
            '   <div layout="row"> <span flex></span>' +
            '       <md-button ng-if="!submit_loading" class="md-warn" ng-click="cancel()" >لغو</md-button>' +
            '       <md-button ng-click="publish()" class="md-raised md-primary">انتشار</md-button>' +
            '   </div>' +
            '  </md-dialog-content>' +
            '</md-dialog>'
        });
        function PublishProductDialogCtl($scope, $mdDialog, $mdToast, obj) {
        	$scope.obj = angular.copy(obj);
        	$scope.cancel = function() { $mdDialog.cancel(); };
        	$scope.publish = function() {
        	    $scope.obj.draft = 0;
                request.updateOneProduct($scope.obj.id, $scope.obj).then(function(resp){
                    if(resp.status == 'success'){
                        if($scope.send_notif) $scope.sendNotification(obj);
                        root.showConfirmDialogSimple('', 'محصول  '+obj.name+' <b>منتشر شد</b>!', function(){
                            window.location.reload();
                        });
                    }else{
                        var failed_txt = ' متاسفیم! انتشار ' + +obj.name + '<b>با شکست مواجه شد</b>';
                        if(resp.msg != null) failed_txt = resp.msg;
                        root.showInfoDialogSimple('', failed_txt);
                    }
                });
        	};

            /* for notification when publish*/
            $scope.sendNotification = function(obj){
                var content  = root.PRODUCT_NEW + obj.name;
                var body = root.getNotificationBody('PRODUCT', obj, content, null);
                root.requestPostNotification(body, function(resp){});
            }
        }
    };

});

function DetailsProductControllerDialog($scope, $mdDialog, request, $mdToast, $route, product) {
	var self        = $scope;
	self.product    = product;
	self.categories = [];
	self.images     = [];
	self.hide   = function() { $mdDialog.hide(); };
	self.cancel = function() { $mdDialog.cancel(); };

	request.getAllCategoryByProductId(self.product.id).then(function(resp){
		self.categories = resp.data;
	});
	request.getAllProductImageByProductId(self.product.id).then(function(resp){
		self.images = resp.data;
	});
}

