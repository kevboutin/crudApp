/**
 * Created by Kevin Boutin on 08/23/15.
 */

angular.module('crudApp.services', []).factory('Item', function ($resource) {
	return $resource('http://crudapp-13434.onmodulus.net/api/items/:id', {id: '@_id'}, {
		update: {
			method: 'PUT'
		}
	});
}).service('popupService', function ($window) {
	this.showPopup = function (message) {
		return $window.confirm(message);
	}
});