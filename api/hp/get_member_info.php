<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 회원 정보
	* parameter ==> memId: 회원ID
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$memId = $data_back->{'memId'};
	
	//$memId = "a11111111";
    $sql = "SELECT memId, memName, memStatus FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);

	//error_log ($sql, 3, "/home/yourphone/log/debug.log");

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$data = array(
			'memId'   => $row->memId,
			'memName' => $row->memName,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "성공";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result_status = "1";
		$result_message = "회원이 존재하지 않습니다.";
	}

	// 최대 수수료
    $sql = "SELECT MAX(price) AS price
			FROM ( SELECT max(priceNew) AS price FROM hp_commi WHERE useYn = 'Y'
				   UNION
                   SELECT max(priceMnp) AS price FROM hp_commi WHERE useYn = 'Y'
                   UNION
                   SELECT max(priceChange) AS price FROM hp_commi WHERE useYn = 'Y'
                 ) t1";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$maxPoint = $row->price;

	/*
	* 개인정보취급약관 및 통신사구분
	*/

	$agreeTerm = "";
    $sql = "SELECT code, content FROM setting WHERE code in('term_04')";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[code] == "term_04") $agreeTerm = $row[content];
		}
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
		'data'      => $data,
		'maxPoint'  => $maxPoint,
		'agreeTerm' => $agreeTerm
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>