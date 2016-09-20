<?php

/**
 * Request
 * Objeto que lida com o request atual e os requests passados para a aplicação
 *
 * @author      Lucas Silva <dev.lucassilva@gmail.com>
 * @copyright   2016 Lucas Silva
 * @link        http://www.downsmaster.com
 * @version     2.0.0
 *
 * MIT LICENSE
 */
namespace DRouter;

class Request
{
    /**
     * Retorna o metodo atual ou GET por padrão
     * @return string
     */
    public function getMethod()
    {
        return (isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }

    /**
     * Retorna o content-type do request atual
     * @return string
     */
    public function getContentType()
    {
        return (isset($_SERVER['CONTENT_TYPE'])) ? $_SERVER['CONTENT_TYPE'] : null;
    }

    /**
     * Retorna o conteudo do request parseado para GET, POST, PUT e DELETE
     * ou lança uma exceção caso o tipo de content-type seja inválido
     * @return array
     */
    public function getParsedBody()
    {
        if (!in_array($this->getMethod(), ['GET', 'POST'])) {
            if ($this->getContentType() == 'application/x-www-form-urlencoded') {
                $input_contents = file_get_contents("php://input");
                if (function_exists('mb_parse_str')) {
                    mb_parse_str($input_contents, $post_vars);
                } else {
                    parse_str($input_contents, $post_vars);
                }
                if (count($_GET) > 0) {
                    $post_vars = array_merge($post_vars, $_GET);
                }
                return $post_vars;
            } else {
                throw new \UnexpectedValueException('Content-type não aceito');
            }
        } elseif ($this->getMethod() == 'POST') {
            if (count($_GET) > 0) {
                $_POST = array_merge($_POST, $_GET);
            }

            return $_POST;
        } elseif ($this->getMethod() == 'GET') {
            return $_GET;
        }
    }

    /**
     * Retorna o valor de um indice no corpo do request, caso exista!
     * @param $key string
     */
    public function get($key)
    {
        $data = $this->getParsedBody();
        if (isset($data[$key])) {
            return $data[$key];
        }
    }

    /**
     * Retorna o RequestUri atual
     * @return string
     */
    public function getRequestUri()
    {
        if (isset($_SERVER['ORIG_PATH_INFO'])) {
            $pathInfo = $_SERVER['ORIG_PATH_INFO'];
        } elseif (isset($_SERVER['PATH_INFO'])) {
            $pathInfo = $_SERVER['PATH_INFO'];
        }
        
        //correção para alguns hosts
        if (isset($pathInfo)) {
            $pathInfo = str_replace('/index.php', '', $pathInfo);
        }

        $rota = (!isset($pathInfo)) ? '/' : strip_tags(trim($pathInfo));

        return $rota;
    }

    /**
     * Retorna a base da aplicação, exemplo /projetos/aplicacao
     * caso a aplicaçao esteja em localhost/projetos/aplicacao
     */
    public function getRoot()
    {
        $base = substr(explode('index.php', $_SERVER['SCRIPT_NAME'])[0], 0, -1);
        return $base;
    }
}
