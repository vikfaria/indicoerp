<?php

namespace Workdo\Recruitment\Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class NotificationsTableSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        
        // email notification
        $notifications = [
            'Application Received'
        ];
        
        $permissions = [
            'manage-recruitment'
        ];
        
        foreach($notifications as $key => $n) {
            $ntfy = Notification::where('action', $n)->where('type', 'mail')->where('module', 'Recruitment')->count();
            if($ntfy == 0) {
                $new = new Notification();
                $new->action = $n;
                $new->status = 'on';
                $new->permissions = $permissions[$key];
                $new->module = 'Recruitment';
                $new->type = 'mail';
                $new->save();
            }
        }
    }
}