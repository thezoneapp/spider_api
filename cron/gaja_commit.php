<?
    /***********************************************************************************************************************
	* 가자렌탈 수수료 자료 업데이트
	***********************************************************************************************************************/

	$db_host = "localhost";
	$db_user = "spiderfla";
	$db_pass = "dlfvkf#$12";
	$db_name = "spiderfla";

	$connect = @mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die("Failed to connect to MySQL: " . mysqli_error()); 

	/**************************** 스폰서 렌탈 수수료 지급 정보 **********************************************************/
	$maPrice = "0";
	$msPrice = "0";
	$sql = "SELECT ifnull(content,0) as sponsPrice FROM setting WHERE code in ('commitR')";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$sponsPrice = $row->sponsPrice;

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[code] == "commitMA") $maPrice = $row[content]; // MD수수료
			else if ($row[code] == "commitMS") $msPrice = $row[content]; // 구독수수료
		}
	}

	// API 호출 일자
	$time = time();
	$sdate = date("Y-m-d",strtotime("-1 day", $time));
	$edate = $sdate;

	//$sdate = "2020-12-01 00:00";
	//$edate = "2021-01-20 23:59";

	// API 호출
	$url = "https://gajarentalcms.com/gajajson.asp";
	$request_info = array(
		'sdate' => $sdate,
		'edate' => $edate
	);

	$ch = curl_init();                                                     // curl 초기화
	curl_setopt($ch, CURLOPT_URL, $url);                                   // URL 지정하기
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                        // 요청 결과를 문자열로 반환 
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);                          // connection timeout 10초 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                       // 원격 서버의 인증서가 유효한지 검사 안함
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request_info)); // data post 전송 
	curl_setopt($ch, CURLOPT_POST, true);                                  // true시 post 전송 

	$response = curl_exec($ch);
	curl_close($ch);

	$datas = json_decode($response, true);

	// api 로그 저장
	$log_date = date("Y-m-d",strtotime("-1 day", $time));
	$log_file = "/home/spiderfla/upload/log/gaja/" . $log_date . ".log";
	error_log ($response, 3, $log_file);

	//print_r($datas);
	//exit;
	for ($i = 0; count($datas) > $i; ++$i) {
		$data = $datas[$i];

		//$memId   = substr($data["Inviter"],1,8);
		$memId   = $data["Inviter"];
		$memName = $data["InviterName"];
		$price   = $data["COMMISSION"];
		$remarks = $data["CUSTOMER"];

		// 회원 CMS납부 상태 체크
		$sql = "SELECT sponsId, memName, clearStatus FROM member WHERE memId = '$memId'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		$sponsId = $row->sponsId;
		$memName = $row->memName;

		if ($row->clearStatus == "0") $clearStatus = "N"; // 정상
		else $clearStatus = "Y"; // 보류

		// 회원 렌탈 수수료 등록
		$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, assort, price, clearStatus, remarks, wdate) 
								VALUES ('$memId', '$memName', '$memId', '$memName', 'R1', '$price', '$clearStatus', '$remarks', now())";
		$connect->query($sql);

		// 스폰서 정보 및 CMS납부 상태 체크
		$sql = "SELECT memId, memName, clearStatus FROM member WHERE memId = '$sponsId'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		$sponsId   = $row->memId;
		$sponsName = $row->memName;

		if ($row->clearStatus == "0") $clearStatus = "N"; // 정상
		else $clearStatus = "Y"; // 보류

		// 스폰서 렌탈 수수료 등록
		$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, assort, price, clearStatus, wdate) 
								VALUES ('$sponsId', '$sponsName', '$memId', '$memName', 'R2', '$sponsPrice', '$clearStatus', now())";
		$connect->query($sql);
	}

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);

	exit;
?>