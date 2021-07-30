# hyperf-tracer-test

## tracer 测试 修改协程支持

## 修改 

#### 修改 .env 增加
```shell
# tracer 发送延时 ，主要用户是协程 中再调协程，一次请求 tracer收集不完全。 
TRACER_SEND_DELAYED=3
```

#### 修改  vendor/hyperf/tracer/src/SpanStarter.php
```php
    protected function startSpan(
        string $name,
        array $option = [],
        string $kind = SPAN_KIND_RPC_SERVER
    ): Span {
        if (Coroutine::inCoroutine()) { // 协程内
            $root = Context::get('tracer.root'); //本协程内取tracer
            if (!$root instanceof Span) { //如果没有，去父协程内取
                $root = Context::get('tracer.root', null, Coroutine::parentId());
                if ($root instanceof Span) { //如果有， 放入本协程
                    // 这里主要是 协程 内 调 协程  是无序的
                    Context::set('tracer.root', $root);
                }
            }
        } else {
            $root = Context::get('tracer.root');
        }
```

#### 修改  vendor/hyperf/tracer/src/SwitchManager.php
```php
    public function isEnable(string $identifier): bool
    {
        if (! isset($this->config[$identifier])) {
            return false;
        }
        if (Coroutine::inCoroutine()) {
            $tracerRoot = Context::get('tracer.root');
            if (!$tracerRoot instanceof Span) {
                $tracerRoot = Context::get('tracer.root', null, Coroutine::parentId());
            }
        } else {
            $tracerRoot = Context::get('tracer.root');
        }
        return $this->config[$identifier] && $tracerRoot instanceof Span;
    }
```

#### 修改 middlewares.php
直接使用
```php
\app\Middleware\TraceMiddleware::class,
```
修改自
```php
\Hyperf\Tracer\Middleware\TraceMiddleware::class,
```
在defer中，加了sleep做延时
```php
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
```

#### 测试

```shell
协程
http://localhost:9501/co

协程 - 协程
http://localhost:9501/coco
```