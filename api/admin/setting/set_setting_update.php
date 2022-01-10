<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 기초정보
		hpIntRate:     휴대폰신청 > 할부이자율
		cashOutRate:   포인트관리 > 현급지급율
		insuRate:      다이렉트보험 > 수수료율
		hpInstallment: 휴대폰 할부개월
		apiKey:        공통 API key
	*/
	$input_data    = json_decode(file_get_contents('php://input'));
	$hpInstallment = $input_data->{'hpInstallment'};
	$hpIntRate     = $input_data->{'hpIntRate'};
	$cashOutRate   = $input_data->{'cashOutRate'};
	$insuRate      = $input_data->{'insuRate'};
	$apiKey        = $input_data->{'apiKey'};

	// ****************************************** 휴대폰신청 **************************************
	// 월 할부 이자율
	$sql = "SELECT code FROM setting WHERE code = 'hpIntRate'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$sql = "INSERT INTO setting (assort, code, content) VALUES ('V', 'hpIntRate', '$hpIntRate')";
		$connect->query($sql);
	} else {
		$sql = "UPDATE setting SET content = '$hpIntRate' WHERE code = 'hpIntRate'";
		$connect->query($sql);
	}

	// 할부개월
	for ($i = 0; count($hpInstallment) > $i; $i++) {
		$row = $hpInstallment[$i];
		$installment = $row->installment;
		$telecom     = $row->telecom;

		$checkdCode = "";

		for ($n = 0; count($installment) > $n; $n++) {
			$row2 = $installment[$n];
			$code    = $row2->code;
			$checked = $row2->checked;

			if ($checked == true) {
				if ($checkdCode != "") $checkdCode = $checkdCode . ",";

				$checkdCode = $checkdCode . $code;
			}
		}

		$sql = "UPDATE hp_installment SET installment = '$checkdCode' WHERE telecom = '$telecom'";
		$connect->query($sql);
	}

	// ****************************************** 포인트관리 **************************************
	// 현급지급율
	$sql = "SELECT code FROM setting WHERE code = 'cashOutRate'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$sql = "INSERT INTO setting (assort, code, content) VALUES ('V', 'cashOutRate', '$cashOutRate')";
		$connect->query($sql);
	} else {
		$sql = "UPDATE setting SET content = '$cashOutRate' WHERE code = 'cashOutRate'";
		$connect->query($sql);
	}

	// ****************************************** 다이렉트보험 **************************************
	// 수수료율
	$sql = "SELECT code FROM setting WHERE code = 'insuRate'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$sql = "INSERT INTO setting (assort, code, content) VALUES ('V', 'insuRate', '$insuRate')";
		$connect->query($sql);
	} else {
		$sql = "UPDATE setting SET content = '$insuRate' WHERE code = 'insuRate'";
		$connect->query($sql);
	}

	// ****************************************** 공통 API KEY **************************************
	for ($i = 0; count($apiKey) > $i; $i++) {
		$row = $apiKey[$i];
		$idx    = $row->idx;
		$apiKey = $row->apiKey;

		$sql = "UPDATE api_key SET apiKey = '$apiKey' WHERE idx = '$idx'";
		$connect->query($sql);
	}

	// 성공 결과를 반환합니다.
	$result = "0";
	$result_message = "저장하였습니다.";

	$response = array(
		'result'    => $result,
		'message'   => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>