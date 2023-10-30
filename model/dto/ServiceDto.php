<?php

namespace model\dto;

use helper\UUID4;
use model\DateTimeSerializable;
use model\Service;
use model\Status;

class ServiceDto {
    public string $id;
    public string $name;
    public Status $status;
    public string $icon;
    public DateTimeSerializable $latestUpdate;
    public array $serviceCheckHistory = [];

    public function __construct(Service $service, Status $status, array $serviceCheckHistory) {
        $this->id = UUID4::validate($service->id);
        $this->name = $service->name;
        $this->status = $status;
        $this->icon = $service->icon;
        $this->latestUpdate = new DateTimeSerializable();
        $this->serviceCheckHistory = array_map(function($serviceCheck) { return new ServiceCheckDto($serviceCheck); }, $serviceCheckHistory);
    }
}