<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include './BaroService_TI.php';

	/*
	* 바로빌 > 일반(수정)세금계산서 "등록" 과 "발행" 을 한번에 처리
	* parameter :
		memId:       회원ID
		totalAmount: 합계금액
	*/

	$data_back = json_decode(file_get_contents('php://input'));
	$memId = $data_back->{'memId'};
	$totalAmount = $data_back->{'totalAmount'};

	$spider_id = "spiderpla1";
	$spider_corpNum = "7378600580";
	$spider_corpName = "(주)스파이더플랫폼";
	$spider_bizType = "서비스,도소매외";
	$spider_bizClass = "통신기기,통신판매업외";
	$spider_ceoName = "권영지";
	$spider_addr = "서울특별시 성동구 성수이로7길 7, 307호";
	$spider_staffName = "마선빈";

	$memId = "a27233377";
	$totalAmount = "155556";

	$sql = "SELECT baroId, corpNum, corpName, ceoName, bizType, bizClass, staffName, email, addr1, addr2 FROM tax_member WHERE memid = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->email !== "") $row->email = aes_decode($row->email);

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
		$result2 = $connect->query($sql);
		$row2 = mysqli_fetch_object($result2);
		$maxIdx = $row2->maxIdx;

		$mgtNumS = "S-" . ($maxIdx + 1);
		$mgtNumR = "R-" . ($maxIdx + 1);

		$issueDirection = 2;					//1-정발행, 2-역발행(위수탁 세금계산서는 정발행만 허용)
		$taxInvoiceType = 1;					//1-세금계산서, 2-계산서, 4-위수탁세금계산서, 5-위수탁계산서

		//-------------------------------------------
		//과세형태
		//-------------------------------------------
		//TaxInvoiceType 이 1,4 일 때 : 1-과세, 2-영세
		//TaxInvoiceType 이 2,5 일 때 : 3-면세
		//-------------------------------------------
		$taxType = 1;
		$taxCalcType = 3;						//세율계산방법 : 1-절상, 2-절사, 3-반올림

		$purposeType = 1;						//1-영수, 2-청구

		$kwon = '';								//별지서식 11호 상의 [권] 항목
		$ho = '';								//별지서식 11호 상의 [호] 항목
		$serialNum = '';						//별지서식 11호 상의 [일련번호] 항목

		//-------------------------------------------
		//합계금액
		//-------------------------------------------
		//공급가액 총액 + 세액합계 와 일치해야 합니다.
		//-------------------------------------------
		$totalAmount = $totalAmount;

		$taxTotal = round($totalAmount * 0.1);
		$amountTotal = $totalAmount - $taxTotal;

		//-------------------------------------------
		//공급가액 총액
		//-------------------------------------------
		//$amountTotal = $amountTotal;

		//-------------------------------------------
		//세액합계
		//-------------------------------------------
		//$TaxType 이 2 또는 3 으로 셋팅된 경우 0으로 입력
		//-------------------------------------------
		//$taxTotal = '';

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
			'MgtNum' 		=> $mgtNumS,
			'CorpNum' 		=> $corpNum,				//필수입력 - 바로빌 회원 사업자번호 ('-' 제외, 10자리)
			'TaxRegID' 		=> '',
			'CorpName' 		=> $corpName,				//필수입력
			'CEOName' 		=> $ceoName,				//필수입력
			'Addr' 			=> $addr,
			'BizType' 		=> $bizType,
			'BizClass' 		=> $bizClass,
			'ContactID' 	=> $baroId,					//필수입력 - 담당자 바로빌 아이디
			'ContactName' 	=> $staffName,				//필수입력
			'TEL' 			=> '',
			'HP' 			=> '',
			'Email' 		=> $email					//필수입력
		);

		//-------------------------------------------
		//공급받는자 정보 - 역발행시 세금계산서 작성자
		//------------------------------------------
		$invoiceeParty = array(
			'MgtNum' 		=> $mgtNumR,			//필수입력 - 연동사부여 문서키
			'CorpNum' 		=> $spider_corpNum,		//필수입력
			'TaxRegID' 		=> '',
			'CorpName' 		=> $spider_corpName,	//필수입력
			'CEOName' 		=> $spider_ceoName,		//필수입력
			'Addr' 			=> $spider_addr,
			'BizType' 		=> $spider_bizType,
			'BizClass' 		=> $spider_bizClass,
			'ContactID' 	=> $spider_id,			//필수입력 - 담당자 바로빌 아이디
			'ContactName' 	=> $spider_staffName,	//필수입력
			'TEL' 			=> '',
			'HP' 			=> '',
			'Email' 		=> ''				//필수입력
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
					'PurchaseExpiry'=> '',			//YYYYMMDD
					'Name'			=> '',
					'Information'	=> '',
					'ChargeableUnit'=> '',
					'UnitPrice'		=> '',
					'Amount'		=> '',
					'Tax'			=> '',
					'Description'	=> ''
				),
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
			'ModifyCode'				=> '',
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
//print_r($taxInvoice);
//exit;
		//-------------------------------------------

		$sendSMS = false;							//문자 발송여부 (공급받는자 정보의 HP 항목이 입력된 경우에만 발송됨)
		$forceIssue = false;						//가산세가 예상되는 세금계산서 발행 여부
		$mailTitle = '';							//전송되는 이메일의 제목 설정 (공백 시 바로빌 기본 제목으로 전송됨)

		//-------------------------------------------
print_r($BaroService_TI);
exit;
		//정발행
		$Result = $BaroService_TI->RegistAndReverseIssueTaxInvoice(array(
			'CERTKEY'	=> $BAROBILL_CERTKEY,
			'CorpNum'	=> $taxInvoice['InvoiceeParty']['CorpNum'],
			'Invoice'	=> $taxInvoice,
			'SendSMS'	=> $sendSMS,
			'ForceIssue'=> $forceIssue,
			'MailTitle'	=> $mailTitle,
		))->RegistAndReverseIssueTaxInvoiceResult;
print_r($Result);
exit;
		if ($Result == "1") {
			if ($baroPw != "") $baroPw = aes128encrypt($baroPw);
			if ($juminNum != "") $juminNum = aes128encrypt($juminNum);
			if ($telNo != "") $telNo = aes128encrypt($telNo);
			if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
			if ($email != "") $email = aes128encrypt($email);

			// 바로빌 사업자회원 테이블에 저장
			$sql = "INSERT INTO tax_invoice (taxAssort, memId, corpNum, corpName, ceoName, amountTotal, taxTotal, totalAmount, issueStatus, wdate) 
			                         VALUES ('I', '$memId', '$corpNum', '$corpName', '$ceoName', '$amountTotal', '$taxTotal', '$totalAmount', '0', now())";
			$connect->query($sql);

			$result_status = "0";
			$result_message = "발행이 완료되었습니다.";

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

		$result_status =  "0";
		$result_message = "발행완료.";

	} else {
		$result_status =  "1";
		$result_message = "바로빌에 등록되지 않은 회원입니다.";
	}	

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
