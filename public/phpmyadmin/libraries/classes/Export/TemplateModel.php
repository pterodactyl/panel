<?php

declare(strict_types=1);

namespace PhpMyAdmin\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Util;

use function sprintf;

final class TemplateModel
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
    }

    public function create(DatabaseName $db, TableName $table, Template $template): string
    {
        $query = sprintf(
            'INSERT INTO %s.%s (`username`, `export_type`, `template_name`, `template_data`)'
                . ' VALUES (\'%s\', \'%s\', \'%s\', \'%s\');',
            Util::backquote($db),
            Util::backquote($table),
            $this->dbi->escapeString($template->getUsername()),
            $this->dbi->escapeString($template->getExportType()),
            $this->dbi->escapeString($template->getName()),
            $this->dbi->escapeString($template->getData())
        );
        $result = $this->dbi->tryQueryAsControlUser($query);
        if ($result !== false) {
            return '';
        }

        return $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);
    }

    public function delete(DatabaseName $db, TableName $table, string $user, int $id): string
    {
        $query = sprintf(
            'DELETE FROM %s.%s WHERE `id` = %s AND `username` = \'%s\';',
            Util::backquote($db),
            Util::backquote($table),
            $id,
            $this->dbi->escapeString($user)
        );
        $result = $this->dbi->tryQueryAsControlUser($query);
        if ($result !== false) {
            return '';
        }

        return $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);
    }

    /**
     * @return Template|string
     */
    public function load(DatabaseName $db, TableName $table, string $user, int $id)
    {
        $query = sprintf(
            'SELECT * FROM %s.%s WHERE `id` = %s AND `username` = \'%s\';',
            Util::backquote($db),
            Util::backquote($table),
            $id,
            $this->dbi->escapeString($user)
        );
        $result = $this->dbi->tryQueryAsControlUser($query);
        if ($result === false) {
            return $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);
        }

        $data = [];
        while ($row = $result->fetchAssoc()) {
            $data = $row;
        }

        return Template::fromArray([
            'id' => (int) $data['id'],
            'username' => $data['username'],
            'exportType' => $data['export_type'],
            'name' => $data['template_name'],
            'data' => $data['template_data'],
        ]);
    }

    public function update(DatabaseName $db, TableName $table, Template $template): string
    {
        $query = sprintf(
            'UPDATE %s.%s SET `template_data` = \'%s\' WHERE `id` = %s AND `username` = \'%s\';',
            Util::backquote($db),
            Util::backquote($table),
            $this->dbi->escapeString($template->getData()),
            $template->getId(),
            $this->dbi->escapeString($template->getUsername())
        );
        $result = $this->dbi->tryQueryAsControlUser($query);
        if ($result !== false) {
            return '';
        }

        return $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);
    }

    /**
     * @return Template[]|string
     */
    public function getAll(DatabaseName $db, TableName $table, string $user, string $exportType)
    {
        $query = sprintf(
            'SELECT * FROM %s.%s WHERE `username` = \'%s\' AND `export_type` = \'%s\' ORDER BY `template_name`;',
            Util::backquote($db),
            Util::backquote($table),
            $this->dbi->escapeString($user),
            $this->dbi->escapeString($exportType)
        );
        $result = $this->dbi->tryQueryAsControlUser($query);
        if ($result === false) {
            return $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);
        }

        $templates = [];
        while ($row = $result->fetchAssoc()) {
            $templates[] = Template::fromArray([
                'id' => (int) $row['id'],
                'username' => $row['username'],
                'exportType' => $row['export_type'],
                'name' => $row['template_name'],
                'data' => $row['template_data'],
            ]);
        }

        return $templates;
    }
}
