<?php

namespace model\configuration;

class Configuration {
    public bool $isDebug;
    public array $basePaths;
    public array $servers;
    public function __construct(bool $isDebug, array $servers, array $basePaths = []) {
        $this->isDebug = $isDebug;
        $this->servers = $servers;
        $this->basePaths = $basePaths;
    }
}