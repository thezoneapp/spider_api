<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
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

	$data = array(
		'telecomOptions' => $arrTelecomAssort2,
		'agreeTerm'      => $agreeTerm
	);

	$response = array(
		'result' => "0",
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>