<?php

namespace Workdo\Performance\Http\Controllers;

use Illuminate\Routing\Controller;
use Workdo\Performance\Models\PerformanceEmployeeReview;
use Workdo\Performance\Models\PerformanceReviewCycle;

use Workdo\Performance\Http\Requests\StoreEmployeeReviewRequest;
use Workdo\Performance\Http\Requests\UpdateEmployeeReviewRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Performance\Models\PerformanceIndicator;
use Workdo\Performance\Events\CreateEmployeeReview;
use Workdo\Performance\Events\UpdateEmployeeReview;
use Workdo\Performance\Events\DestroyEmployeeReview;

class EmployeeReviewController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-employee-reviews')) {
            $reviews = PerformanceEmployeeReview::with(['user', 'reviewer', 'reviewCycle'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-employee-reviews')) {
                        $q->where('performance_employee_reviews.created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-employee-reviews')) {
                        $q->where(function ($query) {
                            $query->where('performance_employee_reviews.creator_id', Auth::id())
                                ->orWhere('performance_employee_reviews.reviewer_id', Auth::id())
                                ->orWhere('performance_employee_reviews.user_id', Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('employee_name'), function($q) {
                    $q->whereHas('user', function($query) {
                        $query->where('name', 'like', '%' . request('employee_name') . '%');
                    });
                })
                ->when(request('reviewer_name'), function($q) {
                    $q->whereHas('reviewer', function($query) {
                        $query->where('name', 'like', '%' . request('reviewer_name') . '%');
                    });
                })
                ->when(request('review_cycle_id'), fn($q) => $q->where('performance_employee_reviews.review_cycle_id', request('review_cycle_id')))
                ->when(request('status'), fn($q) => $q->where('performance_employee_reviews.status', request('status')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $users = $this->getFilteredUsers();
            $reviewCycles = $this->getFilteredReviewCycles();

            return Inertia::render('Performance/EmployeeReviews/Index', [
                'employeeReviews' => $reviews,
                'employees' => $users,
                'reviewers' => $users,
                'reviewCycles' => $reviewCycles,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }



    public function store(StoreEmployeeReviewRequest $request)
    {
        if (Auth::user()->can('create-employee-reviews')) {
            $validated = $request->validated();
            $employeeReview = new PerformanceEmployeeReview();
            $employeeReview->user_id = $validated['user_id'];
            $employeeReview->reviewer_id = $validated['reviewer_id'];
            $employeeReview->review_cycle_id = $validated['review_cycle_id'];
            $employeeReview->review_date = $validated['review_date'];
            $employeeReview->status = $validated['status'];
            $employeeReview->creator_id = Auth::id();
            $employeeReview->created_by = creatorId();
            $employeeReview->save();

            CreateEmployeeReview::dispatch($request, $employeeReview);

            return back()->with('success', __('The employee review has been created successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }



    public function update(UpdateEmployeeReviewRequest $request, PerformanceEmployeeReview $employeeReview)
    {
        if (Auth::user()->can('edit-employee-reviews')) {
            if (!$this->canAccessReview($employeeReview)) {
                return back()->with('error', __('Permission denied'));
            }

            $validated = $request->validated();

            $employeeReview->user_id = $validated['user_id'];
            $employeeReview->reviewer_id = $validated['reviewer_id'];
            $employeeReview->review_cycle_id = $validated['review_cycle_id'];
            $employeeReview->review_date = $validated['review_date'];
            $employeeReview->status = $validated['status'];
            $employeeReview->save();

            UpdateEmployeeReview::dispatch($request, $employeeReview);

            return back()->with('success', __('The employee review details are updated successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function show(PerformanceEmployeeReview $employeeReview)
    {
        if (Auth::user()->can('view-employee-reviews')) {
            if (!$this->canAccessReview($employeeReview)) {
                return back()->with('error', __('Permission denied'));
            }

            $averageRating = null;
            
            // Get existing ratings
            $ratings = [];
            if ($employeeReview->rating) {
                $ratings = json_decode($employeeReview->rating, true) ?: [];
            }
            
            // Get all performance indicators and map ratings to them (only from active categories)
            $performanceIndicators = PerformanceIndicator::with('category')
                ->where('created_by', creatorId())
                ->where('status', 'active')
                ->whereHas('category', function($q) {
                    $q->where('status', 'active');
                })
                ->get()
                ->groupBy('category.name')
                ->map(function ($indicators) use ($ratings) {
                    return $indicators->map(function ($indicator) use ($ratings) {
                        $indicator->user_rating = $ratings[$indicator->id] ?? 0;
                        return $indicator;
                    });
                });
            
            // Calculate average rating only for rated indicators (rating > 0)
            if (!empty($ratings)) {
                $ratedValues = array_filter($ratings, function($rating) {
                    return $rating > 0;
                });
                if (!empty($ratedValues)) {
                    $averageRating = round(array_sum($ratedValues) / count($ratedValues), 1);
                }
            }

            $data = [
                'employeeReview' => $employeeReview->load(['user', 'reviewer', 'reviewCycle']),
                'performanceIndicators' => $performanceIndicators,
                'averageRating' => $averageRating,
            ];

            // Return JSON for AJAX requests, Inertia for regular requests
            if (request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['props' => $data]);
            }

            return Inertia::render('Performance/EmployeeReviews/Show', $data);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(PerformanceEmployeeReview $employeeReview)
    {
        if (Auth::user()->can('delete-employee-reviews')) {
            if (!$this->canAccessReview($employeeReview)) {
                return back()->with('error', __('Permission denied'));
            }

            DestroyEmployeeReview::dispatch($employeeReview);

            $employeeReview->delete();

            return back()->with('success', __('The employee review has been deleted.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function conduct(PerformanceEmployeeReview $employeeReview)
    {
        if (Auth::user()->can('conduct-employee-reviews')) {
            if (!$this->canAccessReview($employeeReview)) {
                return redirect()->route('performance.employee-reviews.index')->with('error', __('Permission denied'));
            }

            // Get performance indicators grouped by category (only from active categories)
            $performanceIndicators = PerformanceIndicator::with('category')
                ->where('created_by', creatorId())
                ->where('status', 'active')
                ->whereHas('category', function($q) {
                    $q->where('status', 'active');
                })
                ->get()
                ->groupBy('category.name');

            // Get existing ratings if any
            $existingRatings = [];
            if ($employeeReview->rating) {
                $existingRatings = json_decode($employeeReview->rating, true) ?: [];
            }

            return Inertia::render('Performance/EmployeeReviews/Conduct', [
                'employeeReview' => $employeeReview->load(['user', 'reviewer', 'reviewCycle']),
                'performanceIndicators' => $performanceIndicators,
                'existingRatings' => $existingRatings,
            ]);
        } else {
            return redirect()->route('performance.employee-reviews.index')->with('error', __('Permission denied'));
        }
    }

    public function conductStore(Request $request, PerformanceEmployeeReview $employeeReview)
    {
        if (Auth::user()->can('conduct-employee-reviews')) {
            if (!$this->canAccessReview($employeeReview)) {
                return back()->with('error', __('Permission denied'));
            }

            $validated = $request->validate([
                'ratings' => 'required|array',
                'ratings.*' => 'integer|min:1|max:5',
                'pros' => 'nullable|string|max:65535',
                'cons' => 'nullable|string|max:65535',
            ]);

            $employeeReview->rating = json_encode($validated['ratings']);
            $employeeReview->pros = $validated['pros'] ?? null;
            $employeeReview->cons = $validated['cons'] ?? null;
            $employeeReview->status = 'completed';
            $employeeReview->completion_date = now();
            $employeeReview->save();

            return redirect()->route('performance.employee-reviews.index')
                ->with('success', __('Performance review has been completed successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    private function canAccessReview(PerformanceEmployeeReview $review)
    {
        if (Auth::user()->can('manage-any-employee-reviews')) {
            return $review->created_by == creatorId();
        } elseif (Auth::user()->can('manage-own-employee-reviews')) {
            return $review->creator_id == Auth::id() 
                || $review->reviewer_id == Auth::id() 
                || $review->user_id == Auth::id();
        } else {
            return false;
        }
    }

    private function getFilteredUsers()
    {
        return User::emp()->where('created_by', creatorId())
            ->when(!Auth::user()->can('manage-any-users'), function ($q) {
                if (Auth::user()->can('manage-own-users')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->select('id', 'name')->get();
    }

    private function getFilteredReviewCycles()
    {
        return PerformanceReviewCycle::where('created_by', creatorId())
            ->where('status', 'active')
            ->when(!Auth::user()->can('manage-any-review-cycles'), function ($q) {
                if (Auth::user()->can('manage-own-review-cycles')) {
                    $q->where('creator_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->select('id', 'name')->get();
    }
}