<?
    /***********************************************************************************************************************
	* 가자렌탈 수수료 자료 업데이트
	***********************************************************************************************************************/
	$db_host = "localhost";
	$db_user = "spiderfla";
	$db_pass = "dlfvkf#$12";
	$db_name = "spiderfla";

	$connect = @mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die("Failed to connect to MySQL: " . mysqli_error()); 

	//*********************************************************************
	//**********          암호화/복호화            **************************
	//*********************************************************************
	$key = "eoqkr!#18@$";
	$key = substr(hash('sha256', $key), 0, 32);
	$key = pack('H*', hash('sha256', $key));

	// AES128/ECB/PKCS5Padding 암호화
	function aes128encrypt($data) {
		global $key;

		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

		mcrypt_generic_init($td, $key, $iv);

		$result = mcrypt_generic($td, pkcs5_pad($data, $size));

		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return base64_encode($result);
	}

	// PKCS5Padding 인코딩
	function pkcs5_pad($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);

		return $text . str_repeat(chr($pad), $pad);
	}

	/**************************** 스폰서 렌탈 수수료 지급 정보 **********************************************************/
	$maPrice = "0";
	$msPrice = "0";
	$sql = "SELECT ifnull(content,0) as sponsPrice FROM setting WHERE code in ('commiR')";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$sponsPrice = $row->sponsPrice;

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[code] == "commiMA") $maPrice = $row[content]; // MD수수료
			else if ($row[code] == "commiMS") $msPrice = $row[content]; // 구독수수료
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

	for ($i = 0; count($datas) > $i; ++$i) {
		$data = $datas[$i];

		$memId     = $data["Inviter"];
		$memName   = $data["InviterName"];
		$price     = $data["COMMISSION"];
		$custName  = $data["CUSTOMER"];
		$hpNo      = $data["HP"];
		$setupDate = $data["SetupDate"];

		if ($hpNo != "") {
			$custHpNo = str_replace("-", "", $hpNo);
			$custId = "c" . substr($custHpNo, 3, 8);

			$sql = "SELECT idx FROM customer WHERE custId = '$custId'";
			$result = $connect->query($sql);

			if ($result->num_rows == 0) {
				$hpNo = aes128encrypt($hpNo);
				$sql = "INSERT INTO customer (custId, custName, nickName, hpNo, wdate)
									  VALUES ('$custId', '$custName', '$custName', '$hpNo', now())";
				$connect->query($sql);
			}

		} else {
			$custId = "";
		}

		// 회원 정보
		$sql = "SELECT sponsId, memName, memAssort FROM member WHERE memId = '$memId'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		$sponsId   = $row->sponsId;
		$memName   = $row->memName;
		$memAssort = $row->memAssort;

		// 렌탈목록에 추가
		$sql = "INSERT INTO rental_request (memId, memName, custId, custName, commission, setupDate, status, wdate) 
							        VALUES ('$memId', '$memName', '$custId', '$custName', '$price', '$setupDate', '9', now())";
		$connect->query($sql);

		// 회원 렌탈 수수료 등록 --> 정산완료
		$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, memAssort, assort, custId, custName, price, accurateStatus, wdate) 
								VALUES ('$memId', '$memName', '$memId', '$memName', '$memAssort', 'R1', '$custId', '$custName', '$price', '9', now())";
		$connect->query($sql);

		// 포인트 정산적립 추가
		$descript = "렌탈 [" . $remarks . "]";
		$sql = "INSERT INTO point (memId, memName, assort, descript, point, wdate)
							   VALUES ('$memId', '$memName', 'OA', '$custName', '$price', now())";
		$connect->query($sql);

		// 포인트 현금인출 추가
		$price = 0 - $price;
		$sql = "INSERT INTO point (memId, memName, assort, descript, point, wdate)
						   VALUES ('$memId', '$memName', 'OC', '$custName', '$price', now())";
		$connect->query($sql);

		// 스폰서 정보
		$sql = "SELECT memId, memName FROM member WHERE memId = '$sponsId'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		$sponsId   = $row->memId;
		$sponsName = $row->memName;

		// 스폰서 렌탈 수수료 등록
		$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, memAssort, assort, custName, price, wdate) 
								VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$memAssort', 'R2', '$custName', '$sponsPrice', now())";
		$connect->query($sql);
	}

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);

	exit;
?>