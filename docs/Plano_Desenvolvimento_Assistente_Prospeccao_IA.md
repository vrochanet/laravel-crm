# Plano de Desenvolvimento --- Assistente de Prospeção Imobiliária com IA

**Projeto:** CRM imobiliário baseado em Krayin\
**Módulo:** `packages/Webkul/Prospeccao`\
**Data de atualização:** 10-11-2026

**Versão do plano:** 2.0

## 1. Objetivo

Evoluir o módulo Prospeção existente para um assistente de prospeção imobiliária com IA, preparado para comunicação manual e automatizada. O utilizador configura o modo de funcionamento por canal. A IA analisa, prepara e recomenda; o envio automático só pode ocorrer quando estiver expressamente ativado e todas as regras e condições estiverem cumpridas. No modo manual, a decisão e o envio ficam sob controlo do consultor.

### Fluxo pretendido

1. O consultor encontra manualmente um anúncio particular.
2. Introduz manualmente o anúncio, imóvel e dados do proprietário no CRM.
3. O sistema organiza os dados e procura possíveis duplicados.
4. A IA analisa o contexto e prepara a primeira mensagem.
5. O sistema aplica as regras do canal e calcula a próxima data recomendada.
6. No modo manual, o consultor revê, decide e envia externamente; no modo automático, o sistema só envia se a automatização estiver ativa e todas as condições forem cumpridas.
7. A resposta é registada manualmente ou recebida por integração, conforme as capacidades do canal implementado.
8. A IA analisa a resposta e o histórico e propõe a abordagem seguinte.
9. O processo termina com reunião, oportunidade, recusa, oposição a contacto ou encerramento.

### Fora do âmbito da primeira versão

- Pesquisa automática de anúncios.
- Recolha automática ou em massa de números/contactos.
- Respostas autónomas da IA sem regras, limites e controlos configurados.
- Chamadas telefónicas automáticas.
- Criação de um segundo CRM.

No modo manual, abrir `wa.me` ou o cliente de email não significa que a mensagem tenha sido enviada; o consultor confirma o envio no CRM. No modo automático, o envio depende de uma integração técnica suportada, de configuração explícita e do cumprimento das regras de segurança e frequência.

**Limite operacional inicial:** máximo de cinco novos primeiros contactos por dia útil (segunda a sexta-feira). O limite deve ser configurável e aplicado ao envio automático; no modo manual, deve ser apresentado como limite/recomendação operacional e impedir ou alertar conforme a política definida. Não constitui garantia de conformidade legal.

## 2. Princípios

1.  Preservar a arquitetura e convenções do Krayin.
2.  Evoluir o pacote Prospeção existente.
3.  Reutilizar Pessoas e Leads do Krayin.
4.  Evitar duplicação de Pessoas, Leads e imóveis.
5.  Suportar modo manual e modo automatizado, configuráveis por canal.
6.  Manter aprovação humana configurável; por defeito, obrigatória.
7.  Aplicar limites de frequência tanto no modo manual (recomendação/alerta) como no automático (bloqueio de execução).
8.  Manter histórico auditável e não sobrescrever mensagens efetivamente enviadas/recebidas.
9.  Minimizar dados pessoais enviados ao fornecedor de IA.
10. Implementar uma etapa de cada vez.
11. Não assumir versões ou comportamentos sem confirmar no código.
12. Não misturar este projeto com a SO Platform ou outros projetos.

## 3. Stack tecnológica

### Confirmado pela análise inicial

  Camada          Tecnologia
  --------------- -----------------------------------
  CRM             Krayin CRM personalizado
  Backend         Laravel
  Linguagem       PHP
  Base de dados   MySQL
  Pacote          `packages/Webkul/Prospeccao`
  Entidades CRM   Pessoas e Leads do Krayin
  Interface       Administração existente do Krayin
  Versionamento   Git

### Confirmar na Etapa 0

Consultar `composer.json`, `composer.lock`, configurações e código para
confirmar versões exatas de PHP, Laravel, Krayin, MySQL, bibliotecas
HTTP, testes, filas, cache, autenticação, permissões, frontend e
timezone. Não atualizar versões sem necessidade demonstrada.

