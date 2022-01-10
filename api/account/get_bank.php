<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
	/*
	* 관리자정보
	* parameter ==> user_id: 회원ID
	* parameter ==> user_pw: 회원PW
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};
	$userPw = $data_back->{'userPw'};
	
	if ($userPw != "") $userPw = aes128encrypt($userPw);

	// 실행할 쿼리를 작성합니다.
    $sql = "SELECT id, name, passwd, auth
			FROM ( SELECT id, name, passwd, 'A' as auth, use_yn AS status
                   FROM admin
                   union 
                   SELECT memId AS id, memName AS name, memPw AS passwd, memAssort AS auth,
                          if(memStatus = '9','Y','N') AS status
                   FROM member
				 ) agency 
			WHERE status = 'Y' and id = '$userId' and passwd = '$userPw'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$data = array(
			'userId'   => $row->id,
			'userName' => $row->name,
			'userAuth' => $row->auth
		);

		// 성공 결과를 반환합니다.
		$result = "0";
		$result_message = "로그인에 성공하였습니다.";

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
		$result_message = "아이디 또는 비밀번호 오류입니다.";
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