<?php
require_once("rest.inc.php");

/**
 * Created by Kevin Boutin on 08/23/15.
 *
 * This class expects the query string and the function name to match.
 * For example, If the query string value is request=deleteItem, the function
 * should be:
 * 		function deleteItem() {
 * 			// Your code goes here
 * 		}
 *
 * Use the following for sanitized input:
 * 		$object->_request['example']
 *
 * Use the following for responses:
 * 		$object->response(output_data, status_code);
 */
class API extends REST
{

public $data = "";

const DB_SERVER = "127.0.0.1";
const DB_USER = "itemadmin";
const DB_PASSWORD = "";
const DB = "prefix_shop";

private $db = NULL;
private $mysqli = NULL;

public function __construct()
{
	parent::__construct();        // Init parent constructor
	$this->dbConnect();          // Initiate Database connection
}

/*
 *  Connect to Database
*/
	private function dbConnect()
	{
		$this->mysqli = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
	}

	/*
	 * Dynamically call the method based on the query string
	 */
	public function processApi()
	{
		$func = strtolower(trim(str_replace("/", "", $_REQUEST['x'])));
		if (method_exists($this, $func))
			$this->$func();
		else
			$this->response('', 404); // If the method does not exist within this class, use "Page not found".
	}

	private function login()
	{
		if ($this->get_request_method() != "POST") {
			$this->response('', 406);
		}
		$email = $this->_request['email'];
		$password = $this->_request['pwd'];
		if (!empty($email) and !empty($password)) {
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$query = "SELECT id, email, role FROM users WHERE email = '$email' AND password = '" . hash("sha512", $password) . "' LIMIT 1";
				$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

				if ($r->num_rows > 0) {
					$result = $r->fetch_assoc();
					// If success, everything is good send header as "OK" and user details
					$this->response($this->json($result), 200);
				}
				$this->response('', 204);  // If no records "No Content" status
			}
		}

		$error = array('status' => "Failed", "msg" => "Invalid Email address or Password");
		$this->response($this->json($error), 400);
	}

	private function items()
	{
		if ($this->get_request_method() != "GET") {
			$this->response('', 406);
		}
		$query = "SELECT DISTINCT i.id, i.title, i.description, i.price, i.type, i.gender, i.vendor, i.site, i.tags,
i.modified FROM items i ORDER BY i.title";
		$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

		if ($r->num_rows > 0) {
			$result = array();
			while ($row = $r->fetch_assoc()) {
				$result[] = $row;
			}
			$this->response($this->json($result), 200); // send user details
		}
		$this->response('', 204);  // If no records "No Content" status
	}

	private function item()
	{
		if ($this->get_request_method() != "GET") {
			$this->response('', 406);
		}
		$id = (int)$this->_request['id'];
		if ($id > 0) {
			$query = "SELECT DISTINCT i.id, i.title, i.description, i.price, i.type, i.gender, i.vendor, i.site, i.tags, i.modified FROM items i WHERE i.id=$id";
			$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
			if ($r->num_rows > 0) {
				$result = $r->fetch_assoc();
				$this->response($this->json($result), 200); // send user details
			}
		}
		$this->response('', 204);  // If no records, use "No Content" status
	}

	private function insertItem()
	{
		if ($this->get_request_method() != "POST") {
			$this->response('', 406);
		}

		$item = json_decode(file_get_contents("php://input"), true);
		$column_names = array('title', 'description', 'price', 'type', 'size', 'vendor', 'site', 'gender', 'tags');
		$keys = array_keys($item);
		$columns = '';
		$values = '';

		// Check the item received. If key does not exist,insert blank into the array.
		foreach ($column_names as $desired_key) {
			if (!in_array($desired_key, $keys)) {
				$$desired_key = '';
			} else {
				$$desired_key = $item[$desired_key];
			}

			$columns = $columns . $desired_key . ',';
			$values = $values . "'" . $$desired_key . "',";
		}

		$query = "INSERT INTO items(" . trim($columns, ',') . ") VALUES(" . trim($values, ',') . ")";
		if (!empty($item)) {
			$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
			$success = array('status' => "Success", "msg" => "Item created successfully.", "data" => $item);
			$this->response($this->json($success), 201);
		} else
			$this->response('', 204);  //"No Content" status
	}

	private function updateItem()
	{
		if ($this->get_request_method() != "POST") {
			$this->response('', 406);
		}

		$item = json_decode(file_get_contents("php://input"), true);
		$id = (int)$item['id'];
		$column_names = array('id', 'title', 'description', 'price', 'type', 'size', 'vendor', 'site', 'gender',
			'tags');
		$keys = array_keys($item['item']);
		$columns = '';
		$values = '';

		// Check the item received. If key does not exist,insert blank into the array.
		foreach ($column_names as $desired_key) {
			if (!in_array($desired_key, $keys)) {
				$$desired_key = '';
			} else {
				$$desired_key = $item['item'][$desired_key];
			}

			$columns = $columns . $desired_key . "='" . $$desired_key . "',";
		}

		$query = "UPDATE items SET " . trim($columns, ',') . " WHERE id=$id";
		if (!empty($item)) {
			$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
			$success = array('status' => "Success", "msg" => "Item " . $id . " updated successfully.", "data" => $item);
			$this->response($this->json($success), 200);
		} else
			$this->response('', 204);  // "No Content" status
	}

	private function deleteItem()
	{
		if ($this->get_request_method() != "DELETE") {
			$this->response('', 406);
		}
		$id = (int)$this->_request['id'];
		if ($id > 0) {
			$query = "DELETE FROM items WHERE id = $id";
			$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
			$success = array('status' => "Success", "msg" => "Successfully deleted one item.");
			$this->response($this->json($success), 200);
		} else
			$this->response('', 204);  // If no records, use "No Content" status
	}

	/*
	 *	Encode array into JSON
	*/
	private function json($data)
	{
		if (is_array($data)) {
			return json_encode($data);
		}
	}
}

$api = new API;
$api->processApi();
?>