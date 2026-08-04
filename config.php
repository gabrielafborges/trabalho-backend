<?php
/**
 * config.php
 * Configuração Global e Conexão com MongoDB e Redis
 * Sistema de Achados e Perdidos
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/RedisClient.php';
require_once __DIR__ . '/MongoDbAdapter.php';

// Inicializar Clientes de Banco de Dados
try {
    $redis = new RedisClient('127.0.0.1', 6379);
    $mongo = new MongoDbAdapter();
} catch (Exception $e) {
    die("Erro ao inicializar adaptadores de banco de dados: " . $e->getMessage());
}

/**
 * Função Auxiliar para o FLUXO OBRIGATÓRIO DE CACHE (Cache-Aside Pattern)
 * 
 * @param string $cacheKey Chave para busca no Redis
 * @param int $ttl Tempo de vida em segundos (padrão: 300s = 5 min)
 * @param callable $mongoQueryCallback Função para executar no Mongo caso ocorra Cache MISS
 * @return array Dados retornados (do Redis ou do Mongo)
 */
function cachedExecute($cacheKey, $ttl, callable $mongoQueryCallback) {
    global $redis;

    // 1. Tentar buscar no Redis (Cache HIT)
    $cachedData = $redis->get($cacheKey);
    if ($cachedData !== null && $cachedData !== false) {
        $decoded = json_decode($cachedData, true);
        if ($decoded !== null) {
            return [
                'source' => 'REDIS_CACHE_HIT',
                'cache_key' => $cacheKey,
                'data' => $decoded
            ];
        }
    }

    // 2. Se Cache MISS: Executar consulta no MongoDB
    $data = $mongoQueryCallback();

    // 3. Salvar o resultado no Redis com TTL (Time-To-Live)
    $redis->set($cacheKey, json_encode($data, JSON_UNESCAPED_UNICODE), $ttl);

    // 4. Retornar resposta com origem MongoDB
    return [
        'source' => 'MONGODB_CACHE_MISS',
        'cache_key' => $cacheKey,
        'ttl_seconds' => $ttl,
        'data' => $data
    ];
}
?>
