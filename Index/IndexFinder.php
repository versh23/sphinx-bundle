<?php

namespace Versh\SphinxBundle\Index;

use Foolz\SphinxQL\SphinxQL;
use Pagerfanta\Pagerfanta;
use Versh\SphinxBundle\Paginator\PagefantaAdapter;
use Versh\SphinxBundle\Persister\ObjectPersister;

class IndexFinder
{
    private $persister;
    private $index;

    public function __construct(ObjectPersister $persister)
    {
        $this->persister = $persister;
        $this->index = $persister->getIndex();
    }

    public function find(SphinxQL $query)
    {
        $result = $query->execute()->fetchAllAssoc();

        return $this->persister->transformToEntity($result);
    }

    public function findText(string $query)
    {
        return $this->find($this->index->createBuilder()->match($query));
    }

    public function findPaginated(SphinxQL $query)
    {
        return new Pagerfanta(new PagefantaAdapter($query, $this->persister));
    }

    public function findTextPaginated(string $query)
    {
        return $this->findPaginated($this->index->createBuilder()->match($query));
    }

    public function createBuilder()
    {
        return $this->index->createBuilder();
    }
}
