<?php

namespace Workdo\SupportTicket\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\KnowledgeBase;
use Workdo\SupportTicket\Models\KnowledgeBaseCategory;
use Workdo\SupportTicket\Events\CreateKnowledgeBase;
use Workdo\SupportTicket\Events\DestroyKnowledgeBase;
use Workdo\SupportTicket\Http\Requests\StoreKnowledgeBaseRequest;
use Workdo\SupportTicket\Http\Requests\UpdateKnowledgeBaseRequest;


class KnowledgeController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-knowledge-base')) {
            $knowledge = KnowledgeBase::query()
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-knowledge-base')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-knowledge-base')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('search'), function($q, $search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                })
                ->when(request('category'), function($q, $category) {
                    $q->where('category', $category);
                })
                ->when(request('sort'), function($q, $sort) {
                    $direction = in_array(request('direction'), ['asc', 'desc']) ? request('direction') : 'asc';
                    $allowedSorts = ['title', 'created_at', 'updated_at'];
                    if (in_array($sort, $allowedSorts)) {
                        $q->orderBy($sort, $direction);
                    }
                }, function($q) {
                    $q->latest();
                })
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $categories = KnowledgeBaseCategory::where(function ($q) {
                    if (Auth::user()->can('manage-any-knowledge-base')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-knowledge-base')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })->select('title')->distinct()->get()->map(function($item, $index) {
                    return (object)['id' => $index + 1, 'title' => $item->title];
                });

            return Inertia::render('SupportTicket/Knowledge/Index', [
                'knowledge' => $knowledge,
                'categories' => $categories
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreKnowledgeBaseRequest $request)
    {
        if (Auth::user()->can('create-knowledge-base')) {
            $knowledgeBase = new KnowledgeBase();
            $knowledgeBase->title = $request->title;
            $knowledgeBase->description = $request->description;
            $knowledgeBase->category = $request->category;
            $knowledgeBase->creator_id = Auth::id();
            $knowledgeBase->created_by = creatorId();
            $knowledgeBase->save();

            CreateKnowledgeBase::dispatch($request, $knowledgeBase);

            return redirect()->route('support-ticket-knowledge.index')->with('success', __('The knowledge base has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }
    
    public function update(UpdateKnowledgeBaseRequest $request, KnowledgeBase $supportTicketKnowledge)
    {
        if (Auth::user()->can('edit-knowledge-base')) {
            $supportTicketKnowledge->title = $request->title;
            $supportTicketKnowledge->description = $request->description;
            $supportTicketKnowledge->category = $request->category;
            $supportTicketKnowledge->save();

            return back()->with('success', __('The knowledge base details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(KnowledgeBase $supportTicketKnowledge)
    {
        if (Auth::user()->can('delete-knowledge-base')) {
            DestroyKnowledgeBase::dispatch($supportTicketKnowledge);
            $supportTicketKnowledge->delete();

            return back()->with('success', __('The knowledge base has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function fileImportExport()
    {
        return Inertia::render('SupportTicket/Knowledge/Import');
    }

    public function fileImport(Request $request)
    {
        $html = '';

        if ($request->file && $request->file->getClientOriginalName() != '') {
            $file_array = explode(".", $request->file->getClientOriginalName());
            $extension = end($file_array);
            if ($extension == 'csv') {
                $file_data = fopen($request->file->getRealPath(), 'r');
                $file_header = fgetcsv($file_data);
                $html .= '<table class="table table-bordered"><tr>';
                for ($count = 0; $count < count($file_header); $count++) {
                    $html .= '<th>' . $file_header[$count] . '</th>';
                }
                $html .= '</tr>';
                $limit = 0;
                while (($row = fgetcsv($file_data)) !== FALSE) {
                    $limit++;
                    $html .= '<tr>';
                    for ($count = 0; $count < count($row); $count++) {
                        $html .= '<td>' . $row[$count] . '</td>';
                    }
                    $html .= '</tr>';
                    if ($limit >= 10) {
                        break;
                    }
                }
                $html .= '</table>';
                $array = ['html' => $html, 'status' => true];
            } else {
                $array = ['error' => __('Please select csv file'), 'status' => false];
            }
        } else {
            $array = ['error' => __('Please select file'), 'status' => false];
        }
        return response()->json($array);
    }

    public function knowledgeImportdata(Request $request)
    {
        try {
            $file_data = fopen($request->file->getRealPath(), 'r');
            $file_header = fgetcsv($file_data);
            $imported = 0;
            
            while (($row = fgetcsv($file_data)) !== FALSE) {
                $knowledge = array();
                for ($count = 0; $count < count($file_header); $count++) {
                    $knowledge[$file_header[$count]] = $row[$count];
                }
                
                if (!empty($knowledge['title']) && !empty($knowledge['description'])) {
                    $newKnowledge = new KnowledgeBase();
                    $newKnowledge->title = $knowledge['title'];
                    $newKnowledge->description = $knowledge['description'];
                    $newKnowledge->category = $knowledge['category'] ?? '';
                    $newKnowledge->creator_id = Auth::id();
                    $newKnowledge->created_by = creatorId();
                    $newKnowledge->save();
                    $imported++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => __('Knowledge base imported successfully. :count items imported.', ['count' => $imported])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Import failed: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }
}