<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 추가/수정
	* parameter
		mode:        insert(추가), update(수정)
		idx:         수정할 레코드 id
		adminId:     아이디
		adminPw:     비밀번호
		adminName:   이름
		phone:       연락처
		email:       이메일
		groupCode    그룹코드
		menuOptions: 메뉴정보배열
		useYn:       사용여부
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$mode        = $input_data->{'mode'};
	$idx         = $input_data->{'idx'};
	$id          = $input_data->{'adminId'};
	$passwd      = $input_data->{'adminPw'};
    $name        = $input_data->{'adminName'};
    $phone       = $input_data->{'phone'};
	$email       = $input_data->{'email'};
	$groupCode   = $input_data->{'groupCode'};
	$menuOptions = $input_data->{'menuOptions'};
	$useYn       = $input_data->{'useYn'};

	if ($passwd != "") $passwd = aes128encrypt($passwd);
	if ($phone != "") $phone = aes128encrypt($phone);
	if ($email != "") $email = aes128encrypt($email);

	if ($mode == "insert") {
		// 같은 아이디가 있나 체크
		$sql = "SELECT id
				FROM ( select id from admin
					   union 
					   select memId as id from agency 
					 ) m 
				WHERE id = '$id'";
		$result = $connect->query($sql);

		if ($result->num_rows == 0) {
			$sql = "INSERT INTO admin (id, passwd, name, phone, email, groupCode, use_yn)
							   VALUES ('$id', '$passwd', '$name', '$phone', '$email', '$groupCode', '$useYn')";
			$connect->query($sql);

			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "등록하였습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "이미 존재하는 아이디입니다..";
		}

	} else {
		$sql = "UPDATE admin SET id = '$id', 
								 passwd = '$passwd', 
								 name = '$name', 
								 phone = '$phone', 
								 email = '$email', 
								 groupCode = '$groupCode', 
								 use_yn = '$useYn' 
				WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "변경하였습니다.";
	}

	// 메뉴권한 변경
	$sql = "UPDATE admin_menu SET updateCheck = 'Y' WHERE adminId = '$id'";
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
				$sql = "SELECT idx FROM admin_menu WHERE adminId = '$id' and menuIdx = '$menuIdx'";
				$result = $connect->query($sql);

				if ($result->num_rows == 0) {
					$sql = "INSERT INTO admin_menu (adminId, menuIdx, subMenu, updateCheck)
											VALUES ('$id', '$menuIdx', '$subMenu', 'N')";
					$connect->query($sql);

				} else {
					$sql = "UPDATE admin_menu SET subMenu = '$subMenu',
									              updateCheck = 'N' 
								WHERE adminId = '$id' and menuIdx = '$menuIdx'";
					$connect->query($sql);
				}
			}
		}
	}

	$sql = "DELETE FROM admin_menu WHERE adminId = '$id' and updateCheck = 'Y'";
	$connect->query($sql);

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>