<?php
//*********************************************************************
//**********            바로빌                **************************
//*********************************************************************
//$CERTKEY = 'A8D38DB3-F634-47D7-A807-2941610496E6'; // 개발서버-바로빌 인증키
$CERTKEY = '350D5AD5-E927-4C96-BACF-081E4B925955'; // 실서버

$spider_id = "spiderpla1";
$spider_corpNum = "7378600580";
$spider_corpName = "(주)스파이더플랫폼";
$spider_bizType = "서비스,도소매외";
$spider_bizClass = "통신기기,통신판매업외";
$spider_ceoName = "권영지";
$spider_addr = "서울특별시 성동구 성수이로7길 7, 307호";
$spider_staffName = "마선빈";
//$spider_email = "ONEEIGHT00@hanmail.net";
//$spider_email = "heemang4989@nate.com";
$spider_email = "masunbin@dream.ceo";

/* 역발행 순서
1. RegistTaxInvoiceReverseEX (저장)
2. ReverseIssueTaxInvoice (프로세스-역발생전송)
3. IssueTaxInvoice (공급자 발행)
*/

//*********************************************************************
//**********              사업자등록           **************************
//*********************************************************************
function registCorp($params) {
	global $CERTKEY, $BaroService_TI;

	$Result = $BaroService_TI->RegistCorp(array(
		'CERTKEY'		=> $CERTKEY,
		'CorpNum'		=> $params['corpNum'],
		'CorpName'		=> $params['corpName'],
		'CEOName'		=> $params['ceoName'],
		'BizType'		=> $params['bizType'],
		'BizClass'		=> $params['bizClass'],
		'PostNum'		=> $params['postNum'],
		'Addr1'			=> $params['addr1'],
		'Addr2'			=> $params['addr2'],
		'MemberName'	=> $params['memberName'],
		'JuminNum'		=> $params['juminNum'],
		'ID'			=> $params['baroId'],
		'PWD'			=> $params['baroPw'],
		'Grade'			=> $params['grade'],
		'TEL'			=> $params['telNo'],
		'HP'			=> $params['hpNo'],
		'Email'			=> $params['email']
	))->RegistCorpResult;

	return $Result;
}

//*********************************************************************
//**********              사업자변경          **************************
//*********************************************************************
function updateCorp($params) {
	global $CERTKEY, $BaroService_TI;

	$Result = $BaroService_TI->UpdateCorpInfo(array(
		'CERTKEY'	=> $CERTKEY,
		'CorpNum'	=> $params['corpNum'],
		'CorpName'	=> $params['corpName'],
		'CEOName'	=> $params['ceoName'],
		'BizType'	=> $params['bizType'],
		'BizClass'	=> $params['bizClass'],
		'PostNum'	=> $params['postNum'],
		'Addr1'		=> $params['addr1'],
		'Addr2'		=> $params['addr2']
	))->UpdateCorpInfoResult;

	return $Result;
}


//*********************************************************************
//**********            사업자휴폐업체크       **************************
//*********************************************************************
function corpState($params) {
	global $CERTKEY, $BaroService_CORPSTATE;

	$Result = $BaroService_CORPSTATE->GetCorpState(array(
		'CERTKEY'		=> $CERTKEY,
		'CorpNum'		=> $params['corpNum'],
		'CheckCorpNum'	=> $params['checkCorpNum']
	))->GetCorpStateResult;

	if ($Result->State < 0){ //실패
		//echo $Result->State;
		$result_status = "1";
		$result_message = "사업자등록번호오류입니다.";

	} else { //성공
		$result_status = "0";
		$result_message = "유효한 사업자등록번호";
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
    );

	return $response;
}

//*********************************************************************
//**********            공인인증서등록         **************************
//*********************************************************************
function certifyRegist($params) {
	global $CERTKEY, $BaroService_TI;

	$Result = $BaroService_TI->GetCertificateRegistURL(array(
		'CERTKEY'	=> $CERTKEY,
		'CorpNum'	=> $params['corpNum'],
		'ID'		=> $params['baroId'],
		'PWD'		=> $params['baroPw']
	))->GetCertificateRegistURLResult;

	return $Result;
}

//*********************************************************************
//**********          공인인증서유효일자       **************************
//*********************************************************************
function certifyExpire($params) {
	global $CERTKEY, $BaroService_TI;

	$Result = $BaroService_TI->GetCertificateExpireDate(array(
		'CERTKEY'	=> $CERTKEY,
		'CorpNum'	=> $params['corpNum'], 
	))->GetCertificateExpireDateResult;

	return $Result;
}

