/**
 * Created by Kevin Boutin on 08/23/15.
 */

angular.module('crudApp.services', []).factory('Item', function ($resource) {
	return $resource('http://crudapp.weprovideit.com/api/items/:id', { id: '@id' }, {
		'get': { method: 'GET' },
		'query': { method: 'GET', isArray: true },
		'update': { method: 'POST' },
		'remove': { method: 'DELETE' },
		'delete': { method: 'DELETE' }
	});
}).service('popupService', function ($window) {
	this.showPopup = function (message) {
		return $window.confirm(message);
	};
});
