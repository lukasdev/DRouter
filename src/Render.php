<?php
    namespace DRouter;

    class Render
    {
        /**
        * O tipo de conteudo carregado na view
        *@var $contentType string
        */
        protected $contentType;

        /**
        * O código de status do response
        *@var $statusCode int
        */
        protected $statusCode;

        /**
        * O caminho até a pasta de views
        *@var $viewsFolder string
        */
        protected $viewsFolder;
        
        /**
        * Array de variaveis globais as views!
        * @var array $globals
        */
        protected $globals;

        public function __construct()
        {
            $this->setStatusCode(200);
            $this->setContentType('text/html');
        }
        /**
        * Define variaveis que serão globais para qualquer view
        * no momento do extract
        * @param array $data
        */
        public function setAsGlobal(array $data)
        {
            $glob = $this->getGlobals();
            if (!empty($glob)) {
                $data = array_merge($glob, $data);
            }

            $this->globals = $data;
        }

        /**
        * Retorna o array de globais
        * @return array
        */
        public function getGlobals()
        {
            return $this->globals;
        }
        /**
        * Seta a pasta de viewss
        *@param string $viewsFolder
        */
        public function setViewsFolder($viewsFolder)
        {
            $this->viewsFolder = $viewsFolder;
        }

        /**
        * Seta o content type
        *@param string $contentType
        */
        public function setContentType($contentType)
        {
            $this->contentType = $contentType;
        }

        /**
        * Retorna o content type definido
        */
        public function getContentType()
        {
            return $this->contentType;
        }

        /**
        * Seta o status do response
        *@param int $code
        */
        public function setStatusCode($code)
        {
            $this->statusCode = $code;
        }

        /**
        * Retorna o código de status do response
        */
        public function getStatusCode()
        {
            return $this->statusCode;
        }

        /**
        * Carrega uma view e injeta valores
        *@param string $fileName
        *@param array $data
        */
        public function load($fileName, $data)
        {
            if (empty($this->viewsFolder)) {
                throw new \Exception('A pasta de views não foi definida!');
            }

            header('HTTP/1.1 '.$this->getStatusCode());
            header('Content-type: '.$this->getContentType().';charset=utf8');
            $data = array_merge($data, $this->getGlobals());

            extract($data);
            if (file_exists($this->viewsFolder.$fileName)) {
                include_once $this->viewsFolder.$fileName;
            }
        }

        public function renderNotFoundPage(){
            echo '<html>
                <head>
                    <meta charset=UTF-8>
                    <title>Pagina não encontrada</title>
                    <style>
                        body{
                            margin:0;
                            padding:30px;
                            font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;
                        }
                        h1{
                            margin:0;
                            font-size:48px;
                            font-weight:normal;
                            line-height:48px;
                        }
                        strong{
                            display:inline-block;
                            width:65px;
                        }
                    </style>
                </head>
                <body>
                    <h1>Pagina não encontrada</h1>
                    <p>A pagina que você procura não está aqui, verifique a url!</p>
                </body>
            </html>';

        }
    }