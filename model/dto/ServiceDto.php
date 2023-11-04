<?php

namespace model\dto;

use helper\UUID4;
use model\DateTimeSerializable;
use model\Service;
use model\Status;

class ServiceDto {
    public string $id;
    public string $name;
    public string $icon;
    public Status $status;
    public array $serviceCheckHistory = [];
    public DateTimeSerializable $latestUpdate;

    public function __construct(string $id, string $name, string $icon, Status $status, array $serviceCheckHistory) {
        $this->id = UUID4::validate($id);
        $this->name = $name;
        $this->icon = $icon;
        $this->status = $status;
        $this->serviceCheckHistory = $this->mapServiceCheckHistory($serviceCheckHistory);
        $this->latestUpdate = new DateTimeSerializable();
    }

    private function mapServiceCheckHistory(array $serviceCheckHistory): array {
        return ($serviceCheckHistory[0] instanceof ServiceCheckDto) ? $serviceCheckHistory
            : array_map(function($serviceCheck) { return ServiceCheckDto::of($serviceCheck); }, $serviceCheckHistory);
    }

    public static function of(Service $service, Status $status, array $serviceCheckHistory): ServiceDto {
        return new ServiceDto($service->id, $service->name, $service->icon, $status, $serviceCheckHistory);
    }
}