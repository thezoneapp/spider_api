<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../utils/utility.php";

	/*
	* 게시판 추가/수정
	* parameter ==> mode:     insert(추가), update(수정)
	* parameter ==> idx:      수정할 레코드 id
	* parameter ==> bbsCode:  게시판코드
	* parameter ==> title:    게시판명
	* parameter ==> thumbYn:  썸네일사용여부
	* parameter ==> replyYn:  댓글사용여부
	* parameter ==> userAuth: 사용자권한배열
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$mode       = $input_data->{'mode'};
	$idx        = $input_data->{'idx'};
	$bbsCode    = $input_data->{'bbsCode'};
	$title      = $input_data->{'title'};
	$thumbYn    = $input_data->{'thumbYn'};
	$replyYn    = $input_data->{'replyYn'};
	$userAuth   = $input_data->{'userAuth'};

	//$userAuth  = $userAuth->{'code'};
	//error_log ($userAuth, 3, "/home/spiderfla/upload/doc/debug.log");

	// 사용자 권한 파싱
	for ($i = 0; $i < count($userAuth); $i++) {
		$arrObj = new ArrayObject($userAuth[$i]);
		$arrObj->setFlags(ArrayObject::ARRAY_AS_PROPS);

		if ($arrObj->authWrite) $authWrite = "Y";
		else $authWrite = "N";

		if ($arrObj->authView) $authView = "Y";
		else $authView = "N";

		if ($arrObj->authDelete) $authDelete = "Y";
		else $authDelete = "N";

		if ($arrObj->authReply) $authReply = "Y";
		else $authReply = "N";

		// 관리자
		if ($arrObj->code == "A") {
			$authAdmin = $authWrite . "," . $authView . "," . $authDelete . "," . $authReply;

		// 파트너
		} else if ($arrObj->code == "P") {
			$authPartner = $authWrite . "," . $authView . "," . $authDelete . "," . $authReply;

		// 회원
		} else if ($arrObj->code == "M") {
			$authMember = $authWrite . "," . $authView . "," . $authDelete . "," . $authReply;

		// 비회원
		} else if ($arrObj->code == "N") {
			$authNoLogin = $authWrite . "," . $authView . "," . $authDelete . "," . $authReply;
		}
	}

	if ($mode == "insert") {
		$sql = "INSERT INTO bbs_manager (bbsCode, title, thumbYn, replyYn, authAdmin, authPartner, authMember, authNoLogin)
						         VALUES ('$bbsCode', '$title', '$thumbYn', '$replyYn', '$authAdmin', '$authPartner', '$authMember', '$authNoLogin')";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_ok = "0";
		$result_message = "등록하였습니다.";

	} else {
		$sql = "UPDATE bbs_manager SET bbsCode = '$bbsCode', 
									   title = '$title', 
									   thumbYn = '$thumbYn', 
									   replyYn = '$replyYn',
									   authAdmin = '$authAdmin', 
									   authPartner = '$authPartner', 
									   authMember = '$authMember',
									   authNoLogin = '$authNoLogin' 
				WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_ok = "0";
		$result_message = "변경하였습니다.";
	}

	$response = array(
		'result'    => $result_ok,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>