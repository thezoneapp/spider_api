<?php
//*********************************************************************
//**********               배열 선언          **************************
//*********************************************************************

// *************************************** 공통
// 예/아니오 
$arrYesNo = array(
	['code' => 'Y', 'name' => '예'],
	['code' => 'N', 'name' => '아니오']
);

$arrYesNo2 = array(
	['value' => 'Y', 'label' => '예'],
	['value' => 'N', 'label' => '아니오']
);

// 사용여부 종류
$arrUseAssort = array(
	['code' => 'Y', 'name' => '사용함'],
	['code' => 'N', 'name' => '사용안함']
);

$arrUseAssort2 = array(
	['value' => 'Y', 'label' => '사용함'],
	['value' => 'N', 'label' => '사용안함']
);

// 정상/에러
$arrErrorYn = array(
	['code' => '0', 'name' => '정상'],
	['code' => '1', 'name' => '오류']
);

// 관리자->사용상태
$arrUseYn = array(
	['code' => 'Y', 'name' => '정상'],
	['code' => 'N', 'name' => '중지']
);

$arrUseYn2 = array(
	['value' => 'Y', 'label' => '정상'],
	['value' => 'N', 'label' => '중지']
);

// 관리자 권한
$arrAdminAuth = array(
	['code' => 'A', 'name' => '스파이더플랫폼'],
	['code' => 'D', 'name' => '드림프리덤']
);

// *************************************** 메뉴
// 메뉴 종류
$arrAuthAssort = array(
	['code' => 'C', 'name' => '프론트'],
	['code' => 'A', 'name' => '관리자'],
	['code' => 'M', 'name' => 'MD플랫폼'],
	['code' => 'S', 'name' => '구독플랫폼'],
	['code' => 'D', 'name' => '드림프리덤']
);

// *************************************** 회원
// 가입비상태 구분
$arrJoinPayStatus = array(
	['code' => '0', 'name' => '없음'],
	['code' => '1', 'name' => '미납'],
	['code' => '9', 'name' => '납부완료']
);

// CMS 상태
$arrCmsStatus = array(
	['code' => '0', 'name' => '접수중'],
	['code' => '1', 'name' => '승인대기'],
	['code' => '2', 'name' => '오류발생'],
	['code' => '8', 'name' => '해지완료'],
	['code' => '9', 'name' => '등록완료']
);

// 가맹점계약 상태
$arrContractStatus = array(
	['code' => '0', 'name' => '접수중'],
	['code' => '1', 'name' => '진행중'],
	['code' => '2', 'name' => '보류중'],
	['code' => '5', 'name' => '계약해지'],
	['code' => '9', 'name' => '계약완료']
);

// 회원 구분
$arrMemAssort = array(
	['code' => 'M', 'name' => 'MD'],
	['code' => 'S', 'name' => '구독']
);

$arrMemAssort2 = array(
	['code' => 'M', 'name' => '플랫폼MD'],
	['code' => 'S', 'name' => '온라인구독']
);

$arrMemAssort3 = array(
	['value' => 'M', 'label' => '플랫폼MD(대리점)'],
	['value' => 'S', 'label' => '온라인구독플랫폼(판매점)']
);

$arrMemAssort4 = array(
	['value' => 'M', 'label' => 'MD가입'],
	['value' => 'S', 'label' => '구독가입']
);

// 회원 상태
$arrMemStatus = array(
	['code' => '0', 'name' => '접수중'],
	['code' => '2', 'name' => '보류중'],
	['code' => '3', 'name' => '사용중지'],
	['code' => '7', 'name' => '탈퇴신청'],
	['code' => '8', 'name' => '탈퇴완료'],
	['code' => '9', 'name' => '승인완료'],
);

// 구독료납부 상태
$arrClearStatus = array(
	['code' => '0', 'name' => '정상'],
	['code' => '1', 'name' => '1회연체'],
	['code' => '2', 'name' => '2회연체']
);

