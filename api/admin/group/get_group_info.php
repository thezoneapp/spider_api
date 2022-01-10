<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 그룹관리 > 그룹 > 목록 > 상세정보
	* parameter ==> idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, groupCode, groupName, joinMethod, recommendOption, accurateMethod, 
	               companyName, ceoName, taxNo, email, telNo, address, bankCode, accountNo, accountName, 
				   mallRegistNo, manageName, domain, logo, logoFooter, useTerms, personTerms, memberTerms, useYn, wdate 
	        FROM group_info 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$groupCode = $row->groupCode;

		if ($row->email != "") $row->email = aes_decode($row->email);
		if ($row->telNo != "") $row->telNo = aes_decode($row->telNo);
		if ($row->accountNo != "") $row->accountNo = aes_decode($row->accountNo);

		// ******************************************* 회원구성 정보  *************************************
		$organizes = array();
		$sql = "SELECT idx, organizeCode, organizeName, memAssort, joinFeeYn, joinFee, recommendBonus, useYn 
				FROM group_organize 
				WHERE groupCode = '$groupCode' 
				ORDER BY idx ASC";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				$organizeIdx    = $row2[idx];
				$organizeCode   = $row2[organizeCode];
				$organizeName   = $row2[organizeName];
				$memAssort      = $row2[memAssort];
				$joinFeeYn      = $row2[joinFeeYn];
				$joinFee        = $row2[joinFee];
				$recommendBonus = $row2[recommendBonus];
				$useYn          = $row2[useYn];

				// 서비스이용료
				$sql = "SELECT idx, payType, subsFee, commiType, 
							   totalPayAssort, totalPayFee, hpPayAssort, hpPayFee, insuPayAssort, insuPayFee, rentalPayAssort, rentalPayFee 
						FROM group_organize_service 
						WHERE groupCode = '$groupCode' and organizeCode = '$organizeCode'";
				$result3 = $connect->query($sql);

				if ($result3->num_rows > 0) {
					$row3 = mysqli_fetch_object($result3);

					$service = array(
						'idx'             => $row3->idx,
						'payType'         => $row3->payType,
						'subsFee'         => $row3->subsFee,
						'commiType'       => $row3->commiType,
						'totalPayAssort'  => $row3->totalPayAssort,
						'totalPayFee'     => $row3->totalPayFee,
						'hpPayAssort'     => $row3->hpPayAssort,
						'hpPayFee'        => $row3->hpPayFee,
						'insuPayAssort'   => $row3->insuPayAssort,
						'insuPayFee'      => $row3->insuPayFee,
						'rentalPayAssort' => $row3->rentalPayAssort,
						'rentalPayFee'    => $row3->rentalPayFee,
					);

				} else {
					$service = array();
				}

				// 후원보너스
				$bonus = array();
				$sql = "SELECT idx, payType, subsSaveAssort, subsSaveFee, hpSaveAssort, hpSaveFee, 
							   insuSaveAssort, insuSaveFee, rentalSaveAssort, rentalSaveFee, rentalSaveAddAssort, rentalSaveAddFee 
						FROM group_organize_bonus 
						WHERE groupCode = '$groupCode' and organizeCode = '$organizeCode' 
						ORDER BY idx ASC";
				$result3 = $connect->query($sql);

				if ($result3->num_rows > 0) {
					while($row3 = mysqli_fetch_array($result3)) {
						$data_info = array(
							'idx'                 => $row3[idx],
							'payType'             => $row3[payType],
							'subsSaveAssort'      => $row3[subsSaveAssort],
							'subsSaveFee'         => $row3[subsSaveFee],
							'hpSaveAssort'        => $row3[hpSaveAssort],
							'hpSaveFee'           => $row3[hpSaveFee],
							'insuSaveAssort'      => $row3[insuSaveAssort],
							'insuSaveFee'         => $row3[insuSaveFee],
							'rentalSaveAssort'    => $row3[rentalSaveAssort],
							'rentalSaveFee'       => $row3[rentalSaveFee],
							'rentalSaveAddAssort' => $row3[rentalSaveAddAssort],
							'rentalSaveAddFee'    => $row3[rentalSaveAddFee],
						);
						array_push($bonus, $data_info);
					}
				}

				$data_info = array(
					'idx'            => $organizeIdx,
					'organizeCode'   => $organizeCode,
					'organizeName'   => $organizeName,
					'memAssort'      => $memAssort,
					'joinFeeYn'      => $joinFeeYn,
					'joinFee'        => $joinFee,
					'recommendBonus' => $recommendBonus,
					'useYn'          => $useYn,
					'service'        => $service,
					'bonuss'         => $bonus,
				);
				array_push($organizes, $data_info);
			}
		}

		// 그룹별 수수료 차감 정보
		$hpPrice     = 0;
		$rentalPrice = 0;
		$insuPrice   = 0;
		$sql = "SELECT hpPrice, rentalPrice, insuPrice FROM group_commi WHERE groupCode = '$groupCode'";
		$result4 = $connect->query($sql);

		if ($result4->num_rows > 0) {
			$row4 = mysqli_fetch_object($result4);
			$hpPrice     = $row4->hpPrice;
			$rentalPrice = $row4->rentalPrice;
			$insuPrice   = $row4->insuPrice;
		}

		$commission = array(
			'hpPrice'     => $hpPrice,
			'rentalPrice' => $rentalPrice,
			'insuPrice'   => $insuPrice,
		);

		// 그룹정보 최종
		$data = array(
			'idx'             => $row->idx,
			'groupCode'       => $row->groupCode,
			'groupName'       => $row->groupName,
			'joinMethod'      => $row->joinMethod,
			'recommendOption' => $row->recommendOption,
			'accurateMethod'  => $row->accurateMethod,
			'companyName'     => $row->companyName,
			'ceoName'         => $row->ceoName,
			'taxNo'           => $row->taxNo,
			'email'           => $row->email,
			'telNo'           => $row->telNo,
			'address'         => $row->address,
			'bankCode'        => $row->bankCode,
			'accountNo'       => $row->accountNo,
			'accountName'     => $row->accountName,
			'mallRegistNo'    => $row->mallRegistNo,
			'manageName'      => $row->manageName,
			'domain'          => $row->domain,
			'logo'            => $row->logo,
			'logoFooter'      => $row->logoFooter,
			'useTerms'        => $row->useTerms,
			'personTerms'     => $row->personTerms,
			'memberTerms'     => $row->memberTerms,
			'useYn'           => $row->useYn,
			'wdate'           => $row->wdate,
			'organizes'       => $organizes,
			'commission'      => $commission,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		$goodsCode = "";
		$organizes = array();

		$commission = array(
			'hpPrice'     => 0,
			'rentalPrice' => 0,
			'insuPrice'   => 0,
		);

		$data = array(
			'idx'             => "",
			'groupCode'       => "",
			'groupName'       => "",
			'joinMethod'      => "",
			'recommendOption' => "",
			'accurateMethod'  => "",
			'companyName'     => "",
			'ceoName'         => "",
			'taxNo'           => "",
			'email'           => "",
			'telNo'           => "",
			'address'         => "",
			'bankCode'        => "",
			'accountNo'       => "",
			'accountName'     => "",
			'mallRegistNo'    => "",
			'manageName'      => "",
			'useTerms'        => "",
			'personTerms'     => "",
			'memberTerms'     => "",
			'useYn'           => "",
			'wdate'           => "",
			'organizes'       => $organizes,
			'commission'      => $commission,
		);

		// 추가모드로 결과를 반환합니다.
		$result_status = "1";
	}

	// 최종 결과
	$response = array(
		'result'            => $result_status,
		'data'              => $data,
		'memAssortOptions'  => $arrMemAssort,
		'joinMethodOptions' => $arrJoinMethod,
		'recommendOptions'  => $arrRecommendOption,
		'accurateOptions'   => $arrAccurateMethod,
		'bankOptions'       => $arrBankCode,
		'yesNoOptions'      => $arrYesNo,
		'useOptions'        => $arrUseAssort,
		'payTypeOptions'    => $arrPayType,
		'commiTypeOptions'  => $arrCommiType,
		'rateOptions'       => $arrRateAssort,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>