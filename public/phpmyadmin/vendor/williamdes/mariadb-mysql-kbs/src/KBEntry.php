<?php

declare(strict_types = 1);

namespace Williamdes\MariaDBMySQLKBS;

use stdClass;
use JsonSerializable;

class KBEntry extends stdClass implements JsonSerializable
{
    /**
     * The name of the variable
     *
     * @var string
     */
    private $name;

    /**
     * Type of variable
     *
     * @var string|null
     */
    private $type = null;

    /**
     * Is dynamic ?
     *
     * @var bool|null
     */
    private $dynamic = null;

    /**
     * Documentations
     *
     * @var KBDocumentation[]
     */
    private $docs = null;

    /**
     * Create a KBEntry object
     *
     * @param string      $name    The name of the variable
     * @param string|null $type    Type of variable
     * @param bool|null   $dynamic Is dynamic ?
     */
    public function __construct(string $name, ?string $type, ?bool $dynamic)
    {
        $this->name = $name;
        if ($type !== null) {
            $this->type = $type;
        }
        if ($dynamic !== null) {
            $this->dynamic = $dynamic;
        }
    }

    /**
     * Get the variable name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Is the variable dynamic
     *
     * @return bool|null
     */
    public function isDynamic(): ?bool
    {
        return $this->dynamic;
    }

    /**
     * Get the variable type
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Variable has documentations
     *
     * @return bool
     */
    public function hasDocumentations(): bool
    {
        if ($this->docs === null) {
            return false;
        } else {
            return count($this->docs) > 0;
        }
    }

    /**
     * Get all documentations
     *
     * @return KBDocumentation[]
     */
    public function getDocumentations(): array
    {
        return $this->docs;
    }

    /**
     * Add documentation link
     *
     * @param string      $url    The URL
     * @param string|null $anchor The anchor
     * @return KBDocumentation
     */
    public function addDocumentation(string $url, ?string $anchor = null): KBDocumentation
    {
        $this->url = $url;
        if ($this->docs === null) {
            $this->docs = [];
        }
        $kbd          = new KBDocumentation($url, $anchor);
        $this->docs[] = $kbd;
        return $kbd;
    }

    /**
     * Used for json_encode function
     * This can seem useless, do not remove it.
     *
     * @return array<string,KBDocumentation[]|bool|string>
     */
    public function jsonSerialize(): array
    {
        $outObj         = [];
        $outObj['name'] = $this->name;
        if ($this->type !== null) {
            $outObj['type'] = $this->type;
        }
        if ($this->dynamic !== null) {
            $outObj['dynamic'] = $this->dynamic;
        }
        if ($this->docs !== null) {
            $outObj['docs'] = $this->docs;
        }
        return $outObj;
    }

}