// 회원 로그 코드
$arrLogAssort = array(
	['code' => 'M', 'name' => '가입구분'],
	['code' => 'C', 'name' => 'CMS관련'],
	['code' => 'E', 'name' => '계약관련'],
	['code' => 'P', 'name' => '가입비'],
	['code' => 'D', 'name' => '구독료'],
	['code' => 'A', 'name' => '회원상태'],
);

// 은행 코드
$arrBankCode = array(
	['code' => '002', 'name' => '산업은행'],
	['code' => '003', 'name' => '기업은행'],
	['code' => '004', 'name' => '국민은행'],
	['code' => '007', 'name' => '수협중앙회'],
	['code' => '011', 'name' => '농협은행'],
	['code' => '020', 'name' => '우리은행'],
	['code' => '023', 'name' => 'SC은행'],
	['code' => '027', 'name' => '씨티은행'],
	['code' => '031', 'name' => '대구은행'],
	['code' => '032', 'name' => '부산은행'],
	['code' => '034', 'name' => '광주은행'],
	['code' => '035', 'name' => '제주은행'],
	['code' => '037', 'name' => '전북은행'],
	['code' => '039', 'name' => '경남은행'],
	['code' => '045', 'name' => '새마을금고'],
	['code' => '048', 'name' => '신협중앙회'],
	['code' => '071', 'name' => '우체국'],
	['code' => '081', 'name' => 'KEB하나은행'],
	['code' => '088', 'name' => '신한은행'],
	['code' => '089', 'name' => '케이뱅크'],
	['code' => '090', 'name' => '카카오뱅크']
);

// CMS 동의 상태
$arrAgreeStatus = array(
	['code' => '0', 'name' => '동의대기'],
	['code' => '1', 'name' => '동의오류'],
	['code' => '9', 'name' => '동의완료']
);

// CMS 납부수단 코드
$arrPaymentKind = array(
	['code' => 'CMS',  'name' => '자동이체'],
	['code' => 'CARD', 'name' => '신용카드']
);

$arrPayKind = array(
	['value' => 'CMS',  'label' => '자동이체'],
	['value' => 'CARD', 'label' => '신용카드']
);

// CMS 승인상태 코드
$arrPayStatus = array(
	['code' => '0', 'name' => '승인대기'],
	['code' => '9', 'name' => '승인성공']
);

// CMS 출금 상태
$arrMonthPayStatus = array(
	['code' => '0', 'name' => '출금대기'],
	['code' => '1', 'name' => '출금중'],
	['code' => '5', 'name' => '출금오류'],
	['code' => '9', 'name' => '출금완료']
);

// 납부상태
$arrPayStatus2 = array(
	['code' => '1', 'name' => '미납'],
	['code' => '9', 'name' => '납부']
);

// CMS 로그 코드
$arrCmsLogAssort = array(
	['code' => '1', 'name' => 'CMS등록'],
	['code' => '2', 'name' => '동의등록'],
	['code' => '3', 'name' => 'CMS조회'],
	['code' => '8', 'name' => '신청결과'],
	['code' => '9', 'name' => '등록완료'],
);

// *************************************** 매출
// 매출 구분
$arrSalesAssort = array(
	['code' => 'SA', 'name' => '개설비용(M)'],
	['code' => 'SS', 'name' => '개설비용(구)'],
	['code' => 'PA', 'name' => '월구독료(M)'],
	['code' => 'PS', 'name' => '월구독료(구)']
);

// *************************************** 수수료
// 수수료 종류
$arrCommiAssort = array(
	['code' => 'CS', 'name' => 'MD유치'],
	['code' => 'MA', 'name' => '월구독료(M)'],
	['code' => 'MS', 'name' => '월구독료(구)'],
	['code' => 'R1', 'name' => '렌탈수수료'],
	['code' => 'R2', 'name' => '렌탈(뎁)'],
	['code' => 'P1', 'name' => '휴대폰신청'],
	['code' => 'A1', 'name' => '다이렉트보험'],
);

// 입금상태
$arrAccountStatus = array(
	['code' => '0', 'name' => '입금예정'],
	['code' => '9', 'name' => '입금완료']
);

