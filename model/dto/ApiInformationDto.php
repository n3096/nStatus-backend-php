<?php

namespace model\dto;

use model\DateTimeSerializable;

class ApiInformationDto {
    public string $version = 'v1.0.0';
    public DateTimeSerializable $latestUpdate;
    public float $logSize;
    public float $apiSize;
    public int $maxCheckTimeout;
    public int $updateInterval;

    public function __construct(float $logSize, float $apiSize, int $maxCheckTimeout, int $updateInterval) {
        $this->latestUpdate = new DateTimeSerializable();
        $this->logSize = $logSize;
        $this->apiSize = $apiSize;
        $this->maxCheckTimeout = $maxCheckTimeout;
        $this->updateInterval = $updateInterval;
    }
}