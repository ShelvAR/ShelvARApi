<?php
	include_once "../../database.php";
	include_once "../../header_include.php";
	include_once "../api_ref_call.php";
	
	$err = array();
	
	if(!$_POST['user_id']) {
		$err[] = "No user_id supplied";
	}
	if($_POST['user_id'] != $oauth_user['user_id']) {
		$err[] = "Invalid access to user account";
	}
	if(!count($err)) {
		$query = "UPDATE users SET ";
		$params = array();
		$user_id = $_POST['user_id'];
		if(isset($_POST['name'])) {
			$query += "name = ?,";
			$params[] = $_POST['name'];
		}
		if(isset($_POST['email'])) {
			$query += "email = ?,";
			$params[] = $_POST['email'];
		}
		if(isset($_POST['password'])) {
			$db = new database();
			$db->query = "SELECT encrip_salt FROM users WHERE user_id = ?";
			$db->params = array($user_id);
			$db->type = 's';
			$result = $db->fetch();
			$salt = $result[0]['salt'];
			
			// TODO error handling
			
			$query += "password = ?,";
			$password = $_POST['password'];
			$password = hash('sha256', $password . $salt);
			$params[] = $password;
		}
		$query = substr($query,0,-1); // removing trailing comma
		$query += "WHERE user_id = ?";
		$params[] = $user_id;
		
		$db = new database();
		$db->query = "UPDATE users SET name = ?, email = ?, password = ? WHERE user_id = ?";
		$db->params = $params;
		$db->type = 'ssss';
		
		if($db->update()) {
			echo json_encode(array('result'=>"SUCCESS", 'user_id'=>$params, 'errors'=>""));
		}
		else $err[] = "SQL Error";
	}
	if($err) {
		echo json_encode(array('result'=>"ERROR", 'user'=>"", 'errors'=>$err)); 
	}
?>