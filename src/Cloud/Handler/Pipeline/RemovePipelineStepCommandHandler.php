<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\RemovePipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Result;

final class RemovePipelineStepCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(RemovePipelineStepCommand $command): Result
    {
        $response = $this->client->removePipelineStepPipelineStepCollection(
            (new Api\Model\PipelineStepRemovePipelineStepCommandInput())
                ->setPipeline($command->pipeline)
                ->setCode($command->code),
            Api\Client::FETCH_RESPONSE
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw new \RuntimeException($response->getReasonPhrase());
        }

        return new Result($response->getStatusCode(), $response->getBody()->getContents());
    }
}
