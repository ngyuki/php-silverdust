<?php
return [
    'target_php_version' => '7.0',

    'file_list' => [],

    'directory_list' => [
        'src/',
        'vendor/doctrine/dbal/',
    ],

    'exclude_file_regex' => '@^vendor/.*/(tests|Tests|test|Test)/@',

    'exclude_file_list' => [],

    'exclude_analysis_directory_list' => [
        'vendor/'
    ],
];
