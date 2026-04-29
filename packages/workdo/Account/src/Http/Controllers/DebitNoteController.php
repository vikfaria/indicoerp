<?php

namespace Workdo\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReturn;
use App\Services\FiscalDocumentComplianceService;
use App\Models\User;
use Workdo\Account\Models\DebitNote;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Workdo\Account\Events\ApproveDebitNote;
use Workdo\Account\Events\DestroyDebitNote;
use Workdo\Account\Services\JournalService;

class DebitNoteController extends Controller
{
    protected $journalService;
    protected $fiscalDocumentComplianceService;

    public function __construct(JournalService $journalService, FiscalDocumentComplianceService $fiscalDocumentComplianceService)
    {
        $this->journalService = $journalService;
        $this->fiscalDocumentComplianceService = $fiscalDocumentComplianceService;
    }

    private function checkDebitNoteAccess(DebitNote $debitNote)
    {
        if(Auth::user()->can('manage-any-debit-notes')) {
            return true;
        } elseif(Auth::user()->can('manage-own-debit-notes')) {
            if($debitNote->creator_id != Auth::id() && $debitNote->vendor_id != Auth::id()) {
                return false;
            }
            if($debitNote->creator_id != Auth::id() && Auth::user()->type == 'vendor' && $debitNote->status == 'draft') {
                return false;
            }
            return true;
        }
        return false;
    }

    public function index(Request $request)
    {
        if(Auth::user()->can('manage-debit-notes')){
            $query = DebitNote::with(['vendor', 'purchaseReturn', 'approvedBy'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-debit-notes')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-debit-notes')) {
                        $q->where('creator_id', Auth::id())->orWhere('vendor_id', Auth::id());
                        if(Auth::user()->type == 'vendor') {
                            $q->where('status','!=', 'draft');
                        }
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            if ($request->vendor_id) {
                $query->where('vendor_id', $request->vendor_id);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->search) {
                $query->where('debit_note_number', 'like', '%' . $request->search . '%');
            }

            if ($request->purchase_return_id) {
                $query->where('return_id', $request->purchase_return_id);
            }

            if ($request->sort) {
                $query->orderBy($request->sort, $request->direction ?? 'asc');
            } else {
                $query->orderBy('debit_note_date', 'desc');
            }

            $debitNotes = $query->paginate($request->per_page ?? 10)->withQueryString();

            $vendors = User::where('type', 'vendor')->where('created_by', creatorId())->get(['id', 'name']);
            $purchaseReturns = PurchaseReturn::where('created_by', creatorId())->get(['id', 'return_number']);

            return Inertia::render('Account/DebitNotes/Index', [
                'debitNotes' => $debitNotes,
                'vendors' => $vendors,
                'purchaseReturns' => $purchaseReturns,
                'filters' => $request->only(['vendor_id', 'status', 'purchase_return_id'])
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function show(DebitNote $debitNote)
    {
        if(Auth::user()->can('view-debit-notes') &&
           (Auth::user()->type == 'vendor' ? $debitNote->vendor_id == Auth::id() : $debitNote->created_by == creatorId())){
            if(!$this->checkDebitNoteAccess($debitNote)) {
                return redirect()->route('account.debit-notes.index')->with('error', __('Permission denied'));
            }

            $debitNote->load(['vendor', 'invoice', 'items.product', 'items.taxes', 'purchaseReturn', 'applications.payment']);

            return Inertia::render('Account/DebitNotes/View', [
                'debitNote' => $debitNote
            ]);
        }
        else{
            return redirect()->route('account.debit-notes.index')->with('error', __('Permission denied'));
        }
    }

    public function print(DebitNote $debitNote)
    {
        if(Auth::user()->can('view-debit-notes') &&
           (Auth::user()->type == 'vendor' ? $debitNote->vendor_id == Auth::id() : $debitNote->created_by == creatorId())){
            if(!$this->checkDebitNoteAccess($debitNote)) {
                return redirect()->route('account.debit-notes.index')->with('error', __('Permission denied'));
            }

            $debitNote->load(['vendor', 'invoice', 'items.product', 'items.taxes', 'purchaseReturn', 'applications.payment']);

            return Inertia::render('Account/DebitNotes/Print', [
                'debitNote' => $debitNote
            ]);
        }
        else{
            return redirect()->route('account.debit-notes.index')->with('error', __('Permission denied'));
        }
    }

    public function approve(DebitNote $debitNote)
    {
        if(Auth::user()->can('approve-debit-notes')){
            if ($debitNote->status !== 'draft') {
                return back()->with('error', __('Only draft debit notes can be approved.'));
            }
            try {
                // Create journal entries
                $this->journalService->createDebitNoteJournal($debitNote);

                $debitNote->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id()
                ]);
                ApproveDebitNote::dispatch($debitNote);

                return back()->with('success', __('Debit note approved successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(DebitNote $debitNote)
    {
        if(Auth::user()->can('delete-debit-notes')){
            if ($debitNote->status !== 'draft') {
                return back()->with('error', __('Only draft debit notes can be deleted.'));
            }

            DestroyDebitNote::dispatch($debitNote);

            $debitNote->delete();
            return back()->with('success', __('Debit note deleted successfully.'));
        }
        else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateFiscalStatus(Request $request, DebitNote $debitNote)
    {
        if (!Auth::user()->can('approve-debit-notes')) {
            return back()->with('error', __('Permission denied'));
        }

        if (!$this->checkDebitNoteAccess($debitNote)) {
            return redirect()->route('account.debit-notes.index')->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'status' => 'required|string|in:pending,submitted,validated,rejected,not_required',
            'reference' => 'nullable|string|max:120',
            'message' => 'nullable|string|max:255',
        ]);

        try {
            $this->fiscalDocumentComplianceService->updateSubmissionStatus(
                $debitNote,
                $validated['status'],
                $validated['reference'] ?? null,
                $validated['message'] ?? null
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', __('Fiscal submission status updated successfully.'));
    }

    public function cancelFiscal(Request $request, DebitNote $debitNote)
    {
        if (!Auth::user()->can('delete-debit-notes') && !Auth::user()->can('approve-debit-notes')) {
            return back()->with('error', __('Permission denied'));
        }

        if (!$this->checkDebitNoteAccess($debitNote)) {
            return redirect()->route('account.debit-notes.index')->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:1000',
            'cancellation_reference' => 'nullable|string|max:120',
            'rectification_reference' => 'nullable|string|max:120',
        ]);

        try {
            $this->fiscalDocumentComplianceService->cancelDocument(
                $debitNote,
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
