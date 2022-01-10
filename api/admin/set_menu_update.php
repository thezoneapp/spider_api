<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 메뉴 추가/수정
	* parameter ==> mode:       insert(추가), update(수정)
	* parameter ==> idx:        수정할 레코드 id
	* parameter ==> parentIdx:  parentIdx
	* parameter ==> depthNo:    Depth No
	* parameter ==> menuName:   메뉴명
	* parameter ==> iconName:   아이콘명
	* parameter ==> linkTo:     Link To
	* parameter ==> authAssort: 사용자 구분
	* parameter ==> sortNo:     Sort No
	* parameter ==> menuStatus: 현상태
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$mode       = $data_back->{'mode'};
	$idx        = $data_back->{'idx'};
	$parentIdx  = $data_back->{'parentIdx'};
	$depthNo    = $data_back->{'depthNo'};
	$menuName   = $data_back->{'menuName'};
	$iconName   = $data_back->{'iconName'};
	$linkTo     = $data_back->{'linkTo'};
	$authAssort = $data_back->{'authAssort'};
	$sortNo     = $data_back->{'sortNo'};
	$menuStatus = $data_back->{'menuStatus'};

	$authAssort = $authAssort->{'code'};

	if ($menuStatus == true || $menuStatus == "1") $menuStatus = "Y";
	else $menuStatus = "N";

	if ($mode == "insert") {
		$sql = "INSERT INTO menu (parentIdx, depthNo, menuName, iconName, linkTo, authAssort, sortNo, menuStatus)
						  VALUES ('$parentIdx', '$depthNo', '$menuName', '$iconName', '$linkTo', '$authAssort', '$sortNo', '$menuStatus')";
		$result = $connect->query($sql);

		// 성공 결과를 반환합니다.
		$result = "0";
		$result_message = "등록하였습니다.";


	} else {
		$sql = "UPDATE menu SET parentIdx = '$parentIdx', 
								depthNo = '$depthNo', 
								menuName = '$menuName', 
								iconName = '$iconName', 
								linkTo = '$linkTo', 
								authAssort = '$authAssort', 
								sortNo = '$sortNo', 
								menuStatus = '$menuStatus' 
						WHERE idx = '$idx'";
		$result = $connect->query($sql);

		// 성공 결과를 반환합니다.
		$result = "0";
		$result_message = "변경하였습니다.";
	}

	$response = array(
		'result'  => $result,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>