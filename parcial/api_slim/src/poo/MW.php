<?php
namespace Acquisto\Franco;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;
use Firebase\JWT\JWT;
use stdClass;

require_once __DIR__ ."/Usuario.php";
require_once __DIR__ ."/Acceso_datos.php";
require_once __DIR__ ."/Autentificadora.php";

class MW
{
    public static function ValidarParametrosLogin(Request $request, RequestHandler $handler) : ResponseMW
    {
        $res=new stdClass();
        $res->mensaje="Objeto JSON usuario no existe";
        $res->exito=false;
        if(isset($request->getParsedBody()["usuario"]))
        {
            $usuario=json_decode($request->getParsedBody()["usuario"]);
            if(isset($usuario->correo) && isset($usuario->clave))
            {
                $res->exito=true;
            }
            else
            {
                $res->mensaje="No se encuentran los siguientes atributos: ";
                if(!isset($usuario->correo))
                {
                    $res->mensaje.="Correo/ ";
                }
                if(!isset($usuario->clave))
                {
                    $res->mensaje.="Clave/ ";
                }
            }
        }
        if($res->exito)
        {
            $response=$handler->handle($request);
        }
        else
        {
            $response=new ResponseMW();
            $response=$response->withStatus(409);
            $response=$response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode($res));
        }
        return $response;
    }
    public function VerificarUsuarioEnBd(Request $request, RequestHandler $handler) : ResponseMW
    {
        $res=new stdClass();
        $res->exito=false;
        $res->mensaje="Usuario no coincide en la base de datos";
        $usuario = json_decode($request->getParsedBody()["usuario"]);
        $correo=$usuario->correo;
        $clave=$usuario->clave;
        $sql=AccesoDatos::retornarObjetoAcceso()->retornarConsulta("SELECT * FROM usuarios");
        if($sql->execute())
        {
            $array_obj=$sql->fetchAll();
            foreach ($array_obj as $item)
            {
                if($item!==null)
                {
                    if($correo===$item["correo"] && $clave===$item["clave"])
                    {
                        $res->exito=true;
                        $res->mensaje="";
                        break;
                    }
                }
            }
        }
        if($res->exito)
        {
            $response=$handler->handle($request);
        }
        else
        {
            $response=new ResponseMW();
            $response=$response->withStatus(403);
            $response->getBody()->write(json_encode($res));
            $response->withHeader('Content-Type', 'application/json');
        }
        return $response;
    }
    public function ChequearJWT(Request $request, RequestHandler $handler) : ResponseMW
    {
        $res=new stdClass();
        $res->exito=false;
        $res->mensaje="El token no existe";
        if(isset($request->getHeader("authorization")[0]))
        {
            $token=$request->getHeader("authorization")[0];
            $token=explode("Bearer ",$token)[1];
            $res_autentificadora=Autentificadora::verificarJWT($token);
            if($res_autentificadora->verificado)
            {
                $res->exito=true;
                $res->mensaje="";
            }
            else
            {
                $res->mensaje="El token no es valido";
            }
        }
        if($res->exito)
        {
            $response=$handler->handle($request);
        }
        else
        {
            $response=new ResponseMW();
            $response=$response->withStatus(403);
            $response->getBody()->write(json_encode($res));
            $response=$response->withHeader('Content-Type', 'application/json');
        }
        return $response;
    }
    public static function ListarTablaUsuarios(Request $request, RequestHandler $handler) : ResponseMW
    {
        $response=$handler->handle($request);
        $contenidoApi=(string)$response->getBody();

        $obj_array=json_decode($contenidoApi);
        $usuarios=json_decode($obj_array->dato,true);
        
        $tabla="";
        $tabla.="<table>
        <tr>
        <th>ID</th><th>CORREO</th><th>NOMBRE</th><th>APELLIDO</th><th>PERFIL</th><th>FOTO</th>
        </tr>";
        foreach($usuarios as $item)
        {
            $tabla.=
            "<tr>
            <th>".$item["id"]."</th><th>".$item["correo"]."</th><th>".$item["nombre"]."</th><th>".$item["apellido"]."</th><th>".$item["perfil"]."</th><th><img src=\"".$item["foto"]."\" alt=\"".$item["foto"]."\" width=\"50px\" heigh=\"50px\"></th>
            </tr>";
        }
        $tabla.="</table>";
        $response=new ResponseMW();
        $response=$response->withStatus(200);
        $response->getBody()->write($tabla);
        $response=$response->withHeader('Content-Type', 'application/json');
        return $response;
    }
    public static function ListarTablaUsuariosConJWT(Request $request, RequestHandler $handler) : ResponseMW
    {
        $res=new stdClass();
        $res->exito=false;
        $res->mensaje="El usuario no es propietario";

        $token=$request->getHeader("authorization")[0];
        $token=explode("Bearer ",$token)[1];
        
        $obj=Autentificadora::obtenerPayLoad($token);
        $perfil=$obj->payload->usuario->perfil;

        $tabla="";
        if($perfil==="propietario")
        {
            $res->exito=true;
            $res->mensaje="";
            $response=$handler->handle($request);
            $contenidoApi=(string)$response->getBody();
    
            $obj_array=json_decode($contenidoApi);
            $usuarios=json_decode($obj_array->dato,true);
            
            $tabla.="<table>
            <tr>
            <th>CORREO</th><th>NOMBRE</th><th>APELLIDO</th>
            </tr>";
            foreach($usuarios as $item)
            {
                $tabla.=
                "<tr>
                <th>".$item["correo"]."</th><th>".$item["nombre"]."</th><th>".$item["apellido"]."</th>
                </tr>";
            }
            $tabla.="</table>";
        }

        $response=new ResponseMW();
        $response=$response->withHeader('Content-Type', 'application/json');
        if($res->exito)
        {
            $response=$response->withStatus(200);
            $response->getBody()->write($tabla);
        }
        else
        {
            $response=$response->withStatus(403);
            $response->getBody()->write(json_encode($res));
        }

        return $response;
    }
    public function ListarTablaJuguetes(Request $request, RequestHandler $handler) : ResponseMW
    {
        $response=$handler->handle($request);
        $contenidoApi=(string)$response->getBody();

        $obj_array=json_decode($contenidoApi);
        $juguetes=json_decode($obj_array->dato,true);
        
        $tabla="";
        $tabla.="<table>
        <tr>
        <th>ID</th><th>MARCA</th><th>PRECIO</th><th>FOTO</th>
        </tr>";
        foreach($juguetes as $item)
        {
            if((int)$item["id"] % 2 !==0)
            {
                $tabla.=
                "<tr>
                <th>".$item["id"]."</th><th>".$item["marca"]."</th><th>".$item["precio"]."</th><th><img src=\"".$item["path_foto"]."\" alt=\"".$item["path_foto"]."\" width=\"50px\" heigh=\"50px\"></th>
                </tr>";
            }
        }
        $tabla.="</table>";
        $response=new ResponseMW();
        $response=$response->withStatus(200);
        $response->getBody()->write($tabla);
        $response=$response->withHeader('Content-Type', 'application/json');
        return $response;
    }
}