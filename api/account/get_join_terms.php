<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
	/*
	* 회원약관 정보
	*/

	// 실행할 쿼리를 작성합니다.
    $sql = "SELECT content FROM setting WHERE CODE = 'joinTerms'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$data = $row->content;

		// 성공 결과를 반환합니다.
		$result = "0";
		$result_message = "약관정보 호출 성공하였습니다.";

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
		$result_message = "약관정보가 없습니다.";
		$data = "";
	}

	$response = array(
		'result'    => $result,
		'message'   => $result_message,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>