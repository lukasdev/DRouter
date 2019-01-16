<?php
namespace DRouter\Middlewares;

use \Closure;
use DRouter\Http\Response;

interface MiddlewareInterface
{
    public function handle($request, $response, Closure $next):Response;
}