// 보류종류
$arrClearAssort = array(
	['code' => '1', 'name' => '가입비미납'],
	['code' => '2', 'name' => '구독료연체']
);

// *************************************** 정산
// 정산 상태
$arrAccurateStatus = array(
	['code' => '0', 'name' => '정산대기'],
	['code' => '1', 'name' => '정산중'],
	['code' => '2', 'name' => '정산보류'],
	['code' => '9', 'name' => '정산완료']
);

// 세금구분 배열
$arrTaxAssort = array(
	['code' => 'P', 'name' => '사업소득세'],
	['code' => 'T', 'name' => '세금계산서']
);

// 사업자구분 배열
$arrBusinessAssort = array(
	['code' => 'P', 'name' => '개인'],
	['code' => 'T', 'name' => '사업자'],
);

// 정산대금구분 배열
$arrAccountAssort = array(
	['code' => 'A', 'name' => '정산'],
	['code' => 'P', 'name' => '입금']
);

// *************************************** 포인트
// 포인트 구분
$arrPointAssort = array(
	['code' => 'OA', 'name' => '정산적립'],
	['code' => 'N1', 'name' => '기타적립'],
	['code' => 'N2', 'name' => '기타차감'],
	['code' => 'OC', 'name' => '현금인출'],
	['code' => 'IJ', 'name' => '가입비'],
);

// 출금요청 구분
$arrCashRequestStatus = array(
	['code' => '0', 'name' => '대기중'],
	['code' => '1', 'name' => '처리중'],
	['code' => '2', 'name' => '보류중'],
	['code' => '7', 'name' => '취소요청'],
	['code' => '8', 'name' => '취소완료'],
	['code' => '9', 'name' => '입금완료'],
);

// 세금계산서 구분 배열
$arrTaxIssueAssort = array(
	['code' => 'I', 'name' => '매입'],
	['code' => 'O', 'name' => '매출']
);

// 세금계산서 수정발행 사유 구분 배열
$arrTaxModifyAssort = array(
	['value' => '2', 'label' => '공급가액'],
	['value' => '4', 'label' => '계약해제']
);

// *************************************** 휴대폰신청 **********************************************************
// 통신망
$arrImtAssort = array(
	['code' => 'L', 'name' => 'LTE'],
	['code' => '5', 'name' => '5G'],
);

$arrImtAssort2 = array(
	['value' => 'L', 'label' => 'LTE'],
	['value' => '5', 'label' => '5G']
);

// 제조사
$arrMakerAssort = array(
	['code' => 'S', 'name' => '삼성'],
	['code' => 'L', 'name' => 'LG'],
	['code' => 'A', 'name' => '애플'],
	['code' => 'O', 'name' => '기타'],
);

// 용량
$arrCapacityAssort = array(
	['code' => '32',  'name' => '32GB'],
	['code' => '64',  'name' => '64GB'],
	['code' => '128', 'name' => '128GB'],
	['code' => '256', 'name' => '256GB'],
	['code' => '512', 'name' => '512GB'],
);

// 통신사
$arrTelecomAssort = array(
	['code' => 'S', 'name' => 'SKT'],
	['code' => 'K', 'name' => 'KT'],
	['code' => 'L', 'name' => 'LGU+'],
	['code' => 'A', 'name' => '알뜰폰'],
);

$arrTelecomAssort2 = array(
	['value' => 'S', 'label' => 'SKT'],
	['value' => 'K', 'label' => 'KT'],
	['value' => 'L', 'label' => 'LGU+'],
	['value' => 'A', 'label' => '알뜰폰'],
);

$arrTelecomAssort3 = array(
	['code' => '0', 'name' => '공용'],
	['code' => 'S', 'name' => 'SKT'],
	['code' => 'K', 'name' => 'KT'],
	['code' => 'L', 'name' => 'LGU+'],
	['code' => 'A', 'name' => '알뜰폰'],
);

