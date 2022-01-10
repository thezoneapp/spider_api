<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 메뉴 목록
	* parameter ==> authAssort: 관리자구분(A: admin, M: md, S: 온라인구독)
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$userId     = $input_data->{'userId'};

    $sql = "SELECT id, name, auth  
			FROM ( SELECT id, name, auth 
                   FROM admin
                   union 
                   SELECT memId AS id, memName AS name, memAssort AS auth 
                   FROM member
				 ) m 
			WHERE id = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$authAssort = $row->auth;
	}

	// 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, menuName, linkTo, iconName 
            FROM ( select @a:=@a+1 no, idx, menuName, linkTo, iconName, sortNo 
		           from menu, (select @a:= 0) AS a 
		           where depthNo = 1 and menuStatus = 'Y' and authAssort = '$authAssort' 
		         ) m 
            ORDER BY sortNo ASC";
	$result = $connect->query($sql);
	$no2 = $result->num_rows;

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$no  = $row[no];
			$idx = $row[idx];
			$data2 = array();

			// 하위 메뉴 검색 
			$sql = "SELECT menuName, linkTo, iconName 
					FROM menu 
					WHERE menuStatus = 'Y' and depthNo = 2 and parentIdx = '$idx' 
					ORDER BY sortNo ASC";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					++$no2;
					$data_info2 = array(
						'no'       => (string) $no2,
						'menuName' => $row2[menuName],
						'linkTo'   => $row2[linkTo],
						'iconName' => $row2[iconName]
					);

					array_push($data2, $data_info2);
				}
			}

			$data_info = array(
				'no'       => (string) $row[no],
				'menuName' => $row[menuName],
				'linkTo'   => $row[linkTo],
				'iconName' => $row[iconName],
				'children' => $data2
			);

			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
	}

	$response = array(
		'result' => $result,
        'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>