<?php

namespace Versh\SphinxBundle\Persister;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Versh\SphinxBundle\Index\Index;

class ObjectPersister
{
    const ALIAS = 'a';
    const PER_PAGES = 100;
    const ID = 'id';

    private $doctrine;

    private $method;
    private $class;
    private $index;
    private $propertyAccessor;

    public function __construct(
        string $method, string  $class,
        Index $index, ManagerRegistry $doctrine,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->method = $method;
        $this->class = $class;
        $this->index = $index;
        $this->doctrine = $doctrine;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @return QueryBuilder
     */
    private function getEntityQueryBuilder(): QueryBuilder
    {
        $repository = $this->doctrine
            ->getManagerForClass($this->class)
            ->getRepository($this->class);

        return call_user_func(
            [$repository, $this->method], self::ALIAS
        );
    }

    public function createPager(): Pagerfanta
    {
        $queryBuilder = $this->getEntityQueryBuilder();

        return new Pagerfanta(new DoctrineORMAdapter($queryBuilder));
    }

    public function insert(Pagerfanta $pager)
    {
        $pager->setMaxPerPage(self::PER_PAGES);

        $lastPage = $pager->getNbPages();
        $page = $pager->getCurrentPage();

        do {
            $pager->setCurrentPage($page);

            $this->insertPage($page, $pager);

            ++$page;
        } while ($page <= $lastPage);
    }

    private function insertPage(int $page, Pagerfanta $pager)
    {
        $pager->setCurrentPage($page);

        $objects = $pager->getCurrentPageResults();

        $values = [];
        $columns = [];
        foreach ($objects as $object) {
            [$columns, $valueObject] = $this->transformToSphinx($object);
            $values[] = $valueObject;
        }

        $this->index->bulkInsert($columns, $values);
    }

    private function transformToSphinx($object): array
    {
        $fields = array_merge($this->index->getFields(), $this->index->getAttributes());

        $values = [];
        $values[] = $this->propertyAccessor->getValue($object, self::ID);
        foreach ($fields as $field) {
            $values[] = $this->propertyAccessor->getValue($object, $field['path']);
        }

        $columns = array_keys($fields);
        array_unshift($columns, self::ID);

        return [$columns, $values];
    }

    public function transformToEntity(array $sphinxResult, $hydrate = true)
    {
        $identifier = self::ID;
        $ids = [];

        foreach ($sphinxResult as $item) {
            $ids[] = $item[self::ID];
        }

        $objects = $this->findByIdentifiers($ids, $hydrate);
        $propertyAccessor = $this->propertyAccessor;

        // sort objects in the order of ids
        $idPos = array_flip($ids);
        usort(
            $objects,
            function ($a, $b) use ($idPos, $identifier, $propertyAccessor, $hydrate) {
                if ($hydrate) {
                    return $idPos[(string) $propertyAccessor->getValue(
                            $a,
                            $identifier
                        )] > $idPos[(string) $propertyAccessor->getValue($b, $identifier)];
                }

                return $idPos[$a[$identifier]] > $idPos[$b[$identifier]];
            }
        );

        return $objects;
    }

    private function findByIdentifiers(array $identifierValues, $hydrate)
    {
        if (empty($identifierValues)) {
            return [];
        }
        $hydrationMode = $hydrate ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY;

        $qb = $this->getEntityQueryBuilder();
        $qb->andWhere($qb->expr()->in(static::ALIAS.'.'.self::ID, ':values'))
            ->setParameter('values', $identifierValues);

        $query = $qb->getQuery();

        return $query->setHydrationMode($hydrationMode)->execute();
    }

    public function getIndex(): Index
    {
        return $this->index;
    }
}
