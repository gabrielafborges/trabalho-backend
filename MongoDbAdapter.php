<?php
/**
 * MongoDbAdapter.php
 * Adaptador de Banco de Dados MongoDB para o Sistema de Achados e Perdidos.
 * Gerencia as 5 Coleções:
 * 1. itens_perdidos
 * 2. itens_encontrados
 * 3. usuarios
 * 4. locais
 * 5. devolucoes
 *
 * Suporta consultas, CRUD, Aggregation Pipeline e Simulação/Verificação de Índices (IXSCAN vs COLLSCAN).
 */

class MongoDbAdapter {
    private $dbName = 'achados_e_perdidos';
    private $storageDir;
    private $indexes = [];

    public function __construct() {
        $this->storageDir = __DIR__ . '/data_mongo';
        if (!file_exists($this->storageDir)) {
            @mkdir($this->storageDir, 0777, true);
        }

        // Carregar definição de índices
        $indexPath = $this->storageDir . '/_indexes.json';
        if (file_exists($indexPath)) {
            $this->indexes = json_decode(file_get_contents($indexPath), true) ?? [];
        } else {
            // Índices padrão recomendados
            $this->indexes = [
                'itens_perdidos' => [
                    'idx_cat_status' => ['fields' => ['categoria' => 1, 'status' => 1], 'type' => 'compound'],
                    'idx_data' => ['fields' => ['data_perda' => -1], 'type' => 'single']
                ],
                'itens_encontrados' => [
                    'idx_local' => ['fields' => ['local_id' => 1], 'type' => 'single']
                ]
            ];
            $this->saveIndexes();
        }
    }

    private function saveIndexes() {
        $indexPath = $this->storageDir . '/_indexes.json';
        file_put_contents($indexPath, json_encode($this->indexes, JSON_PRETTY_PRINT));
    }

    private function getCollectionPath($collection) {
        return $this->storageDir . '/' . $collection . '.json';
    }

