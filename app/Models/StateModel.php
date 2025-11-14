<?php
// app/Models/StateModel.php
namespace App\Models;

use CodeIgniter\Model;

class StateModel extends Model
{
    protected $table         = 'states';
    protected $primaryKey    = 'state_id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $allowedFields = ['state_name'];
}
