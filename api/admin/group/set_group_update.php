<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 그룹관리 > 그룹 > 목록 > 상세정보 > 추가/수정
	* parameter
		mode:            insert(추가), update(수정)
		idx:             수정할 레코드 id
		groupCode:       그룹코드
		groupName:       그룹명
		joinMethod:      가입방식(R: 추천가입, O: 오픈가입)
		recommendOption: 추천가입옵션(D: 다이렉트, N: 네트워크)
		accurateMethod:  정산방식(E: 개별정산, B: 일괄정산)
		companyName:     회사명
		ceoName:         대표자명
		taxNo:           사업자번호
		email:           이메일
		telNo:           대표전화번호
		address:         주소
		bankCode:        거래은행코드
		accountNo:       계좌번호
		accountName:     예금주명
		domain:          도메인
		logo:            로고
		logoFooter:      로고(Footer용)
		personTerms:     개인정보취급방침
		memberTerms:     회원약관
		useTerms:        이용약관
		personTerms:     개인정보취급방침
		memberTerms:     회원약관
		useYn:           사용여부
		organizes:       회원구성정보 배열
	*/
	$data_back       = json_decode(file_get_contents('php://input'));
	$mode            = $data_back->{'mode'};
	$idx             = $data_back->{'idx'};
	$groupCode       = $data_back->{'groupCode'};
	$groupName       = $data_back->{'groupName'};
	$joinMethod      = $data_back->{'joinMethod'};
	$recommendOption = $data_back->{'recommendOption'};
	$accurateMethod  = $data_back->{'accurateMethod'};
	$companyName     = $data_back->{'companyName'};
	$ceoName         = $data_back->{'ceoName'};
	$taxNo           = $data_back->{'taxNo'};
	$email           = $data_back->{'email'};
	$telNo           = $data_back->{'telNo'};
	$address         = $data_back->{'address'};
	$bankCode        = $data_back->{'bankCode'};
	$accountNo       = $data_back->{'accountNo'};
	$accountName     = $data_back->{'accountName'};
	$mallRegistNo    = $data_back->{'mallRegistNo'};
	$manageName      = $data_back->{'manageName'};
	$domain          = $data_back->{'domain'};
	$logo            = $data_back->{'logo'};
	$logoFooter      = $data_back->{'logoFooter'};
	$useTerms        = $data_back->{'useTerms'};
	$personTerms     = $data_back->{'personTerms'};
	$memberTerms     = $data_back->{'memberTerms'};
	$useYn           = $data_back->{'useYn'};
	$commission      = $data_back->{'commission'};
	$arrOrganizes    = $data_back->{'organizes'};

	$bankCode = $bankCode->code;

	if ($email != "") $email = aes128encrypt($email);
	if ($telNo != "") $telNo = aes128encrypt($telNo);
	if ($accountNo != "") $accountNo = aes128encrypt($accountNo);

	if ($mode == "insert") {
		$sql = "SELECT idx FROM group_info WHERE groupCode = '$groupCode'";
		$result = $connect->query($sql);

	    if ($result->num_rows == 0) {
			$sql = "INSERT INTO group_info (groupCode, groupName, joinMethod, recommendOption, accurateMethod, 
	                                        companyName, ceoName, taxNo, email, telNo, address, bankCode, accountNo, accountName, 
				                            mallRegistNo, manageName, domain, logo, logoFooter, useTerms, personTerms, memberTerms, useYn, wdate) 
							        VALUES ('$groupCode', '$groupName', '$joinMethod', '$recommendOption', '$accurateMethod', 
	                                        '$companyName', '$ceoName', '$taxNo', '$email', '$telNo', '$address', '$bankCode', '$accountNo', '$accountName', 
				                            '$mallRegistNo', '$manageName', '$domain', '$logo', '$logoFooter', '$useTerms', '$personTerms', '$memberTerms', '$useYn', now())";
			$connect->query($sql);

			// 루트 회원 등록
			$sql = "INSERT INTO member (groupCode, recommendId, memId, memName, memAssort, memStatus, wdate)
							    VALUES ('$groupCode', '$groupCode', '$groupCode', '$groupName', 'M', '9', now())";
			$connect->query($sql);

			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "등록하였습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "중복된 '그룹코드'입니다.";
		}

	} else {
		$sql = "UPDATE group_info SET groupCode = '$groupCode', 
								      groupName = '$groupName', 
								      joinMethod = '$joinMethod', 
								      recommendOption = '$recommendOption', 
								      accurateMethod = '$accurateMethod', 
								      companyName = '$companyName', 
								      ceoName = '$ceoName', 
								      taxNo = '$taxNo', 
								      email = '$email', 
								      telNo = '$telNo', 
								      address = '$address', 
								      bankCode = '$bankCode', 
								      accountNo = '$accountNo', 
								      accountName = '$accountName', 
								      mallRegistNo = '$mallRegistNo', 
								      manageName = '$manageName', 
								      domain = '$domain', 
								      logo = '$logo', 
								      logoFooter = '$logoFooter', 
								      useTerms = '$useTerms', 
								      personTerms = '$personTerms', 
								      memberTerms = '$memberTerms', 
								      useYn = '$useYn' 
						WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "변경하였습니다.";
	}

	// 그룹정보에 이상이 없으면
	if ($result_status == "0") {
		// ******************************** 회원구성 *********************************************
		$sql = "UPDATE group_organize SET updateCheck = 'Y' WHERE groupCode = '$groupCode'";
		$connect->query($sql);

		for ($i = 0; count($arrOrganizes) > $i; $i++) {
			$organize = $arrOrganizes[$i];

			$idx            = $organize->idx;
			$organizeCode   = $organize->organizeCode;
			$organizeName   = $organize->organizeName;
			$memAssort      = $organize->memAssort;
			$joinFeeYn      = $organize->joinFeeYn;
			$joinFee        = $organize->joinFee;
			$recommendBonus = $organize->recommendBonus;
			$useYn          = $organize->useYn;

			$service        = $organize->service;
			$arrBonus       = $organize->bonuss;

			if ($joinFee == "") $joinFee = "0";
			else $joinFee = str_replace(",", "", $joinFee);

			if ($recommendBonus == "") $recommendBonus = "0";
			else $recommendBonus = str_replace(",", "", $recommendBonus);

			if ($idx == 0) {
				// organizeCode 생성
				$sql = "SELECT ifnull(max(idx),0) + 1 AS maxIdx FROM group_organize";
				$result2 = $connect->query($sql);
				$row2 = mysqli_fetch_object($result2);
				$organizeCode = $row2->maxIdx;

				$sql = "INSERT INTO group_organize (groupCode, organizeCode, organizeName, memAssort, joinFeeYn, joinFee, recommendBonus, useYn)
									        VALUES ('$groupCode', '$organizeCode', '$organizeName', '$memAssort', '$joinFeeYn', '$joinFee', '$recommendBonus', '$useYn')";
				$connect->query($sql);

			} else {
				$sql = "UPDATE group_organize SET organizeName = '$organizeName', 
											      memAssort = '$memAssort', 
											      joinFeeYn = '$joinFeeYn', 
											      joinFee = '$joinFee', 
											      recommendBonus = '$recommendBonus', 
												  useYn = '$useYn', 
											      updateCheck = 'N' 
								WHERE idx = '$idx'";
				$connect->query($sql);
			}

			// -------------------------------- 서비스이용정보 ---------------------------------------------------
			$sql = "UPDATE group_organize_service SET updateCheck = 'Y' WHERE groupCode = '$groupCode' and organizeCode = '$organizeCode'";
			$connect->query($sql);

			$idx             = $service->idx;
			$payType         = $service->payType;
			$subsFee         = $service->subsFee;
			$commiType       = $service->commiType;
			$totalPayAssort  = $service->totalPayAssort;
			$totalPayFee     = $service->totalPayFee;
			$hpPayAssort     = $service->hpPayAssort;
			$hpPayFee        = $service->hpPayFee;
			$insuPayAssort   = $service->insuPayAssort;
			$insuPayFee      = $service->insuPayFee;
			$rentalPayAssort = $service->rentalPayAssort;
			$rentalPayFee    = $service->rentalPayFee;

			if ($subsFee == "") $subsFee = "0";
			else $subsFee = str_replace(",", "", $subsFee);

			if ($totalPayFee == "") $totalPayFee = "0";
			else $totalPayFee = str_replace(",", "", $totalPayFee);

			if ($hpPayFee == "") $hpPayFee = "0";
			else $hpPayFee = str_replace(",", "", $hpPayFee);

			if ($insuPayFee == "") $insuPayFee = "0";
			else $insuPayFee = str_replace(",", "", $insuPayFee);

			if ($rentalPayFee == "") $rentalPayFee = "0";
			else $rentalPayFee = str_replace(",", "", $rentalPayFee);

			if ($idx == 0) {
				$sql = "INSERT INTO group_organize_service (groupCode, organizeCode, payType, subsFee, commiType, totalPayAssort, totalPayFee, hpPayAssort, hpPayFee, 
				                                            insuPayAssort, insuPayFee, rentalPayAssort, rentalPayFee, updateCheck)
											        VALUES ('$groupCode', '$organizeCode', '$payType', '$subsFee', '$commiType', '$totalPayAssort', '$totalPayFee', '$hpPayAssort', '$hpPayFee', 
				                                            '$insuPayAssort', '$insuPayFee', '$rentalPayAssort', '$rentalPayFee', 'N')";
				$connect->query($sql);

			} else {
				$sql = "UPDATE group_organize_service SET payType = '$payType', 
													      subsFee = '$subsFee', 
													      commiType = '$commiType', 
													      totalPayAssort = '$totalPayAssort', 
													      totalPayFee = '$totalPayFee', 
													      hpPayAssort = '$hpPayAssort', 
													      hpPayFee = '$hpPayFee', 
													      insuPayAssort = '$insuPayAssort', 
													      insuPayFee = '$insuPayFee', 
													      rentalPayAssort = '$rentalPayAssort', 
												    	  rentalPayFee = '$rentalPayFee', 
													      updateCheck = 'N' 
								WHERE idx = '$idx'";
				$connect->query($sql);
			}

			$sql = "DELETE FROM group_service_free WHERE groupCode = '$groupCode' and organizeCode = '$organizeCode' and updateCheck = 'Y'";
			$connect->query($sql);

			// -------------------------------- 후원보너스정보 ---------------------------------------------------
			$sql = "UPDATE group_organize_bonus SET updateCheck = 'Y' WHERE groupCode = '$groupCode' and organizeCode = '$organizeCode'";
			$connect->query($sql);

			for ($n = 0; count($arrBonus) > $n; $n++) {
				$bonus = $arrBonus[$n];

				$idx                 = $bonus->idx;
				$payType             = $bonus->payType;
				$subsSaveAssort      = $bonus->subsSaveAssort;
				$subsSaveFee         = $bonus->subsSaveFee;
				$hpSaveAssort        = $bonus->hpSaveAssort;
				$hpSaveFee           = $bonus->hpSaveFee;
				$insuSaveAssort      = $bonus->insuSaveAssort;
				$insuSaveFee         = $bonus->insuSaveFee;
				$rentalSaveAssort    = $bonus->rentalSaveAssort;
				$rentalSaveFee       = $bonus->rentalSaveFee;
				$rentalSaveAddAssort = $bonus->rentalSaveAddAssort;
				$rentalSaveAddFee    = $bonus->rentalSaveAddFee;

				if ($subsSaveFee == "") $subsSaveFee = "0";
				else $subsSaveFee = str_replace(",", "", $subsSaveFee);

				if ($hpSaveFee == "") $hpSaveFee = "0";
				else $hpSaveFee = str_replace(",", "", $hpSaveFee);

				if ($insuSaveFee == "") $insuSaveFee = "0";
				else $insuSaveFee = str_replace(",", "", $insuSaveFee);

				if ($rentalSaveFee == "") $rentalSaveFee = "0";
				else $rentalSaveFee = str_replace(",", "", $rentalSaveFee);

				if ($rentalSaveAddFee == "") $rentalSaveAddFee = "0";
				else $rentalSaveAddFee = str_replace(",", "", $rentalSaveAddFee);

				$sql = "SELECT idx FROM group_organize_bonus WHERE idx = '$idx'";
				$result = $connect->query($sql);

				if ($idx == 0) {
					$sql = "INSERT INTO group_organize_bonus (groupCode, organizeCode, payType, subsSaveAssort, subsSaveFee, hpSaveAssort, hpSaveFee, 
					                                          insuSaveAssort, insuSaveFee, rentalSaveAssort, rentalSaveFee, rentalSaveAddAssort, rentalSaveAddFee, updateCheck)
												      VALUES ('$groupCode', '$organizeCode', '$payType', '$subsSaveAssort', '$subsSaveFee', '$hpSaveAssort', 
			                                                  '$hpSaveFee', '$insuSaveAssort', '$insuSaveFee', '$rentalSaveAssort', '$rentalSaveFee', '$rentalSaveAddAssort', '$rentalSaveAddFee', 'N')";
					$connect->query($sql);

				} else {
					$sql = "UPDATE group_organize_bonus SET payType = '$payType', 
														    subsSaveAssort = '$subsSaveAssort', 
														    subsSaveFee = '$subsSaveFee', 
														    hpSaveAssort = '$hpSaveAssort', 
														    hpSaveFee = '$hpSaveFee', 
														    insuSaveAssort = '$insuSaveAssort', 
														    insuSaveFee = '$insuSaveFee', 
														    rentalSaveAssort = '$rentalSaveAssort', 
														    rentalSaveFee = '$rentalSaveFee', 
															rentalSaveAddAssort = '$rentalSaveAddAssort', 
															rentalSaveAddFee = '$rentalSaveAddFee', 
														    updateCheck = 'N' 
									WHERE idx = '$idx'";
					$connect->query($sql);
				}
			}

			$sql = "DELETE FROM group_organize_bonus WHERE groupCode = '$groupCode' and organizeCode = '$organizeCode' and updateCheck = 'Y'";
			$connect->query($sql);
		}

		$sql = "DELETE FROM group_organize WHERE groupCode = '$groupCode' and updateCheck = 'Y'";
		$connect->query($sql);

		// 그룹별 수수료 정보
		$hpPrice     = $commission->hpPrice;
		$rentalPrice = $commission->rentalPrice;
		$insuPrice   = $commission->insuPrice;

		$sql = "SELECT idx FROM group_commi WHERE groupCode = '$groupCode'";
		$result = $connect->query($sql);

		if ($result->num_rows == 0) {
			$sql = "INSERT INTO group_commi (groupCode, hpPrice, rentalPrice, insuPrice) 
			                         VALUES ('$groupCode', '$hpPrice', '$rentalPrice', '$insuPrice')";
			$connect->query($sql);
		} else {
			$sql = "UPDATE group_commi SET hpPrice = '$hpPrice', 
			                               rentalPrice = '$rentalPrice',
										   insuPrice = '$insuPrice' 
						WHERE groupCode = '$groupCode'";
			$connect->query($sql);
		}
	}

	// 결과 리턴
	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>