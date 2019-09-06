<?php

namespace Makeable\FileSizeCheck\Tests;


use DateTime;
use ZipArchive;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Console\Kernel;
use Spatie\Backup\BackupServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            BackupServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->initializeTempDirectory();

        config()->set('database.default', 'db1');

        Storage::fake('local');
    }

    protected function createFileOnDisk(string $diskName, string $filePath, DateTime $date, String $content = ''): string
    {
        Storage::disk($diskName)->put($filePath, $content);

        touch($this->getFullDiskPath($diskName, $filePath), $date->getTimestamp());

        return $filePath;
    }

    protected function getFullDiskPath(string $diskName, string $filePath): string
    {
        return $this->getDiskRootPath($diskName).DIRECTORY_SEPARATOR.$filePath;
    }

    protected function getDiskRootPath(string $diskName): string
    {
        return Storage::disk($diskName)->getDriver()->getAdapter()->getPathPrefix();
    }

    public function getStubDirectory(): string
    {
        return __DIR__.'/stubs';
    }

    public function getTempDirectory(): string
    {
        return __DIR__.'/temp';
    }

    public function initializeTempDirectory()
    {
        $this->initializeDirectory($this->getTempDirectory());
    }

    public function initializeDirectory(string $directory)
    {
        File::deleteDirectory($directory);

        File::makeDirectory($directory);

        $this->addGitignoreTo($directory);
    }

    public function addGitignoreTo(string $directory)
    {
        $fileName = "{$directory}/.gitignore";

        $fileContents = '*'.PHP_EOL.'!.gitignore';

        File::put($fileName, $fileContents);
    }
}