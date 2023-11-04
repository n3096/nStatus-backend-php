<?php

namespace model\dto;

use model\DateTimeSerializable;
use model\ServiceCheck;
use model\Status;

class ServiceCheckDto {
    public DateTimeSerializable $timestamp;
    public int $latency;
    public Status $status;
    public function __construct(DateTimeSerializable $timestamp, int $latency, Status $status) {
        $this->timestamp = $timestamp;
        $this->latency = $latency;
        $this->status = $status;
    }

    public static function of(ServiceCheck $serviceCheck): ServiceCheckDto {
        return new ServiceCheckDto($serviceCheck->timestamp, $serviceCheck->latency, $serviceCheck->status);
    }
}