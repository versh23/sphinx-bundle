<?php

namespace Versh\SphinxBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Versh\SphinxBundle\Client\Client;
use Versh\SphinxBundle\Index\Index;
use Versh\SphinxBundle\Index\IndexFinder;
use Versh\SphinxBundle\Index\IndexManager;
use Versh\SphinxBundle\Persister\ObjectPersister;
use Versh\SphinxBundle\Persister\ObjectPersisterManager;

class VershSphinxExtension extends ConfigurableExtension
{
    /**
     * Configures the passed container according to the merged configuration.
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $def = $container->getDefinition(Client::class);
        $def->replaceArgument(0, $mergedConfig['connection']);

        $indexesRefs = [];
        $persisterRefs = [];

        //Индексы
        foreach ($mergedConfig['indexes'] as $indexName => $index) {
            $indexId = sprintf('versh_sphinx.index.%s', $indexName);

            $indexDef = new ChildDefinition(Index::class);
            $indexDef->replaceArgument(0, $indexName);
            $indexDef->replaceArgument(1, $index['fields']);
            $indexDef->replaceArgument(2, $index['attributes']);

            $container->setDefinition($indexId, $indexDef);

            $indexesRefs[$indexName] = new Reference($indexId);

            $persisterId = sprintf('versh_sphinx.persister.%s', $indexName);
            $persisterDef = new ChildDefinition(ObjectPersister::class);
            $persisterDef->replaceArgument(0, $index['method']);
            $persisterDef->replaceArgument(1, $index['class']);
            $persisterDef->replaceArgument(2, $indexesRefs[$indexName]);
            $container->setDefinition($persisterId, $persisterDef);

            $persisterRefs[$indexName] = $persisterDef;

            $finderId = sprintf('versh_sphinx.finder.%s', $indexName);
            $finderDef = new ChildDefinition(IndexFinder::class);
            $finderDef->replaceArgument(0, $persisterRefs[$indexName]);
            $container->setDefinition($finderId, $finderDef);
        }

        //IndexManager
        $managerDef = $container->getDefinition(IndexManager::class);
        $managerDef->replaceArgument(0, $indexesRefs);

        $persisterManagerDef = $container->getDefinition(ObjectPersisterManager::class);
        $persisterManagerDef->replaceArgument(0, $persisterRefs);
    }
}
