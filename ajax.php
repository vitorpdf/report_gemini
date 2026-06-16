<?php


define('AJAX_SCRIPT', true);
set_time_limit(120);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method Not Allowed']));
}

require_login();

$sesskey = required_param('sesskey', PARAM_RAW);
$preset  = required_param('preset',  PARAM_ALPHANUMEXT);

if (!confirm_sesskey($sesskey)) {
    http_response_code(403);
    die(json_encode(['error' => get_string('errorsesskey', 'report_gemini_data')]));
}

require_capability('report/gemini_data:view', context_system::instance());

// ── Plugin settings ───────────────────────────────────────────────────────────
$config          = get_config('report_gemini_data');
$apikey          = $config->apikey          ?? '';
$model           = 'gemini-2.5-flash';
$maxoutputtokens = (int)($config->maxoutputtokens ?? 4096);
$temperature     = (float)($config->temperature   ?? 0.1);

if (empty($apikey)) {
    echo json_encode(['error' => get_string('errornoapikey', 'report_gemini_data')]);
    exit;
}

// ── prompt pre definidos ────────────────────────────────────────────────────────
$presets = [

    'countries' => [
        'title'        => get_string('preset_countries', 'report_gemini_data'),
        'columns'      => [
            ['country',    'País'],
            ['continent',  'Continente'],
            ['population', 'População Estimada'],
        ],
        'numeric_cols' => ['population'],
        'prompt'       =>
            "Liste todos os países da América do Sul com população estimada.\n"
          . "Responda SOMENTE com um JSON array. Sem markdown, sem texto extra.\n"
          . "Cada objeto deve ter exatamente estas 3 chaves:\n"
          . '  "country"    (string) — nome do país em português' . "\n"
          . '  "continent"  (string) — deve ser exatamente "América do Sul"' . "\n"
          . '  "population" (number) — estimativa de população como inteiro' . "\n\n"
          . "Exemplo:\n"
          . '[{"country":"Brasil","continent":"América do Sul","population":215313498},'
          . '{"country":"Argentina","continent":"América do Sul","population":46234830}]',
    ],

    'states' => [
        'title'        => get_string('preset_states', 'report_gemini_data'),
        'columns'      => [
            ['state',        'Estado'],
            ['abbreviation', 'Sigla'],
            ['capital',      'Capital'],
            ['region',       'Região'],
            ['founded',      'Data de Fundação / Criação'],
        ],
        'numeric_cols' => [],
        'prompt'       =>
            "Liste os 26 estados brasileiros e o Distrito Federal (27 unidades no total).\n"
          . "Responda SOMENTE com um JSON array. Sem markdown, sem texto extra.\n"
          . "Cada elemento deve ter exatamente estas 5 chaves:\n"
          . '  "state"        (string) — nome completo do estado' . "\n"
          . '  "abbreviation" (string) — sigla de 2 letras (ex: SP, RJ, AM)' . "\n"
          . '  "capital"      (string) — nome da capital' . "\n"
          . '  "region"       (string) — Norte, Nordeste, Centro-Oeste, Sudeste ou Sul' . "\n"
          . '  "founded"      (string) — data de criação oficial no formato DD/MM/AAAA' . "\n\n"
          . "O campo \"founded\" é a data em que a unidade federativa foi criada oficialmente,\n"
          . "não a data de fundação da cidade capital.\n\n"
          . "Exemplo (inclua todos os 27):\n"
          . '[{"state":"Acre","abbreviation":"AC","capital":"Rio Branco","region":"Norte","founded":"15/06/1962"},'
          . '{"state":"Alagoas","abbreviation":"AL","capital":"Maceió","region":"Nordeste","founded":"16/09/1817"},'
          . '{"state":"Amapá","abbreviation":"AP","capital":"Macapá","region":"Norte","founded":"05/10/1988"},'
          . '{"state":"Amazonas","abbreviation":"AM","capital":"Manaus","region":"Norte","founded":"05/09/1850"},'
          . '{"state":"Distrito Federal","abbreviation":"DF","capital":"Brasília","region":"Centro-Oeste","founded":"21/04/1960"}]',
    ],

    'peaks' => [
        'title'        => get_string('preset_peaks', 'report_gemini_data'),
        'columns'      => [
            ['rank',         '#'],
            ['name',         'Pico'],
            ['altitude_m',   'Altitude (m)'],
            ['state',        'Estado'],
            ['park',         'Parque / UC'],
            ['best_seasons', 'Melhores Épocas para Subir'],
        ],
        'numeric_cols' => ['rank', 'altitude_m'],
        'prompt'       =>
            "Liste os 10 picos mais altos do Brasil em ordem decrescente de altitude.\n"
          . "Responda SOMENTE com um JSON array. Sem markdown, sem texto extra.\n"
          . "Cada elemento deve ter exatamente estas 6 chaves:\n"
          . '  "rank"         (number) — posição (1 = mais alto)' . "\n"
          . '  "name"         (string) — nome do pico' . "\n"
          . '  "altitude_m"   (number) — altitude em metros como inteiro' . "\n"
          . '  "state"        (string) — estado(s) onde está localizado' . "\n"
          . '  "park"         (string) — parque/UC ou string vazia se não houver' . "\n"
          . '  "best_seasons" (string) — melhores épocas com justificativa climática' . "\n\n"
          . "Exemplo:\n"
          . '[{"rank":1,"name":"Pico da Neblina","altitude_m":2995,"state":"Amazonas",'
          . '"park":"Parque Nacional do Pico da Neblina",'
          . '"best_seasons":"Junho a novembro (estação seca): menor índice pluviométrico, trilha mais segura."},'
          . '{"rank":2,"name":"Pico 31 de Março","altitude_m":2972,"state":"Amazonas",'
          . '"park":"Parque Nacional do Pico da Neblina",'
          . '"best_seasons":"Junho a novembro, mesmas condições do Pico da Neblina."}]',
    ],
];

