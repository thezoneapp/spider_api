<?
	/* 원클릭 - 휴대폰 개통상태
	    MEMID -- 회원ID
		CUSTNAME -- 고객명
		USETELECOM -- 통신사
		ASSORT -- 구분코드 (N: 신규, M: 번호이동, C: 기기변경)
		MODELCODE -- 모델코드
		PETSNAME -- 펫네임
		COLORCODE -- 색상
		CAPACITYCODE -- 용량 (null)
		PLANCODE -- 요금제
		CARDDISCOUNT -- 제휴카드할인여부 (null)
		COMMENT -- 기타메모 (null)
		PRICODE -- 관리코드
		STATUS -- 상태값 (8: 개통취소, 9: 개통완료)
		OPENDATE -- 개통일
		RECEIPTDATE -- 접수일 
	*/

	// 원클릭 서버
	$serverName = "61.100.15.213,5344";
	$connectionInfo = array(
		"Database" => "SEIERP_DREAM",
		"UID" => "dream",
		"PWD" => "DreamFree20181!"
	);
	
	$conn = sqlsrv_connect($serverName, $connectionInfo);

	if( $conn == false ) {
		die( print_r( sqlsrv_errors(), true));
	}

	// 검색할 시간 설정
	$auto_check = "";

	if ($auto_check == "N") {
		$minDate = "2021-01-01 00:00:01";
		$maxDate = "2021-04-30 23:59:59";

	} else {
		// 1시간 간격으로 업데이트하기 위하여 검색 일시를 설정한다.
		$now_date = date("Y-m-d H:i:s", strtotime("-30 minutes"));
		$current_date = date("Y-m-d", strtotime($now_date));
		$hour = date("H", strtotime($now_date));
		$minutes = date("i", strtotime($now_date));
		//$minutes_from = substr($minutes, 0, 1) . "0";
		$minutes_from = "00";
		$second_from = "00";
		//$minutes_to = substr($minutes, 0, 1) . "9";
		$minutes_to = "59";
		$second_to = "59";

		$minDate = $current_date . " " . $hour . ":00:00";
		$maxDate = $current_date . " " . $hour . ":59:59";
	}

	// 프로시져의 파라메타를 추가한다.
	$procedureName = "{CALL SP_SPIDER_HP_OPEN_STATUS ( ?, ? )}";  

	$params = array(
		array($minDate, SQLSRV_PARAM_IN),
		array($maxDate, SQLSRV_PARAM_IN)
	);

	$stmt = sqlsrv_query($conn, $procedureName, $params);

	if ($stmt == false) {  
		echo "Error in executing statement.\n";  
		die( print_r( sqlsrv_errors(), true));  
	}
	
	//print_r(sqlsrv_fetch_array($stmt));
	$data = array();

	while ($row = sqlsrv_fetch_array($stmt)) { 

		echo $row['MEMID'] . " / ". 
			$row['CUSTNAME'] . " / ". 
			$row['USETELECOM'] . " / ". 
			$row['ASSORT'] . " / ". 
			$row['MODELCODE'] . " / ". 
			$row['PETSNAME'] . " / ". 
			$row['COLORCODE'] . " / ". 
			$row['PRICODE'] . " / ". 
			$row['STATUS'] . " / ". 
			$row['OPENDATE'] . " / ". 
			$row['RECEIPTDATE'] . "<br>";

		$data_info = array(
			'memId'         => $row['MEMID'],
			'custName'      => $row['CUSTNAME'],
			'useTelecom'    => $row['USETELECOM'],
			'requestAssort' => $row['ASSORT'],
			'modelCode'     => $row['MODELCODE'],
			'petsName'      => $row['PETSNAME'],
			'colorCode'     => $row['COLORCODE'],
			'requestStatus' => $row['STATUS'],
			'priCode'       => $row['PRICODE'],
			'receiptDate'   => $row['RECEIPTDATE'],
			'openDate'      => $row['OPENDATE']
		);
		array_push($data, $data_info);
	}

	# statement를 해제한다.
	sqlsrv_free_stmt($stmt);

	#데이타베이스 접속을 해제한다.
	sqlsrv_close($conn);

    /***********************************************************************************************************************
	* 스파이더플랫폼 DB > 신청정보 업데이트
	***********************************************************************************************************************/
	$db_host = "localhost";
	$db_user = "spiderfla";
	$db_pass = "dlfvkf#$12";
	$db_name = "spiderfla";

	$connect = @mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die("Failed to connect to MySQL: " . mysqli_error()); 

	$siteAssort = "O"; // 접수처구분(S: 스파이더플랫폼, O: 원클릭)

	for ($n = 0; $n < count($data); $n++) {
		$row = $data[$n];
		$memId         = $row["memId"];
		$custName      = $row["custName"];
		$useTelecom    = $row["useTelecom"];
		$requestAssort = $row["requestAssort"];
		$modelCode     = $row["modelCode"];
		$petsName      = $row["petsName"];
		$colorName     = $row["colorCode"];
		$requestStatus = $row["requestStatus"];
		$priCode       = $row["priCode"];
		$receiptDate   = $row["receiptDate"];
		$openDate      = $row["openDate"];

		$arrModelCode = explode("-", $modelCode);
		$modelCode    = $data[0];
		$capacityName = $data[1];

		$arrPetsName = explode("-", $petsName);
		$modelName   = $arrPetsName[0];

		// 개통 취소
		if ($requestStatus == "8") {
			$sql = "UPDATE hp_request SET requestStatus = '$requestStatus' WHERE priCode = '$priCode";
			$connect->query($sql);

		// 개통완료
		} else if ($requestStatus == "9") {
			// 휴대폰 신청 테이블에 등록
			$sql = "INSERT INTO hp_request (siteAssort, memId, memName, custName, changeTelecom, requestAssort, 
											modelCode, modelName, colorName, capacityName, requestStatus, wdate)
									VALUES ('$siteAssort', '$memId', '$memName', '$custName', '$changeTelecom', '$requestAssort', 
											'$modelCode', '$modelName', '$colorName', '$capacityName', '$requestStatus', now())";
			//$connect->query($sql);
		}
	}

	// db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
	@mysqli_close($connect);

	exit;
?>