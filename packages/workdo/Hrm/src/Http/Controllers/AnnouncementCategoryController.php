<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\AnnouncementCategory;
use Workdo\Hrm\Http\Requests\StoreAnnouncementCategoryRequest;
use Workdo\Hrm\Http\Requests\UpdateAnnouncementCategoryRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Events\CreateAnnouncementCategory;
use Workdo\Hrm\Events\DestroyAnnouncementCategory;
use Workdo\Hrm\Events\UpdateAnnouncementCategory;


class AnnouncementCategoryController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-announcement-categories')){
            $announcementcategories = AnnouncementCategory::select('id', 'announcement_category', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-announcement-categories')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-announcement-categories')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Hrm/SystemSetup/AnnouncementCategories/Index', [
                'announcementcategories' => $announcementcategories,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreAnnouncementCategoryRequest $request)
    {
        if(Auth::user()->can('create-announcement-categories')){
            $validated = $request->validated();



            $announcementcategory = new AnnouncementCategory();
            $announcementcategory->announcement_category = $validated['announcement_category'];

            $announcementcategory->creator_id = Auth::id();
            $announcementcategory->created_by = creatorId();
            $announcementcategory->save();

            CreateAnnouncementCategory::dispatch($request, $announcementcategory);

            return redirect()->route('hrm.announcement-categories.index')->with('success', __('The announcementcategory has been created successfully.'));
        }
        else{
            return redirect()->route('hrm.announcement-categories.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateAnnouncementCategoryRequest $request, AnnouncementCategory $announcementcategory)
    {
        if(Auth::user()->can('edit-announcement-categories')){
            $validated = $request->validated();



            $announcementcategory->announcement_category = $validated['announcement_category'];

            $announcementcategory->save();

            UpdateAnnouncementCategory::dispatch($request, $announcementcategory);

            return redirect()->route('hrm.announcement-categories.index')->with('success', __('The announcementcategory details are updated successfully.'));
        }
        else{
            return redirect()->route('hrm.announcement-categories.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(AnnouncementCategory $announcementcategory)
    {
        if(Auth::user()->can('delete-announcement-categories')){
            DestroyAnnouncementCategory::dispatch($announcementcategory);
            $announcementcategory->delete();

            return redirect()->route('hrm.announcement-categories.index')->with('success', __('The announcementcategory has been deleted.'));
        }
        else{
            return redirect()->route('hrm.announcement-categories.index')->with('error', __('Permission denied'));
        }
    }


}