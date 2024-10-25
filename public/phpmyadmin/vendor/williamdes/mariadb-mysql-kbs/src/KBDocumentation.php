<?php

declare(strict_types = 1);

namespace Williamdes\MariaDBMySQLKBS;

use stdClass;
use JsonSerializable;

class KBDocumentation extends stdClass implements JsonSerializable
{
    /**
     * The URL
     *
     * @var string
     */
    private $url;

    /**
     * The anchor
     *
     * @var string|null
     */
    private $anchor = null;

    /**
     * Create a KBEntry object
     *
     * @param string      $url    The url
     * @param string|null $anchor The anchor
     */
    public function __construct(string $url, ?string $anchor = null)
    {
        $this->url = $url;
        if ($anchor !== null) {
            $this->anchor = $anchor;
        }
    }

    /**
     * Get the url
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the anchor
     *
     * @return string|null
     */
    public function getAnchor(): ?string
    {
        return $this->anchor;
    }

    /**
     * Used for json_encode function
     * This can seem useless, do not remove it.
     *
     * @return array<string,string>
     */
    public function jsonSerialize(): array
    {
        $outObj        = [];
        $outObj['url'] = $this->url;
        if ($this->anchor !== null) {
            $outObj['anchor'] = $this->anchor;
        }
        return $outObj;
    }

}
