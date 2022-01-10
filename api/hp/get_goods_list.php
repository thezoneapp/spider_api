<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 휴대폰 모델 목록
	* parameter ==> page:      해당페이지
	* parameter ==> rows:      페이지당 행의 갯수
	* parameter ==> makerCode: 제조사코드
	* parameter ==> telecom:   희망통신사코드
	* parameter ==> assort:    구분코드(N: 신규, M: 번호이동, C: 기기변경)
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$page       = $input_data->{'page'};
	$rows       = $input_data->{'rows'};
	$makerCode  = $input_data->{'makerCode'};
	$telecom    = $input_data->{'telecom'};
	$assort     = $input_data->{'assort'};

	//$makerCode = "S";
	//$telecom   = "S";
	//$assort    = "C";

	if ($assort == null || $assort == "") $assort = "M";

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	$maker_sql = "and makerCode = '$makerCode' ";
	$telecom_sql = "and telecoms like '%$telecom%' ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM hp_goods WHERE useYn = 'Y' $maker_sql $telecom_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, goodsCode, goodsName, thumbnail 
	        FROM ( select @a:=@a+1 no, idx, goodsCode, goodsName, thumbnail 
		           from hp_goods, (select @a:= 0) AS a 
		           where useYn = 'Y' $maker_sql $telecom_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$goodsCode = $row[goodsCode];
			$goodsName = $row[goodsName];
			$thumbnail = $row[thumbnail];

			$models = array();
			$sql = "SELECT modelCode, modelName, thumbnail 
					FROM hp_model
					WHERE goodsCode = '$goodsCode' and useYn = 'Y' 
					ORDER BY idx ASC";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					$modelCode  = $row2[modelCode];
					$modelName  = $row2[modelName];
					$modelThumb = $row2[thumbnail];

					// 용량 데이타 검색 
					$capacitys = array();
					$sql = "SELECT capacityCode FROM hp_model_capacity WHERE useYn = 'Y' and modelCode = '$modelCode' ORDER BY idx ASC";
					$result3 = $connect->query($sql);

					if ($result3->num_rows > 0) {
						while($row3 = mysqli_fetch_array($result3)) {
							$capacityName = selected_object($row3[capacityCode], $arrCapacityAssort);

							$capacity_info = array(
								'capacityCode' => $row3[capacityCode],
								'capacityName' => $capacityName,
							);
							array_push($capacitys, $capacity_info);
						}
					}

					// 색상 데이타 검색 
					$colors = array();
					$sql = "SELECT idx, colorName FROM hp_model_color WHERE useYn = 'Y' and modelCode = '$modelCode' and telecoms like '%$telecom%' ORDER BY idx ASC";
					$result3 = $connect->query($sql);

					if ($result3->num_rows > 0) {
						while($row3 = mysqli_fetch_array($result3)) {
							$color_info = array(
								'colorCode' => $row3[idx],
								'colorName' => $row3[colorName],
							);
							array_push($colors, $color_info);
						}
					}

					// 수수료 정보
					$commission = "0";
					$sql = "SELECT priceNew, priceMnp, priceChange 
							FROM hp_commi 
							WHERE telecom = '$telecom' and modelCode = '$modelCode' and useYn = 'Y'";
					$result3 = $connect->query($sql);

					if ($result3->num_rows > 0) {
						$row3 = mysqli_fetch_object($result3);

						if ($assort == "N") $commission = $row3->priceNew;
						else if ($assort == "M") $commission = $row3->priceMnp;
						else if ($assort == "C") $commission = $row3->priceChange;

						$commission = $commission / 10000;
						$commission = (int) $commission;
					}

					$model_info = array(
						'modelCode'     => $modelCode,
						'modelName'     => $modelName,
						'thumbnail'     => $modelThumb,
						'commission'    => $commission,
						'capacitys'     => $capacitys,
						'colors'        => $colors,
					);
					array_push($models, $model_info);
				}
			}

			$data_info = array(
				'goodsCode' => $goodsCode,
				'goodsName' => $goodsName,
				'thumbnail' => $thumbnail,
				'models'    => $models,
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_ok = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_ok = "1";
	}

	$response = array(
		'result'          => $result_ok,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
