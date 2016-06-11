<?php
/**
 * App
 *
 * @author      Lucas Silva <dev.lucassilva@gmail.com>
 * @copyright   2016 Lucas Silva
 * @link        http://www.downsmaster.com
 * @version     1.0.0
 *
 * MIT LICENSE
 */
namespace DRouter;

class App
{
    /**
    * Requests permitidos
    *@var array
    */
    protected $format = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'OPTIONS' => []
    ];

    /**
    * Contém um conjunto de arrays identificados pelo tipo de request
    * e um array de rotas definidas
    *@var array
    */
    protected $uri;

    /**
    * Um conjunto de arrays identificados pelo tipo de request
    * e dentro de cada indice de request, um array contendo os callables
    * para cada uma
    *@var array
    */
    protected $callables;

    /**
    *Uma array associativo no formato: [req:index] = routeName
    * onde req = requestType, index = indice identificador da rota dentro do request
    * e routeName = nome da rota
    *@var array
    */
    protected $routeNames;

    /**
    * String responsavel por indicar em que request a ultima rota foi criada
    *@var string
    */
    protected $lastRoutePath;

    public function __construct()
    {  
        $this->uri = $this->format;
        $this->callables = $this->format;
        $this->routeNames = array();
    }

    /**
    * Declara as rotas em suas respectivas restrições de request
    * e com seus respectivos callables
    *@param string $path
    *@param string $request
    *@param callable $callable
    *@return \DRouter\App
    */
    public function route($path, $request, $callable)
    {
        $request = strtoupper($request);

        if (!in_array($request, array_keys($this->format))) {
            throw new \InvalidArgumentException('Request inválido');
        }

        if ($this->validCallable($callable)) {
            $this->uri[$request][] = $path;
            
            if ($callable instanceof \Closure) {
                $this->callables[$request][] = $callable->bindTo($this, __CLASS__);
            }else{
                $this->callables[$request][] = $callable;
            }
            
            $this->lastRoutePath = $request;
        } else {
            throw new \InvalidArgumentException('O callable passado é inválido!');
        }

        return $this;
    }

    /**
    * Verifica se um callable é valido o retorna caso seja
    * @param callable $callable
    * @return callable ou bolean
    */
    private function validCallable($callable)
    {   
        if (is_callable($callable)) {
            return $callable;
        } elseif (is_string($callable) && count(explode(':', $callable)) == 2){
            $exp = explode(':', $callable);
            $obj = filter_var($exp[0], FILTER_SANITIZE_STRING);
            $obj = new $obj();
            $method = filter_var($exp[1], FILTER_SANITIZE_STRING);

            if (is_callable([$obj, $method])) {
                return [$obj, $method];
            } 
        }

        return false;
    }

    /**
    * Executa um callable dentro dos padrões aceitos
    * @param callable $callable
    * @param array $params
    */
    private function executeCallable($callable, $params) {
        if ($call = $this->validCallable($callable)) {
            call_user_func_array($call, $params);
        }        
    }

    /**
    * Permite a definição de rotas através de metodos especificos tais como:
    * get, post, put, delete, options
    * @param $method string nome do metodo chamado
    * @param $args array de argumentos
    */
    public function __call($method, $args)
    {
        $metodo = trim($method);
        $requestType = strtoupper($metodo);

        if (in_array($requestType, array_keys($this->format))) {
            if (count($args) == 2) {
                $path = strip_tags(trim($args[0]));
                return $this->route($path, $requestType, $args[1]);
            }
        } else {
            throw new \InvalidArgumentException('Request inválido');
        }
    }

    /**
    * Define o nome de uma dada rota recém criada
    *@param string $routeName
    *@return void
    */
    public function setName($routeName)
    {
        $req = $this->lastRoutePath;
        $lastRoute = count($this->uri[$req])-1;
        $this->routeNames[$req.':'.$lastRoute] = $routeName;
    }

    /**
    * substitui as variaveis em uma dada rota por um padrão de expressão
    * regular para verificação interna e dispatch de rotas
    *@param string $pattern
    *@return string
    */
    private function convertPattern($pattern)
    {
        $pattern = explode('/', $pattern);
        $p = '';
        $x = 0;
        foreach($pattern as $n => $bloco){
            if (preg_match('/^[\:]/i', $bloco)) {
                $bloco = '(.*)';
            }
            $p .= $bloco;
            $n++;
            if ($n < count($pattern)) {
                $p .= '/';
            }
        }

        return $p;
    }

    /**
    * Encontra as partes "não-variaveis" de um padrão de uma dada rota
    *@param string $pattern
    *@return array
    */
    protected function getRouteNonVariables($pattern)
    {
        $patternSplit = explode('/', $pattern);
        $patternReturn = [];
        foreach ($patternSplit as $i => $padrao) {
            if ($padrao !== '(.*)') {
                $patternReturn[$i] = $padrao;
            }
        }

        return $patternReturn;
    }

    /**
    * Verifica se as partes não variaveis de um padrão são compativeis
    * com as partes não variaveis de uma rota para dispatch
    *@param string $pattern
    *@param string $rota
    *@return bolean
    */
    protected function verifyMatchingRouteString($pattern, $rota)
    {
        //separa o padrão em pedaços
        $naoVariaveis = $this->getRouteNonVariables($pattern);
        $rotaSplit = explode('/', $rota);

        $matchs = 0;
        foreach($naoVariaveis as $i => $padrao){
            if ($rotaSplit[$i] === $padrao) {
                $matchs++;
            }
        }

        if ($matchs == count($naoVariaveis)) {
            return true;
        }

        return false;
    }

    /**
    * Retorna o request atual
    *@return string
    */
    protected function getRequestType()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
    * Retorna o callable setado em uma rota, usando como referencia seu nome
    *@param string $routeName
    *@return callable ou bolean
    */
    public function getRouteCallable($routeName)
    {
        $routeIndex = array_search($routeName, $this->routeNames);
        $split = explode(':', $routeIndex); //req:index

        if ($this->callables[$split[0]][$split[1]]) {
            $routeCallable = $this->callables[$split[0]][$split[1]];
            return $routeCallable;
        }
        return false;
    }

    /**
    * Verifica e executa o dispatch das rotas criadas
    *@return void
    */
    public function run()
    {
        $rota = (!isset($_SERVER['PATH_INFO'])) ? '/' : strip_tags(trim($_SERVER['PATH_INFO']));
        $found = 0;
        $request = $this->getRequestType();
        $homeIndice = array_search('/', $this->uri[$request]);

        if ($rota == '/' && is_int($homeIndice) && $homeIndice >= 0) {
            //chamar o callable do indice acima
            if ($callable = $this->callables[$request][$homeIndice]) {
                $this->executeCallable($callable,[]);
                $found = 1;
            }
        } else {
            foreach ($this->uri[$request] as $i => $pattern) {
                if ($pattern !== '/') {
                    $pattern = $this->convertPattern($pattern);                        
                    if (count(explode('/', $pattern)) == count(explode('/', $rota))) {
                        if ($this->verifyMatchingRouteString($pattern, $rota)) {
                            if (preg_match("#$pattern#", $rota, $params)) {
                                array_shift($params);
                                
                                $this->executeCallable($this->callables[$request][$i], $params);
                                $found++;
                                break;
                            }
                        }
                    }
                }
            }
        }

        if ($found == 0) {
            echo 'Pagina não encontrada!';
        }
    }
    /*Mic drop...*/
}
