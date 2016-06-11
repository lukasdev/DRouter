<?php
    namespace DRouter;

    class Container
    {
        private $data;

        public function __construct(array $params)
        {
            $this->setData($params);
        }

        private function setData(array $params)
        {
            if (!is_array($params)) {
                throw new \InvalidArgumentException('Dados do container inválidos');
            }

            $this->data = $params;
        }

        public function __set($key, $val)
        {
            $this->data[$key] = $val;
        }

        public function __get($key){
            return $this->data[$key];
        }
    }