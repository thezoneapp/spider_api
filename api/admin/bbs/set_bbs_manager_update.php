<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 게시판 추가/수정
	* parameter ==> mode:     insert(추가), update(수정)
	* parameter ==> idx:      수정할 레코드 id
	* parameter ==> bbsCode:  게시판코드
	* parameter ==> title:    게시판명
	* parameter ==> thumbYn:  썸네일사용여부
	* parameter ==> replyYn:  댓글사용여부
	* parameter ==> userAuth: 사용자권한배열
	* parameter ==> assorts:  분류명배열
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$mode       = $input_data->{'mode'};
	$idx        = $input_data->{'idx'};
	$bbsCode    = $input_data->{'bbsCode'};
	$title      = $input_data->{'title'};
	$thumbYn    = $input_data->{'thumbYn'};
	$replyYn    = $input_data->{'replyYn'};
	$userAuth   = $input_data->{'userAuth'};
	$assorts    = $input_data->{'assorts'};

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
			$authNone = $authWrite . "," . $authView . "," . $authDelete . "," . $authReply;
		}
	}

	if ($mode == "insert") {
		$sql = "INSERT INTO bbs_manager (bbsCode, title, thumbYn, replyYn, authAdmin, authPartner, authMember, authNone)
						         VALUES ('$bbsCode', '$title', '$thumbYn', '$replyYn', '$authAdmin', '$authPartner', '$authMember', '$authNone')";
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
									   authNone = '$authNone' 
				WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_ok = "0";
		$result_message = "변경하였습니다.";
	}

	// ******************************** 대분류 정보 *********************************************
	$sql = "UPDATE bbs_assort SET updateCheck = 'Y' WHERE bbsCode = '$bbsCode' and depthNo = '1'";
	$connect->query($sql);

	for ($i = 0; count($assorts) > $i; $i++) {
		$assort = $assorts[$i];

		$idx          = $assort->idx;
		$assortCode   = $assort->assortCode;
		$assortName   = $assort->assortName;
		$smallAssorts = $assort->smallAssorts;

		$parentCode = $assortCode;

		$sql = "SELECT idx FROM bbs_assort WHERE bbsCode = '$bbsCode' and assortCode = '$assortCode'";
		$result = $connect->query($sql);
		$total = $result->num_rows;

		if ($total == 0) {
			$sql = "SELECT idx FROM bbs_assort WHERE bbsCode = '$bbsCode' ORDER BY idx desc LIMIT 1";
			$result = $connect->query($sql);

			if ($result->num_rows > 0) {
				$row = mysqli_fetch_object($result);
				$idx = $row->idx + 1;

			} else {
				$idx = 1;
			}

			$assortCode = $bbsCode . "-" . $idx;
			$sql = "INSERT INTO bbs_assort (bbsCode, parentCode, assortCode, assortName, depthNo, updateCheck)
								    VALUES ('$bbsCode', '$assortCode', '$assortCode', '$assortName', '1', 'N')";
			$connect->query($sql);

		} else {
			$sql = "UPDATE bbs_assort SET assortName = '$assortName', 
										  updateCheck = 'N' 
							WHERE idx = '$idx'";
			$connect->query($sql);
		}

		// ******************************** 소분류 정보 *********************************************
		$sql = "UPDATE bbs_assort SET updateCheck = 'Y' WHERE parentCode = '$parentCode' and depthNo = '2'";
		$connect->query($sql);

		for ($n = 0; count($smallAssorts) > $n; $n++) {
			$smallAssort = $smallAssorts[$n];

			$idx        = $smallAssort->idx;
			$assortCode = $smallAssort->assortCode;
			$assortName = $smallAssort->assortName;

			$sql = "SELECT idx FROM bbs_assort WHERE parentCode = '$parentCode' and depthNo = '2' and assortCode = '$assortCode'";
			$result = $connect->query($sql);

			if ($result->num_rows == 0) {
				$sql = "SELECT idx FROM bbs_assort WHERE bbsCode = '$bbsCode' ORDER BY idx desc LIMIT 1";
				$result = $connect->query($sql);

				if ($result->num_rows > 0) {
					$row = mysqli_fetch_object($result);
					$idx = $row->idx + 1;

				} else {
					$idx = 1;
				}

				$assortCode = $bbsCode . "-" . $idx;
				$sql = "INSERT INTO bbs_assort (bbsCode, parentCode, assortCode, assortName, depthNo, updateCheck)
										VALUES ('$bbsCode', '$parentCode', '$assortCode', '$assortName', '2', 'N')";
				$connect->query($sql);

			} else {
				$sql = "UPDATE bbs_assort SET assortName = '$assortName', updateCheck = 'N' WHERE idx = '$idx'";
				$connect->query($sql);
			}
		}

		$sql = "DELETE FROM bbs_assort WHERE parentCode = '$parentCode' and depthNo = '2' and updateCheck = 'Y'";
		$connect->query($sql);
	}

	$sql = "DELETE FROM bbs_assort WHERE bbsCode = '$bbsCode' and depthNo = '1' and updateCheck = 'Y'";
	$connect->query($sql);

	$response = array(
		'result'    => $result_ok,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>