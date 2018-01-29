<?php

namespace Versh\SphinxBundle\Paginator;

use Foolz\SphinxQL\Helper;
use Foolz\SphinxQL\SphinxQL;
use Pagerfanta\Adapter\AdapterInterface;
use Versh\SphinxBundle\Persister\ObjectPersister;

class PagefantaAdapter implements AdapterInterface
{
    private $sphinxQL;
    private $persister;

    public function __construct(SphinxQL $sphinxQL, ObjectPersister $persister)
    {
        $this->sphinxQL = $sphinxQL;
        $this->persister = $persister;
    }

    /**
     * Returns the number of results.
     *
     * @return int The number of results.
     */
    public function getNbResults()
    {
        $this->sphinxQL->execute();

        $helper = Helper::create($this->sphinxQL->getConnection());
        $meta = $helper->showMeta()->execute()->fetchAllAssoc();

        foreach ($meta as $item) {
            if ('total_found' === $item['Variable_name']) {
                return (int) $item['Value'];
            }
        }

        return 0;
    }

    /**
     * Returns an slice of the results.
     *
     * @param int $offset The offset.
     * @param int $length The length.
     *
     * @return array|\Traversable The slice.
     */
    public function getSlice($offset, $length)
    {
        $result = $this->sphinxQL->limit($length)->offset($offset)->execute()->fetchAllAssoc();

        return $this->persister->transformToEntity($result);
    }
}
