# crudApp
Basic CRUD web application in AngularJS for maintaining products/items with a RESTful service.

## Getting Started
Angular's `$resource` expects a classic RESTful backend. This means you should have REST endpoints in the following
format:

| URL                                   | HTTP Verb | POST Body   | Result                 |
| ------------------------------------- | --------- | ----------- | ---------------------- |
| http://yourdomain.com/api/entries     | GET       | empty       | Returns all entries    |
| http://yourdomain.com/api/entries     | POST      | JSON string | New entry created      |
| http://yourdomain.com/api/entries/:id | GET       | empty       | Returns single entry   |
| http://yourdomain.com/api/entries/:id | PUT       | JSON string | Updates existing entry |
| http://yourdomain.com/api/entries/:id | DELETE    | empty       | Deletes existing entry |

You can create the endpoints using the server side language of your choice. Using Node + Express + MongoDB to design
the RESTful API is popular but also PHP + MySQL is popular even if older. Once you have the URLs ready, 
you can make use of
`$resource` for interacting with these URLs. So, let’s see how exactly `$resource` works.

## How Does $resource Work?
To use `$resource` inside your controller/service you need to declare a dependency on `$resource`. The next step is
calling the `$resource()` function with your REST endpoint, as shown in the following example. This function call
returns a `$resource` class representation which can be used to interact with the REST backend.

```javascript
angular.module('myApp.services').factory('Entry', function ($resource) {
  return $resource('/api/entries/:id'); // Note the full endpoint address
});
```

The result of the function call is a resource class object which has the following five methods by default:

1. get()
2. query()
3. save()
4. remove()
5. delete()

Now, let’s see how we can use the `get()`, `query()` and `save()` methods in a controller:

```javascript
angular.module('myApp.controllers', []);

angular.module('myApp.controllers').controller('ResourceController', function ($scope, Entry) {
  var entry = Entry.get({ id: $scope.id }, function () {
    console.log(entry);
  }); // get() returns a single entry

  var entries = Entry.query(function () {
    console.log(entries);
  }); //query() returns all the entries

  $scope.entry = new Entry(); //You can instantiate resource class

  $scope.entry.data = 'some data';

  Entry.save($scope.entry, function () {
    //data saved. do something here.
  }); //saves an entry. Assuming $scope.entry is the Entry object
});
```

The `get()` function in the above snippet issues a GET request to `/api/entries/:id`. The parameter `:id` in the URL is
replaced with `$scope.id`. You should also note that the function `get()` returns an empty object which is populated
automatically when the actual data comes from server. The second argument to `get()` is a callback which is executed
when the data arrives from server. This is a useful trick because you can set the empty object returned by `get()` to
the `$scope` and refer to it in the view. When the actual data arrives and the object is populated, the data binding
kicks in and your view is also updated.

The function `query()` issues a GET request to `/api/entries` (notice there is no `:id`) and returns an empty array.
This array is populated when the data arrives from server. Again you can set this array as a `$scope` model and refer
to it in the view using `ng-repeat`. You can also pass a callback to `query()` which is called once the data comes from
server.

The `save()` function issues a POST request to `/api/entries` with the first argument as the post body. The second
argument is a callback which is called when the data is saved. You might recall that the return value of the
`$resource()` function is a resource class. So, in our case we can call new Entry() to instantiate an actual object
out of this class, set various properties on it and finally save the object to backend.

Ideally, you will only use `get()` and `query()` on the resource class (Entry in our case). All the non GET methods like
 `save()` and `delete()` are also available in the instance obtained by calling `new Entry()` (call this a `$resource`
 instance). But the difference is that these methods are prefixed with a `$`. So, the methods available in the
 `$resource` instance (as opposed to `$resource` class) are:

1. $save()
2. $delete()
3. $remove()

For instance, the method `$save()` is used as following:

```javascript
$scope.entry = new Entry(); //this object now has a $save() method
$scope.entry.$save(function () {
  //data saved. $scope.entry is sent as the post body.
});
```

We have explored the create, read and delete parts of CRUD. The only thing left is update. To support an update
operation we need to modify our custom factory Entity as shown below.

