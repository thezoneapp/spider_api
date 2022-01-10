<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../utils/utility.php";

	/*
	* 게시판 정보
	* parameter ==> idx: 메뉴 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx        = $input_data->{'idx'};

	$userAuth = array();
    $sql = "SELECT idx, bbsCode, title, thumbYn, replyYn, authAdmin, authPartner, authMember, authNoLogin FROM bbs_manager WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		// 사용자 권란 파싱
		for ($i = 0; $i < count($arrUserAssort); $i++) {
			$arrObj = new ArrayObject($arrUserAssort[$i]);
			$arrObj->setFlags(ArrayObject::ARRAY_AS_PROPS);

			if ($i == 0) $arrAuth = explode(",", $row->authAdmin);
			else if ($i == 1) $arrAuth = explode(",", $row->authPartner);
			else if ($i == 2) $arrAuth = explode(",", $row->authMember);
			else if ($i == 3) $arrAuth = explode(",", $row->authNoLogin);

			if ($arrAuth[0] == "Y") $authWrite = true;
			else $authWrite = false;

			if ($arrAuth[1] == "Y") $authView = true;
			else $authView = false;

			if ($arrAuth[2] == "Y") $authDelete = true;
			else $authDelete = false;

			if ($arrAuth[3] == "Y") $authReply = true;
			else $authReply = false;

			$user_info = array(
				'no'         => $i,
				'code'       => $arrObj->code,
				'name'       => $arrObj->name,
				'authWrite'  => $authWrite,
				'authView'   => $authView,
				'authDelete' => $authDelete,
				'authReply'  => $authReply,
			);
			array_push($userAuth, $user_info);
		}

		$data = array(
			'idx'          => $row->idx,
			'bbsCode'      => $row->bbsCode,
			'title'        => $row->title,
			'thumbYn'      => $row->thumbYn,
			'thumbOptions' => $arrUseAssort2,
			'replyYn'      => $row->replyYn,
			'replyOptions' => $arrUseAssort2,
			'userAuth'     => $userAuth,
		);

		// 업데이트모드로 결과를 반환합니다.
		$result = "0";

    } else {
		for ($i = 0; $i < count($arrUserAssort); $i++) {
			$arrObj = new ArrayObject($arrUserAssort[$i]);
			$arrObj->setFlags(ArrayObject::ARRAY_AS_PROPS);

			$user_info = array(
				'no'         => $i,
				'code'       => $arrObj->code,
				'name'       => $arrObj->name,
				'authWrite'  => false,
				'authView'   => false,
				'authDelete' => false,
				'authReply'  => false,
			);
			array_push($userAuth, $user_info);
		}

		$data = array(
			'idx'          => '',
			'bbsCode'      => '',
			'title'        => '',
			'thumbYn'      => 'Y',
			'thumbOptions' => $arrUseAssort2,
			'replyYn'      => 'Y',
			'replyOptions' => $arrUseAssort2,
			'userAuth'     => $userAuth,
		);

		// 추가모드로 결과를 반환합니다.
		$result = "1";
	}

	$response = array(
		'result'    => $result,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>