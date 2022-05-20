<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Stream\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class StdoutLoader implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function __construct()
    {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
    }

    public function withLogger(Node\Expr $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function withRejection(Node\Expr $rejection): self
    {
        $this->rejection = $rejection;

        return $this;
    }

    public function withState(Node\Expr $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\Loader\\StdoutLoader'),
        );
    }
}
