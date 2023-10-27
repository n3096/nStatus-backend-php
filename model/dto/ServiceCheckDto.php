<?php

namespace model\dto;

use model\DateTimeSerializable;
use model\ServiceCheck;
use model\Status;

class ServiceCheckDto {
    public DateTimeSerializable $timestamp;
    public int $latency;
    public Status $status;
    public function __construct(ServiceCheck $serviceCheck) {
        $this->timestamp = $serviceCheck->timestamp;
        $this->latency = $serviceCheck->latency;
        $this->status = $serviceCheck->status;
    }
}