# DRouter

Um sistema simplista de roteamento, com o intuito de ser utilizado em aplicações
web pequenas e webservices REST.

## Instalação
via composer

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
<h3>Documentação completa</h3>
<a href="http://drouter.downsmaster.com" target="_blank">drouter.downsmaster.com</a>

## Contribuições

Por favor veja [CONTRIBUTING](CONTRIBUTING.md) para detalhes.

## Créditos

- [Lucas Silva](https://github.com/lukasdev)
- [All Contributors](https://github.com/lukasdev/DRouter/contributors)

## Licença

The MIT License (MIT).
