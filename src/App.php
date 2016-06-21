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

    /**
    * Objeto container
    *@var object
    */
    public $container;
    /**
    * Objeto helper Render
    */
    public $render;
    /**
    * Guarda modificação da pagina de notFound caso exista
    * @var bolean|\Closure
    */
    protected $notFoundModified = false;
    /**
    * Array de rotas encontradas para posterior verificação de similaridades
    * com o path atual
    *@var array
    */
    protected $foundRoutes = array();
    /**
    * Guarda os parametros a serem passados para a rota identificando a mesma
    * por um index 
    *@var array
    */
    protected $paramsDispatch = array();

    /**
    * Ultimo prefixo de grupo criado
    * @var string|null
    */
    protected $routePrefix = null;

    public function __construct($params = array())
    {  
        $this->uri = $this->format;
        $this->callables = $this->format;
        $this->routeNames = array();

        $this->container = new Container($params);
        $this->render = new Render();
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
        $path = $this->validatePath($path);
        $request = strtoupper($request);

        if (!in_array($request, array_keys($this->format))) {
            throw new \InvalidArgumentException('Request inválido');
        }

        if ($this->validCallable($callable)) {
            if (!is_null($this->routePrefix)) {
                $path = $this->routePrefix.$path;
            }

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
    * Valida um path retirando sua ultima barra, caso exista!
    * @param string $path
    * @return string
    */
    private function validatePath($path){
        $last = strlen($path)-1;
        if ($path[$last] == '/') {
            $path = substr($path,0,-1);
        }

        return $path;
    }
    /**
    * Define um grupo de rotas dentro de seu closure
    * @param string $prefix - Prefixo das rotas
    * @param \Closure $fnc - Função que define as rotas
    */
    public function group($prefix, callable $fnc)
    {
        $this->routePrefix = $prefix;

        if ($fnc instanceof \Closure) {
            $fnc = $fnc->bindTo($this, __CLASS__);
            $fnc();
        } else {
            throw new \InvalidArgumentException('Callable do metodo group DEVE ser um Closure');
        }

        $this->routePrefix = null;
    }
    /**
    * Encontra o path de uma rota pelo seu nome
    * @param string $routeName
    * @param array $varSwap - Variaveis para trocar na path
    */
    public function pathFor($routeName, $varSwap = array())
    {
        $base = substr(explode('index.php', $_SERVER['SCRIPT_NAME'])[0],0,-1);

        $index = $this->getRouteIndex($routeName);
        $exp = explode(':', $index);
        $path = $this->uri[$exp[0]][$exp[1]];

        $routeVariables = $this->getRouteVariables($path);
        $expected = count($routeVariables);
        if ($expected > 0) {
            if($expected != count($varSwap)) {
                throw new \InvalidArgumentException('São experados '.$expected.' parametros para a rota');
            } else {
                $pathExp = explode('/', $path);
                $pathReturn = '';
                $arrayVariablesSwap = array_keys($varSwap);

                foreach ($pathExp as $i => $pattern) {
                    $pattern = str_replace(':', '', $pattern);
                    if (in_array($pattern, $arrayVariablesSwap)) {
                        $pathReturn .= $varSwap[$pattern];
                    } else {
                        $pathReturn .= $pattern;
                    }
                    $pathReturn .= '/';
                }

                return $base.substr($pathReturn,0,-1);
            }
        } else {
            return $base.$path;
        }
    }
    /**
    * Redireciona para uma rota pelo seu name!
    * @param string $name
    */
    public function redirectTo($name)
    {
        header("Location: ".$this->pathFor($name));
    }

    /**
    * Retorna o corpo do request como um array
    * @return array|void
    */
    public function getParsedBody()
    {
        if (!in_array($this->getRequestType(), ['GET', 'POST'])) {
            if ($this->getContentType() == 'application/x-www-form-urlencoded') {
                $input_contents = file_get_contents("php://input");
                parse_str($input_contents,$post_vars);

                return $post_vars;
            } else {
                throw new \UnexpectedValueException('Content-type não aceito');
            }
        } elseif ($this->getRequestType() == 'POST') {
            return $_POST;
        } elseif ($this->getRequestType() == 'GET') {
            return $_GET;
        }
    }

    /*
    * Retorna o objeto container
    * @return \DRouter\Container;
    */
    public function getContainer()
    {
        return $this->container;
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
            $obj = new $obj($this);
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
            if (is_object($call) || (is_string($call) && function_exists($call))) {
                $params[] = $this;
            }
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
    * Retorna uma dependencia injetada no container
    */
    public function __get($key){
        if ($this->container->{$key}) {
            return $this->container->{$key};
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

    protected function getRouteIndex($routeName)
    {
        $index = array_search($routeName, $this->routeNames);
        if ($index == false) {
            throw new \Exception('A rota '.$routeName.' não existe!');
        }

        return $index;
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

    protected function getRouteVariables($path){
        $exp = explode('/', $path);
        $return = [];
        foreach ($exp as $i => $pattern) {
            if (preg_match('/^[\:]/i', $pattern)) {
                $return[$i] = str_replace(':', '', $pattern);
            }
        }

        return $return;
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
    * Exibe o content Type atual do request
    * @return string
    */
    protected function getContentType()
    {
        return (isset($_SERVER['CONTENT_TYPE'])) ? $_SERVER['CONTENT_TYPE'] : null;
    }

    /**
    * Retorna o callable setado em uma rota, usando como referencia seu nome
    *@param string $routeName
    *@return callable ou bolean
    */
    public function getRouteCallable($routeName)
    {
        $routeIndex = $this->getRouteIndex($routeName);
        if (empty($routeIndex)) {
            throw new \Exception('Nome da rota não encontrado!');
        }

        $split = explode(':', $routeIndex); //req:index  
        if ($this->callables[$split[0]][$split[1]]) {
            $routeCallable = $this->callables[$split[0]][$split[1]];
            return $routeCallable;
        }
        return false;
    }

    /**
    * Define a pagina notFound usando um closure
    */
    public function notFound(){
        $args = func_get_args();

        if(count($args) == 1 && is_callable($args[0])) {
            $fnc = $args[0];

            if ($fnc instanceof \Closure) {           
                $this->notFoundModified = $fnc;
            }
        }
    }
    /**
    * Dado um array com um conjunto de rotas encontradas para um dado path
    * executa uma comparação entre rotas com maior similaridade para ser executada
    * e retorna seu indice
    *@param array $arrayRoutes
    *@param string $currentPath
    *@return bolean|int
    */
    public function findBySimilarity($arrayRoutes, $currentPath){
        if (count($arrayRoutes) == 0){
            return false;
        } elseif (count($arrayRoutes) == 1) {
            foreach ($arrayRoutes as $i => $path) {
                return $i;
                break;
            }
        }

        $currentPath = explode('/', $currentPath);
        $similarities = $arrayRoutes;

        foreach ($arrayRoutes as $index => $path) {
            $breakRoute = explode('/', $path);
            $nSimilarities = 0;
            foreach ($breakRoute as $i => $val) {
                if($val == $currentPath[$i])
                    $nSimilarities++;
            }

            $similarities[$index] = $nSimilarities;
        }
        $bigger = max(array_values($similarities));
        $mostSimilar = array_search($bigger, $similarities);
        
        return $mostSimilar;
    }

    /**
    * Verifica e executa o dispatch das rotas criadas
    *@return void
    */
    public function run()
    {
        if (isset($_SERVER['ORIG_PATH_INFO'])) {
            $pathInfo = $_SERVER['ORIG_PATH_INFO'];
        } elseif (isset($_SERVER['PATH_INFO'])) {
            $pathInfo = $_SERVER['PATH_INFO'];
        }

        $rota = (!isset($pathInfo)) ? '/' : strip_tags(trim($pathInfo));
        $rota = $this->validatePath($rota);

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
                                
                                $found++;
                                $this->paramsDispatch[$i] = $params;
                                $this->foundRoutes[$i] = $pattern;
                            }
                        }
                    }
                }
            }
            $dispatch = $this->findBySimilarity($this->foundRoutes, $rota);
            if ($dispatch !== false) {
                $parametros = $this->paramsDispatch[$dispatch];
                $this->executeCallable($this->callables[$request][$dispatch], $parametros);
            }
        }

        if ($found == 0) {
            if ($this->notFoundModified) {
                $fnc = $this->notFoundModified->bindTo($this, __CLASS__);
                $this->executeCallable($fnc, []);                
            } else {
                $this->render->renderNotFoundPage();
            }
        }

    }
    /*Mic drop...*/
}
