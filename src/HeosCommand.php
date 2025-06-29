<?php
namespace HeosApp;

class HeosCommand {
    public static function getPlayers(): string {
        return "heos://player/get_players";
    }

    public static function getPlayerInfo(string $pid): string {
        return "heos://player/get_player_info?pid=$pid";
    }

    public static function getPlayState(string $pid): string {
        return "heos://player/get_play_state?pid=$pid";
    }

    public static function setPlayState(string $pid, string $state): string {
        $state = strtolower($state);
        if (!in_array($state, ['play', 'pause', 'stop'])) {
            throw new \InvalidArgumentException("Invalid play state: $state. Use 'play', 'pause', or 'stop'.");
        }
        return "heos://player/set_play_state?pid=$pid&state=$state";
    }

    public static function getNowPlayingMedia(string $pid): string {
        return "heos://player/get_now_playing_media?pid=$pid";
    }

    public static function getVolume(string $pid): string {
        return "heos://player/get_volume?pid=$pid";
    }

    public static function setVolume(string $pid, int $level): string {
        return "heos://player/set_volume?pid=$pid&level=$level";
    }

    public static function toggleMute(string $pid): string {
        return "heos://player/toggle_mute?pid=$pid";
    }


    public static function setMuteOn(string $pid): string {
        return "heos://player/set_mute?pid=$pid&mute=on";
    }

    public static function setMuteOff(string $pid): string {
        return "heos://player/set_mute?pid=$pid&mute=off";
    }

    public static function unmute(string $pid): string {
        return "heos://player/set_mute?pid=$pid&mute=off";
    }

    public static function playStream(string $pid, string $url): string {
        return "heos://browse/play_stream?pid=$pid&url=" . urlencode($url);
    }

    public static function checkAccount(): string {
        return "heos://system/check_account";
    }

    // HEOS Account Sign In heos://system/sign_in?un=heos_username&pw=heos_password
    public static function signIn(string $username, string $password): string {
        return "heos://system/sign_in?un=" . urlencode($username) . "&pw=" . urlencode($password);
    }

    public static function getGroups(): string {
        return "heos://group/get_groups";
    }

    public static function getGroupInfo(string $gid): string {
        return "heos://group/get_group_info?gid=$gid";
    }

// Set Group Volume
// Command: heos://group/set_volume?gid=group_id&level=vol_level

    public static function setGroupVolume(string $gid, int $level): string {
        return "heos://group/set_volume?gid=$gid&level=$level";
    }


    public static function discover(): string {

        echo "Discover HEOS devices on the local network ...\n";
        $discovery = new HeosDiscovery();
        $devices = $discovery->discover();
        if (empty($devices)) {
            throw new Exception("No HEOS devices found on the local network.");
        }
        foreach ($devices as $ip) {
            echo "- $ip\n";
        }
        $ip = $devices[0];
        $client = new HeosClient($ip);
        $client->connect();

        // Get players
        $playersResponse = $client->sendCommand("heos://player/get_players");
        $players = $playersResponse['payload'] ?? [];

        // Get groups
        $groupsResponse = $client->sendCommand("heos://group/get_groups");
        $groups = $groupsResponse['payload'] ?? [];

        // Save to JSON file
        $data = [
            'timestamp' => date('c'),
            'ip' => $ip,
            'devices' => $devices,
            'players' => $players,
            'groups' => $groups
        ];

        file_put_contents(_CACHE_FILE_, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return "HEOS devices discovered and cached.\n";
    }
}
