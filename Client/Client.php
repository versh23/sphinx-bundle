<?php

namespace Versh\SphinxBundle\Client;

use Foolz\SphinxQL\Drivers\Mysqli\Connection;
use Foolz\SphinxQL\Exception\DatabaseException;
use Foolz\SphinxQL\SphinxQL;

class Client
{
    private $connection;

    public function __construct(array $params)
    {
        $this->connection = new Connection();
        $this->connection->setParams($params);
        $this->connection->mbPush();
    }

    public function createBuilder()
    {
        return SphinxQL::create($this->connection);
    }

    public function reCreateTable(string $tableName, array $fields, array $attributes): bool
    {
        $resultDrop = $this->dropTable($tableName);
        $resultCreate = $this->createTable($tableName, $fields, $attributes);

        return $resultDrop || $resultCreate;
    }

    public function dropTable(string $tableName): bool
    {
        $query = sprintf('drop table %s', $tableName);

        try {
            $this->connection->query($query);
        } catch (DatabaseException $exception) {
            return false;
        }

        return true;
    }

    public function createTable(string $tableName, array $fields, array $attributes): bool
    {
        $fieldsAndAttributes = [];
        foreach ($fields as $name => $params) {
            $fieldsAndAttributes[] = $name.' field '.($params['stored'] ? 'stored' : '');
        }

        foreach ($attributes as $name => $params) {
            $fieldsAndAttributes[] = $name.' '.$params['type'];
        }

        $params = implode($fieldsAndAttributes, ', ');

        $query = sprintf('create table %s (%s)', $tableName, $params);

        try {
            $this->connection->query($query);
        } catch (DatabaseException $exception) {
            return false;
        }

        return true;
    }
}
