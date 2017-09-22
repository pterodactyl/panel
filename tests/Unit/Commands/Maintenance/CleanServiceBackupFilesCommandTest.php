<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Tests\Unit\Commands\Maintenance;

use Mockery as m;
use Carbon\Carbon;
use Tests\Unit\Commands\CommandTestCase;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Pterodactyl\Console\Commands\Maintenance\CleanServiceBackupFilesCommand;

class CleanServiceBackupFilesCommandTest extends CommandTestCase
{
    /**
     * @var \Carbon\Carbon|\Mockery\Mock
     */
    protected $carbon;

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

        $this->carbon = m::mock(Carbon::class);
        $this->disk = m::mock(Filesystem::class);
        $this->filesystem = m::mock(Factory::class);
        $this->filesystem->shouldReceive('disk')->withNoArgs()->once()->andReturn($this->disk);

        $this->command = new CleanServiceBackupFilesCommand($this->carbon, $this->filesystem);
        $this->command->setLaravel($this->app);
    }

    /**
     * Test that a file is deleted if it is > 5min old.
     */
    public function testCommandCleansFilesMoreThan5MinutesOld()
    {
        $this->disk->shouldReceive('files')->with('services/.bak')->once()->andReturn(['testfile.txt']);
        $this->disk->shouldReceive('lastModified')->with('testfile.txt')->once()->andReturn('disk:last:modified');
        $this->carbon->shouldReceive('timestamp')->with('disk:last:modified')->once()->andReturnSelf();
        $this->carbon->shouldReceive('now')->withNoArgs()->once()->andReturnNull();
        $this->carbon->shouldReceive('diffInMinutes')->with(null)->once()->andReturn(10);
        $this->disk->shouldReceive('delete')->with('testfile.txt')->once()->andReturnNull();

        $display = $this->runCommand($this->command);

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.maintenance.deleting_service_backup', ['file' => 'testfile.txt']), $display);
    }

    /**
     * Test that a file isn't deleted if it is < 5min old.
     */
    public function testCommandDoesNotCleanFileLessThan5MinutesOld()
    {
        $this->disk->shouldReceive('files')->with('services/.bak')->once()->andReturn(['testfile.txt']);
        $this->disk->shouldReceive('lastModified')->with('testfile.txt')->once()->andReturn('disk:last:modified');
        $this->carbon->shouldReceive('timestamp')->with('disk:last:modified')->once()->andReturnSelf();
        $this->carbon->shouldReceive('now')->withNoArgs()->once()->andReturnNull();
        $this->carbon->shouldReceive('diffInMinutes')->with(null)->once()->andReturn(2);

        $display = $this->runCommand($this->command);

        $this->assertEmpty($display);
    }
}
