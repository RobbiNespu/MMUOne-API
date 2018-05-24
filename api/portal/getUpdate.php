<?php
	/**
	 * Created by PhpStorm.
	 * User: Timothy
	 * Date: 29/3/2018
	 * Time: 12:13 AM
	 */

	//	Header
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Headers: access");
	header("Access-Control-Allow-Methods: GET");
	header("Access-Control-Allow-Credentials: true");
	header("Content-Type: application/json; charset=UTF-8");

	//	Connection
	require_once '../config/connection.php';

	//	Users object
	require_once '../objects/users.php';

	//	Portal object
	require_once '../objects/portal.php';

	//	Get Simple HTML DOM library
	require_once '../library/html_dom.php';

	//	Include Message Sender function
	require_once '../objects/messageSender.php';

	//	Include cURL function: curl(url, postRequest, data, cookie)
	require_once '../objects/curl.php';

	//	New Simple HTML DOM object
	$htmlDOM = new simple_html_dom();

	//	Instantiate users object and retrieve connection
	$db = new Database();
	$conn = $db->connect();

	//	Set up Portal object
	$portal = new Portal($conn);

	//	Set error
	$error = 000000;

	//	Get $_GET data
	//	Check if tab provided
	if (empty($_GET['tab']))
	{
		//	TODO Set error

		//	Echo JSON message

		//	Kill
		die("No tab provided");
	}
	$tab = $_GET['tab'];

	//	Check if Student ID provided
	if (empty($_GET['student_id']))
	{
		//	TODO Set error

		//	Echo JSON message

		//	Kill
		die("No student ID specified");
	}
	$student_id = $_GET['student_id'];

	//	Set cookie
	$cookie = "cookie/portal_{$student_id}.cke";

	//	force_update is optional, default is 0 or no force update
	//	If a token number is given, force_update has no effect
	$forcedUpdate = false;
	if (!empty($_GET['force_update']))
	{
		$forcedUpdate = (bool)$_GET['force_update'];
	}

	//	Check if token provided
	//	Token equals to page number
	if (!empty($_GET['token']))
	{
		$token = $_GET['token'];
	}

	//	Set status variable
	$status = 1;

	//	Set bulletin paged array
	//	bulletin contains max 9 news, page 0 for no pages 1 for more pages
	$bulletinPaged["bulletin"] = array();
	$bulletinPaged["hasPage"] = 0;
	$bulletinPaged["size"] = 0;
	$bulletinPaged["token"] = 0;

	if (empty($token))
	{
		//	If time getting data, no token exist
		//	Get all the bulletin news
		//	Get bulletin with specific page: Page 0 for 1-10 news, 1 for 11-20 news
		//	URL of MMU Portal's Bulletion Boarx
		$url = "https://online.mmu.edu.my/bulletin.php";

		//	cURL
		$curl = NULL;

		//	It is not a POST request
		$postRequest = FALSE;

		//	Execute cURL requets
		$curlResult = curl($curl, $url, $postRequest, $data = array(), $cookie);

		if (!$curlResult[0])
		{
			$errorMessage = $curlResult[1];

			//	TODO ADD ERROR MESSAGE
			//	Get bulletin failed
			$error = 20602;

			//TODO check return result

			// TODO echo error
		}
		else
		{
			//	If bulletin data retrieved successfully
			//	Load the string to HTML DOM without stripping /r/n tags
			$htmlDOM->load($curlResult[1], TRUE, FALSE);

			//	Find the desired input field
			$bulletin = $htmlDOM->find("div[id=tabs-{$tab}] div.bulletinContentAll");

			if (empty($_GET['hash']))
			{
				//	Get old hash
				$portal->getHash($student_id, $tab);
				$oldHash = $portal->hash;
			}
			else
			{
				$oldHash = $_GET['hash'];
			}

			//	Get latest hash
			$latestHash = hash('sha256', $bulletin[0]->plaintext);

			//	Set the latest bulletin news
			foreach ($bulletin as $key => $bulletinSingle)
			{
				//	Get new hash
				$currentHash = hash('sha256', $bulletinSingle->plaintext);

				//	If current new news is already in the database, return
				//	If this is not forced update, return
				if ($oldHash == $currentHash && !$forcedUpdate)
				{
					break;
				}
				else
				{
					//	Push the plaintext into bulletinPaged's bulletin
					array_push($bulletinPaged["bulletin"], $bulletinSingle->plaintext);

					//	Increment the bulletin size by 1
					$bulletinPaged["size"] = $bulletinPaged["size"] + 1;

					//	Token is the total size sent
					$bulletinPaged["token"] = $bulletinPaged["token"] + 1;

					//	If max key reached
					if ($key == 9)
					{
						//	Set more pages to true or 1
						$bulletinPaged["hasPage"] = 1;

						//	Break the foreach loop
						break;
					}
				}
			}

			$bulletinAll = array();

			foreach ($bulletin as $key => $bulletinSingle)
			{
				$bulletinAll[$key] = $bulletinSingle->plaintext;
			}

			//	Clear the htmlDOM memory
			$htmlDOM->clear();

			//	Update table with data and latest hash
			$portal->updateTable($student_id, $tab, json_encode($bulletinAll), $latestHash);
		}
	}
	else
	{
		//	If token exist, get next page of data and echo as JSON
		//	$token is total bulletin size sent
		//	Set the bulletin token
		$bulletinPaged["token"] = $token;

		//	Get bulletin data
		$bulletin = $portal->getBulletin($student_id, $tab);

		//	TODO check if data retrieval succeeded
		if (!$bulletin)
		{

		}
		$bulletin = json_decode(html_entity_decode($portal->data));

		//	Load the string to HTML DOM without stripping /r/n tags
		//$htmlDOM->load($bulletinRetrieved, TRUE, FALSE);

		//	Find the desired input field
		//$bulletin = $htmlDOM->find("div[id=tabs-{$tab}] div.bulletinContentAll");

		//	Counter to skip the bulletin data that are already sent
		$pageCount = 0;

		//	Get the end key
		end($bulletin);
		$lastKey = key($bulletin);

		//	Set the next 10 bulletin data
		foreach ($bulletin as $key => $bulletinSingle)
		{
			if ($pageCount < $token)
			{
				//	Increment the counter
				$pageCount++;
				continue;
			}

			//	Push the plaintext into bulletinPaged's bulletin
			array_push($bulletinPaged["bulletin"], htmlentities($bulletinSingle));

			//	Increment the bulletin size by 1
			$bulletinPaged["size"] = $bulletinPaged["size"] + 1;

			//	Token is the total size sent
			$bulletinPaged["token"] = $bulletinPaged["token"] + 1;

			//	If max key reached
			if ($key - $token == 9 && $key != $lastKey)
			{
				//	Set more pages to true or 1
				$bulletinPaged["hasPage"] = 1;

				//	Break the foreach loop
				break;
			}
		}
	}

	//	Echo result as JSON
	//	-	bulletin data
	//	-	hasPage
	//	-	size
	messageSender($status, $bulletinPaged);