<?php
$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__])
    ->name('*.php')
    ->path('src/')
    ->path('tests/')
    ->exclude([
        'vendor/',
    ])
;

return PhpCsFixer\Config::create()->setRules(['@PSR2' => true])->setFinder($finder);
