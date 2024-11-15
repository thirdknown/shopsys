<?php

declare(strict_types=1);

namespace App\Model\Order;

use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderDataFactory as BaseOrderDataFactory;

/**
 * @method \App\Model\Order\OrderData create()
 * @method \App\Model\Order\OrderData createFromOrder(\App\Model\Order\Order $order)
 * @property \App\Model\Order\Item\OrderItemDataFactory $orderItemDataFactory
 * @method __construct(\App\Model\Order\Item\OrderItemDataFactory $orderItemDataFactory)
 * @method fillFromOrder(\App\Model\Order\OrderData $orderData, \App\Model\Order\Order $order)
 */
class OrderDataFactory extends BaseOrderDataFactory
{
    /**
     * @return \App\Model\Order\OrderData
     */
    protected function createInstance(): BaseOrderData
    {
        return new OrderData();
    }
}
