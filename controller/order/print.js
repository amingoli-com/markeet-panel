angular.module('App').factory("request", function ($http, $cookies) {
    var api_base = '../../services/';

    var obj = {};
    var token = $cookies.get(window.location.origin + '_session_password');
    var config = {headers: {'Token': token}};
    obj.getOneProductOrder = function (id) {
        return $http.get(api_base + 'getOneProductOrder?id=' + id, config);
    };
    obj.getAllProductOrderDetailByOrderId = function (order_id) {
        return $http.get(api_base + 'getAllProductOrderDetailByOrderId?order_id=' + order_id);
    };
    obj.getAllConfig = function () {
        return $http.get(api_base + 'getAllConfig', config);
    };

    return obj;
});
angular.module('App').controller('PrintOrderController', function ($scope, $rootScope, $location, request) {

    var self = $scope;
    var root = $rootScope;
    if ($location.search().id) {
        self.id = $location.search().id;
    } else {
        console.log("Id Not Found");
        return;
    }

    root.findValue = function (config, code) {
        for (var i = 0; i < config.length; ++i) {
            var obj = config[i];
            if (obj.code == code) return obj.value;
        }
    };

    request.getOneProductOrder(self.id).then(function (resp) {
        self.order = resp.data;
        self.order.total_fees = parseFloat(self.order.total_fees).toFixed(0);
        window.document.title = 'Markeet Order - ' + self.order.code;
        request.getAllProductOrderDetailByOrderId(self.id).then(function (resp) {
            self.order_details = resp.data;
            // calculate data
            self.calculateTotal();
        });
    });

    request.getAllConfig().then(function (resp) {
        self.config = resp.data;
        self.conf_currency = root.findValue(self.config, 'CURRENCY');
        self.conf_tax = root.findValue(self.config, 'TAX');
        self.conf_featured_news = root.findValue(self.config, 'FEATURED_NEWS');
    });

    self.getPriceTotal = function (pod) {
        return parseFloat(pod.price_item * pod.amount).toFixed(0);
    };

    self.calculateTotal = function () {
        var price_total = 0;
        var price_tax = 0;
        self.amount_total = 0;
        self.price_tax_formatted = 0;
        self.price_total_formatted = 0;
        self.price_after_tax = 0;
        for (var i = 0; i < self.order_details.length; i++) {
            self.amount_total += self.order_details[i].amount;
            price_total += self.order_details[i].price_item * self.order_details[i].amount;
        }
        price_tax = (self.order.tax / 100) * price_total;
        self.price_tax_formatted = parseFloat(price_tax).toFixed(0);
        self.price_total_formatted = parseFloat(price_total).toFixed(0);
        self.price_after_tax = parseFloat(price_total + price_tax).toFixed(0);
    };

    self.printAction = function () {
        window.print();
    };
});