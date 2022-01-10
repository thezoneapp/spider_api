<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 기초 정보 설정
	*/

    $sql = "SELECT code, content FROM setting WHERE assort = 'V' ORDER by code asc";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[code] == "subsA") $subsA = $row[content];
			else if ($row[code] == "subsS") $subsS = $row[content];
			else if ($row[code] == "payA") $payA = $row[content];
			else if ($row[code] == "payS") $payS = $row[content];
			else if ($row[code] == "commitS") $commitS = $row[content];
			else if ($row[code] == "commitR") $commitR = $row[content];
			else if ($row[code] == "commitMA") $commitMA = $row[content];
			else if ($row[code] == "commitMS") $commitMS = $row[content];
		}

		$data = array(
			'subsA'    => $subsA,
			'subsS'    => $subsS,
			'payA'     => $payA,
			'payS'     => $payS,
			'commitS'  => $commitS,
			'commitR'  => $commitR,
			'commitMA' => $commitMA,
			'commitMS' => $commitMS,
		);

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
		$data = array();
	}

	$response = array(
		'result'    => $result,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>