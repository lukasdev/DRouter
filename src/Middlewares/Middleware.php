<?php
    namespace DRouter\Middlewares;
    
    class Middleware{
        public static function call($middleware, $next, $request, $response)
        {
            return call_user_func_array([$middleware, 'handle'], [$request, $response, $next]);
        }
    }