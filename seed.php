<?php
/**
 * seed.php
 * Script de População (Seeder)
 * Popula exatamente 20 documentos em cada uma das 5 Coleções do MongoDB (100 documentos no total)
 * E hidrata as 4 estruturas de dados no Redis.
 */

require_once __DIR__ . '/config.php';

echo "=========================================================\n";
echo " POPULANDO BANCO DE DADOS MONGODB E CACHE REDIS \n";
echo "=========================================================\n\n";

// 1. COLEÇÃO: LOCAIS (20 Documentos)
$locais = [];
$blocos = ['Bloco A', 'Bloco B', 'Bloco C', 'Bloco D', 'Bloco Central', 'Biblioteca', 'Restaurante Universitário', 'Laboratórios de Informática', 'Ginásio Esportivo', 'Auditório Principal', 'Estacionamento Norte', 'Estacionamento Sul', 'Praça de Convivência', 'Secretaria Acadêmica', 'Coordenação de Cursos', 'Setor de Achados e Perdidos', 'Cantina II', 'Quadra Poliesportiva', 'Laboratório de Química', 'Sala de Estudos 24h'];

foreach ($blocos as $idx => $nomeLoc) {
    $locId = 'loc_' . sprintf('%03d', $idx + 1);
    $locais[] = [
        '_id' => $locId,
        'nome_local' => $nomeLoc,
        'ponto_referencia' => 'Próximo ao acesso ' . ($idx + 1),
        'andar' => (($idx % 3) + 1) . 'º Andar',
        'responsavel' => 'Guarda ' . chr(65 + ($idx % 5)),
        'ativo' => true,
        'criado_em' => date('Y-m-d H:i:s', strtotime("-$idx days"))
    ];
}

$mongo->saveCollectionData('locais', $locais);
echo "✔ [MongoDB] Coleção 'locais': 20 documentos inseridos.\n";

// 2. COLEÇÃO: USUÁRIOS (20 Documentos)
$usuarios = [];
$perfis = ['Aluno', 'Professor', 'Funcionário', 'Visitante', 'Administrador'];
$nomes = ['Carlos Silva', 'Ana Oliveira', 'Mariana Santos', 'Lucas Pereira', 'Beatriz Costa', 'Fernanda Lima', 'Gabriel Souza', 'Rafael Rodrigues', 'Juliana Alves', 'Thiago Martins', 'Camila Ribeiro', 'Rodrigo Carvalho', 'Patricia Mendes', 'Vinicius Ramos', 'Amanda Ferreira', 'Marcelo Barbosa', 'Larissa Gomez', 'Bruno Teixeira', 'Letícia Castro', 'Diego Rocha'];

foreach ($nomes as $idx => $nome) {
    $usrId = 'usr_' . sprintf('%03d', $idx + 1);
    $usuarios[] = [
        '_id' => $usrId,
        'nome' => $nome,
        'email' => strtolower(explode(' ', $nome)[0]) . ($idx + 1) . '@faculdade.edu.br',
        'telefone' => '(11) 9' . rand(1000, 9999) . '-' . rand(1000, 9999),
        'perfil' => $perfis[$idx % count($perfis)],
        'matricula' => '2026' . sprintf('%04d', $idx + 100),
        'criado_em' => date('Y-m-d H:i:s', strtotime("-$idx days"))
    ];
}

$mongo->saveCollectionData('usuarios', $usuarios);
echo "✔ [MongoDB] Coleção 'usuarios': 20 documentos inseridos.\n";

// 3. COLEÇÃO: ITENS PERDIDOS (20 Documentos)
$itensPerdidos = [];
$categorias = ['Eletrônicos', 'Documentos', 'Material Escolar', 'Acessórios', 'Vestuário', 'Chaves', 'Outros'];
$itensNomes = [
    'iPhone 13 Preto', 'Carteira de Couro Marrom', 'Mochila Dell Azul', 'Chaveiro com 3 Chaves e Alarme', 'Casaco de Frio Preto Nike',
    'Caderno Universitário 10 Matérias', 'Garrafa Térmica Stanley Verde', 'AirPods Pro 2ª Geração', 'RG - Registro Geral', 'Calculadora Científica Casio',
    'Relógio Smartwatch Amazfit', 'Óculos de Grau Ray-Ban', 'Guarda-chuva Automático', 'Pen Drive Sandisk 64GB', 'Fone de Ouvido Bluetooth JBL',
    'Pasta de Documentos Sanfonada', 'Chave de Carro Fiat', 'Jaqueta Jeans Levis', 'Tablet Samsung Galaxy Tab', 'Carregador Anker USBC'
];

