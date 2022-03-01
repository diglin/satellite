<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Pipeline;

use Gyroscops\Api\Model\PipelineStepAddBeforePipelineStepCommandInputJsonld;
use Kiboko\Component\Satellite\Cloud\Command\Command;

final class AddBeforePipelineStepCommand implements Command
{
    public function __construct(
        public string $pipeline,
        public string $next,
        public string $code,
        public string $label,
        public array $configuration,
        public array $probes
    )
    {}
}
