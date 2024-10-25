<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server;

use PhpMyAdmin\DatabaseInterface;

use function __;

class Plugins
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param DatabaseInterface $dbi DatabaseInterface instance
     */
    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
    }

    /**
     * @return Plugin[]
     */
    public function getAll(): array
    {
        global $cfg;

        $sql = 'SHOW PLUGINS';
        if (! $cfg['Server']['DisableIS']) {
            $sql = 'SELECT * FROM information_schema.PLUGINS ORDER BY PLUGIN_TYPE, PLUGIN_NAME';
        }

        $result = $this->dbi->query($sql);
        $plugins = [];
        while ($row = $result->fetchAssoc()) {
            $plugins[] = $this->mapRowToPlugin($row);
        }

        return $plugins;
    }

    /**
     * @return array<int|string, string>
     */
    public function getAuthentication(): array
    {
        $result = $this->dbi->query(
            'SELECT `PLUGIN_NAME`, `PLUGIN_DESCRIPTION` FROM `information_schema`.`PLUGINS`'
                . ' WHERE `PLUGIN_TYPE` = \'AUTHENTICATION\';'
        );

        $plugins = [];

        while ($row = $result->fetchAssoc()) {
            $plugins[$row['PLUGIN_NAME']] = $this->getTranslatedDescription($row['PLUGIN_DESCRIPTION']);
        }

        return $plugins;
    }

    private function getTranslatedDescription(string $description): string
    {
        // mysql_native_password
        if ($description === 'Native MySQL authentication') {
            return __('Native MySQL authentication');
        }

        // sha256_password
        if ($description === 'SHA256 password authentication') {
            return __('SHA256 password authentication');
        }

        // caching_sha2_password
        if ($description === 'Caching sha2 authentication') {
            return __('Caching sha2 authentication');
        }

        // auth_socket || unix_socket
        if ($description === 'Unix Socket based authentication') {
            return __('Unix Socket based authentication');
        }

        // mysql_old_password
        if ($description === 'Old MySQL-4.0 authentication') {
            return __('Old MySQL-4.0 authentication');
        }

        return $description;
    }

    /**
     * @param array $row Row fetched from database
     */
    private function mapRowToPlugin(array $row): Plugin
    {
        return Plugin::fromState([
            'name' => $row['PLUGIN_NAME'] ?? $row['Name'],
            'version' => $row['PLUGIN_VERSION'] ?? null,
            'status' => $row['PLUGIN_STATUS'] ?? $row['Status'],
            'type' => $row['PLUGIN_TYPE'] ?? $row['Type'],
            'typeVersion' => $row['PLUGIN_TYPE_VERSION'] ?? null,
            'library' => $row['PLUGIN_LIBRARY'] ?? $row['Library'] ?? null,
            'libraryVersion' => $row['PLUGIN_LIBRARY_VERSION'] ?? null,
            'author' => $row['PLUGIN_AUTHOR'] ?? null,
            'description' => $row['PLUGIN_DESCRIPTION'] ?? null,
            'license' => $row['PLUGIN_LICENSE'] ?? $row['License'],
            'loadOption' => $row['LOAD_OPTION'] ?? null,
            'maturity' => $row['PLUGIN_MATURITY'] ?? null,
            'authVersion' => $row['PLUGIN_AUTH_VERSION'] ?? null,
        ]);
    }
}
