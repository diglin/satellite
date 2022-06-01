<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter;

use Kiboko\Contract\Configurator;

final class AdapterChoice
{
    public function __construct(
        private array $adapters,
    ) {
    }

    public function __invoke(array $configuration): Configurator\SatelliteBuilderInterface
    {
        $factory = null;
        foreach ($this->adapters as $alias => $adapter) {
            if (\array_key_exists($alias, $configuration)) {
                $factory = $adapter;
                break;
            }
        }

        try {
            \assert($factory instanceof FactoryInterface);
        } catch (\AssertionError $exception) {
            throw new \RuntimeException('No compatible adapter was found for your satellite configuration.', previous: $exception);
        }

        return $factory($configuration);
    }
}
