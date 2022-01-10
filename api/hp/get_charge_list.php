<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 휴대폰신청 > 요금제 선택 목록
	* parameter ==> changeTelecom: 통신사코드
	* parameter ==> modelCode:     모델코드
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$telecom    = $input_data->{'changeTelecom'};
	$modelCode  = $input_data->{'modelCode'};

	//$telecom   = "S";
	//$modelCode = "G998";

	// 휴대폰 모델 > 통신망
	$sql = "SELECT hg.imtAssort 
			FROM hp_model  hm 
				 INNER JOIN hp_goods hg ON hm.goodsCode = hg.goodsCode 
			WHERE modelCode = '$modelCode'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$imtAssort = $row->imtAssort;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, chargeCode, chargeName, chargePrice, discountPrice, chargeExplain 
	        FROM ( select @a:=@a+1 no, chargeCode, chargeName, chargePrice, discountPrice, chargeExplain 
		           from hp_charge, (select @a:= 0) AS a 
		           where useYn = 'Y' and telecom = '$telecom' and imtAssort = '$imtAssort' 
		         ) m 
			ORDER BY chargePrice DESC";
	$result = $connect->query($sql);
	$total = $result->num_rows;

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'no'            => $row[no],
				'chargeCode'    => $row[chargeCode],
				'chargeName'    => $row[chargeName],
				'chargePrice'   => number_format($row[chargePrice]),
				'discountPrice' => number_format($row[discountPrice]),
				'chargeExplain' => $row[chargeExplain],
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
		'result'          => $result_status,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
