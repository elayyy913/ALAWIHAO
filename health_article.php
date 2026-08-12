<?php
session_start();
include 'db_connect.php';
// if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$slug = $_GET['topic'] ?? '';

$articles = [
'warning-signs' => [
    'title' => 'Mga Babala Habang Buntis',
    'intro' => 'Nanay, kung maramdaman o mapansin ang alin man sa mga ito, pumunta agad sa health center!',
    'sections' => [
        ['heading' => 'Mga Palatandaan na Dapat Pansinin', 'type' => 'list', 'items' => [
            'Pamamaga o pamamanas ng mga binti, kamay, o mukha',
            'Sakit ng ulo, pagkahilo, o panlalabo ng paningin',
            'Pagdurugo ng puwerta',
            'Pamumutla', 'Lagnat', 'Pagsusuka', 'Hirap na paghinga',
            'Mahapdi na pag-ihi',
            'Malabnaw/mala-tubig na lumalabas sa puwerta',
            'Pagbagal o hindi paggalaw ng bata sa tiyan sa ika-2 trimester ng pagbubuntis (mas mababa sa 10 sipa sa loob ng 12 oras)',
            'Matinding pananakit ng ulo na may kasamang panlalabo ng paningin',
            'Matinding pananakit ng tiyan',
            'Maagang pagputok ng panubigan',
        ]]
    ]
],
'prenatal-checkup' => [
    'title' => 'Pre-natal Check-up',
    'intro' => 'Alamin ang mga dapat mangyari sa bawat check-up at siguraduhing ito ay magagampanan ng iyong health service provider.',
    'sections' => [
        ['heading' => 'Schedule ng Check-up', 'type' => 'table', 'rows' => [
            ['Bilang', 'Kailan'],
            ['Una', 'Mula pagtigil ng regla hanggang ika-3 buwan ng pagbubuntis'],
            ['Pangalawa', 'Mula ika-4 hanggang ika-6 na buwan ng pagbubuntis'],
            ['Pangatlo', 'Mula ika-7 hanggang ika-8 na buwan ng pagbubuntis'],
            ['Pang-apat', 'Ika-9 na buwan ng pagbubuntis'],
        ]],
        ['heading' => 'Ano ang nangyayari pag nagpapa-pre-natal check-up?', 'type' => 'list', 'items' => [
            'Kukunin ang iyong medical at pregnancy history.',
            'Susuriin ang iyong katawan.',
            'Kukunin ang iyong blood pressure (BP), taas, at timbang.',
            'Aalamin ang iyong nutritional status.',
            'Gagawan ka ng laboratory tests.',
        ]],
        ['heading' => 'Para mapanatiling malusog kayo ni baby:', 'type' => 'list', 'items' => [
            'Bibigyan ka ng tableta ng iron na may Folic Acid at Calcium Carbonate. Bibigyan ka rin ng 2 Iodine capsules na iinumin mo sa bahay, kung ikaw ay nasa ika-4 na buwan ng iyong pagbubuntis.',
            'Babakunahan ka laban sa tetano.',
            'Papayuhan ka tungkol sa tamang nutrisyon at pagkain, malusog na pamumuhay, paggawa ng birth plan, pagpapasuso, at pagpaplano ng pamilya.',
        ]],
    ]
],
'pregnancy-dos' => [
    'title' => "Pregnancy Do's",
    'intro' => 'Makinig sa mga payong makabubuti sa iyo at sa dinadalang anak.',
    'sections' => [
        ['heading' => 'Mga Dapat Gawin', 'type' => 'list', 'items' => [
            'Maghanda para sa eksklusibong pagpapasuso ng anak. Alamin ang tamang paraan.',
            'Kumain nang tama at siguraduhing may sapat na sustansya at bitamina.',
            'Mag-ehersisyo nang angkop.',
            'Siguraduhing may sapat na tulog at pahinga.',
            'Maghanda ng mga sumusunod para sa posibleng emergency: pera, pagkukuhanan ng dugo, at transportasyon.',
            'Ugaliin uminom ng tubig araw-araw.',
        ]]
    ]
],
'pregnancy-donts' => [
    'title' => "Pregnancy Don'ts",
    'intro' => 'Iwasan ang mga bagay na maaaring makapinsala sa iyo at sa iyong sanggol.',
    'sections' => [
        ['heading' => 'Mga Dapat Iwasan', 'type' => 'list', 'items' => [
            'Umiwas sa mga pagkaing maalat.',
            'Huwag iinom ng gamot para sa anumang karamdaman nang walang pahintulot ng doktor.',
            'Iwasan ang masasamang bisyo gaya ng paninigarilyo at pag-inom ng anumang may alcohol.',
        ]]
    ]
],
'pamahiin' => [
    'title' => 'Mga Pamahiin: Sabi-sabi vs. Totoo',
    'intro' => 'Huwag basta maniwala sa mga sabi-sabi tungkol sa pagbubuntis. Alamin ang totoo at kumonsulta sa mga health service providers.',
    'sections' => [
        ['heading' => 'Sabi-sabi #1', 'type' => 'myth', 'myth' => 'Kapag ang leeg at singit ni nanay ay maitim habang buntis, siguradong lalaki ang anak.', 'truth' => 'Ang pangingilim ng leeg, singit, o iba pang bahagi ng katawan ng buntis ay dahil sa mga hormones na nagbabago o mas dumarami habang buntis. Hindi ibig sabihin nito na lalaki ang magiging anak. Panatilihing malinis at maligo araw-araw.'],
        ['heading' => 'Sabi-sabi #2', 'type' => 'myth', 'myth' => 'Kapag kumain ng kambal na saging ang buntis, kambal din ang magiging anak.', 'truth' => 'Ang kasarian ng baby ay natutukoy na agad sa sandaling magtagpo ang itlog ng babae at punlay ng lalaki. Ang pagkain ng kambal na saging ay hindi makaaapekto dito. Ang saging ay mayaman sa potassium na makatutulong sa normal na paggana ng puso, kidney, at iba pang organs ng ina.'],
        ['heading' => 'Sabi-sabi #3', 'type' => 'myth', 'myth' => 'Naglalagas ang ngipin sa bawat pagbubuntis!', 'truth' => 'Hindi ito totoo. Basta may sapat na Calcium sa katawan makukuha ng dinadalang sanggol ang kailangang Calcium mula sa buto ng nanay, hindi sa kanyang ngipin. Kaya dapat kumain ang buntis ng mga pagkaing sagana sa Calcium gaya ng keso, gatas, sardinas, okra, orange, avocado, atbp.'],
        ['heading' => 'Sabi-sabi #4', 'type' => 'myth', 'myth' => 'Bawal dumalaw sa may sakit o pumunta sa mga burol ang buntis.', 'truth' => 'Ang lumang kasabihang ito ay base sa praktikal na dahilan at hindi sa pamahiin. Kapag ang dadalawing ay nasa ospital, dapat umiwas ang buntis dahil maaaring makasagap ng kung anong sakit sa maselan niyang kondisyon. Gayun din sa mga burol o lamay, dahil madaming tao ang nasa ibang lugar.'],
        ['heading' => 'Sabi-sabi #5', 'type' => 'myth', 'myth' => 'Huwag makikipagtalik habang buntis – baka mabutas ang inunan!', 'truth' => 'Walang katotohanan ito! Sa katunayan, ang mga buntis ay maaaring magkaroon ng mas mataas na libido dahil sa pagbabago ng hormones sa katawan. Hindi bawal ang makipagtalik, at hindi aabot ang ari sa inunan. Siguraduhin lamang na magpa-check-up sa doktor para malaman ang mga dapat pag-ingatan.'],
        ['heading' => 'Sabi-sabi #6', 'type' => 'myth', 'myth' => 'Huwag kakain ng marami dahil baka lumaki nang masyado ang sanggol sa sinapupunan at mahirapan manganak.', 'truth' => 'Habang nagbubuntis, ang sanggol na dinadala ay umaasa sa iyong katawan para sa sapat na nutrisyon. Kung ang buntis ay hindi kakain nang sapat, bukod sa maaaring manghina, malaki ang posibilidad na mababa ang timbang ng anak sa pagsilang.'],
    ]
],
'baby-growth' => [
    'title' => 'Ang Paglaki ni Baby sa Sinapupunan ni Nanay',
    'intro' => 'Nanay, ito ang iyong buwanang patnubay sa paglaki ni baby sa loob ng iyong sinapupunan. Anuman ang iyong kainin at gawin ay maaaring makaapekto sa tamang paglaki at paghubog ni baby.',
    'sections' => [
        ['heading' => '0-4 na Linggo', 'type' => 'text', 'content' => 'Ang sukat ni baby ay 2 milimetro ang haba. Nagsisimula nang mahubog ang kanyang utak, gulugod, at mukha. Iwasan ang mga gamot na makakaapekto sa kanya. Tumingin sa magagandang larawan at tanawin.'],
        ['heading' => '4-8 na Linggo', 'type' => 'text', 'content' => 'Ang puso ay nagsisimula nang tumibok at ang iba\'t ibang bahagi ng katawan ay nabubuo. Nagsisimula nang magkaroon ng hubog ang kanyang mukha, mata, at ang mga daliri sa kamay at paa. Makinig ng kaaya-ayang musika. Kumain ng iba\'t ibang uri ng pagkain tulad ng karne, isda, dilaw at luntiang gulay, at prutas. Huwag kumain nang higit sa nararapat sapagkat maaaring sumobra ang iyong timbang.'],
        ['heading' => '8-12 na Linggo', 'type' => 'text', 'content' => 'Ang mga pangunahing bahagi ng katawan ay nahubog na. Ang ulo ay malaki kung ikukumpara sa katawan upang mabigyang puwang ang paglaki ng utak. Mayroon nang baba, ilong, at talukap ng mata. Nakalutang si baby sa tubig ng bahay bata. Huwag kalimutang uminom ng iron, Folic Acid, at Calcium Carbonate supplements. Gumamit ng Iodized salt sa iyong pagluto. Huwag kumain nang higit sa nararapat sapagkat maaaring sumobra ang iyong timbang.'],
    ]
],
'newborn-care' => [
    'title' => 'Pangangalaga sa Bagong Silang na Sanggol',
    'intro' => 'Ang mga sumusunod ay ang mga ibinigay na payo sa paraan ng pag-aalaga sa sanggol.',
    'sections' => [
        ['heading' => 'Mga Dapat Gawin', 'type' => 'list', 'items' => [
            'Iwasang malamigan ang sanggol sa pamamagitan ng paglalapat ng katawan ng ina at baby (Unang Yakap/Skin-to-Skin Contact) at pagbabalot ng sanggol sa kumot pagkapanganak.',
            'Sabunin at hugasang mabuti ang mga kamay bago hawakan ang baby, lalo na matapos magpalit ng lampin/diaper ng bata.',
            'Ipagpaliban ang pagpapaligo sa baby nang kahit mga 6 na oras man lamang matapos ipanganak.',
            'Hayaang walang takip ang pusod ng baby hanggang sa ito ay matuyo. Hindi dapat lagyan nang anumang bagay ang pusod ng baby. Makalipas ang 7 hanggang 10 araw, kusang matatanggal na ito.',
            'Dapat ipasuri ang baby kaagad sa health center o ospital kapag may masamang amoy o namumula ang pusod.',
            'Ilayo ang baby sa usok o sa anumang mapanganib na bagay.',
            'Pasususuhin nang madalas at matagal hanggat gusto ng sanggol.',
        ]],
        ['heading' => 'Ipasuri ang baby kung:', 'type' => 'list', 'items' => [
            'Ayaw o humina ang pagsuso',
            'May masamang amoy na nagmumula sa pusod',
            'Nilalagnat (Temperatura ≥ 37.8°C)',
            'Naninigas/nagkukumbulsyon',
            'Mabilis/hirap sa paghinga',
            'Naninilaw ang balat',
        ]],
    ]
],
'unang-linggo' => [
    'title' => 'Ang Mga Pangangailangan ni Baby sa Unang Linggo ng Pagsilang',
    'intro' => 'Ang unang linggo ng buhay ni baby ay napakahalaga. Narito ang mga dapat malaman ng bawat nanay.',
    'sections' => [
        ['heading' => 'Pagpapanatili ng Init', 'type' => 'text', 'content' => 'Panatilihin ang init sa katawan ni baby sa pamamagitan ng skin-to-skin contact. Balutin sa kumot si baby upang di malamigan at mapanatili ang init ng kanyang katawan. Upang mapanatili ang init ng katawan ni baby, siya ay punasan at balutin agad at iantala ang paliligo nang mga 6 na oras matapos siyang isilang.'],
        ['heading' => 'Pagpapasuso', 'type' => 'text', 'content' => 'Panatilihin si baby sa tabi mo. Nasisiyahan ang baby kapag siya ay nasa bisig mo. Hayaan si baby na kusang sumuso sa iyo hanggang gusto niya at di dapat awatin. Ito ay makatutulong upang lalong dumami ang iyong gatas. Ang gatas ng ina lamang ang pinakamainam na pagkain para sa sanggol hanggang 6 na buwan. Hindi pa dapat painumin ng tubig o pakainin ng iba sa ganitong edad si baby. Huwag bigyan si baby ng ibang uri ng gatas na nasa pakete, lata, o kahon.'],
        ['heading' => 'Proteksyon sa Impeksyon', 'type' => 'text', 'content' => 'Tiyakin na napatakan si baby ng antibiyotiko sa mata upang maiwasan ang impeksyon na maaaring mauwi sa pagka-bulag. Siguraduhin din na nabigyan si baby ng bakuna laban sa Hepatitis B at BCG.'],
        ['heading' => 'Kung Nahihirapang Magpasuso', 'type' => 'text', 'content' => 'Kung ikaw ay nahihirapang magpasuso, kumonsulta sa health worker sa pinakamalapit na health center. Mayroon ding mga volunteer health worker o breastfeeding counselor sa inyong barangay na pwedeng makatulong sa inyo sa oras ng pangangailangan.'],
    ]
],
'breastfeeding' => [
    'title' => 'Eksklusibong Pagpapasuso',
    'intro' => 'Nanay, alam mo ba na ang pagpapasuso ng anak ay nakabubuti hindi lang para kay baby, kundi para rin sa iyo? Kaya mahalagang paghandaan ang pagpapasuso.',
    'sections' => [
        ['heading' => 'Tandaan', 'type' => 'highlight', 'content' => 'Ang 6 na buwang eksklusibong pagpapasuso ng gatas ng ina lamang — walang milk formula, am, juice, tubig, o bitamina na hindi inireseta ng doktor kay baby — ay mahalaga sa kalusugan ni baby.'],
        ['heading' => 'Mga Benepisyo para kay Baby', 'type' => 'list', 'items' => [
            'Ang gatas ng ina ay ligtas, malinis, at madaling tunawin. Ito ay sapat na pagkain ng sanggol sa unang 6 na buwan ng kanyang buhay.',
            'Ito ang proteksyon laban sa sakit tulad ng pagtatae at impeksyon sa respiratory system gaya ng ubo, sipon, o pulmonya.',
            'Mas malusog at matibay ang katawan ni baby.',
        ]],
        ['heading' => 'Mga Benepisyo para sa Nanay', 'type' => 'list', 'items' => [
            'Ang gatas ng ina ay libre.',
            'Ang eksklusibong pagpapasuso sa loob ng 6 na buwan ay isang paraan para hindi agad mabuntis muli, ayon sa Lactational Amenorrhea Method (LAM) ng family planning.',
            'Nakatutulong itong pagtibayin ang relasyon ng nanay at anak.',
            'Mabilis makaliiit ng tiyan ni mommy dahil sa pag-contract ng bahay bata nito. Balik sa dating sukat si mommy!',
        ]],
    ]
],
'baby-milestones' => [
    'title' => 'Mga Kaganapan sa Paglaki ng Sanggol',
    'intro' => 'Ang bawat buwan ay may bagong kakayahan si baby. Alamin ang mga ito para masuportahan mo siya nang wasto.',
    'sections' => [
        ['heading' => '4 na Buwan', 'type' => 'text', 'content' => 'Kinagigiliwang tingnan ni baby ang mga matitingkad na kulay. Susundan ng kanyang mga mata ang gumagalaw na bagay, ngumingiti, at kikilalanin ang iyong boses at mukha. Gustung-gusto niyang mag-"gurgling" at gumawa ng ingay, i-angat ang kanyang ulo at tumawa. Ang pagngiti ni baby ay mahalaga para sa iyo at kay tatay. Pakitaan si baby ng matitingkad na bagay, kausapin siya, bigyan ng malalaking lugar upang makapaglaro at iunat ang kanyang mga binti at braso.'],
        ['heading' => '8 na Buwan', 'type' => 'text', 'content' => 'Nakaiikot na si baby, nakauupo nang maayos at tuwid na ang ulo. Kaya na niyang abutin ang mga bagay at isubo sa kanyang bibig. Nakikita na niya ang mga tao at mga bagay sa kanyang paligid. Hayaan ang ibang miyembro ng pamilya na hawakan at kargahin si baby. Ito ang tamang panahon upang matutunan niyang makipag-usap sa ibang tao. Hayaan siyang humawak ng mga malinis, ligtas, at makukulay na bagay. Bigyan siya ng mga laruang makukulay na may iba\'t ibang hugis at laki na maaari niyang paglaruan.'],
    ]
],
'baby-safety' => [
    'title' => 'Mga Simpleng Gabay Para Matiyak ang Kaligtasan ni Baby',
    'intro' => 'Ang iyong anak ay lumalaking bata. Kailangan niya ng tamang pangangalaga at patnubay upang makamit niya ang wastong kalusugan.',
    'sections' => [
        ['heading' => 'Mga Dapat Gawin para sa Kaligtasan ni Baby', 'type' => 'list', 'items' => [
            'Huwag pabayaan si baby na mag-isa nang walang sumusubaybay.',
            'Patulugin si baby sa kanyang kuna para maiwasang mahulog sa kama.',
            'Patulugin si baby nang patihaya o patagilid. Huwag padapa.',
            'Huwag hayaang maligo nang mag-isa hanggang wala pa siyang anim na taong gulang.',
            'Huwag siyang ihagis pataas sa ere o paikut-ikutin.',
            'Itago sa lugar na hindi maaabot ang mga posporo, kandila, at mainit na tubig/sabaw, gayundin ang gaas, gamot sa insekto, at iba pang kemikal, maliit at matutulis na bagay, mga supot na plastic upang hindi mapaglaruan o isuot sa kanyang ulo at hindi makahinga, at mga kawad at saksakan ng kuryente.',
            'Huwag mag-iwan ng timba, palanggana, o bathtub na may tubig.',
            'Huwag manigarilyo o ilayo si baby sa mga naninigarilyo at mauusok na lugar.',
            'Isusi ang mga cabinet at drawer.',
            'Lagyan ng harang ang magkabilang parte ng kama.',
            'Huwag siyang hayaang maglaro sa kalsada.',
            'Laging gamitan ng "seatbelt" kapag nakasakay sa sasakyan.',
            'Huwag hayaang maiwan si baby nang mag-isa sa loob ng sasakyan.',
            'Huwag hayaan si baby na malapit sa swimming pool, ilog, o sapa nang walang sumusubaybay.',
        ]],
    ]
],
'philhealth' => [
    'title' => 'PhilHealth: Mga Benepisyo para sa Ina at Sanggol',
    'intro' => 'Ang Philippine Health Insurance Corporation o PHILHEALTH ay isang ahensya ng gobyerno na bahagi ng Department of Health (DOH). Ang PHILHEALTH ay tumutulong para maisulong ang kalusugang pangkalahatan sa bansa.',
    'sections' => [
        ['heading' => 'Mga Package na Saklaw ng PhilHealth', 'type' => 'table', 'rows' => [
            ['Package', 'Halaga'],
            ['Maternity Care Package', 'Hanggang ₱8,000 (birthing homes/infirmaries) o ₱6,500 (ospital)'],
            ['Cesarean Section Package', 'Hanggang ₱19,000'],
            ['Newborn Care Package', 'Hanggang ₱1,750'],
            ['Z Benefit Package', 'Para sa Prematurity at Low Birth Weight'],
        ]],
        ['heading' => 'Paano malaman kung miyembro na ng PhilHealth?', 'type' => 'list', 'items' => [
            'Lumapit sa tanggapan ng DSWD',
            'Pumunta sa PhilHealth Regional Office o Local Health Insurance Office na malapit sa inyo',
            'Tumawag sa PhilHealth Call Center: (02) 441-7442',
            'Kung nasa ospital na, maaaring alamin ang status mula sa kanilang Health Care Institution (HCI) Portal',
        ]],
        ['heading' => 'Kung hindi ka pa miyembro ng PhilHealth:', 'type' => 'list', 'items' => [
            'Lumapit sa tanggapan ng DSWD para makapagpalista sa kanilang listahan',
            'Kung nasa ospital na at hindi pa miyembro, tumungo sa mga social welfare unit ng ospital para matulungan kang magpa-miyembro sa kanilang point-of-care mechanism',
            'Humingi ng tulong sa inyong Municipal Social Welfare and Development Officer para magpa-sponsor',
        ]],
    ]
],
'family-planning' => [
    'title' => 'Mga Pamamaraan ng Family Planning',
    'intro' => 'Alamin ang iba\'t ibang pamamaraan ng family planning para mapag-usapan ninyong mabuti ng iyong partner at makapili ng method na gagamitin para sa tamang pag-aagwat sa tulong ng isang trained health service provider.',
    'sections' => [
        ['heading' => 'Standard Days Method (Cycle Beads)', 'type' => 'text', 'content' => 'Ito ay isang natural na pamamaraan kung saan ginagamit ang cycle beads upang matukoy ang panahong fertile ng babae. Ito ay angkop sa mga babaeng may regular na siklo (26-32 days ang haba).'],
        ['heading' => 'Lactational Amenorrhea Method (LAM)', 'type' => 'text', 'content' => 'Ang LAM ay isang natural na pamamaraan na maaaring gamitin ng mga babaeng nagpapasuso. Kailangan siguraduhin ang mga sumusunod: Wala pang 6 na buwan ng sanggol. Hindi pa bumabalik ang regla. Madalas magpasuso araw-gabi at tanging gatas lamang ng ina ang ibinibigay sa sanggol.'],
        ['heading' => 'Pills', 'type' => 'text', 'content' => 'Ang pills ay uri ng hormonal contraceptive na iniinom araw-araw sa takdang oras para masiguraduhing epektibo ito. May 2 klase: (1) Combined Oral Contraceptive - Hindi pwede sa mga nagpapasusong ina. (2) Progestin-only Pills - Pwede sa mga nagpapasusong ina.'],
        ['heading' => 'Progestin-Only Injectable', 'type' => 'text', 'content' => 'Ito ay uri ng hormonal contraceptive na iniiniksyon sa babae kada 3 buwan para masiguraduhing epektibo ito. Ito ay maaaring gamitin ng karamihan, maliban na lang ang mga babaeng natukoy na may kanser sa suso.'],
        ['heading' => 'Progestin Subdermal Implant', 'type' => 'text', 'content' => 'Ito ay isang uri ng hormonal contraceptive kung saan ang isang malambot na vinyl rod na mala-palito ng posporo ang liit ay inilalagay sa ilalim ng balat ng braso ng babae. Ito ay mabisa hanggang 3 taon.'],
        ['heading' => 'Intrauterine Device (IUD)', 'type' => 'text', 'content' => 'Ito ay isang malambot na plastic na inilalagay sa matris upang hadlangan ang pagsasanib ng punlay at itlog. Epektibo ito hanggang 12 taon. Angkop para sa mga babaeng naghahanap ng pangmatagalang pamamaraan, nagpapasuso ng sanggol, o may sapat na bilang na ng anak pero hindi nais magpa-ligate.'],
        ['heading' => 'Condom', 'type' => 'text', 'content' => 'Ito ay isinusuot ng lalaki sa kanyang matigas na ari bago tuluyang makipagtalik. Angkop sa mga mag-partner na may mataas na posibilidad magkaroon ng STI, may kalagayang pangkalusugan at hindi maaaring gumamit ng mga hormonal methods, mga lalaking katatapos lamang magpa-vasectomy at naghihintay na maubos ang punlay. Huwag gamitin kung may allergy sa latex.'],
        ['heading' => 'Bilateral Tubal Ligation (BTL) - Para sa Babae', 'type' => 'text', 'content' => 'Ito ay isang permanenteng pamamaraan kung saan tinatalian at pinuputol ang dalawang anurang itlog (fallopian tubes). Angkop sa mga babaeng ayaw nang madagdagan pa ang bilang ng anak. Kailangan lamang siguraduhing siya ay hindi buntis bago gawin ito.'],
        ['heading' => 'No-Scalpel Vasectomy (NSV) - Para sa Lalaki', 'type' => 'text', 'content' => 'Ito ay isang permanenteng pamamaraan kung saan tinatalian at pinuputol ang dalawang anurang punlay (vas deferens). Ito ay angkop sa mga lalaking ayaw nang madagdagan pa ang bilang ng anak.'],
    ]
],
];

