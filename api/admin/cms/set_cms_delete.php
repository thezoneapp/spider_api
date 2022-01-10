<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	// *********************************************************************************************************************************
	// *                                                   등록된 CMS 삭제                                                               *
	// *********************************************************************************************************************************
	/*
	* parameter
	*	memId: 회원 아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId   = $input_data->{'memId'};
	$adminId = $input_data->{'adminId'};

	// 관리자 정보
	$sql = "SELECT name FROM admin WHERE id = '$adminId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$adminName = $row->name;

	// 회원의 CMS 등록 여부 체크
	$sql = "SELECT c.memId, m.cmsId 
	        FROM cms c
			     inner join member m on c.memId = m.memId 
			WHERE c.memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$cmsId = $row->cmsId;
		$result_status = "0";

	} else {
		$result_status = "1";
		$result_message = "CMS에 등록되어 있지 않는 회원니다.";
	}

	// 테스트계정 정보
	//$result_status = "0";
	//$cmsId = "73736742";
	//$managerId = "sdsitest";
	//$CUST_ID   = "sdsitest";
	//$SW_KEY = "4LjFflzr6z4YSknp";
	//$CUST_KEY = "BT2z4D5DUm7cE5tl";

	// API 전송
	if ($result_status == "0") {
		//$url = "https://api.efnc.co.kr:1443/v1/members/$cmsId";
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
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($response, true);
		//print_r( $response );

		if ($response[error] != null) {
			$result_status = "1";
			$message = $response[error][message];
			$result_message = $response[error][message];
		} else {

			//$sql = "DELETE FROM cms WHERE memId = '$memId'";
			//$result = $connect->query($sql);

			// 회원 테이블 ==> CMS상태 '해지완료' 변경
			$sql = "UPDATE member SET cmsStatus = '8' WHERE memId = '$memId'";
			$connect->query($sql);

			$result_status = "0";
			$message = "해지처리";
			$result_message = "해지되었습니다.";
		}

		// CMS 로그등록
		$assort = "9";
		$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
		                     VALUES ('$memId', '$assort', '$message', '$adminId', '$adminName', now())";
		$connect->query($sql);
	}

	$response = array(
		'result'   => $result_status,
		'message'  => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>