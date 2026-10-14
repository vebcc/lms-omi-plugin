<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/lib/src', __DIR__ . '/tests', __DIR__ . '/handlers', __DIR__ . '/modules', __DIR__ . '/bin'])
    ->append([__DIR__ . '/LMSOmiPlugin.php', __DIR__ . '/lib/autoload.php']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
    ])
    ->setFinder($finder);
