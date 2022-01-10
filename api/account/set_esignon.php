<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	// *********************************************************************************************************************************
	// *                                                     토콘값 가져오기                                                             *
	// *********************************************************************************************************************************
	function get_token($comp_id, $client_id, $memb_email, $memb_pwd) {
		$url = "https://docs.esignon.net/api/" . $comp_id . "/login";
		$headers = Array("Content-Type: application/json", "User-Agent: esignonapi");

		$data_header = Array("request_code"=>"1001Q", "request_msg"=>"인증코드를 요청합니다.", "session_id"=>"thread_023");
		$data_body   = Array("comp_id"=>$comp_id, "memb_email"=>$memb_email, "memb_pwd"=>$memb_pwd, "client_id"=>$client_id);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($ch, CURLOPT_ENCODING , "");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(Array('header' => $data_header, 'body' => $data_body))); 
			
		$response = curl_exec($ch);
		$err = curl_error($ch);

		curl_close($ch);

		if ($err) {
			echo "cURL Error #:" . $err;
		}

		return $response;
	}

	// *********************************************************************************************************************************
	// *                                                     계약서 전송                                                                *
	// *********************************************************************************************************************************
	/*
	* parameter ==> memId: 회원 아이디
	*/
	$memId = $_POST['memId'];

	// 회원정보
	$sql = "SELECT memName, hpNo FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$memName = $row->memName;
		if ($row->hpNo !== "") $hpNo = aes_decode($row->hpNo);
	}

	//$comp_id = "Testapi";
	//$client_id = "*C9E7513F88CF918AC0C393B3CF14F9CF26F70017";
	//$memb_email = "heemang4989@nate.com";
	//$memb_pwd = "qkrxotn1013*";

	$comp_id = "daum1nbbangone";
	$client_id = "*DF2EDA5AD954FBA12A9481B8BC30633437FCFD14";
	$memb_email = "nbbangone@daum.net";
	$memb_pwd = "dlfvkf12##";

	$memb_id_type = "Mobile"; // EMai,Mobile
	$enable_mobile_cert = "false"; //
	$workflow_name = "가맹점계약서";
	$api_type = "StartAndEnd";
	$doc_id = "5";
	$biz_id = "0";

	// 토큰 취득
	$gettoken = json_decode(get_token($comp_id, $client_id, $memb_email, $memb_pwd));
	$access_token = $gettoken->body->access_token;

	// 계약서내용발송
	$url = "https://docs.esignon.net/api/" . $comp_id . "/startsimple";
	$headers = Array(
					"Content-Type: application/json; charset=utf-8", 
					"User-Agent: esignonapi", 
					"Authorization: esignon " . $access_token
					);

	$data_header = Array(
						 "request_code"=>"5005Q", 
						 "api_name"=>"start api", 
						 "session_id"=>"S1001", 
						 "version"=>"1.1.58"
						 );

	// 수신정보 설정
	$player_list = Array();

	$player_info = Array(		
						"field_owner"=>"1", 
						"email"=>$hpNo, 
						"id_type"=>"EMail", 
						"name"=>$memName, 
						"language"=>"ko-KR", 
						"enable_mobile_cert"=>$enable_mobile_cert,
						"mobile_number"=>str_replace("-", "", $hpNo)
						);
	array_push($player_list, $player_info);

	// 변수설정
	$field_list = Array();

	$field_info = Array(		
						"doc_id"=>$doc_id, 
						"field_name"=>"year", 
						"field_value"=>date("Y")
						);
	array_push($field_list, $field_info);

	$field_info = Array(		
						"doc_id"=>$doc_id, 
						"field_name"=>"month", 
						"field_value"=>date("m")
						);
	array_push($field_list, $field_info);

	$field_info = Array(		
						"doc_id"=>$doc_id, 
						"field_name"=>"day", 
						"field_value"=>date("d")
						);
	array_push($field_list, $field_info);

	$field_info = Array(		
						"doc_id"=>$doc_id, 
						"field_name"=>"name", 
						"field_value"=>$memName
						);
	array_push($field_list, $field_info);

	$field_info = Array(		
						"doc_id"=>$doc_id, 
						"field_name"=>"phone", 
						"field_value"=>$hpNo
						);
	array_push($field_list, $field_info);

	$field_info = Array(		
						"doc_id"=>$doc_id, 
						"field_name"=>"sign", 
						"field_value"=>""
						);
	array_push($field_list, $field_info);

	// 진행단계설정
	$fields = Array();
	$response_params = Array();
	$request_params = Array();

	$request_info = Array(
						"param_id"=>"", 
						"param_value"=>"", 
						"fields"=>$fields
						);
	array_push($request_params, $request_info);

	$export_info = Array(		
						"api_type"=>$api_type, 
						"url"=>"http://spiderplatform.co.kr/api/account/esignon_result.php", 
						"enable"=>"", 
						"request_code"=>"embed", 
						"clientid"=>$memId,
						"request_params"=>$request_params, 
						"response_params"=>$response_params
						);

	$data_body   = Array(
						"comp_id"=>$comp_id, 
						"biz_id"=>$biz_id, 
						"client_id"=>$client_id, 
						"memb_email"=>$memb_email, 
						"workflow_name"=>$workflow_name,
						"doc_id"=>$doc_id,
						"language"=>"ko-KR", 
						"player_list"=>$player_list,
						"field_list"=>$field_list,
						"export_api_info"=>$export_info,
						"comment"=>""
					);
	//echo json_encode(Array('header' => $data_header, 'body' => $data_body));
	//exit;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($ch, CURLOPT_ENCODING , "");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(Array('header' => $data_header, 'body' => $data_body))); 
			
	$response = curl_exec($ch);

	curl_close($ch);
//echo $response;
	$result_message = $response;
	$response = json_decode($response);
	$result_code = $response->header->result_code;

	if ($result_code == "00") {
		$play_url = $response->body->play_url;
		$result = "0";
		$message = "성공";

	} else {
		$result = "1";
		$message = "실패";
	}

	$response = array(
		'result'   => $result,
		'message'  => $message,
		'data'     => $play_url
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>