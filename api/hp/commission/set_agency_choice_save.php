<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 신청 > 수수료정책표 > 업체선택 > 저장
	* parameter
		assortCode: 구분(N: 신규가입, M: 번호이동, C: 기기변경)
		data: 데이타 배열
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$assortCode = $data_back->{'assortCode'};
	$arrData    = $data_back->{'data'};

	for ($i = 0; count($arrData) > $i; $i++) {
		$data = $arrData[$i];

		$modelCode    = $data->modelCode;
		$agencyId     = $data->agencyId;
		$telecom      = $data->telecom;
		$discountType = $data->discountType;
		$useYn        = $data->useYn;

		if ($assortCode == "N") {
			$sql = "UPDATE hp_agency_commi 
						SET newUseYn = '$useYn' 
						WHERE modelCode = '$modelCode' and agencyId = '$agencyId' and telecom = '$telecom' and discountType = '$discountType'";
			$connect->query($sql);
		
		} else if ($assortCode == "M") {
			$sql = "UPDATE hp_agency_commi 
						SET mnpUseYn = '$useYn' 
						WHERE modelCode = '$modelCode' and agencyId = '$agencyId' and telecom = '$telecom' and discountType = '$discountType'";
			$connect->query($sql);

		} else if ($assortCode == "C") {
			$sql = "UPDATE hp_agency_commi 
						SET changeUseYn = '$useYn' 
						WHERE modelCode = '$modelCode' and agencyId = '$agencyId' and telecom = '$telecom' and discountType = '$discountType'";
			$connect->query($sql);
		}
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
