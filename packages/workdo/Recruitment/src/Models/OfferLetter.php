<?php

namespace Workdo\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class OfferLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'lang',
        'content',
        'creator_id',
        'created_by',
    ];

    public static function getLanguages()
    {
        return [
            'en' => ['name' => 'English', 'countryCode' => 'GB'],
            'es' => ['name' => 'Español', 'countryCode' => 'ES'],
            'ar' => ['name' => 'العربية', 'countryCode' => 'SA'],
            'da' => ['name' => 'Dansk', 'countryCode' => 'DK'],
            'de' => ['name' => 'Deutsch', 'countryCode' => 'DE'],
            'fr' => ['name' => 'Français', 'countryCode' => 'FR'],
            'he' => ['name' => 'עברית', 'countryCode' => 'IL'],
            'it' => ['name' => 'Italiano', 'countryCode' => 'IT'],
            'ja' => ['name' => '日本語', 'countryCode' => 'JP'],
            'nl' => ['name' => 'Nederlands', 'countryCode' => 'NL'],
            'pl' => ['name' => 'Polski', 'countryCode' => 'PL'],
            'pt' => ['name' => 'Português', 'countryCode' => 'PT'],
            'pt-BR' => ['name' => 'Português do Brasil', 'countryCode' => 'BR'],
            'ru' => ['name' => 'Русский', 'countryCode' => 'RU'],
            'tr' => ['name' => 'Türkçe', 'countryCode' => 'TR'],
            'zh' => ['name' => '中文', 'countryCode' => 'CN']
        ];
    }

    public static function replaceVariable($content, $obj)
    {
        $arrVariable = [
            '{applicant_name}',
            '{app_name}',
            '{company_name}',
            '{job_title}',
            '{job_type}',
            '{start_date}',
            '{workplace_location}',
            '{days_of_week}',
            '{salary}',
            '{salary_type}',
            '{salary_duration}',
            '{next_pay_period}',
            '{offer_expiration_date}',
        ];

        $arrValue = [
            'applicant_name' => '-',
            'app_name' => '-',
            'company_name' => '-',
            'job_title' => '-',
            'job_type' => '-',
            'start_date' => '-',
            'workplace_location' => '-',
            'days_of_week' => '-',
            'salary' => '-',
            'salary_type' => '-',
            'salary_duration' => '-',
            'next_pay_period' => '-',
            'offer_expiration_date' => '-',
        ];

        foreach ($obj as $key => $val) {
            $arrValue[$key] = $val;
        }

        $arrValue['app_name'] = env('APP_NAME');
        if (is_null($arrValue['company_name']) || $arrValue['company_name'] == '-') {
            $companySettings = getCompanyAllSetting();
            $arrValue['company_name'] = $companySettings['company_name'] ?? '--';
        }

        return str_replace($arrVariable, array_values($arrValue), $content);
    }

    public static function defaultOfferLetter($company_id = null)
    {
        $defaultTemplate = [
            'en' => '<p style="text-align: center;"><strong>Offer Letter</strong></p><p>Dear {applicant_name},</p><p>{app_name} is excited to bring you on board as {job_title}.</p><p>Were just a few formalities away from getting down to work. Please take the time to review our formal offer. It includes important details about your compensation, benefits, and the terms and conditions of your anticipated employment with {app_name}.</p><p>{app_name} is offering a {job_type}. position for you as {job_title}, reporting to [immediate manager/supervisor] starting on {start_date} at{workplace_location}. Expected hours of work are{days_of_week}.</p><p>In this position, {app_name} is offering to start you at a pay rate of {salary} per {salary_type}. You will be paid on a{salary_duration} basis.&nbsp;</p><p>As part of your compensation, were also offering [if applicable, youll describe your bonus, profit sharing, commission structure, stock options, and compensation committee rules here].</p><p>As an employee of {app_name} , you will be eligible for briefly name benefits, such as health insurance, stock plan, dental insurance, etc.</p><p>Please indicate your agreement with these terms and accept this offer by signing and dating this agreement on or before {offer_expiration_date}.</p><p>Sincerely,</p><p>{app_name}</p>',
            'es' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>Carta de oferta</strong></span></p><p>Estimado {applicant_name},</p><p>{app_name} se complace en incorporarlo como {job_title}.</p><p>Faltaban sólo unos trámites para ponerse manos a la obra. Tómese el tiempo para revisar nuestra oferta formal. Incluye detalles importantes sobre su compensación, beneficios y los términos y condiciones de su empleo anticipado con {app_name}.</p><p>{app_name} está ofreciendo {job_type}. posición para usted como {job_title}, reportando al gerente/supervisor inmediato a partir del {start_date} en {workplace_location}. Las horas de trabajo esperadas son {days_of_week}.</p><p>En este puesto, {app_name} te ofrece comenzar con una tarifa de pago de {salary} por {salary_type}. Se le pagará sobre la base de {salary_duration}.</p><p>Como parte de su compensación, también ofrecemos, si corresponde, aquí describirá su bonificación, participación en las ganancias, estructura de comisiones, opciones sobre acciones y reglas del comité de compensación.</p><p>Como empleado de {app_name}, será elegible para beneficios de nombre breve, como seguro médico, plan de acciones, seguro dental, etc.</p><p>Indique su acuerdo con estos términos y acepte esta oferta firmando y fechando este acuerdo el {offer_expiration_date} o antes.</p><p>Sinceramente,</p><p>{app_name}</p>',
            'ar' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>خطاب العرض</strong></span></p><p>عزيزي {applicant_name}،</p><p>{app_name} متحمس لإحضارك على متن الطائرة كـ {job_title}.</p><p>كانت مجرد بعض الإجراءات الشكلية بعيدا عن النزول للعمل. يرجى أخذ الوقت لمراجعة عرضنا الرسمي. يتضمن تفاصيل مهمة حول تعويضك ومزاياك وشروط وأحكام عملك المتوقع مع {app_name}.</p><p>{app_name} يقدم {job_type}. منصب لك كـ {job_title}، تقرير إلى [المدير المباشر/المشرف] بدءا من {start_date} في {workplace_location}. ساعات العمل المتوقعة هي {days_of_week}.</p><p>في هذا المنصب، {app_name} يعرض أن يبدأك بمعدل راتب {salary} لكل {salary_type}. سيتم دفع راتبك على أساس {salary_duration}.</p><p>كجزء من تعويضك، نحن نقدم أيضا [إذا كان ذلك قابلا للتطبيق، ستصف هنا مكافأتك، تقاسم الأرباح، هيكل العمولة، خيارات الأسهم، وقواعد لجنة التعويض].</p><p>كموظف في {app_name}، ستكون مؤهلا للحصول على مزايا اسم موجز، مثل التأمين الصحي، خطة الأسهم، تأمين الأسنان، إلخ.</p><p>يرجى الإشارة إلى موافقتك على هذه الشروط وقبول هذا العرض من خلال التوقيع وتأريخ هذه الاتفاقية في أو قبل {offer_expiration_date}.</p><p>بإخلاص،</p><p>{app_name}</p>',
            'da' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>Tilbudsbrev</strong></span></p><p>Kære {applicant_name},</p><p>{app_name} er begejstret for at bringe dig ombord som {job_title}.</p><p>Var bare et par formaliteter væk fra at komme ned til arbejde. Tag dig tid til at gennemgå vores formelle tilbud. Det indeholder vigtige detaljer om din kompensation, fordele og vilkårene for din forventede ansættelse hos {app_name}.</p><p>{app_name} tilbyder en {job_type}. stilling for dig som {job_title}, rapporterer til [umiddelbar leder/supervisor] starter den {start_date} på {workplace_location}. Forventede arbejdstimer er {days_of_week}.</p><p>I denne stilling tilbyder {app_name} at starte dig med en lønsats på {salary} pr. {salary_type}. Du vil blive betalt på {salary_duration} basis.</p><p>Som en del af din kompensation tilbyder vi også [hvis det er relevant, vil du beskrive din bonus, overskudsdeling, provisionsstruktur, aktieoptioner og kompensationsudvalgets regler her].</p><p>Som ansat hos {app_name} vil du være berettiget til kort navngivne fordele, såsom sygeforsikring, aktieplan, tandlægeforsikring osv.</p><p>Angiv venligst dit samtykke til disse vilkår og accepter dette tilbud ved at underskrive og datere denne aftale på eller før {offer_expiration_date}.</p><p>Med venlig hilsen,</p><p>{app_name}</p>',
            'fr' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>Lettre doffre</strong></span></p><p>Cher {applicant_name},</p><p>{app_name} est ravi de vous accueillir en tant que {job_title}.</p><p>Étaient juste quelques formalités loin de se mettre au travail. Veuillez prendre le temps dexaminer notre offre formelle. Il comprend des détails importants sur votre rémunération, vos avantages et les termes et conditions de votre emploi prévu avec {app_name}.</p><p>{app_name} propose un {job_type}. poste pour vous en tant que {job_title}, relevant du directeur/superviseur immédiat à partir du {start_date} à {workplace_location}. Les heures de travail prévues sont de {days_of_week}.</p><p>À ce poste, {app_name} vous propose de commencer avec un taux de rémunération de {salary} par {salary_type}. Vous serez payé sur une base de {salary_duration}.</p><p>Dans le cadre de votre rémunération, le cas échéant, vous décrivez ici votre bonus, votre participation aux bénéfices, votre structure de commission, vos options sur actions et les règles du comité de rémunération.</p><p>En tant quemployé de {app_name}, vous aurez droit à des avantages brièvement nommés, tels que lassurance maladie, le plan dactionnariat, lassurance dentaire, etc.</p><p>Veuillez indiquer votre accord avec ces conditions et accepter cette offre en signant et en datant cet accord au plus tard le {offer_expiration_date}.</p><p>Sincèrement,</p><p>{app_name}</p>',
            'de' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>Angebotsschreiben</strong></span></p><p>Sehr geehrter {applicant_name},</p><p>{app_name} freut sich, Sie als {job_title} an Bord zu holen.</p><p>Nur noch wenige Formalitäten bis zur Arbeit. Bitte nehmen Sie sich die Zeit, unser formelles Angebot zu prüfen. Es enthält wichtige Details zu Ihrer Vergütung, Ihren Leistungen und den Bedingungen Ihrer voraussichtlichen Anstellung bei {app_name}.</p><p>{app_name} bietet einen {job_type} an. Position für Sie als {job_title}, ab {start_date} am {workplace_location} unterstellt an unmittelbarer Manager/Vorgesetzter. Erwartete Arbeitszeiten sind {days_of_week}.</p><p>In dieser Position bietet {app_name} Ihnen an, mit einem Gehaltssatz von {salary} pro {salary_type} zu beginnen. Sie werden auf Basis von {salary_duration} bezahlt.</p><p>Als Teil Ihrer Vergütung, die Sie gegebenenfalls auch anbieten, beschreiben Sie hier Ihren Bonus, Ihre Gewinnbeteiligung, Ihre Provisionsstruktur, Ihre Aktienoptionen und die Regeln des Vergütungsausschusses.</p><p>Als Mitarbeiter von {app_name} haben Sie Anspruch auf Kurznamenvorteile wie Krankenversicherung, Aktienplan, Zahnversicherung usw.</p><p>Bitte erklären Sie Ihr Einverständnis mit diesen Bedingungen und nehmen Sie dieses Angebot an, indem Sie diese Vereinbarung am oder vor dem {offer_expiration_date} unterzeichnen und datieren.</p><p>Aufrichtig,</p><p>{app_name}</p>',
            'he' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>מכתב הצעה</strong></span></p><p>יקר {applicant_name},</p><p>{app_name} נרגש להביא אותך על הסיפון כ {job_title}.</p><p>היו רק כמה פורמליות הרחק מלרדת לעבודה. אנא הקדישו זמן לבדוק את ההצעה הפורמלית שלנו. הוא כולל פרטים חשובים על הפיצוי שלך, יתרונות, ותנאים של העסקה צפויה שלך עם {app_name}.</p><p>{app_name} מציע {job_type}. עמדה עבורך כ {job_title}, דיווח ל [מנהל מיידי/מפקח] החל מ {start_date} ב {workplace_location}. שעות עבודה צפויות הן {days_of_week}.</p><p>בתפקיד זה, {app_name} מציע להתחיל אותך בשיעור שכר של {salary} לכל {salary_type}. תקבל תשלום על בסיס {salary_duration}.</p><p>כחלק מהפיצוי שלך, אנחנו גם מציעים [אם רלוונטי, תתאר כאן את הבונוס שלך, חלוקת רווחים, מבנה עמלה, אופציות מניות, וכללי ועדת פיצויים].</p><p>כעובד של {app_name}, תהיה זכאי ליתרונות שם קצר, כגון ביטוח בריאות, תוכנית מניות, ביטוח שיניים, וכו.</p><p>אנא ציין את הסכמתך לתנאים אלה וקבל הצעה זו על ידי חתימה ותיארוך הסכם זה ב או לפני {offer_expiration_date}.</p><p>בכבוד רב,</p><p>{app_name}</p>',
            'it' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>Lettera di offerta</strong></span></p><p>Caro {applicant_name},</p><p>{app_name} è entusiasta di portarti a bordo come {job_title}.</p><p>Erano solo poche formalità lontane dal mettersi al lavoro. Si prega di prendere il tempo per rivedere la nostra offerta formale. Include dettagli importanti sulla tua compensazione, benefici e i termini e condizioni del tuo impiego previsto con {app_name}.</p><p>{app_name} sta offrendo un {job_type}. posizione per te come {job_title}, segnalazione a [manager immediato/supervisore] a partire dal {start_date} a {workplace_location}. Le ore di lavoro previste sono {days_of_week}.</p><p>In questa posizione, {app_name} si offre di iniziare con un tasso di retribuzione di {salary} per {salary_type}. Sarai pagato su base {salary_duration}.</p><p>Come parte della tua compensazione, stiamo anche offrendo [se applicabile, descriverai qui il tuo bonus, condivisione dei profitti, struttura delle commissioni, opzioni azionarie e regole del comitato di compensazione].</p><p>Come dipendente di {app_name}, sarai idoneo per benefici di nome breve, come assicurazione sanitaria, piano azionario, assicurazione dentale, ecc.</p><p>Si prega di indicare il vostro accordo con questi termini e accettare questa offerta firmando e datando questo accordo entro o prima {offer_expiration_date}.</p><p>Cordialmente,</p><p>{app_name}</p>',
            'ja' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>オファーレター</strong></span></p><p>親愛なる {applicant_name}、</p><p>{app_name} は {job_title} としてあなたを迎えることを楽しみにしています。</p><p>仕事に取り掛かるまでのいくつかの手続きだけでした。私たちの正式なオファーを確認する時間を取ってください。{app_name} での予想される雇用の報酬、福利厚生、および条件に関する重要な詳細が含まれています。</p><p>{app_name} は {job_type} を提供しています。{job_title} としてのあなたのポジション、{start_date} から {workplace_location} で [直属のマネージャー/スーパーバイザー] に報告します。予想される勤務時間は {days_of_week} です。</p><p>このポジションでは、{app_name} は {salary_type} あたり {salary} の給与率であなたを開始することを提案しています。{salary_duration} ベースで支払われます。</p><p>あなたの報酬の一部として、私たちは [該当する場合、ここであなたのボーナス、利益分配、手数料構造、ストックオプション、および報酬委員会の規則を説明します] も提供しています。</p><p>{app_name} の従業員として、健康保険、株式プラン、歯科保険などの簡潔な名前の福利厚生を受ける資格があります。</p><p>{offer_expiration_date} までにこの契約に署名し、日付を記入することで、これらの条件に同意し、このオファーを受け入れることを示してください。</p><p>敬具、</p><p>{app_name}</p>',
            'nl' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>Aanbiedingsbrief</strong></span></p><p>Beste {applicant_name},</p><p>{app_name} is opgewonden om je aan boord te brengen als {job_title}.</p><p>Waren slechts een paar formaliteiten weg van het aan het werk gaan. Neem de tijd om ons formele aanbod te bekijken. Het bevat belangrijke details over uw compensatie, voordelen en de voorwaarden van uw verwachte dienstverband bij {app_name}.</p><p>{app_name} biedt een {job_type} aan. positie voor jou als {job_title}, rapporterend aan [directe manager/supervisor] vanaf {start_date} op {workplace_location}. Verwachte werkuren zijn {days_of_week}.</p><p>In deze functie biedt {app_name} aan om je te starten met een loontarief van {salary} per {salary_type}. Je wordt betaald op {salary_duration} basis.</p><p>Als onderdeel van uw compensatie bieden we ook [indien van toepassing, beschrijft u hier uw bonus, winstdeling, commissiestructuur, aandelenopties en compensatiecommissieregels].</p><p>Als werknemer van {app_name} kom je in aanmerking voor kort genoemde voordelen, zoals ziektekostenverzekering, aandelenplan, tandverzekering, enz.</p><p>Geef uw akkoord met deze voorwaarden aan en accepteer dit aanbod door deze overeenkomst te ondertekenen en te dateren op of voor {offer_expiration_date}.</p><p>Met vriendelijke groet,</p><p>{app_name}</p>',
            'pl' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>List ofertowy</strong></span></p><p>Drogi {applicant_name},</p><p>{app_name} jest podekscytowany, aby wprowadzić cię na pokład jako {job_title}.</p><p>Były tylko kilka formalności z dala od przystąpienia do pracy. Proszę poświęcić czas na przegląd naszej formalnej oferty. Zawiera ważne szczegóły dotyczące wynagrodzenia, korzyści oraz warunków przewidywanego zatrudnienia w {app_name}.</p><p>{app_name} oferuje {job_type}. stanowisko dla ciebie jako {job_title}, raportowanie do [bezpośredni menedżer/przełożony] począwszy od {start_date} w {workplace_location}. Oczekiwane godziny pracy to {days_of_week}.</p><p>Na tym stanowisku {app_name} oferuje rozpoczęcie z stawką wynagrodzenia {salary} za {salary_type}. Będziesz płacony na podstawie {salary_duration}.</p><p>W ramach wynagrodzenia oferujemy również [jeśli dotyczy, opiszesz tutaj swój bonus, udział w zyskach, strukturę prowizji, opcje na akcje i zasady komitetu wynagrodzeń].</p><p>Jako pracownik {app_name} będziesz uprawniony do krótko nazwanych korzyści, takich jak ubezpieczenie zdrowotne, plan akcyjny, ubezpieczenie dentystyczne itp.</p><p>Proszę wskazać zgodę na te warunki i zaakceptować tę ofertę, podpisując i datując tę umowę w dniu lub przed {offer_expiration_date}.</p><p>Z poważaniem,</p><p>{app_name}</p>',
            'pt' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>Carta de oferta</strong></span></p><p>Caro {applicant_name},</p><p>{app_name} está animado para trazê-lo a bordo como {job_title}.</p><p>Eram apenas algumas formalidades longe de começar a trabalhar. Por favor, reserve um tempo para revisar nossa oferta formal. Inclui detalhes importantes sobre sua compensação, benefícios e os termos e condições de seu emprego antecipado com {app_name}.</p><p>{app_name} está oferecendo um {job_type}. posição para você como {job_title}, reportando ao [gerente imediato/supervisor] começando em {start_date} em {workplace_location}. As horas de trabalho esperadas são {days_of_week}.</p><p>Nesta posição, {app_name} está oferecendo para começar você com uma taxa de pagamento de {salary} por {salary_type}. Você será pago em base {salary_duration}.</p><p>Como parte de sua compensação, também estamos oferecendo [se aplicável, você descreverá aqui seu bônus, participação nos lucros, estrutura de comissão, opções de ações e regras do comitê de compensação].</p><p>Como funcionário da {app_name}, você será elegível para benefícios de nome breve, como seguro de saúde, plano de ações, seguro dentário, etc.</p><p>Por favor, indique sua concordância com estes termos e aceite esta oferta assinando e datando este acordo em ou antes de {offer_expiration_date}.</p><p>Atenciosamente,</p><p>{app_name}</p>',
            'pt-BR' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>Carta de oferta</strong></span></p><p>Caro {applicant_name},</p><p>{app_name} está animado para trazê-lo a bordo como {job_title}.</p><p>Eram apenas algumas formalidades longe de começar a trabalhar. Por favor, reserve um tempo para revisar nossa oferta formal. Inclui detalhes importantes sobre sua compensação, benefícios e os termos e condições de seu emprego antecipado com {app_name}.</p><p>{app_name} está oferecendo um {job_type}. posição para você como {job_title}, reportando ao [gerente imediato/supervisor] começando em {start_date} em {workplace_location}. As horas de trabalho esperadas são {days_of_week}.</p><p>Nesta posição, {app_name} está oferecendo para começar você com uma taxa de pagamento de {salary} por {salary_type}. Você será pago em base {salary_duration}.</p><p>Como parte de sua compensação, também estamos oferecendo [se aplicável, você descreverá aqui seu bônus, participação nos lucros, estrutura de comissão, opções de ações e regras do comitê de compensação].</p><p>Como funcionário da {app_name}, você será elegível para benefícios de nome breve, como seguro de saúde, plano de ações, seguro dentário, etc.</p><p>Por favor, indique sua concordância com estes termos e aceite esta oferta assinando e datando este acordo em ou antes de {offer_expiration_date}.</p><p>Atenciosamente,</p><p>{app_name}</p>',
            'ru' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>Письмо-предложение</strong></span></p><p>Дорогой {applicant_name},</p><p>{app_name} рад привести вас на борт в качестве {job_title}.</p><p>Были всего лишь несколько формальностей от того, чтобы приступить к работе. Пожалуйста, найдите время, чтобы рассмотреть наше формальное предложение. Он включает важные детали о вашей компенсации, льготах и условиях вашей ожидаемой занятости с {app_name}.</p><p>{app_name} предлагает {job_type}. позицию для вас как {job_title}, отчетность перед [непосредственный менеджер/супервайзер] начиная с {start_date} в {workplace_location}. Ожидаемые часы работы {days_of_week}.</p><p>На этой позиции {app_name} предлагает начать вас с оплатой {salary} за {salary_type}. Вам будут платить на основе {salary_duration}.</p><p>В рамках вашей компенсации мы также предлагаем [если применимо, вы опишете здесь свой бонус, участие в прибыли, структуру комиссии, опционы на акции и правила комитета по компенсации].</p><p>Как сотрудник {app_name}, вы будете иметь право на кратко названные льготы, такие как медицинское страхование, план акций, стоматологическое страхование и т.д.</p><p>Пожалуйста, укажите ваше согласие с этими условиями и примите это предложение, подписав и датировав это соглашение в или до {offer_expiration_date}.</p><p>Искренне,</p><p>{app_name}</p>',
            'tr' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>Teklif Mektubu</strong></span></p><p>Sevgili {applicant_name},</p><p>{app_name} sizi {job_title} olarak gemiye almaktan heyecan duyuyor.</p><p>İşe koyulmaktan sadece birkaç formalite uzaktaydı. Lütfen resmi teklifimizi gözden geçirmek için zaman ayırın. {app_name} ile beklenen istihdamınızın tazminatı, faydaları ve şartları hakkında önemli ayrıntıları içerir.</p><p>{app_name} bir {job_type} sunuyor. {job_title} olarak sizin için pozisyon, {start_date} tarihinde {workplace_location} adresinde [acil müdür/süpervizör] raporlama. Beklenen çalışma saatleri {days_of_week}.</p><p>Bu pozisyonda, {app_name} sizi {salary_type} başına {salary} ödeme oranıyla başlatmayı teklif ediyor. {salary_duration} bazında ödeneceksiniz.</p><p>Tazminatınızın bir parçası olarak, ayrıca [uygulanabilirse, burada bonusunuzu, kar paylaşımınızı, komisyon yapınızı, hisse senedi seçeneklerinizi ve tazminat komitesi kurallarınızı açıklayacaksınız] sunuyoruz.</p><p>{app_name} çalışanı olarak, sağlık sigortası, hisse senedi planı, diş sigortası vb. gibi kısaca adlandırılan faydalar için uygun olacaksınız.</p><p>Lütfen bu şartlarla anlaşmanızı belirtin ve {offer_expiration_date} tarihinde veya öncesinde bu anlaşmayı imzalayarak ve tarihleyerek bu teklifi kabul edin.</p><p>Saygılarımla,</p><p>{app_name}</p>',
            'zh' => '<p style="text-align: center;"><span style="font-size: 18pt;"><strong>录用通知书</strong></span></p><p>亲爱的 {applicant_name}，</p><p>{app_name} 很高兴让您作为 {job_title} 加入我们。</p><p>距离开始工作只有几个手续了。请花时间审查我们的正式报价。它包括关于您的薪酬、福利以及您在 {app_name} 预期就业条款和条件的重要细节。</p><p>{app_name} 正在提供 {job_type}。作为 {job_title} 的职位，从 {start_date} 开始在 {workplace_location} 向 [直接经理/主管] 报告。预期工作时间为 {days_of_week}。</p><p>在这个职位上，{app_name} 提议以每 {salary_type} {salary} 的薪酬率开始您的工作。您将按 {salary_duration} 基础获得报酬。</p><p>作为您薪酬的一部分，我们还提供 [如果适用，您将在此描述您的奖金、利润分享、佣金结构、股票期权和薪酬委员会规则]。</p><p>作为 {app_name} 的员工，您将有资格获得简要命名的福利，如健康保险、股票计划、牙科保险等。</p><p>请通过在 {offer_expiration_date} 或之前签署并注明日期来表明您对这些条款的同意并接受此报价。</p><p>此致敬礼，</p><p>{app_name}</p>'
        ];

        foreach ($defaultTemplate as $lang => $content) {
            $offerletter = OfferLetter::where('lang', $lang)->where('created_by', $company_id)->first();
            if (empty($offerletter)) {
                OfferLetter::create([
                    'lang' => $lang,
                    'content' => $content,
                    'creator_id' => Auth::id(),
                    'created_by' => $company_id,
                ]);
            }
        }
    }
}