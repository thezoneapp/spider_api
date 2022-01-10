<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰신청 정보
	* parameter ==> idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

	// 휴대폰 모델
	$modelOptions = array();
	$sql = "SELECT modelCode, modelName  
			FROM hp_model 
			WHERE useYn = 'Y' 
			ORDER BY modelCode ASC";
	$result2 = $connect->query($sql);

	if ($result2->num_rows > 0) {
		while($row2 = mysqli_fetch_array($result2)) {
			$model_info = array(
				'code' => $row2[modelCode],
				'name' => $row2[modelName]
			);
			array_push($modelOptions, $model_info);
		}
	}

	// 신청자료 검색
    $sql = "SELECT idx, memId, memName, custName, birthday, hpNo, useTelecom, changeTelecom, requestAssort, modelCode, modelName, colorCode, colorName, capacityCode, capacityName, 
	               basicChargeName, chargeName, discountType, factoryPrice, buyPrice, monthInstFee, monthInstPrice, installment, cardCode, cardName, benefitAssort, requestStatus, 
				   commiPrice, commission, openingDate, closeDate, outPlaceAssort, barCode, comment, statusMemo, adminMemo, date_format(wdate, '%Y-%m-%d') as wdate 
	        FROM hp_request 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$modelCode = $row->modelCode;
		$useTelecom = $row->useTelecom;
		$changeTelecom = $row->changeTelecom;
		$useTelecomName = selected_object($row->useTelecom, $arrTelecomAssort);
		$changeTelecomName = selected_object($row->changeTelecom, $arrTelecomAssort);
		$assortName = selected_object($row->requestAssort, $arrRequestAssort);
		$discountTypeName = selected_object($row->discountType, $arrDiscountType3);
		$benefitName = selected_object($row->benefitAssort, $arrBenefitAssort);
		$statusName = selected_object($row->requestStatus, $arrRequestStatus);

		if ($benefitName == "") $benefitName = "없음";
		if ($row->cardCode == "") $row->cardName = "없음";
		if ($row->commission == null) $row->commission = "";
		if ($row->comment == null) $row->comment = "";
		if ($row->statusMemo == null) $row->statusMemo = "";
		if ($row->adminMemo == null) $row->adminMemo = "";

		if ($row->birthday !== "") $row->birthday = aes_decode($row->birthday);
		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);
		if ($row->accountNo !== "") $row->accountNo = aes_decode($row->accountNo);

		// 색상 데이타 검색 
		$colors = array();
		$sql = "SELECT idx, colorName FROM hp_model_color WHERE useYn = 'Y' and modelCode = '$modelCode' ORDER BY idx ASC";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				$target_info = array(
					'code' => $row2[idx],
					'name' => $row2[colorName],
				);
				array_push($colors, $target_info);
			}
		}

		// 용량 데이타 검색 
		$capacitys = array();
		$sql = "SELECT idx, capacityCode FROM hp_model_capacity WHERE useYn = 'Y' and modelCode = '$modelCode' ORDER BY idx ASC";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				$capacityName = selected_object($row2[capacityCode], $arrCapacityAssort);

				$capacity_info = array(
					'code' => $row2[capacityCode],
					'name' => $capacityName,
				);
				array_push($capacitys, $capacity_info);
			}
		}

		// 할인정보
		$discountData = array();
		$sql = "SELECT discountName, discountPrice FROM hp_request_discount WHERE requestIdx = '$idx' ORDER BY idx ASC";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				$capacityName = selected_object($row2[capacityCode], $arrCapacityAssort);

				$discount_info = array(
					'discountName'  => $row2[discountName],
					'discountPrice' => number_format($row2[discountPrice]),
				);
				array_push($discountData, $discount_info);
			}
		}

		// 캐시백정보
		$cashBackData = array();
		$sql = "SELECT cash, bankCode, accountName, accountNo, status FROM hp_cash_back WHERE requestIdx = '$idx'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);

			$bankName = selected_object($row2->bankCode, $arrBankCode);

			if ($row2->accountNo !== "") $row2->accountNo = aes_decode($row2->accountNo);

			$cashBackData = array(
				'cash'            => number_format($row2->cash),
				'bankName'        => $bankName,
				'accountName'     => $row2->accountName,
				'accountNo'       => $row2->accountNo,
				'cashbackStatus'  => $row2->status,
				'cashbackOptions' => $arrAccountStatus,
			);
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

		$data = array(
			'idx'                 => $row->idx,
			'memId'               => $row->memId,
			'memName'             => $row->memName,

			'custName'            => $row->custName,
			'birthday'            => $row->birthday,
			'hpNo'                => $row->hpNo,		
			'addHpNo'             => $addHpNo,
			'postCode'			  => $postCode,
			'addr1'				  => $addr1,
			'addr2'               => $addr2,

			'useTelecom'          => $row->useTelecom,
			'useTelecomName'      => $useTelecomName,
			'changeTelecom'       => $row->changeTelecom,
			'changeTelecomName'   => $changeTelecomName,
			'telecomOptions'      => $arrTelecomAssort,

			'requestAssort'       => $row->requestAssort,
			'assortName'          => $assortName,
			'assortOptions'       => $arrRequestAssort,

			'modelCode'           => $row->modelCode,
			'modelName'           => $row->modelName,
			'modelOptions'        => $modelOptions,

			'colorCode'           => $row->colorCode,
			'colorName'           => $row->colorName,
			'colorOptions'        => $colors,
			'capacityCode'        => $row->capacityCode,
			'capacityName'        => $row->capacityName,
			'capacityOptions'     => $capacitys,

			'basicChargeName'     => $row->basicChargeName,
			'chargeName'          => $row->chargeName,

			'benefitAssort'       => $row->benefitAssort,
			'benefitName'         => $benefitName,
			'benefitOptions'      => $arrBenefitAssort,

			'factoryPrice'        => number_format($row->factoryPrice),
			'buyPrice'            => number_format($row->buyPrice),
			'monthInstFee'        => number_format($row->monthInstFee),
			'monthInstPrice'      => number_format($row->monthInstPrice),
			'installment'         => $row->installment,

			'discountType'        => $row->discountType,
			'discountTypeName'    => $discountTypeName,
			'discountTypeOptions' => $arrDiscountType3,

			'cardCode'            => $row->cardCode,
			'cardName'            => $row->cardName,

			'openingDate'         => $row->openingDate,
			'closeDate'           => $row->closeDate,
			'barCode'             => $row->barCode,
			'outPlaceAssort'      => $row->outPlaceAssort,
			'outPlaceOptions'     => $arrOutPlaceAssort2,

			'requestStatus'       => $row->requestStatus,
			'statusName'          => $statusName,
			'statusOptions'       => $arrRequestStatus,

			'commiPrice'          => number_format($row->commiPrice),
			'commission'          => number_format($row->commission),

			'bankName'            => $row->bankName,
			'accountNo'           => $row->accountNo,
			'accountName'         => $row->accountName,
			'accountDate'         => $row->accountDate,
			'accountStatus'       => $row->accountStatus,
			'accountName'         => $accountName,
			'accountOptions'      => $arrAccountStatus,

			'discountData'        => $discountData,
			'cashBackData'        => $cashBackData,

			'comment'             => $row->comment,
			'statusMemo'          => $row->statusMemo,
			'adminMemo'           => $row->adminMemo,
			'wdate'               => $row->wdate,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result_status = "1";
	}

	// 진행 로그
	//logAssort: 로그구분 => C: CMS, E: 계약, A: 회원상태

	$logData = array();

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
		'data'      => $data,
		'logData'   => $logData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>