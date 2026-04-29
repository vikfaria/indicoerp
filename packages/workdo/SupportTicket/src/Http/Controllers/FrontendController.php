<?php

namespace Workdo\SupportTicket\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Workdo\SupportTicket\Http\Requests\StoreFrontendTicketRequest;
use Workdo\SupportTicket\Http\Requests\StoreFrontendContactRequest;
use Workdo\SupportTicket\Http\Requests\StoreTicketReplyRequest;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\Contact;
use Workdo\SupportTicket\Models\Conversion;
use Workdo\SupportTicket\Models\Faq;
use Workdo\SupportTicket\Models\KnowledgeBase;
use Workdo\SupportTicket\Models\KnowledgeBaseCategory;
use Workdo\SupportTicket\Models\SupportTicketCustomPage;
use Workdo\SupportTicket\Models\TicketCategory;
use Workdo\SupportTicket\Models\Ticket;
use Workdo\SupportTicket\Models\SupportTicketSetting;
use Workdo\SupportTicket\Models\QuickLink;
use Workdo\SupportTicket\Models\TicketField;

class FrontendController extends Controller
{
    private function getUser($slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();
        return $user->id;
    }

    private function getSettings($user_id)
    {
        $allSettings = SupportTicketSetting::getAllByCompany($user_id);
        $privacyPolicy = $allSettings['privacy_policy'] ?? null;
        $termsConditions = $allSettings['terms_conditions'] ?? null;

        $privacyEnabled = false;
        $termsEnabled = false;

        if ($privacyPolicy) {
            $privacyData = json_decode($privacyPolicy, true);
            $privacyEnabled = $privacyData['enabled'] ?? false;
        }

        if ($termsConditions) {
            $termsData = json_decode($termsConditions, true);
            $termsEnabled = $termsData['enabled'] ?? false;
        }

        return [
            'faq_is_on' => company_setting('faq_is_on', $user_id) ?? 'off',
            'knowledge_base_is_on' => company_setting('knowledge_base_is_on', $user_id) ?? 'off',
            'privacy_policy_enabled' => $privacyEnabled,
            'terms_conditions_enabled' => $termsEnabled
        ];
    }

    private function getBrandSettings($user_id)
    {
        $allSettings = SupportTicketSetting::getAllByCompany($user_id);
        $companyAllSetting = getCompanyAllSetting($user_id);
        
        $customPages = SupportTicketCustomPage::where('created_by', $user_id)
            ->where('enable_page_footer', 'on')
            ->get(['slug', 'title']);
            
        $privacyEnabled = $customPages->where('slug', 'privacy-policy')->isNotEmpty();
        $termsEnabled = $customPages->where('slug', 'terms-conditions')->isNotEmpty();
        
        $customPagesArray = $customPages->map(function ($page) {
            return [
                'slug' => $page->slug,
                'name' => $page->title
            ];
        })->toArray();

        return [
            'logo_dark' => $allSettings['logo_dark'] ?? '',
            'favicon' => $allSettings['favicon'] ?? '',
            'titleText' => $allSettings['title_text'] ?? '',
            'footerText' => $allSettings['footer_text'] ?? '',
            'companyAllSetting' => $companyAllSetting,
            'companyId' => $user_id,
            'termsEnabled' => $termsEnabled,
            'privacyEnabled' => $privacyEnabled,
            'customPages' => $customPagesArray
        ];
    }

    private function getTitleSections($user_id)
    {
        $titleSections = SupportTicketSetting::where('key', 'title_sections')
            ->where('created_by', $user_id)
            ->first();

        return $titleSections ? json_decode($titleSections->value, true) : [
            'create_ticket' => [
                'title' => 'Create Support Ticket',
                'description' => 'Submit your support request and get help from our team'
            ],
            'search_ticket' => [
                'title' => 'Search Your Ticket',
                'description' => 'Track the status of your existing support tickets'
            ],
            'knowledge_base' => [
                'title' => 'Knowledge Base',
                'description' => 'Find answers to common questions and issues'
            ],
            'faq' => [
                'title' => 'Frequently Asked Questions',
                'description' => 'Quick answers to the most common questions'
            ],
            'contact' => [
                'title' => 'Contact Us',
                'description' => 'Get in touch with our support team'
            ]
        ];
    }

