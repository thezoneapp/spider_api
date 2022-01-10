<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 약관 추가/수정
	* parameter ==> mode:      insert(추가), update(수정)
	* parameter ==> idx:       수정할 레코드 id
	* parameter ==> code:      약관코드
	* parameter ==> subject:   제목
	* parameter ==> content:   약관내용
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$mode    = $input_data->{'mode'};
	$idx     = $input_data->{'idx'};
	$code    = $input_data->{'code'};
	$subject = $input_data->{'subject'};
    $content = $input_data->{'content'};

	$content = str_replace("'", "＇", $content);

	if ($mode == "insert") {
		// 같은 코드가 있나 체크
		$sql = "SELECT code FROM setting WHERE code = '$code'";
		$result = $connect->query($sql);

		if ($result->num_rows == 0) {
			$assort = "T";
			$sql = "INSERT INTO setting (assort, code, subject, content)
							     VALUES ('$assort', '$code', '$subject', '$content')";
			$result = $connect->query($sql);

			// 성공 결과를 반환합니다.
			$result = "0";
			$result_message = "등록하였습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result = "1";
			$result_message = "이미 존재하는 코드입니다..";
		}

	} else {
		$sql = "UPDATE setting SET code    = '$code', 
								   subject = '$subject', 
								   content = '$content'
				WHERE idx = '$idx'";
		$result = $connect->query($sql);

		// 성공 결과를 반환합니다.
		$result = "0";
		$result_message = "변경하였습니다.";
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