// 요금제 구분
$arrPlanAssort = array(
	['code' => '4',  'name' => '4만원'],
	['code' => '5',  'name' => '5만원'],
	['code' => '7',  'name' => '7만원'],
	['code' => '8',  'name' => '8만원'],
	['code' => '10', 'name' => '10만원'],
	['code' => '0',  'name' => '기타'],
);

// 통신사보조금구분
$arrSupportAssort = array(
	['code' => 'S', 'name' => '공시'],
	['code' => 'C', 'name' => '선택'],
);

$arrSupportAssort2 = array(
	['code' => 'S', 'name' => '공시지원'],
	['code' => 'C', 'name' => '선택약정'],
);

$arrDiscountType = array(
	['code' => 'M', 'name' => '단말기할인'],
	['code' => 'C', 'name' => '요금할인'],
);

$arrDiscountType2 = array(
	['value' => 'M', 'label' => '단말기할인'],
	['value' => 'C', 'label' => '요금할인']
);

$arrDiscountType3 = array(
	['code' => 'S', 'name' => '공시지원할인'],
	['code' => 'C', 'name' => '요금할인'],
);

$arrDiscountType4 = array(
	['value' => 'S', 'label' => '공시지원할인'],
	['value' => 'C', 'label' => '요금할인']
);

// 할인종류
$arrDiscountAssort = array(
	['code' => 'C', 'name' => '제휴카드'],
	['code' => 'S', 'name' => '공시지원'],
	['code' => 'A', 'name' => '사업자할인'],
);

// 할인혜택
$arrBenefitAssort = array(
	['code' => 'M', 'name' => '단말기할인'],
	['code' => 'C', 'name' => '캐시백'],
);

// 전체/개별 구분
$arrAllYnAssort = array(
	['code' => 'Y', 'name' => '전체'],
	['code' => 'N', 'name' => '개별'],
);

$arrAllYnAssort2 = array(
	['value' => 'Y', 'label' => '전체'],
	['value' => 'N', 'label' => '개별']
);

// 할부개월	
$arrInstallmentOptions = array(
	['code' => '24', 'name' => '24'],
	['code' => '30', 'name' => '30'],
	['code' => '36', 'name' => '36'],
);

// 단말기출고지 구분
$arrOutPlaceAssort = array(
	['code' => 'I', 'name' => '내부'],
	['code' => 'O', 'name' => '외부'],
);

$arrOutPlaceAssort2 = array(
	['value' => 'I', 'label' => '내부'],
	['value' => 'O', 'label' => '외부']
);

// 신청 구분
$arrRequestAssort = array(
	['code' => 'N', 'name' => '신규'],
	['code' => 'M', 'name' => '번호이동'],
	['code' => 'C', 'name' => '기기변경'],
);

// 휴대폰신청 상태
$arrRequestStatus = array(
	['code' => '0', 'name' => '접수중'],
	['code' => '1', 'name' => '상담완료'],
	['code' => '5', 'name' => '신청취소'],
	['code' => '8', 'name' => '개통해지'],
	['code' => '9', 'name' => '개통완료'],
);

// 휴대폰신청 상태
$arrRequestStatus2 = array(
	['code' => '0', 'name' => '접수중'],
	['code' => '1', 'name' => '상담완료'],
	['code' => '5', 'name' => '신청취소'],
);

// 휴대폰신청 로그
$arrRequestLog = array(
	['code' => '0', 'name' => '신청접수'],
	['code' => '1', 'name' => '상담완료'],
	['code' => '2', 'name' => '보류중'],
	['code' => '5', 'name' => '신청취소'],
	['code' => '8', 'name' => '개통해지'],
	['code' => '9', 'name' => '개통완료'],
);

// 접수채널 구분
$arrChannelAssort = array(
	['code' => 'S',  'name' => '간편신청'],
	['code' => '1',  'name' => '1분신청'],
	['code' => 'P',  'name' => 'PC신청'],
	['code' => 'L',  'name' => '링크신청'],
);

// 유심구매여부
$arrUsimAssort = array(
	['code' => '0', 'name' => '기존'],
	['code' => '1', 'name' => '구매'],
);

