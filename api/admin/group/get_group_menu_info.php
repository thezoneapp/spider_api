<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 그룹관리 > 그룹 > 목록 > 상세정보 > 회원구성 > 메뉴설정
	* parameter
		organizeCode: 회원구성코드
	*/
	$input_data   = json_decode(file_get_contents('php://input'));
	$organizeCode = $input_data->{'organizeCode'};
//$organizeCode = 13;
	// 메뉴권한 정보
	$menuOptions = array();
	$sql = "SELECT idx, menuName 
			FROM menu 
			WHERE authAssort = 'M' AND menuStatus = 'Y' AND depthNo = 1 
			ORDER BY sortNo ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$parentIdx = $row[idx];

			$sql = "SELECT menuIdx, subMenu FROM member_menu WHERE organizeCode = '$organizeCode' AND menuIdx = '$parentIdx'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);
				$subMenu = $row2->subMenu;
				$checked = true;

			} else {
				$subMenu = "";
				$checked = false;
			}
			
			// 소메뉴
			$subOptions = array();
			$sql = "SELECT idx, menuName 
					FROM menu 
					WHERE parentIdx = '$parentIdx' AND depthNo = 2 
					ORDER BY sortNo ASC";
			$result3 = $connect->query($sql);

			if ($result3->num_rows > 0) {
				while($row3 = mysqli_fetch_array($result3)) {
					if (strpos($subMenu, $row3[idx]) !== false) $subChecked = true;
					else $subChecked = false;

					$data_info = array(
						'code'    => $row3[idx],
						'name'    => $row3[menuName],
						'checked' => $subChecked,
					);
					array_push($subOptions, $data_info);
				}
			}

			$data_info = array(
				'code'       => $row[idx],
				'name'       => $row[menuName],
				'subOptions' => $subOptions,
				'checked'    => $checked,
			);
			array_push($menuOptions, $data_info);
		}

		$result_status = "0";

	} else {
		$result_status = "1";
	}
//print_r ($menuOptions );
//exit;
	// 최종 결과
	$response = array(
		'result'      => $result_status,
		'menuOptions' => $menuOptions,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>