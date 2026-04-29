<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\WordpressWoocommerce\Events\CreateWoocommerceProduct;
use Workdo\Twilio\Services\SendMsg;

class CreateWoocommerceProductLis
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(CreateWoocommerceProduct $event)
    {
        $product     = $event->wooProduct;
        $userProduct = $event->productServiceItem;

        $user = User::find($userProduct->created_by);

        if (company_setting('Twilio New Product') == 'on') {
            if (!empty($user->mobile_no)) {
                $uArr = [
                    'name' => $product['name']
                ];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Product');
            }
        }
    }
}
