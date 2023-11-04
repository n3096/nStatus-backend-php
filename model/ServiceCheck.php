<?php

namespace model;

class ServiceCheck {
    public DateTimeSerializable $timestamp;
    public string $hostName;
    public int $port;
    public SocketProtocol $socketProtocol;
    public string $fullHostName;
    public int $latency;
    public ?string $ipv4;
    public ?string $ipv6;
    public ?string $forwardedHost;
    public Status $status;
    public array|object $response;
    public array $notes;

    public function __construct(string $hostName, int $port, SocketProtocol $socketProtocol, string $fullHostName, DateTimeSerializable $timestamp, int $latency, ?string $ipv4, ?string $ipv6, ?string $forwardedHost, Status $status, array|object $response, array $notes) {
        $this->hostName = $hostName;
        $this->port = $port;
        $this->socketProtocol = $socketProtocol;
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

    public static function createByService(Service $service, string $fullHostName, DateTimeSerializable $timestamp, int $latency, ?string $ipv4, ?string $ipv6, ?string $forwardedHost, Status $status, array|object $response, array $notes): ServiceCheck {
        return new ServiceCheck($service->hostName, $service->port, $service->socketProtocol, $fullHostName, $timestamp, $latency, $ipv4, $ipv6, $forwardedHost, $status, $response, $notes);
    }
}