//*********************************************************************
//**********          세금계산서-역발행        **************************
//*********************************************************************
function reverseIssueTaxInvoice($params) {
	global $connect;
	global $CERTKEY, $BaroService_TI;
	global $spider_id, $spider_corpNum, $spider_corpName, $spider_bizType, $spider_bizClass, $spider_ceoName, $spider_addr, $spider_staffName, $spider_email;

	$tergetAssort = $params['tergetAssort'];
	$idx          = $params['idx'];
	$memId        = $params['memId'];
	$itemName     = $params['itemName'];
	$totalAmount  = $params['totalAmount'];

	$purchaseExpiry = date("Ymd");

	//-------------------------------------------
	//세액합계
	//-------------------------------------------
	//$TaxType 이 2 또는 3 으로 셋팅된 경우 0으로 입력
	//-------------------------------------------

	//-------------------------------------------
	//합계금액
	//-------------------------------------------
	//공급가액 총액 + 세액합계 와 일치해야 합니다.
	//-------------------------------------------

	$taxTotal = round($totalAmount * 0.1);   // 세액
	$amountTotal = $totalAmount - $taxTotal; // 공금가액

	$sql = "SELECT m.memName, tm.baroId, tm.corpNum, tm.corpName, tm.ceoName, tm.bizType, tm.bizClass, tm.staffName, tm.email, tm.addr1, tm.addr2 
			FROM tax_member tm 
			   INNER JOIN member m ON tm.memId = m.memId 
			WHERE tm.memid = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->email !== "") $row->email = aes_decode($row->email);

		$memName   = $row->memName;
		$baroId    = $row->baroId;
		$corpNum   = $row->corpNum;
		$corpName  = $row->corpName;
		$ceoName   = $row->ceoName;
		$bizType   = $row->bizType;
		$bizClass  = $row->bizClass;
		$staffName = $row->staffName;
		$email     = $row->email;
		$addr      = $row->addr1 . " " . $row->addr2;

		$sql = "SELECT ifnull(max(idx),0) as maxIdx FROM tax_invoice";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);
		$maxIdx = $row->maxIdx;

		$timestamp = date("ymdHis");
		$mgtNum = $timestamp . "-" . ($maxIdx + 1);
		$mgtNumS = "S-" . $mgtNum;
		$mgtNumR = "R-" . $mgtNum;

		$issueDirection = 1;					//1-정발행, 2-역발행(위수탁 세금계산서는 정발행만 허용)
		$taxInvoiceType = 1;					//1-세금계산서, 2-계산서, 4-위수탁세금계산서, 5-위수탁계산서

		//-------------------------------------------
		//과세형태
		//-------------------------------------------
		//TaxInvoiceType 이 1,4 일 때 : 1-과세, 2-영세
		//TaxInvoiceType 이 2,5 일 때 : 3-면세
		//-------------------------------------------
		$taxType = 1;
		$taxCalcType = 1;						//세율계산방법 : 1-절상, 2-절사, 3-반올림
		$purposeType = 1;						//1-영수, 2-청구

		//-------------------------------------------
		//수정사유코드
		//-------------------------------------------
		//공백-일반세금계산서, 1-기재사항의 착오 정정, 2-공급가액의 변동, 3-재화의 환입, 4-계약의 해제, 5-내국신용장 사후개설, 6-착오에 의한 이중발행
		//-------------------------------------------
		$modifyCode = '';

		$kwon = '';								//별지서식 11호 상의 [권] 항목
		$ho = '';								//별지서식 11호 상의 [호] 항목
		$serialNum = '';						//별지서식 11호 상의 [일련번호] 항목

		$cash = '';								//현금
		$chkBill = '';							//수표
		$note = '';								//어음
		$credit = '';							//외상미수금

		$remark1 = '';
		$remark2 = '';
		$remark3 = '';

		$writeDate = '';						//작성일자 (YYYYMMDD), 공백입력 시 Today로 작성됨.

		//-------------------------------------------
		//공급자 정보 - 정발행시 세금계산서 작성자
		//------------------------------------------
		$invoicerParty = array(
			'MgtNum' 		=> $mgtNumS,			//필수입력 - 연동사부여 문서키
			'CorpNum' 		=> $corpNum,			//필수입력 - 바로빌 회원 사업자번호 ('-' 제외, 10자리)
			'TaxRegID' 		=> '',
			'CorpName' 		=> $corpName,			//필수입력
			'CEOName' 		=> $ceoName,			//필수입력
			'Addr' 			=> $addr,
			'BizType' 		=> $bizType,
			'BizClass' 		=> $bizClass,
			'ContactID' 	=> $baroId,				//필수입력 - 담당자 바로빌 아이디
			'ContactName' 	=> $staffName,			//필수입력
			'TEL' 			=> '',
			'HP' 			=> '',
			'Email' 		=> $email				//필수입력
		);

		//-------------------------------------------
		//공급받는자 정보 - 역발행시 세금계산서 작성자
		//------------------------------------------
		$invoiceeParty = array(
			'MgtNum' 		=> $mgtNumR,
			'CorpNum' 		=> $spider_corpNum,				//필수입력
			'TaxRegID' 		=> '',
			'CorpName' 		=> $spider_corpName,			//필수입력
			'CEOName' 		=> $spider_ceoName,				//필수입력
			'Addr' 			=> $spider_addr,
			'BizType' 		=> $spider_bizType,
			'BizClass' 		=> $spider_bizClass,
			'ContactID' 	=> $spider_id,
			'ContactName' 	=> $spider_staffName,			//필수입력
			'TEL' 			=> '',
			'HP' 			=> '',
			'Email' 		=> $spider_email
		);

		//-------------------------------------------
		//수탁자 정보 - 입력하지 않음
		//------------------------------------------
		$brokerParty = array(
			'MgtNum' 		=> '',
			'CorpNum' 		=> '',
			'TaxRegID' 		=> '',
			'CorpName' 		=> '',
			'CEOName' 		=> '',
			'Addr' 			=> '',
			'BizType' 		=> '',
			'BizClass' 		=> '',
			'ContactID' 	=> '',
			'ContactName' 	=> '',
			'TEL' 			=> '',
			'HP' 			=> '',
			'Email' 		=> ''
		);

		//-------------------------------------------
		//품목
		//-------------------------------------------
		$taxInvoiceTradeLineItems = array(
			'TaxInvoiceTradeLineItem'	=> array(
				array(
					'PurchaseExpiry'=> $purchaseExpiry, //YYYYMMDD
					'Name'			=> $itemName,
					'Information'	=> '',
					'ChargeableUnit'=> '',
					'UnitPrice'		=> '',
					'Amount'		=> $amountTotal,
					'Tax'			=> $taxTotal,
					'Description'	=> ''
				)
			)
		);

		//-------------------------------------------
		//전자세금계산서
		//-------------------------------------------
		$taxInvoice = array(
			'InvoiceKey'				=> '',
			'InvoiceeASPEmail'			=> '',
			'IssueDirection'			=> $issueDirection,
			'TaxInvoiceType'			=> $taxInvoiceType,
			'TaxType'					=> $taxType,
			'TaxCalcType'				=> $taxCalcType,
			'PurposeType'				=> $purposeType,
			'ModifyCode'				=> $modifyCode,
			'Kwon'						=> $kwon,
			'Ho'						=> $ho,
			'SerialNum'					=> $serialNum,
			'Cash'						=> $cash,
			'ChkBill'					=> $chkBill,
			'Note'						=> $note,
			'Credit'					=> $credit,
			'WriteDate'					=> $writeDate,
			'AmountTotal'				=> $amountTotal,
			'TaxTotal'					=> $taxTotal,
			'TotalAmount'				=> $totalAmount,
			'Remark1'					=> $remark1,
			'Remark2'					=> $remark2,
			'Remark3'					=> $remark3,
			'InvoicerParty'				=> $invoicerParty,
			'InvoiceeParty'				=> $invoiceeParty,
			'BrokerParty'				=> $brokerParty,
			'TaxInvoiceTradeLineItems'	=> $taxInvoiceTradeLineItems
		);

		//-------------------------------------------

		$sendSMS = false;							//문자 발송여부 (공급받는자 정보의 HP 항목이 입력된 경우에만 발송됨)
		$forceIssue = false;						//가산세가 예상되는 세금계산서 발행 여부
		$mailTitle = '';							//전송되는 이메일의 제목 설정 (공백 시 바로빌 기본 제목으로 전송됨)

		//-------------------------------------------
