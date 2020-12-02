<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Runtime\Http;

use Kiboko\Component\ETL\Satellite\Runtime\RuntimeInterface;
use Kiboko\Component\ETL\Satellite\SatelliteInterface;
use PhpParser\Node;

final class Api implements RuntimeInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function prepare(SatelliteInterface $satellite): void
    {
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
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('FastRoute'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Nyholm\\Psr7'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Nyholm\\Psr7Server'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Psr'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Laminas\\HttpHandlerRunner\\Emitter\\SapiEmitter'))]),

            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('psr17Factory'),
                    new Node\Expr\New_(new Node\Name('Psr7\\Factory\\Psr17Factory')),
                ),
            ),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('creator'),
                    new Node\Expr\New_(
                        new Node\Name('Psr7Server\\ServerRequestCreator'),
                        [
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                        ]
                    ),
                ),
            ),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('request'),
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable('creator'),
                        'fromGlobals',
                    ),
                ),
            ),

            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('fastRouteDispatcher'),
                    new Node\Expr\FuncCall(
                        new Node\Name('FastRoute\\simpleDispatcher'),
                        [
                            new Node\Arg(
                                new Node\Expr\Closure([
                                    'params' => [
                                        new Node\Param(
                                             new Node\Expr\Variable('router'),
                                            null,
                                            new Node\Name('FastRoute\\RouteCollector')
                                        )
                                    ],
                                    'uses' => [
                                        new Node\Expr\Variable('psr17Factory')
                                    ],
                                    'stmts' => iterator_to_array($this->compileRoutes(
                                        new Node\Expr\Variable('router'),
                                        new Node\Expr\Variable('psr17Factory'),
                                    )),
                                ])
                            ),
                        ]
                    )
                ),
            ),

            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('dispatcher'),
                    new Node\Expr\New_(
                        new Node\Name('Middlewares\\Utils\\Dispatcher'),
                        [
                            new Node\Arg(
                                new Node\Expr\Array_(
                                    [
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('Middlewares\\Uuid'),
                                            ),
                                        ),
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name(' Middlewares\\BasePath'),
                                                [
                                                    new Node\Arg(
                                                        new Node\Scalar\String_($this->config['path'] ?? '/')
                                                    )
                                                ],
                                            ),
                                        ),
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('Middlewares\\FastRoute'),
                                                [
                                                    new Node\Arg(
                                                        new Node\Expr\Variable('fastRouteDispatcher')
                                                    )
                                                ]
                                            ),
                                        ),
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('Middlewares\\RequestHandler'),
                                            ),
                                        ),
                                    ],
                                    [
                                        'kind' => Node\Expr\Array_::KIND_SHORT
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            ),

            new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    new Node\Expr\New_(
                        new Node\Name('SapiEmitter')
                    ),
                    'emit',
                    [
                        new Node\Arg(
                            new Node\Expr\MethodCall(
                                new Node\Expr\Variable('dispatcher'),
                                'dispatch',
                                [
                                    new Node\Arg(
                                        new Node\Expr\Variable('request')
                                    )
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        ];
    }

    private function routeToAST(array $routeConfig, Node\Expr\Variable $router, Node\Expr\Variable $factory): Node\Stmt\Expression
    {
        return new Node\Stmt\Expression(
            new Node\Expr\MethodCall(
                $router,
                $routeConfig['method'] ?? 'get',
                [
                    new Node\Arg(
                        new Node\Scalar\String_($routeConfig['path'])
                    ),
                    new Node\Arg(
                        new Node\Expr\Include_(
                            new Node\Scalar\String_($routeConfig['function']),
                            Node\Expr\Include_::TYPE_REQUIRE,
                        )
                    ),
                ],
            ),
        );
    }

    private function compileRoutes(Node\Expr\Variable $router, Node\Expr\Variable $factory): \Iterator
    {
        foreach ($this->config['routes'] as $route) {
            yield $this->routeToAST($route, $router, $factory);
        }
    }
}
