<?php

namespace Workdo\Telegram\Listeners;

use Workdo\Internalknowledge\Events\CreateInternalknowledgeArticle;
use Workdo\Internalknowledge\Models\InternalknowledgeBook;
use Workdo\Telegram\Services\SendMsg;

class CreateInternalknowledgeArticleLis
{
    public function __construct()
    {
        //
    }

    public function handle(CreateInternalknowledgeArticle $event)
    {
        $article = $event->internalknowledgeArticle;
        $book = InternalknowledgeBook::find($article->internalknowledge_book_id);

        if (company_setting('Telegram New Article')  == 'on') {

            $uArr = [
                'article_type' => $article->type,
                'book_name'    => !empty($book) ? $book->title : '-',
            ];

            SendMsg::SendMsgs($uArr , 'New Article');
        }
    }
}