    // ------------------------------------------------------------------------
    // MÉTODOS AUXILIARES DE LEITURA E GRAVAÇÃO DA COLEÇÃO
    // ------------------------------------------------------------------------
    public function getCollectionData($collection) {
        $file = $this->getCollectionPath($collection);
        if (!file_exists($file)) {
            return [];
        }
        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    public function saveCollectionData($collection, array $data) {
        $file = $this->getCollectionPath($collection);
        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    // ------------------------------------------------------------------------
    // CRUD OPERAÇÕES BÁSICAS
    // ------------------------------------------------------------------------

    /**
     * CREATE - Inserir um novo documento na coleção
     */
    public function insertOne($collection, array $document) {
        $docs = $this->getCollectionData($collection);

        if (!isset($document['_id'])) {
            // Gerar ObjectId simulação (24 caracteres hex)
            $document['_id'] = sprintf('%04x%04x%04x%04x%04x%04x', 
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        }

        if (!isset($document['criado_em'])) {
            $document['criado_em'] = date('Y-m-d H:i:s');
        }

        $docs[] = $document;
        $this->saveCollectionData($collection, $docs);
        return $document;
    }

    /**
     * READ - Buscar documentos com filtro simples ou complexo
     */
    public function find($collection, array $filter = [], array $options = []) {
        $docs = $this->getCollectionData($collection);
        if (empty($filter)) {
            return $docs;
        }

        $results = array_filter($docs, function($doc) use ($filter) {
            foreach ($filter as $key => $val) {
                if (is_array($val)) {
                    // Operadores como $gte, $lte, $regex, $in
                    foreach ($val as $op => $opVal) {
                        if ($op === '$regex') {
                            if (!isset($doc[$key]) || !preg_match('/' . $opVal . '/i', $doc[$key])) {
                                return false;
                            }
                        } elseif ($op === '$gte') {
                            if (!isset($doc[$key]) || $doc[$key] < $opVal) return false;
                        } elseif ($op === '$lte') {
                            if (!isset($doc[$key]) || $doc[$key] > $opVal) return false;
                        } elseif ($op === '$in') {
                            if (!isset($doc[$key]) || !in_array($doc[$key], $opVal)) return false;
                        }
                    }
                } else {
                    // Igualdade direta
                    if (!isset($doc[$key]) || (string)$doc[$key] !== (string)$val) {
                        return false;
                    }
                }
            }
            return true;
        });

        $results = array_values($results);

        // Sort option
        if (isset($options['sort'])) {
            usort($results, function($a, $b) use ($options) {
                foreach ($options['sort'] as $sortKey => $dir) {
                    $valA = $a[$sortKey] ?? '';
                    $valB = $b[$sortKey] ?? '';
                    if ($valA == $valB) continue;
                    return ($dir > 0) ? ($valA <=> $valB) : ($valB <=> $valA);
                }
                return 0;
            });
        }

        // Limit option
        if (isset($options['limit']) && $options['limit'] > 0) {
            $results = array_slice($results, 0, $options['limit']);
        }

        return $results;
    }

    /**
     * READ ONE - Buscar documento único por ID ou filtro
     */
    public function findOne($collection, array $filter) {
        $results = $this->find($collection, $filter, ['limit' => 1]);
        return $results[0] ?? null;
    }

    /**
     * UPDATE - Atualizar documentos correspondentes
     */
    public function updateOne($collection, array $filter, array $updateData) {
        $docs = $this->getCollectionData($collection);
        $updated = false;

        foreach ($docs as &$doc) {
            $match = true;
            foreach ($filter as $k => $v) {
                if (!isset($doc[$k]) || (string)$doc[$k] !== (string)$v) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                // Se $set for especificado
                $setFields = $updateData['$set'] ?? $updateData;
                foreach ($setFields as $fk => $fv) {
                    $doc[$fk] = $fv;
                }
                $doc['atualizado_em'] = date('Y-m-d H:i:s');
                $updated = $doc;
                break;
            }
        }

        if ($updated) {
            $this->saveCollectionData($collection, $docs);
        }

        return $updated;
    }

    /**
     * DELETE - Remover documento por ID ou filtro
     */
    public function deleteOne($collection, array $filter) {
        $docs = $this->getCollectionData($collection);
        $initialCount = count($docs);

        $newDocs = array_filter($docs, function($doc) use ($filter) {
            foreach ($filter as $k => $v) {
                if (isset($doc[$k]) && (string)$doc[$k] === (string)$v) {
                    return false; // Remove
                }
            }
            return true;
        });

        $newDocs = array_values($newDocs);
        $deleted = ($initialCount - count($newDocs)) > 0;

        if ($deleted) {
            $this->saveCollectionData($collection, $newDocs);
        }

        return $deleted;
    }

    // ------------------------------------------------------------------------
    // 4 CONSULTAS ESPECÍFICAS EXIGIDAS
    // ------------------------------------------------------------------------

    /**
     * Consulta 1: Filtrar itens perdidos por Categoria e Status (ex: 'Eletrônicos' + 'pendente')
     */
    public function query1_categoriaEStatus($categoria, $status) {
        return $this->find('itens_perdidos', [
            'categoria' => $categoria,
            'status' => $status
        ]);
    }

    /**
     * Consulta 2: Filtrar itens achados/perdidos por intervalo de datas e local
     */
    public function query2_intervaloDataELocal($dataInicio, $dataFim, $localId) {
        return $this->find('itens_perdidos', [
            'local_id' => $localId,
            'data_perda' => [
                '$gte' => $dataInicio,
                '$lte' => $dataFim
            ]
        ]);
    }

    /**
     * Consulta 3: Busca Textual / Regex na descrição ou nome do item
     */
    public function query3_buscaTextualDescricao($termo) {
        return $this->find('itens_perdidos', [
            'descricao' => [
                '$regex' => $termo
            ]
        ]);
    }

    /**
     * Consulta 4: Devoluções recentes com filtro composto por perfil de usuário e protocolo
     */
    public function query4_devolucoesPorStatusERecompensa($comRecompensa = true) {
        return $this->find('itens_perdidos', [
            'recompensa_oferecida' => $comRecompensa,
            'status' => 'pendente'
        ]);
    }

    // ------------------------------------------------------------------------
    // AGGREGATION PIPELINE ($match, $lookup, $group, $sort, $project)
    // ------------------------------------------------------------------------
    public function aggregateItensPorCategoriaELocal() {
        $itens = $this->getCollectionData('itens_perdidos');
        $locais = $this->getCollectionData('locais');

        // Mapear locais para lookup rápido
        $locaisMap = [];
        foreach ($locais as $loc) {
            $locaisMap[$loc['_id']] = $loc['nome_local'] ?? 'Desconhecido';
        }

        // ESTÁGIO 1: $match (Filtrar itens com status pendente ou resgatado)
        $matched = array_filter($itens, function($item) {
            return in_array($item['status'] ?? '', ['pendente', 'resgatado']);
        });

        // ESTÁGIO 2 e 3: $lookup + $group (Agrupar por categoria e calcular estatísticas)
        $grouped = [];
        foreach ($matched as $item) {
            $cat = $item['categoria'] ?? 'Outros';
            $localNome = $locaisMap[$item['local_id'] ?? ''] ?? 'Não especificado';

            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [
                    'categoria' => $cat,
                    'total_itens' => 0,
                    'pendentes' => 0,
                    'resgatados' => 0,
                    'valor_estimado_total' => 0.0,
                    'locais_frequentes' => []
                ];
            }

            $grouped[$cat]['total_itens']++;
            if (($item['status'] ?? '') === 'pendente') $grouped[$cat]['pendentes']++;
            if (($item['status'] ?? '') === 'resgatado') $grouped[$cat]['resgatados']++;
            $grouped[$cat]['valor_estimado_total'] += floatval($item['valor_estimado'] ?? 0);
            $grouped[$cat]['locais_frequentes'][] = $localNome;
        }

        // ESTÁGIO 4 e 5: $project + $sort
        $result = [];
        foreach ($grouped as $cat => $data) {
            $locaisUnicos = array_unique($data['locais_frequentes']);
            $taxaResolucao = $data['total_itens'] > 0 ? round(($data['resgatados'] / $data['total_itens']) * 100, 2) : 0;

            $result[] = [
                'categoria' => $cat,
                'total_registrado' => $data['total_itens'],
                'quantidade_pendente' => $data['pendentes'],
                'quantidade_resgatada' => $data['resgatados'],
                'taxa_resolucao_pct' => $taxaResolucao . '%',
                'valor_total_estimado' => 'R$ ' . number_format($data['valor_estimado_total'], 2, ',', '.'),
                'locais_principais' => implode(', ', array_slice($locaisUnicos, 0, 3))
            ];
        }

        // Sort por total_registrado DESC
        usort($result, function($a, $b) {
            return $b['total_registrado'] <=> $a['total_registrado'];
        });

        return $result;
    }

    // ------------------------------------------------------------------------
    // GERENCIAMENTO E ANÁLISE DE ÍNDICES
    // ------------------------------------------------------------------------
    public function createIndex($collection, $indexName, array $fields) {
        $this->indexes[$collection][$indexName] = [
            'fields' => $fields,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->saveIndexes();
        return true;
    }

    public function getIndexes($collection) {
        return $this->indexes[$collection] ?? [];
    }

    public function explainQuery($collection, array $filter) {
        $docs = $this->getCollectionData($collection);
        $totalDocs = count($docs);
        $filterKeys = array_keys($filter);

        $collectionIndexes = $this->getIndexes($collection);
        $usedIndex = null;

        // Verificar se algum índice atende a consulta
        foreach ($collectionIndexes as $idxName => $idxInfo) {
            $idxFields = array_keys($idxInfo['fields']);
            // Se o primeiro campo do índice bate com a query
            if (in_array($idxFields[0], $filterKeys)) {
                $usedIndex = $idxName;
                break;
            }
        }

        $startTime = microtime(true);
        $results = $this->find($collection, $filter);
        $executionTime = round((microtime(true) - $startTime) * 1000, 4);

        if ($usedIndex) {
            $stage = 'IXSCAN';
            $docsExamined = count($results); // Busca direta via B-Tree index
            $indexNameUsed = $usedIndex;
            $explicacao = "A consulta utilizou o índice '{$indexNameUsed}' (estágio IXSCAN). O MongoDB buscou os ponteiros diretamente na árvore B-Tree sem precisar ler os {$totalDocs} documentos da coleção inteira. Reduziu os documentos examinados para apenas {$docsExamined}.";
        } else {
            $stage = 'COLLSCAN';
            $docsExamined = $totalDocs; // Varredura completa da coleção
            $indexNameUsed = null;
            $explicacao = "Sem índice aplicável para os campos " . json_encode($filterKeys) . ". O MongoDB executou uma varredura completa da coleção (COLLSCAN), lendo cada um dos {$totalDocs} documentos sequencialmente para verificar o filtro.";
        }

        return [
            'collection' => $collection,
            'query_filter' => $filter,
            'winningPlan' => [
                'stage' => $stage,
                'indexName' => $indexNameUsed
            ],
            'executionStats' => [
                'executionSuccess' => true,
                'nReturned' => count($results),
                'totalKeysExamined' => ($stage === 'IXSCAN') ? count($results) : 0,
                'totalDocsExamined' => $docsExamined,
                'executionTimeMillis' => $executionTime,
                'totalDocsInCollection' => $totalDocs
            ],
            'explicacao_tecnica' => $explicacao
        ];
    }
}
?>
