<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 휴대폰 모델 목록
	* parameter ==> page:      해당페이지
	* parameter ==> rows:      페이지당 행의 갯수
	* parameter ==> makerCode: 제조사코드
	* parameter ==> telecom:   통신사코드
	* parameter ==> assort:    구분코드(N: 신규, M: 번호이동, C: 기기변경)
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$page       = $input_data->{'page'};
	$rows       = $input_data->{'rows'};
	$makerCode  = $input_data->{'makerCode'};
	$telecom    = $input_data->{'telecom'};
	$assort     = $input_data->{'assort'};

	//$makerCode = "A";
	//$telecom   = "S";
	//$assort    = "C";

	if ($assort == null || $assort == "") $assort = "M";

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($makerCode == null || $makerCode == "") $maker_sql = "";
	else $maker_sql = "and makerCode = '$makerCode' ";

	if ($telecom == null || $telecom == "") $telecom_sql = "";
	else $telecom_sql = "and (telecom = '0' or telecom like '%$telecom%') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM hp_model WHERE useYn = 'Y' $maker_sql $telecom_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT no, idx, makerCode, modelCode, modelName, thumbnail 
	        FROM ( select @a:=@a+1 no, idx, makerCode, modelCode, modelName, thumbnail 
		           from hp_model, (select @a:= 0) AS a 
		           where useYn = 'Y' $maker_sql $telecom_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$modelCode = $row[modelCode];
			$makerName = selected_object($row[makerCode], $arrMakerAssort);

			if ($row[thumbnail] == null || $row[thumbnail] == "") $thumbnail = "";
			else $thumbnail = "http://spiderplatform.co.kr/upload/thumbnail/" . $row[thumbnail];

			// 색상 데이타 검색 
			$colors = "";
			$sql = "SELECT colorName FROM hp_model_color WHERE useYn = 'Y' and modelCode = '$modelCode' ORDER BY idx ASC";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					if ($colors != "") $colors .= "/";
					$colors .= $row2[colorName];
				}
			}

			// 용량 데이타 검색 
			$capacitys = "";
			$sql = "SELECT capacityCode FROM hp_model_capacity WHERE useYn = 'Y' and modelCode = '$modelCode' ORDER BY idx ASC";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					if ($capacitys != "") $capacitys .= "/";
					$capacityName = selected_object($row2[capacityCode], $arrCapacityAssort);
					$capacitys .= $capacityName;
				}
			}

			// 수수료 정보
			$commission = "0";
			$sql = "SELECT priceNew, priceMnp, priceChange 
					FROM hp_commi 
					WHERE telecom = '$telecom' and modelCode = '$modelCode' and useYn = 'Y'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);

				if ($assort == "N") $commission = $row2->priceNew;
				else if ($assort == "M") $commission = $row2->priceMnp;
				else if ($assort == "C") $commission = $row2->priceChange;

				$commission = $commission / 10000;
				$commission = (int) $commission;
			}

			$data_info = array(
				'no'            => $row[no],
				'idx'           => $row[idx],
				'makerCode'     => $row[makerCode],
				'makerName'     => $makerName,
				'modelCode'     => $row[modelCode],
				'modelName'     => $row[modelName],
				'commission'    => $commission,
				'thumbnail'     => $thumbnail,
				'colors'        => $colors,
				'capacitys'     => $capacitys,
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
