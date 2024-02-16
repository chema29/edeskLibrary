<?php
namespace Edesk\dbQuery;

use Throwable;
use Edesk\dbQuery\DbConnect;

class MyQuery extends DbConnect
{
    public $num_rows;
    protected $getConsultarRegistros;
    protected $getConsultaRegistro;
    protected $dbProcedureData;
    protected $call_nemaProcedute;
    private $mysqli;

    public function __construct()
    {
        parent::__construct();
        (array) $this->getConsultaRegistro = [];
        (array) $this->getConsultarRegistros = [];
        (int) $this->num_rows = 0;
        (string) $this->call_nemaProcedute = null;
        $this->mysqli = parent::getConnection();
    }

    public function Reset_dbProcedureData()
    {
        $this->dbProcedureData = "";
    }

    public function append_dbProcedureData(string $data)
    {

        if (!empty($this->dbProcedureData)) {
            $this->dbProcedureData .= ",";
        }
        if (is_numeric($data) && !($data[0] == 0)) {
            $this->dbProcedureData .= $data;
        } else {
            $this->dbProcedureData .= "'" . $data . "'";
        }
    }

    public function append_dbProcedureResult($data) // cantura el resultado out de procedure
    {

        if (!empty($this->dbProcedureData)) {
            $this->dbProcedureData .= ",";
        }
        if (is_numeric($data)) {
            $this->dbProcedureData .= $data;
        } else {
            $this->dbProcedureData .= "  $data ";
        }
    }

    private function mysqli_warning_show($conect)
    {
        $resultwarning = "";
        if ($conect->warning_count) {
            if ($result = $conect->query("SHOW WARNINGS")) {
                $row = $result->fetch_row();
                $resultwarning = $row[0] . ': ' . $row[1] . ' ' . $row[2];
                $result->free();
            }
        }
        return $resultwarning;
    }

    public function set_consultaRegistro(string $sql = ""): void
    {
        try {
            $status = false;
            $error = "";
            $data = "";
            $Affected_rows = 0;
            $sql = $sql;
            $mysqli = $this->mysqli;
            $result = $mysqli->query($sql); //ejecutamos la query
            if (!$result) {
                $error = 'No se pudo consultar:' . $mysqli->error;
                $array = array(
                    'status' => false,
                    'mysqli' => array(
                        'error' => $error,
                        'warning' => "",
                        'info' => "",
                        'Affected_rows' => $Affected_rows,
                        'num_rows' => $this->num_rows
                    ),
                    'sql' => $sql,
                    'mysqli_info' => $mysqli->info
                );
                $this->getConsultaRegistro = $array;
                exit();
            }
            $this->num_rows = $result->num_rows;

            $info = $mysqli->info; // recupera informacion adicional sobre la última consulta ejecutrada OJO xD no funciona con CALL X'D seria chevere
            $warning = $this->mysqli_warning_show($mysqli); // devuelve informacion sobre los errores de la query ejectuda

            if (empty($warning)) { // validamos que no tengamos warning
                $Affected_rows = $mysqli->affected_rows;
                if ($Affected_rows >= 0) { // validamos que no tengamos errores
                    $status = true;
                    $data = $result->fetch_row();
                }
            }

            $array = array(
                'status' => $status,
                'mysqli' => array(
                    'error' => $error,
                    'warning' => $warning,
                    'info' => $info,
                    'Affected_rows' => $Affected_rows,
                    'num_rows' => $this->num_rows
                ),
                'sql' => $sql,
                'result' => $data
            );

            $result->free();
            $this->getConsultaRegistro = $array;
        } catch (Throwable $e) {
            $array = array('status' => false, 'result' => $e);
            $this->getConsultaRegistro =  $array;
        }
    }

    public  function get_consultaRegistro(): array
    {
        if (empty($this->getConsultaRegistro)) {
            return array('status' => false, "getConsultaRegistro" => $this->getConsultaRegistro);
        }
        $res=$this->getConsultaRegistro;
        $this->clearResult();
        return $res;
    }

