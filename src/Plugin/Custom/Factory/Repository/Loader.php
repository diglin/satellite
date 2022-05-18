<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Factory\Repository;

use Kiboko\Component\Satellite\Plugin\Custom;
use Kiboko\Contract\Configurator;

final class Loader implements Configurator\StepRepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private Custom\Builder\Loader $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): Custom\Builder\Loader
    {
        return $this->builder;
    }

    public function merge(Configurator\RepositoryInterface $friend): self
    {
        array_push($this->files, ...$friend->getFiles());
        array_push($this->packages, ...$friend->getPackages());

        return $this;
    }
}
