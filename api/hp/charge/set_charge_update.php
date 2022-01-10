<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 요금제 추가/수정
	* parameter ==> mode:          insert(추가), update(수정)
	* parameter ==> idx:           수정할 레코드 id
	* parameter ==> chargeCode:    요금제코드
	* parameter ==> chargeName:    요금제명
	* parameter ==> chargePrice:   월요금
	* parameter ==> discountPrice: 할인요금
	* parameter ==> imtAssort:     통신망
	* parameter ==> telecom:       통신사
	* parameter ==> expireDayS:    유지기간-공시지원
	* parameter ==> expireDayC:    유지기간-선택약정
	* parameter ==> chargeExplain: 요금제 설명
	* parameter ==> useYn:         사용여부
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$mode          = $data_back->{'mode'};
	$idx           = $data_back->{'idx'};
	$chargeCode    = $data_back->{'chargeCode'};
	$chargeName    = $data_back->{'chargeName'};
	$chargePrice   = $data_back->{'chargePrice'};
	$discountPrice = $data_back->{'discountPrice'};
	$imtAssort     = $data_back->{'imtAssort'};
	$telecom       = $data_back->{'telecom'};
	$expireDayS    = $data_back->{'expireDayS'};
	$expireDayC    = $data_back->{'expireDayC'};
	$chargeExplain = $data_back->{'chargeExplain'};
	$useYn         = $data_back->{'useYn'};

	if ($mode == "insert") {
		$sql = "SELECT idx FROM hp_charge WHERE chargeCode = '$chargeCode'";
		$result = $connect->query($sql);

	    if ($result->num_rows == 0) {
			if ($chargeCode == "") {
				$sql = "SELECT ifnull(max(idx),0) + 1 AS maxIdx FROM hp_charge";
				$result2 = $connect->query($sql);
				$row2 = mysqli_fetch_object($result2);
				$chargeCode = $telecom . "-" . $row2->maxIdx;
			}

			$sql = "INSERT INTO hp_charge (chargeCode, chargeName, chargePrice, discountPrice, imtAssort, telecom, expireDayS, expireDayC, chargeExplain, useYn)
							      VALUES ('$chargeCode', '$chargeName', '$chargePrice', '$discountPrice', '$imtAssort', '$telecom', '$expireDayS', '$expireDayC', '$chargeExplain', '$useYn')";
			$connect->query($sql);

			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "등록하였습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = $sql; //"중복된 '요금제코드'입니다.";
		}

	} else {
		$sql = "UPDATE hp_charge SET chargeCode = '$chargeCode',
		                             chargeName = '$chargeName', 
								     chargePrice = '$chargePrice', 
								     discountPrice = '$discountPrice', 
									 imtAssort = '$imtAssort', 
									 telecom = '$telecom', 
									 expireDayS = '$expireDayS', 
									 expireDayC = '$expireDayC', 
									 chargeExplain = '$chargeExplain', 
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