    private function getQuickLinks($user_id)
    {
        return QuickLink::where('created_by', $user_id)
            ->orderBy('order')
            ->get(['title', 'icon', 'link'])
            ->toArray();
    }

    private function getSupportInformation($user_id)
    {
        $supportInfo = SupportTicketSetting::where('key', 'support_information')
            ->where('created_by', $user_id)
            ->first();

        return $supportInfo ? json_decode($supportInfo->value, true) : null;
    }

    private function getCtaSections($user_id)
    {
        $ctaSections = SupportTicketSetting::where('key', 'cta_sections')
            ->where('created_by', $user_id)
            ->first();

        return $ctaSections ? json_decode($ctaSections->value, true) : [
            'knowledge_base' => [
                'title' => 'Can\'t find what you\'re looking for?',
                'description' => 'Our support team is here to help'
            ],
            'faq' => [
                'title' => 'Still Have Questions?',
                'description' => 'Our support team is ready to help'
            ]
        ];
    }

    private function getContactInformation($user_id)
    {
        $contactInfo = SupportTicketSetting::where('key', 'contact_information')
            ->where('created_by', $user_id)
            ->first();

        if (!$contactInfo) {
            return null;
        }

        $data = json_decode($contactInfo->value, true);

        // Handle media files like DemoItem pattern - convert basename to full path
        if (!empty($data['image']) && !str_contains($data['image'], 'http')) {
            $data['image'] = basename($data['image']);
        }

        return $data;
    }

    public function index($slug)
    {
        $user_id = $this->getUser($slug);

        $categories = TicketCategory::where('created_by', $user_id)->get();
        
        // Get all fields ordered by 'order' field
        $allFields = TicketField::where('created_by', $user_id)
            ->where('status', true)
            ->orderBy('order')
            ->get();
            
        // Ensure default fields exist
        if ($allFields->count() < 1) {
            TicketField::defaultdata($user_id);
            $allFields = TicketField::where('created_by', $user_id)
                ->where('status', true)
                ->orderBy('order')
                ->get();
        }
        
        // Get custom fields (custom_id > 6) ordered by 'order' field
        $customFields = $allFields->where('custom_id', '>', '6')->values();

        return Inertia::render('SupportTicket/Frontend/CreateTicket', [
            'categories' => $categories->toArray(),
            'allFields' => $allFields->toArray(),
            'customFields' => $customFields->toArray(),
            'settings' => $this->getSettings($user_id),
            'brandSettings' => $this->getBrandSettings($user_id),
            'titleSections' => $this->getTitleSections($user_id),
            'quickLinks' => $this->getQuickLinks($user_id),
            'supportInformation' => $this->getSupportInformation($user_id),
            'slug' => $slug
        ])->withViewData([
                    'title' => 'Create Support Ticket'
                ]);
    }

    public function create($slug)
    {
        return $this->index($slug);
    }

