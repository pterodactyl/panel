<?php

namespace Amp;

final class CombinedCancellationToken implements CancellationToken
{
    /** @var array{0: CancellationToken, 1: string}[] */
    private $tokens = [];

    /** @var string */
    private $nextId = "a";

    /** @var callable[] */
    private $callbacks = [];

    /** @var CancelledException|null */
    private $exception;

    public function __construct(CancellationToken ...$tokens)
    {
        $thatException = &$this->exception;
        $thatCallbacks = &$this->callbacks;

        foreach ($tokens as $token) {
            $id = $token->subscribe(static function (CancelledException $exception) use (&$thatException, &$thatCallbacks) {
                $thatException = $exception;

                $callbacks = $thatCallbacks;
                $thatCallbacks = [];

                foreach ($callbacks as $callback) {
                    asyncCall($callback, $thatException);
                }
            });

            $this->tokens[] = [$token, $id];
        }
    }

    public function __destruct()
    {
        foreach ($this->tokens as list($token, $id)) {
            /** @var CancellationToken $token */
            $token->unsubscribe($id);
        }
    }

    /** @inheritdoc */
    public function subscribe(callable $callback): string
    {
        $id = $this->nextId++;

        if ($this->exception) {
            asyncCall($callback, $this->exception);
        } else {
            $this->callbacks[$id] = $callback;
        }

        return $id;
    }

    /** @inheritdoc */
    public function unsubscribe(string $id)
    {
        unset($this->callbacks[$id]);
    }

    /** @inheritdoc */
    public function isRequested(): bool
    {
        foreach ($this->tokens as list($token)) {
            if ($token->isRequested()) {
                return true;
            }
        }

        return false;
    }

    /** @inheritdoc */
    public function throwIfRequested()
    {
        foreach ($this->tokens as list($token)) {
            $token->throwIfRequested();
        }
    }
}
