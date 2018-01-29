<?php

namespace Versh\SphinxBundle\Index;

use Versh\SphinxBundle\Client\Client;

class Index
{
    private $name;

    private $fields;

    private $attributes;

    private $client;

    public function __construct(string $name, array $fields, array $attributes, Client $client)
    {
        $this->fields = $fields;
        $this->attributes = $attributes;
        $this->name = $name;
        $this->client = $client;
    }

    public function drop(): bool
    {
        return $this->client->dropTable($this->name);
    }

    public function create(): bool
    {
        return $this->client->createTable($this->name, $this->fields, $this->attributes);
    }

    public function reCreate(): bool
    {
        return $this->client->reCreateTable($this->name, $this->fields, $this->attributes);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function createBuilder()
    {
        return $this->client->createBuilder()->select()->from($this->getName());
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    public function bulkInsert(array $columns, array $values)
    {
        $builder = $this->createBuilder()

            ->insert()
            ->into($this->getName())
            ->columns($columns);
        foreach ($values as $value) {
            $builder->values($value);
        }

        $result = $builder->execute();

        return $result->getAffectedRows();
    }
}
