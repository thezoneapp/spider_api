<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/kakaoTalk.php";

	/*
	* 회원 > 소비자관리 > 링크전송
	* parameter
		userId:      사용자ID
		custId:      소비자ID
		assort:      링크구분(H: 휴대폰, R: 렌탈, I: 다이렉트보험)
		linkMessage: 전송할 메세지
		registCheck: 자주 사용하는 메세지 등록여부(Y: 등록, N: 미등록)
		memId:       사용자ID(atob로 암호화된 정보)
		marginRate:  휴대폰 마진율(atob로 암호화된 정보)
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$userId      = $input_data->{'userId'};
	$custId      = $input_data->{'custId'};
	$assort      = $input_data->{'assort'};
	$linkMessage = $input_data->{'linkMessage'};
	$registCheck = $input_data->{'registCheck'};
	$memId       = $input_data->{'memId'};
	$marginRate  = $input_data->{'marginRate'};

	//$userId    = "a27233377";
	//$custId      = "c82302082";
	//$assort = "H";
	//$linkMessage = "테스트메세지입니다.";
	//$registCheck = "Y";
	//$memId = "Y";
	//$marginRate = "Y";

	// 자주 쓰는 메세지 등록
	if ($registCheck == "Y") {
		$sql = "INSERT INTO link_message (memId, assort, message) 
		                          VALUES ('$userId', '$assort', '$linkMessage')";
		$connect->query($sql);
	}

	// 회원정보
    $sql = "SELECT memName FROM member WHERE memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$memName = $row->memName;

		// 소비자정보
		$sql = "SELECT custName, hpNo FROM customer WHERE custId = '$custId'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			
			if ($row2->hpNo != "") $row2->hpNo = aes_decode($row2->hpNo);

			$custName = $row2->custName;
			$custHpNo = $row2->hpNo;

			// 알림톡 전송
			if ($assort == "H") {
				$custHpNo = preg_replace('/\D+/', '', $custHpNo);
				$receiptInfo = array(
					"memId"       => $memId,
					"memName"     => $memName,
					"custName"    => $custName,
					"fee"         => $marginRate,
					"receiptHpNo" => $custHpNo,
				);
				//sendTalk("L_HP_01_01", $receiptInfo);
			}

			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "링크를 전송하였습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "소비자정보가 존재하지 않습니다.";
		}

	} else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "사업자정보가 존재하지 않습니다.";
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>