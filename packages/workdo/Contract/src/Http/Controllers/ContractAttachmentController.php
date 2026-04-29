<?php

namespace Workdo\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Workdo\Contract\Models\Contract;
use Workdo\Contract\Models\ContractAttachment;

class ContractAttachmentController extends Controller
{
    public function store(Request $request, Contract $contract)
    {
        if (Auth::user()->can('create-contract-attachments')) {
            if (!$request->has('media_paths')) {
                return back()->with('error', __('No media_paths provided'));
            }

            $mediaPaths = $request->input('media_paths');
            if (!is_array($mediaPaths) || empty($mediaPaths)) {
                return back()->with('error', __('Invalid media paths'));
            }

            foreach ($mediaPaths as $mediaPath) {
                if (!empty($mediaPath)) {
                    ContractAttachment::create([
                        'contract_id' => $contract->id,
                        'file_name' => basename($mediaPath),
                        'file_path' => basename($mediaPath),
                        'uploaded_by' => Auth::id(),
                        'creator_id' => Auth::id(),
                        'created_by' => creatorId(),
                    ]);
                }
            }

            return back()->with('success', __('The attachment has been uploaded successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(ContractAttachment $attachment)
    {
        if (Auth::user()->can('delete-contract-attachments')) {
            // Delete the physical file if it exists
            if ($attachment->file_path && Storage::exists($attachment->file_path)) {
                Storage::delete($attachment->file_path);
            }

            $attachment->delete();
            return back()->with('success', __('The attachment has been deleted successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
}
