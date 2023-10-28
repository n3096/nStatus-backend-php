<?php

namespace model\configuration;

class Configuration {
    public bool $isDebug;
    public int $updateInterval;
    public array $basePaths;
    public array $servers;
    public function __construct(bool $isDebug, int $updateInterval, array $servers, array $basePaths = []) {
        $this->isDebug = $isDebug;
        $this->updateInterval = $updateInterval;
        $this->servers = $servers;
        $this->basePaths = $basePaths;
    }
}