<?php

namespace App\Services;

use App\Interfaces\AgendaRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class AgendaService
{
    private $agendaRepository;

    public function __construct(AgendaRepositoryInterface $agendaRepository)
    {
        $this->agendaRepository = $agendaRepository;
    }

    public function getEvents($startDate, $endDate)
    {
        return $this->agendaRepository->getAll($startDate, $endDate);
    }

    public function getEvent($id)
    {
        return $this->agendaRepository->getById($id);
    }

    public function createEvent(array $data)
    {
        $data['created_by'] = Auth::id();
        return $this->agendaRepository->create($data);
    }

    public function updateEvent($id, array $data)
    {
        return $this->agendaRepository->update($id, $data);
    }

    public function deleteEvent($id)
    {
        return $this->agendaRepository->delete($id);
    }
}
