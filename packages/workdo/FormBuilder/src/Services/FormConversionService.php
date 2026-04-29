<?php

namespace Workdo\FormBuilder\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Workdo\FormBuilder\Models\Form;
use Workdo\FormBuilder\Models\FormConversion;
use Workdo\FormBuilder\Models\FormResponse;

class FormConversionService
{
    public $created_by;
    public $creator_id;
    public $user;

    /**
     * Process form submission conversion
     */
    public function processConversion(Form $form, FormResponse $response): void
    {
        $this->created_by   = $form->created_by;
        $this->creator_id   = $form->creator_id;
        $this->user        = User::find($this->creator_id);
        $conversion         = $form->conversion;

        if (!$conversion || !$conversion->is_active) {
            return;
        }

        try {
            $convertedRecord = $this->convertToModule($conversion, $response);
        } catch (\Exception $e) {
            
        }
    }

    /**
     * Convert form data to specific module
     */
    private function convertToModule(FormConversion $conversion, FormResponse $response): mixed
    {
        $moduleName     = $conversion->module_name;
        $submoduleName  = $conversion->submodule_name;
        $fieldMappings  = $conversion->field_mappings;
        $responseData   = $response->response_data;

        $mappedData = $this->mapFormDataToModule($fieldMappings, $responseData);

        if (!Module_is_active($moduleName, $this->created_by)) {
            throw new \Exception("{$moduleName} module not available or not enabled");
        }

        switch ($moduleName) {
            case 'Lead':
                return $this->convertToLead($submoduleName, $mappedData);
            case 'Taskly':
                return $this->convertToTaskly($submoduleName, $mappedData);
            case 'Sales':
                return $this->convertToSales($submoduleName, $mappedData);
            case 'Contract':
                return $this->convertToContract($submoduleName, $mappedData);
            case 'Internalknowledge':
                return $this->convertToInternalKnowledge($submoduleName, $mappedData);
            case 'Notes':
                return $this->convertToNotes($submoduleName, $mappedData);
            case 'CMMS':
                return $this->convertToCMMS($submoduleName, $mappedData);
            case 'MachineRepairManagement':
                return $this->convertToMachineRepairManagement($submoduleName, $mappedData);
            default:
                throw new \Exception("Unsupported module: {$moduleName}");
        }
    }

    /**
     * Map form field data to module fields
     */
    private function mapFormDataToModule(array $fieldMappings, array $responseData): array
    {
        $mappedData = [];
        foreach ($fieldMappings as $moduleField => $formFieldId) {
            $mappedData[$moduleField] = is_array($formFieldId) ? $formFieldId :   $responseData[$formFieldId] ?? $formFieldId ?? null;
        }

        return $mappedData;
    }

    /**
     * Convert to Taskly module
     */
    private function convertToTaskly(string $submodule, array $data): mixed
    {
        if ($submodule === 'Project') {
            return $this->createProject($data);
        }
        return null;
    }

    /**
     * Convert to Lead module
     */
    private function convertToLead(string $submodule, array $data): mixed
    {
        switch ($submodule) {
            case 'Lead':
                return $this->createLead($data);
            case 'Deal':
                return $this->createDeal($data);
        }
        return null;
    }

    /**
     * Convert to Sales module
     */
    private function convertToSales(string $submodule, array $data): mixed
    {
        switch ($submodule) {
            case 'Contact':
                return $this->createSalesContact($data);
            case 'Opportunity':
                return $this->createSalesOpportunity($data);
        }
        return null;
    }

    /**
     * Convert to Contract module
     */
    private function convertToContract(string $submodule, array $data): mixed
    {
        if ($submodule === 'Contract') {
            return $this->createContract($data);
        }
        return null;
    }

    /**
     * Convert to Internal Knowledge module
     */
    private function convertToInternalKnowledge(string $submodule, array $data): mixed
    {
        switch ($submodule) {
            case 'Book':
                return $this->createBook($data);
            case 'Article':
                return $this->createArticle($data);
        }
        return null;
    }

