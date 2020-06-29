angular.module('App', 
['ngMaterial', 'ngRoute', 'ngMessages', 'ngCookies', 'ngSanitize', 'cl.paging', 'textAngular', 'colorpicker.module']);

/* Theme Configuration
 */
angular.module('App').config(function ($mdThemingProvider) {
	import {JDate} from 'ngx-persian';
	// Use as Primary color
	var myPrimary = $mdThemingProvider.extendPalette('light-blue', {
			'600': '00abc2',
			'contrastDefaultColor': 'light'
		});

	// Use as Accent color
	var myAccent = $mdThemingProvider.extendPalette('blue', {
			'500': '0075c0'
		});

	// Register the new color palette
	$mdThemingProvider.definePalette('myPrimary', myPrimary);

	// Register the new color palette
	$mdThemingProvider.definePalette('myAccent', myAccent);

	$mdThemingProvider.theme('default')
	.primaryPalette('myPrimary')
	.accentPalette('myAccent');
});

/* Menu Route Configuration
 */
angular.module('App').config(['$routeProvider',
	function ($routeProvider) {
		$routeProvider.

		when('/dashboard', { templateUrl: 'view/dashboard/dashboard.html', controller: 'DashboardController' }).

		when('/order', { templateUrl: 'view/order/list.html', controller: 'OrderController' }).
		when('/create_order', { templateUrl: 'view/order/create.html', controller: 'AddOrderController' }).

		when('/product', { templateUrl: 'view/product/list.html', controller: 'ProductController' }).
		when('/create_product', { templateUrl: 'view/product/create.html', controller: 'AddProductController' }).
		
		when('/category', { templateUrl: 'view/category/list.html', controller: 'CategoryController' }).
		when('/create_category', { templateUrl: 'view/category/create.html', controller: 'AddCategoryController' }).
		
		when('/news', { templateUrl: 'view/news/list.html', controller: 'NewsController' }).
		when('/create_news', { templateUrl: 'view/news/create.html', controller: 'AddNewsController' }).
		
		when('/application', { templateUrl: 'view/application/list.html', controller: 'ApplicationController' }).
		when('/notification', { templateUrl: 'view/notification/list.html', controller: 'NotificationController' }).
		when('/setting', { templateUrl: 'view/setting/setting.html', controller: 'SettingController' }).
		when('/about', { templateUrl: 'view/about/about.html', controller: 'AboutController' }).
		when('/login', { templateUrl: 'view/login.html', controller: 'LoginController' }).
		
		otherwise({ redirectTo: '/login' });
	}
]);


angular.module('App').factory('focus', function($timeout, $window) {
    return function(id) {
		// timeout makes sure that is invoked after any other event has been triggered.
		// e.g. click events that need to run before the focus or inputs elements that are in a disabled state but are enabled when those events are triggered.
		$timeout(function() {
			var element = $window.document.getElementById(id);
			if(element)element.focus();
		});
	};
});

angular.module('App').run(function ($location, $rootScope, $cookies) {
	$rootScope.$on('$routeChangeSuccess', function (event, current, previous) {
		// $rootScope.title = current.$$route.title;
	});

    $rootScope.$on('$locationChangeStart', function (event, next, current) {
        $rootScope.isLogin	= true;
        if(!$rootScope.isCookieExist()){
            $location.path('/login');
        } else {
            $rootScope.isLogin	= false;
        }
    });
});

angular.module('App').filter('cut', function () {
	return function (value, wordwise, max, tail) {
		if (!value) return '';

		max = parseInt(max, 10);
		if (!max) return value;
		if (value.length <= max) return value;

		value = value.substr(0, max);
		if (wordwise) {
			var lastspace = value.lastIndexOf(' ');
			if (lastspace != -1) {
				//Also remove . and , so its gives a cleaner result.
				if (value.charAt(lastspace-1) == '.' || value.charAt(lastspace-1) == ',') {
					lastspace = lastspace - 1;
				}
				value = value.substr(0, lastspace);
			}
		}

		return value + (tail || ' …');
	};
});
