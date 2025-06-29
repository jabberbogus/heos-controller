<?php
namespace HeosApp;

class HeosRunner
{

    private array $allowedCommands =
    [
        'group_commands' =>
        [
            'muteon',
            'muteoff',
            'toggle_mute',
            'get_state',
            'set_stop',
            'set_pause',
            'set_play',
            'get_volume',


        ],
        'system_commands' => [
            'discover',
            'help',
        ],
        'complex_commands' => [
            'set_volume'        // Example: set_volume 50, require argument
        ]
    ];

    private array $aliasedCommands = [
        'mute_on' => 'muteon',
        'mute_off' => 'muteoff',
        'state' => 'get_state',
        'stop' => 'set_stop',
        'pause' => 'set_pause',
        'play' => 'set_play',
        'start' => 'set_play',
        'volume' => 'set_volume',
    ];

    public string $ip;

    public array $players = [];

    public array $groups = [];

    public string $pid;


    public function loadCacheFile(): void
    {
        if (!file_exists(_CACHE_FILE_)) {
            throw new \Exception("Cache file not found. Please run discovery first.");
        }
        $data = json_decode(file_get_contents(_CACHE_FILE_), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Error decoding cache file: " . json_last_error_msg());
        }
        $this->ip = $data['ip'] ?? '';
        $this->players = $data['players'] ?? [];
        $this->groups = $data['groups'] ?? [];
        $this->pid = $this->players[0]['pid'] ?? '';
    }


    public function parseArgument(): array
    {
        global $argv;
        $command = $argv[1];
        $arg = null;
        if (empty($command)) {
            throw new \InvalidArgumentException("No command provided. Use 'help' to see available commands.");
        }
        $command = strtolower($command);

        if (!in_array($command, array_merge($this->allowedCommands['group_commands'],
                                            $this->allowedCommands['system_commands'],
                                            $this->allowedCommands['complex_commands'],
                                            array_keys($this->aliasedCommands) ))) {
            throw new \InvalidArgumentException("Invalid command: $command. Use 'help' to see available commands.");
        }

        if (array_key_exists($command, $this->aliasedCommands)) {
            $command = $this->aliasedCommands[$command];
        }

        if (in_array($command, $this->allowedCommands['complex_commands'])) {
            if (count($argv) < 3) {
                throw new \InvalidArgumentException("No arguments provided for command: $command. Use 'help' to see available commands.");
            }
            $arg = $argv[2];
            if (!is_numeric($arg) || $arg < 0 || $arg > 100) {
                throw new \InvalidArgumentException("Invalid argument for command: $command. Argument must be a number between 0 and 100.");
            }
            return ['type' => 'complex', 'command' => $command, 'arg' => $arg];
        }
        if (in_array($command, $this->allowedCommands['group_commands'])) {
            return ['type' => 'group', 'command' => $command];
        }
        if (in_array($command, $this->allowedCommands['system_commands'])) {
            return ['type' => 'system', 'command' => $command];
        }
    }


