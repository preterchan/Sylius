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

namespace Sylius\Bundle\CoreBundle\Fixture\Factory;

use Faker\Factory;
use Faker\Generator;
use Sylius\Component\Core\Formatter\StringInflector;
use Sylius\Component\Customer\Model\CustomerGroupInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @implements ExampleFactoryInterface<CustomerGroupInterface> */
class CustomerGroupExampleFactory extends AbstractExampleFactory implements ExampleFactoryInterface
{
    protected Generator $faker;

    protected OptionsResolver $optionsResolver;

    /** @param FactoryInterface<CustomerGroupInterface> $customerGroupFactory */
    public function __construct(protected readonly FactoryInterface $customerGroupFactory)
    {
        $this->faker = Factory::create();
        $this->optionsResolver = new OptionsResolver();

        $this->configureOptions($this->optionsResolver);
    }

    public function create(array $options = []): CustomerGroupInterface
    {
        $options = $this->optionsResolver->resolve($options);

        /** @var CustomerGroupInterface $customerGroup */
        $customerGroup = $this->customerGroupFactory->createNew();
        $customerGroup->setCode($options['code']);
        $customerGroup->setName($options['name']);

        return $customerGroup;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('name', function (): string {
                /** @var string $words */
                $words = $this->faker->words(3, true);

                return $words;
            })
            ->setDefault('code', fn (Options $options): string => StringInflector::nameToCode($options['name']))
        ;
    }
}
