<?
	$authId       = "wvUqtxu8v7";               // 제휴사 인증ID
	$passwd       = "z7S6EeUJ0s8Yeo2VkUxuJQ=="; // 제휴사 인증KEY
	$coCode       = "QE3";                      // 제휴사 코드
	$siteCode     = "오쩌";                      // 사이트 코드
	$mainCode     = "A01";                      // 메인 코드
	$subCode      = "48";                       // 서브 코드
	$categoryCode = "125";                      // 카테고리 코드
	$intype       = "DreamF_T";                 // 제휴사 인타입 코드

	// *********************************************************************************************************************************
	// *                                                     토콘값 가져오기                                                             *
	// *********************************************************************************************************************************
	function get_token() {
		global $authId, $passwd;

		//$url = "https://dev-usedcar-api.adinsu.co.kr/v2/externalApi/token/get"; // 개발서버
		$url = "https://usedcar-api.adinsu.co.kr/v2/externalApi/token/get"; // 실서버
		$headers = Array("Content-Type: application/json");

		$data_header = Array();
		$data_body   = Array("authId" => $authId, "passwd" => $passwd);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($ch, CURLOPT_ENCODING , "");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_body)); 
			
		$response = curl_exec($ch);
		$err = curl_error($ch);

		curl_close($ch);

		if ($err) {
			echo "curl Error #:" . $err;
		}

		return $response;
	}
?>