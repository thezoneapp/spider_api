<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../utils/utility.php";

	/*
	* 애드인슈 > 회원정보 정보
	* parameter ==> memId: 아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId      = $input_data->{'memId'};
//$memId = "a27233377";
    $sql = "SELECT memName, photo, insuId FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$data = array(
			'memName' => $row->memName,
			'insuId'  => $row->insuId,
			'photo'   => $row->photo,
		);

		$result_status = "0";
		$result_message = "정상";

    } else {
		$data = array();
		$result_status = "1";
		$result_message = "존재하지 않는 회원입니다.";
	}

	$response = array(
		'result'           => $result_status,
		'message'          => $result_message,
		'data'             => $data,
		'CarNoOptions'     => $arrCarNoType3,
		'ExpiredOptions'   => $arrExpiredDate,
		'RegionOptions'    => $arrCustRegion,
		'marketingOptions' => $arrYesNo,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>