### Integração IA

Criar serviço backend desacoplado do fornecedor. As credenciais ficam em
variáveis de ambiente, nunca no código ou Git. O serviço envia apenas
contexto necessário, valida respostas, trata timeouts e erros e nunca
envia mensagens ao WhatsApp. Escolher fornecedor/modelo antes da
implementação, considerando qualidade em português europeu, custo,
privacidade, retenção e condições de tratamento.

## 4. Configuração de comunicação e regras de frequência

### Modos de funcionamento

- **Manual:** o sistema prepara mensagens e recomenda quando contactar. O consultor revê, decide, envia fora do CRM e confirma o envio no CRM.
- **Automático:** o sistema pode executar envios através de um fornecedor/canal integrado, apenas quando a automatização estiver explicitamente ativada e as regras permitirem. A aprovação humana é configurável e fica obrigatória por defeito.

### Âmbito das configurações

- Configurações globais por canal (WhatsApp e email), incluindo modo manual/automático.
- Regras globais independentes por canal.
- Exceções por conversação apenas quando necessário.
- A exceção da conversação prevalece sobre a regra global do canal, exceto bloqueios de segurança, oposição/não contactar e limites legais ou técnicos, que nunca podem ser ultrapassados.
- Deve ser possível consultar qual a regra efetiva aplicada e a origem da configuração (global ou exceção).

### Regras de frequência a configurar

- Limite diário de primeiros contactos.
- Limite diário de mensagens/seguimentos, se aplicável.
- Intervalo mínimo entre mensagens.
- Intervalo entre seguimentos.
- Número máximo de seguimentos sem resposta.
- Dias e horários permitidos.
- Tratamento de fins de semana e, após decisão, feriados.

No modo automático, as regras são condições de execução: se alguma falhar, a mensagem não é enviada e fica adiada/suspensa com motivo registado. No modo manual, as regras geram recomendações de próxima data/hora e alertas visíveis; não enviam mensagens. A política de bloqueio de envio manual por exceder limites deve ser configurável/decidida antes da implementação.

### Próximo contacto recomendado/agendado

O sistema calcula a próxima data de contacto com base nas regras do canal, data e conteúdo da última comunicação, respostas recebidas, silêncio (sem interpretar automaticamente como desinteresse), compromissos assumidos, análise contextual da IA e estado da conversação. A recomendação deve incluir motivo e nível de confiança quando derivada de IA. Pedidos de não voltar a contactar, oposição, recusa inequívoca ou conversação bloqueada prevalecem e impedem qualquer recomendação de envio.

### Separação técnica

Manter um único modelo de conversações e mensagens. Criar uma camada de decisão/dispatch desacoplada dos fornecedores, com componentes equivalentes a `MessageDispatcher`, manipulador manual, manipulador automático e contratos/adaptadores de fornecedor. A lógica de negócio não deve depender diretamente de um fornecedor específico. A integração efetiva de cada canal é uma etapa própria; não assumir que abrir `wa.me` constitui integração de envio automático.

## 5. Estado atual conhecido

A análise inicial do ZIP identificou:

-   Pacote próprio `packages/Webkul/Prospeccao`.
-   Tabela `prospeccao_properties`.
-   Imóveis associados a Pessoa e Lead.
-   Reutilização da entidade `persons`.
-   `ProspeccaoLeadService` para procurar/reutilizar contactos.
-   Modelos de comunicação por fase/canal.
-   `prospeccao_contact_logs` para histórico.
-   `WhatsAppService` que prepara link `wa.me`, sem API oficial.
-   Interface administrativa de prospeção.

### Pontos a rever e reproduzir com testes

1.  Reutilizar uma Pessoa pode não impedir Leads/imóveis duplicados.
2.  Atualizar telefones/emails pode substituir valores existentes.
3.  O histórico pode confundir mensagem preparada com mensagem enviada.
4.  Validações de `property_type` e `locality` podem não coincidir com
    as migrations.
5.  A criação de Leads pode depender de IDs implícitos de
    pipeline/etapa.
6.  O exemplo de ambiente aparenta usar `QUEUE_CONNECTION=sync`.

