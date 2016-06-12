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

        public function __construct()
        {
            $this->setStatusCode(200);
            $this->setContentType('text/html');
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

            extract($data);
            if (file_exists($this->viewsFolder.$fileName)) {
                include_once $this->viewsFolder.$fileName;
            }
        }
    }