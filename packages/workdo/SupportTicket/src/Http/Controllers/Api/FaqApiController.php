<?php

namespace Workdo\SupportTicket\Http\Controllers\Api;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Workdo\SupportTicket\Models\Faq;

class FaqApiController extends Controller
{
    use ApiResponseTrait;
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);

            $faqs = Faq::query()
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-faq')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-faq')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $faqs->getCollection()->transform(function ($faq) {
                return [
                    'id'          => $faq->id,
                    'title'       => $faq->title,
                    'description' => $faq->description,
                ];
            });

            return $this->paginatedResponse($faqs, 'FAQ retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
    public function store(Request $request)
    {
        try {

            if (Auth::user()->can('create-faq')) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'title'       => 'required|string|max:500',
                        'description' => 'required|string|max:10000',
                    ]
                );

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $faq              = new Faq();
                $faq->title       = $request->title;
                $faq->description = $request->description;
                $faq->creator_id  = Auth::id();
                $faq->created_by  = creatorId();
                $faq->save();

                return $this->successResponse('', 'FAQ created successfully');
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
            if (Auth::user()->can('edit-faq')) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'title'       => 'required|string|max:255',
                        'description' => 'required|string',
                    ]
                );

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }
                $faq = Faq::find($id);
                if (!$faq) {
                    return $this->errorResponse('FAQ not found', null, 404);
                }
                $faq->title       = $request->title;
                $faq->description = $request->description;
                $faq->save();

                return $this->successResponse('', 'FAQ updated successfully');
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
            if (Auth::user()->can('delete-faq')) {
                $faq = Faq::find($id);

                if (!$faq) {
                    return $this->errorResponse('FAQ not found', null, 404);
                }
                $faq->delete();

                return $this->successResponse('', 'FAQ deleted successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
}