    public function set_consultarRegistros(string $sql = "")
    {
        try {
            $status = false;
            $error = "";
            $data = "";
            $Affected_rows = 0;
            $sql = $sql;
            $mysqli = $this->mysqli;

            $result = $mysqli->query($sql); //ejecutamos la query
            $data = array();

            if (!$result || empty($result)) {
                $error = 'No se pudo consultar:' . $mysqli->error;
                $array = array(
                    'status' => false, 'mysqli' => array(
                        'error' => $error,
                        'warning' => "",
                        'info' => "",
                        'Affected_rows' => $Affected_rows,
                        'num_rows' => 0
                    ),
                    'sql' => $sql,
                    'result' => [],
                );
                $this->getConsultarRegistros = $array;
                return false;
            }
            $info = $mysqli->info; // recupera informacion adicional sobre la última consulta ejecutada, OJO xD no funciona con CALL X'D seria chevere
            $warning = $this->mysqli_warning_show($mysqli); // devuelve informacion sobre los errores de la query ejectuda
            $this->num_rows = $result->num_rows;
            if (empty($warning)) { // validamos que no tengamos warning
                $Affected_rows = $mysqli->affected_rows;
                if ($Affected_rows >= 0) { // validamos que no tengamos errores
                    $status = true;
                    $indice=0;
                    $limite= mysqli_num_fields ($result);

                    while ($row = mysqli_fetch_array($result,MYSQLI_NUM)) {
                        $data[] = $row;
                    }

                    mysqli_free_result ($result);

                }
            }

            $array = array(
                'status' => $status, 'mysqli' => array(
                    'error' => $error,
                    'warning' => $warning,
                    'info' => $info,
                    'Affected_rows' => $Affected_rows,
                    'num_rows' => $this->num_rows
                ),
                'Sql' => $sql,
                'result' => $data
            );

            $this->getConsultarRegistros = $array;
            
        } catch (Throwable $e) {
            $this->getConsultarRegistros = array('status' => false, 'result' => $e);
        }
    }

    public function get_consultarRegistros(): array
    {
        $res = $this->getConsultarRegistros;
        $this->clearResult();
        return $res;
    }

    // set consultaRegistro con nombre de campos de la tabla
    public function set_consultaRegistroAssociated(string $sql = ""): void
    {
        try {
            $status = false;
            $error = "";
            $data = "";
            $Affected_rows = 0;
            $sql = $sql;
            $mysqli = $this->mysqli;
            $result = $mysqli->query($sql); //ejecutamos la query
            if (!$result) {
                $error = 'No se pudo consultar:' . $mysqli->error;
                $array = array(
                    'status' => false,
                    'mysqli' => array(
                        'error' => $error,
                        'warning' => "",
                        'info' => "",
                        'Affected_rows' => $Affected_rows,
                        'num_rows' => $this->num_rows
                    ),
                    'sql' => $sql,
                    'mysqli_info' => $mysqli->info
                );
                $this->getConsultaRegistro = $array;
                exit();
            }
            $info = $mysqli->info; // recupera informacion adicional sobre la última consulta ejecutrada OJO xD no funciona con CALL X'D seria chevere
            $warning = $this->mysqli_warning_show($mysqli); // devuelve informacion sobre los errores de la query ejectuda

            if (empty($warning)) { // validamos que no tengamos warning
                $Affected_rows = $mysqli->affected_rows;
                if ($Affected_rows >= 0) { // validamos que no tengamos errores
                    $status = true;
                    $data = $result->fetch_assoc();
                }
            }

            $array = array(
                'status' => $status, 'mysqli' => array(
                    'error' => $error,
                    'warning' => $warning,
                    'info' => $info,
                    'Affected_rows' => $Affected_rows,
                    'num_rows' => $this->num_rows
                ), 'Sql' => $sql, 'result' => $data
            );

            $this->getConsultaRegistro = $array;
        } catch (Throwable $e) {
            $array = array('status' => false, 'result' => $e, 'sql' => $sql);
            $this->getConsultaRegistro = $array;
        }
    }

    // get consultaRegistro con nombre de campos de la tabla
    public function get_consultaRegistroAssociated(): array
    {
        if (empty($this->getConsultaRegistro)) {
            return array('status' => false, "getConsultaRegistro" => $this->getConsultaRegistro);
        }
        $res = $this->getConsultaRegistro;
        $this->clearResult();
        return $res;
    }

