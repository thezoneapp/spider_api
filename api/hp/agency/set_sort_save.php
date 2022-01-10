<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 신청 > 업체관리 > 목록 > 수수료정책 노출우선순위 저장
	* parameter
		sortData: 데이타 배열
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$sortData = $data_back->{'sortData'};

	$skData = $sortData->sk;
	$ktData = $sortData->kt;
	$lgData = $sortData->lg;

	//*************************** SK
	// 공시지원
	$arrSupport = $skData->supportData;

	for ($i = 0; count($arrSupport) > $i; $i++) {
		$row = $arrSupport[$i];

		$agencyId = $row->agencyId;
		$sortNo   = $row->sortNo;

		$sql = "UPDATE hp_agency SET sort_sk_s = '$sortNo' WHERE agencyId = '$agencyId'";
		$connect->query($sql);
	}

	// 요금할인
	$arrCharge = $skData->chargeData;

	for ($i = 0; count($arrCharge) > $i; $i++) {
		$row = $arrCharge[$i];

		$agencyId = $row->agencyId;
		$sortNo   = $row->sortNo;

		$sql = "UPDATE hp_agency SET sort_sk_c = '$sortNo' WHERE agencyId = '$agencyId'";
		$connect->query($sql);
	}

	//*************************** KT
	// 공시지원
	$arrSupport = $ktData->supportData;

	for ($i = 0; count($arrSupport) > $i; $i++) {
		$row = $arrSupport[$i];

		$agencyId = $row->agencyId;
		$sortNo   = $row->sortNo;

		$sql = "UPDATE hp_agency SET sort_kt_s = '$sortNo' WHERE agencyId = '$agencyId'";
		$connect->query($sql);
	}

	// 요금할인
	$arrCharge = $ktData->chargeData;

	for ($i = 0; count($arrCharge) > $i; $i++) {
		$row = $arrCharge[$i];

		$agencyId = $row->agencyId;
		$sortNo   = $row->sortNo;

		$sql = "UPDATE hp_agency SET sort_kt_c = '$sortNo' WHERE agencyId = '$agencyId'";
		$connect->query($sql);
	}

	//*************************** LG
	// 공시지원
	$arrSupport = $lgData->supportData;

	for ($i = 0; count($arrSupport) > $i; $i++) {
		$row = $arrSupport[$i];

		$agencyId = $row->agencyId;
		$sortNo   = $row->sortNo;

		$sql = "UPDATE hp_agency SET sort_lg_s = '$sortNo' WHERE agencyId = '$agencyId'";
		$connect->query($sql);
	}

	// 요금할인
	$arrCharge = $lgData->chargeData;

	for ($i = 0; count($arrCharge) > $i; $i++) {
		$row = $arrCharge[$i];

		$agencyId = $row->agencyId;
		$sortNo   = $row->sortNo;

		$sql = "UPDATE hp_agency SET sort_lg_c = '$sortNo' WHERE agencyId = '$agencyId'";
		$connect->query($sql);
	}

	// 성공 결과를 반환합니다.
	$result_status = "0";
	$result_message = "저장하였습니다.";

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>