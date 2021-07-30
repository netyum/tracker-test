<?php
declare(strict_types=1);

namespace App\Middleware;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Utils\Coroutine;
use OpenTracing\Span;
use OpenTracing\Tracer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TraceMiddleware implements MiddlewareInterface
{
    use SpanStarter;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var Tracer
     */
    private $tracer;

    public function __construct(Tracer $tracer, ConfigInterface $config)
    {
        $this->tracer = $tracer;
        $this->config = $config;
    }

    /**
     * Process an incoming server request.
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $span = $this->buildSpan($request);

        defer(function () {
            try {
                Coroutine::sleep((float)$this->config->get('opentracing.send_delayed'));
                $this->tracer->flush();
            } catch (\Throwable $exception) {
            }
        });
        try {
            $response = $handler->handle($request);
        } finally {
            $span->finish();
        }

        return $response;
    }

    protected function buildSpan(ServerRequestInterface $request): Span
    {
        $uri = $request->getUri();
        $span = $this->startSpan('request');
        $span->setTag('coroutine.id', (string) Coroutine::id());
        $span->setTag('request.path', (string) $uri);
        $span->setTag('request.method', $request->getMethod());
        foreach ($request->getHeaders() as $key => $value) {
            $span->setTag('request.header.' . $key, implode(', ', $value));
        }
        return $span;
    }
}
