<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Crud extends Model
{
    use HasFactory;

    protected $table = '';
    protected $pk = '';

    protected $dateFormat = 'datetime';

    protected $created_at = true;
    protected $updated_at = true;
    protected $deleted_at = true;
    protected $deleted = true;

    protected $created_at_fieldname = 'created_at';
    protected $updated_at_fieldname = 'updated_at';
    protected $deleted_at_fieldname = 'deleted_at';
    protected $deleted_fieldname = 'deleted';

    protected $log = true;
    protected $log_tablename = 'cometa_log';

    function __construct($table = '', $pk = 'id'){
        parent::__construct();
        $this->table = $table;
        $this->pk = $pk;

        if(!empty($table))
            $this->_init($table);

    }

    function read($limit = 0){
        $data = DB::table($this->table);

        if($this->deleted)
            $data = $data->where('deleted', 0);

        if($limit > 0)
            $data = $data->limit($limit);

        return $data->get();
    }

    function count(){
        $data = DB::table($this->table);

        if($this->deleted)
            $data = $data->where('deleted', 0);

        return $data->count();
    }

    function add($data = []){
        if ($this->created_at)
            $data[$this->created_at_fieldname] = $this->setDate();

        if($this->log)
            $this->logWrite($data, 'ADD');

        return DB::table($this->table)->insertGetId($data);
    }

    function update($id = '', $data = []){
        if($this->updated_at)
            $data[$this->updated_at_fieldname] = $this->setDate();

        if($this->log)
            $this->logWrite($data, 'UPDATE', $id);

        $affected = DB::table($this->table)
            ->where($this->pk, $id)
            ->update($data);

        if($affected)
            return true;
    }

    function updateArray($where, $data){
        if($this->updated_at)
            $data[$this->updated_at_fieldname] = $this->setDate();

        $id = json_encode($where);

        if($this->log)
            $this->logWrite($data, 'UPDATE', $id);

        $affected = DB::table($this->table)
            ->where($where)
            ->update($data);

        if($affected)
            return true;
    }

    function delete($id = ''){
        if($this->log)
            $this->logWrite('', 'DELETE', $id);

        if($this->deleted){
            DB::table($this->table)
                ->where($this->pk, $id)
                ->update([
                    $this->deleted_at_fieldname => $this->setDate(),
                    $this->deleted_fieldname => 1
                ]);
            return true;
        }else{
            DB::table($this->table)->where($this->pk, $id)->delete();
            return true;
        }
        return false;
    }

    function one($id = ''){
        $data = DB::table($this->table)
            ->where($this->pk, $id);

        if($this->deleted)
            $data->where('deleted', 0);

        return $data->first();
    }

    function oneArray($array = ''){
        $data = DB::table($this->table)
            ->where($array);

        if($this->deleted)
            $data->where('deleted', 0);

        return $data->first();
    }

    function plenty($id = ''){
        $data = DB::table($this->table)
            ->where($this->pk, $id);

        if($this->deleted)
            $data->where('deleted', 0);

        return $data->get();
    }

    function plentyArray($array){
        $data = DB::table($this->table)
            ->where($array);

        if($this->deleted)
            $data->where('deleted', 0);

        return $data->get();
    }

    function exist($id){
        if($this->deleted)
            return (DB::table($this->table)->where($this->pk, $id)->where('deleted', 0)->exists())?TRUE:FALSE;
        else
            return (DB::table($this->table)->where($this->pk, $id)->exists())?TRUE:FALSE;
    }

    function _init($table){
        $columns  = DB::getSchemaBuilder()->getColumnListing($this->table);

        if($this->created_at){
            if(!in_array($this->created_at_fieldname, $columns)){
                DB::statement('ALTER TABLE '.$table.' ADD '.$this->created_at_fieldname.' datetime');
            }
        }

        if($this->updated_at){
            if(!in_array($this->updated_at_fieldname, $columns)){
                DB::statement('ALTER TABLE '.$table.' ADD '.$this->updated_at_fieldname.' datetime');
            }
        }

        if($this->deleted_at){
            if(!in_array($this->deleted_at_fieldname, $columns)){
                DB::statement('ALTER TABLE '.$table.' ADD '.$this->deleted_at_fieldname.' datetime');
            }
        }

        if($this->deleted){
            if(!in_array($this->deleted_fieldname, $columns)){
                DB::statement('ALTER TABLE '.$table.' ADD '.$this->deleted_fieldname.' boolean DEFAULT 0');
            }
        }

        if($this->log){
            if(!Schema::hasTable($this->log_tablename)){
                DB::statement('
                CREATE TABLE `cometa_log` (
                    `id` INT NOT NULL AUTO_INCREMENT ,
                    `date` DATETIME NOT NULL ,
                    `tablename` VARCHAR(100) NOT NULL ,
                    `primarykey` VARCHAR(100) NOT NULL ,
                    `pkvalue` VARCHAR(50) NOT NULL ,
                    `function` VARCHAR(25) NOT NULL ,
                    `ip` VARCHAR(25) NOT NULL ,
                    `session` VARCHAR(512) NOT NULL ,
                    `json` VARCHAR(512) NOT NULL ,
                    PRIMARY KEY (`id`));
                ');
            }
        }
    }

    function logWrite($data, $function = '', $pkvalue = ''){
        if($this->table != $this->log_tablename){
            DB::table($this->log_tablename)->insert([
                'date' => date('Y-m-d H:i:s'),
				'tablename' => $this->table,
				'primarykey' => $this->pk,
				'pkvalue' => $pkvalue,
				'function' => $function,
				'ip' => '',
				'session' => json_encode((!empty($_SESSION))?$_SESSION:""),
				'json' => json_encode($data)
            ]);
        }
    }


    protected function setDate(int $userData = null){
        $currentDate = is_numeric($userData) ? (int) $userData : time();

        switch ($this->dateFormat){
            case 'int':
                return $currentDate;
                break;
            case 'datetime':
                return date('Y-m-d H:i:s', $currentDate);
                break;
            case 'date':
                return date('Y-m-d', $currentDate);
                break;
            default:
                return;
        }
    }

}
