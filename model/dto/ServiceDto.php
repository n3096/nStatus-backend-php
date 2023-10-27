<?php

namespace model\dto;

use model\DateTimeSerializable;
use model\Service;

class ServiceDto {
    public string $id;
    public string $name;
    public string $icon;
    public DateTimeSerializable $latestUpdate;
    public array $serviceCheckDtoHistory = [];

    public function __construct(Service $service, array $serviceChecks) {
        $this->id = $service->id;
        $this->name = $service->name;
        $this->icon = $service->icon;
        $this->latestUpdate = new DateTimeSerializable();
        $this->serviceCheckDtoHistory = array_map(function($serviceCheck) { return new ServiceCheckDto($serviceCheck); }, $serviceChecks);
    }
}