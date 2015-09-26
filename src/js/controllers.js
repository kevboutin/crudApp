/**
 * Created by Kevin Boutin on 08/23/15.
 */
angular.module('crudApp.controllers', []).controller('ItemListController', function ($scope, $state, popupService, $window, Item) {

	// Fetch all items. Issues a GET to /api/items
	$scope.items = Item.query();

	// Delete an item. Issues a DELETE to /api/items/:id
	$scope.deleteItem = function (item) {
		if (popupService.showPopup('Really delete this?')) {
			item.$delete(function () {
				$window.location.href = '';
			});
		}
	};

}).controller('ItemViewController', function ($scope, $stateParams, Item) {

	// Get a single item. Issues a GET to /api/items/:id
	$scope.item = Item.get({ id: $stateParams.id });

}).controller('ItemCreateController', function ($scope, $state, $stateParams, Item) {

	// Create new item instance. Properties will be set via ng-model on UI.
	$scope.item = new Item();

	$scope.addItem = function () {
		console.log($scope.item);
		// Create a new item. Issues a POST to /api/items
		$scope.item.$save(function () {
			// On success, go back to home i.e. items state.
			$state.go('items');
		});
	};

}).controller('ItemEditController', function ($scope, $state, $stateParams, Item) {

	// Update the edited item. Issues a POST to /api/items/:id
	$scope.updateItem = function () {
		$scope.item.$update(function () {
			// On success, go back to home i.e. items state.
			$state.go('items');
		});
	};

	// Issues a GET request to /api/items/:id to get an item to update
	$scope.loadItem = function () {
		$scope.item = Item.get({ id: $stateParams.id });
	};

	$scope.loadItem();
});
