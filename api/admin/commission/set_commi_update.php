<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 포인트관리 > 적립목록 > 상세정보 > 추가/수정
	* parameter
		mode:        insert(추가), update(수정)
		idx:         수정할 레코드 id
		sponsId:     스폰서ID
		sponsName:   스폰서명
		memId:       회원ID
		memName:     회원명
		assort:      포인트구분
		resultPrice: 실적포인트
		payPrice:    이용수수료
		price:       실적포인트
		status:      정산상태
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$mode        = $input_data->{'mode'};
	$idx         = $input_data->{'idx'};
	$sponsId     = $input_data->{'sponsId'};
	$sponsName   = $input_data->{'sponsName'};
	$memId       = $input_data->{'memId'};
	$memName     = $input_data->{'memName'};
	$assort      = $input_data->{'assort'};
	$resultPrice = $input_data->{'resultPrice'};
	$payPrice    = $input_data->{'payPrice'};
	$price       = $input_data->{'price'};
	$status      = $input_data->{'status'};
	$wdate       = $input_data->{'wdate'};

	$assort = $assort->{'code'};
	$status = $status->{'code'};

	$resultPrice = str_replace(",", "", $resultPrice);
	$payPrice = str_replace(",", "", $payPrice);
	$price = str_replace(",", "", $price);

	if ($mode == "insert") {
		$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, assort, resultPrice, payPrice, price, accurateStatus, wdate)
						        VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$assort', '$resultPrice', '$payPrice', '$price', '$status', now())";
		$result = $connect->query($sql);

		// 등록 결과를 반환합니다.
		$result = "0";
		$result_message = "등록되었습니다.";

	} else {
		$sql = "UPDATE commission SET sponsId = '$sponsId', 
		                              sponsName = '$sponsName', 
									  memId = '$memId', 
									  memName = '$memName', 
									  assort = '$assort', 
									  resultPrice = '$resultPrice', 
									  payPrice = '$payPrice', 
									  price = '$price', 
									  accurateStatus = '$status', 
									  wdate = '$wdate'
				WHERE idx = '$idx'";
		$result = $connect->query($sql);

		// 변경 결과를 반환합니다.
		$result = "0";
		$result_message = "변경하였습니다.";
	}

	$response = array(
		'result'    => $result,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>