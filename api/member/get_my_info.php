<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 내정보 정보
	* parameter ==> userId: 아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId     = $input_data->{'userId'};

	$protocol = isset($_SERVER['HTTPS']) ? "https://" : "http://";

	$data = array();
    $sql = "SELECT memPw, memName, memAssort, cmsStatus, memStatus, hpNo, photo, gajaId, conciergeId, 
	               registNo, accountName, accountNo, accountBank, businessAssort, taxAssort, taxRegistNo 
			FROM member 
			WHERE memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->hpNo == null) $row->hpNo = "";
		if ($row->email == null) $row->email = "";
		if ($row->registNo == null) $row->registNo = "";
		if ($row->accountName == null) $row->accountName = "";
		if ($row->accountNo == null) $row->accountNo = "";
		if ($row->accountBank == null) $row->accountBank = "";

		if ($row->memPw !== "") $row->memPw = aes_decode($row->memPw);
		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);
		if ($row->registNo !== "") $row->registNo = aes_decode($row->registNo);
		if ($row->taxRegistNo !== "") $row->taxRegistNo = aes_decode($row->taxRegistNo);
		if ($row->accountNo !== "") $row->accountNo = aes_decode($row->accountNo);

		$registNo2  = substr($row->registNo, 0, 6) . "-*******";
		$accountNo2 = substr($row->accountNo, 0, 3) . "*******";

		$photo = $protocol . "spiderplatform.co.kr/". $row->photo;

		$cmsStatus = selected_object($row->cmsStatus, $arrCmsStatus);
		$memAssort = selected_object($row->memAssort, $arrMemAssort);
		$memStatus = selected_object($row->memStatus, $arrMemStatus);
		$businessName = selected_object($row->businessAssort, $arrBusinessAssort);
		$taxAssort = selected_object($row->taxAssort, $arrTaxAssort);

		$taxNo = "";

		if ($row->businessAssort == "G" || $row->businessAssort == "L") {
			$sql = "SELECT corpNum FROM tax_member WHERE memId = '$userId'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);
			$taxNo = $row2->corpNum;
		}

		// 컨시어지 ID 발급상태
		$sql = "SELECT requestStatus FROM concierge_request WHERE memId = '$userId'";
		$result2 = $connect->query($sql);

	    if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			$conciergeStatus = $row2->requestStatus;

		} else {
			$conciergeStatus = "";
		}

		// 파트너 count
		$sql = "SELECT count(idx) AS partnerCnt FROM member WHERE sponsId = '$userId'";
		$result2 = $connect->query($sql);
		$row2 = mysqli_fetch_object($result2);
		$partnerCnt = $row2->partnerCnt;

		$data = array(
			'userPw'          => $row->memPw,
			'userName'        => $row->memName,
			'hpNo'            => $row->hpNo,
			'photo'           => $photo,
			'gajaId'          => $row->gajaId,
			'conciergeId'     => $row->conciergeId,
			'conciergeStatus' => $conciergeStatus,
			'registNo'        => $row->registNo,
			'registNo2'       => $registNo2,
			'accountName'     => $row->accountName,
			'accountNo'       => $row->accountNo,
			'accountNo2'      => $accountNo2,
			'accountBank'     => $row->accountBank,
			'bankOptions'     => $row->arrBankCode,
		    'cmsStatus'       => $cmsStatus,
			'memAssort'       => $memAssort,
			'memStatus'       => $memStatus,
			'partnerCnt'      => $partnerCnt,
			'businessAssort'  => $row->businessAssort,
			'businessName'    => $businessName,
			'taxAssort'       => $taxAssort,
			'taxNo'           => $taxNo,
			'taxRegistNo'     => $row->taxRegistNo,
		);

		$result_status = "0";

    } else {
		$result_status = "1";
	}

	$response = array(
		'result' => $result_status,
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>