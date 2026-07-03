<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Workdo\Contract\Models\ContractType;
use Workdo\Hrm\Models\AnnouncementCategory;
use Workdo\Hrm\Models\AllowanceType;
use Workdo\Hrm\Models\AwardType;
use Workdo\Hrm\Models\ComplaintType;
use Workdo\Hrm\Models\DeductionType;
use Workdo\Hrm\Models\DocumentCategory;
use Workdo\Hrm\Models\EmployeeDocumentType;
use Workdo\Hrm\Models\EventType;
use Workdo\Hrm\Models\HolidayType;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\LoanType;
use Workdo\Hrm\Models\Shift;
use Workdo\Hrm\Models\TerminationType;
use Workdo\Hrm\Models\WarningType;

class MozambiqueReferenceCatalogDefaultsService
{
    public function seedHrmForCompany(int $companyId): array
    {
        return DB::transaction(function () use ($companyId): array {
            return [
                'employee_document_types' => $this->seedEmployeeDocumentTypes($companyId),
                'award_types' => $this->seedAwardTypes($companyId),
                'allowance_types' => $this->seedAllowanceTypes($companyId),
                'termination_types' => $this->seedTerminationTypes($companyId),
                'warning_types' => $this->seedWarningTypes($companyId),
                'complaint_types' => $this->seedComplaintTypes($companyId),
                'leave_types' => $this->seedLeaveTypes($companyId),
                'document_categories' => $this->seedDocumentCategories($companyId),
                'announcement_categories' => $this->seedAnnouncementCategories($companyId),
                'event_types' => $this->seedEventTypes($companyId),
                'holiday_types' => $this->seedHolidayTypes($companyId),
                'shifts' => $this->seedShiftTypes($companyId),
                'deduction_types' => $this->seedDeductionTypes($companyId),
                'loan_types' => $this->seedLoanTypes($companyId),
            ];
        });
    }

    public function seedContractForCompany(int $companyId): int
    {
        return DB::transaction(function () use ($companyId): int {
            return $this->seedCatalog(
                ContractType::class,
                'name',
                $this->contractTypes(),
                $companyId
            );
        });
    }

    private function seedEmployeeDocumentTypes(int $companyId): int
    {
        return $this->seedCatalog(
            EmployeeDocumentType::class,
            'document_name',
            $this->employeeDocumentTypes(),
            $companyId
        );
    }

    private function seedAwardTypes(int $companyId): int
    {
        return $this->seedCatalog(
            AwardType::class,
            'name',
            $this->awardTypes(),
            $companyId
        );
    }

    private function seedAllowanceTypes(int $companyId): int
    {
        return $this->seedCatalog(
            AllowanceType::class,
            'name',
            $this->allowanceTypes(),
            $companyId
        );
    }

    private function seedTerminationTypes(int $companyId): int
    {
        return $this->seedCatalog(
            TerminationType::class,
            'termination_type',
            $this->terminationTypes(),
            $companyId
        );
    }

    private function seedWarningTypes(int $companyId): int
    {
        return $this->seedCatalog(
            WarningType::class,
            'warning_type_name',
            $this->warningTypes(),
            $companyId
        );
    }

    private function seedComplaintTypes(int $companyId): int
    {
        return $this->seedCatalog(
            ComplaintType::class,
            'complaint_type',
            $this->complaintTypes(),
            $companyId
        );
    }

    private function seedLeaveTypes(int $companyId): int
    {
        return $this->seedCatalog(
            LeaveType::class,
            'legal_code',
            $this->leaveTypes(),
            $companyId
        );
    }

    private function seedDocumentCategories(int $companyId): int
    {
        return $this->seedCatalog(
            DocumentCategory::class,
            'document_type',
            $this->documentCategories(),
            $companyId
        );
    }

    private function seedAnnouncementCategories(int $companyId): int
    {
        return $this->seedCatalog(
            AnnouncementCategory::class,
            'announcement_category',
            $this->announcementCategories(),
            $companyId
        );
    }

    private function seedEventTypes(int $companyId): int
    {
        return $this->seedCatalog(
            EventType::class,
            'event_type',
            $this->eventTypes(),
            $companyId
        );
    }

    private function seedHolidayTypes(int $companyId): int
    {
        return $this->seedCatalog(
            HolidayType::class,
            'holiday_type',
            $this->holidayTypes(),
            $companyId
        );
    }

