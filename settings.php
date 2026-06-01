<?php

if ($hassiteconfig) {

    // ── 1. Link de acesso no menu Administração → Relatórios ─────────────────
    // admin_externalpage é o que faz o item aparecer no menu lateral do Moodle.
    $ADMIN->add('reports', new admin_externalpage(
        'report_gemini_data',
        get_string('pluginname', 'report_gemini_data'),
        new moodle_url('/report/gemini_data/index.php'),
        'report/gemini_data:view'
    ));

    // ── 2. Página de configurações do plugin ──────────────────────────────────
    $settings = new admin_settingpage(
        'report_gemini_data_settings',
        get_string('settings', 'report_gemini_data')
    );

    // API Key.
    $settings->add(new admin_setting_configpasswordunmask(
        'report_gemini_data/apikey',
        get_string('apikey', 'report_gemini_data'),
        get_string('apikey_desc', 'report_gemini_data'),
        ''
    ));

    // informa o modelo 
    $settings->add(new admin_setting_heading(
        'block_gemini_chat/model_info',
        get_string('model', 'block_gemini_chat'),
        '<strong>gemini-2.5-flash</strong>',
    ));

    // Temperature.
    $settings->add(new admin_setting_configtext(
        'report_gemini_data/temperature',
        get_string('temperature', 'report_gemini_data'),
        get_string('temperature_desc', 'report_gemini_data'),
        '0.1',
        PARAM_FLOAT
    ));

    // Max output tokens.
    $settings->add(new admin_setting_configtext(
        'report_gemini_data/maxoutputtokens',
        get_string('maxoutputtokens', 'report_gemini_data'),
        get_string('maxoutputtokens_desc', 'report_gemini_data'),
        '8192',
        PARAM_INT
    ));

    $ADMIN->add('reports', $settings);
}
