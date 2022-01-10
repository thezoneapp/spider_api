<?
	// *********************************************************************************************************************************
	// *                                                     회원가입 전송                                                               *
	// *********************************************************************************************************************************
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "./token.php";

	/*
	* parameter ==> memId: 회원 아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId = $input_data->{'memId'};
	//$memId = "a27233377";

	// 회원정보
	$sql = "SELECT memName, hpNo FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$memName = $row->memName;

		if ($row->hpNo !== "") {
			$hpNo = aes_decode($row->hpNo);
			$hpNo = str_replace("-", "", $hpNo);
		} else $hpNo = "";
	}

	// 토큰 취득
	$token = json_decode(get_token());
	$responseCode = $token->responseCode;
	$result_message = $token->message;

	if ($responseCode == "200") {
		$tokenKey = $token->data->tokenKey;

		// 회원가입 API 호출
		//$url = "https://dev-usedcar-api.adinsu.co.kr/v2/externalApi/member/register"; // 개발서버
		$url = "https://usedcar-api.adinsu.co.kr/v2/externalApi/member/register"; // 실서버
		$headers = Array("Content-Type: application/json");

		$header = Array(
			'OPERA-TOKEN: ' . $tokenKey,
			'Content-Type: application/json'
		);
		$fields = Array(
			"coCode"     => $coCode, 
			"coUserKey"  => $memId,
			"dealerName" => $memName,
			"mobile"     => $hpNo
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($ch, CURLOPT_ENCODING , "");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields)); 
			
		$response = curl_exec($ch);
		$err = curl_error($ch);

		curl_close($ch);

		if ($err) {
			echo "curl Error #:" . $err;
		}

		$response = json_decode($response);
		$responseCode = $response->responseCode;

		if ($responseCode == "200") {
			$insuId = $response->data->dealerCode;
			$sql = "UPDATE member SET insuId = '$insuId' WHERE memId = '$memId'";
			$connect->query($sql);

			$result_status = "0";
			$result_message = "가입이 완료되었습니다.";

		} else {
			$result_status = "1";
			$result_message = $response->message;
		}

	} else {
		$result_status = "1";
	}

	$response = array(
		'result'   => $result_status,
		'message'  => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>