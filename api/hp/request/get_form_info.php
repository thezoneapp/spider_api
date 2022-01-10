<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자페이지 > 휴대폰신청 > 목록 > 상세정보 > 접수,배송양식
	* parameter ==> idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};
$idx = 942;
	// 신청자료 검색
    $sql = "SELECT idx, memId, memName, custName, birthday, hpNo, changeTelecom, requestAssort, modelCode, modelName, colorCode, colorName, capacityCode, capacityName, 
	               basicChargeName, chargeName, discountType, factoryPrice, buyPrice, monthInstFee, monthInstPrice, installment, cardCode, cardName, benefitAssort, 
				   commiPrice, commission, openingDate, closeDate, outPlaceAssort, barCode, agencyId 
	        FROM hp_request 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->birthday !== "") $row->birthday = aes_decode($row->birthday);
		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

		$agencyId = $row->agencyId;
		$changeTelecom = $row->changeTelecom;
		$changeTelecomName = selected_object($row->changeTelecom, $arrTelecomAssort);
		$assortName = selected_object($row->requestAssort, $arrRequestAssort);
		$custName = $row->custName;
		$birthday = $row->birthday;
		$hpNo = $row->hpNo;
		$modelCode = $row->modelCode;
		$modelName = $row->modelName;
		$capacityName = $row->capacityName;
		$colorName = $row->colorName;
		$chargeName = $row->basicChargeName;
		$factoryPrice = $row->factoryPrice;
		$buyPrice = $row->buyPrice;
		$installment = $row->installment;
		$discountType = $row->discountType;

		// 현금 또는 할부 구분
		if ($buyPrice == 0) $isCash = "현금";
		else $isCash = "할부";

		// 공시지원 또는선택약정 구분 및 개월수
		if ($discountType == "S") $isDiscount = "공시지원/" . $installment . "개월";
		else $isDiscount = "선택약정/" . $installment . "개월";

		// 부가서비스정보
		$addServices = "";
		$sql = "SELECT serviceName, servicePrice, periodDay FROM hp_add_service WHERE useYn = 'Y' and telecom = '$changeTelecom'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				if ($addServices != "") $addServices .= ", ";
				$addServices .= $row2[serviceName];
			}
		}

		// 할인정보
		$supportPrice = 0;
		$addPrice = 0;
		$cardPrice = 0;
		$sql = "SELECT discountAssort, discountPrice FROM hp_request_discount WHERE requestIdx = '$idx' ORDER BY idx ASC";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				if ($row2[discountAssort] == "S") $supportPrice = $row2[discountPrice];
				else if ($row2[discountAssort] == "A") $addPrice = $row2[discountPrice];
				else if ($row2[discountAssort] == "C") $cardPrice = $row2[discountPrice];
			}
		}

		// 배송지 정보
		$postCode = "";
		$addr1    = "";
		$addr2    = "";
		$addHpNo  = "";
		$sql = "SELECT postCode, addr1, addr2, addHpNo FROM hp_delivery WHERE requestIdx = '$idx'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);

			if ($row2->addHpNo !== "") $row2->addHpNo = aes_decode($row2->addHpNo);

			$postCode = $row2->postCode;
			$addr1    = $row2->addr1;
			$addr2    = $row2->addr2;
			$addHpNo  = $row2->addHpNo;
		}
	
		// 접수,배송양식
		$acceptForm = "";
		$deliveryForm = "";
		$sql = "SELECT acceptForm, deliveryForm, companyCode, companyName FROM hp_agency WHERE agencyId = '$agencyId'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);
			$acceptForm = $row->acceptForm;
			$deliveryForm = $row->deliveryForm;

			$acceptForm = str_replace("{telecomName}", $changeTelecomName, $acceptForm);
			$acceptForm = str_replace("{assortName}", $assortName, $acceptForm);
			$acceptForm = str_replace("{custName}", $custName, $acceptForm);
			$acceptForm = str_replace("{birthday}", $birthday, $acceptForm);
			$acceptForm = str_replace("{hpNo}", $hpNo, $acceptForm);
			$acceptForm = str_replace("{modelName}", $modelName, $acceptForm);
			$acceptForm = str_replace("{capacityName}", $capacityName, $acceptForm);
			$acceptForm = str_replace("{colorName}", $colorName, $acceptForm);
			$acceptForm = str_replace("{chargeName}", $chargeName, $acceptForm);
			$acceptForm = str_replace("{isCash}", $isCash, $acceptForm);
			$acceptForm = str_replace("{installment}", $installment, $acceptForm);
			$acceptForm = str_replace("{isDiscount}", $isDiscount, $acceptForm);
			$acceptForm = str_replace("{factoryPrice}", number_format($factoryPrice), $acceptForm);
			$acceptForm = str_replace("{supportPrice}", number_format($supportPrice), $acceptForm);
			$acceptForm = str_replace("{addPrice}", number_format($addPrice), $acceptForm);
			$acceptForm = str_replace("{cardPrice}", number_format($cardPrice), $acceptForm);
			$acceptForm = str_replace("{buyPrice}", number_format($buyPrice), $acceptForm);
			$acceptForm = str_replace("{addServices}", $addServices, $acceptForm);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result_status = "1";
	}

	$response = array(
		'result'       => $result_status,
		'message'      => $result_message,
		'acceptForm'   => $acceptForm,
		'deliveryForm' => $deliveryForm,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>