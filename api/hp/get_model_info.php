<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 휴대폰 모델 정보
	* parameter ==> changeTelecom: 이동통신사
	* parameter ==> assort:        구분코드(N: 신규, M: 번호이동, C: 기기변경)
	* parameter ==> modelCode:     모델코드
	* parameter ==> marginPrice:   마진금액 (회원이 선택한 마진금액 --> Pull이면 0)
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$telecom     = $input_data->{'changeTelecom'};
	$assort      = $input_data->{'assort'};
	$modelCode   = $input_data->{'modelCode'};
	$marginPrice = $input_data->{'marginPrice'};

	//$telecom = "S";
	//$assort = "C";
	//$modelCode = "G998";
	//$marginPrice = "50000";

	$colors = array();
	$capacitys = array();

    $sql = "SELECT modelCode, modelName, thumbnail 
	        FROM hp_model 
			WHERE modelCode = '$modelCode' and useYn = 'Y'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$modelCode = $row->modelCode;
		$modelName = $row->modelName;
		$thumbnail = $row->thumbnail;

		// 수수료정책
		$commitPrice = 0;
		$sql = "SELECT priceNew, priceMnp, priceChange FROM hp_commit WHERE telecom = '$telecom' and modelCode = '$modelCode' and useYn = 'Y'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);

			if ($assort == "N") $commitPrice = $row2->priceNew;
			else if ($assort == "M") $commitPrice = $row2->priceMnp;
			else if ($assort == "C") $commitPrice = $row2->priceChange;
		}

		// 할인혜택금액을 구한다.
		$benefitPrice = 0;
		$benefitPrice = $commitPrice - $marginPrice;

		// 용량 데이타 검색 
		$sql = "SELECT capacityCode 
				FROM hp_model_capacity 
				WHERE useYn = 'Y' and modelCode = '$modelCode' 
				ORDER BY idx ASC";
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

		// 색상 데이타 검색 
		$sql = "SELECT idx, colorName 
				FROM hp_model_color 
				WHERE useYn = 'Y' and modelCode = '$modelCode' and telecoms like '%$telecom%' 
				ORDER BY idx ASC";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				$data_info = array(
					'colorCode' => $row2[idx],
					'colorName' => $row2[colorName],
				);
				array_push($colors, $data_info);
			}
		}

		$commitPrice = $commitPrice / 10000;
		$commitPrice = (int) $commitPrice;

		$data = array(
			'modelCode'    => $modelCode,
			'modelName'    => $modelName,
			'thumbnail'    => $thumbnail,
			'commission'   => $commitPrice,
			'benefitPrice' => $benefitPrice,
			'capacitys'    => $capacitys,
			'colors'       => $colors,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "성공";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "존재하지 않습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
		'data'    => $data,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
