<?php

namespace Workdo\Recruitment\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateLang;
use App\Models\User;

class EmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('type','company')->first();

        $emailTemplate = [
            'Application Received',
            'Offer Letter',
        ];

        $defaultTemplate = [
            'Application Received' => [
                'subject' => 'Application Received - {job_title}',
                'variables' => '{
                    "App Name": "app_name",
                    "Company Name": "company_name",
                    "App Url": "app_url",
                    "Candidate Name": "candidate_name",
                    "Candidate Email": "candidate_email",
                    "Job Title": "job_title",
                    "Tracking ID": "tracking_id",
                    "Tracking Link": "tracking_link"
                  }',
                  'lang' => [
                    'ar' => '<p>مرحبا {candidate_name}،</p><p>شكرا لك على تقديم طلبك لمنصب <strong>{job_title}</strong>!</p><p>لقد تلقينا طلبك بنجاح وهو الآن قيد المراجعة.</p><p><strong>تفاصيل الطلب:</strong></p><ul><li>المنصب: {job_title}</li><li>رقم التتبع: {tracking_id}</li></ul><p>يمكنك تتبع حالة طلبك باستخدام رقم التتبع على: {tracking_link}</p><p>سنراجع طلبك ونعاود الاتصال بك قريبا.</p><p>شكرا لاهتمامك بـ {company_name}.</p>',
                    'da' => '<p>Hej {candidate_name},</p><p>Tak for din ansøgning til stillingen som <strong>{job_title}</strong>!</p><p>Vi har modtaget din ansøgning og den er nu under behandling.</p><p><strong>Ansøgningsdetaljer:</strong></p><ul><li>Stilling: {job_title}</li><li>Sporings-ID: {tracking_id}</li></ul><p>Du kan spore status på din ansøgning ved hjælp af dit sporings-ID på: {tracking_link}</p><p>Vi vil gennemgå din ansøgning og vende tilbage til dig snart.</p><p>Tak for din interesse i {company_name}.</p>',
                    'de' => '<p>Hallo {candidate_name},</p><p>Vielen Dank für Ihre Bewerbung für die Position <strong>{job_title}</strong>!</p><p>Wir haben Ihre Bewerbung erfolgreich erhalten und sie wird nun geprüft.</p><p><strong>Bewerbungsdetails:</strong></p><ul><li>Position: {job_title}</li><li>Verfolgungs-ID: {tracking_id}</li></ul><p>Sie können den Status Ihrer Bewerbung mit Ihrer Verfolgungs-ID verfolgen unter: {tracking_link}</p><p>Wir werden Ihre Bewerbung prüfen und uns bald bei Ihnen melden.</p><p>Vielen Dank für Ihr Interesse an {company_name}.</p>',
                    'en' => '<p>Hello {candidate_name},</p><p>Thank you for your application for the position of <strong>{job_title}</strong>!</p><p>We have successfully received your application and it is now under review.</p><p><strong>Application Details:</strong></p><ul><li>Position: {job_title}</li><li>Tracking ID: {tracking_id}</li></ul><p>You can track the status of your application using your tracking ID at: {tracking_link}</p><p>We will review your application and get back to you soon.</p><p>Thank you for your interest in {company_name}.</p>',
                    'es' => '<p>Hola {candidate_name},</p><p>¡Gracias por tu solicitud para el puesto de <strong>{job_title}</strong>!</p><p>Hemos recibido exitosamente tu solicitud y ahora está bajo revisión.</p><p><strong>Detalles de la Solicitud:</strong></p><ul><li>Puesto: {job_title}</li><li>ID de Seguimiento: {tracking_id}</li></ul><p>Puedes rastrear el estado de tu solicitud usando tu ID de seguimiento en: {tracking_link}</p><p>Revisaremos tu solicitud y te contactaremos pronto.</p><p>Gracias por tu interés en {company_name}.</p>',
                    'fr' => '<p>Bonjour {candidate_name},</p><p>Merci pour votre candidature au poste de <strong>{job_title}</strong>!</p><p>Nous avons reçu avec succès votre candidature et elle est maintenant en cours d\'examen.</p><p><strong>Détails de la Candidature:</strong></p><ul><li>Poste: {job_title}</li><li>ID de Suivi: {tracking_id}</li></ul><p>Vous pouvez suivre le statut de votre candidature en utilisant votre ID de suivi à: {tracking_link}</p><p>Nous examinerons votre candidature et vous recontacterons bientôt.</p><p>Merci pour votre intérêt envers {company_name}.</p>',
                    'it' => '<p>Ciao {candidate_name},</p><p>Grazie per la tua candidatura per la posizione di <strong>{job_title}</strong>!</p><p>Abbiamo ricevuto con successo la tua candidatura ed è ora in fase di revisione.</p><p><strong>Dettagli della Candidatura:</strong></p><ul><li>Posizione: {job_title}</li><li>ID di Tracciamento: {tracking_id}</li></ul><p>Puoi tracciare lo stato della tua candidatura usando il tuo ID di tracciamento su: {tracking_link}</p><p>Esamineremo la tua candidatura e ti ricontatteremo presto.</p><p>Grazie per il tuo interesse in {company_name}.</p>',
                    'ja' => '<p>こんにちは {candidate_name}、</p><p><strong>{job_title}</strong>のポジションへのご応募ありがとうございます！</p><p>あなたの応募を正常に受け取り、現在審査中です。</p><p><strong>応募詳細:</strong></p><ul><li>ポジション: {job_title}</li><li>追跡ID: {tracking_id}</li></ul><p>追跡IDを使用して応募状況を確認できます: {tracking_link}</p><p>あなたの応募を審査し、すぐにご連絡いたします。</p><p>{company_name}への関心をお寄せいただきありがとうございます。</p>',
                    'nl' => '<p>Hallo {candidate_name},</p><p>Bedankt voor je sollicitatie voor de functie van <strong>{job_title}</strong>!</p><p>We hebben je sollicitatie succesvol ontvangen en deze wordt nu beoordeeld.</p><p><strong>Sollicitatie Details:</strong></p><ul><li>Functie: {job_title}</li><li>Tracking ID: {tracking_id}</li></ul><p>Je kunt de status van je sollicitatie volgen met je tracking ID op: {tracking_link}</p><p>We zullen je sollicitatie beoordelen en binnenkort contact met je opnemen.</p><p>Bedankt voor je interesse in {company_name}.</p>',
                    'pl' => '<p>Witaj {candidate_name},</p><p>Dziękujemy za aplikację na stanowisko <strong>{job_title}</strong>!</p><p>Pomyślnie otrzymaliśmy Twoją aplikację i jest ona teraz w trakcie przeglądu.</p><p><strong>Szczegóły Aplikacji:</strong></p><ul><li>Stanowisko: {job_title}</li><li>ID Śledzenia: {tracking_id}</li></ul><p>Możesz śledzić status swojej aplikacji używając ID śledzenia na: {tracking_link}</p><p>Przejrzymy Twoją aplikację i wkrótce się z Tobą skontaktujemy.</p><p>Dziękujemy za zainteresowanie {company_name}.</p>',
                    'pt' => '<p>Olá {candidate_name},</p><p>Obrigado pela sua candidatura para a posição de <strong>{job_title}</strong>!</p><p>Recebemos com sucesso a sua candidatura e ela está agora sob análise.</p><p><strong>Detalhes da Candidatura:</strong></p><ul><li>Posição: {job_title}</li><li>ID de Rastreamento: {tracking_id}</li></ul><p>Você pode acompanhar o status da sua candidatura usando seu ID de rastreamento em: {tracking_link}</p><p>Analisaremos sua candidatura e entraremos em contato em breve.</p><p>Obrigado pelo seu interesse em {company_name}.</p>',
                    'pt-BR' => '<p>Olá {candidate_name},</p><p>Obrigado pela sua candidatura para a posição de <strong>{job_title}</strong>!</p><p>Recebemos com sucesso a sua candidatura e ela está agora sob análise.</p><p><strong>Detalhes da Candidatura:</strong></p><ul><li>Posição: {job_title}</li><li>ID de Rastreamento: {tracking_id}</li></ul><p>Você pode acompanhar o status da sua candidatura usando seu ID de rastreamento em: {tracking_link}</p><p>Analisaremos sua candidatura e entraremos em contato em breve.</p><p>Obrigado pelo seu interesse em {company_name}.</p>',
                    'he' => '<p>שלום {candidate_name},</p><p>תודה על הגשת המועמדות שלך לתפקיד <strong>{job_title}</strong>!</p><p>קיבלנו בהצלחה את המועמדות שלך והיא כעת בבדיקה.</p><p><strong>פרטי המועמדות:</strong></p><ul><li>תפקיד: {job_title}</li><li>מזהה מעקב: {tracking_id}</li></ul><p>אתה יכול לעקוב אחר סטטוס המועמדות שלך באמצעות מזהה המעקב ב: {tracking_link}</p><p>נבדוק את המועמדות שלך ונחזור אליך בקרוב.</p><p>תודה על העניין שלך ב-{company_name}.</p>',
                    'tr' => '<p>Merhaba {candidate_name},</p><p><strong>{job_title}</strong> pozisyonu için başvurunuz için teşekkürler!</p><p>Başvurunuzu başarıyla aldık ve şu anda inceleme altında.</p><p><strong>Başvuru Detayları:</strong></p><ul><li>Pozisyon: {job_title}</li><li>Takip ID\'si: {tracking_id}</li></ul><p>Takip ID\'nizi kullanarak başvurunuzun durumunu şu adresten takip edebilirsiniz: {tracking_link}</p><p>Başvurunuzu inceleyeceğiz ve yakında size geri döneceğiz.</p><p>{company_name} için gösterdiğiniz ilgi için teşekkürler.</p>',
                    'ru' => '<p>Здравствуйте {candidate_name},</p><p>Спасибо за вашу заявку на должность <strong>{job_title}</strong>!</p><p>Мы успешно получили вашу заявку, и она сейчас находится на рассмотрении.</p><p><strong>Детали заявки:</strong></p><ul><li>Должность: {job_title}</li><li>ID отслеживания: {tracking_id}</li></ul><p>Вы можете отслеживать статус вашей заявки, используя ID отслеживания по адресу: {tracking_link}</p><p>Мы рассмотрим вашу заявку и свяжемся с вами в ближайшее время.</p><p>Спасибо за ваш интерес к {company_name}.</p>',
                    'zh' => '<p>您好 {candidate_name}，</p><p>感谢您申请<strong>{job_title}</strong>职位！</p><p>我们已成功收到您的申请，目前正在审核中。</p><p><strong>申请详情：</strong></p><ul><li>职位：{job_title}</li><li>跟踪ID：{tracking_id}</li></ul><p>您可以使用跟踪ID在以下地址查看申请状态：{tracking_link}</p><p>我们将审核您的申请并尽快与您联系。</p><p>感谢您对 {company_name} 的关注。</p>',
                ],
            ],
            'Offer Letter' => [
                'subject' => 'Job Offer - {position}',
                'variables' => '{
                    "App Name": "app_name",
                    "Company Name": "company_name",
                    "Candidate Name": "candidate_name",
                    "Position": "position",
                    "Salary": "salary",
                    "Start Date": "start_date",
                    "Download URL": "download_url"
                }',
                'lang' => [
                    'ar' => '<p><strong>عزيزي {candidate_name}،</strong></p><p>يسعدنا أن نعرض عليك منصب <strong>{position}</strong> في {company_name}.</p><p><strong>المنصب:</strong> {position}<br><strong>الراتب:</strong> {salary}<br><strong>تاريخ البدء:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">تحميل خطاب العرض</a></p><p><strong>مع أطيب التحيات،</strong><br><strong>قسم الموارد البشرية</strong><br><strong>{company_name}</strong></p>',
                    'da' => '<p><strong>Kære {candidate_name},</strong></p><p>Vi er glade for at tilbyde dig stillingen som <strong>{position}</strong> hos {company_name}.</p><p><strong>Stilling:</strong> {position}<br><strong>Løn:</strong> {salary}<br><strong>Startdato:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Download Tilbudsbrev</a></p><p><strong>Med venlig hilsen,</strong><br><strong>HR-afdelingen</strong><br><strong>{company_name}</strong></p>',
                    'de' => '<p><strong>Liebe/r {candidate_name},</strong></p><p>Wir freuen uns, Ihnen die Position als <strong>{position}</strong> bei {company_name} anzubieten.</p><p><strong>Position:</strong> {position}<br><strong>Gehalt:</strong> {salary}<br><strong>Startdatum:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Angebotsschreiben herunterladen</a></p><p><strong>Mit freundlichen Grüßen,</strong><br><strong>Personalabteilung</strong><br><strong>{company_name}</strong></p>',
                    'en' => '<p><strong>Dear {candidate_name},</strong></p><p>We are pleased to offer you the position of <strong>{position}</strong> at {company_name}.</p><p><strong>Position:</strong> {position}<br><strong>Salary:</strong> {salary}<br><strong>Start Date:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Download Offer Letter</a></p><p><strong>Best regards,</strong><br><strong>HR Department</strong><br><strong>{company_name}</strong></p>',
                    'es' => '<p><strong>Estimado/a {candidate_name},</strong></p><p>Nos complace ofrecerte la posición de <strong>{position}</strong> en {company_name}.</p><p><strong>Posición:</strong> {position}<br><strong>Salario:</strong> {salary}<br><strong>Fecha de inicio:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Descargar Carta de Oferta</a></p><p><strong>Saludos cordiales,</strong><br><strong>Departamento de RRHH</strong><br><strong>{company_name}</strong></p>',
                    'fr' => '<p><strong>Cher/Chère {candidate_name},</strong></p><p>Nous sommes heureux de vous offrir le poste de <strong>{position}</strong> chez {company_name}.</p><p><strong>Poste:</strong> {position}<br><strong>Salaire:</strong> {salary}<br><strong>Date de début:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Télécharger la Lettre d\'Offre</a></p><p><strong>Cordialement,</strong><br><strong>Département RH</strong><br><strong>{company_name}</strong></p>',
                    'it' => '<p><strong>Caro/a {candidate_name},</strong></p><p>Siamo lieti di offrirti la posizione di <strong>{position}</strong> presso {company_name}.</p><p><strong>Posizione:</strong> {position}<br><strong>Stipendio:</strong> {salary}<br><strong>Data di inizio:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Scarica Lettera di Offerta</a></p><p><strong>Cordiali saluti,</strong><br><strong>Dipartimento HR</strong><br><strong>{company_name}</strong></p>',
                    'ja' => '<p><strong>{candidate_name} 様</strong></p><p>{company_name}にて<strong>{position}</strong>のポジションをご提供できることを嬉しく思います。</p><p><strong>ポジション:</strong> {position}<br><strong>給与:</strong> {salary}<br><strong>開始日:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">オファーレターをダウンロード</a></p><p><strong>敬具</strong><br><strong>人事部</strong><br><strong>{company_name}</strong></p>',
                    'nl' => '<p><strong>Beste {candidate_name},</strong></p><p>We zijn verheugd je de positie van <strong>{position}</strong> bij {company_name} aan te bieden.</p><p><strong>Positie:</strong> {position}<br><strong>Salaris:</strong> {salary}<br><strong>Startdatum:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Download Aanbiedingsbrief</a></p><p><strong>Met vriendelijke groet,</strong><br><strong>HR Afdeling</strong><br><strong>{company_name}</strong></p>',
                    'pl' => '<p><strong>Drogi/a {candidate_name},</strong></p><p>Mamy przyjemność zaoferować Ci stanowisko <strong>{position}</strong> w {company_name}.</p><p><strong>Stanowisko:</strong> {position}<br><strong>Wynagrodzenie:</strong> {salary}<br><strong>Data rozpoczęcia:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Pobierz List Ofertowy</a></p><p><strong>Z poważaniem,</strong><br><strong>Dział HR</strong><br><strong>{company_name}</strong></p>',
                    'pt' => '<p><strong>Caro/a {candidate_name},</strong></p><p>Temos o prazer de oferecer-lhe a posição de <strong>{position}</strong> na {company_name}.</p><p><strong>Posição:</strong> {position}<br><strong>Salário:</strong> {salary}<br><strong>Data de início:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Descarregar Carta de Oferta</a></p><p><strong>Cumprimentos,</strong><br><strong>Departamento de RH</strong><br><strong>{company_name}</strong></p>',
                    'pt-BR' => '<p><strong>Caro/a {candidate_name},</strong></p><p>Temos o prazer de oferecer a você a posição de <strong>{position}</strong> na {company_name}.</p><p><strong>Posição:</strong> {position}<br><strong>Salário:</strong> {salary}<br><strong>Data de início:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Baixar Carta de Oferta</a></p><p><strong>Atenciosamente,</strong><br><strong>Departamento de RH</strong><br><strong>{company_name}</strong></p>',
                    'he' => '<p><strong>יקר/ה {candidate_name},</strong></p><p>אנו שמחים להציע לך את התפקיד של <strong>{position}</strong> ב-{company_name}.</p><p><strong>תפקיד:</strong> {position}<br><strong>משכורת:</strong> {salary}<br><strong>תאריך התחלה:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">הורד מכתב הצעה</a></p><p><strong>בברכה,</strong><br><strong>מחלקת משאבי אנוש</strong><br><strong>{company_name}</strong></p>',
                    'tr' => '<p><strong>Sayın {candidate_name},</strong></p><p>{company_name}\'de <strong>{position}</strong> pozisyonunu size teklif etmekten memnuniyet duyuyoruz.</p><p><strong>Pozisyon:</strong> {position}<br><strong>Maaş:</strong> {salary}<br><strong>Başlangıç Tarihi:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Teklif Mektubunu İndir</a></p><p><strong>Saygılarımızla,</strong><br><strong>İK Departmanı</strong><br><strong>{company_name}</strong></p>',
                    'ru' => '<p><strong>Уважаемый/ая {candidate_name},</strong></p><p>Мы рады предложить вам должность <strong>{position}</strong> в {company_name}.</p><p><strong>Должность:</strong> {position}<br><strong>Зарплата:</strong> {salary}<br><strong>Дата начала:</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Скачать письмо-предложение</a></p><p><strong>С уважением,</strong><br><strong>Отдел кадров</strong><br><strong>{company_name}</strong></p>',
                    'zh' => '<p><strong>亲爱的 {candidate_name}，</strong></p><p>我们很高兴为您提供在 {company_name} 担任<strong>{position}</strong>的职位。</p><p><strong>职位：</strong> {position}<br><strong>薪资：</strong> {salary}<br><strong>入职日期：</strong> {start_date}</p><p style="text-align: center; margin: 30px 0;"><a href="{download_url}" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">下载录用通知书</a></p><p><strong>此致敬礼，</strong><br><strong>人力资源部</strong><br><strong>{company_name}</strong></p>',
                ],
            ],

        ];

        foreach($emailTemplate as $eTemp)
        {
            $table = EmailTemplate::where('name',$eTemp)->where('module_name','Recruitment')->exists();
            if(!$table)
            {
                $emailtemplate = EmailTemplate::create([
                    'name' => $eTemp,
                    'from' => !empty(env('APP_NAME')) ? env('APP_NAME') : 'ERPGo SaaS',
                    'module_name' => 'Recruitment',
                    'created_by' => $admin->id,
                    'creator_id' => $admin->id,
                ]);

                foreach($defaultTemplate[$eTemp]['lang'] as $lang => $content)
                {
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