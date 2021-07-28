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

class IndexController extends AbstractController
{
    /**
     * @Inject()
     * @var ClientFactory
     */
    public $clientFactory;

    public function index()
    {
        $client = $this->clientFactory->create([]);
        $response = $client->request("GET", 'http://www.baidu.com');
        $body = $response->getBody();
        $data = "";
        while (!$body->eof()) {
            $data .= $body->read(1024);
        }
        //var_dump($data);
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }
}