if (!array_key_exists($preset, $presets)) {
    http_response_code(400);
    die(json_encode(['error' => 'Preset inválido: ' . s($preset)]));
}

$p = $presets[$preset];

// ── Cache: return stored result if fresh ──────────────────────────────────────
$cache     = cache::make('report_gemini_data', 'gemini_responses');
$cache_key = 'preset_' . $preset;
$cached    = $cache->get($cache_key);

if ($cached) {
    echo json_encode(array_merge($cached, [
        'from_cache' => true,
        'title'      => $p['title'],   // always use current lang string
    ]), JSON_UNESCAPED_UNICODE);
    exit;
}

// ──  Chama Gemini REST API ───────────────────────────────────────
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apikey}";

$body = json_encode([
    'contents' => [[
        'role'  => 'user',
        'parts' => [['text' => $p['prompt']]],
    ]],
    'generationConfig' => [
        'maxOutputTokens' => $maxoutputtokens,
        'temperature'     => $temperature,
        'responseMimeType'=> 'application/json',
    ],
], JSON_UNESCAPED_UNICODE);

$raw_response = null;
$http_code    = 0;
$wait_secs    = 5;

for ($attempt = 0; $attempt <= 3; $attempt++) {
    $curl = new curl();
    $curl->setHeader(['Content-Type: application/json']);
    $raw_response = $curl->post($endpoint, $body, [
        'CURLOPT_TIMEOUT'        => 90,
        'CURLOPT_CONNECTTIMEOUT' => 20,
        'CURLOPT_RETURNTRANSFER' => true,
    ]);
    $http_code = $curl->get_info()['http_code'] ?? 0;

    if ($curl->get_errno() || $http_code === 0) {
        echo json_encode(['error' => get_string('errorgeneral', 'report_gemini_data') . ' (cURL)']);
        exit;
    }

    $resp = json_decode($raw_response, true);

    // Rate-limit: wait and retry
    if ($http_code === 429 || ($resp['error']['code'] ?? 0) === 429) {
        if ($attempt === 3) {
            $msg = $resp['error']['message'] ?? 'Cota da API excedida.';
            if (preg_match('/retry in ([\d.]+)s/i', $msg, $m)) {
                $msg .= ' Tente novamente em ' . (int)ceil((float)$m[1]) . ' segundos.';
            }
            echo json_encode(['error' => $msg]);
            exit;
        }
        sleep($wait_secs);
        $wait_secs = min($wait_secs * 2, 60);
        continue;
    }

    break; 
}

$response = json_decode($raw_response, true);

if (isset($response['error'])) {
    echo json_encode(['error' => $response['error']['message'] ?? get_string('errorgeneral', 'report_gemini_data')]);
    exit;
}

if ($http_code !== 200) {
    echo json_encode(['error' => get_string('errorgeneral', 'report_gemini_data') . " (HTTP {$http_code})"]);
    exit;
}

// ── Extract text ──────────────────────────────────────────────────────────────
$raw_text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;

if ($raw_text === null) {
    $reason = $response['candidates'][0]['finishReason'] ?? 'UNKNOWN';
    echo json_encode(['error' => get_string('errornoresults', 'report_gemini_data') . " (finishReason: {$reason})"]);
    exit;
}

// ── Parse JSON ────────────────────────────────────────────────────────────────
$clean = trim(preg_replace(['/^```(?:json)?\s*/i', '/\s*```\s*$/i'], '', trim($raw_text)));
$data  = json_decode($clean, true);

// Fallback: extract first [...] block via regex
if (!is_array($data) && preg_match('/\[[\s\S]*\]/u', $clean, $m)) {
    $data = json_decode($m[0], true);
}

$items = null;
if (is_array($data)) {
    if (isset($data['items']) && is_array($data['items'])) {
        $items = $data['items'];
    } elseif (array_is_list($data)) {
        $items = $data;
    }
}

if (empty($items)) {
    echo json_encode([
        'error' => get_string('errorinvalidjson', 'report_gemini_data'),
        'debug' => mb_substr($raw_text, 0, 800),
    ]);
    exit;
}

// ── Salva o cache ─────────────────────────────────────────────────────────────
$payload = [
    'columns'      => $p['columns'],
    'numeric_cols' => $p['numeric_cols'],
    'items'        => $items,
    'count'        => count($items),
];
$cache->set($cache_key, $payload);

// ── Return ────────────────────────────────────────────────────────────────────
echo json_encode(array_merge($payload, [
    'title'      => $p['title'],
    'from_cache' => false,
]), JSON_UNESCAPED_UNICODE);
