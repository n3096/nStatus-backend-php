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

    public static function checkServices(): void {
        LogService::info("Started checking status of services");
        StatisticService::startServicesCheckStatistics();
        $services = EntityService::getServices();
        $serviceCheckMap = ServiceCheckMultithreadingService::checkServicesMultithreaded($services);

        LogService::debug("Updating API files");
        ApiService::updateServers(EntityService::getServers());
        ApiService::updateServices($serviceCheckMap);
        $servicesCheckStatistics = StatisticService::finishServicesCheckStatistics();
        ApiService::updateApiInformation($servicesCheckStatistics, $services);
        ApiService::updateStatistics($servicesCheckStatistics);

        LogService::info("Finished checking status of services");
    }

    public static function checkEncryptedService(?string $data, ?string $salt): ServiceCheck|FALSE {
        $service = ServiceCheckMultithreadingService::parseService($data, $salt);
        return $service ? self::checkService($service) : FALSE;
    }

    public static function checkService(Service $service): ServiceCheck {
        $notes = [];
        $response = [];

        $dateTime = new DateTimeSerializable();
        $latencyStart = microtime(TRUE);
        $resource = self::createResource($service, $errorCode, $errorMessage);
        $latency = intval((microtime(TRUE) - $latencyStart) * 1000); // convert to milliseconds

        $status = Status::UNREACHABLE;
        if ($resource) {
            $status = Status::REACHABLE;
            $response = self::getResponse($resource, $service, $status, $notes);
            fclose($resource);
        }

        if ($errorCode != 10061) { // filter for common unreachable error code
            if ($errorCode)
                $notes["errorCode"] = $errorCode;
            if ($errorMessage)
                $notes["errorMessage"] = $errorMessage;
        }

        if ($status === Status::REACHABLE && sizeof($notes) > 0)
            $status = Status::WARNING;

        if(empty($response))
            $response = (object)[];

        return ServiceCheck::createByService($service, self::getActualHostName($service->hostName, $service->socketProtocol), $dateTime, $latency, self::getIpv4($service), self::getIpv6($service), self::getForwardedHost($service), $status, $response, $notes);
    }

    private static function getActualHostName(string $hostName, SocketProtocol $socketProtocol): string {
        return ($socketProtocol === SocketProtocol::HTTPS ? "ssl://" : "") . $hostName;
    }

    private static function createResource(Service $service, ?int &$errorCode, ?string &$errorMessage) {
        return fsockopen(self::getActualHostName($service->hostName, $service->socketProtocol), $service->port, $errorCode, $errorMessage, $service->timeout);
    }

    private static function getIpv4(Service $service): ?string {
        $host = gethostbyname($service->hostName);
        if ($host === $service->hostName)
            return dns_get_record($host, DNS_A)[0]["ip"] ?? null;
        return $host;
    }

    private static function getIpv6(Service $service): ?string {
        return dns_get_record($service->hostName, DNS_AAAA)[0]["ipv6"] ?? null;
    }

    private static function getForwardedHost(Service $service): ?string {
        return dns_get_record($service->hostName, DNS_A)[0]["host"] ?? dns_get_record($service->hostName, DNS_AAAA)[0]["host"] ?? null;
    }

    private static function getResponse(&$resource, Service $service, Status &$status, array &$notes): array {
        if (!is_resource($resource))
            throw new InvalidArgumentException(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));

        return match ($service->socketProtocol) {
            SocketProtocol::HTTP, SocketProtocol::HTTPS => self::getResponseForHttpAndHttps($resource, $service, $status, $notes),
            default => [],
        };
    }

    private static function getResponseForHttpAndHttps(&$resource, Service $service, Status &$status, array &$notes, string $path = '/', int $redirectionCounter = 0): array {
        if (!is_resource($resource))
            throw new InvalidArgumentException(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));

        $request = "GET $path HTTP/1.1\r\n";
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

        $result = [];
        if (!$httpStatusCode) {
            $status = Status::UNREACHABLE;
            $notes[] = "Could not parse http status code";
            return $result;
        }

        $result["httpStatusCode"] = $httpStatusCode;
        if ($httpStatusCode >= 500) {
            $status = Status::UNREACHABLE;
            $notes[] = "Can't ensure reachability on server error";
        } elseif ($httpStatusCode >= 400) {
            $status = Status::WARNING;
            LogService::warning("Remove client error on request for Service with id '$service->id'");
            $notes[] = "Remove client error on request";
        } elseif ($httpStatusCode >= 300) {
            if ($redirectionCounter > 10) {
                $status = Status::WARNING;
                LogService::warning("Already redirected Service with id '$service->id' 10 times");
                $notes[] = "Already redirected 10 times";
                return $result;
            }
            preg_match('/Location: (.*)/m', $response, $locationMatches);
            $location = trim($locationMatches[1]);
            if (!isset($location)) {
                $status = Status::WARNING;
                $notes[] = "Can't ensure reachability on forwarding without Location header";
                LogService::warning("Can't ensure reachability on forwarding without Location header for Service with id '$service->id'");
                return $result;
            }
            $url = parse_url($location);
            if (isset($url['host']) && $url['host'] != $service->hostName) {
                $status = Status::WARNING;
                $notes[] = "Redirecting to a different host '{$url['host']}'";
                LogService::warning("Redirecting Service with id '$service->id' to a different host '{$url['host']}'");
                return $result;
            }
            if (empty($url['path'])) {
                LogService::warning("Could not parse (empty) path of redirected Location header '$location' for Service with id '$service->id'");
                $notes[] = "Could not parse (empty) path of redirected Location header '$location'";
                return $result;
            }
            $path = $path && preg_match('/^[^\/]*(\/.*\/).*$/m', $path, $urlPath) ? $urlPath[1] : '';
            $path .= $url['path'];

            fclose($resource);
            $resource = self::createResource($service, $errorCode, $errorMessage);

            LogService::debug("Redirecting Service with id '$service->id' to Location '$path'");
            return self::getResponseForHttpAndHttps($resource, $service, $status, $notes, $path, ++$redirectionCounter);
        }
        $status = Status::REACHABLE;
        return $result;
    }
}