<?php
/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:2.18.1|configurator
 * you can change this configuration by importing this file.
 */
return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHP80Migration:risky' => true,
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'declare_strict_types' => false,
        'operator_linebreak' => true,
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
    ])
    ->setFinder(PhpCsFixer\Finder::create()->exclude('vendor')->in(__DIR__));