Não assumir que estes problemas permanecem na versão atual sem os
reproduzir.

## 6. Estados funcionais propostos

### Oportunidade

-   `new`: registada, ainda não analisada.
-   `ready_for_review`: análise e mensagem preparadas.
-   `awaiting_first_contact`: aguarda decisão do consultor.
-   `first_contact_made`: consultor confirmou contacto inicial.
-   `conversation_active`: conversa em curso.
-   `meeting_proposed`: reunião proposta.
-   `meeting_scheduled`: reunião marcada.
-   `qualified`: oportunidade qualificada.
-   `not_interested`: sem interesse.
-   `do_not_contact`: oposição a novo contacto.
-   `closed`: encerrada.

Alinhar os nomes finais com as convenções existentes no pacote.

### Mensagem

-   `draft`: rascunho.
-   `suggested`: sugestão da IA.
-   `approved`: revista e aprovada.
-   `sent_manually`: consultor confirmou envio fora do CRM.
-   `received_manual`: resposta introduzida manualmente.
-   `discarded`: descartada.
-   `failed`: falha de processamento/geração.

`sent_manually` é uma declaração do consultor, não uma confirmação
técnica do WhatsApp.

### Consentimento, origem e oposição

Registar origem dos dados, data de recolha, canal/finalidade, estado de
autorização quando aplicável, evidência, método/data de obtenção e
eventual oposição/revogação. Estados possíveis: `unknown`, `consented`,
`refused`, `withdrawn`, `pending_legal_review`.

Não presumir que um número publicado num anúncio equivale a
consentimento para marketing. O modelo e os bloqueios de contacto devem
ser validados juridicamente. Não usar mensagens não solicitadas como
atalho para obter consentimento.

## 7. Etapas de implementação

### Etapa 0 --- Diagnóstico final (CONCLUÍDA)

Baseline confirmada no repositório local: PHP 8.4.26, Composer 2.10.3, Laravel 12.66.0, Pest 3.8.6, PHPUnit 11.5.50 e MySQL. Testes baseline: Unit 1 passed (2 assertions); Feature 213 passed (435 assertions).

### Etapa 1 --- Conversações, mensagens, modelos e serviço (CONCLUÍDA)

Criadas as tabelas `prospeccao_conversations` e `prospeccao_messages`, modelos `Conversation` e `Message`, relação em `Property` e `ConversationService` para obter/criar conversações, registar mensagens recebidas e rascunhos, aprovar e confirmar envio. Testes específicos: 11 passed (25 assertions).

**Commit:** `161e55f8` — `Implementa conversações e mensagens na prospeção`.

### Etapa 2 --- Interface de consulta de conversações (CONCLUÍDA)

A ficha do imóvel apresenta conversações WhatsApp e email, estado e histórico cronológico de mensagens. A secção antiga de ContactLogs foi substituída na apresentação.

**Commit:** `0a22e0bb` — `Integra conversações na ficha do imóvel`.

### Etapa 3 --- Configurações de comunicação e motor de regras de frequência (PRÓXIMA)

**Objetivo:** preparar o sistema para operação manual e automática antes de implementar os fluxos de gestão e envio.

**Tarefas:**
1. Criar configurações globais independentes por canal (WhatsApp/email).
2. Permitir selecionar modo manual ou automático por canal.
3. Definir aprovação humana obrigatória por defeito e opção configurável para automatização, sujeita às salvaguardas acordadas.
4. Configurar limites diários de primeiros contactos e, se aplicável, de seguimentos/mensagens.
5. Configurar intervalos mínimos, máximo de seguimentos sem resposta, dias e horários permitidos.
6. Permitir exceções por conversação, sem criar configurações individuais por defeito.
7. Definir precedência: exceção da conversação sobre regra global do canal; bloqueios de segurança/oposição prevalecem sempre.
8. Implementar serviço central que resolve as regras efetivas e decide se uma ação pode ser executada.
9. Registar motivo de bloqueio/adiamento e configuração aplicada.
10. Calcular a próxima data recomendada para contacto, inicialmente com regras determinísticas; integrar a avaliação contextual da IA numa etapa posterior.

