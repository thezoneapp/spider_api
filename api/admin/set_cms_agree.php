<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	// *********************************************************************************************************************************
	// *                                                   CMS 동의서 전송                                                               *
	// *********************************************************************************************************************************
	/*
	* parameter
	*	memId:  회원 아이디
	*	files:  동의서 파일명
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$adminId  = $input_data->{'adminId'};
	$memId    = $input_data->{'memId'};
	$agreeDoc = $input_data->{'agreeDoc'};

	//$adminId = "admin";
	//$memId = "22553310";
	//$agreeDoc = "20201018_697.pdf";

	$path = "/home/spiderfla/upload/doc/";
	$agreeDoc = $path . $agreeDoc;

	//error_log ($agreeDoc, 3, "/home/spiderfla/upload/doc/debug.log");

	// 관리자 정보
	$sql = "SELECT name FROM admin WHERE id = '$adminId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$adminName = $row->name;

	// 회원의 CMS 등록 여부 체크
	$sql = "SELECT memId FROM cms WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$result = "0";

	} else {
		$result = "1";
		$result_message = "CMS에 등록되어 있지 않는 회원니다.";
	}

	// 테스트계정 정보
	//$result = "0";
	//$memId = "27233377";
	//$agreeDoc = "ace_24222.jpg";
	//$managerId = "sdsitest";
	//$CUST_ID   = "sdsitest";
	//$SW_KEY = "4LjFflzr6z4YSknp";
	//$CUST_KEY = "BT2z4D5DUm7cE5tl";

	// API 전송
	if ($result == "0") {
		// header
		$header = Array(
			"Content-Type: multipart/form-data;", 
			"Authorization: VAN $SW_KEY:$CUST_KEY"
		);

		$data = array(
			'memberId' => $memId, 
			'file'     => new CURLFILE($agreeDoc)
		);

		$curl = curl_init();

		// 테스트 url
		// CURLOPT_URL => "https://add-test.hyosungcms.co.kr/v1/custs/" . $CUST_ID . "/agreements",
		// real url
		//CURLOPT_URL => "https://add.hyosungcms.co.kr/v1/custs/" . $CUST_ID . "/agreements",

		curl_setopt_array($curl, array(
			//CURLOPT_URL => "https://add-test.hyosungcms.co.kr/v1/custs/" . $CUST_ID . "/agreements",
			CURLOPT_URL => "https://add.hyosungcms.co.kr/v1/custs/" . $CUST_ID . "/agreements",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_HTTPHEADER => $header,
		));

		$response = curl_exec($curl);

		curl_close($curl);

		$response = json_decode($response, true);
		//print_r( $response );
		//exit;

		if ($response[agreementFile][result][code] == "Y") {
			//$sql = "DELETE FROM cms WHERE memId = '$memId'";
			//$result = $connect->query($sql);

			// 회원 테이블 ==> 동의상태를 '동의완료'로 변경
			$sql = "UPDATE member SET agreeStatus = '9' WHERE memId = '$memId'";
			$result = $connect->query($sql);

			$result = "0";
			$message = "정상등록";
			$result_message = "동의서가 등록되었습니다.";

		} else {
			$result = "1";
			$message = "오류 발생";
			$result_message = "오류가 발생하였습니다.";
		}

		// CMS 로그등록
		$assort = "2";
		$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
		                     VALUES ('$memId', '$assort', '$message', '$adminId', '$adminName', now())";
		$connect->query($sql);
	}

	$response = array(
		'result'   => $result,
		'message'  => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>