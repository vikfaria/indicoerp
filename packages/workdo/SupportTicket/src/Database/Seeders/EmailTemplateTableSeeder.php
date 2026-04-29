<?php

namespace Workdo\SupportTicket\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateLang;
use App\Models\User;

class EmailTemplateTableSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('type', 'company')->first();

        $emailTemplate = [
            'New Ticket',
            'New Ticket Reply',
        ];

         $defaultTemplate = [
                        'New Ticket' => [
                                'subject' => 'New Ticket',
                                'variables' => '{
                                        "App Name": "app_name",
                                        "App Url": "app_url",
                                        "Ticket Name": "ticket_name",
                                        "Email": "email",
                                        "Ticket Id" : "ticket_id",
                                        "Password": "password",
                                        "Ticket Url": "ticket_url"
                                  }',
                                  'lang' => [
                                        'ar' => '<p>مرحبا<br />في {app_name}</p><p><strong>Email</strong><br /></p><p><strong>بطاقة طلب الخدمة</strong>: {ticket_id}<br /></p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: \'Sans Serif\', Helvetica, Arial; font-weight: bold; line-height: 120%; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white;">التحقق من التذكرة الخاصة بك</strong></a></span></p><p>{app_url}</p><p>شكرا،<br />{app_name}</p>',
                                        'da' => '<p>Hej, &nbsp;<br />Velkommen til {app_name}</p><p><strong>E-mail</strong>: {email}</p><p><strong>Ticket-id</strong>: {ticket_id}</p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: \'Open Sans\', Helvetica, Arial, sans-serif; font-weight: bold; line-height: 120%; margin: 0px; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white; font-weight: bold;">Tjek din ticket</strong></a></span></p><p>{app_url}</p><p>Tak,<br />{app_name}</p>',
                                        'de' => '<p>Hello, &nbsp;<br />Welcome to {app_name}</p><p><strong>Email</strong>: {email}</p><p><strong>Ticket-ID</strong>: {ticket_id}</p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: \'Open Sans\', Helvetica, Arial, sans-serif; font-weight: bold; line-height: 120%; margin: 0px; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white; font-weight: bold;">Ticket überprüfen</strong></a></span></p><p>{app_url}</p><p>Danke,<br />{app_name}</p>',
                                        'en' => '<p>Welcome<br />to {app_name}</p><p><strong>Email</strong>: {email}</p><p><strong>Ticket ID</strong>: {ticket_id}</p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: Open Sans, Helvetica, Arial, sans-serif; font-weight: bold; line-height: 120%; margin: 0px; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white; font-weight: bold;">Validating your</strong></a></span></p><p>{app_url}</p><p>Thanks,<br />{app_name}</p>',
                                        'es' => '<p>Hola,<br />Bienvenido a {app_name}</p><p><strong>Email</strong>: {email}</p><p><strong>Ticket ID</strong>: {ticket_id}</p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: Open Sans, Helvetica, Arial, sans-serif; font-weight: bold; line-height: 120%; margin: 0px; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white; font-weight: bold;">Comprobar su ticket</strong></a></span></p><p>{app_url}</p><p>Gracias,<br />{app_name}</p>',
                                        'fr' => '<p>Bonjour,<br />Bienvenue dans {app_name}</p><p><strong>Email</strong>: {email}</p><p><strong>ID</strong>: {ticket_id}</p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: Open Sans, Helvetica, Arial, sans-serif; font-weight: bold; line-height: 120%; margin: 0px; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white; font-weight: bold;">Vérifiez votre ticket</strong></a></span></p><p>{app_url}</p><p>Merci,<br />{app_name}</p>',
                                        'it' => '<p>Ciao,<br />Benvenuto in {app_name}</p><p><strong>Email</strong>: {email}</p><p><strong>Ticket ID</strong>: {ticket_id}</p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: Open Sans, Helvetica, Arial, sans-serif; font-weight: bold; line-height: 120%; margin: 0px; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white; font-weight: bold;">Verifica il tuo biglietto</strong></a></span></p><p>{app_url}</p><p>Grazie,<br />{app_name}</p>',
                                        'ja' => '<p>こんにちは、<br />{app_name}へようこそ</p><p><strong>メール</strong>: {email}</p><p><strong>チケットID</strong>: {ticket_id}</p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: \"Open Sans\", Helvetica, Arial, sans-serif; font-weight: bold; line-height: 120%; margin: 0px; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white; font-weight: bold;">チケットを確認する</strong></a></span></p><p>{app_url}</p><p>ありがとう、<br />{app_name}</p>',
                                        'nl' => '<p>Hallo,<br />Welkom bij {app_name}</p><p><strong>E-mail</strong>: {email}</p><p><strong>Ticket-ID</strong>: {ticket_id}</p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: Open Sans, Helvetica, Arial, sans-serif; font-weight: bold; line-height: 120%; margin: 0px; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white; font-weight: bold;">Uw ticket controleren</strong></a></span></p><p>{app_url}</p><p>Bedankt,<br />{app_name}</p>',
                                        'pl' => '<p>Witaj,<br />Witamy w {app_name}</p><p><strong>Email</strong>: {email}</p><p><strong>ID zgłoszenia</strong>: {ticket_id}</p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: Open Sans, Helvetica, Arial, sans-serif; font-weight: bold; line-height: 120%; margin: 0px; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white; font-weight: bold;">Sprawdź swój ticket</strong></a></span></p><p>{app_url}</p><p>Dziękujemy,<br />{app_name}</p>',
                                        'ru' => '<p>Здравствуйте,<br />Добро пожаловать в {app_name}</p><p><strong>Электронная почта</strong>: {email}</p><p><strong>ID тикета</strong>: {ticket_id}</p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: Open Sans, Helvetica, Arial, sans-serif; font-weight: bold; line-height: 120%; margin: 0px; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white; font-weight: bold;">Проверить тикет</strong></a></span></p><p>{app_url}</p><p>Спасибо,<br />{app_name}</p>',
                                        'pt' => '<p>Olá,<br />Bem-vindo à {app_name}</p><p><strong>Email</strong>: {email}</p><p><strong>ID do ticket</strong>: {ticket_id}</p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: Open Sans, Helvetica, Arial, sans-serif; font-weight: bold; line-height: 120%; margin: 0px; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white; font-weight: bold;">Confira o seu ticket</strong></a></span></p><p>{app_url}</p><p>Obrigado,<br />{app_name}</p>',
                                        'tr' => '<p>Merhaba,<br />{app_name}\'a hoş geldiniz</p><p><strong>E-posta</strong>: {email}</p><p><strong>Bilet Kimliği</strong>: {ticket_id}</p><p style="text-align: center;" align="center"><span style="font-size: 18pt;"><a href="{ticket_url}" target="_blank" rel="noopener" style="background: #6676ef; color: #ffffff; font-family: Open Sans, Helvetica, Arial, sans-serif; font-weight: bold; line-height: 120%; margin: 0px; text-decoration: none; text-transform: none; padding: 10px 20px; border-radius: 5px; display: inline-block;"><strong style="color: white; font-weight: bold;">Biletinizi doğrulayın</strong></a></span></p><p>{app_url}</p><p>Teşekkürler,<br />{app_name}</p>'
                                ],
                            ],
                            'New Ticket Reply' => [
                                'subject' => 'Ticket Reply',
                                'variables' => '{
                                        "App Name" : "app_name",
                                        "Company Name" : "company_name",
                                        "App Url": "app_url",
                                        "Ticket Name" : "ticket_name",
                                        "Ticket Id" : "ticket_id",
                                        "Ticket Description" : "reply_description"
                                    }',
                                'lang' => [
                                    'ar' => '<p>مرحبا ، مرحبا بك في { app_name }.</p><p>&nbsp;</p><p>{ ticket_name }</p><p>{ ticket_id }</p><p>&nbsp;</p><p>الوصف : { reply_description }</p><p>&nbsp;</p><p>شكرا</p><p>{ app_name }</p>',
                                    'da' => '<p>Hej, velkommen til { app_name }.</p><p>&nbsp;</p><p>{ ticket_name }</p><p>{ ticket_id }</p><p>&nbsp;</p><p>Beskrivelse: { reply_description }</p><p>&nbsp;</p><p>Tak.</p><p>{ app_name }</p>',
                                    'de' => '<p>Hallo, Willkommen bei {app_name}.</p><p>&nbsp;</p><p>{ticketname}</p><p>{ticket_id}</p><p>&nbsp;</p><p>Beschreibung: {reply_description}</p><p>&nbsp;</p><p>Danke,</p><p>{Anwendungsname}</p>',
                                    'en' => '<p>Hello,&nbsp;<br />Welcome to {app_name}.</p><p>{ticket_name}</p><p>{ticket_id}</p><p><strong>Description</strong> : {reply_description}</p><p>Thanks,<br />{app_name}</p>',
                                    'es' => '<p>Hola, Bienvenido a {app_name}.</p><p>&nbsp;</p><p>{ticket_name}</p><p>{ticket_id}</p><p>&nbsp;</p><p>Descripci&oacute;n: {reply_description}</p><p>&nbsp;</p><p>Gracias,</p><p>{app_name}</p>',
                                    'fr' => '<p>Hola, Bienvenido a {app_name}.</p><p>&nbsp;</p><p>{ticket_name}</p><p>{ticket_id}</p><p>&nbsp;</p><p>Descripci&oacute;n: {reply_description}</p><p>&nbsp;</p><p>Gracias,</p><p>{app_name}</p>',
                                    'it' => '<p>Ciao, Benvenuti in {app_name}.</p><p>&nbsp;</p><p>{ticket_name}</p><p>{ticket_id}</p><p>&nbsp;</p><p>Descrizione: {reply_description}</p><p>&nbsp;</p><p>Grazie,</p><p>{app_name}</p>',
                                    'ja' => '<p>こんにちは、 {app_name}へようこそ。</p><p>&nbsp;</p><p>{ticket_name}</p><p>{ticket_id}</p><p>&nbsp;</p><p>説明 : {reply_description}</p><p>&nbsp;</p><p>ありがとう。</p><p>{app_name}</p>',
                                    'nl' => '<p>Hallo, Welkom bij { app_name }.</p><p>&nbsp;</p><p>{ ticket_name }</p><p>{ ticket_id }</p><p>&nbsp;</p><p>Beschrijving: { reply_description }</p><p>&nbsp;</p><p>Bedankt.</p><p>{ app_name }</p>',
                                    'pl' => '<p>Witaj, Witamy w aplikacji {app_name }.</p><p>&nbsp;</p><p>{ticket_name }</p><p>{ticket_id }</p><p>&nbsp;</p><p>Opis: {reply_description }</p><p>&nbsp;</p><p>Dziękuję,</p><p>{app_name }</p>',
                                    'ru' => '<p>Здравствуйте, Добро пожаловать в { app_name }.</p><p>&nbsp;</p><p>Witaj, Witamy w aplikacji {app_name }.</p><p>&nbsp;</p><p>{ticket_name }</p><p>{ticket_id }</p><p>&nbsp;</p><p>Opis: {reply_description }</p><p>&nbsp;</p><p>Dziękuję,</p><p>{app_name }</p>',
                                    'pt' => '<p>Ol&aacute;, Bem-vindo a {app_name}.</p><p>&nbsp;</p><p>{ticket_name}</p><p>{ticket_id}</p><p>&nbsp;</p><p>Descri&ccedil;&atilde;o: {reply_description}</p><p>&nbsp;</p><p>Obrigado,</p><p>{app_name}</p>',
                                ],
                            ],
                ];
        foreach ($emailTemplate as $eTemp) {
            $table = EmailTemplate::where('name', $eTemp)->where('module_name', 'SupportTicket')->exists();
            if (!$table) {
                $emailtemplate = EmailTemplate::create([
                    'name' => $eTemp,
                    'from' => !empty(env('APP_NAME')) ? env('APP_NAME') : 'ERPGo SaaS',
                    'module_name' => 'SupportTicket',
                    'created_by' => $admin->id,
                    'creator_id' => $admin->id,
                ]);
                
                foreach ($defaultTemplate[$eTemp]['lang'] as $lang => $content) {
                    EmailTemplateLang::create([
                        'parent_id' => $emailtemplate->id,
                        'lang' => $lang,
                        'subject' => $defaultTemplate[$eTemp]['subject'],
                        'variables' => $defaultTemplate[$eTemp]['variables'],
                        'content' => $content,
                    ]);
                }
            }
        }
    }
}