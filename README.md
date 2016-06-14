# DRouter

Um sistema simplista de roteamento, com o intuito de ser utilizado em aplicações
web pequenas e webservices REST.

## Instalação

Clone o repositório e execute

``` bash
$ composer require lukasdev/drouter
```

## Utilização

<p>Esta classe proporciona a criação de rotas para os principais requests REST;
conta ainda coma possibilidade de nomenclatura de rotas criadas bem como a 
reutilização de códigos previamente criados em rotas.
Abaixo alguns exemplos.</p>

<h3>Iniciando uma instancia e criando uma rota</h3>

``` php
$app = new DRouter\App();

//Rota com request GET:

$app->route('/', 'GET', function(){
    echo 'Hello World';
});

//a função criada acima também pode ser abreviada em:
$app->get('/', function(){
    echo 'Hello World';
});

$app->run();
```
<h3>Roteamento com outros request, e nomenclatura de rotas</h3>

``` php
$app = new DRouter\App();

//rota com PUT
$app->route('/test', 'PUT', function(){
    echo 'Rota excutando em request PUT e nomeada!';
})->setName('testPut');

//neste caso $app->put('/test', function(...){...}); também funcionaria
//bem como qualquer outro dos requests ->put, ->delete, ->get, ->options ...

//exemplo de rota com request delete:
$app->route('/post/:id', 'DELETE', function($id){
    echo 'Deletando post do id '.$id;
});

$app->run();
```

<h3>Reutilizando códigos</h3>
<p>No exemplo abaixo, são criadas duas rotas de exemplo. Na primeira um código
executa um calculo que requer um parametro, parametro esse passado pela url</p>

<p>Na segunda rota, o código da primeira rota é reutilizado usando o metodo DRouter\App::getRouteCallable($routeName)</p>

``` php
$app = new DRouter\App();
//As rotas podem ser executadas sob Requests diferentes, como no exemplo abaixo:

$app->route('/test/:val1/:val2/', 'GET', function($val1, $val2){
    //faz algo super complexo que requer muitas linhas de codigo
    //representado abaixo pela soma e retorno do resultado

    $soma = $val1+$val2;
    return $soma;
})->setName('meuTestGet');

//Reutilizando função executada na rota acima em uma outra rota
$app->route('/test-put', 'PUT', function(){
    //chamo pela função da rota anterior
    $callable = $this->getRouteCallable('meuTestGet');
    //a função anterior recebe $val1 e $val2

    $meuValor = 5*7;
    $valor2 = 9;
    $somatoria = $callable($meuValor, $valor2);
});

$app->run();
```

##Objetos e metodos de objetos como callables
<p>Talvez você precise delegar o funcionamento de uma de suas rotas para um
metodo de um objeto, ou até mesmo a um objeto como um todo. Agora (a partir de 11/06/2016)
já possível fazer isso. veja os exemplos abaixo:</p>

<h3>Objetos chamaveis (i.e com metodo magico __invoke) como callables</h3>

``` php
    class ExemploChamavel
    {
        public function __invoke($valor)
        {
            $valor = $valor*$valor;
            $this->fazerAlgoIncrivel($valor);
        }

        private function fazerAlgoIncrivel($quadrado)
        {
            //varias linhas de código ...
            echo 'Fazendo algo incrível, quadrado: '. $quadrado;
        }
    }

    //utilização
    $app = new DRouter\App();
    $app->get('/calcular/:valor', new ExemploChamavel());

    //saida --> Fazendo algo incrível, quadrado: [valorrecebido]
```

<h3>Usando metodos de classes como callables</h3>
``` php
    class Chamavel
    {
        public function fazerAlgoIncrivel($valor)
        {
            //varias linhas de código ...
            echo 'Fazendo algo incrível, valor: '. $valor;
        }
    }

    //utilização:
    $app = new DRouter\App();
    
    //só como exemplo, estou usando request put... pode ser 
    //qualquer um dos outros
    $app->put('/valor/:valor', 'Chamavel:fazerAlgoIncrivel');

    //saida --> Fazendo algo incrível, valor: [valorrecebido]
```
<h3>Injeção de dependencia usando Container</h3>
<p>No código abaixo exemplificamos a criação de um usuário, através da injeção
da dependencia \PDO no Container</p>

``` php
    $app = new DRouter\App();

    $container = $app->getContainer();
    $container->db = function(){
        return new \PDO('mysql:host=localhost;dbname=meubanco', 'root', '');
    };

    $app->post('/user', function(){
        $dados = $this->getParsedBody(); //dados do usuario
        $banco = $this->container->db;

        $stmt = $banco->prepare("INSERT INTO `usuarios` SET `nome` = ?, `email` = ?");
        $stmt->execute([
            $dados['nome'],
            $dados['email']
        ]);
    });
```

## Contribuições

Por favor veja [CONTRIBUTING](CONTRIBUTING.md) para detalhes.

## Créditos

- [Lucas Silva](https://github.com/lukasdev)
- [All Contributors](https://github.com/lukasdev/DRouter/contributors)

## Licença

The MIT License (MIT).
