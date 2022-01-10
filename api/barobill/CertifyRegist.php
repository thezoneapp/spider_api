<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/barobill.php";
	include './BaroService_TI.php';

	/*
	* 바로빌 > 공인인증서 등록
	* parameter :
		memId:      회원ID
	*/

	$data_back = json_decode(file_get_contents('php://input'));
	$memId = $data_back->{'memId'};

	//$memId = "a51607340";

	// 회원 정보
    $sql = "SELECT baroId, baroPw, corpNum FROM tax_member WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->baroPw !== "") $row->baroPw = aes_decode($row->baroPw);

		$params = array(
			'corpNum'	=> $row->corpNum,
			'baroId'	=> $row->baroId,
			'baroPw'	=> $row->baroPw
		);

		// 바로빌 api호출
		$resultCode = certifyRegist($params);

		$result_status = "0";
		$result_message = $resultCode;

	} else {
		$result_status = "1";
		$result_message = "등록되지 않은 회원입니다.";
	}
	
	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>

