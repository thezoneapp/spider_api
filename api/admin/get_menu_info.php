<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 메뉴 정보
	* parameter ==> idx: 메뉴 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx        = $input_data->{'idx'};

    $sql = "SELECT idx, depthNo, parentIdx, menuName, iconName, linkTo, authAssort, sortNo, menuStatus FROM menu WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$authName = selected_object($row->authAssort, $arrAuthAssort);

		if ($row->linkTo == null) $row->linkTo = "";

		if ($row->menuStatus == "Y") $row->menuStatus = true;
		else $row->menuStatus = false;

		$auth_info = array(
			'code'  => $row->authAssort,
			'name ' => $authName
		);

		$data = array(
			'idx'         => $row->idx,
			'depthNo'     => $row->depthNo,
			'parentIdx'   => $row->parentIdx,
			'menuName'    => $row->menuName,
			'iconName'    => $row->iconName,
			'linkTo'      => $row->linkTo,
			'authAssort'  => $row->authAssort,
			'authName'    => $authName,
			'authOptions' => $arrAuthAssort,
			'sortNo'      => $row->sortNo,
			'menuStatus'  => $row->menuStatus
		);

		// 업데이트모드로 결과를 반환합니다.
		$result = "0";

    } else {
		$data = array(
			'idx'         => '',
			'depthNo'     => '',
			'parentIdx'   => '',
			'menuName'    => '',
			'iconName'    => '',
			'linkTo'      => '',
			'authAssort'  => '',
			'authName'    => '',
			'authOptions' => $arrAuthAssort,
			'sortNo'      => '',
			'menuStatus'  => false
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