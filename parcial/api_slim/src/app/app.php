<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Routing\RouteCollectorProxy; //Para utilizar grupos
use Slim\Middleware\MethodOverrideMiddleware; //Para sobreescribir método Post por Put

use Slim\Factory\AppFactory;


require __DIR__ . '/../../vendor/autoload.php';

//NECESARIO PARA GENERAR EL JWT
use Firebase\JWT\JWT;


$app = AppFactory::create();
//Agrego sobreescritura de métodos
$app->addRoutingMiddleware();
$methodOverrideMiddleware=new MethodOverrideMiddleware();
$app->add($methodOverrideMiddleware);

//************************************************************************************************************//
require_once __DIR__ . "/../poo/Usuario.php";
require_once __DIR__ . "/../poo/Juguete.php";
require_once __DIR__ . "/../poo/MW.php";

$app->get('/', \Acquisto\Franco\Usuario::class . ':ListadoDeUsuarios');
$app->post('/', \Acquisto\Franco\Juguete::class . ':AltaDeJuguetes')->add(\Acquisto\Franco\MW::class . ':ChequearJWT');
$app->get('/juguetes', \Acquisto\Franco\Juguete::class . ':ListadoDeJuguetes')->add(\Acquisto\Franco\MW::class . ':ListarTablaJuguetes');
$app->post('/login', \Acquisto\Franco\Usuario::class . ':VerificarUsuario')->add(\Acquisto\Franco\MW::class . ':VerificarUsuarioEnBd')->add(\Acquisto\Franco\MW::class . '::ValidarParametrosLogin');
$app->get('/login', \Acquisto\Franco\Usuario::class . ':VerificarJWT');

$app->group('/toys',function (RouteCollectorProxy $grupo)
{
  $grupo->post('/', \Acquisto\Franco\Juguete::class . ':ModificarJuguetesPorID');
  $grupo->delete('/{id_juguete}', \Acquisto\Franco\Juguete::class . ':BorradoDeJuguetesPorID');
})
->add(\Acquisto\Franco\MW::class . ':ChequearJWT');

$app->group('/tablas',function (RouteCollectorProxy $grupo)
{
  $grupo->get('/usuarios', \Acquisto\Franco\Usuario::class . ':ListadoDeUsuarios')->add(\Acquisto\Franco\MW::class . '::ListarTablaUsuarios');
  $grupo->post('/usuarios', \Acquisto\Franco\Usuario::class . ':ListadoDeUsuarios')->add(\Acquisto\Franco\MW::class . '::ListarTablaUsuariosConJWT')->add(\Acquisto\Franco\MW::class . ':ChequearJWT');
});






















//CORRE LA APLICACIÓN.
$app->run();