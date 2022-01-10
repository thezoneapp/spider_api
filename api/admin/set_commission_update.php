<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 수수료원장 추가/수정
	* parameter ==> mode:       insert(추가), update(수정)
	* parameter ==> idx:        수정할 레코드 id
	* parameter ==> sponsId:    스폰서ID
	* parameter ==> sponsName:  스폰서명
	* parameter ==> memId:      회원ID
	* parameter ==> memName:    회원명
	* parameter ==> assort:     수수료구분
	* parameter ==> price:      수수료
	* parameter ==> status:     정산상태
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$mode      = $input_data->{'mode'};
	$idx       = $input_data->{'idx'};
	$sponsId   = $input_data->{'sponsId'};
	$sponsName = $input_data->{'sponsName'};
	$memId     = $input_data->{'memId'};
	$memName   = $input_data->{'memName'};
	$assort    = $input_data->{'assort'};
	$price     = $input_data->{'price'};
	$status    = $input_data->{'status'};

	$assort = $assort->{'code'};
	$status = $status->{'code'};

	if ($mode == "insert") {
		$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, assort, price, accurateStatus, wdate)
						        VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$assort', '$price', '$status', now())";
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
									  price = '$price', 
									  accurateStatus = '$status'
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