<?php

namespace Workdo\SupportTicket\Http\Controllers\Api;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Workdo\SupportTicket\Models\KnowledgeBase;

class KnowledgeApiController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);

            $KnowledgeBases = KnowledgeBase::query()
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-knowledge-base')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-knowledge-base')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $KnowledgeBases->getCollection()->transform(function ($KnowledgeBase) {
                return [
                    'id'          => $KnowledgeBase->id,
                    'title'       => $KnowledgeBase->title,
                    'description' => $KnowledgeBase->description,
                    'category'    => $KnowledgeBase->category ?? 'No Category',
                ];
            });

            return $this->paginatedResponse($KnowledgeBases, 'Knowledge Bases retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function store(Request $request)
    {
        try {
            if (Auth::user()->can('create-knowledge-base')) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'title'       => 'required|string|max:255',
                        'category'    => 'required|string',
                        'description' => 'required|string|max:500',
                        'content'     => 'nullable|string',
                    ]
                );

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }
                $knowledgeBase              = new KnowledgeBase();
                $knowledgeBase->title       = $request->title;
                $knowledgeBase->description = $request->description;
                $knowledgeBase->category    = $request->category;
                $knowledgeBase->creator_id  = Auth::id();
                $knowledgeBase->created_by  = creatorId();
                $knowledgeBase->save();

                return $this->successResponse('', 'Knowledge base created successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function update(Request $request, $id)
    {
        try {

            if (Auth::user()->can('edit-knowledge-base')) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'title'       => 'required|string|max:255',
                        'description' => 'required|string',
                        'category'    => 'required|string',
                        'content'     => 'nullable|string',
                    ]
                );

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }
                $supportTicketKnowledge = KnowledgeBase::find($id);
                if (!$supportTicketKnowledge) {
                    return $this->errorResponse('Knowledge base not found', null, 404);
                }

                $supportTicketKnowledge->title       = $request->title;
                $supportTicketKnowledge->description = $request->description;
                $supportTicketKnowledge->category    = $request->category;
                $supportTicketKnowledge->save();

                return $this->successResponse('', 'Knowledge base updated successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function destroy($id)
    {
        try {
            if (Auth::user()->can('delete-knowledge-base')) {

                $supportTicketKnowledge = KnowledgeBase::find($id);

                if (!$supportTicketKnowledge) {
                    return $this->errorResponse('Knowledge base not found', null, 404);
                }
                $supportTicketKnowledge->delete();

                return $this->successResponse(null, 'Ticket deleted successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
}
