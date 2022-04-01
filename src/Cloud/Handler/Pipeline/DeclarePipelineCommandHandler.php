<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class DeclarePipelineCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\DeclarePipelineCommand $command): Cloud\Event\PipelineDeclared
    {
        $result = $this->client->declarePipelinePipelineCollection(
            (new Api\Model\PipelineDeclarePipelineCommandInput())
                ->setLabel($command->label)
                ->setCode($command->code)
                ->setProject((string) $command->project)
                ->setOrganization((string) $command->organizationId)
                ->setSteps(array_map(
                    fn (Cloud\DTO\Step $step) =>
                        (new Api\Model\StepInput())
                            ->setCode((string) $step->code)
                            ->setLabel($step->label)
                            ->setConfig($step->config)
                            ->setProbes(array_map(
                                fn (Cloud\DTO\Probe $probe) =>
                                    (new Api\Model\Probe())
                                        ->setCode($probe->code)
                                        ->setLabel($probe->label),
                                $step->probes->toArray()
                            )),
                    $command->steps->toArray()
                ))
                ->setAutoloads(array_map(
                    fn (Cloud\DTO\PSR4AutoloadConfig $autoloadConfig) =>
                        (new Api\Model\AutoloadInput())
                            ->setNamespace($autoloadConfig->namespace)
                            ->setPaths($autoloadConfig->paths),
                    $command->autoload->autoloads
                )),
        );

        if ($result === null) {
            throw throw new \RuntimeException('Something went wrong wile declaring the pipeline.');
        }

        return new Cloud\Event\PipelineDeclared($result->id);
    }
}
