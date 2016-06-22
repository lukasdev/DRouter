<?php
namespace DRouter;
use DRouter\Request;
use DRouter\Route;

class Router
{
    protected $routes = array(
        'GET' => array(),
        'POST' => array(),
        'PUT' => array(),
        'DELETE' => array()
    );

    protected $request;
    protected $matchedRoute;
    protected $routePrefix = null;
    protected $routeNames = array();
    protected $lastRouteMethod = null;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    private function validatePath($path){
        $last = strlen($path)-1;
        if ($path[$last] == '/') {
            $path = substr($path,0,-1);
        }
        return $path;
    }

    public function route($method, $pattern, $callable, $conditions)
    {
        $method = strtoupper($method);
        $pattern = $this->validatePath($pattern);
        if (!is_null($this->routePrefix)) {
            $pattern = $this->routePrefix.$pattern;
        }

        $this->routes[$method][] = new Route($pattern, $callable, $conditions);
        $this->lastRouteMethod = $method;
        return $this;
    }

    public function group($prefix, $fnc)
    {
        $this->routePrefix = $prefix;
        if ($fnc instanceof \Closure) {
            $fnc();
        } else {
            throw new \InvalidArgumentException('Callable do metodo group DEVE ser um Closure');
        }
        $this->routePrefix = null;
    }

    public function setName($routeName)
    {
        $lastMethod = $this->lastRouteMethod;
        $lastIndex = count($this->routes[$lastMethod])-1;
        $indexName = $lastMethod.':'.$lastIndex;
        $this->routeNames[$indexName] = $routeName;
    }

    protected function getRoute($routeName)
    {
        $routeIndex = array_search($routeName, $this->routeNames);
        if ($routeIndex == false) {
            throw new \RuntimeException('Rota '.$routeName.' não encontada');
        } else {
            $split = explode(':', $routeIndex);
            $rota = $this->routes[$split[0]][$split[1]];

            return $rota;
        }
    }

    public function getRouteCallable($routeName)
    {
        $route = $this->getRoute($routeName);
        return $route->getCallable();
    }

    public function pathFor($routeName, $params = array())
    {
        if ($rota = $this->getRoute($routeName)){
            $pattern = $rota->getPattern();

            if (count($params) > 0) {
                foreach ( $params as $key => $value ) {
                    $pattern = str_replace(':'.$key, $value, $pattern);
                }
            }
            return $this->request->getRoot().$pattern;
        }
    }

    public function getRequestAccepted()
    {
        return array_keys($this->routes);
    }

    public function dispatch()
    {
        foreach ($this->routes[$this->request->getMethod()] as $rota) {
            $requestUri = $this->validatePath($this->request->getRequestUri());
            
            if ($rota->match($requestUri)) {
                $this->matchedRoute = $rota;
                return true;
                break;
            }
        }
    }

    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }

    public function execute(\Drouter\Container $container) {
        $rota = $this->getMatchedRoute();
        $callable = $rota->getCallable();
        $params = $rota->getParams();

        if(!empty($params)) {
            $params = array_values($params);
        }

        if (is_object($callable) || (is_string($callable) && is_callable($callable))) {
            $params[] = $container;
        }

        call_user_func_array($callable, $params);
    }
}