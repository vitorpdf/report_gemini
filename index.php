<?php

require_once(__DIR__ . '/../../config.php');

$context = context_system::instance();

require_login();
require_capability('report/gemini_data:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/report/gemini_data/index.php'));
$PAGE->set_title(get_string('pagetitle', 'report_gemini_data'));
$PAGE->set_heading(get_string('pageheading', 'report_gemini_data'));
$PAGE->set_pagelayout('report');

// Enqueue AMD module.
$PAGE->requires->js_call_amd('report_gemini_data/report', 'init', [[
    'sesskey'  => sesskey(),
    'ajax_url' => (new moodle_url('/report/gemini_data/ajax.php'))->out(false),
    'strings'  => [
        'generating'      => get_string('generating',      'report_gemini_data'),
        'generate'        => get_string('generate',        'report_gemini_data'),
        'errornoresults'  => get_string('errornoresults',  'report_gemini_data'),
        'errorinvalidjson'=> get_string('errorinvalidjson','report_gemini_data'),
    ],
]]);

// Preset definitions – label (string key) => preset id used by JS / ajax.
$presets = [
    'countries' => get_string('preset_countries', 'report_gemini_data'),
    'states'    => get_string('preset_states',    'report_gemini_data'),
    'peaks'     => get_string('preset_peaks',     'report_gemini_data'),
];

echo $OUTPUT->header();

// ── Description ──────────────────────────────────────────────────────────────
echo html_writer::div(
    get_string('pagedescription', 'report_gemini_data'),
    'report-gemini-description alert alert-info'
);

// ── Preset selector + button ─────────────────────────────────────────────────
echo html_writer::start_div('report-gemini-controls card p-3 mb-4');

echo html_writer::start_div('form-group mb-3');
echo html_writer::label(
    get_string('generate', 'report_gemini_data'),
    'gemini-preset-select',
    true,
    ['class' => 'form-label fw-semibold d-block mb-2']
);

$select_options = '';
foreach ($presets as $id => $label) {
    $select_options .= html_writer::tag('option', $label, ['value' => $id]);
}
echo html_writer::tag(
    'select',
    $select_options,
    [
        'id'    => 'gemini-preset-select',
        'class' => 'form-select form-control',
        'aria-label' => get_string('generate', 'report_gemini_data'),
    ]
);
echo html_writer::end_div(); // .form-group

// Generate button.
echo html_writer::tag(
    'button',
    html_writer::span(get_string('generate', 'report_gemini_data'), 'btn-label') .
    html_writer::span('', 'spinner-border spinner-border-sm ms-2 d-none', ['role' => 'status', 'aria-hidden' => 'true']),
    [
        'id'    => 'gemini-generate-btn',
        'type'  => 'button',
        'class' => 'btn btn-primary me-2',
    ]
);

// Clear button.
echo html_writer::tag(
    'button',
    get_string('clearreport', 'report_gemini_data'),
    [
        'id'    => 'gemini-clear-btn',
        'type'  => 'button',
        'class' => 'btn btn-outline-secondary d-none',
    ]
);

echo html_writer::end_div(); // .report-gemini-controls

// ── Error container ───────────────────────────────────────────────────────────
echo html_writer::div(
    '',
    'report-gemini-error alert alert-danger d-none',
    ['id' => 'gemini-error', 'role' => 'alert']
);

// ── Results container ─────────────────────────────────────────────────────────
echo html_writer::start_div('report-gemini-results d-none', ['id' => 'gemini-results']);

// Title placeholder.
echo html_writer::tag('h3', '', ['id' => 'gemini-results-title', 'class' => 'report-gemini-results-title mb-3']);

// Table wrapper.
echo html_writer::start_div('table-responsive');
echo html_writer::tag('table', '', [
    'id'    => 'gemini-table',
    'class' => 'table table-striped table-hover table-bordered report-gemini-table',
]);
echo html_writer::end_div();

// Meta info (row count, timestamp).
echo html_writer::div('', 'report-gemini-meta text-muted small mt-2', ['id' => 'gemini-meta']);

echo html_writer::end_div(); // #gemini-results

echo $OUTPUT->footer();
