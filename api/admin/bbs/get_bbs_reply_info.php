<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 댓글 정보
	* parameter ==> idx: 글 idx
	* parameter ==> memId: 조회자ID
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, memId, memName, content, date_format(wdate, '%Y-%m-%d') as wdate 
	        FROM bbs 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$data = array(
			'idx'         => $row->idx,
			'memId'       => $row->memId,
			'memName'     => $row->memName,
			'content'     => $row->content,
			'wdate'       => $row->wdate
		);

		// 성공 결과를 반환합니다.
		$result_ok = "0";

    } else {
		$result_ok = "1";
	}

	// 사용자 정보
    $sql = "SELECT authAssort 
			FROM ( SELECT id, if(auth = 'M', 'A', 'P') AS authAssort FROM admin
                   union 
                   SELECT memId AS id, 'M' as authAssort FROM member
				 ) m 
			WHERE id = '$memId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$authAssort = $row->authAssort;

	// 사용자 권한
	$userAuth = array();
    $sql = "SELECT title, thumbYn, replyYn, authAdmin, authPartner, authMember, authNoLogin FROM bbs_manager WHERE bbsCode = '$bbsCode'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	if ($authAssort == "A") $arrAuth = explode(",", $row->authAdmin);
    else if ($authAssort == "P") $arrAuth = explode(",", $row->authPartner);
	else if ($authAssort == "M") $arrAuth = explode(",", $row->authNoLogin);
	else $arrAuth = explode(",", $row->authNoLogin);

	if ($arrAuth[0] == "Y") $authWrite = true;
	else $authWrite = false;

	if ($arrAuth[1] == "Y") $authView = true;
	else $authView = false;

	if ($arrAuth[2] == "Y") $authDelete = true;
	else $authDelete = false;

	if ($arrAuth[3] == "Y") $authReply = true;
	else $authReply = false;

	$userAuth = array(
		'authWrite'  => $authWrite,
		'authView'   => $authView,
		'authDelete' => $authDelete,
		'authReply'  => $authReply,
	);

	$response = array(
		'result'    => $result_ok,
		'userAuth'  => $userAuth,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>