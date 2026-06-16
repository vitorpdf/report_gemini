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
        'cached'           => get_string('cached',           'report_gemini_data'),
    ],
]]);

// Preset definitions (UI only — data/prompts live in ajax.php).
$presets = [
    'countries' => ['icon' => '🌍', 'label' => get_string('preset_countries', 'report_gemini_data'), 'desc' => get_string('preset_countries_desc', 'report_gemini_data')],
    'states'    => ['icon' => '🗺️',  'label' => get_string('preset_states',    'report_gemini_data'), 'desc' => get_string('preset_states_desc',    'report_gemini_data')],
    'peaks'     => ['icon' => '⛰️',  'label' => get_string('preset_peaks',     'report_gemini_data'), 'desc' => get_string('preset_peaks_desc',     'report_gemini_data')],
];

echo $OUTPUT->header();
?>
<div class="rg-page">

  <!-- ── Description ──────────────────────────────────────────────────────── -->
  <p class="rg-intro"><?php echo get_string('pagedescription', 'report_gemini_data'); ?></p>

  <!-- ── Navigation buttons (Portal da Cultura style) ─────────────────────── -->
  <div class="rg-nav" role="group" aria-label="Consultas disponíveis">
    <?php foreach ($presets as $id => $p): ?>
    <button type="button"
            class="rg-nav-btn gemini-preset-btn"
            data-preset="<?php echo $id; ?>"
            id="gemini-btn-<?php echo $id; ?>">
      <span class="rg-nav-icon"><?php echo $p['icon']; ?></span>
      <span class="rg-nav-label"><?php echo s($p['label']); ?></span>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- ── Active preset description ────────────────────────────────────────── -->
  <div class="rg-desc-bar d-none" id="gemini-desc-bar">
    <?php foreach ($presets as $id => $p): ?>
    <p class="rg-desc-text d-none" id="gemini-desc-<?php echo $id; ?>">
      <?php echo s($p['desc']); ?>
    </p>
    <?php endforeach; ?>
  </div>

  <!-- ── Error ─────────────────────────────────────────────────────────────── -->
  <div class="alert alert-danger d-none mt-3" id="gemini-error" role="alert"></div>

  <!-- ── Results ───────────────────────────────────────────────────────────── -->
  <div class="rg-results d-none" id="gemini-results">

    <div class="rg-results-header">
      <h3 class="rg-results-title" id="gemini-results-title"></h3>
      <div class="rg-results-actions">
        <span class="badge bg-success d-none" id="gemini-cache-badge">
          ⚡ <?php echo get_string('cached', 'report_gemini_data'); ?>
        </span>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="gemini-clear-btn">
          <?php echo get_string('clearreport', 'report_gemini_data'); ?>
        </button>
      </div>
    </div>

    <div class="table-responsive mt-3">
      <table id="gemini-table"
             class="table table-striped table-hover table-bordered report-gemini-table align-middle">
      </table>
    </div>

    <p class="text-muted small mt-2" id="gemini-meta"></p>

  </div><!-- /#gemini-results -->

</div><!-- /.rg-page -->
<?php
echo $OUTPUT->footer();
