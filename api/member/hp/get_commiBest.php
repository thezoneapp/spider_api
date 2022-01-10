<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰신청 > 수수료 목록 > 베스트 Top 3
	*/

	$data = array();

	for ($n=0; $n < count($arrTelecomAssort); $n++) {
		$telecomData = array();
		$telecom = $arrTelecomAssort[$n]["code"];
		$telecomName = $arrTelecomAssort[$n]["name"];

		$sql = "SELECT assort, modelCode, modelName, price 
				FROM (SELECT 'N' AS assort, hc.modelCode, hm.modelName, hc.priceNew AS price 
					  FROM hp_commi hc 
						   INNER JOIN hp_model hm ON hc.modelCode = hm.modelCode
					  WHERE hc.telecom = '$telecom' AND hc.useYn = 'Y' 
					  ORDER BY hc.priceNew DESC, hm.factoryPrice DESC
					  LIMIT 3
				) t1
				UNION
				SELECT assort, modelCode, modelName, price 
				FROM (SELECT 'M' AS assort, hc.modelCode, hm.modelName, hc.priceMnp AS price 
					  FROM hp_commi hc 
						   INNER JOIN hp_model hm ON hc.modelCode = hm.modelCode
					  WHERE hc.telecom = '$telecom' AND hc.useYn = 'Y' 
					  ORDER BY hc.priceMnp DESC, hm.factoryPrice DESC
					  LIMIT 3
				) t2
				UNION
				SELECT assort, modelCode, modelName, price 
				FROM (SELECT 'C' AS assort, hc.modelCode, hm.modelName, hc.priceChange AS price 
					  FROM hp_commi hc 
						   INNER JOIN hp_model hm ON hc.modelCode = hm.modelCode
					  WHERE hc.telecom = '$telecom' AND hc.useYn = 'Y' 
					  ORDER BY hc.priceChange DESC, hm.factoryPrice DESC
					  LIMIT 3
				) t3";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			while($row = mysqli_fetch_array($result)) {
				$assort    = $row[assort];
				$modelCode = $row[modelCode];
				$modelName = $row[modelName];
				$price     = $row[price];

				$price = $price / 10000;
				$price = (int) $price;

				$priceSkt = 0;
				$priceKt  = 0;
				$priceLgu = 0;

				if ($telecom == "S") $priceSkt = $price;
				else if ($telecom == "K") $priceKt = $price;
				else if ($telecom == "L") $priceLgu = $price;

				// 다른 통신사 && 동일한 모델 && 동일한 구분(신규,번호이동,기기변경) 검색
				$sql = "SELECT telecom, priceNew, priceMnp, priceChange 
						FROM hp_commit
						WHERE telecom != '$telecom' AND modelCode = '$modelCode' AND useYn = 'Y'";
				$result2 = $connect->query($sql);

				if ($result2->num_rows > 0) {
					while($row2 = mysqli_fetch_array($result2)) {
						if ($assort == "N") $price = $row2[priceNew];
						else if ($assort == "M") $price = $row2[priceMnp];
						else if ($assort == "C") $price = $row2[priceChange];

						$price = $price / 10000;
						$price = (int) $price;

						if ($row2[telecom] == "S") $priceSkt = $price;
						else if ($row2[telecom] == "K") $priceKt = $price;
						else if ($row2[telecom] == "L") $priceLgu = $price;
					}
				}

				$telecom_info = array(
					'assort'     => $assort,
					'modelName'  => $modelName,
					'priceSkt'   => $priceSkt,
					'priceKt'    => $priceKt,
					'priceLgu'   => $priceLgu,
				);
				array_push($telecomData, $telecom_info);
			}
		}

		$data_info = array(
			'telecomName' => $telecomName,
			'telecomInfo' => $telecomData,
		);
		array_push($data, $data_info);
	}

	// ********************************** 최종 Array *****************************************************************
	$response = array(
		'result' => "0",
		'data'   => $data,
    );

	//print_r($response);
	//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>