    /**
     * Convert to Notes module
     */
    private function convertToNotes(string $submodule, array $data): mixed
    {
        if ($submodule === 'Note') {
            return $this->createNote($data);
        }
        return null;
    }

    /**
     * Convert to CMMS module
     */
    private function convertToCMMS(string $submodule, array $data): mixed
    {
        if ($submodule === 'Location') {
            return $this->createLocation($data);
        }
        return null;
    }

    /**
     * Convert to Machine Repair Management module
     */
    private function convertToMachineRepairManagement(string $submodule, array $data): mixed
    {
        if ($submodule === 'Machine') {
            return $this->createMachine($data);
        }
        return null;
    }

    private function createProject(array $data): mixed
    {
        if ($this->user->can('create-project')) {
            try {
                $project = new \Workdo\Taskly\Models\Project();
                $project->name          = $data['project_name'] ?? '';
                $project->description   = $data['description'] ?? '';
                $project->start_date    = $data['start_date'] ?? date('Y-m-d');
                $project->end_date      = $data['end_date'] ?? date('Y-m-d');
                $project->budget        = $data['budget'] ?? 0;
                $project->status        = 'Ongoing';
                $project->type          = 'Project';
                $project->creator_id    = $this->creator_id;
                $project->created_by    = $this->created_by;
                $project->save();

                if (isset($data['users_id'])) {
                    $userIds = is_array($data['users_id']) ? $data['users_id'] : [$data['users_id']];
                    $project->teamMembers()->sync(array_filter($userIds));
                }

                return $project;
            } catch (\Exception $e) {
                throw $e;
            }
        }
        throw new \Exception('You do not have permission to create project records.');
    }

    private function createLead(array $data): mixed
    {
        if ($this->user->can('create-leads')) {
            // Get or create default pipeline
            $pipeline = null;
            if (isset($data['pipeline_id'])) {
                $pipeline = \Workdo\Lead\Models\Pipeline::where('created_by', $this->created_by)
                    ->where('id', $data['pipeline_id'])
                    ->first();
            }

            if (!$pipeline) {
                $pipeline = \Workdo\Lead\Models\Pipeline::where('created_by', $this->created_by)->first();
            }

            if (!$pipeline) {
                throw new \Exception('Please create a pipeline first.');
            }

            // Get first stage from pipeline
            $stage = \Workdo\Lead\Models\LeadStage::where('pipeline_id', $pipeline->id)->first();
            if (!$stage) {
                throw new \Exception('Please create a stage for this pipeline.');
            }

            $lead = new \Workdo\Lead\Models\Lead();
            $lead->name         = $data['name'] ?? '';
            $lead->email        = $data['email'] ?? '';
            $lead->subject      = $data['subject'] ?? '';
            $lead->phone        = $data['phone'] ?? '';
            $lead->date         = $data['date'] ?? date('Y-m-d');
            $lead->user_id      = $data['user_id'] ?? null;
            $lead->pipeline_id  = $pipeline->id;
            $lead->stage_id     = $stage->id;
            $lead->creator_id   = $this->creator_id;
            $lead->created_by   = $this->created_by;
            $lead->save();

            // Create user leads (same logic as Lead controller)
            $usrLeads = [
                $this->created_by,
                $data['user_id'] ?? null,
            ];
            $usrLeads = array_unique(array_filter($usrLeads));

            foreach ($usrLeads as $usrLead) {
                \Workdo\Lead\Models\UserLead::firstOrCreate([
                    'user_id' => $usrLead,
                    'lead_id' => $lead->id,
                ]);
            }

            return $lead;
        }
        throw new \Exception('You do not have permission to create lead records.');
    }

