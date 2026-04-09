<?php

namespace App\Repositories\Contracts;

interface AvisoRepositoryInterface
{
    public function getAll(array $filters = []);
    public function findById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function getByGroups(array $groupIds, array $filters = []);
}