$article = $articles[$slug] ?? null;
if (!$article) { header("Location: vaccines.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="fil">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> | Alawihao Health Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --green: #2d5016;
            --accent: #5a7c3a;
            --light: #8fbf5a;
            --bg: #f8fffb;
            --white: #ffffff;
            --text: #333333;
            --muted: #666666;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); }

        /* PROGRESS BAR */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            background: var(--light);
            width: 0%;
            z-index: 9999;
            transition: width 0.1s;
        }

        .topbar {
            background: var(--white);
            border-bottom: 3px solid var(--green);
            padding: 15px 20px 15px 65px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .topbar .back-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--green);
            font-size: 18px;
            padding: 4px 8px;
            border-radius: 8px;
            transition: background 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .topbar .back-btn:hover { background: #f0f4f0; }
        .topbar .hamburger { font-size: 24px; cursor: pointer; color: var(--green); }
        .topbar .page-label { font-size: 1rem; font-weight: 600; color: var(--green); }

        #main { transition: margin-left 0.3s; padding: 30px 24px 60px; max-width: 860px; margin: 0 auto; }

        .article-header {
            background: linear-gradient(135deg, var(--green), var(--accent));
            color: white;
            border-radius: 20px;
            padding: 40px 36px;
            margin-bottom: 28px;
            text-align: center;
            box-shadow: 0 6px 20px rgba(45,80,22,0.2);
        }
        .article-header .icon { font-size: 3.5rem; margin-bottom: 14px; display: block; }
        .article-header h1 { font-size: 2rem; margin-bottom: 12px; font-weight: 700; }
        .article-header p { font-size: 1.05rem; opacity: 0.92; line-height: 1.7; max-width: 600px; margin: 0 auto; }

        .section-card {
            background: var(--white);
            border-radius: 14px;
            padding: 28px 30px;
            margin-bottom: 18px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
            border: 1px solid #eef2ee;
        }
        .section-card h2 {
            color: var(--green);
            font-size: 1.15rem;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f4f0;
            font-weight: 700;
        }
        .section-card ul { padding-left: 22px; }
        .section-card ul li { margin-bottom: 12px; line-height: 1.75; color: var(--text); font-size: 1rem; }
        .section-card p { line-height: 1.85; color: var(--text); font-size: 1rem; }

        .myth-box { margin-bottom: 15px; }
        .myth-label {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 15px;
            border-radius: 0 8px 8px 0;
            font-style: italic;
            color: #856404;
            margin-bottom: 8px;
        }
        .myth-label span { font-weight: 700; display: block; margin-bottom: 4px; }
        .truth-label {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 12px 15px;
            border-radius: 0 8px 8px 0;
            color: #155724;
        }
        .truth-label span { font-weight: 700; display: block; margin-bottom: 4px; }

        .highlight-box {
            background: linear-gradient(135deg, #e8f5e9, #f1f8e9);
            border: 2px solid var(--light);
            border-radius: 12px;
            padding: 20px;
            font-size: 1.05rem;
            line-height: 1.7;
            color: var(--green);
            font-weight: 500;
            text-align: center;
        }

        table { width: 100%; border-collapse: collapse; }
        table th { background: var(--green); color: white; padding: 10px 14px; text-align: left; }
        table td { padding: 10px 14px; border-bottom: 1px solid #eee; }
        table tr:nth-child(even) td { background: #f9f9f9; }

        .disclaimer {
            background: #fff9db;
            border-left: 5px solid var(--light);
            border-radius: 8px;
            padding: 18px 20px;
            margin-top: 30px;
            color: #856404;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div id="progressBar" class="progress-bar"></div>
<?php include 'user_sidebar.php'; ?>

<div class="topbar">
    <a href="main_user.php" class="back-btn"><i class="fa fa-arrow-left"></i></a>
    <img src="image/brgy.jpg" alt="Brgy Logo" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--green);">
    <span class="page-label"><?= htmlspecialchars($article['title']) ?></span>
</div>

<div id="main">
    <div class="article-header">
        <span class="icon"><?= $article['icon'] ?></span>
        <h1><?= htmlspecialchars($article['title']) ?></h1>
        <p><?= htmlspecialchars($article['intro']) ?></p>
    </div>

    <?php foreach ($article['sections'] as $sec): ?>
    <div class="section-card">
        <h2><?= htmlspecialchars($sec['heading']) ?></h2>

        <?php if ($sec['type'] === 'list'): ?>
            <ul>
                <?php foreach ($sec['items'] as $item): ?>
                    <li><?= htmlspecialchars($item) ?></li>
                <?php endforeach; ?>
            </ul>

        <?php elseif ($sec['type'] === 'text'): ?>
            <p><?= htmlspecialchars($sec['content']) ?></p>

        <?php elseif ($sec['type'] === 'highlight'): ?>
            <div class="highlight-box"><?= htmlspecialchars($sec['content']) ?></div>

        <?php elseif ($sec['type'] === 'myth'): ?>
            <div class="myth-box">
                <div class="myth-label"><span>Sabi-sabi:</span><?= htmlspecialchars($sec['myth']) ?></div>
                <div class="truth-label"><span>Ang Totoo:</span><?= htmlspecialchars($sec['truth']) ?></div>
            </div>

        <?php elseif ($sec['type'] === 'table'): ?>
            <table>
                <?php foreach ($sec['rows'] as $i => $row): ?>
                    <tr>
                        <?php foreach ($row as $cell): ?>
                            <?= $i === 0 ? "<th>" . htmlspecialchars($cell) . "</th>" : "<td>" . htmlspecialchars($cell) . "</td>" ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="disclaimer">
        <strong>Paalala:</strong> Para sa mga katanungan at karagdagang impormasyon, kumonsulta sa mga health worker ng Alawihao Health Center.
    </div>
</div>

<script>
    // Reading progress bar
    window.addEventListener('scroll', () => {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = (scrollTop / docHeight) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
    });
</script>
</body>
</html>
