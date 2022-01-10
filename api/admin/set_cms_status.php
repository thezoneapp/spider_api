<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	// *********************************************************************************************************************************
	// *                                                   CMS 상태 확인                                                             *
	// *********************************************************************************************************************************
	/*
	* parameter
	*	memId: 회원 아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$adminId    = $input_data->{'adminId'};
	$memId      = $input_data->{'memId'};

	//$memId = "27233377";

	// 관리자 정보
	$sql = "SELECT name FROM admin WHERE id = '$adminId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$adminName = $row->name;

	// 회원의 CMS 등록 여부 체크
	$sql = "SELECT c.memId, c.paymentKind, m.cmsStatus 
	        FROM cms c 
			     inner join member m on c.memId = m.memId 
	        WHERE c.memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$paymentKind = $row->paymentKind;
		$cmsStatus = $row->cmsStatus;
		$result = "0";

	} else {
		$result = "1";
		$result_message = "CMS에 등록되어 있지 않는 회원니다.";
	}

	// 테스트계정 정보
	//$result = "0";
	//$memId = "27233377";
	//$url = "https://api.efnc.co.kr:1443/v1/members/$memId";
	//$managerId = "sdsitest";
	//$CUST_ID   = "sdsitest";
	//$SW_KEY = "4LjFflzr6z4YSknp";
	//$CUST_KEY = "BT2z4D5DUm7cE5tl";

	// API 전송
	if ($result == "0") {
		$url = "https://api.hyosungcms.co.kr/v1/members/$memId";

		// header
		$header = Array(
			"Content-Type: application/json; charset=utf-8", 
			"Authorization: VAN $SW_KEY:$CUST_KEY"
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($ch, CURLOPT_ENCODING , "");
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($response, true);
		//print_r( $response );

		if ($paymentKind == "CARD") { // 신용카드
			if ($response[error] != null) {
				$result = "1";
				$message = $response[error][message];
				$result_message = $response[error][message];

				// 회원 테이블 ==> CMS상태를 '오류발생'으로 변경
				$sql = "UPDATE member SET cmsStatus = '2' WHERE memId = '$memId'";
				$connect->query($sql);

				// CMS 로그등록
				$assort = "3";
				$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
									 VALUES ('$memId', '$assort', '$message', '$adminId', '$adminName', now())";
				$connect->query($sql);

			} else {
				$result = "0";
				$member = $response[member];
				$status = $response[member][status];
				$message = $response[member][status];
				$result_message = $response[member][status];

				if ($cmsStatus != "9" && $status == "신청완료") {
					// 회원 테이블 ==> CMS상태를 '등록완료'로 변경
					$sql = "UPDATE member SET cmsStatus = '9' WHERE memId = '$memId'";
					$connect->query($sql);

					// CMS 로그등록
					$assort = "9";
					$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
										 VALUES ('$memId', '$assort', '$message', '$adminId', '$adminName', now())";
					$connect->query($sql);
				}
			}

		} else { // 자동이체
			if ($response[member][status] == "신청실패") {
				$message = $response[member][result][message];
				$result_message = $response[member][status];

				// 회원 테이블 ==> CMS상태를 '오류발생'으로 변경
				$sql = "UPDATE member SET cmsStatus = '2' WHERE memId = '$memId'";
				$connect->query($sql);

				// CMS 로그등록
				$assort = "3";
				$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
									 VALUES ('$memId', '$assort', '$message', '$adminId', '$adminName', now())";
				$connect->query($sql);

			} else {
				$member = $response[member];
				$status = $response[member][status];
				$message = $response[member][status];
				$result_message = $response[member][status];

				if ($cmsStatus != "9" && $status == "신청완료") {
					// 회원 테이블 ==> CMS상태를 '등록완료'로 변경
					$sql = "UPDATE member SET cmsStatus = '9' WHERE memId = '$memId'";
					$connect->query($sql);

					// CMS 로그등록
					$assort = "9";
					$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
										 VALUES ('$memId', '$assort', '$message', '$adminId', '$adminName', now())";
					$connect->query($sql);
				}
			}
		}
	}

	$response = array(
		'result'   => $result,
		'message'  => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>