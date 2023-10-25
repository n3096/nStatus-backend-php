<?php

namespace service;

use InvalidArgumentException;
use model\DateTimeSerializable;
use model\Service;
use model\ServiceCheck;
use model\SocketProtocol;
use model\Status;

class ServiceCheckService {
    private function __construct() {}

    static public function checkServices(): void {
        ServiceCheckThreaderService::checkServicesMultithreaded(ConfigurationService::getServices());
    }

    static public function checkService(string $data, string $salt): ?ServiceCheck {
        $service = ServiceCheckThreaderService::parseService($data, $salt);
        if ($service == null)
            return null;

        $notes = [];
        $response = [];
        $forwardedHost = self::getForwardedHost($service);
        $fullHostName = self::getActualHostName($forwardedHost ?: $service->hostName, $service->socketProtocol);

        $dateTime = new DateTimeSerializable();
        $latencyStart = microtime(true);
        $resource = fsockopen($fullHostName, $service->port, $errorCode, $errMessage, $service->timeout);
        $latency = intval((microtime(true) - $latencyStart) * 1000); // convert to micro seconds

        if (!$resource) {
            $status = Status::UNREACHABLE;
        } else {
            $status = Status::REACHABLE;
            $response = self::getResponse($resource, $service, $status, $notes);
            fclose($resource);
        }

        if ($errorCode != 10061) { // filter common unreachable error code
            if ($errorCode)
                $notes["errorCode"] = $errorCode;
            if ($errMessage)
                $notes["errMessage"] = $errMessage;
        }

        if ($status === Status::REACHABLE && sizeof($notes) > 0)
            $status = Status::WARNING;

        if(empty($response))
            $response = (object)[];

        return new ServiceCheck($service, $fullHostName, $dateTime, $latency, self::getIpv4($service), self::getIpv6($service), $forwardedHost, $status, $response, $notes);
    }

    static private function getActualHostName(string $hostName, SocketProtocol $socketProtocol): string {
        return ($socketProtocol === SocketProtocol::HTTPS ? "ssl://" : "") . $hostName;
    }

    static private function getIpv4(Service $service): ?string {
        $host = gethostbyname($service->hostName);
        if ($host === $service->hostName)
            return dns_get_record($host, DNS_A)[0]["ip"] ?? null;
        return $host;
    }

    static private function getIpv6(Service $service): ?string {
        return dns_get_record($service->hostName, DNS_AAAA)[0]["ipv6"] ?? null;
    }

    static private function getForwardedHost(Service $service): ?string {
        return dns_get_record($service->hostName, DNS_A)[0]["host"] ?? dns_get_record($service->hostName, DNS_AAAA)[0]["host"] ?? null;
    }

    static private function getResponse($resource, Service $service, Status &$status, array &$notes): array {
        if (!is_resource($resource))
            throw new InvalidArgumentException(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));

        return match ($service->socketProtocol) {
            SocketProtocol::HTTP, SocketProtocol::HTTPS => self::getResponseForHttpAndHttps($resource, $service, $status, $notes),
            default => [],
        };
    }

    static private function getResponseForHttpAndHttps($resource, Service $service, Status &$status, array &$notes): array
    {
        if (!is_resource($resource))
            throw new InvalidArgumentException(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));

        $result = [];

        $request = "GET / HTTP/1.1\r\n";
        $request .= "Host: $service->hostName\r\n";
        $request .= "Connection: close\r\n";
        $request .= "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n";
        $request .= "\r\n";
        fwrite($resource, $request);

        $response = '';
        while (!feof($resource)) {
            $response .= fgets($resource, 128);
        }

        preg_match('/HTTP\/1\.\d (\d{3})/', $response, $statusCodeMatches);
        $httpStatusCode = isset($statusCodeMatches[1]) ? (int)$statusCodeMatches[1] : null;

        if (!$httpStatusCode) {
            $notes[] = "Could not parse http status code";
            return $result;
        }

        $result["httpStatusCode"] = $httpStatusCode;
        if ($httpStatusCode >= 500) {
            $status = Status::UNREACHABLE;
            $notes[] = "Can't ensure reachability on server error";
        } elseif ($httpStatusCode >= 400) {
            $notes[] = "Remove client error on request";
        } elseif ($httpStatusCode >= 300) {
            $notes[] = "Can't ensure reachability on forwarding";
            preg_match('/Location:.*/m', $response, $locationMatches);
            if (isset($locationMatches[0]))
                $notes[] = trim($locationMatches[0]);
        }
        return $result;
    }
}