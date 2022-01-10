<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 메뉴 목록
	* parameter
		userId: 사용자ID
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$userId     = $input_data->{'userId'};

	// 사용자 정보
    $sql = "SELECT id, name, auth, organizeCode 
			FROM ( SELECT id, name, 'A' AS auth, 0 as organizeCode 
                   FROM admin
                   union 
                   SELECT memId AS id, memName AS name, memAssort AS auth, organizeCode 
                   FROM member
				 ) m 
			WHERE id = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$auth         = $row->auth;
		$organizeCode = $row->organizeCode;

		$data = array();

		// 관리자 메뉴
		if ($auth == "A") {
			$no = 1;
			$sql = "SELECT am.menuIdx, m.menuName, m.linkTo, m.iconName, am.subMenu 
					FROM admin_menu am 
						 INNER JOIN menu m ON am.menuIdx = m.idx 
					WHERE am.adminId = '$userId' 
					ORDER BY m.sortNo ASC";
			$result2 = $connect->query($sql);
			$no2 = $result2->num_rows;

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					$menuIdx  = $row2[menuIdx];
					$menuName = $row2[menuName];
					$linkTo   = $row2[linkTo];
					$iconName = $row2[iconName];
					$subMenu  = $row2[subMenu];

					// 하위 메뉴 검색 
					$data2 = array();
					$sql = "SELECT menuName, linkTo, iconName 
							FROM menu 
							WHERE menuStatus = 'Y' and depthNo = 2 and idx in ($subMenu) 
							ORDER BY sortNo ASC";
					$result3 = $connect->query($sql);

					if ($result3->num_rows > 0) {
						while($row3 = mysqli_fetch_array($result3)) {
							++$no2;
							$data_info2 = array(
								'no'       => (string) $no2,
								'menuName' => $row3[menuName],
								'linkTo'   => $row3[linkTo],
								'iconName' => $row3[iconName]
							);

							array_push($data2, $data_info2);
						}
					}

					$data_info = array(
						'no'       => (string) $no,
						'menuName' => $menuName,
						'linkTo'   => $linkTo,
						'iconName' => $iconName,
						'children' => $data2
					);

					array_push($data, $data_info);
					$no++;
				}
			}

			// 성공 결과를 반환합니다.
			$result_status = "0";

		// 회원 메뉴
		} else {
			$no = 1;
			$sql = "SELECT mm.menuIdx, m.menuName, m.linkTo, m.iconName, mm.subMenu 
					FROM member_menu mm 
						 INNER JOIN menu m ON mm.menuIdx = m.idx 
					WHERE mm.organizeCode = '$organizeCode' 
					ORDER BY m.sortNo ASC";
			$result2 = $connect->query($sql);
			$no2 = $result2->num_rows;

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					$menuIdx  = $row2[menuIdx];
					$menuName = $row2[menuName];
					$linkTo   = $row2[linkTo];
					$iconName = $row2[iconName];
					$subMenu  = $row2[subMenu];

					// 하위 메뉴 검색 
					$data2 = array();
					$sql = "SELECT menuName, linkTo, iconName 
							FROM menu 
							WHERE menuStatus = 'Y' and depthNo = 2 and idx in ($subMenu) 
							ORDER BY sortNo ASC";
					$result3 = $connect->query($sql);

					if ($result3->num_rows > 0) {
						while($row3 = mysqli_fetch_array($result3)) {
							++$no2;
							$data_info2 = array(
								'no'       => (string) $no2,
								'menuName' => $row3[menuName],
								'linkTo'   => $row3[linkTo],
								'iconName' => $row3[iconName]
							);

							array_push($data2, $data_info2);
						}
					}

					$data_info = array(
						'no'       => (string) $no,
						'menuName' => $menuName,
						'linkTo'   => $linkTo,
						'iconName' => $iconName,
						'children' => $data2
					);

					array_push($data, $data_info);
					$no++;
				}
			}

			// 성공 결과를 반환합니다.
			$result_status = "0";
		}

	} else {
		$result_status = "1";
		$result_message = "사용자가 존재하지 않습니다.";
	}

	// 최종결과
	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
        'data'    => $data
    );
//print_r($response);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>