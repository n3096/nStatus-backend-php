<?php

namespace model\statistics;

use model\DateTimeSerializable;

class ServicesCheckStatistics {
    public DateTimeSerializable $timestamp;
    public int $runTime;
    public int $logSize;
    public int $apiSize;

    public function __construct() {
        $this->timestamp = new DateTimeSerializable();
    }

    public function finish(float $runTime, int $logSize, int $apiSize): void {
        $this->runTime = $runTime;
        $this->logSize = $logSize;
        $this->apiSize = $apiSize;
    }
}