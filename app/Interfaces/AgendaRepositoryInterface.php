<?php

namespace App\Interfaces;

interface AgendaRepositoryInterface
{
    public function getAll($startDate, $endDate);
    public function getById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
