<?php
require_once("rest.inc.php");

// Include and configure log4php
include("../lib/log4php/Logger.php");
Logger::configure("log4php-config.xml");

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
 * Use the following for sanitized input (note the preceding underscore):
 * 		$object->_request['example']
 *
 * Use the following for responses:
 * 		$object->response(output_data, status_code);
 */
class API extends REST
{
	public $data = "";

	const DB_SERVER = "127.0.0.1";
	const DB_USER = "weprovid_shop";
	const DB_PASSWORD = "";
	const DB = "weprovid_shop";

	private $db = NULL;
	private $mysqli = NULL;
	private $log;

	public function __construct()
	{
		parent::__construct();        // Init parent constructor
		$this->log = Logger::getLogger(__CLASS__);
		$this->dbConnect();           // Initiate Database connection
		$this->log->info("Connected to database " . self::DB . ".");
	}

	/*
	 * Dynamically call the method based on the query string
	 */
	public function processApi()
	{
		$id = "";
		$content_type = "application/x-www-form-urlencoded";
		foreach ($_REQUEST as $key => $value)
		{
			$value = addslashes($value);
			$value = strip_tags($value);
			$this->log->info($key . " - " . $value);
		}

		if(isset($_SERVER['CONTENT_TYPE'])) {
			$content_type = $_SERVER['CONTENT_TYPE'];
			$this->log->info($content_type);
		}

		if($content_type == "application/json") {
			$body = file_get_contents("php://input");
			$body_params = json_decode($body);
			if($body_params) {
				foreach($body_params as $param_name => $param_value) {
					$parameters[$param_name] = $param_value;
					if($param_name == "id") {
						$id = $param_value;
					}
				}
			}
		} else {
			$url_elements = explode("/", $_REQUEST['x']);
			if(isset($url_elements[1])) {
				$id = (int) trim($url_elements[1]);
			}
		}

		$this->log->info("Request method: " . $_SERVER['REQUEST_METHOD']);
		$req_method = $_SERVER['REQUEST_METHOD'];
		if($req_method == "GET") {
			if($_GET['id']) {
				$this->log->info("GET called with id: " . $_GET['id']);
				$this->item((int) $_GET['id']);
			} else if($id != "") {
				$this->log->info("GET called with id: " . $id);
				$this->item($id);
			} else {
				$this->log->info("GET called with no id.");
				$this->items();
			}
		} else if($req_method == "POST") {
			$this->insertItem();
		} else if($req_method == "DELETE") {
			$this->deleteItem();
		} else if($req_method == "PUT") {
			$this->updateItem();
		} else {
			$this->log->warn("Request method is not supported: " . $req_method);
			$this->response('', 404); // If the method does not exist within this class, use "Page not found".
		}
	}

	/*
	 *  Connect to Database
	 */
	private function dbConnect()
	{
		$this->mysqli = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
	}

	private function login()
	{
		if ($this->get_request_method() != "POST") {
			$this->log->error("Returning 406 - not using POST.");
			$this->response('', 406);
		}
		$email = $this->_request['email'];
		$password = $this->_request['pwd'];
		if (!empty($email) and !empty($password)) {
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$query = "SELECT id, email, role FROM users WHERE email = '$email' AND password = '" . hash("sha512", $password) . "' LIMIT 1";
				$this->log->info($query);
				$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

				if ($r->num_rows > 0) {
					$result = $r->fetch_assoc();
					$this->log->debug("Response " . $this->json($result));
					// If success, everything is good send header as "OK" and user details
					$this->response($this->json($result), 200);
				}
				$this->log->info("Returning 204 - no content.");
				$this->response('', 204);  // If no records "No Content" status
			}
		}

		$error = array('status' => "Failed", "msg" => "Invalid Email address or Password");
		$this->log->info("Email address or password was not valid.");
		$this->response($this->json($error), 400);
	}

	private function items()
	{
		if ($this->get_request_method() != "GET") {
			$this->log->info("Returning 406 - not using GET.");
			$this->response('', 406);
		}
		$query = "SELECT DISTINCT i.id, i.title, i.description, i.price, i.type, i.gender, i.vendor, i.site, i.tags,
i.modified FROM items i ORDER BY i.title";
		$this->log->debug($query);
		$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);

		if ($r->num_rows > 0) {
			$result = array();
			while ($row = $r->fetch_assoc()) {
				$result[] = $row;
			}
			$this->log->info("Returning 200 - successfully selected " . $r->num_rows . " item(s).");
			$this->response($this->json($result), 200); // send user details
		}
		$this->log->info("Returning 204 - no content.");
		$this->response('', 204);  // If no records "No Content" status
	}

	private function item($id)
	{
		if ($this->get_request_method() != "GET") {
			$this->log->error("Returning 406 - not using GET.");
			$this->response('', 406);
		}
		//$id = (int)$this->_request['id'];
		if ($id > 0) {
			$query = "SELECT DISTINCT i.id, i.title, i.description, i.price, i.type, i.gender, i.vendor, i.site, i.tags, i.modified FROM items i WHERE i.id=$id";
			$this->log->debug($query);
			$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
			if ($r->num_rows > 0) {
				$result = $r->fetch_assoc();
				$this->log->info("Returning 200 - successfully selected one item.");
				$this->response($this->json($result), 200); // send user details
			}
		}
		$this->log->info("Returning 204 - no content.");
		$this->response('', 204);  // If no records, use "No Content" status
	}

	private function insertItem()
	{
		if ($this->get_request_method() != "POST") {
			$this->log->error("Returning 406 - not using POST.");
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
		$this->log->debug($query);
		if (!empty($item)) {
			$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
			$success = array('status' => "Success", "msg" => "Item created successfully.", "data" => $item);
			$this->log->info("Returning 201 - successfully created one item.");
			$this->response($this->json($success), 201);
		} else {
			$this->log->info("Returning 204 - no content.");
			$this->response('', 204);  //"No Content" status
		}
	}

	private function updateItem()
	{
		if ($this->get_request_method() != "POST") {
			$this->log->error("Returning 406 - not using POST.");
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
		$this->log->debug($query);
		if (!empty($item)) {
			$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
			$success = array('status' => "Success", "msg" => "Item " . $id . " updated successfully.", "data" => $item);
			$this->log->info("Returning 200 - successfully updated one item.");
			$this->response($this->json($success), 200);
		} else {
			$this->log->info("Returning 204 - no content.");
			$this->response('', 204);  // "No Content" status
		}
	}

	private function deleteItem()
	{
		if ($this->get_request_method() != "DELETE") {
			$this->log->error("Returning 406 - not using DELETE.");
			$this->response('', 406);
		}
		$id = (int)$this->_request['id'];
		if ($id > 0) {
			$query = "DELETE FROM items WHERE id = $id";
			$this->log->debug($query);
			$r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
			$success = array('status' => "Success", "msg" => "Successfully deleted one item.");
			$this->log->info("Returning 200 - successfully deleted one item.");
			$this->response($this->json($success), 200);
		} else {
			$this->log->info("Returning 204 - no content.");
			$this->response('', 204);  // If no records, use "No Content" status
		}
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