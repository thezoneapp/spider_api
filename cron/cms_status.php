<?
    /***********************************************************************************************************************
	* CMS 신청상태 자동 업데이트
	***********************************************************************************************************************/

	$db_host = "localhost";
	$db_user = "spiderfla";
	$db_pass = "dlfvkf#$12";
	$db_name = "spiderfla";

	$connect = @mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die("Failed to connect to MySQL: " . mysqli_error()); 

	/**************************** 승인 처리 **********************************************************/
	$managerId = "nbbang18";
	$CUST_ID   = "nbbang18";
	$SW_KEY    = "sldcgtgYKYQ03R2W";
	$CUST_KEY  = "7tPKQGphGwFLD5TJ";

	// 효성CMS 승인상태를 변경한다.
	$adminId = "auto";
	$adminName = "자동";
	$sql = "SELECT c.memId, c.paymentKind, m.cmsId 
            FROM cms c
                 INNER JOIN member m ON c.memId = m.memId 
            WHERE m.agreeStatus = '9' and (m.cmsStatus = '0' or m.cmsStatus = '1') and c.wdate >= date_sub(now(),INTERVAL 31 DAY) 
			ORDER BY c.idx ASC
			Limit 100";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$memId       = $row[memId];
			$cmsId       = $row[cmsId];
			$paymentKind = $row[paymentKind];

			// API 전송
			$url = "https://api.hyosungcms.co.kr/v1/members/$cmsId";

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
					$member = $response[member];
					$status = $response[member][status];
					$message = $response[member][status];
					$result_message = $response[member][status];

					if ($cmsStatus != "9" && $status == "신청완료") {
						// 회원 테이블 ==> CMS상태를 '등록완료'로 변경
						$sql = "UPDATE member SET cmsStatus = '9' WHERE memId = '$memId'";
						$connect->query($sql);

						// CMS변경신청일자
						$sql = "UPDATE cms SET changeDate = '" . date("Y-m-d") . "' WHERE memId = '$memId'";
						$connect->query($sql);

						// CMS 로그등록
						$assort = "9";
						$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
											 VALUES ('$memId', '$assort', '$message', '$adminId', '$adminName', now())";
											 echo $sql;
						$connect->query($sql);
					}
				}

			} else { // 자동이체
				if ($response[member][status] == "신청실패") {
					$message = $response[member][result][message];

					// 회원 테이블 ==> CMS상태를 '오류발생'으로 변경
					$sql = "UPDATE member SET cmsStatus = '2', memStatus = '2' WHERE memId = '$memId'";
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

						// CMS변경신청일자
						$sql = "UPDATE cms SET changeDate = '" . date("Y-m-d") . "' WHERE memId = '$memId'";
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

		// db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
		@mysqli_close($connect);

		// 재호출한다.

	} else {
		// db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
		@mysqli_close($connect);

		exit;
	}
?>