    public function executeGroupCommands(string $command, HeosClient $client, array $players): void
    {
        // Validate command
        if (!in_array($command, $this->allowedCommands['group_commands'])) {
            echo "Invalid command: $command\n";
            echo "Available commands: " . implode(', ', $this->allowedCommands['group_commands']) . "\n";
            return;
        }

        switch ($command) {
            case 'muteon':
                if ($players) {
                    foreach ($players as $player) {
                        $client->sendCommand(HeosCommand::setMuteOn($player['pid']));
                        echo "Muted player: {$player['name']}\n";
                    }
                }
                else {
                    echo "No players specified for muteon command.\n";
                }
                break;
            case 'muteoff':
                if ($players) {
                    foreach ($players as $player) {
                        $client->sendCommand(HeosCommand::setMuteOff($player['pid']));
                        echo "Unmuted player: {$player['name']}\n";
                    }
                }
                else {
                    echo "No players specified for muteoff command.\n";
                }
                break;
            case 'toggle_mute':
                if ($players) {
                    foreach ($players as $player) {
                        $client->sendCommand(HeosCommand::toggleMute($player['pid']));
                        echo "Toggled mute for player: {$player['name']}\n";
                    }
                }
                else {
                    echo "No players specified for toggle_mute command.\n";
                }
                break;
            case 'get_state':
                if ($players) {
                    foreach ($players as $player) {
                        $response = $client->sendCommand(HeosCommand::getPlayState($player['pid']));
                        $state = $response['heos']['message'] ?? 'unknown';
                        $state = explode('=', $response['heos']['message'] ?? 'unknown');
                        $state = end($state);
                        if (!in_array($state, ['play', 'pause', 'stop'])) {
                            $state = 'unknown';
                        }
                        echo "Player: {$player['name']}, State: $state\n";
                    }
                }
                else {
                    echo "No players specified for get_state command.\n";
                }
                break;
            case 'set_stop':
                if ($players) {
                    foreach ($players as $player) {
                        $client->sendCommand(HeosCommand::setPlayState($player['pid'], 'stop'));
                        echo "Stopped player: {$player['name']}\n";
                    }
                }
                else {
                    echo "No players specified for set_stop command.\n";
                }
                break;
            case 'set_pause':
                if ($players) {
                    foreach ($players as $player) {
                        $client->sendCommand(HeosCommand::setPlayState($player['pid'], 'pause'));
                        echo "Paused player: {$player['name']}\n";
                    }
                }
                else {
                    echo "No players specified for set_pause command.\n";
                }
                break;
            case 'set_play':
                if ($players) {
                    foreach ($players as $player) {
                        $client->sendCommand(HeosCommand::setPlayState($player['pid'],  'play'));
                        echo "Playing on player: {$player['name']}\n";
                    }
                }
                else {
                    echo "No players specified for set_play command.\n";
                }
                break;
            case 'get_volume':
                if ($players) {
                    foreach ($players as $player) {
                        $response = $client->sendCommand(HeosCommand::getVolume($player['pid']));
                        $volume = $response['heos']['message'] ?? 'unknown';
                        $volume = explode('=', $response['heos']['message'] ?? 'unknown');
                        $volume = end($volume);
                        if (!is_numeric($volume)) {
                            $volume = 'unknown';
                        }
                        echo "Player: {$player['name']}, Volume: $volume\n";
                    }
                }
                else {
                    echo "No players specified for get_volume command.\n";
                }
                break;
            default:
                echo "Unknown group command: $command\n";
                echo "Available commands: " . implode(', ', $this->allowedCommands['group_commands']) . "\n";
                break;

        }
    }

     public function executeComplexCommands(string $command, HeosClient $client, array $players = null, int $arg = 0): void
    {
        // Validate command
        if (!in_array($command, $this->allowedCommands['complex_commands'])) {
            echo "Invalid command: $command\n";
            echo "Available commands: " . implode(', ', $this->allowedCommands['complex_commands']) . "\n";
            return;
        }
        // Execute the command
        switch ($command) {
            case 'set_volume':
                if ($arg < 0 || $arg > 100) {
                    echo "Invalid volume level: $arg. Must be between 0 and 100.\n";
                    return;
                }
                if ($players) {
                    foreach ($players as $player) {
                        $client->sendCommand(HeosCommand::setVolume($player['pid'], $arg));
                        echo "Set volume to $arg for player: {$player['name']}\n";
                    }
                }
                else {
                    echo "No players specified for set_volume command.\n";
                }
                break;
            default:
                echo "Unknown complex command: $command\n";
                echo "Available commands: " . implode(', ', $this->allowedCommands['complex_commands    ']) . "\n";
                break;
        }
    }

    public function executeSystemCommands(string $command): void
    {
        // Validate command
        if (!in_array($command, $this->allowedCommands['system_commands'])) {
            echo "Invalid command: $command\n";
            echo "Available commands: " . implode(', ', $this->allowedCommands['system_commands']) . "\n";
            return;
        }

        // Execute the command
        switch ($command) {
            case 'discover':
                HeosCommand::discover();
                echo "HEOS devices discovered.\n";
                break;
            case 'help':
                echo "\n";
                echo "Available commands:\n";
                foreach ($this->allowedCommands as $category => $commands) {
                    echo " $category: " . implode(', ', $commands) . "\n";
                }
                echo "\n";
                echo " example: php index.php discover\n";
                echo " example: php index.php muteon\n";
                echo " example: php index.php set_volume 50\n";
                echo " example: php index.php set_play\n";
                echo "\n";
                break;
        }
    }


}



