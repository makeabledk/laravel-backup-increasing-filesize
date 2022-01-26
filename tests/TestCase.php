<?php

namespace Makeable\IncreasingFilesize\Tests;

use DateTime;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Backup\BackupServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            BackupServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->initializeTempDirectory();

        config()->set('database.default', 'db1');

        Storage::fake('local');
    }

    protected function createFileOnDisk(string $diskName, string $filePath, DateTime $date, string $content = ''): string
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
