<?php
/**
 * Value object class for a character set
 */

declare(strict_types=1);

namespace PhpMyAdmin\Charsets;

/**
 * Value object class for a character set
 */
final class Charset
{
    /**
     * The character set name
     *
     * @var string
     */
    private $name;

    /**
     * A description of the character set
     *
     * @var string
     */
    private $description;

    /**
     * The default collation for the character set
     *
     * @var string
     */
    private $defaultCollation;

    /**
     * The maximum number of bytes required to store one character
     *
     * @var int
     */
    private $maxLength;

    /**
     * @param string $name             Charset name
     * @param string $description      Description
     * @param string $defaultCollation Default collation
     * @param int    $maxLength        Maximum length
     */
    private function __construct(
        string $name,
        string $description,
        string $defaultCollation,
        int $maxLength
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->defaultCollation = $defaultCollation;
        $this->maxLength = $maxLength;
    }

    /**
     * @param string[] $state State obtained from the database server
     * @psalm-param array{Charset?:string, Description?:string, 'Default collation'?:string, Maxlen?:string} $state
     *
     * @return Charset
     */
    public static function fromServer(array $state): self
    {
        return new self(
            $state['Charset'] ?? '',
            $state['Description'] ?? '',
            $state['Default collation'] ?? '',
            (int) ($state['Maxlen'] ?? 0)
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDefaultCollation(): string
    {
        return $this->defaultCollation;
    }

    public function getMaxLength(): int
    {
        return $this->maxLength;
    }
}
