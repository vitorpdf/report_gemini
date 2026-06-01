<?php

define('AJAX_SCRIPT', true);

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

// ── prompt pre definidos  ────────────────────────────────────────────────────────

$presets = [

     //   Brasil picos 
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
          . "Responda SOMENTE com um JSON array. Sem markdown, sem texto extra, sem explicação.\n"
          . "Cada elemento do array deve ter exatamente estas chaves:\n"
          . "  \"rank\"         (number) — posição no ranking (1 = mais alto)\n"
          . "  \"name\"         (string) — nome do pico\n"
          . "  \"altitude_m\"   (number) — altitude em metros como número inteiro\n"
          . "  \"state\"        (string) — estado(s) onde está localizado\n"
          . "  \"park\"         (string) — nome do parque ou unidade de conservação (string vazia se não houver)\n"
          . "  \"best_seasons\" (string) — melhores épocas do ano para subir, com justificativa climática em português\n\n"
          . "Exemplo de saída esperada:\n"
          . '[{"rank":1,"name":"Pico da Neblina","altitude_m":2995,"state":"Amazonas",'
          . '"park":"Parque Nacional do Pico da Neblina",'
          . '"best_seasons":"Junho a novembro (estação seca): menor índice pluviométrico, trilha mais segura e melhor visibilidade."}]',
    ],

    // ── continente ──────────────────────────────────────────────────────────
    'countries' => [
        'title'        => get_string('preset_countries', 'report_gemini_data'),
        'columns'      => [
            ['country',    'País'],
            ['continent',  'Continente'],
            ['population', 'População Estimada'],
        ],
        'numeric_cols' => ['population'],
        'prompt'       =>
            "Retorne uma lista de todos os países da america do sul com população estimada.\n"
          . "Responda SOMENTE com um JSON array. Sem markdown, sem texto, sem explicação.\n"
          . "Cada elemento do array deve ter exatamente estas chaves:\n"
          . "  \"country\"    (string) — nome do país em português\n"
          . "  \"continent\"  (string) — continente em português\n"
          . "  \"population\" (number) — população como número inteiro\n\n"
          . "Exemplo de saída esperada:\n"
          . '[{"country":"Brasil","continent":"América do Sul","population":215313498},{"country":"Argentina","continent":"América do Sul","population":46234830}]',
    ],

    // ── estados do br ───────────────────────────────────────────────────
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
            "Liste os 26 estados brasileiros e o Distrito Federal (total de 27 unidades federativas).\n"
          . "Responda SOMENTE com um JSON array. Sem markdown, sem texto extra, sem explicação.\n"
          . "Cada elemento do array deve ter exatamente estas chaves em português:\n"
          . "  \"state\"        (string) — nome completo do estado\n"
          . "  \"abbreviation\" (string) — sigla de 2 letras (ex: SP, RJ, AM)\n"
          . "  \"capital\"      (string) — nome da capital\n"
          . "  \"region\"       (string) — região: Norte, Nordeste, Centro-Oeste, Sudeste ou Sul\n"
          . "  \"founded\"      (string) — data no formato DD/MM/AAAA\n\n"
          . "IMPORTANTE: o campo \"founded\" deve conter a data em que a unidade federativa foi oficialmente\n"
          . "criada ou recebeu seu status atual (ex: data de criação do estado, não a fundação da capital).\n\n"
          . "Exemplo de saída esperada (inclua todos os 27):\n"
          . '[{"state":"Acre","abbreviation":"AC","capital":"Rio Branco","region":"Norte","founded":"15/06/1962"},'
          . '{"state":"Alagoas","abbreviation":"AL","capital":"Maceió","region":"Nordeste","founded":"16/09/1817"},'
          . '{"state":"Distrito Federal","abbreviation":"DF","capital":"Brasília","region":"Centro-Oeste","founded":"21/04/1960"}]',
    ],

   
];

// ─valida o propt ───────────────────────────────────────────────────────────
if (!array_key_exists($preset, $presets)) {
    http_response_code(400);
    die(json_encode(['error' => 'Preset inválido: ' . s($preset)]));
}

$p = $presets[$preset];

// ── Read plugin settings ──────────────────────────────────────────────────────
$config          = get_config('report_gemini_data');
$apikey          = $config->apikey          ?? '';
$model           = 'gemini-2.5-flash';
$maxoutputtokens = (int)($config->maxoutputtokens ?? 4096);
$temperature     = (float)($config->temperature   ?? 0.1);

if (empty($apikey)) {
    echo json_encode(['error' => get_string('errornoapikey', 'report_gemini_data')]);
    exit;
}

// ── controi a requisição no gemini ──────────────────────────────────────────────────────
$request_body = json_encode([
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

// ── Chama Gemini REST API ──────────────────────────────────────────────────────
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apikey}";

$curl = new curl();
$curl->setHeader(['Content-Type: application/json']);
$raw_response = $curl->post($endpoint, $request_body, [
    'CURLOPT_TIMEOUT'        => 120,
    'CURLOPT_CONNECTTIMEOUT' => 20,
    'CURLOPT_RETURNTRANSFER' => true,
]);
$http_code = $curl->get_info()['http_code'] ?? 0;

if ($curl->get_errno() || $http_code === 0) {
    echo json_encode(['error' => get_string('errorgeneral', 'report_gemini_data') . ' (cURL error)']);
    exit;
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

// ── Parse JSON flexibly ───────────────────────────────────────────────────────
// 1. Strip markdown fences.
$clean = trim($raw_text);
$clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
$clean = preg_replace('/\s*```\s*$/i',       '', $clean);
$clean = trim($clean);

// 2. Attempt direct parse.
$data = json_decode($clean, true);

// 3. If failed, try to extract first JSON array via regex.
if (!is_array($data)) {
    if (preg_match('/\[[\s\S]*\]/u', $clean, $m)) {
        $data = json_decode($m[0], true);
    }
}

// 4. Normalise: accept root array OR {items:[...]} envelope.
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

// ── Success ───────────────────────────────────────────────────────────────────
echo json_encode([
    'title'        => $p['title'],
    'columns'      => $p['columns'],
    'numeric_cols' => $p['numeric_cols'],
    'items'        => $items,
    'count'        => count($items),
], JSON_UNESCAPED_UNICODE);
