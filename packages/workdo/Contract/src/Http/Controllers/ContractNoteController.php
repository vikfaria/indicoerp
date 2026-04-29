<?php

namespace Workdo\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Workdo\Contract\Models\Contract;
use Workdo\Contract\Models\ContractNote;

class ContractNoteController extends Controller
{
    public function store(Request $request, Contract $contract)
    {
        if (Auth::user()->can('create-contract-notes')) {
            $request->validate([
                'note' => 'required|string|max:1000',
            ]);

            ContractNote::create([
                'contract_id' => $contract->id,
                'note' => $request->note,
                'user_id' => Auth::id(),
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),

            ]);

            return back()->with('success', __('The note has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(Request $request, ContractNote $note)
    {
        if (Auth::user()->can('edit-contract-notes')) {
            $request->validate([
                'note' => 'required|string|max:1000',
            ]);

            $note->update([
                'note' => $request->note,
                'is_edited' => true,
            ]);

            return back()->with('success', __('The note details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(ContractNote $note)
    {
        if (Auth::user()->can('delete-contract-notes')) {
            $note->delete();
            return back()->with('success', __('The note has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}
