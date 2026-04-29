<?php

namespace Workdo\DoubleEntry\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\DoubleEntry\Models\BalanceSheet;
use Workdo\DoubleEntry\Models\BalanceSheetNote;
use Workdo\DoubleEntry\Models\ComparativeBalanceSheet;
use Workdo\DoubleEntry\Services\BalanceSheetService;
use Workdo\DoubleEntry\Events\CreateBalanceSheet;
use Workdo\DoubleEntry\Events\FinalizeBalanceSheet;
use Workdo\DoubleEntry\Events\DestroyBalanceSheet;
use Workdo\DoubleEntry\Events\CreateBalanceSheetNote;
use Workdo\DoubleEntry\Events\DestroyBalanceSheetNote;
use Workdo\DoubleEntry\Events\YearEndClose;
use Workdo\DoubleEntry\Http\Requests\StoreBalanceSheetRequest;
use Workdo\DoubleEntry\Http\Requests\YearEndCloseRequest;
use Workdo\DoubleEntry\Http\Requests\CompareBalanceSheetRequest;
use Workdo\DoubleEntry\Http\Requests\StoreBalanceSheetNoteRequest;

class BalanceSheetController extends Controller
{
    protected $balanceSheetService;

    public function __construct(BalanceSheetService $balanceSheetService)
    {
        $this->balanceSheetService = $balanceSheetService;
    }

