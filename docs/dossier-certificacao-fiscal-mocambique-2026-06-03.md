# Dossiê de Certificação Fiscal Moçambique

Data de fecho técnico: `2026-06-03`

Base de referência:
- Commit: `09164b0cf`
- Tag de release anterior: `v2026.06.03-02`
- Módulo principal: Faturação, fiscalidade e exportação SAF-T

## 1. Objectivo

Consolidar a evidência técnica mínima para suportar validação de certificação fiscal e go-live, com foco em:
- inalterabilidade documental;
- controlo de séries;
- rastreabilidade de exportações SAF-T;
- validação por XSD configurável;
- auditoria de metadados fiscais;
- leitura operacional das pendências legais ainda externas.

## 2. Evidências técnicas fechadas

### 2.1 SAF-T com validação XSD configurável

Implementado em:
- [app/Services/SaftExportService.php](../app/Services/SaftExportService.php)
- [app/Http/Controllers/FiscalProfileController.php](../app/Http/Controllers/FiscalProfileController.php)
- [packages/workdo/Account/src/Services/ReportService.php](../packages/workdo/Account/src/Services/ReportService.php)
- [config/sce.php](../config/sce.php)
- [.env.example](../.env.example)

Comportamento:
- exportação SAF-T valida sempre XML bem formado;
- quando `SAFT_MZ_REQUIRE_XSD_VALIDATION=true`, exige `SAFT_MZ_XSD_PATH`;
- o caminho XSD tem de ser ficheiro existente e legível;
- o export history regista `xsd_required`, `xsd_path_configured`, `xsd_path_ready` e `xsd_validated`.

### 2.2 Readiness gate para XSD

O gate `exports.saft_xsd_validation_config` falha quando a validação é exigida e o XSD não existe ou não é legível.

### 2.3 Export history com evidência

O histórico de exportação SAF-T regista:
- tipo de exportação;
- período;
- hash do ficheiro;
- tamanho XML;
- estado de validação;
- estado de certificação do perfil fiscal;
- número do certificado do software.

### 2.4 Cobertura automatizada

Testes executados e a passar:
- `tests/Unit/SaftExportServiceTest.php`
- `tests/Feature/CompanyFiscalSettingsTest.php`
- `tests/Feature/MozambiqueGoLiveReadinessTest.php`

Cobertura relevante:
- validação XSD passa com schema permissivo;
- validação XSD falha com XML incompatível;
- export SAF-T grava metadados de validação;
- readiness falha quando a validação é exigida e o XSD está ausente.

## 3. Evidência funcional já existente

- Numeração de documentos por série e prefixo fiscal.
- Bloqueio de edição pós-emissão suportado por documentos rectificativos.
- Histórico fiscal por exportação.
- Trilhas de auditoria e settings por empresa.
- Parametrização fiscal inicial para IVA, IRPS e INSS.

## 4. Pontos que ainda exigem validação externa

### 4.1 XSD oficial SAF-T Moçambique

O sistema já suporta o ficheiro oficial por configuração, mas o XSD oficial não está incluído no repositório.

Validação necessária:
- confirmação do ficheiro oficial a usar;
- armazenamento seguro do caminho em produção;
- aprovação jurídica/fiscal do schema adoptado.

### 4.2 Tabelas legais externas

Ainda requerem validação humana:
- ADT / dupla tributação;
- GIFiM;
- moeda electrónica / IME;
- tabelas fiscais específicas por ano fiscal, quando houver actualizações legais.

## 5. Conclusão técnica

O sistema ficou tecnicamente preparado para suportar certificação fiscal SAF-T com:
- validação XSD configurável;
- confirmação de readiness;
- histórico de exportação auditável;
- testes automatizados a cobrir o caminho feliz e o erro de configuração.

O que permanece bloqueado por decisão externa:
- o ficheiro XSD oficial a adoptar em produção;
- validações legais/fiscais que dependem de fonte normativa externa.
