<?php
/**
 * Server Plugin value object
 */

declare(strict_types=1);

namespace PhpMyAdmin\Server;

/**
 * Server Plugin value object
 */
final class Plugin
{
    /** @var string */
    private $name;

    /** @var string|null */
    private $version;

    /** @var string */
    private $status;

    /** @var string */
    private $type;

    /** @var string|null */
    private $typeVersion;

    /** @var string|null */
    private $library;

    /** @var string|null */
    private $libraryVersion;

    /** @var string|null */
    private $author;

    /** @var string|null */
    private $description;

    /** @var string */
    private $license;

    /** @var string|null */
    private $loadOption;

    /** @var string|null */
    private $maturity;

    /** @var string|null */
    private $authVersion;

    /**
     * @param string      $name           Name of the plugin
     * @param string|null $version        Version from the plugin's general type descriptor
     * @param string      $status         Plugin status
     * @param string      $type           Type of plugin
     * @param string|null $typeVersion    Version from the plugin's type-specific descriptor
     * @param string|null $library        Plugin's shared object file name
     * @param string|null $libraryVersion Version from the plugin's API interface
     * @param string|null $author         Author of the plugin
     * @param string|null $description    Description
     * @param string      $license        Plugin's licence
     * @param string|null $loadOption     How the plugin was loaded
     * @param string|null $maturity       Plugin's maturity level
     * @param string|null $authVersion    Plugin's version as determined by the plugin author
     */
    private function __construct(
        string $name,
        ?string $version,
        string $status,
        string $type,
        ?string $typeVersion,
        ?string $library,
        ?string $libraryVersion,
        ?string $author,
        ?string $description,
        string $license,
        ?string $loadOption,
        ?string $maturity,
        ?string $authVersion
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->status = $status;
        $this->type = $type;
        $this->typeVersion = $typeVersion;
        $this->library = $library;
        $this->libraryVersion = $libraryVersion;
        $this->author = $author;
        $this->description = $description;
        $this->license = $license;
        $this->loadOption = $loadOption;
        $this->maturity = $maturity;
        $this->authVersion = $authVersion;
    }

    /**
     * @param array $state array with the properties
     */
    public static function fromState(array $state): self
    {
        return new self(
            $state['name'] ?? '',
            $state['version'] ?? null,
            $state['status'] ?? '',
            $state['type'] ?? '',
            $state['typeVersion'] ?? null,
            $state['library'] ?? null,
            $state['libraryVersion'] ?? null,
            $state['author'] ?? null,
            $state['description'] ?? null,
            $state['license'] ?? '',
            $state['loadOption'] ?? null,
            $state['maturity'] ?? null,
            $state['authVersion'] ?? null
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'status' => $this->getStatus(),
            'type' => $this->getType(),
            'type_version' => $this->getTypeVersion(),
            'library' => $this->getLibrary(),
            'library_version' => $this->getLibraryVersion(),
            'author' => $this->getAuthor(),
            'description' => $this->getDescription(),
            'license' => $this->getLicense(),
            'load_option' => $this->getLoadOption(),
            'maturity' => $this->getMaturity(),
            'auth_version' => $this->getAuthVersion(),
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTypeVersion(): ?string
    {
        return $this->typeVersion;
    }

    public function getLibrary(): ?string
    {
        return $this->library;
    }

    public function getLibraryVersion(): ?string
    {
        return $this->libraryVersion;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLicense(): string
    {
        return $this->license;
    }

    public function getLoadOption(): ?string
    {
        return $this->loadOption;
    }

    public function getMaturity(): ?string
    {
        return $this->maturity;
    }

    public function getAuthVersion(): ?string
    {
        return $this->authVersion;
    }
}