foreach ($itensNomes as $idx => $nomeItem) {
    $itemId = 'lost_' . sprintf('%03d', $idx + 1);
    $locIndex = $idx % 20;
    $usrIndex = $idx % 20;
    $cat = $categorias[$idx % count($categorias)];
    $status = ($idx % 3 === 0) ? 'resgatado' : 'pendente';
    $dataPerda = date('Y-m-d H:i:s', strtotime("-$idx days -5 hours"));

    $itensPerdidos[] = [
        '_id' => $itemId,
        'titulo' => $nomeItem,
        'categoria' => $cat,
        'descricao' => "Item '{$nomeItem}' esquecido nas imediações do local. Possui marcações visíveis e características específicas.",
        'local_id' => $locais[$locIndex]['_id'],
        'proprietario_usuario_id' => $usuarios[$usrIndex]['_id'],
        'data_perda' => $dataPerda,
        'status' => $status,
        'recompensa_oferecida' => ($idx % 4 === 0),
        'valor_estimado' => rand(50, 1500),
        'criado_em' => $dataPerda
    ];
}

$mongo->saveCollectionData('itens_perdidos', $itensPerdidos);
echo "✔ [MongoDB] Coleção 'itens_perdidos': 20 documentos inseridos.\n";

// 4. COLEÇÃO: ITENS ENCONTRADOS (20 Documentos)
$itensEncontrados = [];
$achadosNomes = [
    'Carregador de Notebook Lenovo', 'Guarda-Chuva Vermelho', 'Fichário Escolar Preto', 'Cartão de Estudante SPTrans', 'Estojo com Canetas Variadas',
    'Agasalho de Moletom Cinza', 'Capacete de Moto Preto', 'Nécessaire de Higiene', 'Crachá de Identificação', 'Caixa de Som Portátil',
    'Bicicleta Dobrável Amarela', 'Óculos de Sol HB', 'Garrafa de Água Transparente', 'Chave de Fenda Multiuso', 'Carregador Portátil Power Bank',
    'Luvas de Ciclismo', 'Livro de Cálculo Vol 1', 'Chapéu Boné Adidas', 'Mouse Sem Fio Logitech', 'Mochila de Costas Vermelha'
];

foreach ($achadosNomes as $idx => $nomeAchado) {
    $foundId = 'found_' . sprintf('%03d', $idx + 1);
    $locIndex = ($idx + 2) % 20;
    $usrIndex = ($idx + 3) % 20;
    $cat = $categorias[($idx + 1) % count($categorias)];

    $itensEncontrados[] = [
        '_id' => $foundId,
        'titulo' => $nomeAchado,
        'categoria' => $cat,
        'descricao' => "Objeto '{$nomeAchado}' encontrado em perfeito estado pelo setor de limpeza/segurança.",
        'local_id' => $locais[$locIndex]['_id'],
        'encontrado_por_usuario_id' => $usuarios[$usrIndex]['_id'],
        'data_achado' => date('Y-m-d H:i:s', strtotime("-$idx days -2 hours")),
        'custodia_status' => ($idx % 2 === 0) ? 'guarda_central' : 'entregue',
        'armario_guarda' => 'Armário #' . sprintf('%02d', $idx + 1),
        'criado_em' => date('Y-m-d H:i:s', strtotime("-$idx days"))
    ];
}

$mongo->saveCollectionData('itens_encontrados', $itensEncontrados);
echo "✔ [MongoDB] Coleção 'itens_encontrados': 20 documentos inseridos.\n";

