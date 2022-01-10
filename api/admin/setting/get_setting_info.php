<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 기초 정보 설정
	parameter
		hpIntRate:   휴대폰신청 > 할부이자율
		cashOutRate: 포인트관리 > 현급지급율
		insuRate:    다이렉트보험 > 수수료율
		deliveryKey: 스마트택배 API key
	*/

	$hpIntRate = 0;
	$cashOutRate = 0;
	$insuRate = 0;

	// 할부이자율, 현급지급율, 수수료율
    $sql = "SELECT code, content FROM setting WHERE assort = 'V' ORDER by code asc";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[code] == "hpIntRate") $hpIntRate = $row[content];
			else if ($row[code] == "cashOutRate") $cashOutRate = $row[content];
			else if ($row[code] == "insuRate") $insuRate = $row[content];
		}
	}

	// 휴대폰 할부개월
	$hpInstallmentData = array();
    $sql = "SELECT telecom, installment FROM hp_installment";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecom     = $row[telecom];
			$installment = $row[installment];

			$telecomName = selected_object($row[telecom], $arrTelecomAssort5);

			$installmentData = array();

			for ($n = 0; count($arrInstallment) > $n; $n++) {
				$row2 = $arrInstallment[$n];
				$code = $row2[code];
				$name = $row2[name];

				if(strpos($installment, $code) > -1) $checked = true;
				else $checked = false;

				$data_info = array(
					'code'    => $code,
					'name'    => $name,
					'checked' => $checked,
				);
				array_push($installmentData, $data_info);
			}

			$data_info = array(
				'telecom'     => $telecom,
				'telecomName' => $telecomName,
				'installment' => $installmentData,
			);
			array_push($hpInstallmentData, $data_info);
		}
	}

	// 스마트택배 API key
	$apiData = array();
    $sql = "SELECT idx, apiKey FROM api_key WHERE useYn = 'Y'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'idx'    => $row[idx],
				'apiKey' => $row[apiKey],
			);
			array_push($apiData, $data_info);
		}
	}

	// 최종
	$data = array(
		'hpInstallment' => $hpInstallmentData,
		'hpIntRate'     => $hpIntRate,
		'cashOutRate'   => $cashOutRate,
		'insuRate'      => $insuRate,
		'apiKey'        => $apiData,
	);

	$response = array(
		'result' => "0",
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>