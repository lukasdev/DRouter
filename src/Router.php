<?php

/**
 * Router
 * Objeto responsavel por definir e despachar rotas sob uma determinada url
 *
 * @author      Lucas Silva <dev.lucassilva@gmail.com>
 * @copyright   2016 Lucas Silva
 * @link        http://www.downsmaster.com
 * @version     2.0.0
 *
 * MIT LICENSE
 */
namespace DRouter;

use DRouter\Request;
use DRouter\Route;

class Router
{
    /**
     * Array que define os metodos aceitos e guarda suas respectivas rotas
     * @var $routes array
     */
    protected $routes = array(
        'GET' => array(),
        'POST' => array(),
        'PUT' => array(),
        'DELETE' => array()
    );

    /**
     * Objeto DRouter\Request
     * @var $request Request
     */
    protected $request;

    /**
     * Objeto DRouter\Route - Rota a ser despachada
     * @var $machedRoute Route
     */
    protected $matchedRoute;

    /**
     * Prefixo do grupo de rota
     * @var $routePrefix null|string
     */
    protected $routePrefix = null;

    /**
     * Array de nomenclatura de rotas no formato [METHOD:index] = name
     * @var $routeNames array
     */
    protected $routeNames = array();

    /**
     * Ultimo metodo utilizado em uma rota
     * @var $lastRouteMethod null|string
     */
    protected $lastRouteMethod = null;

    /**
    * Rotas candidatas a serem despachadas
    * @var $candidateRoutes array
    */
    protected $candidateRoutes = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Valida os paths das rotas, retirando a ultima barra caso exista
     * para evitar conflitos no dispatch
     * @param $path string
     */
    private function validatePath($path)
    {
        $last = strlen($path)-1;
        if ($path[$last] == '/') {
            $path = substr($path, 0, -1);
        }
        return $path;
    }

    /**
     * Define uma rota sob um metodo criando sua representação no objeto Route
     * @param $method string
     * @param $pattern string
     * @param $callable callable
     * @param $conditions null|array
     */
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

    /**
     * Define o prefixo para agrupamento das rotas
     * @param $prefix string
     * @param $fnc callable Closure
     */
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

    /**
     * Define o nome de uma rota recem criada
     * @param $routeName string
     */
    public function setName($routeName)
    {
        $lastMethod = $this->lastRouteMethod;
        $lastIndex = count($this->routes[$lastMethod])-1;
        $indexName = $lastMethod.':'.$lastIndex;
        $this->routeNames[$indexName] = $routeName;

        $rota = $this->routes[$lastMethod][$lastIndex];
        $rota->setName($routeName);
        $this->routes[$lastMethod][$lastIndex] = $rota;
    }

    /**
     * Encontra uma rota pelo seu nome dentro do array de rotas
     * @param $routeName string
     * @return DRouter\Route
     */
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

    /**
     * Retorna o callable de uma rota pelo seu nome
     * @param $routeName
     * @return callable
     */
    public function getRouteCallable($routeName)
    {
        $route = $this->getRoute($routeName);
        return $route->getCallable();
    }

    /**
     * Retorna o path até uma rota nomeada, trocando seus parametros
     * caso necessário
     * @param $routeName string
     * @param $params array
     * @return string
     */
    public function pathFor($routeName, $params = array())
    {
        if ($rota = $this->getRoute($routeName)) {
            $pattern = $rota->getPattern();
            $qtdParams = count($rota->getParamNames());
            
            if ($qtdParams > 0 && count($params) == 0) {
                throw new \RuntimeException('A rota '.$routeName.' requer '.$qtdParams.' parametro(s)!');
            }

            if (count($params) > 0) {
                foreach ($params as $key => $value) {
                    $pattern = str_replace(':'.$key, $value, $pattern);
                }
            }
            return $this->request->getRoot().$pattern;
        }
    }

