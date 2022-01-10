<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/cms.php";

	/*
	* 내정보 > CMS햐지
	/*
	* parameter
		userId: 회원 아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId = $input_data->{'userId'};

	// 회원의 CMS 등록 여부 체크
	$sql = "SELECT c.memId, m.cmsId 
	        FROM cms c
			     inner join member m on c.memId = m.memId 
			WHERE c.memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$cmsId = $row->cmsId;

		// 기존 CMS 등록 정보 삭제 함수
		$response = cmsDelete($cmsId);
		$response = json_decode($response, true);

		$result_status  = $response[result];
		$result_message = $response[message];

		if ($result_status == "0") {
			// 회원정보 > CMS해지로 변경
			$sql = "UPDATE member SET payType = 'C', cmsStatus = '8' WHERE memId = '$userId'";
			$connect->query($sql);

			// CMS정보 > CMS해지일자 변경
			$sql = "UPDATE cms SET closeDate = now() WHERE memId = '$userId'";
			$connect->query($sql);

			// CMS 로그등록
			$assort = "8";
			$message = "해지완료";
			$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
								 VALUES ('$memId', '$assort', '$message', '$adminId', '$adminName', now())";
			$connect->query($sql);
		}

	} else {
		$result_status = "1";
		$result_message = "CMS에 등록되어 있지 않는 회원니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>