    private function seedShiftTypes(int $companyId): int
    {
        return $this->seedCatalog(
            Shift::class,
            'shift_name',
            $this->shiftTypes(),
            $companyId
        );
    }

    private function seedDeductionTypes(int $companyId): int
    {
        return $this->seedCatalog(
            DeductionType::class,
            'name',
            $this->deductionTypes(),
            $companyId
        );
    }

    private function seedLoanTypes(int $companyId): int
    {
        return $this->seedCatalog(
            LoanType::class,
            'name',
            $this->loanTypes(),
            $companyId
        );
    }

    private function seedCatalog(string $modelClass, string $uniqueColumn, array $rows, int $companyId): int
    {
        foreach ($rows as $row) {
            if (!array_key_exists($uniqueColumn, $row)) {
                throw new \InvalidArgumentException(sprintf(
                    'Missing unique column "%s" for catalog seeding in %s.',
                    $uniqueColumn,
                    $modelClass
                ));
            }

            $lookup = [
                $uniqueColumn => $row[$uniqueColumn],
                'created_by' => $companyId,
            ];

            $payload = array_merge($row, [
                'creator_id' => $companyId,
                'created_by' => $companyId,
            ]);

            $modelClass::query()->firstOrCreate($lookup, $payload);
        }

        return count($rows);
    }

    private function employeeDocumentTypes(): array
    {
        return [
            ['document_name' => 'Bilhete de Identidade', 'description' => 'Documento de identificação civil do trabalhador.', 'is_required' => true],
            ['document_name' => 'NUIT', 'description' => 'Número Único de Identificação Tributária.', 'is_required' => true],
            ['document_name' => 'INSS', 'description' => 'Número/cartão de beneficiário da Segurança Social.', 'is_required' => true],
            ['document_name' => 'Passaporte', 'description' => 'Documento para trabalhadores estrangeiros.', 'is_required' => false],
            ['document_name' => 'DIRE / Autorização de Residência', 'description' => 'Documento migratório para trabalhadores estrangeiros.', 'is_required' => false],
            ['document_name' => 'Permissão/Autorização de Trabalho', 'description' => 'Autorização de trabalho aplicável a estrangeiros.', 'is_required' => false],
            ['document_name' => 'Carta de Condução', 'description' => 'Documentação exigida quando a função o requer.', 'is_required' => false],
            ['document_name' => 'Certificado de Habilitações', 'description' => 'Prova de habilitações académicas ou técnicas.', 'is_required' => false],
            ['document_name' => 'Curriculum Vitae', 'description' => 'Resumo profissional do colaborador.', 'is_required' => false],
            ['document_name' => 'Certificado Médico', 'description' => 'Documento clínico ou aptidão médica, quando aplicável.', 'is_required' => false],
            ['document_name' => 'Registo Criminal', 'description' => 'Certidão de registo criminal, quando a função exigir.', 'is_required' => false],
            ['document_name' => 'Atestado/Comprovativo de Residência', 'description' => 'Comprovativo de morada do colaborador.', 'is_required' => false],
            ['document_name' => 'Certidão de Nascimento', 'description' => 'Documento civil complementar.', 'is_required' => false],
            ['document_name' => 'Certidão de Casamento', 'description' => 'Documento civil complementar.', 'is_required' => false],
            ['document_name' => 'Comprovativo Bancário', 'description' => 'Recomendado para processamento salarial.', 'is_required' => false],
            ['document_name' => 'Foto tipo passe', 'description' => 'Fotografia recente do colaborador.', 'is_required' => false],
            ['document_name' => 'Contacto de Emergência', 'description' => 'Contacto para situações de emergência.', 'is_required' => false],
            ['document_name' => 'Contrato de Trabalho', 'description' => 'Contrato assinado entre a empresa e o trabalhador.', 'is_required' => false],
            ['document_name' => 'Adenda ao Contrato', 'description' => 'Aditamento ou alteração contratual.', 'is_required' => false],
            ['document_name' => 'Descrição de Funções', 'description' => 'Documento com as funções e responsabilidades.', 'is_required' => false],
            ['document_name' => 'Ficha de Colaborador', 'description' => 'Ficha individual do colaborador.', 'is_required' => false],
            ['document_name' => 'Declaração de Início de Funções', 'description' => 'Declaração de início de actividade laboral.', 'is_required' => false],
            ['document_name' => 'Avaliação de Desempenho', 'description' => 'Registo de avaliação do colaborador.', 'is_required' => false],
            ['document_name' => 'Recibo de Salário', 'description' => 'Recibo de vencimento e processamento salarial.', 'is_required' => false],
            ['document_name' => 'Mapa/Plano de Férias', 'description' => 'Planeamento de férias do colaborador.', 'is_required' => false],
            ['document_name' => 'Pedido de Férias', 'description' => 'Solicitação formal de férias.', 'is_required' => false],
            ['document_name' => 'Justificativo de Falta', 'description' => 'Justificativo documental para faltas.', 'is_required' => false],
            ['document_name' => 'Processo Disciplinar', 'description' => 'Documentação do processo disciplinar.', 'is_required' => false],
            ['document_name' => 'Certificado de Trabalho', 'description' => 'Certificado emitido após cessação do vínculo.', 'is_required' => false],
            ['document_name' => 'Declaração de Serviço', 'description' => 'Declaração de prestação de serviço.', 'is_required' => false],
            ['document_name' => 'Termo de Cessação/Rescisão', 'description' => 'Documento de cessação do contrato de trabalho.', 'is_required' => false],
        ];
    }

