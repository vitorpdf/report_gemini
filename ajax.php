<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

header('Content-Type: application/json; charset=utf-8');

// ── Accept POST only ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method Not Allowed']));
}

// ── Security ──────────────────────────────────────────────────────────────────
require_login();

$sesskey = required_param('sesskey', PARAM_RAW);
$preset  = required_param('preset',  PARAM_ALPHANUMEXT);

if (!confirm_sesskey($sesskey)) {
    http_response_code(403);
    die(json_encode(['error' => get_string('errorsesskey', 'report_gemini_data')]));
}

require_capability('report/gemini_data:view', context_system::instance());

// ──  strategia de consulta ────────────────────────────────────────────────────────
// Estratégia: use um prompt de texto simples que descreva explicitamente o formato JSON esperado
// e, em seguida, analise a resposta de forma flexível (lida com a raiz do array OU com a raiz {items:[]}root).
// Isso evita problemas de rejeição de esquema com o recurso responseSchema do Gemini.

$presets = [

    // ── Countries and their populations ──────────────────────────────────────
    /*'countries' => [
        'title'   => get_string('preset_countries', 'report_gemini_data'),
        'prompt'  =>
            "Liste todos os países do mundo reconhecidos pela ONU com suas populações estimadas mais recentes.\n"
          . "Responda SOMENTE com um array JSON válido. Sem explicações, sem markdown, sem texto extra.\n"
          . "Formato exato de cada objeto:\n"
          . '{"country":"Nome do país em português","continent":"Continente","population":1234567}' . "\n"
          . "Exemplo:\n"
          . '[{"country":"Brasil","continent":"América do Sul","population":215000000},{"country":"Argentina","continent":"América do Sul","population":46000000}]',
        'columns' => [
            ['country',    'País'],
            ['continent',  'Continente'],
            ['population', 'População Estimada'],
        ],
        'numeric_cols' => ['population'],
    ],*/

    // ── Brazilian states and founding dates ──────────────────────────────────
    /*'states' => [
        'title'   => get_string('preset_states', 'report_gemini_data'),
        'prompt'  =>
            "Liste todos os 26 estados brasileiros mais o Distrito Federal.\n"
          . "Responda SOMENTE com um array JSON válido. Sem explicações, sem markdown, sem texto extra.\n"
          . "Formato exato de cada objeto:\n"
          . '{"state":"Nome do estado","abbreviation":"XX","capital":"Nome da capital","region":"Região","founded":"DD/MM/AAAA"}' . "\n"
          . "Exemplo:\n"
          . '[{"state":"São Paulo","abbreviation":"SP","capital":"São Paulo","region":"Sudeste","founded":"25/01/1554"}]',
        'columns' => [
            ['state',        'Estado'],
            ['abbreviation', 'Sigla'],
            ['capital',      'Capital'],
            ['region',       'Região'],
            ['founded',      'Data de Fundação'],
        ],
        'numeric_cols' => [],
    ],*/

    // ── 10 highest peaks in Brazil ────────────────────────────────────────────
    'peaks' => [
        'title'   => get_string('preset_peaks', 'report_gemini_data'),
        'prompt'  =>
            "Liste os 10 picos mais altos do Brasil em ordem decrescente de altitude.\n"
          . "Responda SOMENTE com um array JSON válido. Sem explicações, sem markdown, sem texto extra.\n"
          . "Formato exato de cada objeto:\n"
          . '{"rank":1,"name":"Nome do pico","altitude_m":2995,"state":"Estado(s)","park":"Parque ou UC (vazio se não houver)","best_seasons":"Descrição das melhores épocas para subir com justificativa climática"}' . "\n"
          . "Exemplo:\n"
          . '[{"rank":1,"name":"Pico da Neblina","altitude_m":2995,"state":"Amazonas","park":"Parque Nacional do Pico da Neblina","best_seasons":"Junho a novembro (estação seca): menor índice pluviométrico e melhor visibilidade."}]',
        'columns' => [
            ['rank',         '#'],
            ['name',         'Pico'],
            ['altitude_m',   'Altitude (m)'],
            ['state',        'Estado'],
            ['park',         'Parque / UC'],
            ['best_seasons', 'Melhores Épocas para Subir'],
        ],
        'numeric_cols' => ['rank', 'altitude_m'],
    ],
];

