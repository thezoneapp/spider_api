<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 그룹관리 > 그룹 > 목록 > 상세정보 > 회원구성 > 메뉴설정
	* parameter
		organizeCode: 회원구성코드
		menuOptions:  메뉴정보배열
	*/
	$input_data   = json_decode(file_get_contents('php://input'));
	$organizeCode = $input_data->{'organizeCode'};
	$menuOptions  = $input_data->{'menuOptions'};

	// 메뉴권한 변경
	$sql = "UPDATE member_menu SET updateCheck = 'Y' WHERE organizeCode = '$organizeCode'";
	$connect->query($sql);

	if (count($menuOptions) > 0) {
		for($i = 0; count($menuOptions) > $i; $i++) {
			$row = $menuOptions[$i];

			$menuIdx    = $row->code;
			$subOptions = $row->subOptions;
			$checked    = $row->checked;

			$subMenu = "";

			if ($checked == true) {
				if (count($subOptions) > 0) {
					for($n = 0; count($subOptions) > $n; $n++) {
						$row2 = $subOptions[$n];

						if ($row2->checked == true) {
							if ($subMenu != "") $subMenu .= ",";

							$subMenu .= $row2->code;
						}
					}
				}

				// 기존 자료 존재 체크
				$sql = "SELECT idx FROM member_menu WHERE organizeCode = '$organizeCode' and menuIdx = '$menuIdx'";
				$result = $connect->query($sql);

				if ($result->num_rows == 0) {
					$sql = "INSERT INTO member_menu (organizeCode, menuIdx, subMenu, updateCheck)
											 VALUES ('$organizeCode', '$menuIdx', '$subMenu', 'N')";
					$connect->query($sql);

				} else {
					$sql = "UPDATE member_menu SET subMenu = '$subMenu',
									               updateCheck = 'N' 
								WHERE organizeCode = '$organizeCode' and menuIdx = '$menuIdx'";
					$connect->query($sql);
				}
			}
		}
	}

	$sql = "DELETE FROM member_menu WHERE organizeCode = '$organizeCode' and updateCheck = 'Y'";
	$connect->query($sql);

	$response = array(
		'result'  => "0",
		'message' => "변경하였습니다.",
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
