<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function adminHelpRespond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function adminHelpEnvironmentValue(string $name): string
{
    $value = getenv($name);
    if ($value === false) {
        $value = $_SERVER[$name] ?? '';
    }
    return trim((string) $value);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    adminHelpRespond(405, ['success' => false, 'message' => 'Hindi suportado ang request na ito.']);
}

$role = strtolower(trim((string) ($_SESSION['role'] ?? '')));
if (empty($_SESSION['user_id']) || !in_array($role, ['admin', 'super admin', 'superadmin'], true)) {
    adminHelpRespond(403, ['success' => false, 'message' => 'Para lamang ito sa Admin at Super Admin.']);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$sessionToken = $_SESSION['admin_help_chat_csrf'] ?? '';
if (!is_string($csrfToken) || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    adminHelpRespond(403, ['success' => false, 'message' => 'Nag-expire ang session. I-refresh ang page at subukan ulit.']);
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 4096) {
    adminHelpRespond(413, ['success' => false, 'message' => 'Masyadong mahaba ang tanong.']);
}

$input = json_decode(file_get_contents('php://input'), true);
$question = is_array($input) && is_string($input['question'] ?? null) ? trim($input['question']) : '';
if ($question === '' || mb_strlen($question, 'UTF-8') > 500) {
    adminHelpRespond(422, ['success' => false, 'message' => 'Maglagay ng tanong na hanggang 500 characters.']);
}

$now = time();
$recentRequests = array_values(array_filter(
    $_SESSION['admin_help_chat_requests'] ?? [],
    static fn($timestamp) => is_int($timestamp) && $timestamp > $now - 60
));
if (count($recentRequests) >= 12) {
    adminHelpRespond(429, ['success' => false, 'message' => 'Sandali muna bago magpadala ulit ng tanong.']);
}
$recentRequests[] = $now;
$_SESSION['admin_help_chat_requests'] = $recentRequests;

$guides = [
    [
        'keywords' => ['schedule change', 'schedule edit', 'edit schedule', 'schedule approval', 'admin edit'],
        'answer' => 'Kapag Admin ang nag-edit ng schedule, hindi agad mababago ang appointment. Isusumite muna ito para sa Super Admin approval. Sa Schedule Management, buksan ang “Admin Edit Approvals,” suriin ang current at proposed schedule, at piliin ang Approve o Reject. Kapag na-approve, maa-update ang schedule at manonotify ang linked account.',
        'context' => 'Gabay sa approval ng schedule edit: Ang edit na isinumite ng Admin ay pending lamang at hindi binabago ang kasalukuyang appointment hangga’t hindi inaaprubahan. Sa Schedule Management ng Super Admin, buksan ang Admin Edit Approvals, ikumpara ang current at proposed schedule, at piliin ang Approve o Reject. Kapag naaprubahan, maa-apply ang mga pagbabago at makakatanggap ng notification ang linked scheduled user account.',
        'links' => [['label' => 'Buksan ang Schedule Management', 'url' => 'schedule_management.php']],
    ],
    [
        'keywords' => ['resched', 'reschedule', 'rescheduled', 'requested', 'request', 'approve', 'reject'],
        'answer' => 'Para mag-review ng reschedule request: buksan ang Schedule Management at piliin ang “Reschedule Requests.” Pindutin ang “Review” sa tamang request, tingnan ang hinihinging petsa, oras, at dahilan, piliin ang “Approve Reschedule” o “Reject Reschedule,” saka pindutin ang “Process Request.” Kapag inaprubahan, maa-update ang schedule sa hinihinging petsa at oras at manonotify ang requester.',
        'context' => 'Gabay sa reschedule request ng pasyente: Buksan ang Schedule Management, piliin ang Reschedule Requests tab, at pindutin ang Review sa request. Suriin ang requested date, requested time, at reason. Sa Action dropdown piliin ang Approve Reschedule o Reject Reschedule, saka pindutin ang Process Request. Ang pag-approve ay mag-a-update sa schedule sa requested date at time; ang pag-reject ay pananatilihin ang kasalukuyang schedule. Makakatanggap ng notification ang requester.',
        'links' => [['label' => 'Buksan ang Reschedule Requests', 'url' => 'schedule_management.php']],
    ],
    [
        'keywords' => ['schedule', 'sched', 'appointment', 'iskedyul', 'batch', 'date', 'time'],
        'answer' => 'Para mag-set ng schedule: buksan ang Schedule Management, piliin ang “Add New Batch Schedule,” piliin kung Child o Maternal, ilagay ang petsa, oras, at service type, piliin ang mga pasyente, at pindutin ang “Save Batch Schedule.” Para mag-edit ng schedule, gamitin ang Edit sa row. Ang Admin edit ay kailangan munang aprubahan ng Super Admin; ang Super Admin ay maaaring mag-apply agad ng edit.',
        'context' => 'Gabay sa schedule management: Sa Schedule Management piliin ang Add New Batch Schedule. Pumili ng Child o Maternal schedule, ilagay ang schedule date at time, service type, piliin ang pasyente o mga pasyente, maglagay ng optional notes, at pindutin ang Save Batch Schedule. Para baguhin ang existing schedule, gamitin ang Edit action, baguhin ang detalye o status (Pending, Completed, Cancelled, o Reschedule Requested), at pindutin ang Update Schedule. Maaari ring mag-delete gamit ang Delete action at kumpirmahin ito. Ang reschedule request mula sa user ay hiwalay na nirereview sa Reschedule Requests tab.',
        'links' => [['label' => 'Buksan ang Schedule Management', 'url' => 'schedule_management.php']],
    ],
    [
        'keywords' => ['approval', 'approvals', 'approve', 'approving', 'pending', 'verify', 'verification', 'accept', 'review', 'registration', 'enrollment', 'enroll'],
        'answer' => 'Para mag-review ng pending maternal o child registration, buksan ang Home dashboard at hanapin ang Pending Approvals. Buksan at suriin ang record; para sa maternal registration kumpletuhin ang verification bago isumite, at para sa child registration kumpirmahin ang detalye. Maaari ring i-reject ang pending registration mula sa dashboard.',
        'context' => 'Gabay sa registration approvals: Sa Home dashboard pumunta sa Pending Approvals at piliin ang pending maternal o infant enrollment. Buksan at suriin ang detalye. Para sa maternal registration, kumpletuhin ang verification form at isumite para ma-approve; para sa infant registration, kumpirmahin ang impormasyon sa review modal. Maaari ring i-reject ang pending entry mula sa dashboard.',
        'links' => [['label' => 'Buksan ang Admin Home', 'url' => 'admin_dashboard.php']],
    ],
    [
        'keywords' => ['record', 'records', 'history', 'search', 'find', 'maghanap', 'hanapin', 'filter', 'maternalrecord', 'childrecord'],
        'answer' => 'Para maghanap ng patient record, buksan ang Maternal Records o Child/Infant Records sa navigation menu at gamitin ang search o filters. Piliin ang record para makita ang kaugnay na detalye. Huwag maglagay ng pangalan o personal na impormasyon dito sa chat.',
        'context' => 'Gabay sa paghahanap ng records: Buksan ang Maternal Records o Child/Infant Records mula sa navigation. Gamitin ang search field at available na filters upang mahanap ang record. Buksan ang record sa mismong system. Huwag magpadala ng pangalan, personal, o health information sa chatbot.',
        'links' => [
            ['label' => 'Maternal Records', 'url' => 'admin_maternal_rec.php'],
            ['label' => 'Child/Infant Records', 'url' => 'admin_child_rec.php'],
        ],
    ],
    [
        'keywords' => ['infant', 'baby', 'child', 'bata', 'sanggol', 'newborn'],
        'answer' => 'Para mag-register ng child o infant record: buksan ang Child Management > Registration Form. Punan ang mga required na detalye ng bata at magulang, suriin ang impormasyon, at pindutin ang “Confirm Registration.”',
        'context' => 'Gabay sa child/infant registration: Sa Child Management piliin ang Registration Form. Kumpletuhin ang required na detalye ng bata, birth information, address at magulang. Suriin ang form at pindutin ang Confirm Registration.',
        'links' => [['label' => 'Buksan ang Child Registration', 'url' => 'admin_child_reg.php']],
    ],
    [
        'keywords' => ['maternal', 'pregnancy', 'pregnant', 'buntis', 'nanay', 'mother'],
        'answer' => 'Para mag-register ng maternal record: buksan ang Maternal Management > Registration Form. Punan ang client at mga kaugnay na detalye, tiyaking tama ang impormasyon, at pindutin ang “Confirm Registration.”',
        'context' => 'Gabay sa maternal registration: Sa Maternal Management piliin ang Registration Form. Punan ang required na client details at kaugnay na impormasyon, tiyaking tama ang datos, at pindutin ang Confirm Registration.',
        'links' => [['label' => 'Buksan ang Maternal Registration', 'url' => 'admin_maternal_reg.php']],
    ],
    [
        'keywords' => ['register', 'registration', 'patient', 'record', 'add', 'enroll', 'enrollment'],
        'answer' => 'Para magdagdag ng patient record, gamitin ang Registration Form sa Maternal Management o Child Management depende sa uri ng record. Punan ang required fields at pindutin ang “Confirm Registration.”',
        'context' => 'Gabay sa patient registration: Pumili ng Registration Form sa Maternal Management para sa maternal record o Child Management para sa child/infant record. Punan ang required fields, suriin ang detalye, at pindutin ang Confirm Registration.',
        'links' => [
            ['label' => 'Maternal Registration', 'url' => 'admin_maternal_reg.php'],
            ['label' => 'Child Registration', 'url' => 'admin_child_reg.php'],
        ],
    ],
    [
        'keywords' => ['checkup', 'check-up', 'prenatal', 'visit', 'consultation', 'health record', 'health records', 'clinical'],
        'answer' => 'Para magdagdag o mag-update ng maternal o child health/check-up record, buksan ang kaukulang Maternal Health Record o Child Health Record sa navigation menu, hanapin at piliin ang pasyente, saka gamitin ang available na form para sa bagong entry. Available ito sa Super Admin.',
        'context' => 'Gabay sa health records: Gamitin ang Maternal Health Record o Child Health Record mula sa navigation menu. Hanapin at piliin ang pasyente sa page at gamitin ang form doon para magdagdag o mag-update ng record. Huwag maglagay ng patient details sa chatbot.',
        'links' => [
            ['label' => 'Maternal Health Records', 'url' => 'admin_maternal_hr.php'],
            ['label' => 'Child Health Records', 'url' => 'admin_child_hr.php'],
        ],
        'super_admin_only' => true,
    ],
    [
        'keywords' => ['worker', 'staff', 'healthworker', 'personnel'],
        'answer' => 'Ang pag-manage o pag-register ng health worker ay para sa Super Admin. Buksan ang Health Worker sa navigation menu para makita ang personnel directory at mga available na action.',
        'context' => 'Gabay sa health worker: Ang Health Worker directory at pag-manage ng personnel ay available sa Super Admin sa pamamagitan ng Health Worker navigation item. Kung Admin ang role mo, humingi ng tulong sa Super Admin.',
        'links' => [['label' => 'Health Worker Directory', 'url' => 'admin_health_workers.php']],
        'super_admin_only' => true,
    ],
    [
        'keywords' => ['vaccine', 'vaccines', 'bakuna', 'inventory', 'stock'],
        'answer' => 'Para sa vaccine inventory, buksan ang Vaccines sa navigation menu. Piliin ang action para magdagdag ng vaccine o tumanggap ng stock, punan ang required fields, at i-save. Available ito sa Super Admin.',
        'context' => 'Gabay sa vaccine inventory: Buksan ang Vaccines sa navigation menu; piliin ang Add Vaccine / Receive Stock, punan ang kinakailangang detalye kasama ang source at oras, pagkatapos i-save. Ang page ay available sa Super Admin.',
        'links' => [['label' => 'Vaccine Inventory', 'url' => 'vaccine_inventory.php']],
        'super_admin_only' => true,
    ],
    [
        'keywords' => ['sms', 'text', 'reminder', 'message'],
        'answer' => 'Para sa SMS reminders o test message, buksan ang SMS Management sa navigation menu. Sundin ang label sa seksyong Send Test SMS o Send 3-Day Reminders at tiyaking tama ang recipients bago magpadala. Available ito sa Super Admin.',
        'context' => 'Gabay sa SMS: Sa SMS Management, gamitin ang Send Test SMS para sa test o Send 3-Day Reminders para sa mga kwalipikadong appointment. I-review muna ang recipient details bago magpadala. Available ito sa Super Admin.',
        'links' => [['label' => 'SMS Management', 'url' => 'sms_management.php']],
        'super_admin_only' => true,
    ],
    [
        'keywords' => ['history', 'logs', 'activity', 'audit'],
        'answer' => 'Para makita ang history ng admin activities, piliin ang History sa navigation menu. Available ito sa Super Admin.',
        'context' => 'Gabay sa activity history: Piliin ang History sa navigation menu para tingnan ang admin activity logs. Available ito sa Super Admin.',
        'links' => [['label' => 'Activity History', 'url' => 'activity_logs.php']],
        'super_admin_only' => true,
    ],
    [
        'keywords' => ['settings', 'account', 'password', 'email', 'profile', 'backup', 'maintenance'],
        'answer' => 'Para baguhin ang account profile o password, buksan ang Settings. Para sa Super Admin, nasa Super Admin Settings din ang system information, account recovery, at database backup. Iwasang magbahagi ng password o recovery code sa chat.',
        'context' => 'Gabay sa settings: Buksan ang Settings para sa account settings. Ang Super Admin Settings ay may system information, profile at security, account recovery, at database backup. Huwag kailanman ibigay sa chatbot ang password, PIN, o recovery code.',
        'links' => [['label' => 'Buksan ang Settings', 'url' => 'admin_settings.php']],
    ],
];

$normalizedQuestion = function_exists('mb_strtolower')
    ? mb_strtolower($question, 'UTF-8')
    : strtolower($question);
$questionTokens = [];
preg_match_all('/[\p{L}\p{N}]+/u', $normalizedQuestion, $questionMatches);
$questionTokens = $questionMatches[0] ?? [];
$questionText = ' ' . implode(' ', $questionTokens) . ' ';
$matchedGuide = null;
$bestScore = 0;
foreach ($guides as $guide) {
    $score = 0;
    foreach ($guide['keywords'] as $keyword) {
        $keywordTokens = [];
        preg_match_all('/[\p{L}\p{N}]+/u', $keyword, $keywordMatches);
        $keywordTokens = $keywordMatches[0] ?? [];
        $keywordPhrase = implode(' ', $keywordTokens);
        $containsKeyword = count($keywordTokens) > 1
            ? strpos($questionText, ' ' . $keywordPhrase . ' ') !== false
            : in_array($keywordPhrase, $questionTokens, true);
        if ($containsKeyword) {
            $score += strpos($keyword, ' ') !== false ? 3 : 1;
        }
    }
    if ($score > $bestScore) {
        $matchedGuide = $guide;
        $bestScore = $score;
    }
}

if ($matchedGuide === null) {
    adminHelpRespond(200, [
        'success' => true,
        'answer' => 'Wala pa akong guide para sa tanong na iyan. Maaari akong tumulong sa schedule, reschedule requests, registration approvals, patient records, health worker, vaccine stock, SMS, activity history, at settings. Subukan ang ibang salita o pumili ng quick question.',
        'links' => [],
        'notice' => '',
    ]);
}

$answer = $matchedGuide['answer'];
$notice = 'Sagot mula sa admin guide.';
$guideLinks = $matchedGuide['links'];
if ($role === 'admin' && !empty($matchedGuide['super_admin_only'])) {
    $guideLinks = [];
}
$apiKey = adminHelpEnvironmentValue('GEMINI_API_KEY');
$model = adminHelpEnvironmentValue('GEMINI_MODEL');
if ($apiKey !== '' && $model === '') {
    error_log('Admin guide Gemini model is not configured.');
    $notice = 'AI unavailable; ipinapakita ang approved admin guide.';
} elseif ($apiKey !== '' && !function_exists('curl_init')) {
    error_log('Admin guide requires the PHP cURL extension to call Gemini.');
    $notice = 'Hindi available ang AI connection; ipinapakita ang approved admin guide.';
} elseif ($apiKey !== '') {
    $prompt = "Ipaliwanag nang malinaw at maikli sa Filipino ang aprubadong admin guide sa ibaba. Sagutin lamang gamit ang gabay; huwag mag-imbento ng system steps at huwag magbigay ng personal o patient-specific na impormasyon. Hanggang 120 salita.\n\nAprubadong admin guide:\n" . $matchedGuide['context'];
    $requestBody = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 260],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($requestBody === false) {
        error_log('Admin guide could not encode the Gemini request.');
        $notice = 'Hindi maihanda ang AI response; ipinapakita ang approved admin guide.';
    } else {
        $curl = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $requestBody,
        ]);
        $apiResponse = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($apiResponse === false) {
            error_log('Admin guide Gemini request failed: ' . $curlError);
            $notice = 'Hindi maabot ang AI; ipinapakita ang approved admin guide.';
        } elseif ($httpStatus < 200 || $httpStatus >= 300) {
            error_log('Admin guide Gemini returned HTTP ' . $httpStatus);
            $notice = 'Hindi available ang AI ngayon; ipinapakita ang approved admin guide.';
        } else {
            $decodedResponse = json_decode($apiResponse, true);
            $generatedAnswer = $decodedResponse['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (is_string($generatedAnswer) && trim($generatedAnswer) !== '') {
                $answer = trim($generatedAnswer);
                $notice = 'AI-generated mula sa approved admin guide.';
            } else {
                error_log('Admin guide Gemini response did not contain answer text.');
                $notice = 'Hindi nakabuo ng AI response; ipinapakita ang approved admin guide.';
            }
        }
    }
}

adminHelpRespond(200, [
    'success' => true,
    'answer' => $answer,
    'links' => $guideLinks,
    'notice' => $notice,
]);
