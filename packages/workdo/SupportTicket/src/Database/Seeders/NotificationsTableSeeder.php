<?php

namespace Workdo\SupportTicket\Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class NotificationsTableSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $notifications = [
            'New Ticket', 'New Ticket Reply'
        ];
        $permissions = [
            'create-support-tickets',
            'reply-support-tickets',
        ];

        foreach ($notifications as $key => $n) {
            $ntfy = Notification::where('action', $n)->where('type', 'mail')->where('module', 'SupportTicket')->count();
            if ($ntfy == 0) {
                $new = new Notification();
                $new->action = $n;
                $new->status = 'on';
                $new->permissions = $permissions[$key];
                $new->module = 'SupportTicket';
                $new->type = 'mail';
                $new->save();
            }
        }
    }
}