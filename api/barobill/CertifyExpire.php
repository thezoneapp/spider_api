<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/barobill.php";
	include './BaroService_TI.php';

	/*
	* 바로빌 > 공인인증서 만료일자 체크
	* parameter
		memId:      회원ID
	*/

	$data_back = json_decode(file_get_contents('php://input'));
	$memId = $data_back->{'memId'};

	//$memId = "a27233377";

	// 회원 정보
    $sql = "SELECT baroId, baroPw, corpNum FROM tax_member WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$params = array(
			'corpNum' => $row->corpNum,
		);

		// 바로빌 api호출
		$resultCode = certifyExpire($params);

		// 바로빌 에러 코드 정보
		$errorCode = str_replace("-", "", $resultCode);
		$sql = "SELECT errorMessage FROM error_code WHERE errorCode = '$errorCode'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);
			$result_message = $row->errorMessage;
			$result_status = "1";

		} else {
			if ($Result != "1") {
				$result_status = "0";
				$result_message = $resultCode;

				// 사업자정보 테이블에 공인인증서 등록 체크
				$sql = "UPDATE tax_member SET certifyStatus = 'Y', expireDate = '$Result' WHERE memId = '$memId'";
				$connect->query($sql);

			} else {
				$result_status = "1";
				$result_message = $Result;
			}
		}

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
