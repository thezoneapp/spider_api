<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰신청 > 제휴카드할인 > 목록 > 상세정보
	* parameter ==> idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, cardCode, cardName, telecom, usePrice, discountPrice, thumbnail, cardExplain, useYn 
	        FROM hp_alliance_card 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$telecomName = selected_object($row->telecom, $arrTelecomAssort);

		$data = array(
			'idx'           => $row->idx,
			'cardCode'      => $row->cardCode,
			'cardName'      => $row->cardName,
			'telecom'       => $row->telecom,
			'telecomName'   => $telecomName,
			'usePrice'      => $row->usePrice,
			'discountPrice' => $row->discountPrice,
			'thumbnail'     => $row->thumbnail,
			'cardExplain'   => $row->cardExplain,
			'useYn'         => $row->useYn,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		$modelCode = "";

		$data = array(
			'idx'           => '',
			'cardCode'      => '',
			'cardName'      => '',
			'telecom'       => '',
			'telecomName'   => '',
			'usePrice'      => '',
			'discountPrice' => '',
			'thumbnail'     => '',
			'cardExplain'   => '',
			'useYn'         => 'Y',
		);

		// 추가모드로 결과를 반환합니다.
		$result_status = "1";
	}

	$response = array(
		'result'          => $result_status,
		'data'            => $data,
		'telecomOptions'  => $arrTelecomAssort,
		'useOptions'      => $arrUseAssort,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>