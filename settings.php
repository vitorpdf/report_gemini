<?php
if ($ADMIN->fulltree) {

    // cabeçalho da configuração da API.
    $settings->add(new admin_setting_heading(
        'block_gemini_chat/settingsheading',
        get_string('settings', 'block_gemini_chat'),
        ''
    ));

    // chave da API .
    $settings->add(new admin_setting_configpasswordunmask(
        'block_gemini_chat/apikey',
        get_string('apikey', 'block_gemini_chat'),
        get_string('apikey_desc', 'block_gemini_chat'),
        ''
    ));
    // informa o modelo 
    $settings->add(new admin_setting_heading(
        'block_gemini_chat/model_info',
        get_string('model', 'block_gemini_chat'),
        '<strong>gemini-2.5-flash</strong>',
    ));

    // quantidade de tokens.
    $settings->add(new admin_setting_configtext(
        'block_gemini_chat/maxoutputtokens',
        get_string('maxoutputtokens', 'block_gemini_chat'),
        get_string('maxoutputtokens_desc', 'block_gemini_chat'),
        '1024',
        PARAM_INT
    ));

    // analise de sentimento se e mais preciso ou não.
    $settings->add(new admin_setting_configtext(
        'block_gemini_chat/temperature',
        get_string('temperature', 'block_gemini_chat'),
        get_string('temperature_desc', 'block_gemini_chat'),
        '0.7',
        PARAM_FLOAT
    ));
    $ADMIN->add('reports', $settings);
}
