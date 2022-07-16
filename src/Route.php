<?php

/**
 * Route
 * Representação de uma rota armazenada no array de rotas do objeto Router
 *
 * @author      Lucas Silva <dev.lucassilva@gmail.com>
 * @copyright   2016 Lucas Silva
 * @link        http://www.downsmaster.com
 * @version     2.0.0
 *
 * MIT LICENSE
 */

namespace DRouter;

class Route
{
    /**
     * String com o nome da rota em questão para uso futuro na aplicação
     * @var $name string
     */
    protected $name;
    /**
     * String com o padrão da rota, exemplo /user/:id
     * @var $pattern string
     */
    protected $pattern;

    /**
     * Callable a ser executado na rota. Pode ser [obj, method], 'fnc_name' ou obj
     * @var $callable callable|string|object
     */
    protected $callable;

    /**
     * Array com condições para parametros desta rota, exemplo
     * ['id' => '[\d]{1,8}'] para que o parametro :id seja um digito até 8
     * caracteres
     * @var $conditions array
     */
    protected $conditions = array();

    /**
     * Array associativo com os parametros desta rota, exemplo
     * ['id' => '5', 'tipo' => 8]
     * @var $params array
     */
    protected $params = array();

    /**
     * Array contendo todas as middleawres a serem executadas antes desta
     * rota
     * @var $middlewares array
     */
    private $middlewares = [];

    /**
     * Propriedade contendo caso exista, o prefixo de group dessa rota
     * @var null | string
     */
    private $groupPrefix = null;

    private $options = [];

    public function __construct($pattern, $callable, array $conditions)
    {
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->conditions = $conditions;
    }

    /**
     * Seta o prefixo de group da rota
     */
    public function setGroupPrefix($prefix)
    {
        $this->groupPrefix = $prefix;
    }

    /**
     * Retorna o prefixo de group da rota
     */
    public function getGroupPrefix()
    {
        return $this->groupPrefix;
    }

    /**
     * Adiciona um determinado middleware ao array de middlewares
     */
    public function addMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Adiciona um conjunto de middlewares ao array de middlewares
     */
    public function addMiddlewares(array $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /**
     * Retorna o array de middlewares
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * Configura o nome da rota em questão
     * @var $name string
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Retorna o nome da rota em questão
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Retorna o callable da rota atual
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * Retorna os parametros encontrados desta rota
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Retorna os padrão desta rota
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    public function getParamNames()
    {
        preg_match_all('@:([\w]+)@', $this->pattern, $paramNames, PREG_PATTERN_ORDER);
        return $paramNames[0];
    }


    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Verifica se o padrão da rota coincide com o padrão escrito na
     * url da aplicação, e guarda os parametros encontrados sob suas
     * respectivas - caso existentes - restrições
     * @param $resourceUri
     * @return bolean
     */
    public function match($resourceUri)
    {
        $paramNames = $this->getParamNames();

        $pattern =  $this->getPattern();

        if (preg_match('/\[:options\]/', $pattern)) {
            $exp = explode('[:options]', $pattern);
            $estatico = $exp[0];
            $estaticoDinamico = explode($estatico, $resourceUri);
            if (count($estaticoDinamico) == 2) {
                $estaticoUrl = explode($estaticoDinamico[1], $resourceUri);
                $estaticoUrl = $estaticoUrl[0];

                if ($estaticoUrl == $estatico) {
                    //estou em uma rota dinamica valida
                    $dinamico = $estaticoDinamico[1];
                    $this->options = explode('/', $dinamico);
                    return true;
                }
            } else {
                return false;
            }
        } else {
            $patternAsRegex = preg_replace_callback('@:[\w]+@', [$this, 'convertToRegex'], $this->pattern);
            if (substr($this->pattern, -1) === '/') {
                $patternAsRegex = $patternAsRegex . '?';
            }
            $patternAsRegex = '@^' . $patternAsRegex . '$@';

            if (preg_match($patternAsRegex, $resourceUri, $paramValues)) {
                array_shift($paramValues);

                if (count($paramValues) > 0) {
                    foreach ($paramNames as $index => $value) {
                        $this->params[substr($value, 1)] = urldecode($paramValues[$index]);
                    }
                }
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Converte uma variavel em regex, exemplo :id pode virar
     * ([a-zA-Z0-9_\-\.]+) ou pode virar ([\d]{1,8}) caso seja
     * encontrada tal restrição na $this->conditions
     * @param $matches array
     */
    public function convertToRegex($matches)
    {
        $key = str_replace(':', '', $matches[0]);
        if (array_key_exists($key, $this->conditions)) {
            return '(' . $this->conditions[$key] . ')';
        } else {
            return '([a-zA-Z0-9_\-\.]+)';
        }
    }
}
