<?php


namespace App\Controller;


use Hyperf\Guzzle\ClientFactory;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Coroutine;

class CoCoController extends AbstractController
{
    /**
     * @Inject()
     * @var ClientFactory
     */
    public $clientFactory;

    public function index() {
        //$this->getData("http://www.baidu.com");
        Coroutine::create(function () {
            $this->getData("http://www.baidu.com");
            $this->getData("http://www.baidu.com");
            Coroutine::create(function () {
                $this->getData("http://www.sina.com");
                $this->getData("http://www.sina.com");
            });
        });

        return 1;
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