//print_r($taxInvoice);
//exit;

		//정발행
		$Result = $BaroService_TI->RegistAndIssueTaxInvoice(array(
			'CERTKEY'	=> $CERTKEY,
			'CorpNum'	=> $taxInvoice['InvoicerParty']['CorpNum'],
			'Invoice'	=> $taxInvoice,
			'SendSMS'	=> $sendSMS,
			'ForceIssue'=> $forceIssue,
			'MailTitle'	=> $mailTitle
		))->RegistAndIssueTaxInvoiceResult;

		if ($Result == "1") {
			if ($baroPw != "") $baroPw = aes128encrypt($baroPw);
			if ($juminNum != "") $juminNum = aes128encrypt($juminNum);
			if ($telNo != "") $telNo = aes128encrypt($telNo);
			if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
			if ($email != "") $email = aes128encrypt($email);

			// 바로빌 사업자회원 테이블에 저장
			$sql = "INSERT INTO tax_invoice (taxAssort, memId, memName, corpNum, corpName, ceoName, amountTotal, taxTotal, totalAmount, mgtNum, tergetAssort, targetIdx, wdate) 
				                     VALUES ('I', '$memId', '$memName', '$corpNum', '$corpName', '$ceoName', '$amountTotal', '$taxTotal', '$totalAmount', '$mgtNum', '$tergetAssort', '$idx', now())";
			$connect->query($sql);

			$result_status = "0";
			$result_message = "발행완료";

		} else {
			// 바로빌 에러 코드 정보
			$errorCode = str_replace("-", "", $Result);
			$sql = "SELECT errorMessage FROM error_code WHERE errorCode = '$errorCode'";
			$result = $connect->query($sql);

			if ($result->num_rows > 0) {
				$row = mysqli_fetch_object($result);
				$errorMessage = $row->errorMessage;
			}

			$result_status = "1";
			$result_message = $errorMessage;
		}

		if ($tergetAssort == "C") { // 현금인출요청
			$sql = "UPDATE cash_request SET issueStatus = '$result_status', issueMessage = '$result_message' WHERE idx = '$idx'";
			$connect->query($sql);
		}
		
	} else {
		$result_status = "1";
		$result_message = "등록된 사업자회원이 아닙니다.";
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
    );

	return $response;
}

