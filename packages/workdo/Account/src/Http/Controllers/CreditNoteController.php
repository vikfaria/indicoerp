<?php

namespace Workdo\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoiceReturn;
use App\Services\FiscalDocumentComplianceService;
use App\Models\User;
use Workdo\Account\Models\CreditNote;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Workdo\Account\Events\ApproveCreditNote;
use Workdo\Account\Events\DestroyCreditNote;
use Workdo\Account\Services\JournalService;

class CreditNoteController extends Controller
{
    protected $journalService;
    protected $fiscalDocumentComplianceService;

    public function __construct(JournalService $journalService, FiscalDocumentComplianceService $fiscalDocumentComplianceService)
    {
        $this->journalService = $journalService;
        $this->fiscalDocumentComplianceService = $fiscalDocumentComplianceService;
    }

    private function checkCreditNoteAccess(CreditNote $creditNote)
    {
        if(Auth::user()->can('manage-any-credit-notes')) {
            return true;
        } elseif(Auth::user()->can('manage-own-credit-notes')) {
            if($creditNote->creator_id != Auth::id() && $creditNote->customer_id != Auth::id()) {
                return false;
            }
            if($creditNote->creator_id != Auth::id() && Auth::user()->type == 'client' && $creditNote->status == 'draft') {
                return false;
            }
            return true;
        }
        return false;
    }

    public function index(Request $request)
    {
        if(Auth::user()->can('manage-credit-notes')){
            $query = CreditNote::with(['customer', 'salesReturn', 'approvedBy'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-credit-notes')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-credit-notes')) {
                        $q->where('creator_id', Auth::id())->orWhere('customer_id', Auth::id());
                        if(Auth::user()->type == 'client') {
                            $q->where('status','!=', 'draft');
                        }
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            if ($request->customer_id) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->search) {
                $query->where('credit_note_number', 'like', '%' . $request->search . '%');
            }

            if ($request->sales_return_id) {
                $query->where('return_id', $request->sales_return_id);
            }

            if ($request->sort) {
                $query->orderBy($request->sort, $request->direction ?? 'asc');
            } else {
                $query->orderBy('credit_note_date', 'desc');
            }

            $creditNotes = $query->paginate($request->per_page ?? 10)->withQueryString();

            $customers = User::where('type', 'client')->where('created_by', creatorId())->get(['id', 'name']);
            $salesReturns = SalesInvoiceReturn::where('created_by', creatorId())->get(['id', 'return_number']);

            return Inertia::render('Account/CreditNotes/Index', [
                'creditNotes' => $creditNotes,
                'customers' => $customers,
                'salesReturns' => $salesReturns,
                'filters' => $request->only(['customer_id', 'status', 'sales_return_id'])
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function show(CreditNote $creditNote)
    {
        if(Auth::user()->can('view-credit-notes') &&
           (Auth::user()->type == 'client' ? $creditNote->customer_id == Auth::id() : $creditNote->created_by == creatorId())){
            if(!$this->checkCreditNoteAccess($creditNote)) {
                return redirect()->route('account.credit-notes.index')->with('error', __('Permission denied'));
            }

            $creditNote->load(['customer', 'invoice', 'items.product', 'items.taxes', 'salesReturn', 'applications.payment']);

            return Inertia::render('Account/CreditNotes/View', [
                'creditNote' => $creditNote
            ]);
        }
        else{
            return redirect()->route('account.credit-notes.index')->with('error', __('Permission denied'));
        }
    }

    public function print(CreditNote $creditNote)
    {
        if(Auth::user()->can('view-credit-notes') &&
           (Auth::user()->type == 'client' ? $creditNote->customer_id == Auth::id() : $creditNote->created_by == creatorId())){
            if(!$this->checkCreditNoteAccess($creditNote)) {
                return redirect()->route('account.credit-notes.index')->with('error', __('Permission denied'));
            }

            $creditNote->load(['customer', 'invoice', 'items.product', 'items.taxes', 'salesReturn', 'applications.payment']);

            return Inertia::render('Account/CreditNotes/Print', [
                'creditNote' => $creditNote
            ]);
        }
        else{
            return redirect()->route('account.credit-notes.index')->with('error', __('Permission denied'));
        }
    }

    public function approve(CreditNote $creditNote)
    {
        if(Auth::user()->can('approve-credit-notes')){
            if ($creditNote->status !== 'draft') {
                return back()->with('error', __('Only draft credit notes can be approved.'));
            }
            try {
                // Create journal entries
                $this->journalService->createCreditNoteJournal($creditNote);
                $this->journalService->createCreditNoteCOGSJournal($creditNote);

                $creditNote->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id()
                ]);
                ApproveCreditNote::dispatch($creditNote);

                return back()->with('success', __('Credit note approved successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(CreditNote $creditNote)
    {
        if(Auth::user()->can('delete-credit-notes')){
            if ($creditNote->status !== 'draft') {
                return back()->with('error', __('Only draft credit notes can be deleted.'));
            }

            DestroyCreditNote::dispatch($creditNote);

            $creditNote->delete();
            return back()->with('success', __('Credit note deleted successfully.'));
        }
        else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateFiscalStatus(Request $request, CreditNote $creditNote)
    {
        if (!Auth::user()->can('approve-credit-notes')) {
            return back()->with('error', __('Permission denied'));
        }

        if (!$this->checkCreditNoteAccess($creditNote)) {
            return redirect()->route('account.credit-notes.index')->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'status' => 'required|string|in:pending,submitted,validated,rejected,not_required',
            'reference' => 'nullable|string|max:120',
            'message' => 'nullable|string|max:255',
        ]);

        try {
            $this->fiscalDocumentComplianceService->updateSubmissionStatus(
                $creditNote,
                $validated['status'],
                $validated['reference'] ?? null,
                $validated['message'] ?? null
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', __('Fiscal submission status updated successfully.'));
    }

    public function cancelFiscal(Request $request, CreditNote $creditNote)
    {
        if (!Auth::user()->can('delete-credit-notes') && !Auth::user()->can('approve-credit-notes')) {
            return back()->with('error', __('Permission denied'));
        }

        if (!$this->checkCreditNoteAccess($creditNote)) {
            return redirect()->route('account.credit-notes.index')->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:1000',
            'cancellation_reference' => 'nullable|string|max:120',
            'rectification_reference' => 'nullable|string|max:120',
        ]);

        try {
            $this->fiscalDocumentComplianceService->cancelDocument(
                $creditNote,
                $validated['reason'],
                $validated['cancellation_reference'] ?? null,
                $validated['rectification_reference'] ?? null,
                Auth::id()
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', __('Document cancelled successfully.'));
    }
}