    private function awardTypes(): array
    {
        return [
            ['name' => 'Bónus por desempenho', 'description' => 'Remuneração variável ligada ao desempenho individual.'],
            ['name' => 'Bónus de produtividade', 'description' => 'Remuneração ligada à produtividade.'],
            ['name' => 'Prémio de assiduidade', 'description' => 'Prémio por presença e assiduidade.'],
            ['name' => 'Prémio de pontualidade', 'description' => 'Prémio por pontualidade.'],
            ['name' => 'Comissão de vendas', 'description' => 'Comissão para equipas comerciais e vendedores.'],
            ['name' => 'Comissão de cobrança', 'description' => 'Comissão associada a cobrança de valores.'],
            ['name' => 'Prémio por objectivos', 'description' => 'Prémio pelo cumprimento de objectivos.'],
            ['name' => 'Bónus anual', 'description' => 'Bónus atribuído no fecho do exercício.'],
            ['name' => 'Gratificação', 'description' => 'Gratificação pontual ou discricionária.'],
            ['name' => 'Prémio por projecto', 'description' => 'Prémio por conclusão de projecto.'],
            ['name' => 'Prémio de risco', 'description' => 'Prémio para funções expostas a risco.'],
            ['name' => 'Prémio de turno', 'description' => 'Prémio associado ao trabalho por turnos.'],
            ['name' => 'Prémio nocturno', 'description' => 'Prémio associado ao trabalho nocturno.'],
            ['name' => 'Prémio de horas extras', 'description' => 'Prémio associado a horas extraordinárias.'],
        ];
    }

    private function allowanceTypes(): array
    {
        return [
            ['name' => 'Subsídio de alimentação', 'description' => 'Abono regular para alimentação.'],
            ['name' => 'Subsídio de transporte', 'description' => 'Abono regular para transporte.'],
            ['name' => 'Subsídio de comunicação', 'description' => 'Ajuda para telefone ou dados.'],
            ['name' => 'Subsídio de renda', 'description' => 'Ajuda para despesas de habitação.'],
            ['name' => 'Abono/falha de caixa', 'description' => 'Abono aplicável a funções de caixa.'],
            ['name' => 'Ajuda de custo', 'description' => 'Ajuda para deslocações e missões.'],
            ['name' => 'Diuturnidade', 'description' => 'Abono por antiguidade.'],
        ];
    }

    private function terminationTypes(): array
    {
        return [
            ['termination_type' => 'Caducidade por fim do prazo'],
            ['termination_type' => 'Caducidade por conclusão da obra/tarefa'],
            ['termination_type' => 'Caducidade por incapacidade definitiva'],
            ['termination_type' => 'Caducidade por reforma'],
            ['termination_type' => 'Caducidade por morte do trabalhador'],
            ['termination_type' => 'Caducidade por morte do empregador individual'],
            ['termination_type' => 'Caducidade por revogação da autorização de trabalho'],
            ['termination_type' => 'Acordo revogatório'],
            ['termination_type' => 'Denúncia pelo trabalhador'],
            ['termination_type' => 'Denúncia pelo empregador'],
            ['termination_type' => 'Rescisão com justa causa pelo empregador'],
            ['termination_type' => 'Rescisão com justa causa pelo trabalhador'],
            ['termination_type' => 'Rescisão por motivos económicos'],
            ['termination_type' => 'Despedimento disciplinar'],
            ['termination_type' => 'Terminação no período probatório'],
            ['termination_type' => 'Abandono do trabalho'],
            ['termination_type' => 'Mútuo acordo'],
            ['termination_type' => 'Transferência para outra empresa do grupo'],
        ];
    }

