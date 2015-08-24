/**
 * Created by Kevin Boutin on 08/23/15.
 */

angular.module('crudApp.services', []).factory('Item', function ($resource) {
	return $resource('http://crudapp.weprovideit.com/api/items/:id', { id: '@_id' }, {
		'get': { method: 'GET', cache: true },
		'query': { method: 'GET', cache: true, isArray: true },
		'update': { method: 'PUT' }
	});
}).service('popupService', function ($window) {
	this.showPopup = function (message) {
		return $window.confirm(message);
	};
});
