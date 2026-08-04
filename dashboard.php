<?php
/**
 * dashboard.php
 * Painel de Controle Principal do Sistema de Achados e Perdidos (MongoDB + Redis)
 * Interface Estilizada Baseada no Template BISNYS (Emerald Green & Clean White)
 */

require_once __DIR__ . '/config.php';

// Carregar contadores iniciais
$totalLost = count($mongo->find('itens_perdidos'));
$totalFound = count($mongo->find('itens_encontrados'));
$totalUsers = count($mongo->find('usuarios'));
$totalLocais = count($mongo->find('locais'));
$totalDevolucoes = count($mongo->find('devolucoes'));

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Carlos Silva';
$usuarioPerfil = $_SESSION['usuario_perfil'] ?? 'Aluno';
$redisStatus = $redis->isConnected() ? 'ONLINE (Porta 6379)' : 'STANDALONE / RESP PROTOCOL';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Achados e Perdidos | Dashboard MongoDB & Redis</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- TOP ANNOUNCEMENT BAR (Estilo BISNYS Header) -->
    <div class="top-announcement-bar">
        <div>
            <strong>TRENDING</strong> Sistema de Achados & Perdidos da Faculdade — Otimizado com MongoDB & Redis
        </div>
        <div>
            📞 Suporte: +55 (11) 4002-8922 &nbsp;|&nbsp; ✉️ achados@faculdade.edu.br
        </div>
    </div>

    <!-- APP NAVBAR HEADER -->
    <header class="app-header">
        <div class="brand">
            <div class="brand-icon">📦</div>
            <div class="brand-text">
                <h1>Achados & Perdidos</h1>
                <p>Plataforma de Gestão de Objetos Resgatados</p>
            </div>
        </div>
        <div class="nav-badges">
            <span class="badge badge-mongo">
                <span class="pulse-dot" style="background: #10b981;"></span>
                MongoDB: 5 Coleções (100 Docs)
            </span>
            <span class="badge badge-redis">
                <span class="pulse-dot" style="background: #ef4444;"></span>
                Redis: <?= $redisStatus ?>
            </span>
            <span class="badge badge-user">
                👤 <?= htmlspecialchars($usuarioNome) ?> (<?= htmlspecialchars($usuarioPerfil) ?>)
            </span>
            <a href="logout.php" class="btn btn-danger btn-sm">Sair ➔</a>
        </div>
    </header>

    <div class="container">

        <!-- HERO SECTION (Estilo BISNYS Banner) -->
        <div class="hero-section">
            <div class="hero-content">
                <h2>Gerencie <span>Achados e Perdidos</span> com Alta Performance e Cache Redis</h2>
                <p>Backend moderno migrado para MongoDB com 5 coleções relacionais, 4 consultas complexas, Aggregation Pipeline, otimização de índices e cache em memória via Redis.</p>
            </div>
            <div>
                <button class="btn btn-primary" onclick="switchTab('tab-crud')">Iniciar Gestão ➔</button>
            </div>
        </div>

        <!-- TOP METRICS CARDS -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-header-row">
                    <span class="metric-title">Itens Perdidos</span>
                    <div class="metric-icon-wrapper">🔍</div>
                </div>
                <div class="metric-value"><?= $totalLost ?></div>
                <div class="metric-sub">Coleção: <code>itens_perdidos</code></div>
            </div>

            <div class="metric-card">
                <div class="metric-header-row">
                    <span class="metric-title">Itens Encontrados</span>
                    <div class="metric-icon-wrapper">🎯</div>
                </div>
                <div class="metric-value"><?= $totalFound ?></div>
                <div class="metric-sub">Coleção: <code>itens_encontrados</code></div>
            </div>

            <div class="metric-card">
                <div class="metric-header-row">
                    <span class="metric-title">Usuários Cadastrados</span>
                    <div class="metric-icon-wrapper">👥</div>
                </div>
                <div class="metric-value"><?= $totalUsers ?></div>
                <div class="metric-sub">Coleção: <code>usuarios</code></div>
            </div>

            <div class="metric-card">
                <div class="metric-header-row">
                    <span class="metric-title">Devoluções Resgatadas</span>
                    <div class="metric-icon-wrapper">✅</div>
                </div>
                <div class="metric-value"><?= $totalDevolucoes ?></div>
                <div class="metric-sub">Coleção: <code>devolucoes</code></div>
            </div>
        </div>

        <!-- NAVIGATION TABS -->
        <div class="tabs-nav">
            <button class="tab-btn active" onclick="switchTab('tab-crud', event)">
                <span>📁</span> 1. CRUD (5 Coleções Mongo)
            </button>
            <button class="tab-btn" onclick="switchTab('tab-queries', event)">
                <span>🔍</span> 2. 4 Consultas MongoDB
            </button>
            <button class="tab-btn" onclick="switchTab('tab-aggregation', event)">
                <span>📊</span> 3. Aggregation Pipeline
            </button>
            <button class="tab-btn" onclick="switchTab('tab-indexes', event)">
                <span>⚡</span> 4. Otimização & Índices (IXSCAN)
            </button>
            <button class="tab-btn" onclick="switchTab('tab-redis', event)">
                <span>🚀</span> 5. Redis (4 Estruturas & Cache)
            </button>
        </div>

        <!-- TAB 1: CRUD MONODB -->
        <div id="tab-crud" class="tab-content">
            <div class="card-box">
                <div class="card-header">
                    <h2>Gerenciamento CRUD (5 Coleções do MongoDB)</h2>
                    <select id="select-collection" class="form-control" onchange="loadCollectionData()" style="width: auto; cursor: pointer;">
                        <option value="itens_perdidos">Coleção 1: itens_perdidos (20 docs)</option>
                        <option value="itens_encontrados">Coleção 2: itens_encontrados (20 docs)</option>
                        <option value="usuarios">Coleção 3: usuarios (20 docs)</option>
                        <option value="locais">Coleção 4: locais (20 docs)</option>
                        <option value="devolucoes">Coleção 5: devolucoes (20 docs)</option>
                    </select>
                </div>

                <!-- Formulário para Criar Novo Item (CREATE) -->
                <div class="purple-create-box">
                    <h3><span>✨</span> Inserir Novo Registro no MongoDB (CREATE & Invalidação de Cache)</h3>
                    <form id="form-create-item" onsubmit="handleCreateItem(event)">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Título / Nome do Item:</label>
                                <input type="text" id="input-titulo" class="form-control" placeholder="Ex: Notebook Asus ZenBook Pro" required>
                            </div>
                            <div class="form-group">
                                <label>Categoria:</label>
                                <select id="input-categoria" class="form-control">
                                    <option value="Eletrônicos">Eletrônicos</option>
                                    <option value="Documentos">Documentos</option>
                                    <option value="Material Escolar">Material Escolar</option>
                                    <option value="Acessórios">Acessórios</option>
                                    <option value="Vestuário">Vestuário</option>
                                    <option value="Chaves">Chaves</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Valor Estimado (R$):</label>
                                <input type="number" id="input-valor" class="form-control" value="1200" required>
                            </div>
                            <div class="form-group" style="justify-content: flex-end;">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">+ Salvar no Mongo ➔</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="custom-table" id="table-crud">
                        <thead>
                            <tr id="crud-headers">
                                <th>ID</th>
                                <th>Título / Nome</th>
                                <th>Categoria / Tipo</th>
                                <th>Status / Custódia</th>
                                <th>Data de Registro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="crud-body">
                            <!-- Preenchido via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: 4 CONSULTAS MONGODB -->
        <div id="tab-queries" class="tab-content" style="display: none;">
            <div class="card-box">
                <div class="card-header">
                    <h2>Testador das 4 Consultas MongoDB Requeridas</h2>
                </div>

                <div class="form-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <!-- Query 1 -->
                    <div style="background: #ffffff; padding: 22px; border-radius: 14px; border: 1.5px solid var(--border-color); box-shadow: var(--card-shadow);">
                        <h4 style="color: var(--emerald-primary); margin-bottom: 8px; font-family: var(--font-heading); font-size: 1.1rem;">1. Categoria & Status</h4>
                        <p style="font-size: 0.9rem; color: var(--text-body); margin-bottom: 14px; font-weight: 500;">
                            Filtro composto no Mongo: <code style="color: var(--emerald-primary); background: var(--emerald-light); padding: 2px 6px; border-radius: 4px;">{ categoria: "Eletrônicos", status: "pendente" }</code>
                        </p>
                        <button class="btn btn-primary btn-sm" onclick="runQuery('query1', {categoria: 'Eletrônicos', status: 'pendente'})">▶ Executar Consulta 1</button>
                    </div>

                    <!-- Query 2 -->
                    <div style="background: #ffffff; padding: 22px; border-radius: 14px; border: 1.5px solid var(--border-color); box-shadow: var(--card-shadow);">
                        <h4 style="color: var(--emerald-primary); margin-bottom: 8px; font-family: var(--font-heading); font-size: 1.1rem;">2. Intervalo de Datas e Local</h4>
                        <p style="font-size: 0.9rem; color: var(--text-body); margin-bottom: 14px; font-weight: 500;">
                            Filtro no Mongo: <code style="color: var(--emerald-primary); background: var(--emerald-light); padding: 2px 6px; border-radius: 4px;">{ local_id: "loc_001", data_perda: { $gte, $lte } }</code>
                        </p>
                        <button class="btn btn-primary btn-sm" onclick="runQuery('query2', {local_id: 'loc_001'})">▶ Executar Consulta 2</button>
                    </div>

                    <!-- Query 3 -->
                    <div style="background: #ffffff; padding: 22px; border-radius: 14px; border: 1.5px solid var(--border-color); box-shadow: var(--card-shadow);">
                        <h4 style="color: var(--emerald-primary); margin-bottom: 8px; font-family: var(--font-heading); font-size: 1.1rem;">3. Busca Textual por Regex</h4>
                        <p style="font-size: 0.9rem; color: var(--text-body); margin-bottom: 14px; font-weight: 500;">
                            Filtro de busca por termo na descrição: <code style="color: var(--emerald-primary); background: var(--emerald-light); padding: 2px 6px; border-radius: 4px;">{ descricao: { $regex: "iPhone" } }</code>
                        </p>
                        <button class="btn btn-primary btn-sm" onclick="runQuery('query3', {termo: 'iPhone'})">▶ Executar Consulta 3</button>
                    </div>

                    <!-- Query 4 -->
                    <div style="background: #ffffff; padding: 22px; border-radius: 14px; border: 1.5px solid var(--border-color); box-shadow: var(--card-shadow);">
                        <h4 style="color: var(--emerald-primary); margin-bottom: 8px; font-family: var(--font-heading); font-size: 1.1rem;">4. Recompensa Oferecida & Status</h4>
                        <p style="font-size: 0.9rem; color: var(--text-body); margin-bottom: 14px; font-weight: 500;">
                            Filtro composto no Mongo: <code style="color: var(--emerald-primary); background: var(--emerald-light); padding: 2px 6px; border-radius: 4px;">{ recompensa_oferecida: true, status: "pendente" }</code>
                        </p>
                        <button class="btn btn-primary btn-sm" onclick="runQuery('query4', {recompensa: 1})">▶ Executar Consulta 4</button>
                    </div>
                </div>

                <div style="margin-top: 26px;">
                    <h3 style="font-size: 1.05rem; margin-bottom: 14px; color: var(--text-dark); font-family: var(--font-heading); font-weight: 800;">Resultado da Consulta (JSON Terminal):</h3>
                    
                    <div class="json-viewer-window">
                        <div class="terminal-header">
                            <span class="dot dot-red"></span>
                            <span class="dot dot-yellow"></span>
                            <span class="dot dot-green"></span>
                            <span class="terminal-title">mongodb://localhost:27017/achados_e_perdidos</span>
                        </div>
                        <pre class="json-viewer" id="query-result">Clique em uma das consultas acima para disparar o filtro e visualizar a resposta JSON do MongoDB...</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: AGGREGATION PIPELINE -->
        <div id="tab-aggregation" class="tab-content" style="display: none;">
            <div class="card-box">
                <div class="card-header">
                    <h2>Aggregation Pipeline do MongoDB</h2>
                    <button class="btn btn-primary btn-sm" onclick="loadAggregationData()">⚡ Rodar Pipeline Completo ➔</button>
                </div>

                <div style="background: var(--emerald-light); border: 2px solid #a7f3d0; padding: 24px; border-radius: 16px; margin-bottom: 26px;">
                    <h4 style="color: var(--teal-dark); margin-bottom: 12px; font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800;">Estágios do Pipeline de Agregação Executados:</h4>
                    <ol style="margin-left: 20px; font-size: 0.95rem; color: var(--text-dark); font-weight: 600; line-height: 1.8;">
                        <li><code style="color: var(--emerald-primary); background: #ffffff; padding: 2px 8px; border-radius: 4px;">$match</code>: Seleciona itens com status <strong style="color: #d97706;">pendente</strong> ou <strong style="color: #059669;">resgatado</strong>.</li>
                        <li><code style="color: var(--emerald-primary); background: #ffffff; padding: 2px 8px; border-radius: 4px;">$lookup</code>: Realiza o JOIN relacional com a coleção <strong>locais</strong> trazendo o nome do bloco.</li>
                        <li><code style="color: var(--emerald-primary); background: #ffffff; padding: 2px 8px; border-radius: 4px;">$group</code>: Agrupa por <strong>categoria</strong>, soma o valor acumulado e contagens.</li>
                        <li><code style="color: var(--emerald-primary); background: #ffffff; padding: 2px 8px; border-radius: 4px;">$project</code>: Calcula a <strong>taxa de resolução (%)</strong> e formata a moeda em Real (R$).</li>
                        <li><code style="color: var(--emerald-primary); background: #ffffff; padding: 2px 8px; border-radius: 4px;">$sort</code>: Ordena o resultado final em ordem decrescente pelo volume de registros.</li>
                    </ol>
                </div>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Total Registrado</th>
                                <th>Pendentes</th>
                                <th>Resgatados</th>
                                <th>Taxa de Resolução (%)</th>
                                <th>Valor Total Estimado</th>
                                <th>Locais Principais</th>
                            </tr>
                        </thead>
                        <tbody id="aggregation-body">
                            <tr><td colspan="7" style="text-align: center; color: var(--text-muted); font-weight: 600;">Clique em "Rodar Pipeline Completo" para processar a agregação...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 4: ÍNDICES E OTIMIZAÇÃO (IXSCAN vs COLLSCAN) -->
        <div id="tab-indexes" class="tab-content" style="display: none;">
            <div class="card-box">
                <div class="card-header">
                    <h2>Análise e Otimização por Índices MongoDB (Explain Plan)</h2>
                    <button class="btn btn-primary btn-sm" onclick="runIndexExplain()">⚡ Testar Explain Plan na Consulta 1 ➔</button>
                </div>

                <div id="index-explain-output">
                    <p style="color: var(--text-body); font-weight: 600; font-size: 1rem;">Clique no botão acima para analisar os planos de execução <code>IXSCAN</code> vs <code>COLLSCAN</code> do MongoDB.</p>
                </div>
            </div>
        </div>

        <!-- TAB 5: REDIS (4 ESTRUTURAS & FLUXO DE CACHE) -->
        <div id="tab-redis" class="tab-content" style="display: none;">
            <div class="card-box">
                <div class="card-header">
                    <h2>Estruturas de Dados do Redis & Fluxo de Cache (Cache-Aside Pattern)</h2>
                    <button class="btn btn-primary btn-sm" onclick="testCacheAsideFlow()">🚀 Testar Fluxo de Cache (HIT vs MISS) ➔</button>
                </div>

                <div id="cache-flow-demo" style="background: #ecfdf5; border: 2px solid #a7f3d0; border-radius: 16px; padding: 24px; margin-bottom: 26px; display: none;">
                    <h4 style="color: #047857; margin-bottom: 10px; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800;">Resultado do Teste do Fluxo de Cache:</h4>
                    <p id="cache-flow-text" style="font-size: 0.98rem; color: var(--text-dark); font-weight: 600; line-height: 1.7;"></p>
                </div>

                <h3 style="font-size: 1.15rem; margin-bottom: 20px; color: var(--text-dark); font-family: var(--font-heading); font-weight: 800;">Inspeção em Tempo Real das 4 Estruturas no Redis:</h3>
                
                <div class="form-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <!-- Struct 1 -->
                    <div>
                        <h4 style="color: var(--emerald-primary); margin-bottom: 10px; font-family: var(--font-heading); font-size: 1.05rem;">1. STRING (Cache Chave-Valor)</h4>
                        <div class="json-viewer-window">
                            <div class="terminal-header">
                                <span class="dot dot-red"></span><span class="dot dot-yellow"></span><span class="dot dot-green"></span>
                                <span class="terminal-title">redis://127.0.0.1:6379/strings</span>
                            </div>
                            <pre class="json-viewer" id="redis-struct-1" style="max-height: 220px;">Carregando...</pre>
                        </div>
                    </div>

                    <!-- Struct 2 -->
                    <div>
                        <h4 style="color: var(--emerald-primary); margin-bottom: 10px; font-family: var(--font-heading); font-size: 1.05rem;">2. HASH (Sessões de Usuários)</h4>
                        <div class="json-viewer-window">
                            <div class="terminal-header">
                                <span class="dot dot-red"></span><span class="dot dot-yellow"></span><span class="dot dot-green"></span>
                                <span class="terminal-title">redis://127.0.0.1:6379/hashes</span>
                            </div>
                            <pre class="json-viewer" id="redis-struct-2" style="max-height: 220px;">Carregando...</pre>
                        </div>
                    </div>

                    <!-- Struct 3 -->
                    <div>
                        <h4 style="color: var(--emerald-primary); margin-bottom: 10px; font-family: var(--font-heading); font-size: 1.05rem;">3. SORTED SET / ZSET (Feed Temporal)</h4>
                        <div class="json-viewer-window">
                            <div class="terminal-header">
                                <span class="dot dot-red"></span><span class="dot dot-yellow"></span><span class="dot dot-green"></span>
                                <span class="terminal-title">redis://127.0.0.1:6379/zsets</span>
                            </div>
                            <pre class="json-viewer" id="redis-struct-3" style="max-height: 220px;">Carregando...</pre>
                        </div>
                    </div>

                    <!-- Struct 4 -->
                    <div>
                        <h4 style="color: var(--emerald-primary); margin-bottom: 10px; font-family: var(--font-heading); font-size: 1.05rem;">4. SET & LIST (Categorias e Logs)</h4>
                        <div class="json-viewer-window">
                            <div class="terminal-header">
                                <span class="dot dot-red"></span><span class="dot dot-yellow"></span><span class="dot dot-green"></span>
                                <span class="terminal-title">redis://127.0.0.1:6379/sets_lists</span>
                            </div>
                            <pre class="json-viewer" id="redis-struct-4" style="max-height: 220px;">Carregando...</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- JAVASCRIPT APP LOGIC -->
    <script>
        function switchTab(tabId, evt) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tabId).style.display = 'block';
            if (evt && evt.currentTarget) {
                evt.currentTarget.classList.add('active');
            } else {
                const btn = document.querySelector(`.tab-btn[onclick*="${tabId}"]`);
                if (btn) btn.classList.add('active');
            }

            if (tabId === 'tab-crud') loadCollectionData();
            if (tabId === 'tab-aggregation') loadAggregationData();
            if (tabId === 'tab-redis') loadRedisData();
            if (tabId === 'tab-indexes') runIndexExplain();
        }

        async function loadCollectionData() {
            const collection = document.getElementById('select-collection').value;
            const res = await fetch(`api.php?action=list&collection=${collection}`);
            const data = await res.json();

            const tbody = document.getElementById('crud-body');
            tbody.innerHTML = '';

            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted);">Nenhum documento encontrado nesta coleção.</td></tr>';
                return;
            }

            data.data.forEach(item => {
                const tr = document.createElement('tr');
                const isResgatado = item.status === 'resgatado' || item.custodia_status === 'entregue';
                tr.innerHTML = `
                    <td><code style="color: var(--emerald-primary); font-weight: 700;">${item._id}</code></td>
                    <td><strong style="color: var(--text-dark);">${item.titulo || item.nome || item.nome_local || item.protocolo_devolucao || 'N/A'}</strong></td>
                    <td style="color: var(--text-body);">${item.categoria || item.perfil || item.andar || 'N/A'}</td>
                    <td><span class="badge ${isResgatado ? 'badge-mongo' : 'badge-redis'}">${item.status || item.custodia_status || item.responsavel || 'ativo'}</span></td>
                    <td style="color: var(--text-muted);">${item.criado_em || '2026-08-04'}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteDocument('${collection}', '${item._id}')">Deletar</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        async function handleCreateItem(e) {
            e.preventDefault();
            const collection = document.getElementById('select-collection').value;
            const titulo = document.getElementById('input-titulo').value;
            const categoria = document.getElementById('input-categoria').value;
            const valor = document.getElementById('input-valor').value;

            const res = await fetch('api.php?action=create&collection=' + collection, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    titulo: titulo,
                    categoria: categoria,
                    valor_estimado: parseFloat(valor),
                    status: 'pendente',
                    descricao: `Item ${titulo} cadastrado manualmente via Dashboard UI.`
                })
            });

            const result = await res.json();
            alert(result.message);
            document.getElementById('input-titulo').value = '';
            loadCollectionData();
        }

        async function deleteDocument(collection, id) {
            if (!confirm(`Deseja remover o documento ${id} do MongoDB?`)) return;
            const res = await fetch(`api.php?action=delete&collection=${collection}&id=${id}`);
            const result = await res.json();
            alert(result.message);
            loadCollectionData();
        }

        async function runQuery(queryAction, params) {
            const queryParams = new URLSearchParams({ action: queryAction, ...params });
            const res = await fetch(`api.php?${queryParams.toString()}`);
            const data = await res.json();
            document.getElementById('query-result').innerText = JSON.stringify(data, null, 2);
        }

        async function loadAggregationData() {
            const res = await fetch('api.php?action=aggregation');
            const data = await res.json();

            const tbody = document.getElementById('aggregation-body');
            tbody.innerHTML = '';

            data.data.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong style="color: var(--text-dark);">${row.categoria}</strong></td>
                    <td style="color: var(--text-dark); font-weight: 700;">${row.total_registrado}</td>
                    <td><span style="color: #b91c1c; font-weight: 800;">${row.quantidade_pendente}</span></td>
                    <td><span style="color: #047857; font-weight: 800;">${row.quantidade_resgatada}</span></td>
                    <td><strong style="color: var(--emerald-primary);">${row.taxa_resolucao_pct}</strong></td>
                    <td><span style="color: #7c3aed; font-weight: 800;">${row.valor_total_estimado}</span></td>
                    <td><small style="color: var(--text-muted); font-weight: 600;">${row.locais_principais}</small></td>
                `;
                tbody.appendChild(tr);
            });
        }

        async function runIndexExplain() {
            const res = await fetch('api.php?action=explain_index&categoria=Eletrônicos&status=pendente');
            const data = await res.json();

            const explain = data.explain;
            const container = document.getElementById('index-explain-output');

            container.innerHTML = `
                <div class="explain-box">
                    <h4><span>⚡</span> Plano Vencedor: <span class="badge badge-mongo">${explain.winningPlan.stage}</span> (Índice Utilizado: <code>${explain.winningPlan.indexName}</code>)</h4>
                    <p>${explain.explicacao_tecnica}</p>
                </div>

                <div class="metrics-grid" style="margin-top: 24px;">
                    <div class="metric-card">
                        <div class="metric-title">Estágio no Mongo</div>
                        <div class="metric-value" style="color: #047857;">${explain.winningPlan.stage}</div>
                        <div class="metric-sub">Busca direta via B-Tree Index</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-title">Docs Examinados</div>
                        <div class="metric-value" style="color: #0284c7;">${explain.executionStats.totalDocsExamined} / ${explain.executionStats.totalDocsInCollection}</div>
                        <div class="metric-sub">Em vez de ler os 20 docs</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-title">Tempo de Execução</div>
                        <div class="metric-value" style="color: #be123c;">${explain.executionStats.executionTimeMillis} ms</div>
                        <div class="metric-sub">Resposta ultrarrápida</div>
                    </div>
                </div>
            `;
        }

        async function loadRedisData() {
            const res = await fetch('api.php?action=redis_inspect');
            const data = await res.json();

            const structs = data.structures;
            document.getElementById('redis-struct-1').innerText = JSON.stringify(structs['1_string'].dados, null, 2);
            document.getElementById('redis-struct-2').innerText = JSON.stringify(structs['2_hash'].dados, null, 2);
            document.getElementById('redis-struct-3').innerText = JSON.stringify(structs['3_sorted_set'].dados, null, 2);
            document.getElementById('redis-struct-4').innerText = JSON.stringify(structs['4_set_and_list'].dados, null, 2);
        }

        async function testCacheAsideFlow() {
            const demoBox = document.getElementById('cache-flow-demo');
            const demoText = document.getElementById('cache-flow-text');
            demoBox.style.display = 'block';

            demoText.innerHTML = '⚡ <strong>1ª Chamada:</strong> Requisição <code>GET /api.php?action=get_item_cached&id=lost_001</code>...';
            const res1 = await fetch('api.php?action=get_item_cached&id=lost_001');
            const data1 = await res1.json();

            setTimeout(async () => {
                demoText.innerHTML += `<br>👉 Status da 1ª chamada: <span class="badge badge-redis">${data1.cache_status}</span> (Dado consultado no MongoDB em disco e armazenado no Redis com TTL).<br><br>⚡ <strong>2ª Chamada (Imediata):</strong> Efetuando nova requisição idêntica...`;

                const res2 = await fetch('api.php?action=get_item_cached&id=lost_001');
                const data2 = await res2.json();

                demoText.innerHTML += `<br>🎉 Status da 2ª chamada: <span class="badge badge-mongo">${data2.cache_status}</span> (Retornado instantaneamente da RAM do Redis sem tocar no MongoDB!).`;
            }, 1000);
        }

        // Inicializar tabela principal
        loadCollectionData();
    </script>
</body>
</html>
