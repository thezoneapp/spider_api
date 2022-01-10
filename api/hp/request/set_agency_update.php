<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/kakaoTalk.php";

	/*
	* 관리자페이지 > 휴대폰신청 > 배송정보변경
	* parameter 
		idx  :     신청서 일련번호
		agencyId:  관리자ID
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$idx        = $input_data->{'idx'};
	$agencyId   = $input_data->{'agencyId'};

	$agencyId = $agencyId->{'code'};

	//$idx      = "1478";
	//$agencyId = "HMC_KT4";

	// 신청정보
	$sql = "SELECT m.groupCode, m.organizeCode, m.payType, hr.requestAssort, hr.modelCode, hr.changeTelecom, hr.discountType, hr.factoryPrice, 
				   hr.marginPrice, hr.openingCheck, hr.agencyPrice, hr.commiPrice, hr.payPrice, hr.commission 
			FROM hp_request hr 
				 INNER JOIN member m ON hr.memId = m.memId 
			WHERE hr.idx = '$idx'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$groupCode     = $row->groupCode;
		$organizeCode  = $row->organizeCode;
		$payType       = $row->payType;
		$requestAssort = $row->requestAssort;
		$modelCode     = $row->modelCode;
		$telecom       = $row->changeTelecom;
		$discountType  = $row->discountType;
		$marginPrice   = $row->marginPrice;
		$factoryPrice  = $row->factoryPrice;
		$openingCheck  = $row->openingCheck;

		$tmpAgencyPrice = $row->agencyPrice;
		$tmpCommiPrice  = $row->commiPrice;
		$tmpPayPrice    = $row->payPrice;
		$tmpMarginPrice = $row->commission;

		// 그룹 마진 정보
		$groupMarginPrice = 0;
		$sql = "SELECT hpPrice FROM group_commi WHERE groupCode = '$groupCode'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);
			$groupMarginPrice = $row->hpPrice;

		} else $groupMarginPrice = 0;

		// 그룹정보 > 회원구성정보 > 서비스정보
		$sql = "SELECT commiType, totalPayAssort, totalPayFee, hpPayAssort, hpPayFee 
				FROM group_organize_service 
				WHERE groupCode = '$groupCode' AND organizeCode = '$organizeCode'";
		$result2 = $connect->query($sql);
		$row2 = mysqli_fetch_object($result2);

		if ($row2->commiType == "B") { // 이용수수료(일괄적용)
			$payAssort = $row2->totalPayAssort;
			$payFee    = $row2->totalPayFee;

		} else if ($row2->commiType == "E") { // 이용수수료(건별적용)
			$payAssort = $row2->hpPayAssort;
			$payFee    = $row2->hpPayFee;
		} else {
			$payAssort = "";
			$payFee    = "0";
		}		

		// 공급업체의 수수료 정보정보
		$sql = "SELECT priceNew, priceMnp, priceChange 
				FROM hp_agency_commi 
				WHERE agencyId = '$agencyId' and modelCode = '$modelCode' AND telecom = '$telecom' AND discountType = '$discountType' 
				ORDER BY policyDate DESC
				LIMIT 1";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);

			if ($requestAssort == "N") $agencyPrice = $row2->priceNew;
			else if ($requestAssort == "M") $agencyPrice = $row2->priceMnp;
			else if ($requestAssort == "C") $agencyPrice = $row2->priceChange;
			else $agencyPrice = "0";

			// 그릅 마진 차감
			if ($groupMarginPrice != "") {
				if ($agencyPrice != 0) $commiPrice = $agencyPrice - $groupMarginPrice;
				else $commiPrice = 0;

			} else {
				$commiPrice = 0;
			}

			// 서비스이용료
			if ($payType == "S") {  // 납부방식 > 구독료
				$payPrice = 0;

			} else if ($payType == "C") {  // 납부방식 > 이용수수료
				if ($payAssort == "R") { // 정율차감
					if ($commiPrice > 0) $payPrice = $commiPrice * ($payFee / 100);
					else $payPrice = 0;

				} else { // 정액차감
					if ($commiPrice > 0) $payPrice = $payFee;
					else $payPrice = 0;
				}

				// 백원 단위로 절삭
				$payPrice = (int) $payPrice / 100;
				$payPrice = $payPrice * 100;

				// 수수료 = 수수료 - 이용수수료
				$commission = $commiPrice - $payPrice;
			}

			// 사업자 할인금액
			$sql = "SELECT discountPrice FROM hp_request_discount WHERE requestIdx = '$idx' and discountAssort = 'A'";
			$result3 = $connect->query($sql);

			if ($result3->num_rows > 0) {
				$row3 = mysqli_fetch_object($result3);
				$discountPrice = $row3->discountPrice;

			} else {
				$discountPrice = 0;
			}

			// 사업자 마진 금액
			$marginPrice = $commiPrice - $payPrice + $discountPrice;

			if ($marginPrice < 0) {
				$discountPrice =  $discountPrice - $marginPrice;
				$marginPrice = 0;

				// 할인 변경 저장
				$sql = "UPDATE discountPrice SET hp_request_discount = '$discountPrice' WHERE requestIdx = '$idx' and discountAssort = 'A'"; 
				$connect->query($sql);
			}

			// 변경 저장
			if ($openingCheck != "Y") {
				$sql = "UPDATE hp_request SET agencyId = '$agencyId', 
											  agencyPrice = '$agencyPrice', 
											  payPrice = '$payPrice', 
											  commission = '$marginPrice' 
							WHERE idx = '$idx'";
				$connect->query($sql);

			} else {
				$sql = "UPDATE hp_request SET agencyId = '$agencyId' 
							WHERE idx = '$idx'";
				$connect->query($sql);

				$agencyPrice = $tmpAgencyPrice;
				$commiPrice  = $tmpCommiPrice;
				$payPrice    = $tmpPayPrice;
				$marginPrice = $tmpMarginPrice;
			}

			$result_status = "0";
			$result_message = "저장되었습니다.";

		} else {
			$sql = "UPDATE hp_request SET agencyId = '$agencyId' 
						WHERE idx = '$idx'";
			$connect->query($sql);

			$agencyPrice = $tmpAgencyPrice;
			$commiPrice  = $tmpCommiPrice;
			$payPrice    = $tmpPayPrice;
			$marginPrice = $tmpMarginPrice;

			$result_status = "1";
			$result_message = "해당업체의 '수수료정보'가 없습니다.";
		}

	} else {
		$result_status = "1";
		$result_message = "신청서가 존재하지 않습니다.";
	}

	$response = array(
		'result'      => $result_status,
		'message'     => $result_message,
		'agencyPrice' => number_format($agencyPrice),
		'commiPrice'  => number_format($commiPrice),
		'payPrice'    => number_format($payPrice),
		'commission'  => number_format($marginPrice),
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>