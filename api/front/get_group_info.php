<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 프론트 > 그룹정보
	*/

	$domain    = $_SERVER['HTTP_REFERER'];
	$domain    = str_replace("https://", "", $domain);
	$domain    = str_replace("http://", "", $domain);
	$domain    = str_replace("www.", "", $domain);
	$arrDomain = explode("/", $domain);
	$domain    = $arrDomain[0];

	// 그룹코드
	$groupCode = getGroupCode($domain);

	if (strpos($domain, "localhost") !== false) $domain = "spiderplatform.co.kr";

	// 그룹 정보
	$sql = "SELECT groupName, joinMethod, recommendOption, companyName, address, mallRegistNo, manageName, bankCode, accountNo, accountName, 
	               logo, logoFooter, useTerms, personTerms, memberTerms 
			FROM group_info 
			WHERE groupCode = '$groupCode'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->accountNo != "") $row->accountNo = aes_decode($row->accountNo);

		$bankName = selected_object($row->bankCode, $arrBankCode);

		// 회원구성정보
		$organizes = array();
		$sql = "SELECT idx, organizeCode, organizeName, memAssort, joinFeeYn, joinFee, recommendBonus, useYn 
				FROM group_organize 
				WHERE groupCode = '$groupCode' and useYn = 'Y' 
				ORDER BY idx ASC";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				$organizeCode   = $row2[organizeCode];
				$organizeName   = $row2[organizeName];
				$memAssort      = $row2[memAssort];
				$joinFeeYn      = $row2[joinFeeYn];
				$joinFee        = $row2[joinFee];
				$recommendBonus = $row2[recommendBonus];

				// 서비스이용료
				$sql = "SELECT idx, payType, subsFee, commiType, 
							   totalPayAssort, totalPayFee, hpPayAssort, hpPayFee, insuPayAssort, insuPayFee, rentalPayAssort, rentalPayFee 
						FROM group_organize_service 
						WHERE groupCode = '$groupCode' and organizeCode = '$organizeCode'";
				$result3 = $connect->query($sql);

				if ($result3->num_rows > 0) {
					$row3 = mysqli_fetch_object($result3);

					$service = array(
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

		$data = array(
			'groupName'       => $row->groupName,
			'joinMethod'      => $row->joinMethod,
			'recommendOption' => $row->recommendOption,
			'companyName'     => $row->companyName,
			'address'         => $row->address,
			'mallRegistNo'    => $row->mallRegistNo,
			'manageName'      => $row->manageName,
			'bankName'        => $bankName,
			'accountNo'       => $row->accountNo,
			'accountName'     => $row->accountName,
			'favicon'         => "https://" . $domain . "/upload/group/favicon.ico",
			'logo'            => "https://" . $domain . "/". $row->logo,
			'logoFooter'      => "https://" . $domain . "/". $row->logoFooter,
			'useTerms'        => $row->useTerms,
			'personTerms'     => $row->personTerms,
			'memberTerms'     => $row->memberTerms,
			'organizes'       => $organizes,
		);
		
		// 성공 결과를 반환합니다.
		$result_status = "0";

	} else {
		$data = array();
		$result_status = "1";
	}

	$response = array(
		'result' => $result_status,
		'data'   => $data,
    );
//print_r($response);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>