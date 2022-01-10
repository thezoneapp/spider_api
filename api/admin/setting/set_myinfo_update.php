<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 기초정보
	* parameter ==> code[]:    코드 배열
	* parameter ==> content[]: 코드 배열

	* 개설비용    > 대리점       > 코드: subsA
	* 개설비용    > 판매점       > 코드: subsS

	* 월 납입비용 > 대리점       > 코드: payA
	* 월 납입비용 > 판매점       > 코드: payS

	* 수익플랜    > 개설비용     > 코드: commitS
	* 수익플랜    > 월구독료(대) > 코드: commitMA
	* 수익플랜    > 월구독료(판) > 코드: commitMS
	* 수익플랜    > 렌탈수수료   > 코드: commitR

	* 가입약관 > 코드: joinTerms
	*/

	$input_data  = json_decode(file_get_contents('php://input'));
	$subsA    = $input_data->{'subsA'};
	$subsS    = $input_data->{'subsS'};
	$payA     = $input_data->{'payA'};
	$payS     = $input_data->{'payS'};
	$commitS  = $input_data->{'commitS'};
	$commitMA = $input_data->{'commitMA'};	
	$commitMS = $input_data->{'commitMS'};
	$commitR  = $input_data->{'commitR'};

	// 개설비용 > 대리점
	$sql = "SELECT code FROM setting WHERE code = 'subsA'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$sql = "INSERT INTO setting (code, content) VALUES ('subsA', '$subsA')";
		$connect->query($sql);
	} else {
		$sql = "UPDATE setting SET content = '$subsA' WHERE code = 'subsA'";
		$connect->query($sql);
	}

	// 개설비용 > 판매점
	$sql = "SELECT code FROM setting WHERE code = 'subsS'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$sql = "INSERT INTO setting (code, content) VALUES ('subsS', '$subsS')";
		$connect->query($sql);
	} else {
		$sql = "UPDATE setting SET content = '$subsS' WHERE code = 'subsS'";
		$connect->query($sql);
	}

	// 월 납입비용 > 대리점
	$sql = "SELECT code FROM setting WHERE code = 'payA'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$sql = "INSERT INTO setting (code, content) VALUES ('payA', '$payA')";
		$connect->query($sql);
	} else {
		$sql = "UPDATE setting SET content = '$payA' WHERE code = 'payA'";
		$connect->query($sql);
	}

	// 월 납입비용 > 판매점
	$sql = "SELECT code FROM setting WHERE code = 'payS'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$sql = "INSERT INTO setting (code, content) VALUES ('payS', '$payS')";
		$connect->query($sql);
	} else {
		$sql = "UPDATE setting SET content = '$payS' WHERE code = 'payS'";
		$connect->query($sql);
	}

	// 수익플랜 > 개설비용
	$sql = "SELECT code FROM setting WHERE code = 'commitS'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$sql = "INSERT INTO setting (code, content) VALUES ('commitS', '$commitS')";
		$connect->query($sql);
	} else {
		$sql = "UPDATE setting SET content = '$commitS' WHERE code = 'commitS'";
		$connect->query($sql);
	}

	// 수익플랜 > 월구독료(대)
	$sql = "SELECT code FROM setting WHERE code = 'commitMA'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$sql = "INSERT INTO setting (code, content) VALUES ('commitMA', '$commitMA')";
		$connect->query($sql);
	} else {
		$sql = "UPDATE setting SET content = '$commitMA' WHERE code = 'commitMA'";
		$connect->query($sql);
	}

	// 수익플랜 > 월구독료(판)
	$sql = "SELECT code FROM setting WHERE code = 'commitMS'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$sql = "INSERT INTO setting (code, content) VALUES ('commitMS', '$commitMS')";
		$connect->query($sql);
	} else {
		$sql = "UPDATE setting SET content = '$commitMS' WHERE code = 'commitMS'";
		$connect->query($sql);
	}

	// 수익플랜 > 렌탈수수료
	$sql = "SELECT code FROM setting WHERE code = 'commitR'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$sql = "INSERT INTO setting (code, content) VALUES ('commitR', '$commitR')";
		$connect->query($sql);
	} else {
		$sql = "UPDATE setting SET content = '$commitR' WHERE code = 'commitR'";
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