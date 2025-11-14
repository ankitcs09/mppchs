<?php
// app/Models/CityModel.php
namespace App\Models;

use CodeIgniter\Model;

class CityModel extends Model
{
    protected $table         = 'cities';
    protected $primaryKey    = 'city_id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $allowedFields = ['city_name', 'state_id', 'is_request_enabled'];
}