    // set consultarRegistros con nombre de campos de la tabla
    public function set_consultarRegistrosAssociated(string $sql = "")
    {
        try {
            $status = false;
            $error = "";
            $data = "";
            $Affected_rows = 0;
            $sql = $sql;
            $mysqli = $this->mysqli;

            $result = $mysqli->query($sql); //ejecutamos la query
            $data = array();

            if (!$result || empty($result)) {
                $error = 'No se pudo consultar:' . $mysqli->error;
                $array = array(
                    'status' => false, 'mysqli' => array(
                        'error' => $error,
                        'warning' => "",
                        'info' => "",
                        'Affected_rows' => $Affected_rows,
                        'num_rows' => 0
                    ),
                    'sql' => $sql,
                    'result' => [],
                );
                $this->getConsultarRegistros = $array;
                return false;
            }
            $info = $mysqli->info; // recupera informacion adicional sobre la última consulta ejecutada, OJO xD no funciona con CALL X'D seria chevere
            $warning = $this->mysqli_warning_show($mysqli); // devuelve informacion sobre los errores de la query ejectuda
            $this->num_rows = $result->num_rows;
            if (empty($warning)) { // validamos que no tengamos warning
                $Affected_rows = $mysqli->affected_rows;
                if ($Affected_rows >= 0) { // validamos que no tengamos errores
                    $status = true;
                    $indice = 0;
                    $limite = mysqli_num_fields($result);
                    while ($row = mysqli_fetch_assoc($result)) {
                        $data[] = $row;
                    }
                    mysqli_free_result($result);
                }
            }

            $array = array(
                'status' => $status, 'mysqli' => array(
                    'error' => $error,
                    'warning' => $warning,
                    'info' => $info,
                    'Affected_rows' => $Affected_rows,
                    'num_rows' => $this->num_rows
                ),
                'Sql' => $sql,
                'result' => $data
            );

            $this->getConsultarRegistros = $array;
        } catch (Throwable $e) {
            $this->getConsultarRegistros = array('status' => false, 'result' => $e);
        }
    }

    // get consultarRegistros con nombre de campos de la tabla
    public function get_consultarRegistrosAssociated(): array
    {
        $res = $this->getConsultarRegistros;
        $this->clearResult();
        return $res;
    }

    public function ejecutar(string $sql = ""): array
    {
        try {
            $status = false;
            $error = "";
            $data = "";
            $sql = $sql;
            $Affected_rows = 0;
            $mysqli = $this->mysqli;

            if(!$mysqli){
                $array = array('status' => false, 'result' => "conexion cerrada");
                return $array;
            }
            $result = $mysqli->query($sql); //ejecutamos la query
            if (!$result) {
                printf("Error - SQLSTATE %s.\n", $mysqli->sqlstate);
                $error = 'No se pudo consultar:' . $mysqli->error.' mysqli:'.$mysqli->info;
                $array = array('status' => false, 'result' => $error, 'sql' => $sql,'mysqli_info'=>$mysqli->info,"sqlstate"=> $mysqli->sqlstate);
                return $array;
            }
            $info = $mysqli->info; // recupera informacion adicional sobre la última consulta ejecutrada OJO xD no funciona con CALL X'D seria chevere
            $warning = $this->mysqli_warning_show($mysqli); // devuelve informacion sobre los errores de la query ejectuda


            $Affected_rows = $mysqli->affected_rows;
            $insert_id=0;

            if ($Affected_rows >= 0) { // validamos que no tengamos errores
                $status = true;
                $insert_id=$mysqli->insert_id;
            }


            $array = array(
                'status' => $status, 'mysqli' => array(
                    'error' => $error,
                    'warning' => $warning,
                    'info' => $info,
                    'Affected_rows' => $Affected_rows,
                    'num_rows' => $this->num_rows,
                    'result' => $data,
                    'insert_id'=>$insert_id //secuencial generado en la tabla por el registro insertado
                ), 'Sql' => $sql
            );

            return $array;
        } catch (Throwable $e) {
            $array = array('status' => false, 'result' => $e, 'sql' => $sql);
            return $array;
        }
    }