// ── Validate preset ───────────────────────────────────────────────────────────
if (!array_key_exists($preset, $presets)) {
    http_response_code(400);
    die(json_encode(['error' => 'Preset inválido: ' . s($preset)]));
}

$config_preset = $presets[$preset];

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

// ── Construir requisição Gemini (prompt de texto simples, JSON mime type) ──────────────────
// Solicitamos application/json, mas confiamos na engenharia do prompt para a estrutura,
// NOT on responseSchema, que pode falhar silenciosamente ou remodelar os dados inesperadamente.
$request_body = json_encode([
    'contents' => [
        [
            'role'  => 'user',
            'parts' => [['text' => $config_preset['prompt']]],
        ],
    ],
    'generationConfig' => [
        'maxOutputTokens' => $maxoutputtokens,
        'temperature'     => $temperature,
        'responseMimeType'=> 'application/json',
    ],
]);

// ── Call Gemini REST API ──────────────────────────────────────────────────────
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apikey}";

$curl = new curl();
$curl->setHeader(['Content-Type: application/json']);

$options = [
    'CURLOPT_TIMEOUT'        => 90,
    'CURLOPT_CONNECTTIMEOUT' => 20,
    'CURLOPT_RETURNTRANSFER' => true,
];

$raw_response = $curl->post($endpoint, $request_body, $options);
$http_code    = $curl->get_info()['http_code'] ?? 0;

if ($curl->get_errno() || $http_code === 0) {
    echo json_encode(['error' => get_string('errorgeneral', 'report_gemini_data') . ' (cURL error)']);
    exit;
}

$response = json_decode($raw_response, true);

// ── Handle API-level errors ───────────────────────────────────────────────────
if (isset($response['error'])) {
    $msg = $response['error']['message'] ?? get_string('errorgeneral', 'report_gemini_data');
    echo json_encode(['error' => $msg]);
    exit;
}

if ($http_code !== 200) {
    echo json_encode(['error' => get_string('errorgeneral', 'report_gemini_data') . " (HTTP {$http_code})"]);
    exit;
}

// ── Extract text from Gemini response ────────────────────────────────────────
$raw_text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;

if ($raw_text === null) {
    $finish_reason = $response['candidates'][0]['finishReason'] ?? 'UNKNOWN';
    echo json_encode(['error' => get_string('errornoresults', 'report_gemini_data') . " (finishReason: {$finish_reason})"]);
    exit;
}

// ── Flexibly parse the JSON ───────────────────────────────────────────────────
// Strip markdown fences if present (some models add them even with responseMimeType).
$clean = trim($raw_text);
$clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
$clean = preg_replace('/\s*```\s*$/i',       '', $clean);
$clean = trim($clean);

$data = json_decode($clean, true);

if ($data === null) {
    // Try to extract the first JSON array or object with a regex fallback.
    if (preg_match('/(\[[\s\S]*\]|\{[\s\S]*\})/u', $clean, $m)) {
        $data = json_decode($m[1], true);
    }
}

// Normalise: accept both a root array and a root {items:[...]} object.
$items = null;
if (is_array($data)) {
    if (isset($data['items']) && is_array($data['items'])) {
        // {items:[...]} envelope.
        $items = $data['items'];
    } elseif (array_values($data) === $data) {
        // Root array — every element is a row.
        $items = $data;
    }
}

if (empty($items)) {
    echo json_encode([
        'error'    => get_string('errorinvalidjson', 'report_gemini_data'),
        'debug'    => mb_substr($raw_text, 0, 500), // first 500 chars for debugging
    ]);
    exit;
}

// ── Return success payload ────────────────────────────────────────────────────
echo json_encode([
    'title'        => $config_preset['title'],
    'columns'      => $config_preset['columns'],
    'numeric_cols' => $config_preset['numeric_cols'],
    'items'        => $items,
    'count'        => count($items),
]);
