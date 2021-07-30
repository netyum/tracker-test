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
use Hyperf\HttpServer\Router\Router;

// 协程
Router::addRoute(['GET', 'POST', 'HEAD'], '/co', 'App\Controller\CoController@index');
// 协程 - 协程
Router::addRoute(['GET', 'POST', 'HEAD'], '/coco', 'App\Controller\CoCoController@index');


Router::get('/favicon.ico', function () {
    return '';
});