// 5. COLEÇÃO: DEVOLUÇÕES (20 Documentos)
$devolucoes = [];
foreach ($itensPerdidos as $idx => $item) {
    $devId = 'ret_' . sprintf('%03d', $idx + 1);
    $dataDev = date('Y-m-d H:i:s', strtotime("-$idx days +4 hours"));

    $devolucoes[] = [
        '_id' => $devId,
        'item_id' => $item['_id'],
        'usuario_recebedor_id' => $item['proprietario_usuario_id'],
        'responsavel_entrega_id' => 'usr_003', // Guarda / Administrador
        'protocolo_devolucao' => 'PROT-2026-' . sprintf('%05d', $idx + 1000),
        'documento_verificado' => 'RG / CNH Apresentado',
        'data_devolucao' => $dataDev,
        'observacoes' => 'Devolução realizada mediante comprovação de propriedade e assinatura do termo de resgate.',
        'criado_em' => $dataDev
    ];
}

$mongo->saveCollectionData('devolucoes', $devolucoes);
echo "✔ [MongoDB] Coleção 'devolucoes': 20 documentos inseridos.\n";

echo "\n---------------------------------------------------------\n";
echo " HIDRATANDO AS 4 ESTRUTURAS DE DADOS NO REDIS \n";
echo "---------------------------------------------------------\n\n";

$redis->flushAll();

// 1. REDIS STRUCT 1: STRING (Contadores Globais e Detalhes Rápidos)
$redis->set('stats:total_lost', count($itensPerdidos));
$redis->set('stats:total_found', count($itensEncontrados));
$redis->set('stats:total_users', count($usuarios));
$redis->set('item:lost:lost_001', json_encode($itensPerdidos[0], JSON_UNESCAPED_UNICODE), 600);
echo "✔ [Redis 1 - STRING] Gravadas chaves 'stats:total_lost', 'stats:total_found' e 'item:lost:lost_001' (com TTL 600s).\n";

// 2. REDIS STRUCT 2: HASH (Sessão de Usuários e Perfil Estruturado)
foreach ($usuarios as $idx => $u) {
    if ($idx < 5) { // Criar 5 sessões ativas
        $token = 'sess_token_' . ($idx + 1);
        $redis->hset("user:session:{$token}", 'user_id', $u['_id']);
        $redis->hset("user:session:{$token}", 'nome', $u['nome']);
        $redis->hset("user:session:{$token}", 'email', $u['email']);
        $redis->hset("user:session:{$token}", 'perfil', $u['perfil']);
        $redis->hset("user:session:{$token}", 'login_timestamp', time() - ($idx * 3600));
    }
}
echo "✔ [Redis 2 - HASH] Criadas hashes 'user:session:sess_token_1' até 'sess_token_5'.\n";

// 3. REDIS STRUCT 3: SORTED SET (ZSET - Ranking e Feed Temporal de Itens Recentes)
foreach ($itensPerdidos as $item) {
    $scoreTimestamp = strtotime($item['data_perda']);
    $redis->zadd('items:recent:zset', $scoreTimestamp, $item['_id'] . ':' . $item['titulo']);
}
echo "✔ [Redis 3 - SORTED SET] Populado 'items:recent:zset' com 20 itens ordenados por Timestamp como Score.\n";

// 4. REDIS STRUCT 4: SET e LIST (Fila de Logs de Devoluções e Conjunto de Categorias)
foreach ($categorias as $cat) {
    $redis->sadd('categories:set', $cat);
}
foreach ($devolucoes as $idx => $dev) {
    $logEntry = "[{$dev['data_devolucao']}] Protocolo {$dev['protocolo_devolucao']} concluído para o item {$dev['item_id']}";
    $redis->lpush('returns:recent:list', $logEntry);
}
echo "✔ [Redis 4 - SET & LIST] Criado SET 'categories:set' (categorias únicas) e LIST 'returns:recent:list' (logs de auditoria).\n";

echo "\n=========================================================\n";
echo " SEEDING CONCLUÍDO COM SUCESSO! TOTAL: 100 DOCS NO MONGO \n";
echo "=========================================================\n";
?>
