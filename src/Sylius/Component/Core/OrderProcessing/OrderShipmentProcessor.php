<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Component\Core\OrderProcessing;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Shipping\Exception\UnresolvedDefaultShippingMethodException;
use Sylius\Component\Shipping\Resolver\DefaultShippingMethodResolverInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class OrderShipmentProcessor implements OrderProcessorInterface
{
    /** @param FactoryInterface<ShipmentInterface> $shipmentFactory */
    public function __construct(
        private DefaultShippingMethodResolverInterface $defaultShippingMethodResolver,
        private FactoryInterface $shipmentFactory,
        private ShippingMethodsResolverInterface $shippingMethodsResolver,
    ) {
    }

    public function process(BaseOrderInterface $order): void
    {
        /** @var OrderInterface $order */
        Assert::isInstanceOf($order, OrderInterface::class);

        if (!$order->canBeProcessed()) {
            return;
        }

        if ($order->isEmpty() || !$order->isShippingRequired()) {
            $order->removeShipments();

            return;
        }

        if ($order->hasShipments()) {
            $this->recalculateExistingShipmentWithProperMethod($order);

            return;
        }

        $this->createNewOrderShipment($order);
    }

    private function createNewOrderShipment(OrderInterface $order): void
    {
        /** @var ShipmentInterface $shipment */
        $shipment = $this->shipmentFactory->createNew();
        $shipment->setOrder($order);

        try {
            $this->processShipmentUnits($order, $shipment);

            $shipment->setMethod($this->defaultShippingMethodResolver->getDefaultShippingMethod($shipment));

            $order->addShipment($shipment);
        } catch (UnresolvedDefaultShippingMethodException) {
            foreach ($shipment->getUnits() as $unit) {
                $shipment->removeUnit($unit);
            }
        }
    }

    private function processShipmentUnits(BaseOrderInterface $order, ShipmentInterface $shipment): void
    {
        foreach ($shipment->getUnits() as $unit) {
            $shipment->removeUnit($unit);
        }

        /** @var OrderInterface $order */
        Assert::isInstanceOf($order, OrderInterface::class);

        foreach ($order->getItemUnits() as $itemUnit) {
            /** @var ProductVariantInterface $shippable */
            $shippable = $itemUnit->getShippable();

            if (null === $itemUnit->getShipment() && $shippable->isShippingRequired()) {
                $shipment->addUnit($itemUnit);
            }
        }
    }

    private function recalculateExistingShipmentWithProperMethod(OrderInterface $order): void
    {
        /** @var ShipmentInterface $shipment */
        $shipment = $order->getShipments()->first();

        $this->processShipmentUnits($order, $shipment);

        if (!in_array($shipment->getMethod(), $this->shippingMethodsResolver->getSupportedMethods($shipment), true)) {
            try {
                $shipment->setMethod($this->defaultShippingMethodResolver->getDefaultShippingMethod($shipment));
            } catch (UnresolvedDefaultShippingMethodException) {
                return;
            }
        }
    }
}
