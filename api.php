<?php
/**
 * api.php
 * Backend API RESTful para o Sistema de Achados e Perdidos
 * Gerencia CRUD, 4 Consultas MongoDB, Aggregation Pipeline, Análise de Índices e Fluxo de Cache no Redis.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$collection = $_GET['collection'] ?? $_POST['collection'] ?? 'itens_perdidos';

try {
    switch ($action) {
        // --------------------------------------------------------------------
        // 1. LISTAR / BUSCAR COM FLUXO OBRIGATÓRIO DE CACHE (Cache-Aside)
        // --------------------------------------------------------------------
        case 'list':
            $cacheKey = "api:list:{$collection}";
            $ttl = 60; // 60 segundos de cache

            $response = cachedExecute($cacheKey, $ttl, function() use ($mongo, $collection) {
                return $mongo->find($collection, [], ['sort' => ['criado_em' => -1]]);
            });

            echo json_encode([
                'success' => true,
                'action' => 'list',
                'collection' => $collection,
                'total' => count($response['data']),
                'cache_info' => [
                    'source' => $response['source'],
                    'key' => $response['cache_key'],
                    'ttl' => $response['ttl_seconds'] ?? null
                ],
                'data' => $response['data']
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        case 'get_item_cached':
            $itemId = $_GET['id'] ?? 'lost_001';
            $cacheKey = "item:lost:{$itemId}";
            $ttl = 300; // 5 minutos

            $response = cachedExecute($cacheKey, $ttl, function() use ($mongo, $itemId) {
                return $mongo->findOne('itens_perdidos', ['_id' => $itemId]);
            });

            echo json_encode([
                'success' => true,
                'action' => 'get_item_cached',
                'item_id' => $itemId,
                'cache_status' => $response['source'],
                'data' => $response['data']
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        // --------------------------------------------------------------------
        // 2. CRUD OPERAÇÕES (Create, Read, Update, Delete)
        // --------------------------------------------------------------------
        case 'create':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            if (empty($input['titulo'])) {
                throw new Exception("O título do item é obrigatório.");
            }

            $created = $mongo->insertOne($collection, $input);

            // Invalidação do Cache Redis (Invalida a lista em cache)
            $redis->del("api:list:{$collection}");
            $redis->set("item:lost:{$created['_id']}", json_encode($created, JSON_UNESCAPED_UNICODE), 300);
            
            // Adicionar ao Sorted Set do Redis
            $redis->zadd('items:recent:zset', time(), $created['_id'] . ':' . $created['titulo']);

            echo json_encode([
                'success' => true,
                'message' => 'Documento criado com sucesso e cache atualizado!',
                'data' => $created
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        case 'update':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = $input['_id'] ?? $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception("ID do documento não informado.");
            }

            unset($input['_id']);
            $updated = $mongo->updateOne($collection, ['_id' => $id], $input);

            // Invalidação de Cache
            $redis->del("api:list:{$collection}");
            $redis->del("item:lost:{$id}");

            echo json_encode([
                'success' => true,
                'message' => 'Documento atualizado com sucesso e cache invalidado.',
                'data' => $updated
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        case 'delete':
            $id = $_GET['id'] ?? $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception("ID do documento não informado.");
            }

            $deleted = $mongo->deleteOne($collection, ['_id' => $id]);

            // Invalidação de Cache
            $redis->del("api:list:{$collection}");
            $redis->del("item:lost:{$id}");

            echo json_encode([
                'success' => $deleted,
                'message' => $deleted ? 'Documento removido e cache atualizado.' : 'Documento não encontrado.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        // --------------------------------------------------------------------
        // 3. AS 4 CONSULTAS MONGODB REQUERIDAS
        // --------------------------------------------------------------------
        case 'query1': // Categoria e Status
            $categoria = $_GET['categoria'] ?? 'Eletrônicos';
            $status = $_GET['status'] ?? 'pendente';
            $data = $mongo->query1_categoriaEStatus($categoria, $status);
            
            echo json_encode([
                'success' => true,
                'query' => "Consulta 1: Categoria '{$categoria}' AND Status '{$status}'",
                'total' => count($data),
                'data' => $data
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        case 'query2': // Intervalo de Data e Local
            $dataInicio = $_GET['data_inicio'] ?? '2026-01-01 00:00:00';
            $dataFim = $_GET['data_fim'] ?? date('Y-m-d H:i:s');
            $localId = $_GET['local_id'] ?? 'loc_001';
            $data = $mongo->query2_intervaloDataELocal($dataInicio, $dataFim, $localId);

            echo json_encode([
                'success' => true,
                'query' => "Consulta 2: Local '{$localId}' AND Data entre '{$dataInicio}' e '{$dataFim}'",
                'total' => count($data),
                'data' => $data
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        case 'query3': // Busca Textual / Regex na Descrição
            $termo = $_GET['termo'] ?? 'iPhone';
            $data = $mongo->query3_buscaTextualDescricao($termo);

            echo json_encode([
                'success' => true,
                'query' => "Consulta 3: Busca por Regex/Texto no campo 'descricao' contendo '{$termo}'",
                'total' => count($data),
                'data' => $data
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        case 'query4': // Recompensa Oferecida & Status
            $recompensa = isset($_GET['recompensa']) ? (bool)$_GET['recompensa'] : true;
            $data = $mongo->query4_devolucoesPorStatusERecompensa($recompensa);

            echo json_encode([
                'success' => true,
                'query' => "Consulta 4: Itens com Recompensa = " . ($recompensa ? 'SIM' : 'NÃO') . " e Status Pendente",
                'total' => count($data),
                'data' => $data
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        // --------------------------------------------------------------------
        // 4. AGGREGATION PIPELINE ($match, $lookup, $group, $sort, $project)
        // --------------------------------------------------------------------
        case 'aggregation':
            $resultado = $mongo->aggregateItensPorCategoriaELocal();

            echo json_encode([
                'success' => true,
                'pipeline_stages' => [
                    'stage_1' => '$match: filtrar status pendente/resgatado',
                    'stage_2' => '$lookup: join com coleção locais via local_id',
                    'stage_3' => '$group: agrupar por categoria, somar valor_estimado, total_itens e calcular taxa de resolução',
                    'stage_4' => '$project: formatar campos de saída e moeda',
                    'stage_5' => '$sort: ordenar por total_registrado DESC'
                ],
                'data' => $resultado
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        // --------------------------------------------------------------------
        // 5. DEMONSTRAÇÃO E EXPLICATIVO DE ÍNDICES (IXSCAN vs COLLSCAN)
        // --------------------------------------------------------------------
        case 'explain_index':
            $cat = $_GET['categoria'] ?? 'Eletrônicos';
            $stat = $_GET['status'] ?? 'pendente';
            $filter = ['categoria' => $cat, 'status' => $stat];

            $explainResult = $mongo->explainQuery('itens_perdidos', $filter);

            echo json_encode([
                'success' => true,
                'explain' => $explainResult
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        // --------------------------------------------------------------------
        // 6. INSPEÇÃO DAS 4 ESTRUTURAS DO REDIS
        // --------------------------------------------------------------------
        case 'redis_inspect':
            $strings = [
                'stats:total_lost' => $redis->get('stats:total_lost'),
                'stats:total_found' => $redis->get('stats:total_found'),
                'stats:total_users' => $redis->get('stats:total_users'),
                'item:lost:lost_001' => json_decode($redis->get('item:lost:lost_001') ?? 'null', true)
            ];

            $hashes = [
                'user:session:sess_token_1' => $redis->hgetall('user:session:sess_token_1'),
                'user:session:sess_token_2' => $redis->hgetall('user:session:sess_token_2')
            ];

            $zsets = [
                'items:recent:zset' => $redis->zrange('items:recent:zset', 0, 9, true)
            ];

            $sets_and_lists = [
                'categories:set' => $redis->smembers('categories:set'),
                'returns:recent:list' => $redis->lrange('returns:recent:list', 0, 4)
            ];

            echo json_encode([
                'success' => true,
                'redis_connection_active' => $redis->isConnected(),
                'structures' => [
                    '1_string' => [
                        'descricao' => 'Estrutura String: Chave-Valor simples usada para Contadores Globais e Objetos individuais em cache.',
                        'dados' => $strings
                    ],
                    '2_hash' => [
                        'descricao' => 'Estrutura Hash: Mapeamento de campos e valores ideal para guardar Sessões de Usuários e Perfis.',
                        'dados' => $hashes
                    ],
                    '3_sorted_set' => [
                        'descricao' => 'Estrutura Sorted Set (ZSET): Conjunto ordenado por pontuação (Score/Timestamp). Usado para ranking/feed temporal.',
                        'dados' => $zsets
                    ],
                    '4_set_and_list' => [
                        'descricao' => 'Estrutura Set e List: Conjunto de elementos únicos (Categories SET) e Fila Sequencial de Auditoria (Returns LIST).',
                        'dados' => $sets_and_lists
                    ]
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception("Ação inválida especificada: {$action}");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
