<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* parameter 
		companyCode: 택배사코드
		deliveryNo:  송장번호
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$companyCode = $input_data->{'companyCode'};
	$deliveryNo  = $input_data->{'deliveryNo'};

	$companyCode = "06";
	$deliveryNo = "30834744231";

	$sql = "SELECT apiKey FROM api_key WHERE useYn = 'Y' and assortCode = 'ST'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$data = Array(
			"t_key"     => $row->apiKey,
			"t_code"    => $companyCode,
			"t_invoice" => $deliveryNo,
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
			$trackingDetails = $response[trackingDetails];

			foreach($trackingDetails as $row) {
				$data_info = array(
					'time'  => $row[timeString],
					'where' => $row[where],
					'kind'  => $row[kind],
				);
				array_push($data, $data_info);
			}

			$result_status = "0";
			$result_message = "정상";

		} else {
			$result_status = "1";
			$result_message = $response[msg];
		}

	} else {
		$result_status = "1";
		$result_message = "택배사 API Key값이 없습니다.";
	}

	$response = array(
		'result'   => $result_status,
		'message'  => $result_message,
		'data'     => $data
    );
print_r($response);
exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
