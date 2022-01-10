<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 휴대폰신청 > 신청목록 > 관리자메모 > 등록
	* parameter
		idx:        회원Idx
		adminId:    관리자ID
		adminName:  관리자명
		adminMemo:  관리자 메모
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx        = $input_data->{'idx'};
	$adminId    = $input_data->{'adminId'};
	$adminName  = $input_data->{'adminName'};
	$adminMemo  = $input_data->{'adminMemo'};

	$sql = "INSERT INTO hp_request_memo (requestIdx, adminId, adminName, adminMemo, wdate) 
						         VALUES ('$idx', '$adminId', '$adminName', '$adminMemo', now())";
	$connect->query($sql);

	$response = array(
		'result'  => "0",
		'message' => "'등록'되었습니다."
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>