    public function index(Request $request)
    {
        if(Auth::user()->can('manage-balance-sheets')){
            if(Auth::user()->can('view-balance-sheets')){
                $latestBalanceSheet = BalanceSheet::where('created_by', creatorId())
                    ->latest()
                    ->first();

                if ($latestBalanceSheet) {
                    return redirect()->route('double-entry.balance-sheets.show', $latestBalanceSheet->id);
                }
            }
            return redirect()->route('double-entry.balance-sheets.list');
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function list(Request $request)
    {
        if(Auth::user()->can('manage-balance-sheets')){
            $balanceSheets = BalanceSheet::query()
                ->where('created_by', creatorId())
                ->when(request('financial_year'), fn($q) => $q->where('financial_year', request('financial_year')))
                ->when(request('status'), fn($q) => $q->where('status', request('status')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'desc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('DoubleEntry/BalanceSheets/Index', [
                'balanceSheets' => $balanceSheets,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreBalanceSheetRequest $request)
    {
        if(Auth::user()->can('create-balance-sheets')){
            try {
                $validated = $request->validated();
                $balanceSheetId = $this->balanceSheetService->generateBalanceSheet(
                    $validated['balance_sheet_date'],
                    $validated['financial_year']
                );

                $balanceSheet = BalanceSheet::find($balanceSheetId);
                CreateBalanceSheet::dispatch($request, $balanceSheet);

                return redirect()->route('double-entry.balance-sheets.show', $balanceSheetId)
                    ->with('success', __('Balance sheet generated successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function show($id)
    {
        if(Auth::user()->can('view-balance-sheets')){
            $balanceSheet = BalanceSheet::with(['items.account', 'notes'])
                ->where('id', $id)
                ->where('created_by', creatorId())
                ->firstOrFail();

            if(!empty($balanceSheet))
            {
                // Group items by section
                $groupedItems = $balanceSheet->items->groupBy(['section_type', 'sub_section']);

                // Get all balance sheets including current
                $allBalanceSheets = BalanceSheet::where('created_by', creatorId())
                    ->select('id', 'balance_sheet_date', 'financial_year')
                    ->orderBy('balance_sheet_date', 'desc')
                    ->get();

                // Get other balance sheets for comparison (finalized only)
                $otherBalanceSheets = BalanceSheet::where('created_by', creatorId())
                    ->where('id', '!=', $id)
                    ->where('status', 'finalized')
                    ->select('id', 'balance_sheet_date', 'financial_year')
                    ->get();

                return Inertia::render('DoubleEntry/BalanceSheets/View', [
                    'balanceSheet' => $balanceSheet,
                    'groupedItems' => $groupedItems,
                    'allBalanceSheets' => $allBalanceSheets,
                    'otherBalanceSheets' => $otherBalanceSheets
                ]);
            }
            return back()->with('error', __('Balance sheet not found'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function finalize(Request $request, $id)
    {
        if(Auth::user()->can('finalize-balance-sheets')){
            try {
                $balanceSheet = $this->balanceSheetService->finalizeBalanceSheet($id);
                FinalizeBalanceSheet::dispatch($balanceSheet);
                return back()->with('success', __('Balance sheet finalized successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy($id)
    {
        if(Auth::user()->can('delete-balance-sheets')){
            $balanceSheet = BalanceSheet::where('id', $id)
                ->where('created_by', creatorId())
                ->where('status', 'draft')
                ->firstOrFail();

            DestroyBalanceSheet::dispatch($balanceSheet);
            $balanceSheet->delete();
            return back()->with('success', __('Balance sheet deleted successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function addNote(StoreBalanceSheetNoteRequest $request, $id)
    {
        if(Auth::user()->can('create-balance-sheet-notes')){
            $validated = $request->validated();
            $nextNoteNumber = BalanceSheetNote::where('balance_sheet_id', $id)->max('note_number') + 1;

            $note                       = new BalanceSheetNote();
            $note->balance_sheet_id     = $id;
            $note->note_number          = $nextNoteNumber;
            $note->note_title           = $validated['note_title'];
            $note->note_content         = $validated['note_content'];
            $note->creator_id           = Auth::id();
            $note->created_by           = creatorId();
            $note->save();

            CreateBalanceSheetNote::dispatch($request, $note);

            return back()->with('success', __('Note added successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function deleteNote($balanceSheetId, $noteId)
    {
        if(Auth::user()->can('delete-balance-sheet-notes')){
            $note = BalanceSheetNote::where('id', $noteId)
                ->where('balance_sheet_id', $balanceSheetId)
                ->where('created_by', creatorId())
                ->firstOrFail();

            DestroyBalanceSheetNote::dispatch($note);
            $note->delete();
            return back()->with('success', __('Note deleted successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function compare(CompareBalanceSheetRequest $request)
    {
        if(Auth::user()->can('create-balance-sheet-comparisons')){
            $validated = $request->validated();

            $comparison = ComparativeBalanceSheet::create([
                'current_period_id' => $validated['current_period_id'],
                'previous_period_id' => $validated['previous_period_id'],
                'comparison_date' => now(),
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            return redirect()->route('double-entry.balance-sheets.comparison', $comparison->id);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function comparisons()
    {
        if(Auth::user()->can('view-balance-sheet-comparisons')){
            $comparisons = ComparativeBalanceSheet::query()
                ->with([
                    'currentPeriod:id,balance_sheet_date,financial_year',
                    'previousPeriod:id,balance_sheet_date,financial_year'
                ])
                ->where('created_by', creatorId())
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'desc')), fn($q) => $q->orderBy('comparison_date', 'desc'))
                ->paginate(request('per_page', 15))
                ->withQueryString();

            return Inertia::render('DoubleEntry/BalanceSheets/Comparisons', [
                'comparisons' => $comparisons
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function showComparison($id)
    {
        if(Auth::user()->can('view-balance-sheets')){
            $comparison = ComparativeBalanceSheet::with([
                'currentPeriod.items.account',
                'previousPeriod.items.account'
            ])->findOrFail($id);

            return Inertia::render('DoubleEntry/BalanceSheets/Comparison', [
                'comparison' => $comparison
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function yearEndClose(YearEndCloseRequest $request)
    {
        if(Auth::user()->can('year-end-close')){
            try {
                $validated = $request->validated();
                $this->balanceSheetService->performYearEndClose(
                    $validated['financial_year'],
                    $validated['closing_date']
                );

                YearEndClose::dispatch($request, $validated['financial_year'], $validated['closing_date']);

                return back()->with('success', __('Year-end closing completed successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function print($id)
    {
        if(Auth::user()->can('print-balance-sheets')){
            $balanceSheet = BalanceSheet::with(['items.account'])
                ->where('id', $id)
                ->where('created_by', creatorId())
                ->firstOrFail();

            $groupedItems = $balanceSheet->items->groupBy(['section_type', 'sub_section']);

            return Inertia::render('DoubleEntry/BalanceSheets/Print', [
                'balanceSheet' => $balanceSheet,
                'groupedItems' => $groupedItems
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function comparisonPrint(Request $request)
    {
        if(Auth::user()->can('print-balance-sheets')){
            $currentId = $request->get('current_id');
            $previousId = $request->get('previous_id');

            $currentPeriod = BalanceSheet::with(['items.account'])
                ->where('id', $currentId)
                ->where('created_by', creatorId())
                ->firstOrFail();

            $previousPeriod = BalanceSheet::with(['items.account'])
                ->where('id', $previousId)
                ->where('created_by', creatorId())
                ->firstOrFail();

            return Inertia::render('DoubleEntry/BalanceSheets/ComparisonPrint', [
                'comparison' => [
                    'currentPeriod' => $currentPeriod,
                    'previousPeriod' => $previousPeriod
                ]
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
