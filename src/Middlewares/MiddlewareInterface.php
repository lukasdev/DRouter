<?php
namespace DRouter\Middlewares;

use \Closure;

interface MiddlewareInterface
{
    public function handle($request, $response, Closure $next);
}