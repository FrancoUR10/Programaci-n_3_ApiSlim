<?php
namespace Acquisto\Franco;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;
use Firebase\JWT\JWT;
use stdClass;

require_once __DIR__ ."/Acceso_datos.php";
require_once __DIR__ ."/Autentificadora.php";

class Juguete
{
  public int $id;
 	public string $marca;
  public float $precio;
  public string $foto;

  public function ListadoDeJuguetes(Request $request, Response $response, array $args) : Response
  {
    $res=self::TraerTodosLosJuguetes();
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
  public function AltaDeJuguetes(Request $request, Response $response, array $args) : Response
  {
    $juguete_json = json_decode($request->getParsedBody()["juguete_json"]);
    $marca=$juguete_json->marca;
    $precio=$juguete_json->precio;

    $destino=isset($_FILES["foto"]["name"]) ? $_FILES["foto"]["name"] : null;
    $extension=pathinfo($destino,PATHINFO_EXTENSION);
    $destino="../src/fotos/".$marca.".".$extension;

    $item = new Juguete();
    $item->marca = $marca;
    $item->precio = $precio;
		$item->foto = $destino;

    $res=$item->AgregarUnJugueteConFoto();
    if($res->exito)
    {
      $response=$response->withStatus(200);
    }
    else
    {
      $response=$response->withStatus(418);
    }
    $response->getBody()->write(json_encode($res));
    return $response->withHeader('Content-Type', 'application/json');
  }
  public function ModificarJuguetesPorID(Request $request, Response $response, array $args) : Response
  {
    $juguete = json_decode($request->getParsedBody()["juguete"]);
    $id_juguete=$juguete->id_juguete;
    $marca=$juguete->marca;
    $precio=$juguete->precio;

    $destino=isset($_FILES["foto"]["name"]) ? $_FILES["foto"]["name"] : null;
    $extension=pathinfo($destino,PATHINFO_EXTENSION);
    $destino="../src/fotos/".$marca."_modificacion".".".$extension;

    $item = new Juguete();
    $item->id = (int)$id_juguete;
    $item->marca = $marca;
    $item->precio = (float)$precio;
    $item->foto = $destino;

    $res=$item->ModificarUnJugueteConFoto();
    if($res->exito)
    {
      $response=$response->withStatus(200);
    }
    else
    {
      $response=$response->withStatus(418);
    }
    $response->getBody()->write(json_encode($res));
    return $response->withHeader('Content-Type', 'application/json');
  }
  public function BorradoDeJuguetesPorID(Request $request, Response $response, array $args) : Response
  {
    $id_juguete=$args["id_juguete"];

    $res=self::EliminarUnJugueteConFoto((int)$id_juguete);
    if($res->exito)
    {
      $response=$response->withStatus(200);
    }
    else
    {
      $response=$response->withStatus(418);
    }
    $response->getBody()->write(json_encode($res));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public static function TraerTodosLosJuguetes() : stdClass
  {
    $res=new stdClass();
    $res->exito=false;
    $res->mensaje="Error al listar";
    $res->dato=null;
    $array_res=array();
    $sql=AccesoDatos::retornarObjetoAcceso()->retornarConsulta("SELECT * FROM juguetes");
    if($sql->execute())
    {
      $res->exito=true;
      $res->mensaje="Listado de juguetes";
      $array_res=$sql->fetchAll(PDO::FETCH_CLASS, "Acquisto\Franco\Juguete");	
      $res->dato=json_encode($array_res);
    }
    return $res;
  }
  public function AgregarUnJugueteConFoto():stdClass
  {
    $res=$this->AgregarUnJuguete();
    if($res->exito && isset($_FILES["foto"]["name"]))
    {
      if(move_uploaded_file($_FILES["foto"]["tmp_name"],$this->foto))
      {
        //chmod($this->foto,0666);
      }
    }
    return $res;
  }
  public function AgregarUnJuguete():stdClass
  {
    $res=new stdClass();
    $res->exito=false;
    $res->mensaje="Error al agregar el dato";
    
    $objAcceso=AccesoDatos::retornarObjetoAcceso();
    $sql=$objAcceso->retornarConsulta("INSERT INTO juguetes (marca, precio, path_foto) VALUES (:marca, :precio, :path_foto)");
    $sql->bindParam(":marca",$this->marca,PDO::PARAM_STR);
    $sql->bindParam(":precio",$this->precio,PDO::PARAM_INT);
    $sql->bindParam(":path_foto",$this->foto,PDO::PARAM_STR);
    $sql->execute();
    if($sql->rowCount() > 0)
    {
      $res->exito=true;
      $res->mensaje="Dato agregado con exito";
    }
    return $res;
  }
  public function ModificarUnJugueteConFoto():stdClass
  {
    $fotoAnterior=self::TraerRutaFoto($this->id);
    $res=$this->ModificarUnJuguete();
    if($res->exito)
    {
      if(file_exists($fotoAnterior))
      {
        unlink($fotoAnterior);
      }
      if(isset($_FILES["foto"]["name"]))
      {
        if(move_uploaded_file($_FILES["foto"]["tmp_name"],$this->foto))
        {
          //chmod($this->foto,0666);
        }
      }
    }
    return $res;
  }
  public function ModificarUnJuguete():stdClass
  {
    $res=new stdClass();
    $res->exito=false;
    $res->mensaje="Error al modificar el dato";
    
    $objAcceso=AccesoDatos::retornarObjetoAcceso();
    $sql=$objAcceso->retornarConsulta("UPDATE juguetes SET marca=:marca, precio=:precio, path_foto=:foto WHERE id=:id");
    $sql->bindParam(":id",$this->id,PDO::PARAM_INT);
    $sql->bindParam(":marca",$this->marca,PDO::PARAM_STR);
    $sql->bindParam(":precio",$this->precio,PDO::PARAM_INT);
    $sql->bindParam(":foto",$this->foto,PDO::PARAM_STR);
    if($sql->execute())
    {
      $res->exito=true;
      $res->mensaje="Dato modificado con exito";
    }
    return $res;
  }
  public static function EliminarUnJugueteConFoto(int $id):stdClass
  {
    $foto=self::TraerRutaFoto($id);
    $res=self::EliminarUnJuguete($id);
    if($res->exito)
    {
      if(file_exists($foto))
      {
        unlink($foto);
      }
    }
    return $res;
  }
  public static function EliminarUnJuguete(int $id):stdClass
  {
    $res=new stdClass();
    $res->exito=false;
    $res->mensaje="Error al eliminar el dato";
    
    $objAcceso=AccesoDatos::retornarObjetoAcceso();
    $sql=$objAcceso->retornarConsulta("DELETE FROM juguetes WHERE id=:id");
    $sql->bindParam(":id",$id,PDO::PARAM_INT);
    $sql->execute();
    if($sql->rowCount() > 0)
    {
      $res->exito=true;
      $res->mensaje="Dato eliminado con exito";
    }
    return $res;
  }
  public static function TraerRutaFoto(int $id):string
  {
    $foto="";
    $sql=AccesoDatos::retornarObjetoAcceso()->retornarConsulta("SELECT * FROM juguetes");
    $sql->execute();
    $array_obj=$sql->fetchAll();
    foreach($array_obj as $item)
    {
      if($item!==null)
      {
        if($item["id"]===$id)
        {
          $foto=$item["path_foto"];
          break;
        }
      }
    }
    return $foto;
  }
}