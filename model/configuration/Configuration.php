<?php

namespace model\configuration;

class Configuration {
    public array $basePaths;
    public array $servers;
    public function __construct(array $servers, array $basePaths = []) {
        $this->servers = $servers;
        $this->basePaths = $basePaths;
    }
}