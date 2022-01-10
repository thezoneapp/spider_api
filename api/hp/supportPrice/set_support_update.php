<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 신청 > 공시지원가 > 상세정보 > 추가.수정
	* parameter ==> mode:        insert(추가), update(수정)
	* parameter ==> idx:         수정할 레코드 id
	* parameter ==> telecom:     통신사
	* parameter ==> modelCode:   모델코드
	* parameter ==> priceNew:    신규
	* parameter ==> priceMnp:    번호이동
	* parameter ==> priceChange: 기기변경
	* parameter ==> useYn:       사용여부
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$mode        = $data_back->{'mode'};
	$idx         = $data_back->{'idx'};
	$telecom     = $data_back->{'telecom'};
	$modelCode   = $data_back->{'modelCode'};
	$priceNew    = $data_back->{'priceNew'};
	$priceMnp    = $data_back->{'priceMnp'};
	$priceChange = $data_back->{'priceChange'};
	$useYn       = $data_back->{'useYn'};

	//$telecom   = $telecom->{'code'};
	$modelCode = $modelCode->{'code'};

	if ($mode == "insert") {
		$sql = "INSERT INTO hp_support_price (telecom, modelCode, priceNew, priceMnp, priceChange, useYn, wdate)
						              VALUES ('$telecom', '$modelCode', '$priceNew', '$priceMnp', '$priceChange', '$useYn', now())";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "등록하였습니다.";

	} else {
		$sql = "UPDATE hp_support_price SET telecom = '$telecom',
		                                    modelCode = '$modelCode', 
								            priceNew = '$priceNew', 
								            priceMnp = '$priceMnp', 
									        priceChange = '$priceChange', 
								            useYn = '$useYn', 
									        wdate = now() 
						WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "변경하였습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>