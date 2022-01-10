<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
	/*
	* 동일한 휴대폰번호 체크
	* parameter ==> hpNo:    휴대폰번호
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$hpNo = trim($data_back->{'hpNo'});

	//$hpNo = "010-2723-3377";

	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

	// 실행할 쿼리를 작성합니다.
    $sql = "SELECT idx FROM member WHERE hpNo = '$hpNo'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	if ($row->idx == "") {
		// 결과를 반환합니다.
		$result_status = "0";
		$result_message = "정상";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "동일한 휴대폰번호가 존재합니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>