**Testes:** modos independentes por canal; valores globais; exceções; precedência; limites diários; intervalos; dias/horários; bloqueios por oposição; data recomendada; ausência de envio automático nesta etapa.

**Commit previsto:** `feat(prospeccao): adiciona configurações e regras de comunicação`.

### Etapa 4 --- Gestão manual de mensagens

**Tarefas:** registar mensagens recebidas; criar e editar rascunhos; aprovar mensagens pendentes; copiar texto/abrir `wa.me` ou cliente de email; confirmar manualmente mensagens enviadas; atualizar estado da conversação; apresentar recomendações de próxima abordagem com data/motivo; respeitar limites e avisos definidos na Etapa 3. Não implementar envio automático nesta etapa.

**Testes:** registo, edição e estados; conteúdo enviado/recebido imutável; aprovação; confirmação manual; links; atualização de estado; recomendações e limites.

**Commit previsto:** `feat(prospeccao): completa gestão manual de mensagens`.

### Etapa 5 --- IA para primeira abordagem

Escolher fornecedor/modelo; criar serviço desacoplado; analisar anúncio/contexto; gerar mensagem em português europeu sem inventar factos; validar resposta; permitir regenerar, editar, aprovar e descartar; guardar metadados mínimos e minimizar dados enviados.

**Commit previsto:** `feat(prospeccao): adiciona assistente IA para primeira abordagem`.

### Etapa 6 --- IA para análise de respostas e recomendação de contacto

Analisar histórico e contexto; identificar intenção, objeções, interesse, compromissos e oposição; atualizar resumo contextual; calcular termómetro 0–100 por conversação, com justificação, fatores e confiança; sugerir próxima resposta e data de contacto; permitir correção pelo consultor. Silêncio reduz confiança, mas não significa automaticamente desinteresse.

**Commit previsto:** `feat(prospeccao): adiciona análise IA e recomendações de seguimento`.

### Etapa 7 --- Integração de envio automático

Implementar adaptadores para os fornecedores efetivamente escolhidos; envio apenas no modo automático; aprovação humana obrigatória por defeito; agendamento/filas; idempotência; registo de resultados e falhas; receção de respostas apenas quando o fornecedor/canal suportar essa capacidade; respeitar sempre regras de frequência e bloqueios. A integração WhatsApp deve usar uma solução tecnicamente suportada e juridicamente validada.

**Testes:** mocks de fornecedor; falhas/timeouts; duplicação; limites; horários; aprovação; bloqueios; auditoria; confirmação de estado sem falsos positivos.

**Commit previsto:** `feat(prospeccao): integra envio automatizado por canal`.

### Etapa 8 --- Planeador e acompanhamento global

Vista de contactos recomendados para hoje/próximos dias, mensagens pendentes de aprovação, envios agendados, ações adiadas/bloqueadas e motivos. Distinguir recomendação manual de agendamento automático.

**Commit previsto:** `feat(prospeccao): adiciona planeador de contactos`.

### Etapa 9 --- Qualificação e funil

Integrar qualificação, tarefas, reuniões, resultados e Lead do Krayin; respeitar oposição e estados de encerramento.

**Commit previsto:** `feat(prospeccao): integra qualificação e acompanhamento no CRM`.

### Etapa 10 --- Segurança, privacidade e validação jurídica

Rever permissões, inputs, credenciais, logs, retenção, origem dos dados, autorização, oposição, bloqueios e tratamento pelo fornecedor IA/mensagens. Validar juridicamente o processo antes da utilização real.

**Commit previsto:** `security(prospeccao): reforça privacidade e controlo de acesso`.

### Etapa 11 --- Integração final e produção

Executar suite completa e testes ponta a ponta; testar dados fictícios, falhas, custos, backups/recuperação, configuração e deploy controlado.

**Commit previsto:** `release(prospeccao): prepara primeira versão do assistente IA`.

## 8. Regra obrigatória de conclusão

Uma etapa só fica concluída quando: 1. Código implementado e revisto. 2.
Migrations verificadas, se aplicável. 3. Testes novos criados. 4. Testes
existentes relevantes executados. 5. Todos os testes passam. 6.
Verificação manual realizada quando aplicável. 7. Diff revisto e sem
alterações alheias. 8. Sem segredos ou dados pessoais indevidos no Git.
9. Documentação atualizada. 10. Commit criado. 11. Hash do commit
registado.

