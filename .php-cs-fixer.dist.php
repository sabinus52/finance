<?php

$header = <<<'EOF'
 This file is part of My Finance Application.
 (c) Sabinus52 <sabinus52@gmail.com>
 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
EOF;


$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
    ->in([
        __DIR__ . '/src/',
        __DIR__ . '/tests/',
    ])
;

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PHP74Migration' => true,
        '@PHP74Migration:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'no_superfluous_phpdoc_tags' => false,
        'native_function_invocation' => false,
        'native_constant_invocation' => false,
        'header_comment' => ['header' => $header, 'comment_type' => 'PHPDoc', 'location' => 'after_declare_strict', 'separate' => 'both'],
        'phpdoc_to_comment' => false,
    ])
    ->setFinder($finder)
;

return $config;
