<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 약관 목록
	*/

	// 데이타 검색 
	$data = array();
	$sql = "SELECT @a:=@a+1 no, idx, code, subject 
	        FROM ( select idx, code, subject 
		           from setting 
		           where assort = 'T' 
				   order by subject asc 
		         ) s, (select @a:= 0) as a ";

	$result = $connect->query($sql);
	$total = $result->num_rows;

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'no'      => $row[no],
				'idx'     => $row[idx],
				'code'    => $row[code],
				'subject' => $row[subject],
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
		'result'      => $result,
		'rowTotal'    => $total,
		'pageCount'   => 1,
        'data'        => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>