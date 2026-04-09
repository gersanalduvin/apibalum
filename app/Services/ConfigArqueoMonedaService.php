<?php

namespace App\Services;

use App\Repositories\ConfigArqueoMonedaRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfigArqueoMonedaService
{
    public function __construct(private ConfigArqueoMonedaRepository $repo) {}

    public function getAll() { return $this->repo->getAll(); }
    public function getAllPaginated(int $perPage = 15) { return $this->repo->getAllPaginated($perPage); }
    public function searchPaginated(string $term, int $perPage = 15) { return $this->repo->searchPaginated($term, $perPage); }
    public function searchWithFiltersPaginated(array $filters, int $perPage = 15) { return $this->repo->searchWithFiltersPaginated($filters, $perPage); }
    public function getAllByMoneda(bool $moneda) { return $this->repo->getAllByMoneda($moneda); }
    public function create(array $data) { try { DB::beginTransaction(); $data['created_by']=Auth::id(); $data['version']=1; $m=$this->repo->create($data); DB::commit(); return $m; } catch (Exception $e) { DB::rollBack(); throw $e; } }
    public function find(int $id) { return $this->repo->find($id); }
    public function update(int $id, array $data) { try { DB::beginTransaction(); $data['updated_by']=Auth::id(); $m=$this->repo->find($id); if(!$m) throw new Exception('Registro no encontrado'); $data['version']=$m->version+1; $this->repo->update($id,$data); DB::commit(); return $this->repo->find($id); } catch (Exception $e) { DB::rollBack(); throw $e; } }
    public function delete(int $id) { try { DB::beginTransaction(); $this->repo->update($id,['deleted_by'=>Auth::id()]); $r=$this->repo->delete($id); DB::commit(); return $r; } catch (Exception $e) { DB::rollBack(); throw $e; } }
}
