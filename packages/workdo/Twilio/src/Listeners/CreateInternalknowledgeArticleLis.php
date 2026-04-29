<?php

namespace Workdo\Twilio\Listeners;

use App\Models\User;
use Workdo\Internalknowledge\Events\CreateInternalknowledgeArticle;
use Workdo\Internalknowledge\Models\InternalknowledgeBook;
use Workdo\Twilio\Services\SendMsg;

class CreateInternalknowledgeArticleLis
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
    public function handle(CreateInternalknowledgeArticle $event)
    {
        if (company_setting('Twilio New Article') == 'on') {

            $article = $event->internalknowledgeArticle;

            $book = InternalknowledgeBook::find($article->internalknowledge_book_id);
            $user = User::find($article->created_by);

            if (!empty($book) && !empty($user->mobile_no)) {
                $uArr = [
                    'article_type' => $article->type,
                    'book_name'    => $book->title,
                ];

                SendMsg::SendMsgs($user->mobile_no, $uArr, 'New Article');
            }

        }
    }
}
