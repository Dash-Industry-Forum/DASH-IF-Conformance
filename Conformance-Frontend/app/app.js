(function () {

    var app = angular.module('ConformanceSoftware', ['ngCookies','ngRoute', 'ngAnimate', 'ngMaterial', 'md-steppers', 'angular-intro','ngMaterialCollapsible']);

    app.config(function ($routeProvider) {
        $routeProvider
            .when('/', {
                controller: 'stepperController',
                templateUrl: 'app/views/start.html'
            })
            .when('/start', {
                controller: 'stepperController',
                templateUrl: 'app/views/stepper.html'
            })
            .when('/faq', {
                controller: 'stepperController',
                templateUrl: 'app/views/faq.html'
            })
            .otherwise({
                redirectTo: '/'
            });
    });

}());