//*********************************************************************
//**********       수정   세금계산서-역발행     **************************
//*********************************************************************
function reverseModifyTaxInvoice($params) {
	global $connect;
	global $CERTKEY, $BaroService_TI;
	global $spider_id, $spider_corpNum, $spider_corpName, $spider_bizType, $spider_bizClass, $spider_ceoName, $spider_addr, $spider_staffName, $spider_email;

	$tergetAssort = $params['tergetAssort'];
	$modifyCode   = $params['modifyCode'];
	$idx          = $params['idx'];
	$memId        = $params['memId'];
	$itemName     = $params['itemName'];
	$modifyAmount = $params['totalAmount'];

	$purchaseExpiry = date("Ymd");

	// 정발행된 세금계산서 정보
	$sql = "SELECT totalAmount FROM tax_invoice WHERE tergetAssort = 'C' and targetIdx = '$idx' ORDER BY idx DESC LIMIT 1";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$oldAmount = $row->totalAmount;
		$result_status = "0";

		if ($modifyCode == "2") { // 공급가액 변경
			$totalAmount = $modifyAmount - $oldAmount;
			$result_status = "0";

		} else if ($modifyCode == "4") { // 계약해제
			$totalAmount = 0 - $modifyAmount;
			$result_status = "0";

		} else {
			$result_status = "1";
			$result_message = "발행금액에 변동이 없습니다.";
		}

	} else {
		$result_status = "1";
		$result_message = "기존에 발행된 세금계산서 정보가 없습니다.";
	}

	// 정발행된 세금계산서 정보가 이상이 없으면...
	if ($result_status == "0") {
		$taxTotal = round($totalAmount * 0.1);   // 세액
		$amountTotal = $totalAmount - $taxTotal; // 공금가액		

		$sql = "SELECT m.memName, tm.baroId, tm.corpNum, tm.corpName, tm.ceoName, tm.bizType, tm.bizClass, tm.staffName, tm.email, tm.addr1, tm.addr2 
				FROM tax_member tm 
				   INNER JOIN member m ON tm.memId = m.memId 
				WHERE tm.memid = '$memId'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);

			if ($row->email !== "") $row->email = aes_decode($row->email);

			$memName   = $row->memName;
			$baroId    = $row->baroId;
			$corpNum   = $row->corpNum;
			$corpName  = $row->corpName;
			$ceoName   = $row->ceoName;
			$bizType   = $row->bizType;
			$bizClass  = $row->bizClass;
			$staffName = $row->staffName;
			$email     = $row->email;
			$addr      = $row->addr1 . " " . $row->addr2;

			$sql = "SELECT ifnull(max(idx),0) as maxIdx FROM tax_invoice";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);
			$maxIdx = $row->maxIdx;

			$timestamp = date("ymdHis");
			$mgtNum = $timestamp . "-" . ($maxIdx + 1);
			$mgtNumS = "S-" . $mgtNum;
			$mgtNumR = "R-" . $mgtNum;

			$issueDirection = 1;					//1-정발행, 2-역발행(위수탁 세금계산서는 정발행만 허용)
			$taxInvoiceType = 1;					//1-세금계산서, 2-계산서, 4-위수탁세금계산서, 5-위수탁계산서

			//-------------------------------------------
			//과세형태
			//-------------------------------------------
			//TaxInvoiceType 이 1,4 일 때 : 1-과세, 2-영세
			//TaxInvoiceType 이 2,5 일 때 : 3-면세
			//-------------------------------------------
			$taxType = 1;
			$taxCalcType = 1;						//세율계산방법 : 1-절상, 2-절사, 3-반올림
			$purposeType = 1;						//1-영수, 2-청구

			//-------------------------------------------
			//수정사유코드
			//-------------------------------------------
			//공백-일반세금계산서, 1-기재사항의 착오 정정, 2-공급가액의 변동, 3-재화의 환입, 4-계약의 해제, 5-내국신용장 사후개설, 6-착오에 의한 이중발행
			//-------------------------------------------
			//$modifyCode = $modifyCode;

			$kwon = '';								//별지서식 11호 상의 [권] 항목
			$ho = '';								//별지서식 11호 상의 [호] 항목
			$serialNum = '';						//별지서식 11호 상의 [일련번호] 항목

			$cash = '';								//현금
			$chkBill = '';							//수표
			$note = '';								//어음
			$credit = '';							//외상미수금

			$remark1 = '';
			$remark2 = '';
			$remark3 = '';

			$writeDate = '';						//작성일자 (YYYYMMDD), 공백입력 시 Today로 작성됨.

			//-------------------------------------------
			//공급자 정보 - 정발행시 세금계산서 작성자
			//------------------------------------------
			$invoicerParty = array(
				'MgtNum' 		=> $mgtNumS,			//필수입력 - 연동사부여 문서키
				'CorpNum' 		=> $corpNum,			//필수입력 - 바로빌 회원 사업자번호 ('-' 제외, 10자리)
				'TaxRegID' 		=> '',
				'CorpName' 		=> $corpName,			//필수입력
				'CEOName' 		=> $ceoName,			//필수입력
				'Addr' 			=> $addr,
				'BizType' 		=> $bizType,
				'BizClass' 		=> $bizClass,
				'ContactID' 	=> $baroId,				//필수입력 - 담당자 바로빌 아이디
				'ContactName' 	=> $staffName,			//필수입력
				'TEL' 			=> '',
				'HP' 			=> '',
				'Email' 		=> $email				//필수입력
			);

			//-------------------------------------------
			//공급받는자 정보 - 역발행시 세금계산서 작성자
			//------------------------------------------
			$invoiceeParty = array(
				'MgtNum' 		=> $mgtNumR,
				'CorpNum' 		=> $spider_corpNum,				//필수입력
				'TaxRegID' 		=> '',
				'CorpName' 		=> $spider_corpName,			//필수입력
				'CEOName' 		=> $spider_ceoName,				//필수입력
				'Addr' 			=> $spider_addr,
				'BizType' 		=> $spider_bizType,
				'BizClass' 		=> $spider_bizClass,
				'ContactID' 	=> $spider_id,
				'ContactName' 	=> $spider_staffName,			//필수입력
				'TEL' 			=> '',
				'HP' 			=> '',
				'Email' 		=> $spider_email
			);

			//-------------------------------------------
			//수탁자 정보 - 입력하지 않음
			//------------------------------------------
			$brokerParty = array(
				'MgtNum' 		=> '',
				'CorpNum' 		=> '',
				'TaxRegID' 		=> '',
				'CorpName' 		=> '',
				'CEOName' 		=> '',
				'Addr' 			=> '',
				'BizType' 		=> '',
				'BizClass' 		=> '',
				'ContactID' 	=> '',
				'ContactName' 	=> '',
				'TEL' 			=> '',
				'HP' 			=> '',
				'Email' 		=> ''
			);

			//-------------------------------------------
			//품목
			//-------------------------------------------
			$taxInvoiceTradeLineItems = array(
				'TaxInvoiceTradeLineItem'	=> array(
					array(
						'PurchaseExpiry'=> $purchaseExpiry, //YYYYMMDD
						'Name'			=> $itemName,
						'Information'	=> '',
						'ChargeableUnit'=> '',
						'UnitPrice'		=> '',
						'Amount'		=> $amountTotal,
						'Tax'			=> $taxTotal,
						'Description'	=> ''
					)
				)
			);

			//-------------------------------------------
			//전자세금계산서
			//-------------------------------------------
			$taxInvoice = array(
				'InvoiceKey'				=> '',
				'InvoiceeASPEmail'			=> '',
				'IssueDirection'			=> $issueDirection,
				'TaxInvoiceType'			=> $taxInvoiceType,
				'TaxType'					=> $taxType,
				'TaxCalcType'				=> $taxCalcType,
				'PurposeType'				=> $purposeType,
				'ModifyCode'				=> $modifyCode,
				'Kwon'						=> $kwon,
				'Ho'						=> $ho,
				'SerialNum'					=> $serialNum,
				'Cash'						=> $cash,
				'ChkBill'					=> $chkBill,
				'Note'						=> $note,
				'Credit'					=> $credit,
				'WriteDate'					=> $writeDate,
				'AmountTotal'				=> $amountTotal,
				'TaxTotal'					=> $taxTotal,
				'TotalAmount'				=> $totalAmount,
				'Remark1'					=> $remark1,
				'Remark2'					=> $remark2,
				'Remark3'					=> $remark3,
				'InvoicerParty'				=> $invoicerParty,
				'InvoiceeParty'				=> $invoiceeParty,
				'BrokerParty'				=> $brokerParty,
				'TaxInvoiceTradeLineItems'	=> $taxInvoiceTradeLineItems
			);

			//-------------------------------------------

			$sendSMS = false;							//문자 발송여부 (공급받는자 정보의 HP 항목이 입력된 경우에만 발송됨)
			$forceIssue = false;						//가산세가 예상되는 세금계산서 발행 여부
			$mailTitle = '';							//전송되는 이메일의 제목 설정 (공백 시 바로빌 기본 제목으로 전송됨)

			//-------------------------------------------

			//정발행
			$Result = $BaroService_TI->RegistAndIssueTaxInvoice(array(
				'CERTKEY'	      => $CERTKEY,
				'CorpNum'	      => $taxInvoice['InvoicerParty']['CorpNum'],
				'Invoice'	      => $taxInvoice,
				'ChargeDirection' => 2,
				'SendSMS'	      => $sendSMS,
				'ForceIssue'      => $forceIssue,
				'MailTitle'	      => $mailTitle
			))->RegistAndIssueTaxInvoiceResult;

			if ($Result == "1") {
				if ($baroPw != "") $baroPw = aes128encrypt($baroPw);
				if ($juminNum != "") $juminNum = aes128encrypt($juminNum);
				if ($telNo != "") $telNo = aes128encrypt($telNo);
				if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
				if ($email != "") $email = aes128encrypt($email);

				// 바로빌 사업자회원 테이블에 저장
				$sql = "INSERT INTO tax_invoice (taxAssort, memId, memName, corpNum, corpName, ceoName, amountTotal, taxTotal, totalAmount, mgtNum, tergetAssort, targetIdx, wdate) 
										 VALUES ('I', '$memId', '$memName', '$corpNum', '$corpName', '$ceoName', '$amountTotal', '$taxTotal', '$totalAmount', '$mgtNum', '$tergetAssort', '$idx', now())";
				$connect->query($sql);

				$result_status = "0";
				$result_message = "수정발행완료";

			} else {
				// 바로빌 에러 코드 정보
				$errorCode = str_replace("-", "", $Result);
				$sql = "SELECT errorMessage FROM error_code WHERE errorCode = '$errorCode'";
				$result = $connect->query($sql);

				if ($result->num_rows > 0) {
					$row = mysqli_fetch_object($result);
					$errorMessage = $row->errorMessage;
				}

				$result_status = "1";
				$result_message = $errorMessage;
			}

			if ($tergetAssort == "C") { // 현금인출요청
				$sql = "UPDATE cash_request SET issueCode = '$modifyCode', issueStatus = '$result_status', issueMessage = '$result_message' WHERE idx = '$idx'";
				$connect->query($sql);
			}

		} else {
			$result_status = "1";
			$result_message = "등록된 사업자회원이 아닙니다.";
		}
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
    );

	return $response;
}
?>