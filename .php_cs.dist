<?php
$finder = PhpCsFixer\Finder::create()
    ->exclude('bootstrap/')
    ->exclude('public/')
    ->exclude('resources/')
    ->exclude('storage/')
    ->in(__DIR__)
;
return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;