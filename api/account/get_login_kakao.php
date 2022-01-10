<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 카카오톡 로그인
	* parameter
		token:   token
		kakaoId: 카카오ID
		hpNo:    휴대폰번호
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$token   = trim($data_back->{'token'});
	$kakaoId = trim($data_back->{'kakaoId'});
	$hpNo    = trim($data_back->{'hpNo'});

	/*
	$token = "MAFv7fC9IYp0o9olGcSzzxSRnOd8E87rtWFwKwo9dBEAAAF9270BWQ";	

	//사용자 정보 가저오기 
	$API_URL = "https://kapi.kakao.com/v2/user/me"; 


	// header
	$header = Array(
		"Content-type: application/x-www-form-urlencoded;charset=utf-8",
		"Authorization: Bearer " . $token,
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $API_URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($ch, CURLOPT_ENCODING , "");
	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	$response = curl_exec($ch);

	curl_close($ch);

	$response = json_decode($response, true);

	$kakaoId = $response['id'];
	$account = $response['kakao_account'];
	$hpNo    = $account['phone_number'];
	*/

	$hpNo = str_replace("+82 ", "0", $hpNo);

	if ($hpNo == "" || $hpNo == null) {
		$result_status = "1";
		$result_message = "'휴대폰번호'가 누락되었습니다.";

	} else {
		$hpNo = aes128encrypt($hpNo);

		$sql = "SELECT memId, memPw FROM member WHERE hpNo = '$hpNo'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);

			if ($row->memPw != "") $row->memPw = aes_decode($row->memPw);

			$memId = $row->memId;
			$memPw = $row->memPw;

			$sql = "UPDATE member SET kakaoId = '$kakaoId', kakaoToken = '$token' WHERE memId = '$memId'";
			$connect->query($sql);

			$result_status = "0";
			$result_message = "정상";

		} else {
			$result_status = "1";
			$result_message = "가입된 '휴대폰번호'가 아닙니다.";
		}
	}

	$response = array(
		'userId'  => $row->memId,
		'userPw'  => $row->memPw,
		'result'  => $result_status,
		'message' => $result_message,
    );

//print_r($response);
//exit;

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
