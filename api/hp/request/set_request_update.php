<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰신청 수정
	* parameter ==> 
		idx  :          신청서 일련번호
		adminId:        관리자ID
		memId:          회원ID
		memName:        회원명

		custName:       고객명
		birthday:       생년월일
		hpNo:           휴대폰번호
		addHpNo:        추가연락번호
		postCode:       우편번호
		addr1:          배송지-기본주소
		add2r:          배송지-상세주소

		benefitAssort:  할인혜택
		buyPrice:       할부원금

		commiPrice:     정책포인트
		commission:     정산포인트
		pointRemarks:   포인트 비고

		bankCode:       은행코드
		accountName:    예금주
		accountNo:      계좌번호
		cash:           캐시

		agencyId:       단말기출고업체
		barCode:        단말기 바코드
		usimAssort:     유심구매여부
		usimNo:         유심 일련번호
		insuName:       보험명
		channelAssort:  접수채널
		writeStatus:    신청서작성여부
		writeLink:      가입신청서 링크

		requestStatus:  신청상태
		adminMemo:      관리자메모
		discountData:   할인정보 배열
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$idx             = $input_data->{'idx'};
	$adminId         = $input_data->{'adminId'};
	$memId           = $input_data->{'memId'};
	$memName         = $input_data->{'memName'};
	$custName        = $input_data->{'custName'};
	$birthday        = $input_data->{'birthday'};
	$hpNo            = $input_data->{'hpNo'};
	$addHpNo         = $input_data->{'addHpNo'};
	$postCode        = $input_data->{'postCode'};
	$addr1           = $input_data->{'addr1'};
	$addr2           = $input_data->{'addr2'};
	$benefitAssort   = $input_data->{'benefitAssort'};
	$buyPrice        = $input_data->{'buyPrice'};

	$agencyPrice     = $input_data->{'agencyPrice'};
	$commiPrice      = $input_data->{'commiPrice'};
	$payPrice        = $input_data->{'payPrice'};
	$commission      = $input_data->{'commission'};
	$pointRemarks    = $input_data->{'pointRemarks'};

	$bankCode        = $input_data->{'bankCode'};
	$accountName     = $input_data->{'accountName'};
	$accountNo       = $input_data->{'accountNo'};
	$cash            = $input_data->{'cash'};

	$requestStatus   = $input_data->{'requestStatus'};
	
	$agencyId        = $input_data->{'agencyId'};
	$barCode         = $input_data->{'barCode'};
	$usimAssort      = $input_data->{'usimAssort'};
	$usimNo          = $input_data->{'usimNo'};
	$deliveryCompany = $input_data->{'deliveryCompany'};
	$deliveryNo      = $input_data->{'deliveryNo'};
	$deliveryStatus  = $input_data->{'deliveryStatus'};
	$insuName        = $input_data->{'insuName'};
	$channelAssort   = $input_data->{'channelAssort'};
	$writeStatus     = $input_data->{'writeStatus'};
	$writeLink       = $input_data->{'writeLink'};

	$adminMemo       = $input_data->{'adminMemo'};

	$arrDiscountData = $input_data->{'discountData'};

	$benefitAssort   = $benefitAssort->{'code'};
	$bankCode        = $bankCode->{'code'};
	$channelAssort   = $channelAssort->{'code'};
	$writeStatus     = $writeStatus->{'code'};
	$deliveryStatus  = $deliveryStatus->{'code'};
	$requestStatus   = $requestStatus->{'code'};
	$agencyId        = $agencyId->{'agencyId'};

	if ($birthday != "") $birthday = aes128encrypt($birthday);
	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
	if ($addHpNo != "") $addHpNo = aes128encrypt($addHpNo);
	if ($accountNo != "") $accountNo = aes128encrypt($accountNo);

	if ($agencyPrice != "") $agencyPrice = str_replace(",", "", $agencyPrice);
	if ($commiPrice != "") $commiPrice = str_replace(",", "", $commiPrice);
	if ($payPrice != "") $payPrice = str_replace(",", "", $payPrice);
	if ($commission != "") $commission = str_replace(",", "", $commission);
	if ($cash != "") $cash = str_replace(",", "", $cash);
	if ($buyPrice != "") $buyPrice = str_replace(",", "", $buyPrice);

	// 신청정보 업데이트
	$sql = "UPDATE hp_request SET custName = '$custName', 
								  birthday = '$birthday', 
								  hpNo = '$hpNo', 
								  benefitAssort = '$benefitAssort', 
								  buyPrice = '$buyPrice', 
								  agencyPrice = '$agencyPrice', 
								  commiPrice = '$commiPrice', 
								  payPrice = '$payPrice', 
								  commission = '$commission', 
								  pointRemarks = '$pointRemarks', 
								  requestStatus = '$requestStatus', 
								  barCode = '$barCode', 
								  usimAssort = '$usimAssort', 
								  usimNo = '$usimNo', 
								  insuName = '$insuName', 
								  channelAssort = '$channelAssort', 
								  writeStatus = '$writeStatus', 
								  writeLink = '$writeLink', 
								  deliveryStatus = '$deliveryStatus', 
								  comment = '$comment', 
								  adminMemo = '$adminMemo'
			WHERE idx = '$idx'";
	$connect->query($sql);

	// 할인정보 업데이트
	for ($i = 0; count($arrDiscountData) > $i; $i++) {
		$Discount = $arrDiscountData[$i];

		$discountIdx   = $Discount->idx;
		$discountPrice = $Discount->discountPrice;

		if ($discountPrice != "") $discountPrice = str_replace(",", "", $discountPrice);

		$sql = "UPDATE hp_request_discount SET discountPrice = '$discountPrice' WHERE idx = '$discountIdx'";
		$connect->query($sql);
	}

	// 캐시백정보 업데이트
	$sql = "SELECT idx FROM hp_cash_back WHERE requestIdx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows == 0) {
		if ($cash != "0" && $cash != "") {
			$sql = "INSERT INTO hp_cash_back (requestIdx, cash, bankCode, accountName, accountNo) 
									  VALUES ('$idx', '$cash', '$bankCode', '$accountName', '$accountNo')";
			$connect->query($sql);
		}

	} else {
		$sql = "UPDATE hp_cash_back SET cash = '$cash', 
										bankCode = '$bankCode', 
										accountName = '$accountName', 
										accountNo = '$accountNo' 
				WHERE requestIdx = '$idx'";
		$connect->query($sql);
	}

	// 배송지 정보 업데이트
	$sql = "SELECT idx FROM hp_delivery WHERE requestIdx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows == 0) {
		$sql = "INSERT INTO hp_delivery (requestIdx, addHpNo, postCode, addr1, addr2) 
		                         VALUES ('$idx', '$addHpNo', '$postCode', '$addr1', '$addr2')";
		$connect->query($sql);

	} else {
		$row = mysqli_fetch_object($result);
		$requestIdx = $row->idx;

		$sql = "UPDATE hp_delivery SET addHpNo = '$addHpNo', 
									   postCode = '$postCode', 
									   addr1 = '$addr1', 
									   addr2 = '$addr2' 
				WHERE idx = '$requestIdx'";
		$connect->query($sql);
	}

	$result_status = "0";
	$result_message = "저장되었습니다.";

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>