```javascript
angular.module('myApp.services').factory('Entry', function ($resource) {
  return $resource('/api/entries/:id', { id: '@_id' }, {
    update: {
      method: 'PUT' // this method issues a PUT request
    }
  });
});
```

The second argument to `$resource()` is a hash indicating what should be the value of the parameter `:id` in the URL.
Setting it to `@_id` means whenever we will call methods like `$update()` and `$delete()` on the resource instance, the
value of `:id` will be set to the `_id` property of the instance. This is useful for PUT and DELETE requests. Also note
the third argument. This is a hash that allows us to add any custom methods to the resource class. If the method
issues a non-GET request it’s made available to the `$resource` instance with a `$` prefix. So, let’s see how to use
our `$update` method. Assuming we are in a controller:

```javascript
$scope.entry = Movie.get({ id: $scope.id }, function () {
  // $scope.entry is fetched from server and is an instance of Entry
  $scope.entry.data = 'something else';
  $scope.entry.$update(function () {
    //updated in the backend
  });
});
```

When the `$update()` function is called, it does the following:

AngularJS knows that `$update()` function will trigger a PUT request to the URL `/api/entries/:id`.
It reads the value of `$scope.entry._id`, assigns the value to `:id` and generates the URL.
Sends a PUT request to the URL with `$scope.entry` as the post body.
Similarly, if you want to delete an entry it can be done as following:

```javascript
$scope.entry = Movie.get({ id: $scope.id }, function () {
  // $scope.entry is fetched from server and is an instance of Entry
  $scope.entry.data = 'something else';
  $scope.entry.$delete(function () {
    //gone forever!
  });
});
```

It follows the same steps as above, except the request type is DELETE instead of PUT.

We have covered all the operations in a CRUD, but left with a small thing. The `$resource` function also has an
optional fourth parameter. This is a hash with custom settings. Currently, there is only one setting available which 
is `stripTrailingSlashes`. By default this is set to true, which means trailing slashes will be removed from the URL 
you pass to `$resource()`. If you want to turn this off you can do so like this:

```javascript
angular.module('myApp.services').factory('Entry', function ($resource) {
  return $resource('/api/entries/:id', { id: '@_id' }, {
    update: {
      method: 'PUT' // this method issues a PUT request
    }
  }, {
    stripTrailingSlashes: false
  });
});
```

By the way, I did not cover each and every thing about `$resource`. What we covered here are the basics that will help
you get started with CRUD apps easily. If you want to explore `$resource` in detail, 
you can go through the documentation.

## Building a Generic Item App
To reinforce the concepts of `$resource` let’s build an app for typical shop keepers. This is going to be a CRUD where 
users can add a new item to our database, update an existing item, and finally delete one. We will use `$resource` to 
interact with the REST API. You can check out a live demo of what we are going to build [here](http://weprovideit
.com/crudApp/).

