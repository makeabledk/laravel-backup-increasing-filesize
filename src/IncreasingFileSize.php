<?php

namespace Makeable\FileSizeCheck;

use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Helpers\Format;
use Spatie\Backup\Tasks\Monitor\HealthCheck;

class IncreasingFileSize extends HealthCheck
{
    /**
     * @var float|int
     */
    protected $tolerance;

    /**
     * IncreasingFileSize constructor.
     * @param float $tolerance
     */
    public function __construct($tolerance = 0.05)
    {
        $this->tolerance = $this->parseValue($tolerance);
    }

    /**
     * @param BackupDestination $backupDestination
     */
    public function checkHealth(BackupDestination $backupDestination)
    {
        if ($backupDestination->backups()->count() < 2) {
            return;
        }

        [$newestSize, $previousSize] = [
            $backupDestination->backups()->get(0)->size(),
            $backupDestination->backups()->get(1)->size(),
        ];

        $relativeSize = $newestSize / $previousSize;
        $loss = 1 - $relativeSize;

        [$fromSize, $toSize, $percentage] = [
            Format::humanReadableSize($previousSize),
            Format::humanReadableSize($newestSize),
            '-'.number_format($loss * 100, 2).'%',
        ];

        $this->failIf(
            $loss > $this->tolerance,
            "The size of your latest backup has reduced from {$fromSize} to {$toSize} ({$percentage}) compared to the previous backup."
        );
    }

    /**
     * @param $value
     * @return float|int
     */
    protected function parseValue($value)
    {
        if (! is_numeric($value) && preg_match('/%/', $value)) {
            return floatval($value) / 100;
        }

        return floatval($value);
    }
}
