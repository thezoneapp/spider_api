<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/cms.php";

	/*
	* 회원 > 내정보 > CMS등록정보
	* parameter ==> userId: 회원아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId     = $input_data->{'userId'};
	
	//$userId = "a96004445";

	// 회원정보 검색
	$sql = "SELECT c.paymentKind, m.cmsId, m.clearStatus 
	        FROM cms c 
			     inner join member m on c.memId = m.memId 
	        WHERE c.memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$clearStatus = $row->clearStatus;
		$cmsId       = $row->cmsId;
		$paymentKind = $row->paymentKind;

		if ($clearStatus == "0") $cmsStatus = "정상";
		else $cmsStatus = $clearStatus . "회미납";

		// CMS등록조회 함수
		$response = cmsView($cmsId);
		$response = json_decode($response);

		$paymentKind = $response->{'paymentKind'};
		$paymentCompany = $response->{'paymentCompany'};
		$paymentNumber = $response->{'paymentNumber'};

		if ($paymentKind == "CMS") {
			$paymentKind = selected_object($paymentKind, $arrPaymentKind);
			$paymentCompany = selected_object($paymentCompany, $arrBankCode);
		}

		$data = array(
			'cmsStatus'      => $cmsStatus,
			'paymentKind'    => $paymentKind,
			'paymentCompany' => $paymentCompany,
			'paymentNumber'  => $paymentNumber
		);

		$result_status  = $response->{'result'};
		$result_message = $response->{'message'};

	} else {
		$data = array();
		$result_status = "1";
		$result_message = "CMS가 등록되어 있지 않는 회원입니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
		'data'    => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>