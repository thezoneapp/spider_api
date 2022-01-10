<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 계약완료여부 확인
	* parameter ==> memId: 회원 아이디
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};

    $sql = "SELECT contractStatus FROM member WHERE memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->contractStatus != "9") {
			$result_status = "1";
			$result_message = "계약이 완료되지 않습니다.\n다시 진행해주세요.";

		} else {
			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "계약이 완료되었습니다.\nCMS를 진행해주세요.";
		}

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "계약이 완료되지 않습니다.\n다시 진행해주세요.";
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>