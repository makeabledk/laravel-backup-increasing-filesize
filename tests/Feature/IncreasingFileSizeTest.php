<?php

namespace Makeable\IncreasingFilesize\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Makeable\IncreasingFilesize\IncreasingFileSize;
use Makeable\IncreasingFilesize\Tests\TestCase;
use Spatie\Backup\Events\HealthyBackupWasFound;
use Spatie\Backup\Events\UnhealthyBackupWasFound;

class IncreasingFileSizeTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Event::fake();

        config()->set('backup.monitor_backups.0.health_checks', [
            IncreasingFileSize::class,
        ]);
    }

    /** @test **/
    public function it_is_considered_healthy_when_only_one_backup_present()
    {
        $this->fakeNextBackupOfSize(1, now());

        $this->artisan('backup:monitor')->assertExitCode(0);

        Event::assertDispatched(HealthyBackupWasFound::class);
    }

    /** @test **/
    public function it_is_considered_healthy_when_newest_backup_is_reduced_within_tolerance()
    {
        $this->fakeNextBackupOfSize(1, now()->subDay(1), 100);
        $this->fakeNextBackupOfSize(2, now(), 96);

        $this->artisan('backup:monitor')->assertExitCode(0);

        Event::assertDispatched(HealthyBackupWasFound::class);
    }

    /** @test **/
    public function it_is_considered_unhealthy_when_newest_backup_is_reduced_beyond_tolerance()
    {
        $this->fakeNextBackupOfSize(1, now()->subDay(1), 100);
        $this->fakeNextBackupOfSize(2, now(), 94);

        $this->artisan('backup:monitor')->assertExitCode(0);

        Event::assertDispatched(UnhealthyBackupWasFound::class);
    }

    /** @test **/
    public function tolerance_can_be_configured()
    {
        $this->app['config']->set('backup.monitor_backups.0.health_checks', [
            IncreasingFileSize::class => '10%',
        ]);

        $this->fakeNextBackupOfSize(1, now()->subDay(2), 100);
        $this->fakeNextBackupOfSize(2, now()->subDay(1), 94);

        $this->artisan('backup:monitor')->assertExitCode(0);
        Event::assertDispatched(HealthyBackupWasFound::class);

        $this->fakeNextBackupOfSize(3, now(), 80);

        $this->artisan('backup:monitor')->assertExitCode(0);
        Event::assertDispatched(UnhealthyBackupWasFound::class);
    }

    /**
     * @param $no
     * @param $date
     * @param int $sizeInKb
     */
    protected function fakeNextBackupOfSize($no, $date, $sizeInKb = 1)
    {
        $this->createFileOnDisk(
            'local',
            "mysite/backup-{$no}.zip",
            $date,
            random_bytes($sizeInKb * 1024)
        );
    }
}
