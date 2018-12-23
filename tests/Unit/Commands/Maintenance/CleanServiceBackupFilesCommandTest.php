<?php

namespace Tests\Unit\Commands\Maintenance;

use SplFileInfo;
use Mockery as m;
use Carbon\Carbon;
use Tests\Unit\Commands\CommandTestCase;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Pterodactyl\Console\Commands\Maintenance\CleanServiceBackupFilesCommand;

class CleanServiceBackupFilesCommandTest extends CommandTestCase
{
    /**
     * @var \Pterodactyl\Console\Commands\Maintenance\CleanServiceBackupFilesCommand
     */
    protected $command;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem|\Mockery\Mock
     */
    protected $disk;

    /**
     * @var \Illuminate\Contracts\Filesystem\Factory|\Mockery\Mock
     */
    protected $filesystem;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());
        $this->disk = m::mock(Filesystem::class);
        $this->filesystem = m::mock(Factory::class);
        $this->filesystem->shouldReceive('disk')->withNoArgs()->once()->andReturn($this->disk);
    }

    /**
     * Test that a file is deleted if it is > 5min old.
     */
    public function testCommandCleansFilesMoreThan5MinutesOld()
    {
        $file = new SplFileInfo('testfile.txt');

        $this->disk->shouldReceive('files')->with('services/.bak')->once()->andReturn([$file]);
        $this->disk->shouldReceive('lastModified')->with($file->getPath())->once()->andReturn(Carbon::now()->subDays(100)->getTimestamp());
        $this->disk->shouldReceive('delete')->with($file->getPath())->once()->andReturnNull();

        $display = $this->runCommand($this->getCommand());

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.maintenance.deleting_service_backup', ['file' => 'testfile.txt']), $display);
    }

    /**
     * Test that a file isn't deleted if it is < 5min old.
     */
    public function testCommandDoesNotCleanFileLessThan5MinutesOld()
    {
        $file = new SplFileInfo('testfile.txt');

        $this->disk->shouldReceive('files')->with('services/.bak')->once()->andReturn([$file]);
        $this->disk->shouldReceive('lastModified')->with($file->getPath())->once()->andReturn(Carbon::now()->getTimestamp());

        $display = $this->runCommand($this->getCommand());

        $this->assertEmpty($display);
    }

    /**
     * Return an instance of the command for testing.
     *
     * @return \Pterodactyl\Console\Commands\Maintenance\CleanServiceBackupFilesCommand
     */
    private function getCommand(): CleanServiceBackupFilesCommand
    {
        $command = new CleanServiceBackupFilesCommand($this->filesystem);
        $command->setLaravel($this->app);

        return $command;
    }
}
