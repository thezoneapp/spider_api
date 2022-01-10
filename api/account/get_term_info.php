<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
	/*
	* 가입구분 및 약관 정보
	*/

	$term_01 = "";
	$term_02 = "";
	$term_03 = "";
	$term_04 = "";
    $sql = "SELECT code, content FROM setting WHERE code in('term_01','term_02','term_03','term_04')";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[code] == "term_01") $term_01 = $row[content];
			else if ($row[code] == "term_02") $term_02 = $row[content];
			else if ($row[code] == "term_03") $term_03 = $row[content];
			else if ($row[code] == "term_04") $term_04 = $row[content];
		}
	}

	$data = array(
		'assortOptions' => $arrMemAssort3,
		'term_01'       => $term_01,
		'term_02'       => $term_02,
		'term_03'       => $term_03,
		'term_04'       => $term_04
	);

	$response = array(
		'result' => "0",
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>