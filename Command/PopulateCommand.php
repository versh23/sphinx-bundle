<?php

namespace Versh\SphinxBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Versh\SphinxBundle\Index\IndexManager;
use Versh\SphinxBundle\Persister\ObjectPersisterManager;

class PopulateCommand extends Command
{
    /**
     * @var IndexManager
     */
    private $indexManager;
    private $persisterManager;

    public function __construct(?string $name = null, IndexManager $indexManager, ObjectPersisterManager $persisterManager)
    {
        parent::__construct($name);
        $this->indexManager = $indexManager;
        $this->persisterManager = $persisterManager;
    }

    protected static $defaultName = 'versh:sphinx:populate';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexes = $this->indexManager->getAllIndexes();

        foreach ($indexes as $index) {
            $index->reCreate();
            $persister = $this->persisterManager->getPersister($index->getName());

            $pager = $persister->createPager();

            $persister->insert($pager);
        }
    }
}
