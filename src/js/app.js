/**
 * Created by Kevin Boutin on 08/23/15.
 */

angular.module('crudApp', ['ui.router', 'ngResource', 'crudApp.controllers', 'crudApp.services']);

angular.module('crudApp').config(function ($stateProvider, $httpProvider) {
	$stateProvider.state('items', {
		url: '/items',
		templateUrl: 'partials/items.html',
		controller: 'ItemListController'
	}).state('viewItem', {
		url: '/items/:id/view',
		templateUrl: 'partials/item-view.html',
		controller: 'ItemViewController'
	}).state('newItem', {
		url: '/items/new',
		templateUrl: 'partials/item-add.html',
		controller: 'ItemCreateController'
	}).state('editItem', {
		url: '/items/:id/edit',
		templateUrl: 'partials/item-edit.html',
		controller: 'ItemEditController'
	});
}).run(function ($state) {
	$state.go('items');
});