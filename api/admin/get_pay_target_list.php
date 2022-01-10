<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 출금신청대상 목록
	* parameter ==> memId:        회원ID
	* parameter ==> memAssort:    회원구분
	* parameter ==> paymentKind:  납부수단
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$memId       = trim($input_data->{'memId'});
	$memAssort   = $input_data->{'memAssort'};
	$paymentKind = $input_data->{'paymentKind'};

	$memAssort   = $memAssort->{'code'};
	$paymentKind = $paymentKind->{'code'};

	$cmsStatus   = "9";
	$agreeStatus = "9";
	$memStatus   = "9";

	if ($memId === null || $memId === "") $memId_sql = "";
	else $memId_sql = "and m.memId = '$memId' ";

	if ($memAssort === null || $memAssort=== "") $memAssort_sql = "";
	else $memAssort_sql = "and memAssort = '$memAssort' ";

	if ($paymentKind === null || $paymentKind === "") $paymentKind_sql = "";
	else $paymentKind_sql = "and c.paymentKind = '$paymentKind' ";

	// 전체 데이타 갯수
    $sql = "SELECT c.idx 
	        FROM cms c 
			     inner join member m on c.memId = m.memId 
			WHERE m.cmsStatus='$cmsStatus' and m.agreeStatus='$agreeStatus' and m.memStatus='$memStatus' $memId_sql $memAssort_sql $paymentKind_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT no, idx, sponsId, memId, memName, memAssort, hpNo, cmsStatus, agreeStatus, paymentKind, memStatus, wdate 
	        FROM ( select @a:=@a+1 no, idx, sponsId, memId, memName, memAssort, hpNo, cmsStatus, agreeStatus, paymentKind, memStatus, wdate 
			       from ( select c.idx, m.sponsId, m.memId, m.memName, m.memAssort, m.hpNo, m.cmsStatus, c.agreeStatus, c.paymentKind, m.memStatus, date_format(c.wdate, '%Y-%m-%d') as wdate 
		                  from cms c 
					           inner join member m on c.memId = m.memId 
		                  where m.cmsStatus='$cmsStatus' and m.agreeStatus='$agreeStatus' and m.memStatus='$memStatus' $memId_sql $memAssort_sql $paymentKind_sql 
						 ) t1, (select @a:= 0) AS a 
		         ) t2 
			ORDER BY no DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$cmsStatus = selected_object($row[cmsStatus], $arrCmsStatus);
			$agreeStatus = selected_object($row[agreeStatus], $arrAgreeStatus);
			$memAssort = selected_object($row[memAssort], $arrMemAssort);
			$memStatus = selected_object($row[memStatus], $arrMemStatus);
			$paymentKind = selected_object($row[paymentKind], $arrPaymentKind);

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'sponsId'        => $row[sponsId],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'memAssort'      => $memAssort,
				'cmsStatus'      => $cmsStatus,
				'agreeStatus'    => $agreeStatus,
				'paymentKind'    => $paymentKind,
				'memStatus'      => $memStatus,
				'wdate'          => $row[wdate]
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
		'assortOptions'   => $arrMemAssort,
		'kindOptions'     => $arrPaymentKind,
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>