<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 회원 > 소비자관리 > 링크 목록
	* parameter
		userId:      사용자ID
		assort:      링크구분(H: 휴대폰, R: 렌탈, I: 다이렉트보험)
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$userId      = $input_data->{'userId'};
	$assort      = $input_data->{'assort'};

	//$userId    = "a27233377";
	//$assort = "H";

	$userId_sql = "and memId = '$userId' ";
	$assort_sql = "and assort = '$assort' ";

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, message 
	        FROM ( select @a:=@a+1 no, idx, message 
		           from link_message, (select @a:= 0) AS a 
		           where idx > 0 $userId_sql $assort_sql 
		         ) m 
			ORDER BY no DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'no'      => $row[no],
				'idx'     => $row[idx],
				'message' => $row[message],
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	$response = array(
		'result' => $result_status,
		'data'   => $data,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
