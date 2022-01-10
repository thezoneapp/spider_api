<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
	/*
	* 비밀번호 변경 저장
	* api/account/set_pw_change.php
	* parameter ==> userId: 회원ID
	* parameter ==> userPw: 비밀번호
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$userId = trim($data_back->{'userId'});
	$userPw = trim($data_back->{'userPw'});

	// 실행할 쿼리를 작성합니다.
    $sql = "SELECT memId FROM member WHERE memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		if ($userPw != "") $userPw = aes128encrypt($userPw);

		$sql = "UPDATE member SET memPw = '$userPw' WHERE memId = '$userId'";
		$connect->query($sql);

		// 결과를 반환합니다.
		$result_status = "0";
		$result_message = "'비밀번호'가 변경되었습니다.";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "해당하는 회원이 존재하지 않습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
