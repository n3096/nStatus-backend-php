<?php

namespace model;

class ServiceCheck {
    public string $hostName;
    public string $port;
    public SocketProtocol $socketProtocol;
    public string $fullHostName;
    public DateTimeSerializable $timestamp;
    public int $latency;
    public ?string $ipv4;
    public ?string $ipv6;
    public string $forwardedHost;
    public Status $status;
    public array|object $response;
    public array $notes;

    public function __construct(Service $service, string $fullHostName, DateTimeSerializable $timestamp, int $latency, ?string $ipv4, ?string $ipv6, ?string $forwardedHost, Status $status, array|object $response, array $notes) {
        $this->hostName = $service->hostName;
        $this->port = $service->port;
        $this->socketProtocol = $service->socketProtocol;
        $this->fullHostName = $fullHostName;
        $this->timestamp = $timestamp;
        $this->latency = $latency;
        $this->ipv4 = $ipv4;
        $this->ipv6 = $ipv6;
        $this->forwardedHost = $forwardedHost;
        $this->status = $status;
        $this->response = $response;
        $this->notes = $notes;
    }
}