<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 할인제 추가/수정
	* parameter ==> mode:          insert(추가), update(수정)
	* parameter ==> idx:           수정할 레코드 id
	* parameter ==> discountCode:  할인코드
	* parameter ==> discountName:  할인명
	* parameter ==> discountPrice: 할인요금
	* parameter ==> discountType:  할인구분
	* parameter ==> allYn:         적용대상
	* parameter ==> useYn:         사용여부

	* parameter ==> models:        모델배열
	* parameter ==> charges:       요금제배열
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$mode          = $data_back->{'mode'};
	$idx           = $data_back->{'idx'};
	$discountCode  = $data_back->{'discountCode'};
	$discountName  = $data_back->{'discountName'};
	$discountPrice = $data_back->{'discountPrice'};
	$discountType  = $data_back->{'discountType'};
	$allYn         = $data_back->{'allYn'};
	$useYn         = $data_back->{'useYn'};

	$models        = $data_back->{'models'};
	$charges       = $data_back->{'charges'};

	if ($mode == "insert") {
		$sql = "SELECT idx FROM hp_discount WHERE discountCode = '$discountCode'";
		$result = $connect->query($sql);

	    if ($result->num_rows == 0) {
			if ($discountCode == "") {
				$sql = "SELECT ifnull(max(idx),0) + 1 AS maxIdx FROM hp_discount";
				$result2 = $connect->query($sql);
				$row2 = mysqli_fetch_object($result2);
				$discountCode = $discountType . "-" . $row2->maxIdx;
			}

			$sql = "INSERT INTO hp_discount (discountCode, discountName, discountPrice, discountType, allYn, useYn)
							         VALUES ('$discountCode', '$discountName', '$discountPrice', '$discountType', '$allYn', '$useYn')";
			$connect->query($sql);

			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "등록하였습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "중복된 '요금제코드'입니다.";
		}

	} else {
		$sql = "UPDATE hp_discount SET discountCode = '$discountCode',
		                               discountName = '$discountName', 
								       discountPrice = '$discountPrice', 
									   discountType = '$discountType', 
									   allYn = '$allYn', 
								       useYn = '$useYn' 
						WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "변경하였습니다.";
	}

	// ********************************** 단말기 옵션 *****************************************
	if ($allYn == "Y") {
		$sql = "DELETE FROM hp_discount_model WHERE discountCode = '$discountCode'";
		$connect->query($sql);

	} else {
		$sql = "UPDATE hp_discount_model SET updateCheck = 'Y' WHERE discountCode = '$discountCode'";
		$connect->query($sql);

		for ($i = 0; count($models) > $i; $i++) {
			$model     = $models[$i];
			$idx       = $model->{'idx'};
			$modelCode = $model->{'modelCode'};

			if ($idx == "0") {
				$sql = "INSERT INTO hp_discount_model (discountCode, modelCode, useYn) 
												VALUES ('$discountCode', '$modelCode', 'N')";
				$connect->query($sql);

			} else {
				$sql = "UPDATE hp_discount_model SET discountCode = '$discountCode', 
													  modelCode = '$modelCode', 
													  updateCheck = null 
						WHERE idx = '$idx'";
				$connect->query($sql);
			}
		}

		$sql = "DELETE FROM hp_discount_model WHERE discountCode = '$discountCode' and updateCheck = 'Y'";
		$connect->query($sql);
	}

	// ********************************** 요금제 옵션 *****************************************
	if ($allYn == "Y") {
		$sql = "DELETE FROM hp_discount_charge WHERE discountCode = '$discountCode'";
		$connect->query($sql);

	} else {
		$sql = "UPDATE hp_discount_charge SET updateCheck = 'Y' WHERE discountCode = '$discountCode'";
		$connect->query($sql);

		for ($i = 0; count($charges) > $i; $i++) {
			$charge     = $charges[$i];
			$idx        = $charge->{'idx'};
			$chargeCode = $charge->{'chargeCode'};

			if ($idx == "0") {
				$sql = "INSERT INTO hp_discount_charge (discountCode, chargeCode, useYn) 
												VALUES ('$discountCode', '$chargeCode', 'N')";
				$connect->query($sql);

			} else {
				$sql = "UPDATE hp_discount_charge SET discountCode = '$discountCode', 
													  chargeCode = '$chargeCode', 
													  useYn = '$useYn',
													  updateCheck = null 
						WHERE idx = '$idx'";
				$connect->query($sql);
			}
		}

		$sql = "DELETE FROM hp_discount_charge WHERE discountCode = '$discountCode' and updateCheck = 'Y'";
		$connect->query($sql);
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>