    private function warningTypes(): array
    {
        return [
            ['warning_type_name' => 'Admoestação verbal'],
            ['warning_type_name' => 'Repreensão registada'],
            ['warning_type_name' => 'Advertência escrita'],
            ['warning_type_name' => 'Suspensão com perda de remuneração'],
            ['warning_type_name' => 'Multa disciplinar'],
            ['warning_type_name' => 'Despromoção temporária'],
            ['warning_type_name' => 'Despedimento disciplinar'],
            ['warning_type_name' => 'Chamada de atenção'],
            ['warning_type_name' => 'Plano de melhoria/correcção'],
        ];
    }

    private function complaintTypes(): array
    {
        return [
            ['complaint_type' => 'Salário'],
            ['complaint_type' => 'Atraso salarial'],
            ['complaint_type' => 'Horas extras'],
            ['complaint_type' => 'INSS'],
            ['complaint_type' => 'IRPS'],
            ['complaint_type' => 'Férias'],
            ['complaint_type' => 'Faltas'],
            ['complaint_type' => 'Assiduidade'],
            ['complaint_type' => 'Condições de trabalho'],
            ['complaint_type' => 'Saúde e segurança'],
            ['complaint_type' => 'Assédio'],
            ['complaint_type' => 'Discriminação'],
            ['complaint_type' => 'Conflito com chefia'],
            ['complaint_type' => 'Conflito com colega'],
            ['complaint_type' => 'Transferência'],
            ['complaint_type' => 'Promoção/carreira'],
            ['complaint_type' => 'Avaliação de desempenho'],
            ['complaint_type' => 'Benefícios'],
            ['complaint_type' => 'Disciplina'],
            ['complaint_type' => 'Documentos'],
            ['complaint_type' => 'Outros'],
        ];
    }

