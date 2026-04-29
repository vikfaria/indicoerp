<?php

namespace Workdo\Hrm\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class HrmDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(PermissionTableSeeder::class);
        $this->call(MarketplaceSettingSeeder::class);

        if (config('app.run_demo_seeder')) {
            // Add here your demo data seeders
            $userId = User::where('email', 'company@example.com')->first()->id;
            (new DemoBranchSeeder())->run($userId);
            (new DemoDepartmentSeeder())->run($userId);
            (new DemoDesignationSeeder())->run($userId);
            (new DemoDesignationSeeder())->run($userId);
            (new DemoEmployeeDocumentTypeSeeder())->run($userId);
            (new DemoShiftSeeder())->run($userId);
            (new DemoEmployeeSeeder())->run($userId);
            (new DemoAwardTypeSeeder())->run($userId);
            (new DemoAwardSeeder())->run($userId);
            (new DemoPromotionSeeder())->run($userId);
            (new DemoResignationSeeder())->run($userId);
            (new DemoTerminationTypeSeeder())->run($userId);
            (new DemoTerminationSeeder())->run($userId);
            (new DemoWarningTypeSeeder())->run($userId);
            (new DemoWarningSeeder())->run($userId);
            (new DemoComplaintTypeSeeder())->run($userId);
            (new DemoComplaintSeeder())->run($userId);
            (new DemoEmployeeTransferSeeder())->run($userId);
            (new DemoEmployeeTransferSeeder())->run($userId);
            (new DemoHolidayTypeSeeder())->run($userId);
            (new DemoHolidaySeeder())->run($userId);
            (new DemoDocumentCategorySeeder())->run($userId);
            (new DemoHrmDocumentSeeder())->run($userId);
            (new DemoAcknowledgmentSeeder())->run($userId);
            (new DemoAnnouncementCategorySeeder())->run($userId);
            (new DemoAnnouncementSeeder())->run($userId);
            (new DemoEventTypeSeeder())->run($userId);
            (new DemoEventSeeder())->run($userId);
            (new DemoLeaveTypeSeeder())->run($userId);
            (new DemoLeaveApplicationSeeder())->run($userId);
            (new DemoAttendanceSeeder())->run($userId);
            (new DemoAllowanceTypeSeeder())->run($userId);
            (new DemoDeductionTypeSeeder())->run($userId);
            (new DemoLoanTypeSeeder())->run($userId);
            (new DemoAllowanceSeeder())->run($userId);
            (new DemoDeductionSeeder())->run($userId);
            (new DemoLoanSeeder())->run($userId);
            (new DemoOvertimeSeeder())->run($userId);
            (new DemoPayrollSeeder())->run($userId);
            (new DemoIpRestrictSeeder())->run($userId);
        }
    }
}
