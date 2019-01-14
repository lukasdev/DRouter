<?php
/**
 * Response
 * Objeto que lida com informações referentes ao response http (em desenvolvimento)
 *
 * @author      Lucas Silva <dev.lucassilva@gmail.com>
 * @copyright   2019 Lucas Silva
 * @link        http://www.downsmaster.com
 * @version     2.5.0
 *
 * MIT LICENSE
 */
    namespace DRouter\Http;

    class Response
    {
        use HttpDataTrait;
        
        protected static $messages = [
            //Informational 1xx
            100 => '100 Continue',
            101 => '101 Switching Protocols',
            //Successful 2xx
            200 => '200 OK',
            201 => '201 Created',
            202 => '202 Accepted',
            203 => '203 Non-Authoritative Information',
            204 => '204 No Content',
            205 => '205 Reset Content',
            206 => '206 Partial Content',
            226 => '226 IM Used',
            //Redirection 3xx
            300 => '300 Multiple Choices',
            301 => '301 Moved Permanently',
            302 => '302 Found',
            303 => '303 See Other',
            304 => '304 Not Modified',
            305 => '305 Use Proxy',
            306 => '306 (Unused)',
            307 => '307 Temporary Redirect',
            //Client Error 4xx
            400 => '400 Bad Request',
            401 => '401 Unauthorized',
            402 => '402 Payment Required',
            403 => '403 Forbidden',
            404 => '404 Not Found',
            405 => '405 Method Not Allowed',
            406 => '406 Not Acceptable',
            407 => '407 Proxy Authentication Required',
            408 => '408 Request Timeout',
            409 => '409 Conflict',
            410 => '410 Gone',
            411 => '411 Length Required',
            412 => '412 Precondition Failed',
            413 => '413 Request Entity Too Large',
            414 => '414 Request-URI Too Long',
            415 => '415 Unsupported Media Type',
            416 => '416 Requested Range Not Satisfiable',
            417 => '417 Expectation Failed',
            418 => '418 I\'m a teapot',
            422 => '422 Unprocessable Entity',
            423 => '423 Locked',
            426 => '426 Upgrade Required',
            428 => '428 Precondition Required',
            429 => '429 Too Many Requests',
            431 => '431 Request Header Fields Too Large',
            //Server Error 5xx
            500 => '500 Internal Server Error',
            501 => '501 Not Implemented',
            502 => '502 Bad Gateway',
            503 => '503 Service Unavailable',
            504 => '504 Gateway Timeout',
            505 => '505 HTTP Version Not Supported',
            506 => '506 Variant Also Negotiates',
            510 => '510 Not Extended',
            511 => '511 Network Authentication Required'
        ];

        public function __construct(){

        }


        public function setStatus($key) {
            $status = self::$messages[$key];
            header("HTTP/1.1 ".$status);
        }


        public function setHeaders(array $headers){
            foreach ($headers as $key => $value) {
                header("$key:$value");
            }
        }


        public function setJsonResponse(int $status, array $data)
        {
            $this->setStatus($status);
            $this->setHeaders([
                'Content-Type' => 'application/json'
            ]);

            die(json_encode($data));
        }
    }