    private function leaveTypes(): array
    {
        return [
            [
                'name' => 'Férias anuais',
                'legal_code' => 'annual',
                'description' => 'Férias anuais: 12 dias no primeiro ano e 30 dias nos anos subsequentes.',
                'max_days_per_year' => 30,
                'is_paid' => true,
                'requires_supporting_document' => false,
                'must_be_consecutive' => false,
                'fixed_duration_days' => null,
                'min_advance_notice_days' => 5,
                'allow_cash_out' => false,
                'color' => '#10b77f',
            ],
            [
                'name' => 'Licença de maternidade',
                'legal_code' => 'maternity',
                'description' => 'Licença de maternidade por 90 dias consecutivos, com início até 20 dias antes do parto.',
                'max_days_per_year' => 90,
                'is_paid' => true,
                'requires_supporting_document' => true,
                'must_be_consecutive' => true,
                'fixed_duration_days' => 90,
                'min_advance_notice_days' => 20,
                'pre_event_start_window_days' => 20,
                'post_event_start_offset_days' => 0,
                'allow_cash_out' => false,
                'color' => '#F59E0B',
            ],
            [
                'name' => 'Licença de paternidade',
                'legal_code' => 'paternity',
                'description' => 'Licença de paternidade por 7 dias; em casos previstos, pode aplicar-se a regra especial de 60 dias.',
                'max_days_per_year' => 7,
                'is_paid' => true,
                'requires_supporting_document' => true,
                'must_be_consecutive' => true,
                'fixed_duration_days' => 7,
                'min_advance_notice_days' => 0,
                'allow_cash_out' => false,
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Licença de adopção',
                'legal_code' => 'adoption',
                'description' => 'Licença por adopção conforme a legislação aplicável.',
                'is_paid' => true,
                'requires_supporting_document' => true,
                'must_be_consecutive' => true,
                'allow_cash_out' => false,
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'Licença de acolhimento familiar',
                'legal_code' => 'foster_care',
                'description' => 'Licença por acolhimento familiar conforme a legislação aplicável.',
                'is_paid' => true,
                'requires_supporting_document' => true,
                'must_be_consecutive' => true,
                'allow_cash_out' => false,
                'color' => '#6366F1',
            ],
            [
                'name' => 'Licença por doença',
                'legal_code' => 'sick_leave',
                'description' => 'Licença por doença mediante certificado médico.',
                'is_paid' => true,
                'requires_supporting_document' => true,
                'must_be_consecutive' => false,
                'allow_cash_out' => false,
                'color' => '#EF4444',
            ],
            [
                'name' => 'Licença por falecimento',
                'legal_code' => 'bereavement',
                'description' => 'Licença por luto/falecimento de familiar.',
                'max_days_per_year' => 5,
                'is_paid' => true,
                'requires_supporting_document' => true,
                'must_be_consecutive' => true,
                'fixed_duration_days' => 5,
                'allow_cash_out' => false,
                'color' => '#6B7280',
            ],
            [
                'name' => 'Licença por casamento',
                'legal_code' => 'marriage',
                'description' => 'Licença por casamento.',
                'max_days_per_year' => 5,
                'is_paid' => true,
                'requires_supporting_document' => true,
                'must_be_consecutive' => true,
                'fixed_duration_days' => 5,
                'allow_cash_out' => false,
                'color' => '#EC4899',
            ],
            [
                'name' => 'Licença para assistência familiar',
                'legal_code' => 'family_assistance',
                'description' => 'Licença para assistência familiar por doença ou acidente.',
                'is_paid' => true,
                'requires_supporting_document' => true,
                'must_be_consecutive' => false,
                'allow_cash_out' => false,
                'color' => '#0EA5E9',
            ],
            [
                'name' => 'Licença sindical',
                'legal_code' => 'union_leave',
                'description' => 'Licença para actividade sindical ou representativa.',
                'is_paid' => false,
                'requires_supporting_document' => true,
                'must_be_consecutive' => false,
                'allow_cash_out' => false,
                'color' => '#14B8A6',
            ],
            [
                'name' => 'Licença por acidente de trabalho',
                'legal_code' => 'work_accident',
                'description' => 'Licença por acidente de trabalho ou doença profissional.',
                'is_paid' => true,
                'requires_supporting_document' => true,
                'must_be_consecutive' => false,
                'allow_cash_out' => false,
                'color' => '#B91C1C',
            ],
            [
                'name' => 'Licença por serviço público',
                'legal_code' => 'public_service',
                'description' => 'Licença para cumprimento de deveres de serviço público.',
                'is_paid' => true,
                'requires_supporting_document' => true,
                'must_be_consecutive' => false,
                'allow_cash_out' => false,
                'color' => '#2563EB',
            ],
            [
                'name' => 'Outras licenças',
                'legal_code' => 'other',
                'description' => 'Categoria genérica para outras licenças configuráveis.',
                'is_paid' => false,
                'requires_supporting_document' => false,
                'must_be_consecutive' => false,
                'allow_cash_out' => false,
                'color' => '#64748B',
            ],
        ];
    }

    private function documentCategories(): array
    {
        return [
            ['document_type' => 'Identificação pessoal', 'status' => true],
            ['document_type' => 'Fiscal', 'status' => true],
            ['document_type' => 'Segurança social', 'status' => true],
            ['document_type' => 'Contratual', 'status' => true],
            ['document_type' => 'Académico / Formação', 'status' => true],
            ['document_type' => 'Médico', 'status' => true],
            ['document_type' => 'Disciplinar', 'status' => true],
            ['document_type' => 'Férias e faltas', 'status' => true],
            ['document_type' => 'Salários / Recibos', 'status' => true],
            ['document_type' => 'Benefícios', 'status' => true],
            ['document_type' => 'Avaliação de desempenho', 'status' => true],
            ['document_type' => 'Recrutamento', 'status' => true],
            ['document_type' => 'Cessação / Terminação', 'status' => true],
            ['document_type' => 'Estrangeiro', 'status' => true],
            ['document_type' => 'Outros', 'status' => true],
        ];
    }

