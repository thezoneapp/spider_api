<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자페이지 > 휴대폰신청 > 업체관리 > 목록 > 상세정보 > 추가/수정
	* parameter
	  mode:         insert(추가), update(수정)
	  idx:          수정할 idx
	  agencyId:     업체코드
	  agencyName:   업체명
	  telNo:        전화번호
	  hpNo:         휴대폰번호
	  openingForm:  접수양식
	  deliveryForm: 배송양식
	  telecoms:     취급통신사
	  companyCode:  배송업체코드
	  useYn:        사용여부
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$mode         = $data_back->{'mode'};
	$idx          = $data_back->{'idx'};
	$agencyId     = $data_back->{'agencyId'};
	$agencyName   = $data_back->{'agencyName'};
	$telNo        = $data_back->{'telNo'};
	$hpNo         = $data_back->{'hpNo'};
	$openingForm  = $data_back->{'openingForm'};
	$deliveryForm = $data_back->{'deliveryForm'};
	$arrTelecoms  = $data_back->{'telecoms'};
	$companyCode  = $data_back->{'companyCode'};
	$useYn        = $data_back->{'useYn'};

	$companyName  = $companyCode->{'name'};
	$companyCode  = $companyCode->{'code'};

	// 취급 통신사
	$telecoms = "";
	for ($i=0; $i < count($arrTelecoms); $i++) {
		$telecom = $arrTelecoms[$i];
		
		$telecomCode = $telecom->code;
		$checked     = $telecom->checked;

		if ($checked) {
			if ($telecoms != "") $telecoms .= ",";
			$telecoms .= $telecomCode;
		}
	}

	if ($telNo != "") $telNo = aes128encrypt($telNo);
	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

	if ($mode == "insert") {
		$sql = "SELECT idx FROM hp_agency WHERE agencyId = '$agencyId'";
		$result = $connect->query($sql);

	    if ($result->num_rows == 0) {
			$sql = "INSERT INTO hp_agency (agencyId, agencyName, telNo, hpNo, openingForm, deliveryForm, telecoms, companyCode, companyName, useYn, wdate)
							       VALUES ('$agencyId', '$agencyName', '$telNo', '$hpNo', '$openingForm', '$deliveryForm', '$telecoms', '$companyCode', '$companyName', '$useYn', now())";
			$connect->query($sql);

			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "등록하였습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "중복된 '서비스코드'입니다.";
		}

	} else {
		$sql = "UPDATE hp_agency SET agencyId = '$agencyId',
		                             agencyName = '$agencyName', 
								     telNo = '$telNo', 
								     hpNo = '$hpNo', 
									 openingForm = '$openingForm', 
									 deliveryForm = '$deliveryForm', 
									 telecoms = '$telecoms', 
									 companyCode = '$companyCode', 
									 companyName = '$companyName', 
								     useYn = '$useYn' 
						WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "변경하였습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>