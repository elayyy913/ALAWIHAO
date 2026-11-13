<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function chatbotRespond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function chatbotEnvironmentValue(string $name): string
{
    $value = getenv($name);
    if ($value === false) {
        $value = $_SERVER[$name] ?? '';
    }
    return trim((string) $value);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    chatbotRespond(405, ['success' => false, 'message' => 'Hindi suportado ang request na ito.']);
}

if (empty($_SESSION['user_id'])) {
    chatbotRespond(401, ['success' => false, 'message' => 'Mag-log in muna para magamit ang assistant.']);
}

if (($_SESSION['role'] ?? '') !== 'User') {
    chatbotRespond(403, ['success' => false, 'message' => 'Para ito sa user portal.']);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$sessionToken = $_SESSION['user_chatbot_csrf'] ?? '';
if ($sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    chatbotRespond(403, ['success' => false, 'message' => 'Nag-expire ang session. I-refresh ang page at subukan ulit.']);
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 4096) {
    chatbotRespond(413, ['success' => false, 'message' => 'Masyadong mahaba ang tanong.']);
}

$input = json_decode(file_get_contents('php://input'), true);
$question = is_array($input) ? trim((string) ($input['question'] ?? '')) : '';
if ($question === '' || mb_strlen($question, 'UTF-8') > 500) {
    chatbotRespond(422, ['success' => false, 'message' => 'Maglagay ng tanong na hanggang 500 characters.']);
}

$now = time();
$recentRequests = array_values(array_filter(
    $_SESSION['user_chatbot_requests'] ?? [],
    static fn($timestamp) => is_int($timestamp) && $timestamp > $now - 60
));
if (count($recentRequests) >= 12) {
    chatbotRespond(429, ['success' => false, 'message' => 'Sandali muna bago magpadala ulit ng tanong.']);
}
$recentRequests[] = $now;
$_SESSION['user_chatbot_requests'] = $recentRequests;

require_once __DIR__ . '/registration_form_guide.php';
$matchedFormGuide = registrationFormGuideMatch($question, 'user');

function chatbotFlattenText($value): string
{
    if (is_string($value) || is_numeric($value)) {
        return (string) $value;
    }

    if (!is_array($value)) {
        return '';
    }

    $parts = [];
    foreach ($value as $item) {
        $text = chatbotFlattenText($item);
        if ($text !== '') {
            $parts[] = $text;
        }
    }
    return implode(' ', $parts);
}

function chatbotTokens(string $text): array
{
    preg_match_all('/[\p{L}\p{N}]{2,}/u', mb_strtolower($text, 'UTF-8'), $matches);
    return array_values(array_unique($matches[0] ?? []));
}

function chatbotArticleChunks(array $article): array
{
    $chunks = [];
    if (!empty($article['intro'])) {
        $chunks[] = (string) $article['intro'];
    }

    foreach ($article['sections'] ?? [] as $section) {
        $heading = (string) ($section['heading'] ?? '');
        if (!empty($section['items'])) {
            foreach ($section['items'] as $item) {
                $text = chatbotFlattenText($item);
                if ($text !== '') {
                    $chunks[] = trim($heading . ': ' . $text);
                }
            }
        }

        foreach (['content', 'myth', 'truth', 'rows', 'steps'] as $field) {
            if (!empty($section[$field])) {
                $text = chatbotFlattenText($section[$field]);
                if ($text !== '') {
                    $chunks[] = trim($heading . ': ' . $text);
                }
            }
        }
    }

    return $chunks;
}

$queryTokens = array_values(array_diff(
    chatbotTokens($question),
    ['ang', 'mga', 'ng', 'sa', 'ano', 'paano', 'ba', 'po', 'yung', 'about', 'the', 'is', 'are', 'what', 'how', 'of', 'for', 'and', 'or', 'ko', 'mo', 'ito', 'iyan', 'siya', 'na', 'at', 'kung', 'may', 'ba']
));
$synonyms = [
    'symptom' => ['symptoms', 'sintomas', 'palatandaan', 'babala'],
    'symptoms' => ['symptom', 'sintomas', 'palatandaan', 'babala'],
    'sintomas' => ['symptom', 'symptoms', 'palatandaan', 'babala'],
    'buntis' => ['pagbubuntis', 'pregnancy', 'buntis'],
    'pregnancy' => ['buntis', 'pagbubuntis', 'pregnancy'],
    'baby' => ['sanggol', 'newborn', 'sanggol'],
    'sanggol' => ['baby', 'newborn', 'sanggol'],
    'vaccine' => ['bakuna', 'vaccination', 'bakuna'],
    'vaccination' => ['bakuna', 'vaccine', 'bakuna'],
    'bakuna' => ['vaccine', 'vaccination', 'bakuna'],
];
$searchTokens = $queryTokens;
foreach ($queryTokens as $token) {
    foreach ($synonyms[$token] ?? [] as $synonym) {
        $searchTokens[] = $synonym;
    }
}
$searchTokens = array_values(array_unique($searchTokens));

$navigationHelp = [
    [
        'keywords' => ['registration', 'register', 'magparehistro', 'magrehistro', 'magregister', 'enrollment', 'enroll'],
        'answer' => 'Para mag-register, buksan ang Registration sa sidebar at piliin ang Maternal Form o Child Form. Punan at suriin ang mga kailangang detalye bago isumite. Kung nakapag-register ka na dati, tingnan ang saved personal information sa itaas ng form; maaari mo itong i-edit bago magpatuloy sa bagong case.',
        'links' => [
            ['label' => 'Maternal Form', 'url' => 'user_maternal_reg.php'],
            ['label' => 'Child Form', 'url' => 'user_infant_reg.php'],
        ],
        'requires_any' => ['registration', 'register', 'magparehistro', 'magrehistro', 'magregister', 'enrollment', 'enroll'],
        'min_score' => 1,
    ],
    [
        'keywords' => ['maternal', 'buntis', 'pregnancy', 'pregnant', 'nanay', 'mother', 'form', 'register', 'registration', 'magregister', 'magparehistro', 'magrehistro', 'enroll', 'enrollment'],
        'answer' => 'Para sa maternal registration, piliin ang Registration > Maternal Form sa sidebar. Ang saved personal information ay maaaring i-review at i-edit sa itaas; punan ang mga detalye para sa kasalukuyang pregnancy at isumite ang form.',
        'links' => [['label' => 'Buksan ang Maternal Form', 'url' => 'user_maternal_reg.php']],
        'requires_any' => ['maternal', 'buntis', 'pregnancy', 'pregnant', 'nanay', 'mother'],
        'min_score' => 2,
    ],
    [
        'keywords' => ['child', 'infant', 'baby', 'sanggol', 'bata', 'form', 'register', 'registration', 'magregister', 'magparehistro', 'magrehistro', 'enroll', 'enrollment'],
        'answer' => 'Para sa child o infant registration, piliin ang Registration > Child Form sa sidebar. Tingnan at i-edit ang saved personal o family information sa itaas ng form, pagkatapos punan ang mga detalye para sa bata at isumite.',
        'links' => [['label' => 'Buksan ang Child Form', 'url' => 'user_infant_reg.php']],
        'requires_any' => ['child', 'infant', 'baby', 'sanggol', 'bata'],
        'min_score' => 2,
    ],
    [
        'keywords' => ['another', 'again', 'case', 'pregnancy', 'child', 'personal', 'information', 'details', 'saved', 'save', 'fetch', 'reuse', 'edit', 'registration', 'register', 'form'],
        'answer' => 'Kapag magre-register ng panibagong pregnancy o bata, buksan muli ang kaukulang Maternal Form o Child Form. Ipinapakita sa itaas ang na-save na personal/family information; gamitin ang Edit kung may kailangang baguhin. Punan ang natitirang detalye para sa bagong case bago isumite.',
        'links' => [
            ['label' => 'Maternal Form', 'url' => 'user_maternal_reg.php'],
            ['label' => 'Child Form', 'url' => 'user_infant_reg.php'],
        ],
        'requires_any' => ['another', 'again', 'case', 'saved', 'fetch', 'reuse', 'personal', 'details'],
        'min_score' => 2,
    ],
    [
        'keywords' => ['schedule', 'appointment', 'iskedyul', 'reschedule', 'resched', 'cancel', 'cancelled', 'kansela', 'request'],
        'answer' => 'Piliin ang Schedule sa sidebar para makita ang Upcoming at History. Para magpalit ng oras o petsa, piliin ang Request Resched. Habang pending pa ang request, maaari mong piliin ang Cancel Request sa appointment card. Kapag na-review o naproseso na ito, hindi na ito makakansela rito; makipag-ugnayan sa health center.',
        'links' => [['label' => 'Buksan ang Schedule', 'url' => 'user_schedule.php']],
        'requires_any' => ['schedule', 'appointment', 'iskedyul', 'reschedule', 'resched', 'cancel', 'kansela', 'request'],
        'min_score' => 1,
    ],
    [
        'keywords' => ['record', 'records', 'history', 'maternal', 'child', 'health'],
        'answer' => 'Piliin ang My Records sa sidebar para makita ang maternal o child health records na naka-link sa account mo. Piliin ang tamang record type; hindi nakakakita o nakakabago ng record ang guide.',
        'links' => [
            ['label' => 'Maternal Health', 'url' => 'user_maternal_records.php'],
            ['label' => 'Child Health', 'url' => 'user_child_records.php'],
        ],
        'requires_any' => ['record', 'records', 'history'],
        'min_score' => 1,
    ],
    [
        'keywords' => ['account', 'settings', 'profile', 'password', 'email', 'change', 'update'],
        'answer' => 'Piliin ang Settings sa sidebar para pamahalaan ang mga setting ng iyong account at profile.',
        'links' => [['label' => 'Buksan ang Settings', 'url' => 'user_settings.php']],
        'requires_any' => ['account', 'settings', 'profile', 'password', 'email'],
        'min_score' => 1,
    ],
    [
        'keywords' => ['article', 'articles', 'topic', 'health', 'hub', 'home', 'read', 'basahin'],
        'answer' => 'Pumunta sa Home page para makita at hanapin ang health articles ayon sa topic.',
        'links' => [['label' => 'Buksan ang Health Hub', 'url' => 'user_dashboard.php']],
        'requires_any' => ['article', 'articles', 'topic', 'hub', 'home'],
        'min_score' => 1,
    ],
];
$matchedNavigationHelp = null;
foreach ($navigationHelp as $help) {
    if (!array_intersect($help['requires_any'], $searchTokens)) {
        continue;
    }

    $score = count(array_intersect($searchTokens, $help['keywords']));
    if ($score >= $help['min_score']
        && ($matchedNavigationHelp === null || $score > $matchedNavigationHelp['score'])) {
        $matchedNavigationHelp = ['score' => $score, 'guide' => $help];
    }
}
$matchedNavigationHelp = $matchedNavigationHelp['guide'] ?? null;
$registrationTerms = ['registration', 'register', 'magparehistro', 'magrehistro', 'magregister', 'enrollment', 'enroll'];
$maternalTerms = ['maternal', 'buntis', 'pregnancy', 'pregnant', 'nanay', 'mother'];
$childTerms = ['child', 'infant', 'baby', 'sanggol', 'bata'];
if (array_intersect($registrationTerms, $searchTokens)
    && array_intersect($maternalTerms, $searchTokens)
    && array_intersect($childTerms, $searchTokens)) {
    $matchedNavigationHelp = $navigationHelp[0];
}

$articles = require __DIR__ . '/health_articles_data.php';
$articleMatches = [];
foreach ($articles as $slug => $article) {
    $titleTokens = chatbotTokens((string) ($article['title'] ?? ''));
    $chunks = chatbotArticleChunks($article);
    $scoredChunks = [];
    $matchedTerms = [];

    foreach ($chunks as $chunk) {
        $chunkTokens = chatbotTokens($chunk);
        $chunkMatches = array_values(array_intersect($searchTokens, $chunkTokens));
        if ($chunkMatches) {
            foreach ($chunkMatches as $term) {
                $matchedTerms[$term] = true;
            }
            $scoredChunks[] = ['score' => count($chunkMatches), 'text' => $chunk];
        }
    }

    if (!$scoredChunks) {
        continue;
    }

    usort($scoredChunks, static fn($left, $right) => $right['score'] <=> $left['score']);
    $titleMatches = count(array_intersect($searchTokens, $titleTokens));
    $articleMatches[] = [
        'slug' => $slug,
        'article' => $article,
        'score' => count($matchedTerms) + ($titleMatches * 2),
        'chunks' => array_slice($scoredChunks, 0, 3),
    ];
}

usort($articleMatches, static fn($left, $right) => $right['score'] <=> $left['score']);
$matchedArticle = $articleMatches[0] ?? null;
$articleThreshold = count($queryTokens) > 1 ? 2 : 1;
$aiUsed = false;
$urgentTerms = [
    'hirap huminga',
    'hindi makahinga',
    'nahihirapang huminga',
    'huminga',
    'matinding pagdurugo',
    'pagdurugo',
    'seizure',
    'kombulsyon',
    'walang malay',
    'matinding sakit ng ulo',
    'panlalabo ng paningin',
    'biglang pamamaga',
    'emergency',
];
$normalizedQuestion = mb_strtolower($question, 'UTF-8');
$isUrgent = false;
foreach ($urgentTerms as $urgentTerm) {
    if (mb_strpos($normalizedQuestion, $urgentTerm) !== false) {
        $isUrgent = true;
        break;
    }
}
$allowGemini = true;

if ($isUrgent) {
    $allowGemini = false;
    $answer = 'Kung nararanasan mo ito ngayon, huwag maghintay ng sagot sa chatbot. Humingi agad ng tulong sa health worker o pumunta sa pinakamalapit na ospital.';
    $links = [['label' => 'Basahin ang mga babala habang buntis', 'url' => 'health_article.php?topic=warning-signs']];
    $context = '';
    $notice = 'Pangkalahatang paalala ito para sa agarang kaligtasan; hindi ito diagnosis.';
    if (isset($articles['warning-signs'])) {
        $urgentArticle = $articles['warning-signs'];
        $sourceTitle = (string) ($urgentArticle['title'] ?? 'Health article');
        $sourceName = (string) ($urgentArticle['source'] ?? 'Alawihao Health Center health article');
        $notice .= ' Kaugnay na artikulo: ' . $sourceTitle . ' (' . $sourceName . ').';
    }
} elseif ($matchedFormGuide !== null) {
    $allowGemini = false;
    $answer = $matchedFormGuide['answer'];
    $links = $matchedFormGuide['links'];
    $context = '';
    $notice = 'Sagot mula sa gabay sa registration form.';
} elseif ($matchedNavigationHelp !== null) {
    $allowGemini = false;
    $answer = $matchedNavigationHelp['answer'];
    $links = $matchedNavigationHelp['links'];
    $context = '';
    $notice = 'Gabay sa paggamit ng user portal.';
} elseif ($matchedArticle !== null && $matchedArticle['score'] >= $articleThreshold) {
    $article = $matchedArticle['article'];
    $sourceTitle = (string) ($article['title'] ?? 'Health article');
    $sourceName = (string) ($article['source'] ?? 'Alawihao Health Center health article');
    $sourceChunks = array_map(static function ($chunk) {
        $text = trim($chunk['text']);
        return mb_strlen($text, 'UTF-8') > 420 ? mb_substr($text, 0, 417, 'UTF-8') . '...' : $text;
    }, $matchedArticle['chunks']);
    $answer = 'Batay sa artikulong "' . $sourceTitle . '":' . "\n\n• " . implode("\n\n• ", $sourceChunks)
        . "\n\nKung tungkol ito sa nararamdaman mo ngayon, kumonsulta sa health worker para sa personal na payo.";
    $links = [[
        'label' => 'Basahin ang artikulo: ' . $sourceTitle,
        'url' => 'health_article.php?topic=' . rawurlencode($matchedArticle['slug']),
    ]];
    $context = "Approved article: {$sourceTitle}\nSource listed in article: {$sourceName}\n\n" . implode("\n\n", $sourceChunks)
        . "\n\nHuwag magbigay ng diagnosis, paggamot, dosis, o personalized medical advice. Sabihin sa user na kumonsulta sa health worker para sa personal na payo.";
    $notice = 'Sagot mula sa health article na ito: ' . $sourceName . '. Hindi ito diagnosis o kapalit ng health worker.';
} else {
    chatbotRespond(200, [
        'success' => true,
        'answer' => 'Hindi ko makita ang sagot sa kasalukuyang gabay. Subukang magtanong tungkol sa registration, schedule, records, account settings, o health articles.',
        'links' => [['label' => 'Buksan ang Home', 'url' => 'user_dashboard.php']],
        'ai_used' => false,
        'notice' => 'Hindi ipinapadala ang tanong mo sa AI dahil walang tumugmang aprubadong source.',
    ]);
}

$fallbackNotice = 'Hindi pa naka-configure ang Gemini; ipinapakita ang impormasyon mula sa approved source.';
$apiKey = chatbotEnvironmentValue('GEMINI_API_KEY');
$model = chatbotEnvironmentValue('GEMINI_MODEL');
if (!$allowGemini) {
    $fallbackNotice = '';
} elseif ($apiKey !== '' && $model === '') {
    error_log('Health chatbot Gemini model is not configured.');
    $fallbackNotice = 'Hindi pa naka-configure ang Gemini model; ipinapakita ang impormasyon mula sa approved source.';
} elseif ($apiKey !== '' && !function_exists('curl_init')) {
    error_log('Health chatbot requires the PHP cURL extension to call Gemini.');
    $fallbackNotice = 'Hindi available ang server connection sa Gemini; ipinapakita ang impormasyon mula sa approved source.';
} elseif ($apiKey !== '') {
    $prompt = "Ipaliwanag nang maikli at malinaw sa Filipino ang aprubadong source sa ibaba. Sagutin lamang gamit ang impormasyong nasa source; huwag magdagdag ng diagnosis, paggamot, dosis, o personalized medical advice. Huwag magbanggit ng impormasyong wala sa source. Hanggang 100 salita.\n\nAprubadong source:\n" . $context;
    $requestBody = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 220],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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
        error_log('Health chatbot Gemini request failed: ' . $curlError);
        $fallbackNotice = 'Hindi maabot ang Gemini; ipinapakita ang impormasyon mula sa approved source.';
    } elseif ($httpStatus < 200 || $httpStatus >= 300) {
        error_log('Health chatbot Gemini returned HTTP ' . $httpStatus);
        $fallbackNotice = 'Hindi available ang Gemini ngayon; ipinapakita ang impormasyon mula sa approved source.';
    } else {
        $decodedResponse = json_decode($apiResponse, true);
        $generatedAnswer = $decodedResponse['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (is_string($generatedAnswer) && trim($generatedAnswer) !== '') {
            $answer = trim($generatedAnswer);
            $aiUsed = true;
        } else {
            error_log('Health chatbot Gemini response did not contain answer text.');
            $fallbackNotice = 'Hindi nakabuo ng sagot ang Gemini; ipinapakita ang impormasyon mula sa approved source.';
        }
    }
}

chatbotRespond(200, [
    'success' => true,
    'answer' => $answer,
    'links' => $links,
    'ai_used' => $aiUsed,
    'notice' => $aiUsed ? $notice : trim($fallbackNotice . ' ' . $notice),
]);