Se algum teste falhar, a etapa permanece em curso. Não avançar até
corrigir ou documentar formalmente um bloqueio. Não declarar concluído
apenas porque o código foi escrito ou a página parece funcionar.

### Registo de progresso

  Etapa                                      Estado        Testes/resultado             Commit    Hash
  ------------------------------------------ ------------- ---------------------------- --------- ----------
  0 --- Diagnóstico final                    Concluída     Baseline registada           ---       ---
  1 --- Conversações e serviço               Concluída     11 passed, 25 assertions     Criado    161e55f8
  2 --- Interface de consulta                Concluída     Validação manual             Criado    0a22e0bb
  3 --- Configurações/regras de frequência   Por iniciar   ---                          ---       ---
  4 --- Gestão manual de mensagens           Por iniciar   ---                          ---       ---
  5 --- Primeira abordagem IA                Por iniciar   ---                          ---       ---
  6 --- Análise IA/recomendações             Por iniciar   ---                          ---       ---
  7 --- Envio automatizado                   Por iniciar   ---                          ---       ---
  8 --- Planeador de contactos               Por iniciar   ---                          ---       ---
  9 --- Qualificação/funil                   Por iniciar   ---                          ---       ---
  10 --- Segurança/privacidade               Por iniciar   ---                          ---       ---
  11 --- Produção                             Por iniciar   ---                          ---       ---

## 9. Convenções Git

-   Usar branch própria, respeitando convenções existentes.
-   Excluir `.env`, logs, backups, `vendor`, `node_modules` e
    temporários.
-   Não misturar refatorações fora do âmbito.
-   Um commit por etapa concluída; commits intermédios são permitidos.
-   Não reescrever histórico partilhado sem autorização.
-   Antes do commit, verificar `git status`, diff e staging.
-   Depois do commit, confirmar working tree limpo.

## 10. Regras para a IA

-   Nunca envia mensagens nem telefona.
-   Nunca altera dados críticos sem confirmação.
-   Não inventa dados do imóvel ou proprietário.
-   Não promete resultados ou condições não confirmadas.
-   Não pressiona nem manipula.
-   Respeita pedidos de não voltar a contactar.
-   Distingue factos, inferências e desconhecido.
-   Usa português europeu, linguagem natural, profissional e não
    agressiva.
-   Sugere respostas concisas e adequadas ao contexto.
-   Quando houver ambiguidade, declara incerteza.
-   A decisão final pertence sempre ao consultor.

## 11. Decisões pendentes

Resolver antes da etapa correspondente: 1. Fornecedor/modelo IA e
custos. 2. Dados enviados ao fornecedor e retenção. 3. Critérios de
duplicação de anúncios/imóveis. 4. Exclusão ou não de feriados na quota.
5. Timezone efetiva. 6. Validação jurídica do método de contacto e
tratamento de dados. 7. Alojamento, backups e serviços necessários. 8.
Limites de consumo IA.

## 12. Método de trabalho nesta conversa

Para cada etapa: 1. Explicar objetivo e ficheiros a analisar/alterar. 2.
Confirmar abordagem antes de mudanças estruturais. 3. Implementar apenas
a etapa atual. 4. Fornecer comandos concretos para o ambiente local. 5.
Analisar resultados e falhas. 6. Rever diff e preparar commit. 7. Só
avançar após testes aprovados e commit existente.

Não presumir que o código está correto sem testes executados.

## 13. Ponto de partida

**Próxima tarefa: Etapa 3 --- Configurações de comunicação e motor de regras de frequência.**

O repositório já tem conversações e mensagens implementadas e a ficha do imóvel apresenta o histórico. Antes de desenvolver a gestão manual completa, implementar configurações globais por canal, modos manual/automático, exceções por conversação e regras de frequência. O sistema deve calcular a próxima data recomendada nos dois modos; no modo automático, as regras condicionam a execução, enquanto no manual produzem recomendações e alertas. A integração real de envio automático fica para uma etapa posterior.
