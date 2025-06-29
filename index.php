<?php
require 'vendor/autoload.php';

use HeosApp\HeosClient;
use HeosApp\HeosDiscovery;
use HeosApp\HeosException;
use HeosApp\HeosRunner;

const _CACHE_FILE_ = __DIR__ . '/heos_state.json';

try {
    $runner = new HeosRunner();
    $command = $runner->parseArgument();

    if ($command['type'] === 'system') {
        $runner->executeSystemCommands($command['command']);
    }

    if (!file_exists(_CACHE_FILE_)) {
        throw new Exception("Cache file not found. Please run discovery first.");
    }
    else {
        $data = json_decode(file_get_contents(_CACHE_FILE_), true);
        $ip = $data['ip'];
        $players = $data['players'];
        $groups = $data['groups'];
        $pid = $players[0]['pid'] ?? null;
        if (!$pid) {
            throw new Exception("No players found in cache file.");
        }
    }

    if ($command['type'] === 'group') {
       echo "Executing group command: " . $command['command'] . "\n";
        $client = new HeosClient($ip);
        $client->connect();
        $runner->executeGroupCommands($command['command'], $client, $players ?? null);
        $client->disconnect();
    }
    else if ($command['type'] === 'complex') {
        echo "Executing complex command: " . $command['command'] . " with argument " . $command['arg'] . "\n";
        $client = new HeosClient($ip);
        $client->connect();
        $runner->executeComplexCommands($command['command'], $client, $players ?? null, $command['arg']);
        $client->disconnect();
    }
    else {
        throw new Exception("Unknown command type: " . $command['type']);
    }

}
catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}