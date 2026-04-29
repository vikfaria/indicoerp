<?php

namespace Workdo\Recruitment\Http\Controllers;

use App\Models\EmailTemplate;
use Workdo\Recruitment\Models\Offer;
use Workdo\Recruitment\Http\Requests\StoreOfferRequest;
use Workdo\Recruitment\Http\Requests\UpdateOfferRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Models\Candidate;
use Workdo\Recruitment\Models\JobPosting;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Workdo\Recruitment\Http\Requests\ConvertToEmployeeRequest;
use Illuminate\Support\Facades\Mail;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeDocument;
use Workdo\Hrm\Models\EmployeeDocumentType;
use Workdo\Hrm\Models\Shift;
use Workdo\Recruitment\Models\Job;
use Workdo\Recruitment\Models\OfferLetter;
use Workdo\Recruitment\Events\CreateOffer;
use Workdo\Recruitment\Events\UpdateOffer;
use Workdo\Recruitment\Events\DestroyOffer;
use Workdo\Recruitment\Events\UpdateOfferApprovalStatus;
use Workdo\Recruitment\Events\ConvertOfferToEmployee;

class OfferController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-offers')) {
            $offers = Offer::query()
                ->with(['candidate', 'job', 'approvedBy', 'department'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-offers')) {
                        $q->where('offers.created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-offers')) {
                        $q->where('offers.creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('position'), function ($q) {
                    $searchTerm = request('position');
                    $q->where('offers.position', 'like', '%' . $searchTerm . '%');
                })
                ->when(request('candidate_id') && request('candidate_id') !== 'all', fn($q) => $q->where('offers.candidate_id', request('candidate_id')))
                ->when(request('start_date'), fn($q) => $q->where('offers.start_date', '>=', request('start_date')))
                ->when(request('expiration_date'), fn($q) => $q->where('offers.expiration_date', '<=', request('expiration_date')))
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('offers.status', request('status')))
                ->when(request('approval_status') && request('approval_status') !== 'all', fn($q) => $q->where('offers.approval_status', request('approval_status')))
                ->when(request('offer_date'), fn($q) => $q->whereDate('offers.offer_date', request('offer_date')))
                ->when(request('sort'), function ($q) {
                    $sort = request('sort');
                    $direction = request('direction', 'asc');

                    if ($sort === 'candidate_id') {
                        $q->join('candidates', 'offers.candidate_id', '=', 'candidates.id')
                            ->orderBy('candidates.first_name', $direction)
                            ->orderBy('candidates.last_name', $direction)
                            ->select('offers.*');
                    } else {
                        $q->orderBy($sort, $direction);
                    }
                }, fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            // Add download URLs to offers
            $offers->getCollection()->transform(function ($offer) {
                $offer->download_url = route('recruitment.offers.download', Crypt::encrypt($offer->id));
                return $offer;
            });

            return Inertia::render('Recruitment/Offers/Index', [
                'offers' => $offers,
                'candidates' => Candidate::where('created_by', creatorId())
                    ->where(function ($query) {
                        $query->whereHas('candidateAssessments', function ($q) {
                            $q->where('pass_fail_status', '0'); // Pass status
                        })->orWhereHas('interviewFeedbacks', function ($q) {
                            $q->whereIn('recommendation', [0, 1]); // Strong Hire & Hire
                        });
                    })
                    ->select('id', 'first_name', 'last_name')
                    ->get()
                    ->map(function ($candidate) {
                        return [
                            'id' => $candidate->id,
                            'name' => $candidate->first_name . ' ' . $candidate->last_name
                        ];
                    }),
                'jobpostings' => JobPosting::where('created_by', creatorId())->where('is_published', 1)->where('status', 'active')->select('id', 'title')->get()->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'name' => $job->title
                    ];
                }),
                'users' => User::emp()->where('created_by', creatorId())->select('id', 'name')->get(),
                'departments' => Department::where('created_by', creatorId())->select('id', 'department_name as name')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if (Auth::user()->can('create-offers')) {
            return Inertia::render('Recruitment/Offers/Create', [
                'candidates' => Candidate::where('created_by', creatorId())
                    ->where(function ($query) {
                        $query->whereHas('candidateAssessments', function ($q) {
                            $q->where('pass_fail_status', '0'); // Pass status
                        })->orWhereHas('interviewFeedbacks', function ($q) {
                            $q->whereIn('recommendation', [0, 1]); // Strong Hire & Hire
                        });
                    })
                    ->whereNotIn('id', function ($query) {
                        $query->select('candidate_id')
                            ->from('offers')
                            ->where('converted_to_employee', true);
                    })
                    ->select('id', 'first_name', 'last_name')
                    ->get()
                    ->map(function ($candidate) {
                        return [
                            'id' => $candidate->id,
                            'name' => $candidate->first_name . ' ' . $candidate->last_name
                        ];
                    }),
                'users' => User::emp()->where('created_by', creatorId())->select('id', 'name')->get(),
                'departments' => Department::where('created_by', creatorId())->select('id', 'department_name as name')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function edit(Offer $offer)
    {
        if (Auth::user()->can('edit-offers')) {
            return Inertia::render('Recruitment/Offers/Edit', [
                'offer' => $offer->load(['candidate', 'job', 'approvedBy']),
                'candidates' => Candidate::where('created_by', creatorId())
                    ->where(function ($query) use ($offer) {
                        $query->whereHas('candidateAssessments', function ($q) {
                            $q->where('pass_fail_status', '0'); // Pass status
                        })->orWhereHas('interviewFeedbacks', function ($q) {
                            $q->whereIn('recommendation', [0, 1]); // Strong Hire & Hire
                        });
                    })
                    ->where(function ($query) use ($offer) {
                        $query->whereNotIn('id', function ($subQuery) {
                            $subQuery->select('candidate_id')
                                ->from('offers')
                                ->where('converted_to_employee', true);
                        })->orWhere('id', $offer->candidate_id);
                    })
                    ->select('id', 'first_name', 'last_name')
                    ->get()
                    ->map(function ($candidate) {
                        return [
                            'id' => $candidate->id,
                            'name' => $candidate->first_name . ' ' . $candidate->last_name
                        ];
                    }),
                'users' => User::emp()->where('created_by', creatorId())->select('id', 'name')->get(),
                'departments' => Department::where('created_by', creatorId())->select('id', 'department_name as name')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreOfferRequest $request)
    {
        if (Auth::user()->can('create-offers')) {
            $validated = $request->validated();

            // Get job_id from candidate's job application
            $candidate = Candidate::find($validated['candidate_id']);

            $offer = new Offer();
            $offer->candidate_id = $validated['candidate_id'];
            $offer->job_id = $candidate->job_id;
            $offer->offer_date = $validated['offer_date'];
            $offer->position = $validated['position'];
            $offer->department_id = $validated['department_id'];
            $offer->salary = $validated['salary'];
            $offer->bonus = $validated['bonus'];
            $offer->equity = $validated['equity'];
            $offer->benefits = $validated['benefits'];
            $offer->start_date = $validated['start_date'];
            $offer->expiration_date = $validated['expiration_date'];
            $offer->offer_letter_path = $validated['offer_letter_path'];
            $offer->status = $validated['status'];
            $offer->response_date = $validated['response_date'];
            $offer->decline_reason = $validated['decline_reason'];

            $offer->creator_id = Auth::id();
            $offer->created_by = creatorId();
            $offer->save();

            CreateOffer::dispatch($request, $offer);

            return redirect()->route('recruitment.offers.index')->with('success', __('The offer has been created successfully.'));
        } else {
            return redirect()->route('recruitment.offers.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateOfferRequest $request, Offer $offer)
    {
        if (Auth::user()->can('edit-offers')) {
            $validated = $request->validated();

            // Get job_id from candidate's job application
            $candidate = Candidate::find($validated['candidate_id']);

            $offer->candidate_id = $validated['candidate_id'];
            $offer->job_id = $candidate->job_id;
            $offer->offer_date = $validated['offer_date'] ?? $offer->offer_date;
            $offer->position = $validated['position'];
            $offer->department_id = $validated['department_id'];
            $offer->salary = $validated['salary'];
            $offer->bonus = $validated['bonus'];
            $offer->equity = $validated['equity'];
            $offer->benefits = $validated['benefits'];
            $offer->start_date = $validated['start_date'];
            $offer->expiration_date = $validated['expiration_date'];
            $offer->offer_letter_path = $validated['offer_letter_path'] ?? $offer->offer_letter_path;
            $offer->status = $validated['status'] ?? $offer->status;
            $offer->response_date = $validated['response_date'] ?? $offer->response_date;
            $offer->decline_reason = $validated['decline_reason'] ?? $offer->decline_reason;

            $offer->save();

            UpdateOffer::dispatch($request, $offer);

            return redirect()->back()->with('success', __('The offer details are updated successfully.'));
        } else {
            return redirect()->route('recruitment.offers.index')->with('error', __('Permission denied'));
        }
    }

    public function updateApprovalStatus(Request $request, Offer $offer)
    {
        if (Auth::user()->can('approve-offers')) {
            $action = $request->input('action');

            if ($action === 'approve') {
                $offer->approved_by = Auth::id();
                $offer->approval_status = 'approved';
                $message = __('The offer has been approved successfully.');
            } else {
                $offer->approved_by = null;
                $offer->approval_status = 'rejected';
                $message = __('The offer has been rejected successfully.');
            }

            $offer->save();

            UpdateOfferApprovalStatus::dispatch($request, $offer);

            return redirect()->back()->with('success', $message);
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Offer $offer)
    {
        if (Auth::user()->can('delete-offers')) {
            DestroyOffer::dispatch($offer);

            $offer->delete();

            return redirect()->back()->with('success', __('The offer has been deleted.'));
        } else {
            return redirect()->route('recruitment.offers.index')->with('error', __('Permission denied'));
        }
    }

    public function downloadOfferLetter($encryptedId)
    {
        if (Auth::user()->can('download-offer-letters')) {
            try {
                $offerId = Crypt::decrypt($encryptedId);
                $offer = Offer::with(['candidate', 'job', 'department', 'approvedBy'])->findOrFail($offerId);

                $companySettings = getCompanyAllSetting();
                $companyName = $companySettings['company_name'] ?? config('app.name');

                // Get company default language
                $defaultLanguage = company_setting('defaultLanguage', creatorId()) ?? 'en';

                // Get offer letter template in company's default language
                $offerLetter = OfferLetter::where('lang', $defaultLanguage)
                    ->where('created_by', creatorId())
                    ->first();

                // Fallback to English if default language template not found
                if (!$offerLetter) {
                    $offerLetter = OfferLetter::where('lang', 'en')
                        ->where('created_by', creatorId())
                        ->first();
                }

                $templateContent = '';
                if ($offerLetter) {
                    // Prepare data for variable replacement
                    $workplaceLocation = 'Office';
                    if ($offer->job && $offer->job->location) {
                        $location = is_string($offer->job->location) ? json_decode($offer->job->location, true) : $offer->job->location;
                        if (is_array($location) && isset($location['address'])) {
                            $workplaceLocation = $location['address'] . ', ' . ($location['city'] ?? '') . ', ' . ($location['state'] ?? '');
                        } else {
                            $workplaceLocation = is_string($offer->job->location) ? $offer->job->location : 'Office';
                        }
                    }

                    $offerData = [
                        'applicant_name' => $offer->candidate->first_name . ' ' . $offer->candidate->last_name,
                        'company_name' => $companyName,
                        'job_title' => $offer->position ?? 'N/A',
                        'job_type' => $offer->job->job_type ?? 'Full-time',
                        'start_date' => $offer->start_date ?? 'TBD',
                        'workplace_location' => $workplaceLocation,
                        'days_of_week' => 'Monday to Friday',
                        'salary' => $offer->salary ? '$' . number_format($offer->salary) : 'N/A',
                        'salary_type' => 'year',
                        'salary_duration' => 'monthly',
                        'next_pay_period' => 'monthly',
                        'offer_expiration_date' => $offer->expiration_date ?? 'N/A',
                    ];

                    $templateContent = OfferLetter::replaceVariable($offerLetter->content, $offerData);
                }

                return Inertia::render('Recruitment/Offers/OfferLetterPdf', [
                    'offer' => $offer,
                    'companyName' => $companyName,
                    'companySettings' => $companySettings,
                    'templateContent' => $templateContent
                ]);
            } catch (\Exception $e) {
                return response('Invalid offer letter link.', 404);
            }
        } else {
            return response('Permission denied.', 403);
        }
    }

    public function sendEmail(Request $request, Offer $offer)
    {
        if (Auth::user()->can('send-offer-emails')) {
            try {
                $candidate = $offer->candidate;
                if (!$candidate || !$candidate->email) {
                    return redirect()->back()->with('error', __('Candidate email not found'));
                }

                $emailData = [
                    'candidate_name' => $candidate->first_name . ' ' . $candidate->last_name,
                    'position' => $offer->position,
                    'salary' => $offer->salary ? '$' . number_format($offer->salary) : 'To be discussed',
                    'start_date' => $offer->start_date,
                    'company_name' => getCompanyAllSetting()['company_name'] ?? config('app.name'),
                    'download_url' => route('recruitment.offers.public-download', Crypt::encrypt($offer->id)),
                ];

                $result = EmailTemplate::sendEmailTemplate('Offer Letter', [$candidate->email], $emailData, $offer->created_by);

                if ($result['is_success']) {
                    return redirect()->back()->with('success', __('Email sent successfully'));
                } else {
                    return redirect()->back()->with('error', __($result['error']));
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to send email'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function showConvertToEmployeeForm($id)
    {
        if (Auth::user()->can('convert-offers-to-employees')) {
            $offer = Offer::with('candidate')->findOrFail($id);

            if ($offer->converted_to_employee) {
                return redirect()->back()->with('error', __('Offer already converted to employee'));
            }

            return Inertia::render('Recruitment/Offers/ConvertToEmployee', [
                'offer' => $offer,
                'branches' => Branch::where('created_by', creatorId())->select('id', 'branch_name')->get(),
                'departments' => Department::where('created_by', creatorId())->select('id', 'department_name', 'branch_id')->get(),
                'designations' => Designation::where('created_by', creatorId())->select('id', 'designation_name', 'branch_id', 'department_id')->get(),
                'shifts' => Shift::where('created_by', creatorId())->select('id', 'shift_name')->get(),
                'documentTypes' => EmployeeDocumentType::where('created_by', creatorId())->select('id', 'document_name', 'is_required')->get(),
                'generatedEmployeeId' => Employee::generateEmployeeId(),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function convertToEmployee(ConvertToEmployeeRequest $request, $id)
    {
        if (Auth::user()->can('convert-offers-to-employees')) {
            try {
                $offer = Offer::with('candidate')->findOrFail($id);

                if ($offer->converted_to_employee) {
                    return redirect()->back()->with('error', __('Offer already converted to employee'));
                }

                // Convert gender from numeric to text
                $genderMap = [
                    '0' => 'Male',
                    '1' => 'Female',
                    '2' => 'Other'
                ];
                $genderText = $genderMap[$request->gender] ?? 'Male';

                // Create user account
                $user = new User();
                $user->name = $request->candidate_name;
                $user->email = $request->candidate_email;
                $user->mobile_no = $request->phone_number;
                $user->password = bcrypt($request->password);
                $user->type = 'staff';
                $user->is_enable_login = true;
                $user->lang = company_setting('defaultLanguage') ?? 'en';
                $user->creator_id = Auth::id();
                $user->created_by = creatorId();
                $user->email_verified_at = now();
                $user->save();

                $user->assignRole('staff');

                // Create employee record
                $employee = new Employee();
                $employee->employee_id = $request->employee_id;
                $employee->date_of_birth = $request->date_of_birth;
                $employee->gender = $genderText;
                $employee->shift = $request->shift_id;
                $employee->date_of_joining = $request->date_of_joining;
                $employee->employment_type = $request->employment_type;
                $employee->address_line_1 = $request->address_line_1;
                $employee->address_line_2 = $request->address_line_2;
                $employee->city = $request->city;
                $employee->state = $request->state;
                $employee->country = $request->country;
                $employee->postal_code = $request->postal_code;
                $employee->emergency_contact_name = $request->emergency_contact_name;
                $employee->emergency_contact_relationship = $request->emergency_contact_relationship;
                $employee->emergency_contact_number = $request->emergency_contact_number;
                $employee->bank_name = $request->bank_name;
                $employee->account_holder_name = $request->account_holder_name;
                $employee->account_number = $request->account_number;
                $employee->bank_identifier_code = $request->bank_identifier_code;
                $employee->bank_branch = $request->bank_branch;
                $employee->tax_payer_id = $request->tax_payer_id;
                $employee->user_id = $user->id;
                $employee->branch_id = $request->branch_id;
                $employee->department_id = $request->department_id;
                $employee->designation_id = $request->designation_id;
                $employee->basic_salary = $request->basic_salary;
                $employee->hours_per_day = $request->hours_per_day;
                $employee->days_per_week = $request->days_per_week;
                $employee->rate_per_hour = $request->rate_per_hour;
                $employee->creator_id = Auth::id();
                $employee->created_by = creatorId();

                $employee->save();

                // Store documents
                if ($request->has('documents')) {
                    foreach ($request->input('documents', []) as $index => $document) {
                        if ($request->hasFile("documents.{$index}.file") && !empty($document['document_type_id'])) {
                            $file = $request->file("documents.{$index}.file");

                            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                            $extension = $file->getClientOriginalExtension();
                            $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                            $upload = upload_file($request, "documents.{$index}.file", $fileNameToStore, 'employee_documents');

                            if (isset($upload['flag']) && $upload['flag'] == 1 && isset($upload['url'])) {
                                EmployeeDocument::create([
                                    'user_id' => $employee->id,
                                    'document_type_id' => $document['document_type_id'],
                                    'file_path' => $upload['url'],
                                    'creator_id' => Auth::id(),
                                    'created_by' => creatorId(),
                                ]);
                            }
                        }
                    }
                }

                // Update offer status
                $offer->update([
                    'converted_to_employee' => true,
                    'employee_id' => $employee->id
                ]);

                // Update candidate status from Offer (3) to Hired (4)
                $candidate = $offer->candidate;
                if ($candidate && $candidate->status === '3') {
                    $candidate->status = '4';
                    $candidate->save();
                }

                ConvertOfferToEmployee::dispatch($request, $offer, $employee);

                return redirect()->route('recruitment.offers.index')->with('success', __('Offer converted to employee successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['error' => __('Failed to convert offer to employee: ' . $e->getMessage())]);
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function getJobsByCandidate($candidateId)
    {
        if (Auth::user()->can('manage-job-postings') || Auth::user()->can('create-offers') || Auth::user()->can('edit-offers')) {
            $jobs = Job::where('candidate_id', $candidateId)
                ->where('created_by', creatorId())
                ->select('id', 'name')
                ->get();

            return response()->json($jobs);
        } else {
            return response()->json([], 403);
        }
    }
}
