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

	// 신청자료 검색
    $sql = "SELECT idx, memId, memName, memHpNo, custName, birthday, hpNo, useTelecom, changeTelecom, requestAssort, modelCode, modelName, colorCode, colorName, capacityCode, capacityName, 
	               basicChargeName, chargeName, discountType, factoryPrice, buyPrice, monthInstFee, monthInstPrice, installment, cardCode, cardName, cardDiscountPrice, benefitAssort, requestStatus, 
				   agencyPrice, commiPrice, payPrice, commission, pointRemarks, openingStatus, openingDate, closeDate, 
				   agencyId, usimAssort, usimNo, barCode, deliveryCompany, deliveryNo, deliveryStatus, writeLink, writeStatus, channelAssort, insuName, 
				   comment, adminMemo, date_format(wdate, '%Y-%m-%d') as wdate 
	        FROM hp_request 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$modelCode = $row->modelCode;
		$useTelecom = $row->useTelecom;
		$changeTelecom = $row->changeTelecom;
		$deliveryCompany = $row->deliveryCompany;
		$agencyId = $row->agencyId;

		$useTelecomName = selected_object($row->useTelecom, $arrTelecomAssort);
		$changeTelecomName = selected_object($row->changeTelecom, $arrTelecomAssort);
		$assortName = selected_object($row->requestAssort, $arrRequestAssort);
		$discountTypeName = selected_object($row->discountType, $arrDiscountType3);
		$benefitName = selected_object($row->benefitAssort, $arrBenefitAssort);
		$channelName = selected_object($row->channelAssort, $arrChannelAssort);
		$deliveryName = selected_object($row->deliveryStatus, $arrDeliveryStatus);
		$writeName = selected_object($row->writeStatus, $arrWriteStatus);
		$statusName = selected_object($row->requestStatus, $arrRequestStatus);

		if ($benefitName == "") $benefitName = "없음";
		if ($row->cardCode == "") $row->cardName = "없음";
		if ($row->commission == null) $row->commission = "";
		if ($row->comment == null) $row->comment = "";
		if ($row->adminMemo == null) $row->adminMemo = "";

		if ($row->birthday !== "") $row->birthday = aes_decode($row->birthday);
		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);
		if ($row->memHpNo !== "") $row->memHpNo = aes_decode($row->memHpNo);
		if ($row->accountNo !== "") $row->accountNo = aes_decode($row->accountNo);

		if ($row->discountType == "S") $isDiscount = "공시지원";
		else $isDiscount = "선택약정";

		// 색상정보 
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

		// 용량정보
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
		$discountData = array();
		$sql = "SELECT idx, discountAssort, discountName, discountPrice FROM hp_request_discount WHERE requestIdx = '$idx' ORDER BY idx ASC";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				if ($row2[discountAssort] == "S") $supportPrice = number_format(0 - $row2[discountPrice]);
				else if ($row2[discountAssort] == "A") $addPrice = number_format(0 - $row2[discountPrice]);

				$discount_info = array(
					'idx'            => $row2[idx],
					'discountAssort' => $row2[discountAssort],
					'discountName'   => $row2[discountName],
					'discountPrice'  => number_format($row2[discountPrice]),
				);
				array_push($discountData, $discount_info);
			}
		}

		// 캐시백정보
		$cash = "";
		$bankCode = "";
		$bankName = "";
		$accountName = "";
		$accountNo = "";
		$sql = "SELECT cash, bankCode, accountName, accountNo, status FROM hp_cash_back WHERE requestIdx = '$idx'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);

			$bankName = selected_object($row2->bankCode, $arrBankCode);

			if ($row2->accountNo !== "") $row2->accountNo = aes_decode($row2->accountNo);

			$cash        = $row2->cash;
			$bankCode    = $row2->bankCode;
			$accountName = $row2->accountName;
			$accountNo   = $row2->accountNo;
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

		// 배송업체 정보
		$sql = "SELECT companyCode, companyName, deliveryForm, openingForm FROM hp_agency WHERE useYn = 'Y' and agencyId = '$agencyId'";
		$result3 = $connect->query($sql);

		if ($result3->num_rows > 0) {
			$row3 = mysqli_fetch_object($result3);
			$deliveryCompany = $row3->companyCode;
			$companyName     = $row3->companyName;
			$deliveryForm    = $row3->deliveryForm;
			$openingForm     = $row3->openingForm;

		} else {
			$deliveryForm = "■ 스파이더플랫폼 배송요청";
			$openingForm  = "■ 스파이더플랫폼 개통요청";
		}

		if ($row->deliveryCompany != "") {
			$sql = "SELECT companyName FROM delivery_company WHERE companyCode = '$deliveryCompany'";
			$result3 = $connect->query($sql);

			if ($result3->num_rows > 0) {
				$row3 = mysqli_fetch_object($result3);
				$companyName = $row3->companyName;
			}
		}

		// 배송요청양식
		$address = $addr1 . " ". $addr2;
	    $deliveryForm = str_replace("{changeTelecom}", $changeTelecomName,  $deliveryForm);
	    $deliveryForm = str_replace("{custName}",      $row->custName,      $deliveryForm);
	    $deliveryForm = str_replace("{hpNo}",          $row->hpNo,          $deliveryForm);
	    $deliveryForm = str_replace("{address}",       $address,            $deliveryForm);
	    $deliveryForm = str_replace("{modelCode}",     $row->modelCode,     $deliveryForm);
	    $deliveryForm = str_replace("{modelName}",     $row->modelName,     $deliveryForm);
	    $deliveryForm = str_replace("{capacityName}",  $row->capacityName,  $deliveryForm);
		$deliveryForm = str_replace("{colorName}",     $row->colorName,     $deliveryForm);

		// 개통요청양식
		$factoryPrice = number_format($row->factoryPrice);
		$buyPrice = number_format($row->buyPrice);

		if ($buyPrice <= 0) $isCash = "현금";
		else $isCash = "할부";

	    $openingForm = str_replace("{useTelecom}",    $useTelecomName,     $openingForm);
	    $openingForm = str_replace("{changeTelecom}", $changeTelecomName,  $openingForm);
	    $openingForm = str_replace("{telecomName}",   $changeTelecomName,  $openingForm);
	    $openingForm = str_replace("{assortName}",    $assortName,         $openingForm);
	    $openingForm = str_replace("{custName}",      $row->custName,      $openingForm);
	    $openingForm = str_replace("{birthday}",      $row->birthday,      $openingForm);
	    $openingForm = str_replace("{hpNo}",          $row->hpNo,          $openingForm);
	    $openingForm = str_replace("{modelCode}",     $row->modelCode,     $openingForm);
	    $openingForm = str_replace("{modelName}",     $row->modelName,     $openingForm);
	    $openingForm = str_replace("{capacityName}",  $row->capacityName,  $openingForm);
	    $openingForm = str_replace("{colorName}",     $row->colorName,     $openingForm);
	    $openingForm = str_replace("{chargeName}",    $row->chargeName,    $openingForm);
	    $openingForm = str_replace("{isCash}",        $isCash,             $openingForm);
	    $openingForm = str_replace("{installment}",   $row->installment,   $openingForm);
	    $openingForm = str_replace("{isDiscount}",    $isDiscount,         $openingForm);
	    $openingForm = str_replace("{factoryPrice}",  $factoryPrice,       $openingForm);
	    $openingForm = str_replace("{supportPrice}",  $supportPrice,       $openingForm);
	    $openingForm = str_replace("{addPrice}",      $addPrice,           $openingForm);
	    $openingForm = str_replace("{buyPrice}",      $buyPrice,           $openingForm);
	    $openingForm = str_replace("{addServices}",   $addServices,        $openingForm);
	    $openingForm = str_replace("{barCode}",       $row->barCode,       $openingForm);
	    $openingForm = str_replace("{usimNo}",        $row->usimNo,        $openingForm);

		$data = array(
			'idx'                 => $row->idx,
			'memId'               => $row->memId,
			'memName'             => $row->memName,
			'memHpNo'             => $row->memHpNo,
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
			'requestAssort'       => $row->requestAssort,
			'assortName'          => $assortName,
			'modelCode'           => $row->modelCode,
			'modelName'           => $row->modelName,
			'modelOptions'        => $modelOptions,
			'colorCode'           => $row->colorCode,
			'colorName'           => $row->colorName,
			'colorOptions'        => $colors,
			'capacityCode'        => $row->capacityCode,
			'capacityName'        => $row->capacityName,
			'capacityOptions'     => $capacitys,
			'addServices'         => $addServices,
			'basicChargeName'     => $row->basicChargeName,
			'chargeName'          => $row->chargeName,
			'benefitAssort'       => $row->benefitAssort,
			'benefitName'         => $benefitName,
			'factoryPrice'        => number_format($row->factoryPrice),
			'buyPrice'            => number_format($row->buyPrice),
			'monthInstFee'        => number_format($row->monthInstFee),
			'monthInstPrice'      => number_format($row->monthInstPrice),
			'installment'         => $row->installment,
			'discountType'        => $row->discountType,
			'discountTypeName'    => $discountTypeName,

			'cardCode'            => $row->cardCode,
			'cardName'            => $row->cardName,
			'cardDiscountPrice'   => number_format($row->cardDiscountPrice),

			'agencyId'            => $row->agencyId,
			'barCode'             => $row->barCode,
			'usimAssort'          => $row->usimAssort,
			'usimNo'              => $row->usimNo,
			'deliveryCompany'     => $row->deliveryCompany,
			'companyName'         => $companyName,
			'deliveryNo'          => $row->deliveryNo,
			'deliveryStatus'      => $row->deliveryStatus,
			'deliveryName'        => $deliveryName,
			'writeStatus'         => $row->writeStatus,
			'writeName'           => $writeName,
			'writeLink'           => $row->writeLink,
			'channelAssort'       => $row->channelAssort,
			'channelName'         => $channelName,
			'insuName'            => $row->insuName,

			'openingStatus'       => $row->openingStatus,
			'openingDate'         => $row->openingDate,
			'closeDate'           => $row->closeDate,
			'requestStatus'       => $row->requestStatus,
			'statusName'          => $statusName,

			'agencyPrice'         => number_format($row->agencyPrice),
			'commiPrice'          => number_format($row->commiPrice),
			'payPrice'            => number_format($row->payPrice),
			'commission'          => number_format($row->commission),
			'pointRemarks'        => $row->pointRemarks,

			'cash'                => number_format($cash),
			'bankCode'            => $bankCode,
			'bankName'            => $bankName,
			'accountName'         => $accountName,
			'accountNo'           => $accountNo,
			'comment'             => $row->comment,
			'adminMemo'           => $row->adminMemo,
			'wdate'               => $row->wdate,

			'deliveryForm'        => $deliveryForm,
			'openingForm'         => $openingForm,
			'discountData'        => $discountData,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result_status = "1";
	}

	// 단말기업체정보 
	$agencyOptions = array();
	$sql = "SELECT agencyId, agencyName FROM hp_agency WHERE telecoms LIKE '%$changeTelecom%' ORDER BY agencyName ASC";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$dta_info = array(
				'code' => $row[agencyId],
				'name' => $row[agencyName],
			);
			array_push($agencyOptions, $dta_info);
		}
	}

	// 배송업체정보 
	$companyOptions = array();
	$sql = "SELECT companyCode, companyName FROM delivery_company WHERE companyCode != '000' ORDER BY companyName ASC";
	$result = $connect->query($sql);

	$data_info = array(
		'code' => "000",
		'name' => "퀵서비스",
	);
	array_push($companyOptions, $data_info);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'code' => $row[companyCode],
				'name' => $row[companyName],
			);
			array_push($companyOptions, $data_info);
		}
	}

	// 택배조회 API 키값
	$sql = "SELECT apiKey FROM api_key WHERE useYn = 'Y' and assortCode = 'ST'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$deliveryKey = $row->apiKey;
	}

	// 관리자 메모
	$memoData = array();
    $sql = "SELECT idx, adminId, adminName, adminMemo, wdate FROM hp_request_memo WHERE requestIdx = '$idx' ORDER BY idx DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'idx'       => $row[idx],
				'adminId'   => $row[adminId],
				'adminName' => $row[adminName],
				'adminMemo' => $row[adminMemo],
				'wdate'     => $row[wdate],
			);
			array_push($memoData, $data_info);
		}
	}

	$response = array(
		'result'          => $result_status,
		'message'         => $result_message,
		'deliveryKey'     => $deliveryKey,
		'telecomOptions'  => $arrTelecomAssort,
		'assortOptions'   => $arrRequestAssort,
		'discountOptions' => $arrDiscountType3,
		'benefitOptions'  => $arrBenefitAssort,
		'usimOptions'     => $arrUsimAssort,
		'companyOptions'  => $companyOptions,
		'agencyOptions'   => $agencyOptions,
		'openingOptions'  => $arrOpeningStatus,
		'deliveryOptions' => $arrDeliveryStatus,
		'writeOptions'    => $arrWriteStatus,
		'channelOptions'  => $arrChannelAssort,
		'statusOptions'   => $arrRequestStatus2,
		'bankOptions'     => $arrBankCode,
		'data'            => $data,
		'memoData'        => $memoData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>