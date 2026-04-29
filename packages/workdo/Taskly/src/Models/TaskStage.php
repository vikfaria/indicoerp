<?php

namespace Workdo\Taskly\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'complete',
        'order',
        'creator_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'complete' => 'boolean',
        ];
    }

    protected $casts = [
        //
    ];

    public static function defaultdata($company_id = null)
    {
        if (!empty($company_id)) {
            $existingStages = TaskStage::where('created_by', $company_id)->count();
                if ($existingStages == 0) {
                    $defaultStages = [
                        '#77b6ea' => 'Todo',
                        '#545454' => 'In Progress',
                        '#3cb8d9' => 'Review',
                        '#37b37e' => 'Done',
                    ];
                $key = 0;
                $lastKey       = count($defaultStages) - 1;
                foreach($defaultStages as $color => $stage)
                {
                    TaskStage::create([
                        'name' => $stage,
                        'color' => $color,
                        'complete' => ($key == $lastKey) ? true : false,
                        'order' => $key,
                        'creator_id' => $company_id,
                        'created_by' => $company_id,
                    ]);
                    $key++;
                }
            }
        }
    }
}
