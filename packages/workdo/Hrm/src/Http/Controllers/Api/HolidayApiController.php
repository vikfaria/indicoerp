<?php

namespace Workdo\Hrm\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Workdo\Hrm\Models\Holiday;

class HolidayApiController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        try {
            if (Auth::user()->can('manage-holidays')) {
                $holidays = Holiday::with('holidayType')
                    ->select('name', 'start_date', 'end_date')
                    ->where(function ($q) {
                        if (Auth::user()->can('manage-any-holidays')) {
                            $q->where('created_by', creatorId());
                        } elseif (Auth::user()->can('manage-own-holidays')) {
                            $q->where('creator_id', Auth::id());
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    })
                    ->latest()
                    ->paginate(request('per_page', 10))
                    ->withQueryString();

                $holidays->getCollection()->transform(function ($holiday) {
                    return [
                        'name' => $holiday->name,
                        'start_date' => $holiday->start_date->format('Y-m-d'),
                        'end_date' => $holiday->end_date->format('Y-m-d')
                    ];
                });

                return $this->paginatedResponse($holidays, 'Holidays retrieved successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
}
