<?php
/**
 * Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\PHPPM\Bridges;

use App\Application;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Response;
use Cake\Http\Runner;
use Cake\Http\Server;
use Cake\Http\ServerRequestFactory;
use PHPPM\Bridges\BridgeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Cakephp implements BridgeInterface
{

    /**
     * @var Server
     */
    protected $server;

    /**
     * @var string root path
     */
    protected $root;

    public function __construct()
    {
        $this->root = dirname(__DIR__, 5);
    }

    /**
     * Bootstrap an application
     *
     * @param string|null $appBootstrap The environment your application will use to bootstrap (if any)
     * @param string $appenv
     * @param bool $debug If debug is enabled
     */
    public function bootstrap($appBootstrap, $appenv, $debug)
    {
        require $this->root . '/config/requirements.php';
        require $this->root . '/vendor/autoload.php';
        $this->application = new Application($this->root . '/config');
        $this->application->bootstrap();
        $this->server = new Server($this->application);
    }

    /**
     * Handle the request and return a response.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request)
    {
        $response = new Response();
        $request = ServerRequestFactory::fromGlobals();

        $middleware = $this->application->middleware(new MiddlewareQueue());
        if (!($middleware instanceof MiddlewareQueue)) {
            throw new \RuntimeException('The application `middleware` method did not return a middleware queue.');
        }
        $this->server->dispatchEvent('Server.buildMiddleware', ['middleware' => $middleware]);
        $middleware->add($this->application);
        $runner = new Runner();
        $response = $runner->run($middleware, $request, $response);

        if (!($response instanceof ResponseInterface)) {
            throw new \RuntimeException(sprintf(
                'Application did not create a response. Got "%s" instead.',
                is_object($response) ? get_class($response) : $response
            ));
        }

        return $response;
    }
}
