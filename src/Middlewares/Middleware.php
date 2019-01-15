<?php
    namespace DRouter\Middlewares;
    
    class Middleware{
        public static function call($middleware, $next, $request, $response)
        {
            return call_user_func_array([$middleware, 'handle'], [$request, $response, $next]);
        }

        public static function executeMiddlewares($middlewares, &$container){
            foreach ($middlewares as $middleware) {
                if (is_string($middleware) || is_object($middleware)) {
                    if (is_string($middlewares)) {
                        $middleware = new $middleware();
                    }

                    try {
                        if ($container->request instanceof \DRouter\Http\Request) {
                            $container->request = self::call($middleware, function($request, $response){
                                return $request;
                            }, $container->request, $container->response);
                        } else {
                            throw new \Exception('Todo middleware deve retornar \DRouter\Http\Request');
                            break;
                        }
                    } catch (\Exception $e) {
                        echo 'Erro: '.$e->getMessage();
                        die;
                    }
                }
            }
        }
    }