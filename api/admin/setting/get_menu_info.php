<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

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
			'sortNo'      => $row->sortNo,
			'menuStatus'  => $row->menuStatus
		);

		// 업데이트모드로 결과를 반환합니다.
		$result_status = "0";

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
			'sortNo'      => '',
			'menuStatus'  => 'Y'
		);

		// 추가모드로 결과를 반환합니다.
		$result_status = "1";
	}

	// ******************************************* 서브 메뉴 정보  *************************************
	$menus = array();
    $sql = "SELECT idx, menuName, iconName, linkTo, sortNo, menuStatus FROM menu WHERE depthNo = 2 and parentIdx = '$idx' ORDER BY sortNo ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$statusName = selected_object($row[menuStatus], $arrUseAssort);
			
			$status_info = array(
				'code' => $row[menuStatus],
				'name' => $statusName,
			);

			$data_info = array(
				'idx'        => $row[idx],
				'menuName'   => $row[menuName],
				'iconName'   => $row[iconName],
				'linkTo'     => $row[linkTo],
				'sortNo'     => $row[sortNo],
				'menuStatus' => $status_info,
			);

			array_push($menus, $data_info);
		}
	}

	$response = array(
		'result'      => $result_status,
		'data'        => $data,
		'menus'       => $menus,
		'authOptions' => $arrAuthAssort,
		'useOptions'  => $arrUseAssort,
		'useOptions2' => $arrUseAssort2,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>