Just note that the API I have built is CORS enabled, so it is possible for you to create an Angular app separately 
and use the URL [http://crudapp-weprovideit.com/](http://crudapp-weprovideit.com/) as the API
. You can develop the Angular app and play around with it without worrying about the backend.

## Our API
I have created a RESTful backend using PHP and MySQL. Take a look at the following screenshot to get to know the API.

| URI           | HTTP Verb | POST Body   | Result                |
| ------------- | --------- | ----------- | --------------------- |
| api/items     | GET       | empty       | Returns all items     |
| api/items     | POST      | JSON string | New item created      |
| api/items/:id | GET       | empty       | Returns single item   |
| api/items/:id | PUT       | JSON string | Updates existing item |
| api/items/:id | DELETE    | empty       | Deletes existing item |


### Directory Structure
Let’s start with the following directory structure for our AngularJS app:

```
crudApp
  /css
    app.css
    bootstrap.min.css
  /js
    app.js
    controllers.js
    services.js
  /lib
    angular.min.js
    angular-resource.min.js
    angular-ui-router.min.js
  /partials
    _form.html
    item-add.html
    item-edit.html
    item-view.html
    items.html
  index.html
```

Just note that we will be using Angular UI Router for routing.

## Creating Our Service to Interact with REST Endpoints
As discussed in previous sections we will create a custom service that will use `$resource` internally to interact 
with the REST API. The service is defined in `js/services.js`.

services.js:
```javascript
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
```

The name of our factory is Item. As we are using MySQL/MongoDB, each movie instance has a property called `_id`. The 
rest is simple and straightforward.

Now that we have our service ready let’s build views and controllers.

### index.html : Building the App Entry Page
The `index.html` is our app entry point. To start we need to include all the required scripts and stylesheets in this 
page. We will use Bootstrap to quickly create the layout. Here is the content of `index.html`.

```html
<!DOCTYPE html>
<html data-ng-app="crudApp">
<head lang="en">
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<base href="/"/>
	<title>The CRUD App</title>
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css"/>
	<link rel="stylesheet" type="text/css" href="css/app.css"/>
</head>
<body>
<nav class="navbar navbar-default" role="navigation">
	<div class="container-fluid">
		<div class="navbar-header">
			<a class="navbar-brand" ui-sref="items" title="The CRUD App">The CRUD App</a>
		</div>
		<div class="collapse navbar-collapse">
			<ul class="nav navbar-nav">
				<li class="active"><a ui-sref="items" title="Home">Home</a></li>
			</ul>
		</div>
	</div>
</nav>
<div class="container">
	<div class="row top-buffer">
		<div class="col-xs-8 col-xs-offset-2">
			<div ui-view></div>
		</div>
	</div>
</div>
<script type="text/javascript" src="lib/angular.min.js"></script>
<script type="text/javascript" src="js/app.js"></script>
<script type="text/javascript" src="js/controllers.js"></script>
<script type="text/javascript" src="js/services.js"></script>
<script type="text/javascript" src="js/directives.js"></script>
<script type="text/javascript" src="js/filters.js"></script>
<script type="text/javascript" src="lib/angular-ui-router.min.js"></script>
<script type="text/javascript" src="lib/angular-resource.min.js"></script>
</body>
</html>
```

The markup is pretty self explanatory. Just pay special attention to `<div ui-view></div>`. The ui-view directive
comes from UI Router module and acts as a container for our views.

### Creating Main Module and States
Our main module and states are defined in `js/app.js`:

app.js:
```javascript
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
```

So, our application has the following four states:

1. movies
2. viewMovie
3. newMovie
4. editMovie

Each state is composed of a `url`, `templateUrl` and `controller`. Also note that when our main module is loaded we 
make a transition to state movies showing all the movies in our system. Take a look at the following table to know 
which state corresponds to what URI.

| State    | URI              |
| -------- | ---------------- |
| items    | #/items          |
| newItem  | #/items/new      |
| editItem | #/items/:id/edit |
| viewItem | #/items/:id/view |


### Creating Templates
All of our templates are inside partials. Let’s see what each of them does!

_form.html:
`_form.html` contains a simple form which allows users to enter data. Note that this form will be included by 
item-add.html and item-edit.html because both of them accept inputs from users.

Here is the content of `_form.html`:

```html
<div class="form-group">
	<label for="title" class="col-sm-2 control-label">Title</label>

	<div class="col-sm-10">
		<input type="text" ng-model="item.title" class="form-control" id="title" placeholder="Title Here"/>
	</div>
</div>
<div class="form-group">
	<label for="price" class="col-sm-2 control-label">Price</label>

	<div class="col-sm-10">
		<input type="text" ng-model="item.price" class="form-control" id="price" plac
					 eholder="What is the cost?"/>
	</div>
</div>
<div class="form-group">
	<label for="description" class="col-sm-2 control-label">Description</label>

	<div class="col-sm-10">
		<input type="text" ng-model="item.description" class="form-control" id="description" plac
					 eholder="More information about the item."/>
	</div>
</div>

<div class="form-group">
	<label for="tags" class="col-sm-2 control-label">Item Tags</label>

	<div class="col-sm-10">
		<input type="text" ng-model="item.tags" class="form-control" id="tags" placeholder="Item tags here"/>
	</div>
</div>

<div class="form-group">
	<div class="col-sm-offset-2 col-sm-10">
		<input type="submit" class="btn btn-primary" value="Save"/>
	</div>
</div>
```

The template uses ng-model to bind various movie details to different properties of scope model movie.

item-add.html:
This template is used to accept user inputs and add a new movie to our system. Here is the content:

```html
<form class="form-horizontal" role="form" ng-submit="addItem()">
  <div ng-include="'partials/_form.html'"></div>
</form>
```

When the form is submitted the function `addItem()` of the scope is called which in turn sends a POST request to 
server to create a new item.

item-edit.html:
This template is used to accept user inputs and update an existing movie in our system.

```html
<form class="form-horizontal" role="form" ng-submit="updateItem()">
  <div ng-include="'partials/_form.html'"></div>
</form>
```

Once the form is submitted the scope function `updateItem()` is called which issues a PUT request to server to update
 an item.

item-view.html:
This template is used to show details about a single item. The content looks like following:

```html
<table class="table itemtable">
	<tbody>
		<tr>
			<td><h3>Details for {{item.title}}</h3></td>
			<td></td>
		</tr>
		<tr>
			<td>Title</td>
			<td>{{item.title}}</td>
		</tr>
		<tr>
			<td>Description</td>
			<td>{{item.description}}</td>
		</tr>
		<tr>
			<td>Price</td>
			<td>{{item.price}}</td>
		</tr>
		<tr>
			<td>Tags</td>
			<td>{{item.tags}}</td>
		</tr>
	</tbody>
</table>
<div>
	<a class="btn btn-primary" ui-sref="editItem({id:item._id})" title="Edit">Edit</a>
</div>
```

In the end there is an edit button. Once clicked it changes the state to editMovie with the movie id in the 
`$stateParams`.

items.html:
This template displays all the movies in the system.

```html
<a ui-sref="newItem" class="btn-primary btn-lg nodecoration" title="Add New Item">Add New Item</a>
<table class="table itemtable">
	<tbody>
		<tr>
			<td><h3>All Items</h3></td>
			<td></td>
		</tr>
		<tr ng-repeat="item in items">
			<td>{{item.title}}</td>
			<td>
				<a class="btn btn-primary" ui-sref="viewItem({id:item._id})" title="View">View</a>
				<a class="btn btn-danger" ng-click="deleteItem(item)" title="Delete">Delete</a>
			</td>
		</tr>
	</tbody>
</table>
```

It loops through all the movie objects obtained from the API and displays the details. There is also a button Add New
 Item which changes the state to `newItem`. As a result a new route loads and we can create a new item entry.

For each movie there are two buttons, View and Delete. View triggers a state transition so that the details for the 
movie are displayed. Delete button deletes the item permanently.

### Creating Controllers
Each state has a controller. So, in total we have four controllers for four states. All the controllers go into 
`js/controllers.js`. The controllers just utilize our custom service Movie and work the way we have discussed above. 
So, here is how our controllers look.

controllers.js:

```javascript
angular.module('crudApp.controllers', []).controller('ItemListController', function ($scope, $state, popupService, $window, Item) {

	$scope.items = Item.query();

	$scope.deleteItem = function (item) {
		if (popupService.showPopup('Really delete this?')) {
			item.$delete(function () {
				$window.location.href = '';
			});
		}
	}

}).controller('ItemViewController', function ($scope, $stateParams, Item) {

	$scope.item = Item.get({id: $stateParams.id});

}).controller('ItemCreateController', function ($scope, $state, $stateParams, Item) {

	$scope.item = new Item();

	$scope.addItem = function () {
		$scope.item.$save(function () {
			$state.go('items');
		});
	}

}).controller('ItemEditController', function ($scope, $state, $stateParams, Item) {

	$scope.updateItem = function () {
		$scope.item.$update(function () {
			$state.go('items');
		});
	};

	$scope.loadItem = function () {
		$scope.item = Item.get({id: $stateParams.id});
	};

	$scope.loadItem();
});
```

## Conclusion
Assuming the app is deployed under `localhost/crudApp`, you can access it at [http://localhost/crudApp/index.html]
(http://localhost/crudApp/index.html).

## License
The code is available under the [MIT license](LICENSE.txt).

## Usage
Download or clone the repository, change the domain in the URL contained within `src/js/services.js` to point to the 
RESTful API containing the data and then type the following commands:
```
$ npm install
$ gulp
```

If the build is successful, deploy the archive or dist directory.
