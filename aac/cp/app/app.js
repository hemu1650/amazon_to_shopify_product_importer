/**
 * declare 'ng-laravel' module with dependencies
 */
'use strict';

angular.module("ng-laravel", [
    'ngSanitize', // it's require for some plugins
    'ngRoute', // it's require for some plugins
    'ngResource', // it's require for some plugins
    'ngCookies',// it used to save some data in cookie like langKey and etc
    'restangular', // AngularJS service to handle Rest API Restful Resources properly and easily
    'ui.router', // Powerful routing framework for AngularJS
    'oc.lazyLoad', // Lazy load modules & components on page load in AngularJS
    'ui.jq', // AngularJS module for jquery library loading
    'ngAA', // authentication and authorization for angular and ui-router based on JWT security
    'ngAnimate', // it's require for some plugins
    'pascalprecht.translate',// i18n in your Angular apps, made easy
    'anim-in-out', // An animation directive to use with ngAnimate 1.2+ and ui-router to animate effect in page load
    'ncy-angular-breadcrumb', // Generate a breadcrumb from ui-router's states
    'cfp.hotkeys', // Configuration-centric keyboard shortcuts for your Angular apps
    'angular-cache',//angular-cache is a very useful replacement for the Angular 1 $cacheFactory
    'tmh.dynamicLocale',//Module to be able to change the locale at an angularjs application
    'ui-notification',//Service providing simple notifications using Bootstrap 3 styles with css transitions for animating
    'dcbImgFallback'// Utility to work with loading images, has fallback-src to handle erros in image loading and loading-src 
]);

var app = angular.module('ng-laravel');



