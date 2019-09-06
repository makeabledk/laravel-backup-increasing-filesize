<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Makeable\FileSizeCheck\IncreasingFileSize;
use Makeable\FileSizeCheck\Tests\TestCase;
use Spatie\Backup\Events\HealthyBackupWasFound;
use Spatie\Backup\Events\UnhealthyBackupWasFound;

class IncreasingFileSizeTest extends TestCase
{
    /** @var \Carbon\Carbon */
    protected $date;

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
        $this->fakeNextBackupOfSize(1, now()->subDay(1), 100);
        $this->fakeNextBackupOfSize(2, now(), 96);

        Artisan::call('backup:monitor');

        Event::assertDispatched(HealthyBackupWasFound::class);

        $this->expectsEvents(HealthyBackupWasFound::class);
        Artisan::call('backup:monitor');
    }

    /** @test **/
    public function it_is_considered_healthy_when_newest_backup_is_reduced_within_tolerance()
    {
        $this->fakeNextBackupOfSize(1, now()->subDay(1), 100);
        $this->fakeNextBackupOfSize(2, now(), 96);
        $this->expectsEvents(HealthyBackupWasFound::class);
        Artisan::call('backup:monitor');
    }

    /** @test **/
    public function it_is_considered_unhealthy_when_newest_backup_is_reduced_beyond_tolerance()
    {
        $this->fakeNextBackupOfSize(1, now()->subDay(1), 100);
        $this->fakeNextBackupOfSize(2, now(), 94);

        Artisan::call('backup:monitor');

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
        $this->expectsEvents(HealthyBackupWasFound::class);
        Artisan::call('backup:monitor');
        $this->fakeNextBackupOfSize(3, now(), 80);
        $this->expectsEvents(UnhealthyBackupWasFound::class);
        Artisan::call('backup:monitor');
    }

    /**
     * @param $no
     * @param string $date
     * @param int $sizeInKb
     * @throws Exception
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
