<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Coroutine;
use Hyperf\Utils\Exception\ParallelExecutionException;
use Hyperf\Utils\Parallel;
use Hyperf\Redis\Redis;

class IndexController extends AbstractController
{
    /**
     * @Inject()
     * @var ClientFactory
     */
    public $clientFactory;

    /**
     * @Inject
     * @var Redis
     */
    public $redis;

    public function index()
    {
        // http
        $this->getData("http://www.baidu.com");
        $this->coGet();

        // redis
        $this->redis->set("TEST", '11111', 100);
        echo $this->redis->get("TEST"), "\n";
        $this->coRedis();
        $this->coGetRedis();

        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    public function coGetRedis() {
        $keys = ['a', 'b', 'c', 'd'];

        $wg = new \Hyperf\Utils\WaitGroup();
        foreach($keys as $key) {
            $wg->add(1);
            Coroutine::create(function () use ($wg, $key) {
                Coroutine::defer(function() use($wg) {
                    $wg->done();
                });
                $data = $this->redis->get($key);
                echo $key, " -------- ", $data, "\n";
            });
        }
        $wg->wait();
    }

    public function coRedis() {
        $keys = ['a', 'b', 'c', 'd'];

        $parallel = new Parallel(count($keys));
        foreach ($keys as $key) {
            $parallel->add(function () use ($key) {
                $ret = $this->redis->set($key, $key, 100);
                return [$key, $ret, Coroutine::id()];
            });
        }

        try{
            $results = $parallel->wait();
            foreach($results as $v) {
                echo $v[0], '--------', $v[1], " -------- ", $v[2], "\n";
            }
        } catch(ParallelExecutionException $e){
            var_dump($e->getMessage());
        }
    }

    public function coGet() {

        $urls = [
            'http://www.baidu.com',
            'http://www.baidu.com',
            'http://www.baidu.com',
            'http://www.baidu.com',
            'http://www.baidu.com',
            'http://www.baidu.com',
            'http://www.baidu.com',
        ];

        $wg = new \Hyperf\Utils\WaitGroup();
        foreach($urls as $url) {
            $wg->add(1);
            Coroutine::create(function () use ($wg, $url) {
                Coroutine::defer(function() use($wg) {
                    $wg->done();
                });
                $data = $this->getData($url);
                echo $url, " -------- ", md5($data), "\n";
            });
        }
        $wg->wait();
    }

    public function getData($url) {
        $data = "";
        try {
            $client = $this->clientFactory->create([]);
            $response = $client->request("GET", $url);
            $body = $response->getBody();
            while (!$body->eof()) {
                $data .= $body->read(1024);
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
        return $data;
    }
}
