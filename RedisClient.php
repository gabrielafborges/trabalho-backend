<?php
/**
 * RedisClient.php
 * Cliente Redis nativo em PHP usando Protocolo RESP (REdis Serialization Protocol) via Sockets TCP.
 * Suporta as 4 estruturas requeridas: String, Hash, Sorted Set e List/Set.
 * Inclui persistência em arquivo/sessão para manter a aplicação funcional 100% no XAMPP e CLI.
 */

class RedisClient {
    private $host;
    private $port;
    private $socket = null;
    private $connected = false;
    private $storePath;
    private $storeData = [];

    public function __construct($host = '127.0.0.1', $port = 6379, $timeout = 1.0) {
        $this->host = $host;
        $this->port = $port;
        $this->storePath = __DIR__ . '/data_mongo/redis_store.json';

        // Tentar conexão com servidor Redis real
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($fp) {
            $this->socket = $fp;
            $this->connected = true;
            stream_set_timeout($this->socket, 1);
        } else {
            $this->connected = false;
            $this->loadStore();
        }
    }

    private function loadStore() {
        if (file_exists($this->storePath)) {
            $this->storeData = json_decode(file_get_contents($this->storePath), true) ?? [];
        } else {
            $this->storeData = [
                'strings' => [],
                'hashes' => [],
                'zsets' => [],
                'lists' => [],
                'sets' => [],
                'ttls' => []
            ];
            $this->saveStore();
        }
    }

