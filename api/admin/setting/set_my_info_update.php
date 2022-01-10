<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 내정보 수정
	* parameter ==> userId:   아이디
	* parameter ==> userPw:   비밀번호
	* parameter ==> userName: 이름
	* parameter ==> phone:    연락처
	* parameter ==> email:    이메일
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId   = $input_data->{'userId'};
	$userPw   = $input_data->{'userPw'};
    $userName = $input_data->{'userName'};
    $phone    = $input_data->{'phone'};
	$email    = $input_data->{'email'};

	if ($userPw != "") $userPw = aes128encrypt($userPw);
	if ($phone != "") $phone = aes128encrypt($phone);
	if ($email != "") $email = aes128encrypt($email);

	$sql = "UPDATE admin SET passwd = '$userPw', 
							 name = '$userName', 
							 phone = '$phone', 
							 email = '$email'
			WHERE id = '$userId'";
	$result = $connect->query($sql);

	// 성공 결과를 반환합니다.
	$result = "0";
	$result_message = "변경하였습니다.";

	$response = array(
		'result'    => $result,
		'message'   => $result_message,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>