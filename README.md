# DRouter

Um sistema simplista de roteamento, com o intuito de ser utilizado em aplicações
web pequenas e webservices REST.

## Instalação
via composer

``` bash
$ composer require lukasdev/drouter
```

## Utilização

<h3>Iniciando uma instancia e criando uma rota</h3>

``` php
$app = new DRouter\App();

//Rota com request GET:
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
