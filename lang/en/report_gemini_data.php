<?php


// Plugin name (shown in "Reports" menu).
$string['pluginname']        = 'Gemini Data Reports';
$string['gemini_data:view']  = 'View Gemini Data Reports';

// Page title / heading.
$string['pagetitle']         = 'Gemini Data Reports';
$string['pageheading']       = 'AI-Powered Data Reports';
$string['pagedescription']   = 'Choose a preset query below and click <strong>Generate Report</strong> to fetch structured data from Google Gemini AI.';

// Preset labels.
$string['preset_countries']  = 'Countries and their populations';
$string['preset_states']     = 'Brazilian states and founding dates';
$string['preset_peaks']      = '10 highest peaks in Brazil';

// Buttons.
$string['generate']          = 'Generate Report';
$string['generating']        = 'Generating…';
$string['clearreport']       = 'Clear';

// Status / errors.
$string['errornoapikey']     = 'The Gemini API key is not configured. Please ask a site administrator to set it up under Site administration → Plugins → Reports → Gemini Data Reports.';
$string['errorgeneral']      = 'An error occurred while contacting Gemini. Please try again.';
$string['errorsesskey']      = 'Session key mismatch. Please refresh the page.';
$string['errorinvalidjson']  = 'The AI returned an unexpected response format. Please try again.';
$string['errornoresults']    = 'No results were returned by the AI. Please try again.';

// Admin settings.
$string['settings']              = 'Gemini Data Reports Settings';
$string['apikey']                = 'Gemini API Key';
$string['apikey_desc']           = 'Your Google Gemini API key. Get one free at <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>.';
$string['model']                 = 'Gemini Model';
$string['model_desc']            = 'Model used to generate report data.';
$string['temperature']           = 'Temperature';
$string['temperature_desc']      = 'Controls response creativity. 0.0 = deterministic, 1.0 = creative.';
$string['maxoutputtokens']       = 'Max output tokens';
$string['maxoutputtokens_desc']  = 'Maximum tokens in the AI response (256–8192).';
