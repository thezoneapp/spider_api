<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/barobill.php";
	include './BaroService_TI.php';

	/*
	* 바로빌 > 세금계산서 발행상태
	* parameter

	*/

	$data_back = json_decode(file_get_contents('php://input'));
	$memId = $data_back->{'memId'};

	$idx = "77";

	// 회원 정보
    $sql = "SELECT idx, corpNum, mgtNum FROM tax_invoice";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$idx = $row[idx];

			$params = array(
				'corpNum' => $row[corpNum],
				'mgtKey'  => "S-" . $row[mgtNum],
			);

			// 바로빌 > 세금계산서 발행 상태 api호출
			$response = taxInvoiceState($params);
			$response = json_decode($response);
	print_r($response);
			$approvalNo     = $response->{'approvalNo'};
			$result_status  = $response->{'result'};
			$result_message = $response->{'message'};

			if ($result_status == "0") {
				$sql = "UPDATE tax_invoice SET approvalNo = '$approvalNo' WHERE idx = '$idx'";
				$connect->query($sql);
			}

		}
	}
exit;
	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
