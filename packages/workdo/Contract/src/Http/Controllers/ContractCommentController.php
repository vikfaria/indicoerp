<?php

namespace Workdo\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Workdo\Contract\Models\Contract;
use Workdo\Contract\Models\ContractComment;

class ContractCommentController extends Controller
{
    public function store(Request $request, Contract $contract)
    {
        if (Auth::user()->can('create-contract-comments')) {
            $request->validate([
                'comment' => 'required|string|max:1000',
            ]);

            ContractComment::create([
                'contract_id' => $contract->id,
                'comment' => $request->comment,
                'user_id' => Auth::id(),
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),
            ]);

            return back()->with('success', __('The comment has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(Request $request, ContractComment $comment)
    {
        if (Auth::user()->can('edit-contract-comments')) {
            $request->validate([
                'comment' => 'required|string|max:1000',
            ]);

            $comment->update([
                'comment' => $request->comment,
                'is_edited' => true,
            ]);

            return back()->with('success', __('The comment details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(ContractComment $comment)
    {
        if (Auth::user()->can('delete-contract-comments')) {
            $comment->delete();
            return back()->with('success', __('The comment has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}