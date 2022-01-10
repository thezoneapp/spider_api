<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 메뉴 추가/수정
	* parameter ==> mode:       insert(추가), update(수정)
	* parameter ==> idx:        수정할 레코드 id
	* parameter ==> menuName:   메뉴명
	* parameter ==> iconName:   아이콘명
	* parameter ==> linkTo:     Link To
	* parameter ==> authAssort: 사용자 구분
	* parameter ==> sortNo:     Sort No
	* parameter ==> menuStatus: 현상태
	* parameter ==> menus:      서브메뉴 배열
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$mode       = $data_back->{'mode'};
	$parentIdx  = $data_back->{'idx'};
	$menuName   = $data_back->{'menuName'};
	$iconName   = $data_back->{'iconName'};
	$linkTo     = $data_back->{'linkTo'};
	$authAssort = $data_back->{'authAssort'};
	$sortNo     = $data_back->{'sortNo'};
	$menuStatus = $data_back->{'menuStatus'};
	$arrMemu    = $data_back->{'menus'};

	$authAssort = $authAssort->{'code'};

	if ($mode == "insert") {
		$depthNo = 1;
		$sql = "INSERT INTO menu (depthNo, menuName, iconName, linkTo, authAssort, sortNo, menuStatus)
						  VALUES ('$depthNo', '$menuName', '$iconName', '$linkTo', '$authAssort', '$sortNo', '$menuStatus')";
		$connect->query($sql);

		$sql = "SELECT idx FROM menu WHERE depthNo = 1 and menuName = '$menuName' ORDER BY idx DESC LIMIT 1";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);
			$parentIdx = $row->idx;
		}

		$sql = "UPDATE menu SET parentIdx = '$parentIdx' WHERE idx = '$parentIdx'";
		$result = $connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "등록하였습니다.";

	} else {
		$sql = "UPDATE menu SET menuName = '$menuName', 
								iconName = '$iconName', 
								linkTo = '$linkTo', 
								authAssort = '$authAssort', 
								sortNo = '$sortNo', 
								menuStatus = '$menuStatus' 
						WHERE idx = '$parentIdx'";
		$result = $connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "변경하였습니다.";
	}

	// ******************************** 서브 메뉴 *********************************************
	$sql = "UPDATE menu SET updateCheck = 'Y' WHERE depthNo = 2 and parentIdx = '$parentIdx'";
	$connect->query($sql);

	for ($i = 0; count($arrMemu) > $i; $i++) {
		$menu = $arrMemu[$i];

		$idx        = $menu->idx;
		$menuName   = $menu->menuName;
		$iconName   = $menu->iconName;
		$linkTo     = $menu->linkTo;
		$sortNo     = $menu->sortNo;
		$menuStatus = $menu->menuStatus;

		$menuStatus = $menuStatus->{'code'};

		$sql = "SELECT idx FROM menu WHERE idx = '$idx'";
		$result = $connect->query($sql);

		if ($result->num_rows == 0) {
			$depthNo = 2;
			$menuStatus = "Y";
			$sql = "INSERT INTO menu (parentIdx, depthNo, menuName, iconName, linkTo, authAssort, sortNo, menuStatus)
							  VALUES ('$parentIdx', '$depthNo', '$menuName', '$iconName', '$linkTo', '$authAssort', '$sortNo', '$menuStatus')";
			$connect->query($sql);

		} else {
			$sql = "UPDATE menu SET menuName = '$menuName', 
									iconName = '$iconName', 
									linkTo = '$linkTo', 
									authAssort = '$authAssort', 
									sortNo = '$sortNo', 
									menuStatus = '$menuStatus',
									updateCheck = 'N' 
							WHERE idx = '$idx'";
			$connect->query($sql);
		}

		$sql2 = $sql;
	}

	$sql = "DELETE FROM menu WHERE depthNo = 2 and parentIdx = '$parentIdx' and updateCheck = 'Y'";
	$connect->query($sql);

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>