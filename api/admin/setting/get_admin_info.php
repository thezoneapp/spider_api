<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 환경설정 > 관리자관리 > 목록 > 상세정보
	* parameter
		idx: 아이디에 해당하는 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx        = $input_data->{'idx'};
//$idx = 1;
    $sql = "SELECT idx, groupCode, id, passwd, name, phone, email, use_yn FROM admin WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$authName = selected_object($row->auth, $arrAdminAuth);

		if ($row->phone == null) $row->phone = "";
		if ($row->email == null) $row->email = "";

		if ($row->passwd != "") $row->passwd = aes_decode($row->passwd);
		if ($row->phone != "") $row->phone = aes_decode($row->phone);
		if ($row->email != "") $row->email = aes_decode($row->email);

		$idx       = $row->idx;
		$groupCode = $row->groupCode;
		$adminId   = $row->id;
		$adminPw   = $row->passwd;
		$adminName = $row->name;
		$phone     = $row->phone;
		$email     = $row->email;
		$useYn     = $row->use_yn;

    } else {
		$idx       = "0";
		$groupCode = "";
		$adminId   = "";
		$adminPw   = "";
		$adminName = "";
		$phone     = "";
		$email     = "";
		$useYn     = "";
	}

	$data = array(
		'idx'       => $idx,
		'groupCode' => $groupCode,
		'adminId'   => $adminId,
		'adminPw'   => $adminPw,
		'adminName' => $adminName,
		'phone'     => $phone,
		'email'     => $email,
		'useYn'     => $useYn,
	);

	// 메뉴권한 정보
	$menuOptions = array();
	$sql = "SELECT idx, menuName 
			FROM menu 
			WHERE authAssort = 'A' AND menuStatus = 'Y' AND depthNo = 1 
			ORDER BY sortNo ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$parentIdx = $row[idx];

			$sql = "SELECT menuIdx, subMenu FROM admin_menu WHERE adminId = '$adminId' AND menuIdx = '$parentIdx'";
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
	}

	// 그룹 선택 정보
	$groupOptions = array();
	$sql = "SELECT groupCode, groupName FROM group_info WHERE useYn = 'Y'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'code' => $row[groupCode],
				'name' => $row[groupName],
			);
			array_push($groupOptions, $data_info);
		}
	}

	$response = array(
		'groupOptions' => $groupOptions,
		'menuOptions'  => $menuOptions,
		'useYnOptions' => $arrUseYn2,
		'data'         => $data
    );
//print_r($response);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>