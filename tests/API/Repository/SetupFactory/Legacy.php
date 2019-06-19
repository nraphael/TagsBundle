<?php

namespace Netgen\TagsBundle\Tests\API\Repository\SetupFactory;

use eZ\Publish\API\Repository\Tests\SetupFactory\Legacy as BaseLegacy;
use eZ\Publish\Core\Base\ServiceContainer;
use Netgen\TagsBundle\API\Repository\TagsService as APITagsService;
use Netgen\TagsBundle\Core\Persistence\Legacy\Tags\Gateway\DoctrineDatabase;
use Netgen\TagsBundle\Core\Persistence\Legacy\Tags\Gateway\ExceptionConversion;
use Netgen\TagsBundle\Core\Persistence\Legacy\Tags\Handler;
use Netgen\TagsBundle\Core\Persistence\Legacy\Tags\Mapper;
use Netgen\TagsBundle\Core\Repository\TagsService;

/**
 * A Test Factory is used to setup the infrastructure for a tests, based on a
 * specific repository implementation to test.
 */
class Legacy extends BaseLegacy
{
    /**
     * Initial data for eztags field type.
     *
     * @var array
     */
    protected static $tagsInitialData;

    public function getServiceContainer(): ServiceContainer
    {
        /** @var \Symfony\Component\DependencyInjection\Loader\YamlFileLoader $loader */
        $loader = null;

        if (!isset(self::$serviceContainer)) {
            $config = include __DIR__ . '/../../../../vendor/ezsystems/ezpublish-kernel/config.php';
            $installDir = $config['install_dir'];

            /** @var \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder */
            $containerBuilder = include $config['container_builder_path'];

            $loader->load('search_engines/legacy.yml');
            $loader->load('tests/integration_legacy.yml');
            $loader->load(__DIR__ . '/../../../../bundle/Resources/config/papi.yml');
            $loader->load(__DIR__ . '/../../../../bundle/Resources/config/limitations.yml');
            $loader->load(__DIR__ . '/../../../../bundle/Resources/config/fieldtypes.yml');
            $loader->load(__DIR__ . '/../../../../bundle/Resources/config/persistence.yml');
            $loader->load(__DIR__ . '/../../../../bundle/Resources/config/storage/doctrine.yml');
            $loader->load(__DIR__ . '/../../../../bundle/Resources/config/storage/cache_disabled.yml');
            $loader->load(__DIR__ . '/../../../../bundle/Resources/config/search/legacy.yml');

            $loader->load(__DIR__ . '/../../../../tests/settings/settings.yml');
            $loader->load(__DIR__ . '/../../../../tests/settings/integration/legacy.yml');

            $containerBuilder->setParameter(
                'legacy_dsn',
                self::$dsn
            );

            self::$serviceContainer = new ServiceContainer(
                $containerBuilder,
                $installDir,
                $config['cache_dir'],
                true,
                true
            );
        }

        return self::$serviceContainer;
    }

    /**
     * Returns a configured tags service for testing.
     */
    public function getTagsService(bool $initializeFromScratch = true): APITagsService
    {
        $repository = $this->getRepository($initializeFromScratch);

        $languageHandler = $this->getServiceContainer()->get('eztags.ezpublish.spi.persistence.legacy.language.handler');
        $languageMaskGenerator = $this->getServiceContainer()->get('eztags.ezpublish.persistence.legacy.language.mask_generator');

        $tagsHandler = new Handler(
            new ExceptionConversion(
                new DoctrineDatabase(
                    $this->getDatabaseHandler(),
                    $languageHandler,
                    $languageMaskGenerator
                )
            ),
            new Mapper(
                $languageHandler,
                $languageMaskGenerator
            )
        );

        return new TagsService(
            $repository,
            $tagsHandler,
            $languageHandler
        );
    }

    protected function getPostInsertStatements(): array
    {
        $statements = parent::getPostInsertStatements();

        if (self::$db === 'pgsql') {
            $setvalPath = __DIR__ . '/../../../_fixtures/schema/setval.pgsql.sql';

            return array_merge($statements, array_filter(preg_split('(;\\s*$)m', file_get_contents($setvalPath))));
        }

        return $statements;
    }

    protected function getInitialData(): array
    {
        parent::getInitialData();

        if (!isset(self::$tagsInitialData)) {
            self::$tagsInitialData = include __DIR__ . '/../../../_fixtures/tags_tree.php';
            self::$initialData = array_merge(self::$initialData, self::$tagsInitialData);
        }

        return self::$initialData;
    }

    /**
     * Returns the database schema as an array of SQL statements.
     */
    protected function getTagsSchemaStatements(): array
    {
        $tagsSchemaPath = __DIR__ . '/../../../_fixtures/schema/schema.' . self::$db . '.sql';

        return array_filter(preg_split('(;\\s*$)m', file_get_contents($tagsSchemaPath)));
    }

    protected function initializeSchema(): void
    {
        parent::initializeSchema();

        $statements = $this->getTagsSchemaStatements();
        $this->applyStatements($statements);
    }
}