    private function createDeal(array $data): mixed
    {
        if ($this->user->can('create-deals')) {
            // Get or create default pipeline
            $pipeline = null;
            if (isset($data['pipeline_id'])) {
                $pipeline = \Workdo\Lead\Models\Pipeline::where('created_by', $this->created_by)
                    ->where('id', $data['pipeline_id'])
                    ->first();
            }

            if (!$pipeline) {
                $pipeline = \Workdo\Lead\Models\Pipeline::where('created_by', $this->created_by)->first();
            }

            if (!$pipeline) {
                throw new \Exception('Please create a pipeline first.');
            }

            // Get first stage from pipeline
            $stage = \Workdo\Lead\Models\DealStage::where('pipeline_id', $pipeline->id)->first();
            if (!$stage) {
                throw new \Exception('Please create a stage for this pipeline.');
            }

            $deal = new \Workdo\Lead\Models\Deal();
            $deal->name         = $data['name'] ?? '';
            $deal->price        = $data['price'] ?? 0;
            $deal->phone        = $data['phone'] ?? '';
            $deal->pipeline_id  = $pipeline->id;
            $deal->stage_id     = $stage->id;
            $deal->status       = 'Active';
            $deal->creator_id   = $this->creator_id;
            $deal->created_by   = $this->created_by;
            $deal->save();

            // Create client deals - handle both array and single values
            if (isset($data['clients'])) {
                $clients = is_array($data['clients']) ? $data['clients'] : [$data['clients']];
                $clients = array_filter($clients);

                foreach ($clients as $clientId) {
                    if (!empty($clientId)) {
                        \Workdo\Lead\Models\ClientDeal::create([
                            'deal_id' => $deal->id,
                            'client_id' => $clientId,
                        ]);
                    }
                }
            }

            // Create user deals (same logic as Deal controller)
            $usrDeals = [$this->created_by];
            if ($this->creator_id !== $this->created_by) {
                $usrDeals[] = $this->creator_id;
            }

            foreach ($usrDeals as $usrDeal) {
                \Workdo\Lead\Models\UserDeal::create([
                    'user_id' => $usrDeal,
                    'deal_id' => $deal->id,
                ]);
            }

            return $deal;
        }
        throw new \Exception('You do not have permission to create deal records.');
    }

    private function createSalesContact(array $data): mixed
    {
        if ($this->user->can('create-sales-contacts')) {
            $contact = new \Workdo\Sales\Models\SalesContact();
            $contact->name              = $data['name'] ?? '';
            $contact->email             = $data['email'] ?? '';
            $contact->phone             = $data['phone'] ?? '';
            $contact->address           = $data['address'] ?? '';
            $contact->city              = $data['city'] ?? '';
            $contact->state             = $data['state'] ?? '';
            $contact->postal_code       = $data['postal_code'] ?? '';
            $contact->country           = $data['country'] ?? '';
            $contact->account_id        = $data['account_id'] ?? null;
            $contact->assign_user_id    = $data['assign_user_id'] ?? null;
            $contact->is_active         = true;
            $contact->creator_id        = $this->creator_id;
            $contact->created_by        = $this->created_by;
            $contact->save();

            return $contact;
        }
        throw new \Exception('You do not have permission to create sales contact records.');
    }

    private function createSalesOpportunity(array $data): mixed
    {
        if ($this->user->can('create-sales-opportunities')) {
            $opportunity = new \Workdo\Sales\Models\SalesOpportunity();
            $opportunity->name              = $data['name'] ?? '';
            $opportunity->account_id        = $data['account_id'] ?? null;
            $opportunity->contact_id        = $data['contact_id'] ?? null;
            $opportunity->stage_id          = $data['stage_id'] ?? null;
            $opportunity->amount            = $data['amount'] ?? 0;
            $opportunity->probability       = $data['probability'] ?? 0;
            $opportunity->close_date        = $data['close_date'] ?? null;
            $opportunity->assign_user_id    = $data['assign_user_id'] ?? null;
            $opportunity->is_active         = true;
            $opportunity->creator_id        = $this->creator_id;
            $opportunity->created_by        = $this->created_by;
            $opportunity->save();

            return $opportunity;
        }
        throw new \Exception('You do not have permission to create sales opportunity records.');
    }

