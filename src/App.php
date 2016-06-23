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
    protected $router;
    protected $request;
    protected $container;
    public $render;
    protected $notFoundModified = false;

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

    public function getContainer()
    {
        return $this->container;
    }

    private function validCallable($callable)
    {
        if (is_callable($callable)) {
            if ($callable instanceof \Closure){
                $callable = $callable->bindTo($this->container);
            }
            return $callable;
        } elseif (is_string($callable) && count(explode(':', $callable)) == 2){
            $exp = explode(':', $callable);
            $obj = filter_var($exp[0], FILTER_SANITIZE_STRING);
            $obj = new $obj($this->container);
            $method = filter_var($exp[1], FILTER_SANITIZE_STRING);
            if (is_callable([$obj, $method])) {
                return [$obj, $method];
            } 
        }
        return false;
    }

    public function __call($method, $args)
    {
        $methodUpper = strtoupper($method);
        $accepted = $this->router->getRequestAccepted();

        if (in_array($methodUpper, $accepted)) {
            if (count($args) == 3) {
                $conditions = $args[2];
            } elseif(count($args) == 2) {
                $conditions = array();
            }

            $callable = $this->validCallable($args[1]);

            return $this->router->route($methodUpper, $args[0], $callable, $conditions);
        } elseif ($method == 'group' && count($args) == 2) {
            $callable = $this->validCallable($args[1]);
            $this->router->group($args[0], $callable);
        }
    }

    public function notFound()
    {
        $args = func_get_args();
        if(count($args) == 1 && is_callable($args[0])) {
            $fnc = $args[0];
            if ($fnc instanceof \Closure) {           
                $this->notFoundModified = $fnc;
            }
        }
    }


    public function run()
    {
        try {

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
