<?php
namespace HeosApp;

use HeosApp\HeosException;

class HeosClient {
    private string $host;
    private int $port = 1255;
    private $socket;

    public function __construct(string $host) {
        $this->host = $host;
    }

    public function connect(): void {
        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 5);
        if (!$this->socket) {
            throw new \Exception("Connection error: $errstr ($errno)");
        }
    }

    public function sendCommand(string $command): array {
        if (!is_resource($this->socket)) {
            throw new \Exception("Socket is not connected.");
        }

        fwrite($this->socket, $command . "\r\n");
        usleep(200000);

        $response = '';
        while (!feof($this->socket)) {
            $line = fgets($this->socket);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // Try to decode as soon as we have something
            $decoded = json_decode($response, true);
            if (is_array($decoded) && isset($decoded['heos'])) {
                break;
            }
        }

        $decoded = json_decode($response, true);

        if (!$decoded || !isset($decoded['heos'])) {
            throw new \Exception("Invalid response from HEOS device: $response");
        }

        if ($decoded['heos']['result'] === 'fail') {
            throw HeosException::fromHeosMessage($decoded['heos']['message'] ?? '');
        }

        return $decoded;
    }

    public function disconnect(): void {
        if ($this->socket) {
            fclose($this->socket);
        }
    }
}
