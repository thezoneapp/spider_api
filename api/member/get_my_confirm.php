<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 내정보 확인
	* parameter ==> userId: 아이디
	* parameter ==> userPw: 비밀번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId     = $input_data->{'userId'};
	$userPw     = $input_data->{'userPw'};

	if ($userPw != "") $userPw = aes128encrypt($userPw);

    $sql = "SELECT idx FROM member WHERE memId = '$userId' and memPw = '$userPw'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$result_ok = "0";
		$result_message = "비밀번호가 일치합니다.";

    } else {
		$result_ok = "1";
		$result_message = "비밀번호가 일치하지 않습니다";
	}

	$response = array(
		'result'  => $result_ok,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>