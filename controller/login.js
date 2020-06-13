angular.module('App').controller('LoginController', function ($rootScope, $scope, $http, $mdToast, $route, $timeout, request) {
	var self = $scope;
	var root = $rootScope;

    if($rootScope.isCookieExist()){ window.location.href = '#dashboard'; }

	root.isLogin = true;
	root.toolbar_menu = null;

	$rootScope.pagetitle = 'ورود';
	self.submit_loading = false;

	self.doLogin = function () {
		self.submit_loading = true;
		request.login(self.userdata).then(function (result) {
		    var resp = result.data;
			$timeout(function () { // give delay for good UI
				self.submit_loading = false;
				if (resp == "") {
				    $mdToast.show($mdToast.simple().content('ورود ناموفق').position('bottom right'));
				    return;
				}
                if(resp.status == "success"){
                    // saving session
                    root.saveCookies(resp.user.id, resp.user.name, resp.user.email, resp.user.password);
                    $mdToast.show($mdToast.simple().content('خوش آمدید '+resp.user.name + ' عزیز').position('bottom right'));
                    window.location.href = '#dashboard';
                    window.location.reload();
                } else {
				    $mdToast.show($mdToast.simple().content('ورود ناموفق').position('bottom right'));
                }
			}, 1000);
			//console.log(JSON.stringify(result.data));
		});
	};

});
