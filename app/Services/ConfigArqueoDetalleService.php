<?php

namespace App\Services;

use App\Repositories\ConfigArqueoDetalleRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfigArqueoDetalleService
{
    public function __construct(private ConfigArqueoDetalleRepository $repo) {}

    public function getAll() { return $this->repo->getAll(); }
    public function getAllPaginated(int $perPage = 15) { return $this->repo->getAllPaginated($perPage); }
    public function create(array $data) { try { DB::beginTransaction(); $data['created_by']=Auth::id(); $data['version']=1; $m=$this->repo->create($data); DB::commit(); return $m; } catch (Exception $e) { DB::rollBack(); throw $e; } }
    public function find(int $id) { return $this->repo->find($id); }
    public function update(int $id, array $data) { try { DB::beginTransaction(); $data['updated_by']=Auth::id(); $m=$this->repo->find($id); if(!$m) throw new Exception('Registro no encontrado'); $data['version']=$m->version+1; $this->repo->update($id,$data); DB::commit(); return $this->repo->find($id); } catch (Exception $e) { DB::rollBack(); throw $e; } }
    public function delete(int $id) { try { DB::beginTransaction(); $this->repo->update($id,['deleted_by'=>Auth::id()]); $r=$this->repo->delete($id); DB::commit(); return $r; } catch (Exception $e) { DB::rollBack(); throw $e; } }
}

