<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
	/*
	* 추천인 체크
	* parameter ==> recommandId: 추천인 아이디
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$recommandId = trim($data_back->{'recommandId'});

	//$recommandId = "a27233377";

	// 실행할 쿼리를 작성합니다.
    $sql = "SELECT memId, memName, memAssort, memStatus 
            FROM member
			WHERE memId = '$recommandId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->memAssort == "M" && $row->memStatus == "9") {
			// 결과를 반환합니다.
			$result_status = "0";
			$result_message = "'" . $row->memName . "'님 입니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "'" . $row->memName . "'님은 추천할 수 없는 상태입니다.";
		}

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "존재하지 않는 '추천인'입니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