    /**
     * Efetua um redirecionamento para um path, passando gets opcionais
     * convertidos de array, como parametros
     * @param string $routeName
     * @param array $query
     * @param array $params
     */
    public function redirectTo($routeName, $query = array(), $params = array())
    {
        $path = $this->pathFor($routeName, $params);

        if (!is_array($query)) {
            throw new \UnexpectedValueException('Router::redirectTo A query deve ser um array!');
        }

        if (count($query) > 0) {
            $path = $path.'?'.http_build_query($query);
        }
        $path = ($path == '') ? '/' :  $path;
        header("Location: ".$path);
        die;
    }

    /**
     * Retorna array com tipos de requests aceitos pelo roteamento
     * @return array
     */
    public function getRequestAccepted()
    {
        return array_keys($this->routes);
    }

    /**
     * Pelo request method atual, navega pelas rotas definidas
     * E encontra a rota que coincidir com o padrão do RequestUri atual
     * Guardando-a no array de rotas candidatas
     * @return bolean
     */
    public function dispatch()
    {
        $requestUri = $this->validatePath($this->request->getRequestUri());

        foreach ($this->routes[$this->request->getMethod()] as $rota) {
            if ($rota->match($requestUri)) {
                $this->candidateRoutes[] = $rota;
            }
        }

        if (count($this->candidateRoutes) > 0) {
            $this->dispatchCandidateRoutes($requestUri);
            return true;
        }
        return false;
    }

    /**
    * Retorno o que não for variavel de uma pattern, exemplo: /categoria/:slug
    * Nestecaso :slug é umavariavel, e eu retornarei "categoria"
    * @param $pattern string
    */
    public function getNonVariables($pattern) {
        $exp = explode('/',$pattern);
        $retorno = [];
        foreach($exp as $i => $v) {
            if(!preg_match('/^[\:]/i', $v)) {
                $retorno[$i] = $v;
            }
        }

        return $retorno;
    }

    /**
    * Determina qual rota deve ser despachada, com base em sua similaridade com
    * a request URI,para evitar conflitos entre rotas parecidas.
    * @param $requestUri string
    */
    public function dispatchCandidateRoutes($requestUri) {
        $expUri = explode('/',$requestUri);
        $similaridades = [];

        foreach ($this->candidateRoutes as $n => $rota) {
            $padrao = $rota->getPattern();
            $naoVariaveis = $this->getNonVariables($padrao);

            foreach ($naoVariaveis as $i => $valor) {
                if(!isset($similaridades[$n]))
                    $similaridades[$n] = 0;

                if (isset($expUri[$i]) && $expUri[$i] == $valor) {
                    $similaridades[$n] += 1;
                }
            }
        }

        $bigger = max(array_values($similaridades));
        $mostSimilar = array_search($bigger, $similaridades);
        $this->matchedRoute = $this->candidateRoutes[$mostSimilar];
        $this->candidateRoutes = [];
    }

    /**
     * Retorna a rota que coincidiu com a RequestUri atual
     * @return DRouter\Route
     */
    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }

    /**
     * Executa callable da rota que coincidiu
     * passando como ultimo prametro o objeto container, caso necessário
     * @param $container DRouter\Container
     */
    public function execute(\Drouter\Container $container)
    {
        $rota = $this->getMatchedRoute();
        $callable = $rota->getCallable();
        $params = $rota->getParams();

        

        if (is_string($callable) && preg_match('/^[a-zA-Z\d\\\\]+[\:][\w\d]+$/', $callable)) {
            $exp = explode(':', $callable);

            $obj = filter_var($exp[0], FILTER_SANITIZE_STRING);
            $obj = new $obj($container);
            $method = filter_var($exp[1], FILTER_SANITIZE_STRING);

            $callable = [$obj, $method];
        }

            

        if (!empty($params)) {
            $params = array_values($params);
        }

        if (is_object($callable) || (is_string($callable) && is_callable($callable))) {
            $params[] = $container;
        }

        call_user_func_array($callable, $params);
    }
}
