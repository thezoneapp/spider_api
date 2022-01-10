<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 정산서 세부 목록
	* parameter ==> idx: 정산서번호
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = trim($input_data->{'idx'});

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM commi_accurate_detail WHERE accurateIdx = '$idx'";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT @a:=@a+1 no, idx, memId, memName, assort, price, commiDate 
	        FROM ( select idx, memId, memName, assort, price, commiDate 
		           from commi_accurate_detail 
		           where accurateIdx = '$idx' 
		         ) m, (select @a:= 0) as a 
			ORDER BY no DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assortName = selected_object($row[assort], $arrCommiAssort);

			$data_info = array(
				'no'        => $row[no],
				'idx'       => $row[idx],
				'memId'     => $row[memId],
				'memName'   => $row[memName],
				'assort'    => $assortName,
				'price'     => number_format($row[price]),
				'commiDate' => $row[commiDate]
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
	}

	$response = array(
		'result'          => $result,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>