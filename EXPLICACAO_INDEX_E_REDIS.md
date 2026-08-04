# Explicação Técnica: Índices MongoDB e Estruturas Redis

Este documento fornece a fundamentação teórica e prática sobre os **Índices no MongoDB** e o uso das **4 Estruturas de Dados do Redis** com o **Fluxo Obrigatório de Cache (Cache-Aside Pattern)** no **Sistema de Achados e Perdidos**.

---

## 1. Índices no MongoDB e Otimização de Consultas

### O que é um Índice no MongoDB?
Por padrão, quando o MongoDB executa uma consulta sem índices, ele precisa percorrer **todos os documentos de uma coleção sequencialmente** para verificar se cada documento atende aos critérios do filtro. Esse processo é conhecido como **Collection Scan (`COLLSCAN`)**.

Um **Índice** é uma estrutura de dados especializada (árvore balanceada B-Tree) que armazena uma pequena porção do conjunto de dados da coleção em uma forma fácil de percorrer. O índice armazena os valores de um ou mais campos específicos ordenados pelo valor do campo.

### Por que o Índice melhora a performance de uma determinada consulta?

#### Exemplo Prático da Aplicação:
Consulta de Itens Perdidos por **Categoria** e **Status**:
```javascript
db.itens_perdidos.find({ categoria: "Eletrônicos", status: "pendente" })
```

#### Comparativo de Execução (Com vs Sem Índice):

| Métrica / Estágio | Sem Índice (`COLLSCAN`) | Com Índice Composto (`IXSCAN`) |
| :--- | :--- | :--- |
| **Estágio de Execução** | `COLLSCAN` (Varredura Completa) | `IXSCAN` (Index Scan) |
| **Documentos Na Coleção** | 100.000 documentos | 100.000 documentos |
| **Documentos Examinados (`totalDocsExamined`)** | **100.000** (lê toda a coleção) | **2** (lê apenas os correspondentes) |
| **Chaves Examinadas (`totalKeysExamined`)** | 0 | 2 |
| **Complexidade de Algoritmo** | $O(N)$ (Linear) | $O(\log N)$ (Logarítmica B-Tree) |
| **Tempo de Execução Médio** | ~120 ms | **~0.15 ms** (800x mais rápido) |
| **Uso de Memória RAM na Ordenação** | Alto (carrega docs inteiros na RAM) | Mínimo (apenas ponteiros indexados) |

#### Razões Técnicas da Melhoria:
1. **Evita Leitura de Disco Desnecessária:** No estágio `IXSCAN`, o motor do MongoDB consulta diretamente a árvore B-Tree indexada mantida em memória. Ele identifica exatamente os ponteiros para os documentos desejados sem ler os documentos irrelevantes.
2. **ReduçãoDrástica de `totalDocsExamined`:** Em vez de examinar todos os documentos da coleção (ex: 20 ou 100.000 docs), o banco examina **apenas a quantidade exata de documentos retornados (`nReturned`)**.
3. **Índice Composto (Compound Index):** O índice `{ categoria: 1, status: 1 }` permite filtrar dois campos simultaneamente em um único salto na árvore de índice.
4. **Otimização de Ordenação (In-Memory Sort Prevention):** Quando a consulta exige ordenação (ex: por `data_perda`), um índice na ordem especificada dispensa o uso do buffer de ordenação em RAM (limitado a 32MB no MongoDB), eliminando o erro de *Exceeded memory limit for $sort stage*.

---

## 2. Estruturas de Dados no Redis e Suas Aplicações

O Redis é um armazenamento de chave-valor na memória RAM ultrarrápido. No Sistema de Achados e Perdidos, utilizamos **4 estruturas de dados distintas**:

```
+-----------------------------------------------------------------------+
|                       ESTRUTURAS DO REDIS UTILIZADAS                   |
+-----------------------------------------------------------------------+
|  1. STRING       | Contadores globais e Cache JSON de itens           |
|  2. HASH         | Sessões de usuário e Perfil estruturado            |
|  3. SORTED SET   | Feed temporal de itens ordenados por Timestamp     |
|  4. SET & LIST   | SET de Categorias Únicas & LIST de Logs Auditoria  |
+-----------------------------------------------------------------------+
```

