<?php

namespace MartynBiz\Slim3Controller\Test\PHPUnit;

use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\RequestBody;
use Slim\Http\Uri;

// use MartynBiz\Slim3Controller\App;
// use MartynBiz\Slim3Controller\Http\Request;
// use MartynBiz\Slim3Controller\Http\Response;

use Symfony\Component\DomCrawler\Crawler;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MartynBiz\Slim3Controller\App
     */
    protected $app;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var Headers
     */
    protected $headers;

    /**
     * Perform get dispatch
     */
    public function get($path)
    {
        return $this->dispatch($path, 'GET');
    }

    /**
     * Perform post dispatch
     */
    public function post($path, $data=array())
    {
        return $this->dispatch($path, 'POST', $data);
    }

    /**
     * Perform put dispatch
     */
    public function put($path, $data=array())
    {
        // simulate a PUT by using POST with _METHOD=PUT
        $data = array_merge($data, array(
            '_METHOD' => 'PUT',
        ));

        return $this->post($path, $data);
    }

    /**
     * Perform delete dispatch
     */
    public function delete($path, $data=array())
    {
        // simulate a PUT by using POST with _METHOD=PUT
        $data = array_merge($data, array(
            '_METHOD' => 'DELETE',
        ));

        return $this->post($path, $data);
    }

    protected function dispatch($path, $method='GET', $data=array())
    {
        // Prepare a mock environment
        $env = Environment::mock(array(
            'REQUEST_URI' => $path,
            'REQUEST_METHOD' => $method,
        ));

        // Prepare request and response objects
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();

        $container = $this->app->getContainer();

        // create request, and set params
        $req = new $container['request']($method, $uri, $headers, $cookies, $serverParams, $body);
        if (!empty($data))
            $req = $req->withParsedBody($data);

        $res = new $container['response']();

        $this->headers = $headers;
        $this->request = $req;
        $this->response = call_user_func_array($this->app, array($req, $res));
    }

    public function assertController($controllerName)
    {
        if (!method_exists($this->response, 'getControllerName')) {
            throw new \Exception('getControllerName not found, please use \MartynBiz\Slim3Controller\Http\Response in your app.');
        }

        $this->assertEquals($controllerName, $this->response->getControllerName());
    }

    public function assertAction($actionName)
    {
        if (!method_exists($this->response, 'getActionName')) {
            throw new \Exception('getActionName not found, please use \MartynBiz\Slim3Controller\Http\Response in your app.');
        }

        $this->assertEquals($actionName, $this->response->getActionName());
    }

    public function assertStatusCode($statusCode)
    {
        $this->assertEquals($statusCode, $this->response->getStatusCode());
    }

    public function assertRedirects()
    {
        $this->assertTrue($this->response->isRedirect());
    }

    public function assertRedirectsTo($path)
    {
        return $this->assertEquals($path, $this->response->getHeaderLine('Location'));
    }

    public function assertQuery($query)
    {
        // TODO getBody is not returning anything :(
        $html = (string)$this->response->getBody();

        $crawler = new Crawler($html);
        $this->assertTrue($crawler->filter($query)->count() > 0);
    }

    public function assertQueryCount($query, $count)
    {
        $html = (string)$this->response->getBody();

        $crawler = new Crawler($html);
        $this->assertEquals($count, $crawler->filter($query)->count());
    }
}
