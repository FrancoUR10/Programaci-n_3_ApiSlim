<?php
namespace Acquisto\Franco;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;
use Firebase\JWT\JWT;
use PDO;
use stdClass;

require_once __DIR__ ."/Acceso_datos.php";
require_once __DIR__ ."/Autentificadora.php";

class Usuario
{
    public int $id;
 	public string $correo;
    public string $clave;
    public string $nombre;
    public string $apellido;
    public string $foto;
    public string $perfil;

    public function ListadoDeUsuarios(Request $request, Response $response, array $args) : Response
    {
        $res=self::TraerTodosLosUsuarios();
        if($res->exito)
        {
            $response=$response->withStatus(200);
        }
        else
        {
            $response=$response->withStatus(424);
        }
        $response->getBody()->write(json_encode($res));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public static function TraerTodosLosUsuarios():stdClass
    {
        $res=new stdClass();
        $res->exito=false;
        $res->mensaje="Error al listar los usuarios";
        $res->dato=null;
        $array_res=array();
        $sql=AccesoDatos::retornarObjetoAcceso()->retornarConsulta("SELECT * FROM usuarios");
        if($sql->execute())
        {
            $res->exito=true;
            $res->mensaje="Listado de usuarios";
            $array_res=$sql->fetchAll(PDO::FETCH_CLASS, "Acquisto\Franco\Usuario");	
            $res->dato=json_encode($array_res);
        }
        return $res;
    }
    public function VerificarUsuario(Request $request, Response $response, array $args) : Response
    {
        $res=new stdClass();
        $res->exito=false;
        $res->jwt=null;

        $token=null;
        $data=null;
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
                        $item["clave"]="";
                        $data=$item;
                        break;
                    }
                }
            }
        }
        if($res->exito)
        {
            $token=Autentificadora::crearJWT($data,60*2);
            $res->jwt=$token;
            $response=$response->withStatus(200);
        }
        else
        {
            $response=$response->withStatus(403);
        }
        $response->getBody()->write(json_encode($res));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function ValidarParametrosUsuario(Request $request, RequestHandler $handler) : ResponseMW
    {
        $res=new stdClass();
        $res->mensaje="Objeto JSON no existe";
        $res->exito=false;
        if(isset($request->getParsedBody()["obj_json"]))
        {
            $obj_json=json_decode($request->getParsedBody()["obj_json"]);
            if(isset($obj_json->correo) && isset($obj_json->clave))
            {
                $res->exito=true;
            }
            else
            {
                $res->mensaje="No se encuentran los siguientes atributos: ";
                if(!isset($obj_json->correo))
                {
                    $res->mensaje.="Correo/ ";
                }
                if(!isset($obj_json->clave))
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
            $response=$response->withStatus(403);
            $response=$response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode($res));
        }
        return $response;
    }
    public function VerificarJWT(Request $request, Response $response, array $args) : Response
    {
        $res=new stdClass();
        $res->exito=false;
        if(isset($request->getHeader("authorization")[0]))
        {
            $token=$request->getHeader("authorization")[0];
            $token=explode("Bearer ",$token)[1];
            $res_autentificadora=Autentificadora::verificarJWT($token);
            if($res_autentificadora->verificado)
            {
                $res->exito=true;
                $response=$response->withStatus(200);
            }
            else
            {
                $response=$response->withStatus(403);
            }
        }
        $response->getBody()->write(json_encode($res));
        return $response->withHeader('Content-Type', 'application/json');
    }
}