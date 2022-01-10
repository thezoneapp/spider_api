<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰신청 > 수수료 목록 > 통신사별
	* parameter
		memId:       회원ID
		telecom:     통신사
		assort:      할인구분(S: 공시지원, C: 요금할인)
		payType:     서비스이용료 > 납부방식: (S: CMS정기결제, C: 이용수수료)
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId      = $input_data->{'memId'};
	$telecom    = $input_data->{'telecom'};
	$assort     = $input_data->{'assort'};
	$payType    = $input_data->{'payType'};

	//if ($userId == null | $userId == "") $userId = "a27233377";
	if ($telecom == null | $telecom == "") $telecom = "S";
	if ($assort == null | $assort == "") $assort = "S";
	if ($payType == null | $payType == "") $payType = "S";

	if ($telecom != "") $telecom_sql = "and hc.telecom = '$telecom' ";
	else $telecom_sql = "";

	if ($assort != "") $assort_sql = "and hc.assortCode = '$assort' ";
	else $assort_sql = "";

	// 서비스이용료 > 납부방식 > 이용수수료
	if ($payType == "C") {
		// 회원정보
		$sql = "SELECT groupCode, organizeCode FROM member WHERE memId = '$memId'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		$groupCode    = $row->groupCode;
		$organizeCode = $row->organizeCode;

		// 그룹정보 > 회원구성정보 > 서비스정보
		$sql = "SELECT commiType, totalPayAssort, totalPayFee, hpPayAssort, hpPayFee 
				FROM group_organize_service 
				WHERE groupCode = '$groupCode' AND organizeCode = '$organizeCode'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		if ($row->commiType == "B") { // 이용수수료(일괄적용)
			$payAssort = $row->totalPayAssort;
			$payFee    = $row->totalPayFee;

		} else if ($row->commiType == "E") { // 이용수수료(건별적용)
			$payAssort = $row->hpPayAssort;
			$payFee    = $row->hpPayFee;
		}
	}

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT @a:=@a+1 no, modelCode, modelName, priceNew, priceMnp, priceChange 
	        FROM ( select hm.modelCode, hm.modelName, hc.priceNew, hc.priceMnp, hc.priceChange 
		           from hp_commi hc 
				        inner join hp_model hm on hc.modelCode = hm.modelCode 
		           where hc.useYn = 'Y' $telecom_sql $assort_sql 
				   order by hc.modelCode asc 
		         ) t, (select @a:= 0) AS a";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecom = selected_object($row[telecom], $arrTelecomAssort);
			$requestStatus = selected_object($row[requestStatus], $arrRequestStatus);

			$priceNew    = $row[priceNew];
			$priceMnp    = $row[priceMnp];
			$priceChange = $row[priceChange];

			if ($payType == "C") { // 서비스이용료 > 납부방식 > 이용수수료
				if ($payAssort == "R") { // 정율차감
					if ($priceNew > 0) $priceNew = $priceNew - ($priceNew * ($payFee / 100));
					if ($priceMnp > 0) $priceMnp = $priceMnp - ($priceMnp * ($payFee / 100));
					if ($priceChange > 0) $priceChange = $priceChange - ($priceChange * ($payFee / 100));

				} else { // 정액차감
					if ($priceNew > 0) $priceNew = $priceNew - $payFee;
					if ($priceMnp > 0) $priceMnp = $priceMnp - $payFee;
					if ($priceChange > 0) $priceChange = $priceChange - $payFee;
				}

				// 백원 단위로 절삭
				$priceNew = (int) $priceNew / 100;
				$priceNew = $priceNew * 100;

				$priceMnp = (int) $priceMnp / 100;
				$priceMnp = $priceMnp * 100;

				$priceChange = (int) $priceChange / 100;
				$priceChange = $priceChange * 100;
			}

			$data_info = array(
				'no'          => $row[no],
				'modelCode'   => $row[modelCode],
				'modelName'   => $row[modelName],
				'priceNew'    => number_format($priceNew),
				'priceMnp'    => number_format($priceMnp),
				'priceChange' => number_format($priceChange),
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	$response = array(
		'result'         => $result_status,
		'telecomOptions' => $arrTelecomAssort,
		'data'           => $data
    );

	//print_r($response);
	//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