    private function createContract(array $data): mixed
    {
        if ($this->user->can('create-contracts')) {
            $contract = new \Workdo\Contract\Models\Contract();
            $contract->subject      = $data['subject'] ?? '';
            $contract->user_id      = $data['user_id'] ?? null;
            $contract->value        = $data['value'] ?? 0;
            $contract->type_id      = $data['type_id'] ?? null;
            $contract->start_date   = $data['start_date'] ?? date('Y-m-d');
            $contract->end_date     = $data['end_date'] ?? date('Y-m-d');
            $contract->description  = $data['description'] ?? '';
            $contract->status       = $data['status'] ?? 'pending';
            $contract->creator_id   = $this->creator_id;
            $contract->created_by   = $this->created_by;
            $contract->save();

            return $contract;
        }
        throw new \Exception('You do not have permission to create contract records.');
    }

    private function createBook(array $data): mixed
    {
        if ($this->user->can('create-internalknowledge-books')) {
            $book = new \Workdo\Internalknowledge\Models\InternalknowledgeBook();
            $book->title        = $data['title'] ?? '';
            $book->description  = $data['description'] ?? '';

            $users = isset($data['users']) ? $data['users'] : [];
            if (is_array($users)) {
                $book->users = array_map("strval", $users);
            } else {
                $book->users = $users ? [strval($users)] : [];
            }

            $book->creator_id   = $this->creator_id;
            $book->created_by   = $this->created_by;
            $book->save();

            return $book;
        }
        throw new \Exception('You do not have permission to create book records.');
    }

    private function createArticle(array $data): mixed
    {
        if ($this->user->can('create-internalknowledge-articles')) {
            $article = new \Workdo\Internalknowledge\Models\InternalknowledgeArticle();
            $article->internalknowledge_book_id = $data['internalknowledge_book_id'] ?? null;
            $article->title           = $data['title'] ?? '';
            $article->description     = $data['description'] ?? '';
            $article->type            = $data['type'] ?? 'Document';
            $article->document_content = $data['document_content'] ?? null;
            $article->mindmap_content = $data['mindmap_content'] ?? null;
            $article->creator_id      = $this->creator_id;
            $article->created_by      = $this->created_by;
            $article->save();

            return $article;
        }
        throw new \Exception('You do not have permission to create article records.');
    }

    private function createNote(array $data): mixed
    {
        if ($this->user->can('create-notes')) {
            $note = new \Workdo\Notes\Models\Note();
            $note->title = $data['title'] ?? '';
            $note->description = $data['description'] ?? '';
            $note->color = $data['color'] ?? 'bg-primary';
            $note->type = $data['type'] ?? '0';
            $note->is_pinned = false;
            $note->is_favorite = false;
            $note->creator_id = $this->creator_id;
            $note->created_by = $this->created_by;
            $note->save();

            return $note;
        }
        throw new \Exception('You do not have permission to create note records.');
    }

    private function createLocation(array $data): mixed
    {
        if ($this->user->can('create-cmms-locations')) {
            $location = new \Workdo\CMMS\Models\CmmsLocation();
            $location->name = $data['name'] ?? '';
            $location->address = $data['address'] ?? '';
            $location->creator_id = $this->creator_id;
            $location->created_by = $this->created_by;
            $location->save();

            return $location;
        }
        throw new \Exception('You do not have permission to create location records.');
    }

    private function createMachine(array $data): mixed
    {
        if ($this->user->can('create-machines')) {
            $machine = new \Workdo\MachineRepairManagement\Models\Machine();
            $machine->machine_name = $data['machine_name'] ?? '';
            $machine->manufacturer = $data['manufacturer'] ?? '';
            $machine->model = $data['model'] ?? '';
            $machine->installation_date = $data['installation_date'] ?? date('Y-m-d');
            $machine->description = $data['description'] ?? '';
            $machine->is_enabled = $data['is_enabled'] ?? true;
            $machine->creator_id = $this->creator_id;
            $machine->created_by = $this->created_by;
            $machine->save();

            return $machine;
        }
        throw new \Exception('You do not have permission to create machine records.');
    }
}
