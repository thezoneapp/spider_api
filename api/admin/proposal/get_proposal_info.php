<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 >제휴제안 >목록 >상세정보
	* parameter ==> idx: 글 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};
	
	//$idx = 38;

	// 게시판 정보
	$sql = "SELECT companyName, postCode, addr1, addr2, telNo, hpNo, email, chargeName, introduction, content, companyDoc, answerYn, adminMemo, wdate 
			FROM proposal 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->telNo !== "") $row->telNo = aes_decode($row->telNo);
		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);
		if ($row->email !== "") $row->email = aes_decode($row->email);

		// 이전글, 다음글 정보
		$navData = array();
		$sql = "SELECT nav, idx, companyName, wdate 
				FROM ( SELECT '이전글' as nav, idx, companyName, wdate 
					   FROM ( SELECT idx, companyName, wdate 
							  FROM proposal
							  WHERE idx > '$idx' 
							  ORDER BY idx DESC
							  LIMIT 1
							) t1
						UNION
						SELECT '다음글' as nav, idx, companyName, wdate 
						FROM ( SELECT idx, companyName, wdate 
							   FROM proposal
							   WHERE idx < '$idx' 
							   ORDER BY idx DESC
							   LIMIT 1
							) t2
				) t";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				$nav_info = array(
					'nav'         => $row2[nav],
					'idx'         => $row2[idx],
					'companyName' => $row2[companyName],
					'wdate'       => $row2[wdate],
				);
				array_push($navData, $nav_info);
			}
		}

		$data = array(
			'idx'          => $row->idx,
			'companyName'  => $row->companyName,
			'postCode'     => $row->postCode,
			'addr1'        => $row->addr1,
			'addr2'        => $row->addr2,
			'telNo'        => $row->telNo,
			'hpNo'         => $row->hpNo,
			'email'        => $row->email,
			'chargeName'   => $row->chargeName,
			'introduction' => $row->introduction,
			'content'      => $row->content,
			'companyDoc'   => $row->companyDoc,
			'answerYn'     => $row->answerYn,
			'adminMemo'    => $row->adminMemo,
			'wdate'        => $row->wdate,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

	} else {
		$data = array(
			'idx'          => "",
			'companyName'  => "",
			'postCode'     => "",
			'addr1'        => "",
			'addr2'        => "",
			'telNo'        => "",
			'hpNo'         => "",
			'email'        => "",
			'chargeName'   => "",
			'introduction' => "",
			'content'      => "",
			'companyDoc'   => "",
			'answerYn'     => "",
			'adminMemo'    => "",
			'wdate'        => "",
		);
	}

	$response = array(
		'result'        => $result_status,
		'data'          => $data,
		'statusOptions' => $arrProposalStatus,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>