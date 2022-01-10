<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰신청 > 업체관리 > 수수료정책 > 복사등록
	* parameter
		agencyId:   공급사ID,
		policyDate: 정책일자
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$agencyId   = $data_back->{'agencyId'};
	$policyDate = $data_back->{'policyDate'};
//$agencyId = 'pantaca';
//$policyDate = '2021-11-29 10:00';

	// 복사할 정책일자 검색
	$sql = "SELECT policyDate 
			FROM hp_agency_commi 
			WHERE agencyId = '$agencyId' 
			GROUP BY policyDate 
			ORDER BY policyDate DESC 
			LIMIT 1";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$targetDate = $row->policyDate;

		$sql = "SELECT telecom, discountType, modelCode, priceNew, newUseYn, priceMnp, mnpUseYn, priceChange, changeUseYn
				FROM hp_agency_commi  
				WHERE agencyId = '$agencyId' and policyDate = '$targetDate'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				$telecom      = $row2[telecom];
				$discountType = $row2[discountType];
				$modelCode    = $row2[modelCode];
				$priceNew     = $row2[priceNew];
				$newUseYn     = $row2[newUseYn];
				$priceMnp     = $row2[priceMnp];
				$mnpUseYn     = $row2[mnpUseYn];
				$priceChange  = $row2[priceChange];
				$changeUseYn  = $row2[changeUseYn];

				$sql = "INSERT INTO hp_agency_commi (agencyId, policyDate, telecom, discountType, modelCode, priceNew, newUseYn, priceMnp, mnpUseYn, priceChange, changeUseYn, wdate)
											 VALUES ('$agencyId', '$policyDate', '$telecom', '$discountType', '$modelCode', '$priceNew', '$newUseYn', '$priceMnp', '$mnpUseYn', '$priceChange', '$changeUseYn', now())";
				$connect->query($sql);

			}
		}

		// 결과를 반환합니다.
		$result_status = "0";
		$result_message = "등록하였습니다.";

	} else {
		// 결과를 반환합니다.
		$result_status = "1";
		$result_message = "복사할 정책이 없습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>