    private function saveStore() {
        if (!file_exists(dirname($this->storePath))) {
            @mkdir(dirname($this->storePath), 0777, true);
        }
        file_put_contents($this->storePath, json_encode($this->storeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function isConnected() {
        return $this->connected;
    }

    // ------------------------------------------------------------------------
    // RESP PROTOCOL HANDLERS FOR REAL REDIS SERVER
    // ------------------------------------------------------------------------
    private function executeCommand(...$args) {
        if (!$this->connected || !$this->socket) {
            return false;
        }

        $cmd = '*' . count($args) . "\r\n";
        foreach ($args as $arg) {
            $argStr = (string)$arg;
            $cmd .= '$' . strlen($argStr) . "\r\n" . $argStr . "\r\n";
        }

        @fwrite($this->socket, $cmd);
        return $this->readResponse();
    }

    private function readResponse() {
        $line = fgets($this->socket);
        if ($line === false) return null;

        $type = substr($line, 0, 1);
        $value = trim(substr($line, 1));

        switch ($type) {
            case '+': // Simple String
            case ':': // Integer
                return $value;
            case '-': // Error
                return ['error' => $value];
            case '$': // Bulk String
                $len = intval($value);
                if ($len === -1) return null;
                $data = '';
                while (strlen($data) < $len) {
                    $read = fread($this->socket, min(1024, $len - strlen($data)));
                    if ($read === false) break;
                    $data .= $read;
                }
                fgets($this->socket); // consume CRLF
                return $data;
            case '*': // Multi-bulk Array
                $count = intval($value);
                if ($count === -1) return null;
                $result = [];
                for ($i = 0; $i < $count; $i++) {
                    $result[] = $this->readResponse();
                }
                return $result;
            default:
                return null;
        }
    }

    // ------------------------------------------------------------------------
    // 1. ESTRUTURA: STRING (Cache de Chave-Valor Simples)
    // ------------------------------------------------------------------------
    public function set($key, $value, $ttlSeconds = null) {
        if ($this->connected) {
            if ($ttlSeconds) {
                return $this->executeCommand('SET', $key, $value, 'EX', $ttlSeconds);
            }
            return $this->executeCommand('SET', $key, $value);
        }
        
        $this->storeData['strings'][$key] = (string)$value;
        if ($ttlSeconds) {
            $this->storeData['ttls'][$key] = time() + $ttlSeconds;
        }
        $this->saveStore();
        return 'OK';
    }

    public function get($key) {
        if ($this->connected) {
            return $this->executeCommand('GET', $key);
        }
        
        if (isset($this->storeData['ttls'][$key]) && time() > $this->storeData['ttls'][$key]) {
            unset($this->storeData['strings'][$key]);
            unset($this->storeData['ttls'][$key]);
            $this->saveStore();
            return null;
        }

        return $this->storeData['strings'][$key] ?? null;
    }

    public function del($key) {
        if ($this->connected) {
            return $this->executeCommand('DEL', $key);
        }

        unset($this->storeData['strings'][$key]);
        unset($this->storeData['hashes'][$key]);
        unset($this->storeData['zsets'][$key]);
        unset($this->storeData['lists'][$key]);
        unset($this->storeData['sets'][$key]);
        unset($this->storeData['ttls'][$key]);
        $this->saveStore();
        return 1;
    }

    // ------------------------------------------------------------------------
    // 2. ESTRUTURA: HASH (Objetos e Sessões Estruturadas)
    // ------------------------------------------------------------------------
    public function hset($key, $field, $value) {
        if ($this->connected) {
            return $this->executeCommand('HSET', $key, $field, $value);
        }
        $this->storeData['hashes'][$key][$field] = (string)$value;
        $this->saveStore();
        return 1;
    }

    public function hgetall($key) {
        if ($this->connected) {
            $raw = $this->executeCommand('HGETALL', $key);
            if (!is_array($raw)) return [];
            $assoc = [];
            for ($i = 0; $i < count($raw); $i += 2) {
                if (isset($raw[$i + 1])) {
                    $assoc[$raw[$i]] = $raw[$i + 1];
                }
            }
            return $assoc;
        }

        return $this->storeData['hashes'][$key] ?? [];
    }

    // ------------------------------------------------------------------------
    // 3. ESTRUTURA: SORTED SET (ZSET - Ranking / Feeds com Score/Data)
    // ------------------------------------------------------------------------
    public function zadd($key, $score, $member) {
        if ($this->connected) {
            return $this->executeCommand('ZADD', $key, $score, $member);
        }

        $this->storeData['zsets'][$key][$member] = floatval($score);
        asort($this->storeData['zsets'][$key]);
        $this->saveStore();
        return 1;
    }

    public function zrange($key, $start = 0, $stop = -1, $withScores = false) {
        if ($this->connected) {
            $args = ['ZRANGE', $key, $start, $stop];
            if ($withScores) $args[] = 'WITHSCORES';
            return $this->executeCommand(...$args) ?? [];
        }

        $set = $this->storeData['zsets'][$key] ?? [];
        asort($set);
        $keys = array_keys($set);
        
        if ($stop == -1) $stop = count($keys) - 1;
        $slice = array_slice($keys, $start, ($stop - $start + 1));

        if (!$withScores) {
            return $slice;
        }

        $result = [];
        foreach ($slice as $k) {
            $result[$k] = $set[$k];
        }
        return $result;
    }

    // ------------------------------------------------------------------------
    // 4. ESTRUTURA: LIST / SET (Filas de Logs e Conjuntos Únicos)
    // ------------------------------------------------------------------------
    public function lpush($key, $value) {
        if ($this->connected) {
            return $this->executeCommand('LPUSH', $key, $value);
        }

        if (!isset($this->storeData['lists'][$key])) {
            $this->storeData['lists'][$key] = [];
        }
        array_unshift($this->storeData['lists'][$key], (string)$value);
        $this->saveStore();
        return count($this->storeData['lists'][$key]);
    }

    public function lrange($key, $start = 0, $stop = -1) {
        if ($this->connected) {
            return $this->executeCommand('LRANGE', $key, $start, $stop) ?? [];
        }

        $list = $this->storeData['lists'][$key] ?? [];
        if ($stop == -1) $stop = count($list) - 1;
        return array_slice($list, $start, ($stop - $start + 1));
    }

    public function sadd($key, $member) {
        if ($this->connected) {
            return $this->executeCommand('SADD', $key, $member);
        }

        if (!isset($this->storeData['sets'][$key])) {
            $this->storeData['sets'][$key] = [];
        }
        if (!in_array($member, $this->storeData['sets'][$key])) {
            $this->storeData['sets'][$key][] = (string)$member;
        }
        $this->saveStore();
        return 1;
    }

    public function smembers($key) {
        if ($this->connected) {
            return $this->executeCommand('SMEMBERS', $key) ?? [];
        }

        return $this->storeData['sets'][$key] ?? [];
    }

    public function flushAll() {
        if ($this->connected) {
            $this->executeCommand('FLUSHALL');
        }
        $this->storeData = [
            'strings' => [],
            'hashes' => [],
            'zsets' => [],
            'lists' => [],
            'sets' => [],
            'ttls' => []
        ];
        $this->saveStore();
        return true;
    }
}
?>
