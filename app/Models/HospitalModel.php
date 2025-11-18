<?php
// app/Models/HospitalModel.php
namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

class HospitalModel extends Model
{
    protected $table         = 'network_list_medsave';
    protected $primaryKey    = 'CAREPROVIDERCODE';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'CAREPROVIDERCODE', 'CARENAME', 'CARECITY', 'CARESTATE',
        'PPN', 'CAREPHONE', 'CAREEMAIL', 'PANNO', 'city_id',
    ];
    protected $useTimestamps = false;

    public function tableName(): string
    {
        return $this->table;
    }

    public function datatableBuilder(?int $stateId, ?int $cityId, ?string $search): BaseBuilder
    {
        $builder = $this->db->table($this->table . ' n')
            ->select([
                'n.CAREPROVIDERCODE',
                'n.CARENAME',
                'n.CAREPHONE',
                'n.CAREEMAIL',
                'n.PPN',
                'c.city_id',
                'c.city_name',
                's.state_id',
                's.state_name',
            ])
            ->join('cities c', 'c.city_id = n.city_id', 'left')
            ->join('states s', 's.state_id = c.state_id', 'left');

        if ($stateId) {
            $builder->where('s.state_id', $stateId);
        }

        if ($cityId) {
            $builder->where('c.city_id', $cityId);
        }

        if ($search) {
            $builder->groupStart()
                ->like('n.CARENAME', $search)
                ->orLike('c.city_name', $search)
                ->orLike('s.state_name', $search)
                ->orLike('n.CAREPHONE', $search)
                ->orLike('n.CAREEMAIL', $search)
                ->groupEnd();
        }

        return $builder;
    }

    public function total(?int $stateId, ?int $cityId): int
    {
        return (int) $this->datatableBuilder($stateId, $cityId, null)->countAllResults();
    }

    public function filtered(?int $stateId, ?int $cityId, ?string $search): int
    {
        return (int) $this->datatableBuilder($stateId, $cityId, $search)->countAllResults();
    }
}
