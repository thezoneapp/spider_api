<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 관리자 추가/수정
	* parameter ==> mode:      insert(추가), update(수정)
	* parameter ==> idx:       수정할 레코드 id
	* parameter ==> adminId:   아이디
	* parameter ==> adminPw:   비밀번호
	* parameter ==> adminName: 이름
	* parameter ==> phone:     연락처
	* parameter ==> email:     이메일
	* parameter ==> auth:      권한구분
	* parameter ==> useYn:     사용여부
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$mode   = $input_data->{'mode'};
	$idx    = $input_data->{'idx'};
	$id     = $input_data->{'adminId'};
	$passwd = $input_data->{'adminPw'};
    $name   = $input_data->{'adminName'};
    $phone  = $input_data->{'phone'};
	$email  = $input_data->{'email'};
	$auth   = $input_data->{'auth'};
	$useYn  = $input_data->{'useYn'};

	$auth   = $auth->{'code'};

	if ($passwd != "") $passwd = aes128encrypt($passwd);
	if ($phone != "") $phone = aes128encrypt($phone);
	if ($email != "") $email = aes128encrypt($email);

	if ($mode == "insert") {
		// 같은 아이디가 있나 체크
		$sql = "SELECT id
				FROM ( select id as agencyId from admin
					   union 
					   select agencyId from agency 
					 ) agency 
				WHERE agencyId = '$id'";
		$result = $connect->query($sql);

		if ($result->num_rows == 0) {
			$sql = "INSERT INTO admin (id, passwd, name, phone, email, auth, use_yn)
							   VALUES ('$id', '$passwd', '$name', '$phone', '$email', '$auth', '$useYn')";
			$result = $connect->query($sql);

			// 성공 결과를 반환합니다.
			$result = "0";
			$result_message = "등록하였습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result = "1";
			$result_message = "이미 존재하는 아이디입니다..";
		}

	} else {
		$sql = "UPDATE admin SET id = '$id', 
								 passwd = '$passwd', 
								 name = '$name', 
								 phone = '$phone', 
								 email = '$email', 
								 auth = '$auth', 
								 use_yn = '$useYn' 
				WHERE idx = '$idx'";
		$result = $connect->query($sql);

		// 성공 결과를 반환합니다.
		$result = "0";
		$result_message = "변경하였습니다.";
	}

	$response = array(
		'result'    => $result,
		'message'   => $result_message,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>