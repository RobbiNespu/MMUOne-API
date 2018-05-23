<?php
	//	***********************************
	//
	//				MMU PORTAL
	//
	//	***********************************
	
	//	Headers
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Headers: access");
	header("Access-Control-Allow-Methods: GET");
	header("Access-Control-Allow-Credentials: true");
	header("Content-Type: application/json; charset=UTF-8");
	//	TODO TOKEN AUTHORIZATION
	//	TODO ADD COMMENTS
	
	//	Connection
	require_once '../config/connection.php';
	
	//	Users object
	require_once '../objects/users.php';
	
	//	Portal object
	require_once '../objects/portal.php';
	
	//	Get Simple HTML DOM library
	require_once '../library/html_dom.php';

	//	Include cURL function: curl(url, postRequest, data, cookie)
	require_once '../objects/curl.php';

	//	Include Message Sender function
	require_once '../objects/messageSender.php';
	
	//	Instantiate users object and retrieve connection
	$db = new Database();
	$conn = $db->connect();
	
	//	Set connection for users table
	$users = new Users($conn);
	
	//	Set up Portal object
	$portal = new Portal($conn);

	//	Set error
	$error = 00000;
	
	//	Check if Student ID provided
	if (empty($_GET['student_id']))
	{
		//	TODO Set error
		
		//	Echo JSON message
		
		//	Kill
		die("No student ID specified");	
	}
	
	//	Set the student ID
	$users->student_id = $_GET['student_id'];

	//	Set cookie
	$cookie = "cookie/portal_{$users->student_id}.cke";

	//	Check if file exist
	if (!file_exists($cookie))
	{
		file_put_contents($cookie, "New file");
	}

	//	Retrieve user MMU IDM password to login to MMU Portal
	$users->readPasswordMMU();
	
	if (empty($users->password_mmu))
	{
		//	Failed to get user's MMU (IDM) password.
		//	TODO Set error
		
		//	Echo JSON message
		
		//	Kill
		die("Failed to get user's MMU password");
	}
	
	//	Set Login Credentials for MMU Portal
	$studentID = $_GET['student_id'];
	$password = $users->password_mmu;
		
	//	Login to MMU Portal
	//	Check if login fails
	if (!login($studentID, $password, $cookie))
	{
		//	Failed to login user to MMU Portal
		//	TODO Set error (error code 20601)
		
		//	Echo JSON message
		
		//	Kill
		die("Failed to login user to MMU Portal");
	}

	//	Echo message
	messageSender(1, "Logged in");

	//	Login function with URL and cURL
	function login($studentID, $passwordMMU, $cookie)
	{
		//	Login user
		//	Session ends when browser ends

		//	URL of MMU Portal login
		$url = "https://online.mmu.edu.my/index.php";

		//	Data for Login POST
		$data = array('form_loginUsername' => $studentID, 'form_loginPassword' => $passwordMMU);

		//	It is a POST request
		$postRequest = true;

		//cURL
		$curl = NULL;

		$curlResult = curl($curl, $url, $postRequest, $data, $cookie);

		if (!$curlResult[0])
		{
			//	log in failed
			//	TODO ADD ERROR MESSAGE
			$this->error = 20601;

			return false;
		}
		//	TODO check if log in succeeded
		//return $curlResult[1];
		//	Return login succeeded
		return true;
	}
