<?php

namespace Pterodactyl\Exceptions;

use Exception;
use Throwable;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Response;
use Illuminate\Container\Container;
use Prologue\Alerts\AlertsMessageBag;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DisplayException extends PterodactylException implements HttpExceptionInterface
{
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    /**
     * @var string
     */
    protected $level;

    /**
     * Exception constructor.
     *
     * @param string $message
     * @param string $level
     * @param int $code
     */
    public function __construct($message, Throwable $previous = null, $level = self::LEVEL_ERROR, $code = 0)
    {
        parent::__construct($message, $code, $previous);

        $this->level = $level;
    }

    /**
     * @return string
     */
    public function getErrorLevel()
    {
        return $this->level;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return Response::HTTP_BAD_REQUEST;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return [];
    }

    /**
     * Render the exception to the user by adding a flashed message to the session
     * and then redirecting them back to the page that they came from. If the
     * request originated from an API hit, return the error in JSONAPI spec format.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json(Handler::toArray($this), $this->getStatusCode(), $this->getHeaders());
        }

        app(AlertsMessageBag::class)->danger($this->getMessage())->flash();

        return redirect()->back()->withInput();
    }

    /**
     * Log the exception to the logs using the defined error level only if the previous
     * exception is set.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function report()
    {
        if (!$this->getPrevious() instanceof Exception || !Handler::isReportable($this->getPrevious())) {
            return null;
        }

        try {
            $logger = Container::getInstance()->make(LoggerInterface::class);
        } catch (Exception $ex) {
            throw $this->getPrevious();
        }

        return $logger->{$this->getErrorLevel()}($this->getPrevious());
    }
}
