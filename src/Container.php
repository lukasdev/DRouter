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
                throw new \InvalidArgumentException('Dados do container deve ser um array');
            }

            $this->data = $params;
        }

        public function getData()
        {
            return $this->data;
        }

        public function __set($key, $val)
        {
            $this->data[$key] = $val;
        }

        public function __get($key){
            if ($this->data[$key]) {
                if ($this->data[$key] instanceof \Closure) {
                    $fnc = $this->data[$key];
                    return $fnc();
                } else {
                    return $this->data[$key];
                }
            }
        }
    }