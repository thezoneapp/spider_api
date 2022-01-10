<?
    /***********************************************************************************************************************
	* 택배 배송상태 체크
	***********************************************************************************************************************/

	$db_host = "localhost";
	$db_user = "spiderfla";
	$db_pass = "dlfvkf#$12";
	$db_name = "spiderfla";

	$connect = @mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die("Failed to connect to MySQL: " . mysqli_error()); 

	// API 키값
	$sql = "SELECT apiKey FROM api_key WHERE useYn = 'Y' and assortCode = 'ST'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$apiKey = $row->apiKey;

		// 배송중인 자료 검색
		$sql = "SELECT idx, deliveryCompany, deliveryNo 
				FROM hp_request
				WHERE deliveryStatus IN ('1','2') and deliveryCompany != '000' and deliveryCompany != '' and deliveryNo != ''";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				$idx = $row2[idx];

				$data = Array(
					"t_key"     => $apiKey,
					"t_code"    => $row2[deliveryCompany],
					"t_invoice" => $row2[deliveryNo]
				);

				$url = "http://info.sweettracker.co.kr/api/v1/trackingInfo?" . http_build_query($data, '');

				$ch = curl_init();                                 //curl 초기화
				curl_setopt($ch, CURLOPT_URL, $url);               //URL 지정하기
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    //요청 결과를 문자열로 반환 
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);      //connection timeout 10초 
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   //원격 서버의 인증서가 유효한지 검사 안함
				 
				$response = curl_exec($ch);

				curl_close($ch);

				$response = json_decode($response, true);

				$data = array();

				if ($response[result] == "Y") {
					if ($response[completeYN] == "Y") $status = "9"; // 배송완료
					else $status = "2"; // 배송중

					$sql = "UPDATE hp_request SET deliveryStatus = '$status' WHERE idx = '$idx'";
					$connect->query($sql);
				}
			}
		}
	}

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);

	exit;
?>