<?php

declare(strict_types = 1);

namespace Williamdes\MariaDBMySQLKBS;

use stdClass;
use JsonSerializable;

class SlimData extends stdClass implements JsonSerializable
{
    /**
     * Variables
     *
     * @var KBEntry[]
     */
    private $vars = [];

    /**
     * File revision
     *
     * @var float
     */
    private $version = 1;

    /**
     * Urls
     *
     * @var string[]
     */
    private $urls = [];

    /**
     * Types of documentation
     *
     * @var array<string,int>
     */
    private $types = ['MYSQL' => 1, 'MARIADB' => 2];

    /**
     * Types of variables
     *
     * @var array<string,int>
     */
    private $varTypes = [
        'string' => 1,
        'boolean' => 2,
        'integer' => 3,
        'numeric' => 4,
        'enumeration' => 5,
        'set' => 6,
        'directory name' => 7,
        'file name' => 8,
        'byte' => 9,
    ];

    /**
     * Create a slimData object
     *
     * @param float|null             $version  The version
     * @param array<string,int>|null $types    The types of documentations
     * @param array<string,int>|null $varTypes The types of variables
     */
    public function __construct(
        ?float $version = null,
        ?array $types = null,
        ?array $varTypes = null
    ) {
        if ($version !== null) {
            $this->version = $version;
        }

        if ($types !== null) {
            $this->types = $types;
        }

        if ($varTypes !== null) {
            $this->varTypes = $varTypes;
        }
    }

    /**
     * Add a variable
     *
     * @param string      $name    The name
     * @param string|null $type    The type
     * @param bool|null   $dynamic Is dynamic
     * @return KBEntry The newly created KBEntry
     */
    public function addVariable(string $name, ?string $type, ?bool $dynamic): KBEntry
    {
        $kbe          = new KBEntry($name, $type, $dynamic);
        $this->vars[] = $kbe;
        return $kbe;
    }

    /**
     * Used for json_encode function
     * This can seem useless, do not remove it.
     *
     * @phpstan-ignore-next-line
     * @return array<string,array|float|stdClass>
     */
    public function jsonSerialize(): array
    {
        $outObj = [];
        if (count($this->vars) > 0) {
            $vars = new stdClass();
            foreach ($this->vars as $var) {
                $variable    = new stdClass();
                $variable->d = $var->isDynamic();
                if ($variable->d === null) {
                    unset($variable->d);
                }

                if ($var->getType() !== null) {
                    if (isset($this->varTypes[$var->getType()]) === false) {
                        $this->varTypes[$var->getType()] = count($this->varTypes) + 1;
                    }

                    $variable->t = $this->varTypes[$var->getType()];
                }

                if ($var->hasDocumentations()) {
                    $variable->a = [];
                    foreach ($var->getDocumentations() as $kbd) {
                        $entry    = new stdClass();
                        $entry->a = $kbd->getAnchor();
                        if ($entry->a === null) {
                            unset($entry->a);
                        }
                        if (preg_match('!^(https|http)://mariadb.com!', $kbd->getUrl())) {
                            $entry->t = $this->types['MARIADB'];
                        } elseif (preg_match('!^(https|http)://dev.mysql.com!', $kbd->getUrl())) {
                            $entry->t = $this->types['MYSQL'];
                        }
                        if (isset($entry->t)) {// If has no valid type, skip.
                            //Do not allow other urls.
                            $keyIndex = array_search($kbd->getUrl(), $this->urls);
                            if ($keyIndex === false) {
                                $this->urls[] = $kbd->getUrl();
                            }
                            $keyIndex = array_search($kbd->getUrl(), $this->urls);
                            $entry->u = $keyIndex;

                            $variable->a[] = $entry;
                        }
                    }
                }

                $vars->{$var->getName()} = $variable;
            }
            $outObj['vars'] = $vars;
        }
        $outObj['version'] = $this->version;
        if (count($this->vars) > 0) {
            $outObj['types']    = array_flip($this->types);
            $outObj['varTypes'] = array_flip($this->varTypes);
            $outObj['urls']     = $this->urls;
        }
        return $outObj;
    }

}
