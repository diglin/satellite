<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Runtime;

use Kiboko\Component\ETL\Config\ArrayBuilder;
use Kiboko\Component\ETL\FastMap;
use Kiboko\Component\ETL\Metadata\ClassReferenceMetadata;
use Kiboko\Component\ETL\Satellite\SatelliteInterface;
use PhpParser\Node;

final class Pipeline implements RuntimeInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function prepare(SatelliteInterface $satellite): void
    {
    }

    private function buildPipeline(array $config): array
    {
        $pipeline = new Node\Expr\New_(
            new Node\Name('Pipeline\\Pipeline'),
            [
                new Node\Arg(
                    new Node\Expr\New_(
                        new Node\Name('Pipeline\\PipelineRunner'),
                    ),
                ),
            ],
        );

        foreach ($config as $step) {
            if (isset($step['extract'])) {
                $pipeline = $this->buildPipelineExtractor($pipeline, $step);
            } else if (isset($step['transform'])) {
                $pipeline = $this->buildPipelineTransformer($pipeline, $step);
            } else if (isset($step['load'])) {
                $pipeline = $this->buildPipelineLoader($pipeline, $step);
            }
        }

        return [
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('pipeline'),
                    $pipeline
                ),
            ),

            new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable('pipeline'),
                    'run'
                )
            )
        ];
    }

    private function buildPipelineExtractor(Node\Expr $pipeline, array $config): Node\Expr
    {
        return new Node\Expr\MethodCall(
            $pipeline,
            'extract',
            [
                new Node\Arg(
                    new Node\Expr\New_(
                        new Node\Name\FullyQualified($config['extract'])
                    )
                )
            ]
        );
    }

    private function buildPipelineTransformer(Node\Expr $pipeline, array $config): Node\Expr
    {
        $compiler = new FastMap\Compiler\Strategy\Spaghetti();
        if (isset($config['array'])) {
            $mapper = $this->buildArrayMapper($compiler, $config['array']);
        } else if (isset($config['object'])) {
            $mapper = $this->buildObjectMapper($compiler, $config['object']);
        }

        return new Node\Expr\MethodCall(
            $pipeline,
            'transform',
            [
                new Node\Arg(
                    new Node\Expr\New_(
                        new Node\Name\FullyQualified($config['transform'])
                    )
                )
            ]
        );
    }

    private function buildPipelineLoader(Node\Expr $pipeline, array $config): Node\Expr
    {
        return new Node\Expr\MethodCall(
            $pipeline,
            'load',
            [
                new Node\Arg(
                    new Node\Expr\New_(
                        new Node\Name\FullyQualified($config['load'])
                    )
                )
            ]
        );
    }

    private function buildArrayMapper(FastMap\Compiler\Strategy\StrategyInterface $compiler, array $config): array
    {
        $builder = new ArrayBuilder();
        $node = $builder->children();
        foreach ($config as $fieldMapping) {
            if (isset($fieldMapping['copy'])) {
                $node->copy($fieldMapping['field'], $fieldMapping['copy']);
            } else if (isset($fieldMapping['expression'])) {
                $node->expression($fieldMapping['field'], $fieldMapping['expression']);
            } else if (isset($fieldMapping['constant'])) {
                $node->constant($fieldMapping['field'], $fieldMapping['constant']);
            }
        }
        $node = $node->end();

        return $compiler->buildTree(
            new FastMap\PropertyAccess\EmptyPropertyPath(),
            new ClassReferenceMetadata('Lorem', 'Ipsum'),
            $node->getMapper()
        );
    }

    public function build(): array
    {
        return [
            new Node\Stmt\Namespace_(new Node\Name('Foo')),
            new Node\Stmt\Expression(
                new Node\Expr\Include_(
                    new Node\Expr\BinaryOp\Concat(
                        new Node\Scalar\MagicConst\Dir(),
                        new Node\Scalar\String_('/vendor/autoload.php')
                    ),
                    Node\Expr\Include_::TYPE_REQUIRE
                ),
            ),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Kiboko\\Component\\ETL\\Pipeline'))]),

            ...$this->buildPipeline($this->config['steps'])
        ];
    }
}
