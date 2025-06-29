<?php
namespace HeosApp;

class HeosDiscovery
{
    private string $st = 'urn:schemas-denon-com:device:ACT-Denon:1';
    private int $timeout = 2; // seconds

    public function discover(): array
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === false) {
            throw new \Exception("Unable to create socket: " . socket_strerror(socket_last_error()));
        }

        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ["sec" => $this->timeout, "usec" => 0]);

        $message = implode("\r\n", [
            'M-SEARCH * HTTP/1.1',
            'HOST: 239.255.255.250:1900',
            'MAN: "ssdp:discover"',
            'MX: 2',
            "ST: {$this->st}",
            '', ''
        ]);

        $addr = '239.255.255.250';
        $port = 1900;

        $sent = socket_sendto($socket, $message, strlen($message), 0, $addr, $port);
        if ($sent === false) {
            throw new \Exception("Failed to send SSDP request: " . socket_strerror(socket_last_error()));
        }

        $results = [];
        while (true) {
            $buf = '';
            $from = '';
            $port = 0;
            $bytes = @socket_recvfrom($socket, $buf, 1024, 0, $from, $port);
            if ($bytes === false || $bytes === 0) {
                break;
            }

            if (stripos($buf, 'LOCATION:') !== false) {
                preg_match('/LOCATION:\s*(http:\/\/[\d.:]+)/i', $buf, $match);
                if (isset($match[1])) {
                    $url = parse_url(trim($match[1]));
                    $ip = $url['host'] ?? null;

                    if ($ip && $this->isPrivateIp($ip) && !in_array($ip, $results)) {
                        $results[] = $ip;
                    }
                }
            }
        }

        socket_close($socket);
        return $results;
    }

    private function isPrivateIp(string $ip): bool
    {
        return (
            preg_match('/^192\.168\./', $ip) ||
            preg_match('/^10\./', $ip) ||
            preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $ip)
        );
    }
}
