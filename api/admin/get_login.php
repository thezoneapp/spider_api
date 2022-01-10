<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
	/*
	* 관리자정보
	* parameter
		userId:  회원ID
		userPw:  회원PW
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$userId  = trim($data_back->{'userId'});
	$userPw  = trim($data_back->{'userPw'});

	//$userId = "admin";
	//$userPw = "123123";

	if ($userPw != "") $userPw = aes128encrypt($userPw);

	$domain    = $_SERVER['HTTP_REFERER'];
	$domain    = str_replace("https://", "", $domain);
	$domain    = str_replace("http://", "", $domain);
	$domain    = str_replace("www.", "", $domain);
	$arrDomain = explode("/", $domain);
	$domain    = $arrDomain[0];

	if (strpos($domain, "localhost") !== false) $domain = "spiderplatform.co.kr";

	// 실행할 쿼리를 작성합니다.
	$data = Array();
    $sql = "SELECT idx, id, name, if(use_yn = 'Y', '9', '8') AS memStatus, 'A' as memAssort  
            FROM admin
			WHERE id = '$userId' and passwd = '$userPw'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->memStatus == "9") {
			$data = array(
				'userId'         => $row->id,
				'userName'       => $row->name,
				'userAuth'       => "A",
			);

			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "정상";

		} else {
			// 결과를 반환합니다.
			$result_status = "1";
			$result_message = "'사용중지'된 아이디입니다.";
		}

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "아이디 또는 비밀번호 오류입니다.";
	}

	$response = array(
		'userId'         => $row->id,
		'userName'       => $row->name,
		'userAuth'       => "A",
		'result'         => $result_status,
		'message'        => $result_message,
		'data'           => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>