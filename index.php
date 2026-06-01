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

$PAGE->requires->js_call_amd('report_gemini_data/report', 'init', [[
    'sesskey'  => sesskey(),
    'ajax_url' => (new moodle_url('/report/gemini_data/ajax.php'))->out(false),
    'strings'  => [
        'generating'       => get_string('generating',       'report_gemini_data'),
        'generate'         => get_string('generate',         'report_gemini_data'),
        'errornoresults'   => get_string('errornoresults',   'report_gemini_data'),
        'errorinvalidjson' => get_string('errorinvalidjson', 'report_gemini_data'),
    ],
]]);

// Presets: id => [label, icon, description, button_class].
$presets = [
    'countries' => [
        'label'       => get_string('preset_countries', 'report_gemini_data'),
        'icon'        => '🌍',
        'description' => get_string('preset_countries_desc', 'report_gemini_data'),
        'btn_class'   => 'btn-primary',
    ],
    'states' => [
        'label'       => get_string('preset_states', 'report_gemini_data'),
        'icon'        => '🗺️',
        'description' => get_string('preset_states_desc', 'report_gemini_data'),
        'btn_class'   => 'btn-success',
    ],
    'peaks' => [
        'label'       => get_string('preset_peaks', 'report_gemini_data'),
        'icon'        => '⛰️',
        'description' => get_string('preset_peaks_desc', 'report_gemini_data'),
        'btn_class'   => 'btn-warning',
    ],
];

echo $OUTPUT->header();

// ── Page description ──────────────────────────────────────────────────────────
echo html_writer::div(
    get_string('pagedescription', 'report_gemini_data'),
    'alert alert-info mb-4'
);

// ── Preset cards with individual buttons ──────────────────────────────────────
echo html_writer::start_div('row g-3 mb-4', ['id' => 'gemini-preset-cards']);

foreach ($presets as $id => $preset) {
    echo html_writer::start_div('col-12 col-md-4');
    echo html_writer::start_div('card h-100 shadow-sm');
    echo html_writer::start_div('card-body d-flex flex-column');

    // Card title.
    echo html_writer::tag('h5',
        $preset['icon'] . ' ' . $preset['label'],
        ['class' => 'card-title']
    );

    // Card description.
    echo html_writer::tag('p',
        $preset['description'],
        ['class' => 'card-text text-muted flex-grow-1']
    );

    // Generate button (individual per preset).
    echo html_writer::tag('button',
        html_writer::span(get_string('generate', 'report_gemini_data'), 'btn-label') .
        html_writer::span('', 'spinner-border spinner-border-sm ms-2 d-none',
            ['role' => 'status', 'aria-hidden' => 'true']),
        [
            'type'         => 'button',
            'class'        => 'btn ' . $preset['btn_class'] . ' mt-auto gemini-preset-btn',
            'data-preset'  => $id,
            'id'           => 'gemini-btn-' . $id,
        ]
    );

    echo html_writer::end_div(); // .card-body
    echo html_writer::end_div(); // .card
    echo html_writer::end_div(); // .col
}

echo html_writer::end_div(); // #gemini-preset-cards

// ── Error container ───────────────────────────────────────────────────────────
echo html_writer::div('', 'alert alert-danger d-none mb-3',
    ['id' => 'gemini-error', 'role' => 'alert']);

// ── Results section ───────────────────────────────────────────────────────────
echo html_writer::start_div('d-none', ['id' => 'gemini-results']);

// Results header bar.
echo html_writer::start_div('d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2');

echo html_writer::tag('h3', '', [
    'id'    => 'gemini-results-title',
    'class' => 'report-gemini-results-title mb-0',
]);

echo html_writer::tag('button',
    get_string('clearreport', 'report_gemini_data'),
    [
        'id'    => 'gemini-clear-btn',
        'type'  => 'button',
        'class' => 'btn btn-outline-secondary btn-sm',
    ]
);

echo html_writer::end_div(); // flex header

// Table.
echo html_writer::start_div('table-responsive');
echo html_writer::tag('table', '', [
    'id'    => 'gemini-table',
    'class' => 'table table-striped table-hover table-bordered report-gemini-table align-middle',
]);
echo html_writer::end_div();

// Meta info.
echo html_writer::div('', 'text-muted small mt-2', ['id' => 'gemini-meta']);

echo html_writer::end_div(); // #gemini-results

echo $OUTPUT->footer();
