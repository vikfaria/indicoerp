<?php

return [
    'saft' => [
        /*
        |--------------------------------------------------------------------------
        | SAF-T MZ XSD validation
        |--------------------------------------------------------------------------
        |
        | Set SAFT_MZ_REQUIRE_XSD_VALIDATION=true in production to enforce
        | validation against the official Mozambican schema before export.
        | Provide the absolute path to the official XSD via SAFT_MZ_XSD_PATH.
        |
        */
        'require_xsd_validation' => env('SAFT_MZ_REQUIRE_XSD_VALIDATION', false),
        'xsd_path' => env('SAFT_MZ_XSD_PATH', ''),
    ],
    'vat' => [
        /*
        |--------------------------------------------------------------------------
        | Input VAT deductibility enforcement
        |--------------------------------------------------------------------------
        |
        | warn  => keeps posting and stores warning for non-deductible scenarios
        | block => blocks posting when deductibility prerequisites fail
        |
        */
        'deductibility_enforcement' => env('SCE_VAT_DEDUCTIBILITY_ENFORCEMENT', 'warn'),
    ],
];
