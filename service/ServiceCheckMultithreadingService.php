<?php

namespace service;

use CurlHandle;
use CurlMultiHandle;
use Exception;
use model\configuration\LogFile;
use model\security\nSecure;
use model\Service;
use model\ServiceCheck;
use model\SocketProtocol;
use model\Status;

class ServiceCheckMultithreadingService {
    static private string $SALT_LIST_FILE_PATH = __DIR__ . '/../tmp/getTemporarySalts.php';
    static private string $PASSWORD = 'PD3&3cfpU*T$b&^*G&KxX8Gn&@TUEMWd7^#x*4J*V&9!!h%*TVidiLx@A8EzkKD9Sr@q2ophVsVT$k$tN@TnA#m&x^Syj2ZJtKvj!ayUcd@QHDsWf4EuxdM3BWfC6%t*';

    private function __construct() {}

    static public function parseService(?string $data, ?string $salt): Service|FALSE {
        if (empty($data) || empty($salt))
            return FALSE;

        $hash = self::$PASSWORD.$salt;
        try {
            $serviceMap = nSecure::decrypt($data, $hash, self::getLocalFileSaltList($hash));
            return new Service($serviceMap['id'], $serviceMap['name'], $serviceMap['hostName'], SocketProtocol::parse($serviceMap['socketProtocol']), $serviceMap['port'], $serviceMap['icon'], $serviceMap['enabled'], $serviceMap['timeout']);
        } catch (Exception) {
            return FALSE;
        }
    }

    public static function checkServicesMultithreaded(array $services): array {
        $salts = nSecure::generateSaltList();
        $headerDate = gmdate('D, d M Y H:i:s T');
        $hash = self::$PASSWORD . $headerDate;

        self::createLocalSaltFile($salts, $hash);

        $multiCurlHandler = curl_multi_init();
        $serviceCurlMap = [];
        foreach($services as $service) {
            if ($curl = self::getServiceCurlHandle($service, $hash, $salts, $headerDate)) {
                curl_multi_add_handle($multiCurlHandler, $curl);
                $serviceCurlMap[] = ['service' => $service, 'curl' => $curl];
            } else {
                LogService::error(LogFile::SERVICE_MULTITHREADING, "Could not create Curl for service with id '$service->id'");
            }
        }

        self::executeAndWaitMultiCurl($multiCurlHandler);

        $serviceCheckMap = [];
        foreach($serviceCurlMap as $serviceCurlEntry){
            curl_multi_remove_handle($multiCurlHandler, $serviceCurlEntry['curl']);
            if ($serviceCheck = self::parseCurlResponse($serviceCurlEntry['service'], $serviceCurlEntry['curl'], $headerDate))
                $serviceCheckMap[$serviceCurlEntry['service']->id] = $serviceCheck;
        }
        curl_multi_close($multiCurlHandler);
        FileService::clear(self::$SALT_LIST_FILE_PATH);

        return $serviceCheckMap;
    }

    static private function createLocalSaltFile(array $salts, string $hash): void {
        $salt = nSecure::encrypt($salts, $hash, [$hash]);
        FileService::set(self::$SALT_LIST_FILE_PATH, "<?php\nfunction getSalt(): string { return '$salt'; }");
    }

    static private function getLocalFileSaltList(string $hash): array|FALSE {
        try {
            require_once self::$SALT_LIST_FILE_PATH;
            return nSecure::decrypt(getSalt(), $hash, [$hash]);
        } catch (Exception) {
            return FALSE;
        }
    }

    static private function getServiceCurlHandle(Service $service, string $hash, array $salts, string $headerDate): CurlHandle|FALSE {
        $timeout = $service->timeout + 5;
        $serviceHash = nSecure::encrypt($service, $hash, $salts);
        $url = "{$_SERVER['SERVER_NAME']}/status/api/services/$serviceHash/serviceCheck";

        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ["Date: $headerDate"]);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
            curl_setopt($curl, CURLOPT_VERBOSE, FALSE);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl, CURLOPT_NOPROGRESS, TRUE);
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_HEADER, TRUE);
            curl_setopt($curl, CURLOPT_NOBODY, FALSE);
        }
        return $curl;
    }

    static private function executeAndWaitMultiCurl(CurlMultiHandle $multiCurlHandler): void {
        do {
            $status = curl_multi_exec($multiCurlHandler, $isRunning);
            if ($isRunning) curl_multi_select($multiCurlHandler);
        } while ($isRunning && $status == CURLM_OK);
    }

    private static function parseCurlResponse(Service $service, CurlHandle $curlHandle, string $headerDate): ServiceCheck|FALSE {
        $httpResponseCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        if ($httpResponseCode < 200 || $httpResponseCode >= 300) {
            LogService::error(LogFile::SERVICE_MULTITHREADING, "Could not internally curl ServiceCheck for service with id '$service->id' due to http '$httpResponseCode'");
            return FALSE;
        }
        $response = curl_multi_getcontent($curlHandle);
        $headerSize = curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);

        preg_match('/Date:.*/m', $header, $dateMatches);
        if (!isset($dateMatches[0]) || $dateMatches[0] != $headerDate) {
            LogService::error(LogFile::SERVICE_MULTITHREADING, "Response of internal ServiceCheck curl for service with id '$service->id' does not contain header 'Date'");
            return FALSE;
        }
        try {
            $response = substr($response, $headerSize);
            $serviceCheckMap = json_decode($response, TRUE);
            $serviceCheck = ServiceCheck::createByService(
                $service,
                $serviceCheckMap['fullHostName'],
                $serviceCheckMap['timestamp'],
                $serviceCheckMap['latency'],
                $serviceCheckMap['ipv4'],
                $serviceCheckMap['ipv6'],
                $serviceCheckMap['forwardedHost'],
                Status::parse($serviceCheckMap['status']),
                $serviceCheckMap['response'],
                $serviceCheckMap['notes']);
        } catch (Exception) {
            LogService::error(LogFile::SERVICE_MULTITHREADING, "Could not parse response of internal ServiceCheck curl for service with id '$service->id' as ServiceCheck: '$response'");
            return FALSE;
        }
        return $serviceCheck;
    }
}