    public function get_call_nameProcedute(string $name = '')
    {
        $this->call_nemaProcedute = $name;
    }

    public function call_runProcedure(): array
    {
        try {
            $status = 0;
            $error = "";
            $resultProcedure = "";
            $Affected_rows = 0;
            /* prepare la consulta */
            $sql = "CALL " . $this->call_nemaProcedute  . "(" . $this->dbProcedureData . ");";
            //$call_store_procedure = ; //ejecutamos la query
            $mysqli = $this->mysqli;
            if (!$mysqli->query($sql)) {
                $error = 'No se pudo consultar:' . $mysqli->error;
                $array = array('status' => false, 'result' => $error, 'call' => $sql);
                return $array;
            }

            $info = $mysqli->info; // recupera informacion adicional sobre la última consulta ejecutrada OJO xD no funciona con CALL X'D seria chevere
            $warning = $this->mysqli_warning_show($mysqli); // devuelve informacion sobre los errores de la query ejectuda

            if (empty($warning)) { // validamos que no tengamos warning
                $Affected_rows = $mysqli->affected_rows;
                $result = $mysqli->query("SELECT @_result AS `_result`;"); //ejecutamos la query
                $data = $result->fetch_row();
                if (empty($data[0])) {
                    if ($Affected_rows >= 0) { // validamos que no tengamos errores
                        $status = 1;
                    }
                } else {
                    $resultProcedure = $data[0];
                }
            }

            $array = array(
                'status' => $status, 'mysqli' => array(
                    'error' => $error,
                    'warning' => $warning,
                    'info' => $info,
                    'Affected_rows' => $Affected_rows,
                    'num_rows' => $this->num_rows,
                    'result' => $resultProcedure
                ), 'Sql' => $sql
            );
            return $array;
        } catch (Throwable  $e) {
            $array = array('status' => false, 'result' => $e, 'call' => $sql);
            return $array;
        }
    }

    public function call_storeProcedure(): array
    {

        try {
            $status = 0;
            $valores = NULL;
            $error = "";
            $Affected_rows = 0;
            $sql = "CALL " . $this->call_nemaProcedute . "(" . $this->dbProcedureData . ");";

            $mysqli = $this->mysqli;
            $call_store_procedure = $mysqli->query($sql); //ejecutamos la query
            $this->num_rows = $call_store_procedure->num_rows;
            $Affected_rows = $mysqli->affected_rows; // recueper el numero de filas afectada, si -1 = Error
            $info = $mysqli->info; // recupera informacion adicional sobre la última consulta ejecutrada OJO xD no funciona con CALL X'D seria chevere
            $warning = $this->mysqli_warning_show($mysqli); // devuelve informacion sobre los errores de la query ejectuda

            if (!$call_store_procedure) { // validamos si la consulta se ejecuto sin errores
                $error = 'No se pudo consultar:' . $mysqli->error;
                $array = array('status' => false, 'result' => $error, 'call' => $sql);
                return $array;
            } else {
                $status = 1;
                $valores = $call_store_procedure->fetch_all(MYSQLI_NUM);
            }
            //parent::connectClose($mysqli);
            $array = array(
                'status' => $status, 'result' => $valores, 'mysqli' => array(
                    'error' => $error,
                    'warning' => $warning,
                    'info' => $info,
                    'Affected_rows' => $Affected_rows,
                    'num_rows' => $this->num_rows
                ), 'Sql' => $sql
            );

            return $array;
        } catch (Throwable  $e) {
            $array = array('status' => false, 'result' => $e);
            return $array;
        }
    }


    function call_storeProcedure_old($name = '')
    {
        $link = $this->mysqli;
        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit();
        }

        $sql = "CALL " . $name . "(" . $this->dbProcedureData . ");";
        $call_store_procedure = mysqli_query($link, $sql);
        $valores = NULL;
        $valores = mysqli_fetch_array($call_store_procedure, MYSQLI_ASSOC);
        mysqli_close($link);
        return $valores;
    }

    public function clearResult()
    {
        $this->getConsultaRegistro=[];
        $this->getConsultarRegistros=[];
    }

    public function closeConnection():void {
        parent::connectClose($this->mysqli);
    }
}
