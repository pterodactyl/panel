<?php

namespace Facebook\WebDriver\Remote\Service;

use Exception;
use Facebook\WebDriver\Net\URLChecker;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Start local WebDriver service (when remote WebDriver server is not used).
 * This will start new process of respective browser driver and take care of its lifecycle.
 */
class DriverService
{
    /**
     * @var string
     */
    private $executable;

    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $args;

    /**
     * @var array
     */
    private $environment;

    /**
     * @var Process|null
     */
    private $process;

    /**
     * @param string $executable
     * @param int $port The given port the service should use.
     * @param array $args
     * @param array|null $environment Use the system environment if it is null
     */
    public function __construct($executable, $port, $args = [], $environment = null)
    {
        $this->setExecutable($executable);
        $this->url = sprintf('http://localhost:%d', $port);
        $this->args = $args;
        $this->environment = $environment ?: $_ENV;
    }

    /**
     * @return string
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * @return DriverService
     */
    public function start()
    {
        if ($this->process !== null) {
            return $this;
        }

        $this->process = $this->createProcess();
        $this->process->start();

        $this->checkWasStarted($this->process);

        $checker = new URLChecker();
        $checker->waitUntilAvailable(20 * 1000, $this->url . '/status');

        return $this;
    }

    /**
     * @return DriverService
     */
    public function stop()
    {
        if ($this->process === null) {
            return $this;
        }

        $this->process->stop();
        $this->process = null;

        $checker = new URLChecker();
        $checker->waitUntilUnavailable(3 * 1000, $this->url . '/shutdown');

        return $this;
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        if ($this->process === null) {
            return false;
        }

        return $this->process->isRunning();
    }

    /**
     * @deprecated Has no effect. Will be removed in next major version. Executable is now checked
     * when calling setExecutable().
     * @param string $executable
     * @return string
     */
    protected static function checkExecutable($executable)
    {
        return $executable;
    }

    /**
     * @param string $executable
     * @throws Exception
     */
    protected function setExecutable($executable)
    {
        if ($this->isExecutable($executable)) {
            $this->executable = $executable;

            return;
        }

        throw new Exception(
            sprintf(
                '"%s" is not executable. Make sure the path is correct or use environment variable to specify'
                 . ' location of the executable.',
                $executable
            )
        );
    }

    /**
     * @param Process $process
     */
    protected function checkWasStarted($process)
    {
        usleep(10000); // wait 10ms, otherwise the asynchronous process failure may not yet be propagated

        if (!$process->isRunning()) {
            throw new Exception(
                sprintf(
                    'Error starting driver executable "%s": %s',
                    $process->getCommandLine(),
                    $process->getErrorOutput()
                )
            );
        }
    }

    /**
     * @return Process
     */
    private function createProcess()
    {
        // BC: ProcessBuilder deprecated since Symfony 3.4 and removed in Symfony 4.0.
        if (class_exists(ProcessBuilder::class)
            && mb_strpos('@deprecated', (new \ReflectionClass(ProcessBuilder::class))->getDocComment()) === false
        ) {
            $processBuilder = (new ProcessBuilder())
                ->setPrefix($this->executable)
                ->setArguments($this->args)
                ->addEnvironmentVariables($this->environment);

            return $processBuilder->getProcess();
        }
        // Safe to use since Symfony 3.3
        $commandLine = array_merge([$this->executable], $this->args);

        return new Process($commandLine, null, $this->environment);
    }

    /**
     * Check whether given file is executable directly or using system PATH
     *
     * @param string $filename
     * @return bool
     */
    private function isExecutable($filename)
    {
        if (is_executable($filename)) {
            return true;
        }
        if ($filename !== basename($filename)) { // $filename is an absolute path, do no try to search it in PATH
            return false;
        }

        $paths = explode(PATH_SEPARATOR, getenv('PATH'));
        foreach ($paths as $path) {
            if (is_executable($path . DIRECTORY_SEPARATOR . $filename)) {
                return true;
            }
        }

        return false;
    }
}
