<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot.'/vendor/autoload.php';

$instant = \Carbon\CarbonImmutable::parse('2026-08-25 10:00:00', 'America/Hermosillo');
\Carbon\CarbonImmutable::setTestNow($instant);
\Carbon\Carbon::setTestNow($instant);
