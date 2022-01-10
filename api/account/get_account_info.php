<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 회원 정보
	* parameter ==> userId: 회원 아이디
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};

    $sql = "SELECT id, name, auth  
			FROM ( SELECT id, name, 'A' as auth 
                   FROM admin
                   union 
                   SELECT memId AS id, memName AS name, memAssort AS auth 
                   FROM member
				 ) m 
			WHERE id = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$accountName = $row->name . "(" . $row->id . ")";

		if ($row->auth == "A") $accountAuth = "관리자";
		else if ($row->auth == "M") $accountAuth = "플랫폼MD";
		else if ($row->auth == "S") $accountAuth = "온라인구독플랫폼";
		else $accountAuth = "";

		$data = array(
			'accountName' => $accountName,
			'accountAuth' => $accountAuth,
		);

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result = "1";
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