<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 휴대폰신청 > 제휴카드할인 선택 목록
	* parameter ==> changeTelecom: 통신사코드
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$telecom    = $input_data->{'changeTelecom'};

	//$telecom = "K";

	// 데이타 검색 
	$data = array();
    $sql = "SELECT no, cardCode, cardName, telecom, usePrice, discountPrice, thumbnail, cardExplain 
	        FROM ( select @a:=@a+1 no, cardCode, cardName, telecom, usePrice, discountPrice, thumbnail, cardExplain 
		           from hp_alliance_card, (select @a:= 0) AS a 
		           where useYn = 'Y' and telecom = '$telecom' 
		         ) m 
			ORDER BY discountPrice ASC";
	$result = $connect->query($sql);
	$total = $result->num_rows;

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'no'            => $row[no],
				'cardCode'      => $row[cardCode],
				'cardName'      => $row[cardName],
				'usePrice'      => number_format($row[usePrice]),
				'discountPrice' => number_format($row[discountPrice]),
				'cardExplain'   => $row[cardExplain],
				'thumbnail'     => $row[thumbnail],
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
		'result'    => $result_status,
		'rowTotal'  => $total,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