    public function store(StoreFrontendTicketRequest $request, $slug)
    {
        $user_id = $this->getUser($slug);

        try {
            $ticketId = time();

            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = $file->store('tickets', 'public');
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => basename($filename)
                    ];
                }
            }

            $ticket = new Ticket();
            $ticket->ticket_id = $ticketId;
            $ticket->name = $request->name;
            $ticket->email = $request->email;
            $ticket->category = $request->category;
            $ticket->subject = $request->subject;
            $ticket->description = $request->description;
            $ticket->status = $request->status ?? 'In Progress';
            $ticket->account_type = 'custom';
            $ticket->attachments = json_encode($attachments);
            $ticket->creator_id = $user_id;
            $ticket->created_by = $user_id;
            $ticket->save();
            
            // Save custom field data
            if ($request->has('fields') && !empty($request->fields)) {
                TicketField::saveData($ticket, $request->fields);
            }

            $encryptedId = Crypt::encrypt($ticket->id);

            // Send email notification for new ticket
            if (!empty(company_setting('New Ticket', $user_id)) && company_setting('New Ticket', $user_id) == true) {
                $uArr = [
                    'ticket_name' => $ticket->name,
                    'email' => $ticket->email,
                    'ticket_id' => $ticket->ticket_id,
                    'ticket_url' => route('support-ticket.show.ticket', [$slug, Crypt::encrypt($ticket->id)])
                ];
                EmailTemplate::sendEmailTemplate('New Ticket', [$ticket->email], $uArr, $user_id);
            }

            $ticketLink = route('support-ticket.show', [$slug, $encryptedId]);
            return redirect()->route('support-ticket.index', $slug)
                ->with('success', __('The Ticket has been created successfully') . ' <a href="' . $ticketLink . '"><b>' . __('Your unique ticket link is this.') . '</b></a>');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to create ticket. Please try again.') . ' Error: ' . $e->getMessage());
        }
    }

    public function search($slug)
    {
        $user_id = $this->getUser($slug);
        $settings = [
            'faq_is_on' => company_setting('faq_is_on', $user_id) ?? 'off',
            'knowledge_base_is_on' => company_setting('knowledge_base_is_on', $user_id) ?? 'off'
        ];

        return Inertia::render('SupportTicket/Frontend/SearchTicket', [
            'settings' => $settings,
            'brandSettings' => $this->getBrandSettings($user_id),
            'titleSections' => $this->getTitleSections($user_id),
            'slug' => $slug
        ]);
    }

    public function searchTicket(Request $request, $slug)
    {
        $user_id = $this->getUser($slug);

        if ($request->filled('ticket_id') && $request->filled('email')) {
            $ticket = Ticket::where('ticket_id', $request->ticket_id)
                ->where('email', $request->email)
                ->where('created_by', $user_id)
                ->first();

            if (!$ticket) {
                $ticket = Ticket::where('ticket_id', $request->ticket_id)
                    ->whereRaw('LOWER(email) = ?', [strtolower($request->email)])
                    ->where('created_by', $user_id)
                    ->first();
            }

            if ($ticket) {
                $encryptedId = Crypt::encrypt($ticket->id);
                return redirect()->route('support-ticket.show.ticket', [$slug, $encryptedId]);
            } else {
                $searchInfo = "Searching for Ticket ID: {$request->ticket_id}, Email: {$request->email}";
                return redirect()->back()->with('error', __('Ticket not found. ') . $searchInfo);
            }
        }

        return redirect()->back()->with('error', __('Please provide both ticket ID and email.'));
    }

    public function knowledge($slug)
    {
        $user_id = $this->getUser($slug);

        $settings = [
            'faq_is_on' => company_setting('faq_is_on', $user_id) ?? 'off',
            'knowledge_base_is_on' => company_setting('knowledge_base_is_on', $user_id) ?? 'off'
        ];

        try {
            $categories = KnowledgeBaseCategory::where('created_by', $user_id)->get();
            $knowledgeItems = KnowledgeBase::where('created_by', $user_id)->get();

            // Map category names to category objects for frontend
            $knowledgeItems = $knowledgeItems->map(function ($item) use ($categories) {
                $categoryObj = $categories->firstWhere('title', $item->category);
                $item->category = $categoryObj ? [
                    'id' => $categoryObj->id,
                    'name' => $categoryObj->title
                ] : null;
                return $item;
            });
        } catch (\Exception $e) {
            $knowledgeItems = collect();
            $categories = collect();
        }

        return Inertia::render('SupportTicket/Frontend/Knowledge', [
            'knowledgeItems' => $knowledgeItems,
            'categories' => $categories,
            'settings' => $settings,
            'brandSettings' => $this->getBrandSettings($user_id),
            'titleSections' => $this->getTitleSections($user_id),
            'ctaSections' => $this->getCtaSections($user_id),
            'slug' => $slug
        ]);
    }

    public function faq($slug)
    {
        $user_id = $this->getUser($slug);

        $settings = [
            'faq_is_on' => company_setting('faq_is_on', $user_id) ?? 'off',
            'knowledge_base_is_on' => company_setting('knowledge_base_is_on', $user_id) ?? 'off'
        ];

        $faqs = Faq::where('created_by', $user_id)->get();

        return Inertia::render('SupportTicket/Frontend/Faq', [
            'faqs' => $faqs,
            'settings' => $settings,
            'brandSettings' => $this->getBrandSettings($user_id),
            'titleSections' => $this->getTitleSections($user_id),
            'ctaSections' => $this->getCtaSections($user_id),
            'slug' => $slug
        ]);
    }

    public function knowledgeArticle($slug, $id)
    {
        $user_id = $this->getUser($slug);

        $settings = [
            'faq_is_on' => company_setting('faq_is_on', $user_id) ?? 'off',
            'knowledge_base_is_on' => company_setting('knowledge_base_is_on', $user_id) ?? 'off'
        ];

        try {
            $article = KnowledgeBase::where('id', $id)
                ->where('created_by', $user_id)
                ->with('category')
                ->firstOrFail();

            $relatedArticles = KnowledgeBase::where('created_by', $user_id)
                ->where('id', '!=', $id)
                ->when($article->category, function ($query) use ($article) {
                    $query->where('category', $article->category);
                })
                ->limit(3)
                ->get(['id', 'title', 'description', 'created_at']);

            if ($relatedArticles->count() < 3) {
                $additionalArticles = KnowledgeBase::where('created_by', $user_id)
                    ->where('id', '!=', $id)
                    ->when($article->category, function ($query) use ($article) {
                        $query->where('category', '!=', $article->category);
                    })
                    ->limit(3 - $relatedArticles->count())
                    ->get(['id', 'title', 'description', 'created_at']);

                $relatedArticles = $relatedArticles->merge($additionalArticles);
            }

            return Inertia::render('SupportTicket/Frontend/KnowledgeArticle', [
                'article' => $article,
                'relatedArticles' => $relatedArticles,
                'settings' => $settings,
                'brandSettings' => $this->getBrandSettings($user_id),
                'slug' => $slug
            ]);
        } catch (\Exception $e) {
            return redirect()->route('support-ticket.knowledge')->with('error', __('Article not found.'));
        }
    }

    public function contact($slug)
    {
        $user_id = $this->getUser($slug);
        $settings = [
            'faq_is_on' => company_setting('faq_is_on', $user_id) ?? 'off',
            'knowledge_base_is_on' => company_setting('knowledge_base_is_on', $user_id) ?? 'off'
        ];

        return Inertia::render('SupportTicket/Frontend/Contact', [
            'settings' => $settings,
            'brandSettings' => $this->getBrandSettings($user_id),
            'titleSections' => $this->getTitleSections($user_id),
            'contactInformation' => $this->getContactInformation($user_id),
            'slug' => $slug
        ]);
    }

    public function storeContact(StoreFrontendContactRequest $request, $slug)
    {
        $user_id = $this->getUser($slug);

        try {

            $contact = new Contact();
            $contact->first_name = $request->firstName;
            $contact->last_name = $request->lastName;
            $contact->email = $request->email;
            $contact->subject = $request->subject;
            $contact->message = $request->message;
            $contact->creator_id = $user_id;
            $contact->created_by = $user_id;
            $contact->save();

            return redirect()->back()->with('success', __('The contact has been added successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to send message. Please try again.'));
        }
    }

    public function showByTicketId($slug, $ticket_id)
    {
        $user_id = $this->getUser($slug);

        try {
            $decrypted_id = Crypt::decrypt($ticket_id);
            $ticket = Ticket::where('id', $decrypted_id)
                ->where('created_by', $user_id)
                ->with([
                    'conversions' => function ($query) {
                        $query->orderBy('created_at', 'asc');
                    }
                ])
                ->firstOrFail();
                
            // Load category separately
            $category = null;
            if ($ticket->category) {
                $category = TicketCategory::find($ticket->category);
            }

            if ($ticket->attachments) {
                $ticket->attachments = json_decode($ticket->attachments, true) ?: [];
            }

            if ($ticket->conversions) {
                $ticket->conversions->each(function ($conversion) {
                    if ($conversion->attachments) {
                        $conversion->attachments = is_string($conversion->attachments) 
                            ? json_decode($conversion->attachments, true) ?: [] 
                            : (is_array($conversion->attachments) ? $conversion->attachments : []);
                    } else {
                        $conversion->attachments = [];
                    }
                });
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Ticket not found'));
        }

        $ticketData = [
            'id' => $ticket->id,
            'ticket_id' => $ticket->ticket_id,
            'name' => $ticket->name,
            'email' => $ticket->email,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'description' => $ticket->description,
            'created_at' => $ticket->created_at->toISOString(),
            'attachments' => is_string($ticket->attachments) ? json_decode(html_entity_decode($ticket->attachments), true) ?? [] : ($ticket->attachments ?? []),
            'conversions' => $ticket->conversions ? $ticket->conversions->map(function ($conversation) use ($ticket, $user_id) {
                $replyByName = 'User';
                if ($conversation->sender === 'admin') {
                    $companyName = company_setting('company_name', $user_id);
                    $replyByName = $companyName ?: 'Admin';
                } else {
                    $replyByName = $ticket->name;
                }
                
                return [
                    'id' => $conversation->id,
                    'description' => html_entity_decode($conversation->description),
                    'sender' => $conversation->sender,
                    'created_at' => $conversation->created_at ? $conversation->created_at->toISOString() : null,
                    'attachments' => is_array($conversation->attachments) ? $conversation->attachments : (is_string($conversation->attachments) ? json_decode($conversation->attachments, true) ?? [] : []),
                    'replyBy' => ['name' => $replyByName],
                ];
            }) : []
        ];

        return Inertia::render('SupportTicket/Show', [
            'ticket' => $ticketData,
            'settings' => $this->getSettings($user_id),
            'brandSettings' => $this->getBrandSettings($user_id),
            'slug' => $slug
        ]);
    }

    public function customPage($slug, $pageSlug)
    {
        $user_id = $this->getUser($slug);

        $customPage = SupportTicketCustomPage::where('slug', $pageSlug)
            ->where('created_by', $user_id)
            ->firstOrFail();

        return Inertia::render('SupportTicket/Frontend/CustomPage', [
            'settings' => $this->getSettings($user_id),
            'customPage' => $customPage,
            'brandSettings' => $this->getBrandSettings($user_id),
            'slug' => $slug
        ]);
    }

    public function privacyPolicy($slug)
    {
        $user_id = $this->getUser($slug);

        $privacyPage = SupportTicketCustomPage::where('slug', 'privacy-policy')
            ->where('created_by', $user_id)
            ->first();

        return Inertia::render('SupportTicket/Frontend/PrivacyPolicy', [
            'settings' => $this->getSettings($user_id),
            'privacyPolicy' => $privacyPage ? ['content' => $privacyPage->contents, 'enabled' => $privacyPage->enable_page_footer === 'on'] : null,
            'brandSettings' => $this->getBrandSettings($user_id),
            'slug' => $slug
        ]);
    }

    public function termsConditions($slug)
    {
        $user_id = $this->getUser($slug);

        $termsPage = SupportTicketCustomPage::where('slug', 'terms-conditions')
            ->where('created_by', $user_id)
            ->first();

        return Inertia::render('SupportTicket/Frontend/TermsConditions', [
            'settings' => $this->getSettings($user_id),
            'termsConditions' => $termsPage ? ['content' => $termsPage->contents, 'enabled' => $termsPage->enable_page_footer === 'on'] : null,
            'brandSettings' => $this->getBrandSettings($user_id),
            'slug' => $slug
        ]);
    }

    public function storeReply(StoreTicketReplyRequest $request, $slug, $ticket_id)
    {
        $user_id = $this->getUser($slug);

        try {
            $decrypted_id = Crypt::decrypt($ticket_id);
            $ticket = Ticket::where('id', $decrypted_id)
                ->where('created_by', $user_id)
                ->firstOrFail();
        } catch (\Exception $e) {
            return back()->with('error', 'Ticket not found');
        }

        // Handle file attachments
        $attachments = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filename = $file->store('tickets', 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => basename($filename)
                ];
            }
        }

        // Create conversation
        $conversion = new Conversion();
        $conversion->ticket_id = $ticket->id;
        $conversion->message = $request->description;
        $conversion->sender_type = 'user';
        $conversion->sender_name = $ticket->name;
        $conversion->attachments = json_encode($attachments);
        $conversion->created_by = $user_id;
        $conversion->save();

        return back()->with('success', __('The reply has been added successfully'));
    }

    public function show($slug, $id)
    {
        $user_id = $this->getUser($slug);
    
        try {
            $decryptedId = Crypt::decrypt($id);
            $ticket = Ticket::find($decryptedId);
        } catch (\Exception $e) {
            $ticket = Ticket::find($id);
        }

        if (!$ticket) {
            return redirect()->route('support-tickets.index')->with('error', __('Ticket not found'));
        }

        // Load relationships separately to avoid conflicts
        $ticket->load(['conversions']);
        $category = null;
        if ($ticket->category) {
            $category = TicketCategory::find($ticket->category);
        }

        $ticketData = [
            'id' => $ticket->id,
            'ticket_id' => $ticket->ticket_id,
            'name' => $ticket->name,
            'email' => $ticket->email,
            'user_id' => $ticket->user_id,
            'account_type' => $ticket->account_type,
            'category' => $ticket->category,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'description' => $ticket->description,
            'note' => $ticket->note,
            'attachments' => is_string($ticket->attachments) ? json_decode(html_entity_decode($ticket->attachments), true) ?? [] : ($ticket->attachments ?? []),

            'category_info' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color ?? '#000000'
            ] : null,
            'conversions' => $ticket->conversions->map(function ($conversation) use ($ticket, $user_id) {
                $replyByName = 'User';
                if ($conversation->sender === 'admin') {
                    $companyName = company_setting('company_name', $user_id);
                    $replyByName = $companyName ?: 'Admin';
                } else {
                    $replyByName = $ticket->name;
                }
                
                return [
                    'id' => $conversation->id,
                    'ticket_id' => $conversation->ticket_id,
                    'description' => html_entity_decode($conversation->description),
                    'sender' => $conversation->sender,
                    'attachments' => is_array($conversation->attachments) ? $conversation->attachments : (is_string($conversation->attachments) ? json_decode(html_entity_decode($conversation->attachments), true) ?? [] : []),
                    'created_at' => $conversation->created_at ? $conversation->created_at->toISOString() : null,
                    'replyBy' => ['name' => $replyByName],
                ];
            }),
            'created_at' => $ticket->created_at ? $ticket->created_at->toISOString() : null,
            'updated_at' => $ticket->updated_at ? $ticket->updated_at->toISOString() : null,
        ];

        return Inertia::render('SupportTicket/Show', [
            'ticket' => $ticketData,
            'settings' => $this->getSettings($user_id),
            'brandSettings' => $this->getBrandSettings($user_id),
            'slug' => $slug
        ]);
    }
}