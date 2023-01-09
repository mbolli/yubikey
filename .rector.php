<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([LevelSetList::UP_TO_PHP_80, \Rector\PHPUnit\Set\PHPUnitLevelSetList::UP_TO_PHPUNIT_90]);
    $rectorConfig->disableParallel();
};
