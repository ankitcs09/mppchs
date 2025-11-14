<?php
 namespace App\Models;
 use CodeIgniter\Model;
 class UsersViewModel extends Model {
  protected $table = 'tmusers';
  protected $primaryKey = 'id';
  protected $allowedfields = ['id', 'empid', 'empname', 'emppost', 'empgender', 'empdob', 'empmobile', 'empemail','username','password'];
  protected $column_order = ['id', 'empid', 'empname', 'emppost', 'empgender', 'empdob', 'empmobile', 'empemail','username','password'];
  protected $order = array('id' => 'asc');
  function getinformation() {
   if (!isset($_GET['draw'])) {
    $_GET['draw']=0;
   }
   if (!isset($_GET['start'])) {
    $_GET['start']=0;
   }
   if (!isset($_GET['length'])) {
    $_GET['length']=10;
   }
   if (!isset($_GET['order'])) {
    $_GET['order']['0']['column'] = 0;
    $_GET['order']['0']['dir'] = 'asc';
   }
   if (!isset($_GET['search']['value'])) {
    $_GET['search']['value']='';
   }
   if ($_GET['search']['value']) {
    $search = $_GET['search']['value'];
    $lssearch = "empid LIKE '%$search%' OR empname LIKE '%$search%' OR emppost LIKE '%$search%' OR empgender LIKE '%$search%' OR empdob LIKE '%$search%' OR empmobile LIKE '%$search%' OR empemail LIKE '%$search%'";
   } else {
    $lssearch = "empid != -1";
   }
   if ($_GET['order']) {
    $result_order = $this->column_order[$_GET['order']['0']['column']];
    $result_dir = $_GET['order']['0']['dir'];
   } else if ($this->order) {
    $order = $this->order;
    $result_order = key($order);
    $result_dir = $order[key($order)];
   }
   if ($_GET['length']!=-1);
    $db = db_connect();
    $builder = $db->table($this->table);
    $query = $builder->select('*')
                     ->where($lssearch)
                     ->orderBy($result_order, $result_dir)
                     ->limit($_GET['length'], $_GET['start'])
                     ->get();
    return $query->getResult();
   }
   function allcnt() {
    $sQuery = "SELECT COUNT($this->primaryKey) as qallcnt FROM $this->table";
    $db = db_connect();
    $query = $db->query($sQuery)->getRow();
    return $query;
   }
   function filtercnt() {
    if (!isset($_GET['draw'])) {
     $_GET['draw']=0;
    }
    if (!isset($_GET['start'])) {
     $_GET['start']=0;
    }
    if (!isset($_GET['length'])) {
     $_GET['length']=10;
    }
    if (!isset($_GET['order'])) {
     $_GET['order']['0']['column'] = 0;
     $_GET['order']['0']['dir'] = 'asc';
    }
    if (!isset($_GET['search']['value'])) {
     $_GET['search']['value']='';
    }
    if ($_GET['search']['value']) {
     $search = $_GET['search']['value'];
     $lssearch = "empid LIKE '%$search%' OR empname LIKE '%$search%' OR emppost LIKE '%$search%' OR empgender LIKE '%$search%' OR empdob LIKE '%$search%' OR empmobile LIKE '%$search%' OR empemail LIKE '%$search%'";
    } else {
     $lssearch = "id != -1";
    }
    $sQuery = "SELECT COUNT($this->primaryKey) as qfiltercnt FROM $this->table WHERE $lssearch";    
    $db = db_connect();
    $query = $db->query($sQuery)->getRow();
    return $query;
   }
   public function getAllEmployees() {
    $db = db_connect();
    $builder = $db->table($this->table);
    $query = $builder->select('*')
                     ->orderBy('id', 'asc')
                     ->limit(5000, 0)
                     ->get();
    return $query->getResult();
   }
  function getexport() {
   if (!isset($_GET['draw'])) {
    $_GET['draw']=0;
   }
   if (!isset($_GET['start'])) {
    $_GET['start']=0;
   }
   if (!isset($_GET['length'])) {
    $_GET['length']=10;
   }
   if (!isset($_GET['order'])) {
    $_GET['order']['0']['column'] = 0;
    $_GET['order']['0']['dir'] = 'asc';
   }
   if (!isset($_GET['search']['value'])) {
    $_GET['search']['value']='';
   }
   if ($_GET['search']['value']) {
    $search = $_GET['search']['value'];
    $lssearch = "empid LIKE '%$search%' OR empname LIKE '%$search%' OR emppost LIKE '%$search%' OR empgender LIKE '%$search%' OR empdob LIKE '%$search%' OR empmobile LIKE '%$search%' OR empemail LIKE '%$search%'";
   } else {
    $lssearch = "id != -1";
   }
   if ($_GET['order']) {
    $result_order = $this->column_order[$_GET['order']['0']['column']];
    $result_dir = $_GET['order']['0']['dir'];
   } else if ($this->order) {
    $order = $this->order;
    $result_order = key($order);
    $result_dir = $order[key($order)];
   }
   if ($_GET['length']!=-1);
    $db = db_connect();
    $builder = $db->table($this->table);
    $query = $builder->select('*')
                     ->where($lssearch)
                     ->orderBy($result_order, $result_dir)
                     ->limit($_GET['length'], $_GET['start'])
                     ->get();
    return $query->getResult();
   }
}