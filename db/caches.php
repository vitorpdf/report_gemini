<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Cache para report_gemini_data.
 * Salva as respostas do geminni por 1 hora no cahe 
 */
$definitions = [
    'gemini_responses' => [
        'mode'       => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => false,
        'ttl'        => 3600, // 1 hora
    ],
];
