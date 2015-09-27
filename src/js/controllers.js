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
				$scope.items = Item.query();
				$window.location.reload();
			});
		}
	};

}).controller('ItemViewController', function ($scope, $stateParams, Item) {

	// Get a single item. Issues a GET to /api/items/:id
	$scope.item = Item.get({ id: $stateParams.id });
	console.log($scope.item);

}).controller('ItemCreateController', function ($scope, $state, $stateParams, Item) {
	$scope.submitted = false;

	// Create new item instance. Properties will be set via ng-model on UI.
	$scope.item = new Item();

	$scope.addItem = function () {
		console.log($scope.item);
		if ($scope.itemForm.$valid) {
			// Create a new item. Issues a POST to /api/items
			$scope.item.$save(function () {
				// On success, go back to home i.e. items state.
				$state.go('items');
			});
		} else {
			$scope.itemForm.submitted = true;
		}
	};

}).controller('ItemEditController', function ($scope, $state, $stateParams, Item) {
	$scope.submitted = false;

	$scope.updateItem = function () {
		console.log($scope.item);
		if ($scope.itemForm.$valid) {
			// Update the edited item. Issues a POST to /api/items/:id
			$scope.item.$update(function () {
				// On success, go back to home i.e. items state.
				$state.go('items');
			});
		} else {
			$scope.itemForm.submitted = true;
		}
	};

	// Issues a GET request to /api/items/:id to get an item to update
	$scope.loadItem = function () {
		$scope.item = Item.get({ id: $stateParams.id });
	};

	$scope.loadItem();
	console.log($scope.item);
});
