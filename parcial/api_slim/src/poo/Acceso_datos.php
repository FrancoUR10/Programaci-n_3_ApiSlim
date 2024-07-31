<?php
namespace Acquisto\Franco;
use PDO;
use PDOException;
use PDOStatement;

class AccesoDatos
{
    private PDO $db;
    private static self $objAccesoDatos;

    private function __construct()
    {
        try
        {
            $this->db=new PDO("mysql:host=localhost;dbname=jugueteria_bd;charset=utf8","root","");
        }
        catch(PDOException $e)
        {
            echo "Error: ".$e->getMessage();
            die();
        }
    }

    public function retornarConsulta(string $sql):PDOStatement | false
    {
        return $this->db->prepare($sql);
    }
    public function retornarUltimoIdInsertado():string|false
    { 
        return $this->db->lastInsertId(); 
    }
    public static function retornarObjetoAcceso():self
    {
        if(!isset(self::$objAccesoDatos))
        {
            self::$objAccesoDatos=new self();
        }
        return self::$objAccesoDatos;
    }
    public function __clone()
    {
        trigger_error("Clonación de este objeto no permitida",E_USER_ERROR);
    }
}