// 개통상태 구분
$arrOpeningStatus = array(
	['code' => '0', 'name' => '개통전'],
	['code' => '9', 'name' => '개통완료'],
	['code' => '8', 'name' => '개통취소'],
);

// 배송상태
$arrDeliveryStatus = array(
	['code' => '0', 'name' => '발송전'],
	['code' => '1', 'name' => '발송처리'],
	['code' => '2', 'name' => '배송중'],
	['code' => '9', 'name' => '배송완료'],
);

// 가입신청서 상태
$arrWriteStatus = array(
	['code' => '0', 'name' => '작성전'],
	['code' => '9', 'name' => '작성완료'],
);

// *************************************** 다이렉트보험 **********************************************************
// 보험만기일
$arrExpiredDate = array(
	['code' => '모름', 'name' => '모름'],
	['code' => '신차', 'name' => '신차'],
	['code' => '1월', 'name' => '1월'],
	['code' => '2월', 'name' => '2월'],
	['code' => '3월', 'name' => '3월'],
	['code' => '4월', 'name' => '4월'],
	['code' => '5월', 'name' => '5월'],
	['code' => '6월', 'name' => '6월'],
	['code' => '7월', 'name' => '7월'],
	['code' => '8월', 'name' => '8월'],
	['code' => '9월', 'name' => '9월'],
	['code' => '10월', 'name' => '10월'],
	['code' => '11월', 'name' => '11월'],
	['code' => '12월', 'name' => '12월'],
);

// 거주지역
$arrCustRegion = array(
	['code' => '서울', 'name' => '서울'],
	['code' => '강원', 'name' => '강원'],
	['code' => '경기', 'name' => '경기'],
	['code' => '경남', 'name' => '경남'],
	['code' => '경북', 'name' => '경북'],
	['code' => '광주', 'name' => '광주'],
	['code' => '대구', 'name' => '대구'],
	['code' => '대전', 'name' => '대전'],
	['code' => '부산', 'name' => '부산'],
	['code' => '울산', 'name' => '울산'],
	['code' => '인천', 'name' => '인천'],
	['code' => '전남', 'name' => '전남'],
	['code' => '전북', 'name' => '전북'],
	['code' => '제주', 'name' => '제주'],
	['code' => '충남', 'name' => '충남'],
	['code' => '충북', 'name' => '충북'],
	['code' => '세종', 'name' => '세종'],
);

// 차량번호 타입
$arrCarNoType = array(
	['code' => '1', 'name' => '차량'],
	['code' => '2', 'name' => '차대'],
);

$arrCarNoType2 = array(
	['value' => '1', 'label' => '차량'],
	['value' => '2', 'label' => '차대']
);

$arrCarNoType3 = array(
	['code' => '1', 'name' => '차량번호'],
	['code' => '2', 'name' => '차대번호'],
);

// 신청상태
$arrInsuStatus = array(
	['code' => '0', 'name' => '접수중'],
	['code' => '7', 'name' => '상담완료'],
	['code' => '8', 'name' => '계약취소'],
	['code' => '9', 'name' => '계약완료'],
);

// *************************************** 게시판 **********************************************************
// 사용자 구분
$arrUserAssort = array(
	['code' => 'A', 'name' => '관리자'],
	['code' => 'P', 'name' => '파트너'],
	['code' => 'M', 'name' => '회원'],
	['code' => 'N', 'name' => '비회원'],
);

// *************************************** SMS 메세지
// 메세지구분 구분
$arrSmsAssort = array(
	['code' => 'J', 'name' => '회원가입'],
	['code' => 'M', 'name' => '회원관리'],
	['code' => 'C', 'name' => 'CMS출금'],
	['code' => 'A', 'name' => '정산관련'],
	['code' => 'D', 'name' => '다운라인'],
	['code' => 'H', 'name' => '휴대폰신청'],
	['code' => 'B', 'name' => '게시판관련'],
	['code' => 'I', 'name' => '다이렉트보험'],
);
?>