    private function announcementCategories(): array
    {
        return [
            ['announcement_category' => 'Geral'],
            ['announcement_category' => 'Recursos Humanos'],
            ['announcement_category' => 'Salários / Pagamentos'],
            ['announcement_category' => 'Férias'],
            ['announcement_category' => 'Feriados'],
            ['announcement_category' => 'Formação'],
            ['announcement_category' => 'Recrutamento interno'],
            ['announcement_category' => 'Segurança e saúde'],
            ['announcement_category' => 'Benefícios'],
            ['announcement_category' => 'Políticas internas'],
            ['announcement_category' => 'Eventos'],
            ['announcement_category' => 'Urgente'],
            ['announcement_category' => 'Sistema / IT'],
            ['announcement_category' => 'Comunicado da Direcção'],
            ['announcement_category' => 'Disciplinar'],
            ['announcement_category' => 'Outros'],
        ];
    }

    private function eventTypes(): array
    {
        return [
            ['event_type' => 'Admissão'],
            ['event_type' => 'Fim do período probatório'],
            ['event_type' => 'Renovação de contrato'],
            ['event_type' => 'Terminação de contrato'],
            ['event_type' => 'Promoção'],
            ['event_type' => 'Transferência'],
            ['event_type' => 'Mudança de função'],
            ['event_type' => 'Mudança de departamento'],
            ['event_type' => 'Aumento salarial'],
            ['event_type' => 'Avaliação de desempenho'],
            ['event_type' => 'Formação'],
            ['event_type' => 'Acidente de trabalho'],
            ['event_type' => 'Doença profissional'],
            ['event_type' => 'Falta'],
            ['event_type' => 'Férias'],
            ['event_type' => 'Licença'],
            ['event_type' => 'Aniversário'],
            ['event_type' => 'Aniversário da empresa'],
            ['event_type' => 'Processo disciplinar'],
            ['event_type' => 'Reclamação'],
            ['event_type' => 'Elogio'],
            ['event_type' => 'Empréstimo / adiantamento'],
            ['event_type' => 'Outros'],
        ];
    }

    private function holidayTypes(): array
    {
        return [
            ['holiday_type' => 'Feriado Nacional'],
            ['holiday_type' => 'Feriado Religioso'],
            ['holiday_type' => 'Feriado Provincial'],
            ['holiday_type' => 'Feriado Municipal'],
            ['holiday_type' => 'Feriado Empresarial'],
        ];
    }

