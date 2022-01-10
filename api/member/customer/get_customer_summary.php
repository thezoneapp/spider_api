<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 회원 > 소비자관리
	* parameter 
		page:           해당페이지
		rows:           페이지당 행의 갯수
		searchValue:    검색값
		userId:         사용자아이디
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$userId      = $input_data->{'userId'};
	$sort        = $input_data->{'sort'};
	$searchValue = trim($input_data->{'searchValue'});

	//$userId = "a27233377";
	//$sort = "lastDate";
	//$searchValue = "";

	if ($searchValue == "") $searchHpNo = "";
	else $searchHpNo = aes128encrypt($searchValue);

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 데이타 검색 
	$total = 0;
	$pageCount = 0;
	$data = array();

	$connect2 = @mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die("Failed to connect to MySQL: " . mysqli_error()); 
	$result = $connect2->query("CALL sp_customer_summary('$userId', '$searchValue', '$searchHpNo', '$sort', $page, $rows)");

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($total == 0) {
				$total = $row[totalRow];
				$pageCount = ceil($total / $rows);
			}

			$custId = $row[custId];
			$sumCommission = 0;

			if ($row[hpNo] !== "") $row[hpNo] = aes_decode($row[hpNo]);
	
			// 휴대폰 신청정보
			$hpCount = 0;
			$hpData = array();
			$sql = "SELECT idx, openingDate, chargeExpire, addServiceExpire, openingExipre, commission,
						   TIMESTAMPDIFF(DAY, openingDate, NOW()) AS dDay 
					from hp_request
					WHERE custId = '$custId' AND requestStatus = '9'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					++$hpCount;
					$sumCommission = $sumCommission + $row2[commission];

					$hp_info = array(
						'idx'              => $row2[idx],
						'openingDate'      => $row2[openingDate],
						'commission'       => number_format($row2[commission]),
						'dDay'             => $row2[dDay],
						'openingExipre'    => $row2[openingExipre],
						'chargeExpire'     => $row2[chargeExpire],
						'addServiceExpire' => $row2[addServiceExpire],
					);
					array_push($hpData, $hp_info);
				}
			}

			// 렌탈 신청 정보
			$rentalCount = 0;
			$rentalData = array();
			$sql = "SELECT idx, setupDate, goodsName, commission FROM rental_request WHERE custId = '$custId'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					++$rentalCount;
					$sumCommission = $sumCommission + $row2[commission];

					$rental_info = array(
						'idx'        => $row2[idx],
						'setupDate'  => $row2[setupDate],
						'goodsName'  => $row2[goodsName],
						'commission' => number_format($row2[commission]),

					);
					array_push($rentalData, $rental_info);
				}
			}

			// 다이렉트보험 신청 정보
			$insuCount = 0;
			$insuData = array();
			$sql = "SELECT idx, expiredDate, carNo, commission, DATE_FORMAT(wdate, '%Y-%m-%d') as requestDate FROM insu_request WHERE custId = '$custId' and requestStatus = '9'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					++$insuCount;
					$sumCommission = $sumCommission + $row2[commission];

					$insu_info = array(
						'idx'         => $row2[idx],
						'expiredDate' => $row2[expiredDate],
						'carNo'       => $row2[carNo],
						'commission'  => number_format($row2[commission]),
						'requestDate' => $row2[requestDate],

					);
					array_push($insuData, $insu_info);
				}
			}

			$data_info = array(
				'no'           => $row[listNo],
				'custId'       => $row[custId],
				'custName'     => $row[custName],
				'nickName'     => $row[nickName],
				'hpNo'         => $row[hpNo],
				'custMemo'     => $row[custMemo],
				'registDate'   => $row[registDate],
				'issueOpening' => $row[issueOpening],
				'issueAdd'     => $row[issueAdd],
				'issueCharge'  => $row[issueCharge],
				'issueInsu'    => $row[issueInsu],
				'hpCount'      => number_format($hpCount),
				'rentalCount'  => number_format($rentalCount),
				'insuCount'    => number_format($insuCount),
				'commission'   => number_format($sumCommission),
				'hpData'       => $hpData,
				'rentalData'   => $rentalData,
				'insuData'     => $insuData,
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	// 합계 정보
	$hpCount = 0;
	$insuCount = 0;
	$rentalCount = 0;
	$sql = "SELECT 'H' as assort, COUNT(idx) AS sumCount FROM hp_request WHERE memId = '$userId' and requestStatus = '9'
			UNION 
			SELECT 'I' as assort, COUNT(idx) AS sumCount FROM insu_request WHERE memId = '$userId' and requestStatus = '9'
			UNION 
			SELECT 'R' as assort, COUNT(idx) AS sumCount FROM rental_request WHERE memId = '$userId'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[assort] == "H") $hpCount = $row[sumCount];
			else if ($row[assort] == "I") $insuCount = $row[sumCount];
			else if ($row[assort] == "R") $rentalCount = $row[sumCount];
		}
	}

	$response = array(
		'result'      => $result_status,
		'rowTotal'    => $total,
		'pageCount'   => $pageCount,
		'custCount'   => number_format($total),
		'hpCount'     => number_format($hpCount),
		'insuCount'   => number_format($insuCount),
		'rentalCount' => number_format($rentalCount),
		'data'        => $data
    );
// print_r($response);
// exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect2);
	@mysqli_close($connect);
?>
