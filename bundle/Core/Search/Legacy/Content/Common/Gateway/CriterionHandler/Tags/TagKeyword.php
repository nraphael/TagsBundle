<?php

declare(strict_types=1);

namespace Netgen\TagsBundle\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler\Tags;

use Doctrine\DBAL\Query\QueryBuilder;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Netgen\TagsBundle\API\Repository\Values\Content\Query\Criterion\TagKeyword as TagKeywordCriterion;
use Netgen\TagsBundle\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler\Tags;

final class TagKeyword extends Tags
{
    public function accept(Criterion $criterion): bool
    {
        return $criterion instanceof TagKeywordCriterion;
    }

    public function handle(CriteriaConverter $converter, QueryBuilder $queryBuilder, Criterion $criterion, array $languageSettings): string
    {
        /** @var \Netgen\TagsBundle\API\Repository\Values\Content\Query\Criterion\Value\TagKeywordValue|null $valueData */
        $valueData = $criterion->valueData;

        $subSelect = $this->connection->createQueryBuilder();
        $subSelect
            ->select('t1.id')
            ->from('ezcontentobject', 't1')
            ->innerJoin(
                't1',
                'eztags_attribute_link',
                't2',
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('t2.objectattribute_version', 't1.current_version'),
                    $queryBuilder->expr()->eq('t2.object_id', 't1.id')
                )
            )->innerJoin(
                't2',
                'eztags',
                't3',
                $queryBuilder->expr()->eq('t2.keyword_id', 't3.id')
            )->leftJoin(
                't3',
                'eztags_keyword',
                't4',
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('t3.id', 't4.keyword_id'),
                    $queryBuilder->expr()->eq('t4.status', 1)
                )
            );

        if ($valueData !== null && count($valueData->languages ?? []) > 0) {
            if ($valueData->useAlwaysAvailable) {
                $subSelect->where(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->in('t4.locale', $valueData->languages),
                        $queryBuilder->expr()->eq(
                            't3.main_language_id',
                            $this->connection->getDatabasePlatform()->getBitAndComparisonExpression(
                                't4.language_id',
                                -2 // -2 == PHP_INT_MAX << 1
                            )
                        )
                    )
                );
            } else {
                $subSelect->where(
                    $queryBuilder->expr()->in('t4.locale', $valueData->languages)
                );
            }
        }

        if ($criterion->operator === Criterion\Operator::LIKE) {
            $subSelect->where(
                $queryBuilder->expr()->like('t4.keyword', $criterion->value[0])
            );
        } else {
            $subSelect->where(
                $queryBuilder->expr()->in('t4.keyword', $criterion->value)
            );
        }

        $fieldDefinitionIds = $this->getSearchableFields($criterion->target);
        if ($fieldDefinitionIds !== null) {
            $subSelect->innerJoin(
                't2',
                'ezcontentobject_attribute',
                't5',
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('t5.id', 't2.objectattribute_id'),
                    $queryBuilder->expr()->eq('t5.version', 't2.objectattribute_version'),
                )
            );

            $subSelect->where(
                $queryBuilder->expr()->in('t5.contentclassattribute_id', $fieldDefinitionIds)
            );
        }

        return $queryBuilder->expr()->in('c.id', $subSelect->getSQL());
    }
}