    private function shiftTypes(): array
    {
        return [
            ['shift_name' => 'Horário normal', 'start_time' => '08:00:00', 'end_time' => '17:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'is_night_shift' => false],
            ['shift_name' => 'Trabalho por turnos - manhã', 'start_time' => '06:00:00', 'end_time' => '14:00:00', 'break_start_time' => '10:00:00', 'break_end_time' => '10:30:00', 'is_night_shift' => false],
            ['shift_name' => 'Trabalho por turnos - tarde', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'break_start_time' => '18:00:00', 'break_end_time' => '18:30:00', 'is_night_shift' => false],
            ['shift_name' => 'Horário nocturno', 'start_time' => '22:00:00', 'end_time' => '06:00:00', 'break_start_time' => '02:00:00', 'break_end_time' => '02:30:00', 'is_night_shift' => true],
            ['shift_name' => 'Tempo parcial', 'start_time' => '09:00:00', 'end_time' => '13:00:00', 'break_start_time' => null, 'break_end_time' => null, 'is_night_shift' => false],
            ['shift_name' => 'Horário flexível', 'start_time' => '10:00:00', 'end_time' => '18:00:00', 'break_start_time' => '13:00:00', 'break_end_time' => '14:00:00', 'is_night_shift' => false],
            ['shift_name' => 'Isenção de horário', 'start_time' => '08:00:00', 'end_time' => '17:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'is_night_shift' => false],
            ['shift_name' => 'Trabalho extraordinário', 'start_time' => '17:00:00', 'end_time' => '21:00:00', 'break_start_time' => null, 'break_end_time' => null, 'is_night_shift' => false],
            ['shift_name' => 'Horário excepcional', 'start_time' => '07:00:00', 'end_time' => '15:00:00', 'break_start_time' => '11:00:00', 'break_end_time' => '11:30:00', 'is_night_shift' => false],
            ['shift_name' => 'Teletrabalho', 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'break_start_time' => '12:30:00', 'break_end_time' => '13:30:00', 'is_night_shift' => false],
        ];
    }

    private function deductionTypes(): array
    {
        return [
            ['name' => 'INSS trabalhador', 'description' => 'Desconto legal do INSS a cargo do trabalhador.'],
            ['name' => 'IRPS', 'description' => 'Retenção do imposto sobre o rendimento.'],
            ['name' => 'Adiantamento salarial', 'description' => 'Adiantamento previamente autorizado.'],
            ['name' => 'Prestação de empréstimo da empresa', 'description' => 'Desconto para amortização de empréstimo interno.'],
            ['name' => 'Falta injustificada', 'description' => 'Desconto associado a faltas injustificadas.'],
            ['name' => 'Atrasos', 'description' => 'Desconto associado a atrasos.'],
            ['name' => 'Suspensão com perda de remuneração', 'description' => 'Sanção disciplinar com perda de remuneração.'],
            ['name' => 'Multa disciplinar', 'description' => 'Multa disciplinar devidamente suportada.'],
            ['name' => 'Penhora / ordem judicial', 'description' => 'Desconto por ordem judicial.'],
            ['name' => 'Quota sindical', 'description' => 'Quota sindical autorizada ou colectiva.'],
            ['name' => 'Seguro de saúde', 'description' => 'Desconto associado a seguro de saúde.'],
            ['name' => 'Fundo de pensões', 'description' => 'Contribuição para fundo de pensões.'],
            ['name' => 'Alimentação / cantina', 'description' => 'Desconto para alimentação ou cantina.'],
            ['name' => 'Transporte da empresa', 'description' => 'Desconto associado a transporte.'],
            ['name' => 'Telefone / dados', 'description' => 'Desconto por uso de comunicação.'],
            ['name' => 'Danos em equipamento', 'description' => 'Desconto por danos em equipamento, quando permitido.'],
            ['name' => 'Uniforme', 'description' => 'Desconto de uniforme conforme política interna.'],
            ['name' => 'Outros descontos autorizados', 'description' => 'Outros descontos com autorização escrita.'],
        ];
    }

    private function loanTypes(): array
    {
        return [
            ['name' => 'Adiantamento salarial', 'description' => 'Adiantamento sobre o salário.'],
            ['name' => 'Empréstimo pessoal', 'description' => 'Empréstimo pessoal ao trabalhador.'],
            ['name' => 'Empréstimo de emergência', 'description' => 'Empréstimo para situações urgentes.'],
            ['name' => 'Empréstimo médico', 'description' => 'Empréstimo para despesas médicas.'],
            ['name' => 'Empréstimo para educação', 'description' => 'Empréstimo para educação/formação.'],
            ['name' => 'Empréstimo para habitação', 'description' => 'Empréstimo para habitação.'],
            ['name' => 'Empréstimo para transporte', 'description' => 'Empréstimo para transporte.'],
            ['name' => 'Apoio/funeral', 'description' => 'Apoio ou empréstimo para funeral.'],
            ['name' => 'Empréstimo para equipamento', 'description' => 'Empréstimo para aquisição de equipamento.'],
            ['name' => 'Empréstimo geral da empresa', 'description' => 'Empréstimo interno concedido pela empresa.'],
            ['name' => 'Adiantamento de subsídio', 'description' => 'Adiantamento de subsídio regular.'],
            ['name' => 'Adiantamento de comissão', 'description' => 'Adiantamento sobre comissões.'],
            ['name' => 'Outro empréstimo', 'description' => 'Outro tipo de empréstimo configurável.'],
        ];
    }

    private function contractTypes(): array
    {
        return [
            ['name' => 'Tempo indeterminado', 'is_active' => true],
            ['name' => 'Prazo certo', 'is_active' => true],
            ['name' => 'Prazo incerto', 'is_active' => true],
            ['name' => 'Tempo parcial', 'is_active' => true],
            ['name' => 'Trabalhador estrangeiro', 'is_active' => true],
            ['name' => 'Trabalho temporário', 'is_active' => true],
            ['name' => 'Aprendizagem', 'is_active' => true],
            ['name' => 'Intermitente', 'is_active' => true],
            ['name' => 'Teletrabalho', 'is_active' => true],
            ['name' => 'Comissão de serviço', 'is_active' => true],
            ['name' => 'Trabalho no domicílio', 'is_active' => true],
            ['name' => 'Empreitada', 'is_active' => true],
            ['name' => 'Cedência ocasional', 'is_active' => true],
        ];
    }
}
