<?php

namespace DRouter;

class Render
{
    /**
     * O caminho até a pasta de views
     * @var $viewsFolder string
     */
    protected $viewsFolder;
        
    /**
     * Array de variaveis globais as views!
     * @var array $globals
     */
    protected $globals = array();
    /**
    * Array que define header e footer para o template
    * @var array $hf [headerfooter]
    */

    protected $hf = [
        'header' => '', 
        'footer' => ''
    ];

    /**
    * Define qual o arquivo de header e qual o arquivo de footer do template
    * @param string $header
    * @param string $footer
    */
    public function setHf($header, $footer){
        $this->hf['header'] = $header;
        $this->hf['footer'] = $footer;
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
    * @param string $viewsFolder
    */
    public function setViewsFolder($viewsFolder)
    {
        $this->viewsFolder = $viewsFolder;
    }


    /**
    * Carrega uma view e injeta valores
    * @param string $fileName
    * @param array $data
    */
    public function load($fileName, $data, $hf = true)
    {
        if (empty($this->viewsFolder)) {
            throw new \Exception('A pasta de views não foi definida!');
        }

        $data = array_merge($data, $this->getGlobals());

        extract($data);
        
        if (file_exists($this->viewsFolder.$fileName)) {
            if ($hf === true && $this->hf['header'] != '') {
                include_once $this->viewsFolder.$this->hf['header'];
            }

            include_once $this->viewsFolder.$fileName;

            if ($hf === true && $this->hf['footer'] != '') {
                include_once $this->viewsFolder.$this->hf['footer'];
            }
        }
    }

    public function renderNotFoundPage()
    {
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
