<?php

/**
 * App
 *
 * @author      Lucas Silva <dev.lucassilva@gmail.com>
 * @copyright   2016 Lucas Silva
 * @link        http://www.downsmaster.com
 * @version     2.0.0
 *
 * MIT LICENSE
 */
namespace DRouter;

class App
{
    /**
     * Objeto \DRouter\Router
     * @var $router Router
     */
    protected $router;

    /**
     * Objeto \DRouter\Request
     * @var $request Request
     */
    protected $request;

    /**
     * Objeto DRouter\Container
     * @var $container Container
     */
    protected $container;

    /**
     * Objeto \DRouter\Render
     * @var $render Render
     */
    public $render;

    /**
     * Pagina notFound modificada
     * @var $notFoundModified false|callable
     */
    protected $notFoundModified = false;

    /**
     * Exceptions adicionais da App a serem lançadas!
     * @var $addedExceptions array
     */
    protected $addedExceptions = array();

    public function __construct($paramsContainer = array())
    {
        $this->request = new Request();
        $this->render = new Render();
        $this->router = new Router($this->request);

        $content = [
            'request' => $this->request,
            'render' => $this->render,
            'router' => $this->router
        ];
        $params = array_merge($paramsContainer, $content);
        $this->container = new Container($params);
    }

    /**
     * @return \DRouter\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Recebe um callable e retorna sua referencia de acordo
     * @return callable
     */
    private function validCallable($callable)
    {
        if (is_callable($callable)) {
            if ($callable instanceof \Closure) {
                $callable = $callable->bindTo($this->container);
            }

            return $callable;
        } elseif (is_string($callable) && count(explode(':', $callable)) == 2) {
            return $callable;
        }

        $this->addedExceptions['\InvalidArgumentException'] = 'Callable inválido';
        return false;
    }

    /**
     * Emula metodos get, post, put, delete e group do objeto Router
     */
    public function __call($method, $args)
    {
        $methodUpper = strtoupper($method);
        $accepted = $this->router->getRequestAccepted();

        if (in_array($methodUpper, $accepted)) {
            if (count($args) == 3) {
                $conditions = $args[2];
            } elseif (count($args) == 2) {
                $conditions = array();
            }

            $callable = $this->validCallable($args[1]);

            return $this->router->route($methodUpper, $args[0], $callable, $conditions);
        } elseif ($method == 'group' && count($args) == 2) {
            $callable = $this->validCallable($args[1]);
            $this->router->group($args[0], $callable);
        } else {
            $this->addedExceptions['\Exception'] = 'O metodo '.$method.' não existe';
        }
    }

    /**
     * Define uma pagina notfoud
     * @param callable $fnc
     */
    public function notFound($fnc)
    {
        if (is_callable($fnc)) {
            if ($fnc instanceof \Closure) {
                $this->notFoundModified = $fnc;
            } else {
                $this->addedExceptions['\InvalidArgumentException'] = 'O callable do metodo notFound deve ser um closure!';
            }
        } else {
            $this->addedExceptions['\InvalidArgumentException'] = 'App::notFound, callable invalido';
        }
    }

    /**
     * Retorna o path root via request
     * @return string
     */
    public function root()
    {
        return $this->request->getRoot();
    }

    /**
     * Lança exceções adicionais do objeto App.
     */
    private function runAddedExceptions()
    {
        if (!empty($this->addedExceptions)) {
            foreach ($this->addedExceptions as $exception => $message) {
                throw new $exception($message);
                break;
            }
        }
    }

    /**
     * Da inicio a App. Executando as rotas criadas, renderizando uma pagina 404
     * ou exibindo a mensagem de uma exceção que tenha sido lançada
     */
    public function run()
    {
        try {
            $this->runAddedExceptions();

            if ($this->router->dispatch()) {
                $this->router->execute($this->container);
            } else {
                if ($this->notFoundModified) {
                    $fnc = $this->notFoundModified->bindTo($this->container);
                    $fnc();
                } else {
                    $this->render->renderNotFoundPage();
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
