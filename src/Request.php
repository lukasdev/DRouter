<?php
namespace DRouter;

class Request
{
    public function getMethod()
    {
        return (isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }

    public function getContentType()
    {
        return (isset($_SERVER['CONTENT_TYPE'])) ? $_SERVER['CONTENT_TYPE'] : null;
    }

    public function getParsedBody()
    {
        if (!in_array($this->getMethod(), ['GET', 'POST'])) {
            if ($this->getContentType() == 'application/x-www-form-urlencoded') {
                $input_contents = file_get_contents("php://input");
                if (function_exists('mb_parse_str')) {
                    mb_parse_str($input_contents, $post_vars);
                } else {
                    parse_str($input_contents,$post_vars);
                }
                return $post_vars;
            } else {
                throw new \UnexpectedValueException('Content-type não aceito');
            }
        } elseif ($this->getMethod() == 'POST') {
            return $_POST;
        } elseif ($this->getMethod() == 'GET') {
            return $_GET;
        }
    }

    public function getRequestUri()
    {
        if (isset($_SERVER['ORIG_PATH_INFO'])) {
            $pathInfo = $_SERVER['ORIG_PATH_INFO'];
        } elseif (isset($_SERVER['PATH_INFO'])) {
            $pathInfo = $_SERVER['PATH_INFO'];
        }

        $rota = (!isset($pathInfo)) ? '/' : strip_tags(trim($pathInfo));

        return $rota;
    }

    public function getRoot()
    {
        $base = substr(explode('index.php', $_SERVER['SCRIPT_NAME'])[0],0,-1);
        return $base;
    }
}