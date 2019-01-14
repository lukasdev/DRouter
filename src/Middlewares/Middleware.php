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

                    $container->request = self::call($middleware, function($res){
                        return $res;
                    }, $container->request, $container->response);
                }
            }
        }
    }