### 1. Structure 1: **STRING** (Cache Chave-Valor Simples)
- **Caso de Uso:** Guardar contadores globais em tempo real (`stats:total_lost`, `stats:total_found`) e cache de detalhes de itens específicos (`item:lost:lost_001`).
- **Comandos:** `SET`, `GET`, `DEL`, `INCR`
- **Exemplo:**
  ```bash
  SET item:lost:lost_001 '{"_id":"lost_001","titulo":"iPhone 13","status":"pendente"}' EX 300
  GET item:lost:lost_001
  ```

### 2. Structure 2: **HASH** (Dicionário de Campos e Valores)
- **Caso de Uso:** Armazenar dados estruturados de sessão de usuário ativas sem necessidade de serializar/deserializar JSON inteiro.
- **Comandos:** `HSET`, `HGETALL`, `HGET`
- **Exemplo:**
  ```bash
  HSET user:session:sess_token_1 user_id "usr_001" nome "Carlos Silva" perfil "Aluno"
  HGETALL user:session:sess_token_1
  ```

### 3. Structure 3: **SORTED SET (ZSET)** (Conjunto Ordenado por Score)
- **Caso de Uso:** Feed dos itens mais recentes ordenados por Timestamp como pontuação (Score), permitindo paginação e busca por relevância sem onerar o banco principal.
- **Comandos:** `ZADD`, `ZRANGE`, `ZREVRANGE`
- **Exemplo:**
  ```bash
  ZADD items:recent:zset 1785884974 "lost_001:iPhone 13 Preto"
  ZRANGE items:recent:zset 0 9 WITHSCORES
  ```

### 4. Structure 4: **SET** e **LIST** (Conjuntos Únicos e Filas de Logs)
- **Caso de Uso SET:** Lista de categorias ativas do sistema (`categories:set`), garantindo que não haja duplicatas.
- **Caso de Uso LIST:** Fila de auditoria com histórico dos últimos resgates e devoluções efetuadas (`returns:recent:list`).
- **Comandos:** `SADD`, `SMEMBERS`, `LPUSH`, `LRANGE`
- **Exemplo:**
  ```bash
  SADD categories:set "Eletrônicos" "Documentos" "Acessórios"
  LPUSH returns:recent:list "[2026-08-04 20:00:00] Protocolo PROT-1001 concluído"
  LRANGE returns:recent:list 0 4
  ```

---

## 3. Fluxo Obrigatório de Cache (Cache-Aside Pattern)

O padrão **Cache-Aside (Lazy Loading)** garante que as consultas mais frequentes sejam respondidas diretamente pela memória RAM (Redis), reduzindo a carga de leitura no MongoDB.

```
                          FLUXO DE LEITURA (GET)
                          
   [ Cliente / Browser ]
             |
             v
    1. Requisição GET /api?action=get_item_cached&id=lost_001
             |
             v
   +--------------------+  Cache HIT   +-----------------------+
   | Verificar no Redis | -----------> | Retornar Dado Instantâneo|
   +--------------------+              | (Origem: REDIS_CACHE) |
             |                         +-----------------------+
         Cache MISS
             |
             v
   +-------------------------+
   | Consultar MongoDB (Disk)|
   +-------------------------+
             |
             v
   +------------------------------------+
   | Gravardados no Redis com TTL (ex: 300s)|
   +------------------------------------+
             |
             v
   +-----------------------------+
   | Retornar Resposta ao Cliente|
   | (Origem: MONGODB_CACHE_MISS)|
   +-----------------------------+
```

```
                        FLUXO DE ESCRITA / MUTAÇÃO (POST / PUT / DELETE)
                        
   [ Cliente / Browser ]
             |
             v
    1. Requisição POST/PUT para alterar ou excluir um item
             |
             v
   +------------------------------+
   | Executar Mutação no MongoDB  |
   +------------------------------+
             |
             v
   +------------------------------------------------+
   | Invalidação / Atualização de Cache no Redis    |
   | (Remover chaves afetas: DEL api:list & item)   |
   +------------------------------------------------+
             |
             v
   +---------------------------------------+
   | Retornar Confirmação de Sucesso ao Cliente|
   +---------------------------------------+
```
