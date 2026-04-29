<?php

namespace Workdo\SupportTicket\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\Faq;
use Workdo\SupportTicket\Http\Requests\StoreFaqRequest;
use Workdo\SupportTicket\Http\Requests\UpdateFaqRequest;


class FaqController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-faq')) {
            $faqs = Faq::where(function ($q) {
                    if (Auth::user()->can('manage-any-faq')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-faq')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                       $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('search'), function($q, $search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
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

            return Inertia::render('SupportTicket/FAQ/Index', [
                'faqs' => $faqs
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreFaqRequest $request)
    {
        if (Auth::user()->can('create-faq')) {
            $faq = new Faq();
            $faq->title = $request->title;
            $faq->description = $request->description;
            $faq->creator_id = Auth::id();
            $faq->created_by = creatorId();
            $faq->save();

            return redirect()->route('support-ticket-faq.index')->with('success', __('The FAQ has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateFaqRequest $request, Faq $supportTicketFaq)
    {
        if (Auth::user()->can('edit-faq')) {
            $supportTicketFaq->title = $request->title;
            $supportTicketFaq->description = $request->description;
            $supportTicketFaq->save();

            return back()->with('success', __('The FAQ details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Faq $supportTicketFaq)
    {
        if (Auth::user()->can('delete-faq')) {
            $supportTicketFaq->delete();

            return back()->with('success', __('The FAQ has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function fileImportExport()
    {
        return Inertia::render('SupportTicket/FAQ/Import');
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

    public function faqImportdata(Request $request)
    {
        try {
            $file_data = fopen($request->file->getRealPath(), 'r');
            $file_header = fgetcsv($file_data);
            $imported = 0;
            
            while (($row = fgetcsv($file_data)) !== FALSE) {
                $faq = array();
                for ($count = 0; $count < count($file_header); $count++) {
                    $faq[$file_header[$count]] = $row[$count];
                }
                
                if (!empty($faq['title']) && !empty($faq['description'])) {
                    $newFaq = new Faq();
                    $newFaq->title = $faq['title'];
                    $newFaq->description = $faq['description'];
                    $newFaq->creator_id = Auth::id();
                    $newFaq->created_by = creatorId();
                    $newFaq->save();
                    $imported++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => __('FAQ imported successfully. :count items imported.', ['count' => $imported])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Import failed: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }
}