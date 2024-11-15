<?php

namespace Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Form\ProductType;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\AdvancedSearchFilterInterface;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\Exception\AdvancedSearchFilterOperatorNotFoundException;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem;

class OrderProductFilter implements AdvancedSearchFilterInterface
{
    public const NAME = 'orderProduct';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedOperators()
    {
        return [
            self::OPERATOR_CONTAINS,
            self::OPERATOR_NOT_CONTAINS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getValueFormType()
    {
        return ProductType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueFormOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function extendQueryBuilder(QueryBuilder $queryBuilder, $rulesData)
    {
        foreach ($rulesData as $index => $ruleData) {
            if (
                $ruleData->operator === self::OPERATOR_CONTAINS
                || $ruleData->operator === self::OPERATOR_NOT_CONTAINS
            ) {
                /** @var \Shopsys\FrameworkBundle\Model\Product\Product|null $searchValue */
                $searchValue = $ruleData->value;

                if ($searchValue === null) {
                    continue;
                }
                $dqlOperator = $this->getContainsDqlOperator($ruleData->operator);
                $parameterName = 'orderProduct_' . $index;
                $tableAlias = 'oi_' . $index;
                $queryBuilder->andWhere(
                    $dqlOperator . ' (SELECT 1 FROM ' . OrderItem::class . ' ' . $tableAlias . ' ' .
                        'WHERE ' . $tableAlias . '.order = o AND ' . $tableAlias . '.product = :' . $parameterName . ')'
                );
                $queryBuilder->setParameter($parameterName, $searchValue);
            }
        }
    }

    /**
     * @param string $operator
     * @return string
     */
    protected function getContainsDqlOperator($operator)
    {
        switch ($operator) {
            case self::OPERATOR_CONTAINS:
                return 'EXISTS';
            case self::OPERATOR_NOT_CONTAINS:
                return 'NOT EXISTS';
        }

        throw new AdvancedSearchFilterOperatorNotFoundException($operator);
    }
}
