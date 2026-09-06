<?php
session_start();
include 'db_connect.php';

$slug = $_GET['topic'] ?? '';

// ── Article data ─────────────────────────────────────────────────────────────
// Section types:
//   'detailed' – items with 'label' + 'why' (DOH/study reasoning)
//   'list'     – plain bullet items
//   'table'    – rows array
//   'text'     – single paragraph
//   'highlight'– boxed callout
//   'myth'     – myth/truth pair
//   'steps'    – numbered steps with optional 'note'
// ─────────────────────────────────────────────────────────────────────────────

$articles = [

// ═══════════════════════════════════════════════════════════
'warning-signs' => [
    'title'    => 'Mga Babala Habang Buntis',
    'icon'     => 'fa-triangle-exclamation',
    'image'    => 'image/pic9.jpg',
    'color'    => '#c0392b',
    'source'   => 'DOH Philippines – Focused Antenatal Care Guidelines',
    'intro'    => 'Nanay, ang mga palatandaang ito ay senyales na kailangan ng agarang medikal na atensyon. Huwag hintayin na lumala — pumunta agad sa pinakamalapit na health center o ospital.',
    'sections' => [
        [
            'heading' => 'Mga Palatandaan ng Panganib — At Bakit Ito Mapanganib',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Pamamaga ng mga binti, kamay, o mukha',
                    'why'   => 'Ang biglaang pamamaga, lalo na sa mukha at kamay, ay isa sa mga pangunahing sintomas ng pre-eclampsia — isang mapanganib na kondisyon na nagpapataas ng blood pressure at maaaring humantong sa seizure (eclampsia). Ayon sa WHO, ang pre-eclampsia ay responsable sa halos 18% ng maternal deaths sa buong mundo.',
                ],
                [
                    'label' => 'Matinding sakit ng ulo, pagkahilo, o panlalabo ng paningin',
                    'why'   => 'Ang mga sintomas na ito, kasama ang mataas na BP, ay nagpapahiwatig ng hypertensive crisis o pre-eclampsia na may komplikasyon sa utak (HELLP syndrome). Kailangang suriin agad ang blood pressure.',
                ],
                [
                    'label' => 'Pagdurugo ng puwerta',
                    'why'   => 'Ang anumang pagdurugo pagkatapos ng unang trimester ay maaaring palatandaan ng placenta previa (naka-harang ang inunan sa cervix) o placental abruption (napaghiwalay ang inunan). Parehong emergency na nangangailangan ng agarang operasyon.',
                ],
                [
                    'label' => 'Pamumutla, panghihina, at mabilis na tibok ng puso',
                    'why'   => 'Ang matinding anemia sa pagbubuntis ay nagpapababa ng oxygen sa dugo ng ina at sanggol. Ayon sa DOH, ang anemia ay nakakaapekto sa higit 40% ng mga buntis na kababaihan sa Pilipinas at isang nangungunang dahilan ng maternal mortality.',
                ],
                [
                    'label' => 'Lagnat na higit sa 38°C',
                    'why'   => 'Ang lagnat habang buntis ay maaaring senyales ng impeksyon sa ihi (UTI), chorioamnionitis (impeksyon sa loob ng matris), o iba pang infeksyon na maaaring kumalat sa sanggol at magdulot ng premature labor o birth defects.',
                ],
                [
                    'label' => 'Mahapdi at masakit na pag-ihi, may dugo',
                    'why'   => 'Ang urinary tract infection (UTI) ay 2x mas karaniwan sa mga buntis dahil sa pagbabago ng hormones at presyon ng matris sa pantog. Kung hindi agad nagagamot, maaaring umabot sa kidney infection (pyelonephritis) at magdulot ng premature birth.',
                ],
                [
                    'label' => 'Pagbagal ng galaw ni baby (< 10 sipa sa 12 oras mula ika-28 linggo)',
                    'why'   => 'Ang pagsipa ni baby ay tanda ng kalusugan niya. Ayon sa mga pag-aaral, ang pagbaba ng fetal movement ay kadalasang unang tanda ng fetal distress o oxygen deficiency. Ang pag-monitor ng kick count araw-araw mula sa ika-28 linggo ay inirerekomenda ng Royal College of Obstetricians.',
                ],
                [
                    'label' => 'Malabnaw o mala-tubig na lumalabas sa puwerta',
                    'why'   => 'Maaaring ito ang amniotic fluid — tubig na nagpoprotekta kay baby. Ang premature rupture of membranes (PROM) bago mag-37 linggo ay naglalagay kay baby sa panganib ng impeksyon at umbilical cord compression. Emergency ito.',
                ],
                [
                    'label' => 'Matinding pananakit ng tiyan',
                    'why'   => 'Maaaring senyales ito ng placental abruption, appendicitis, o maagang labor. Ang ectopic pregnancy (nabubuo ang baby sa labas ng matris) ay nagdudulot din ng matinding tiyan at buhay-buhay na emergency.',
                ],
            ],
        ],
        [
            'heading' => 'Dapat Alalahanin',
            'type'    => 'highlight',
            'content' => 'Ang maagang pagtukoy at pagtrato ng mga komplikasyon sa pagbubuntis ang pinakamabisang paraan ng pag-iwas sa maternal at infant mortality. Huwag mahiyang humingi ng tulong — iyan ang layunin ng iyong health center.',
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'prenatal-checkup' => [
    'title'    => 'Pre-natal Check-up: Ang Iyong Gabay',
    'icon'     => 'fa-stethoscope',
    'image'    => 'image/pic10.jpg',
    'color'    => '#2980b9',
    'source'   => 'DOH Philippines – Focused Antenatal Care (FANC) Protocol',
    'intro'    => 'Ang pre-natal check-up ay hindi lang basta pormal na bisita — ito ang susi sa pagprotekta sa buhay mo at ng iyong sanggol. Ang bawat check-up ay may layunin at siyentipikong batayan.',
    'sections' => [
        [
            'heading' => 'Bakit 4 na Check-up ang Minimum?',
            'type'    => 'highlight',
            'content' => 'Ayon sa WHO at DOH, ang 4 na focused antenatal care visits (FANC) ay nagbibigay ng 23% na pagbaba sa perinatal mortality kumpara sa walang antenatal care. Ang bawat visit ay may tiyak na layunin na sumasaklaw sa pinakakritikal na yugto ng pagbubuntis.',
        ],
        [
            'heading' => 'Schedule at Layunin ng Bawat Check-up',
            'type'    => 'table',
            'rows'    => [
                ['Bisita', 'Kailan', 'Pangunahing Layunin'],
                ['Una (1st)',    'Hanggang ika-3 buwan',       'Kumpirmahin ang pagbubuntis, kumuha ng baseline BP at weight, laboratory tests, iron/folate supplementation'],
                ['Pangalawa (2nd)', 'Ika-4 hanggang ika-6 na buwan', 'Suriin ang fetal growth, BP monitoring, tetanus toxoid, screen for gestational diabetes at anemia'],
                ['Pangatlo (3rd)', 'Ika-7 hanggang ika-8 na buwan', 'Suriin ang posisyon ng baby, birth plan preparation, breastfeeding counseling'],
                ['Pang-apat (4th)', 'Ika-9 na buwan',             'Final assessment, birth preparedness, identify danger signs, confirm delivery plan'],
            ],
        ],
        [
            'heading' => 'Ano ang Ginagawa sa Bawat Check-up — At Bakit',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Pagkuha ng Blood Pressure (BP)',
                    'why'   => 'Ang hypertension (BP ≥ 140/90) habang buntis ay senyales ng pre-eclampsia. Kailangan itong subaybayan sa bawat visit dahil maaaring mabilis na tumaas. Ang gestational hypertension ay nakakaapekto sa 5-10% ng mga pagbubuntis.',
                ],
                [
                    'label' => 'Timbang at Taas',
                    'why'   => 'Ginagamit ito para kalkulahin ang Body Mass Index (BMI) at subaybayan ang tamang pagtaas ng timbang. Ayon sa Institute of Medicine, ang normal na pagtaas ng timbang sa buong pagbubuntis ay 11-16 kg para sa normal weight na kababaihan. Masyadong mababa = malnutrition; masyadong mataas = gestational diabetes risk.',
                ],
                [
                    'label' => 'Iron at Folic Acid Supplementation',
                    'why'   => 'Ang folic acid (400-800 mcg/araw) sa unang trimester ay nagpapababa ng panganib ng neural tube defects (spina bifida) ng hanggang 70%, ayon sa CDC. Ang iron naman ay pinipigilan ang anemia na nakakaapekto sa higit 40% ng mga buntis sa Pilipinas.',
                ],
                [
                    'label' => 'Tetanus Toxoid Vaccination',
                    'why'   => 'Pinoprotektahan nito ang ina at ang bagong silang na sanggol laban sa tetanus — isang nakamamatay na impeksyon. Ang neonatal tetanus ay responsable sa maraming infant deaths sa mga developing countries bago naging widespread ang vaccination.',
                ],
                [
                    'label' => 'Laboratory Tests (urinalysis, CBC, blood type)',
                    'why'   => 'Ang urinalysis ay nagde-detect ng UTI at proteinuria (senyales ng pre-eclampsia). Ang Complete Blood Count (CBC) ay nagpapakita ng anemia. Ang blood typing at Rh factor ay kritikal — ang Rh-negative na nanay na may Rh-positive na baby ay nangangailangan ng espesyal na gamot (Rh immunoglobulin).',
                ],
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'pregnancy-dos' => [
    'title'    => "Pregnancy Do's: Mga Dapat Gawin",
    'icon'     => 'fa-thumbs-up',
    'image'    => 'image/pic11.jpg',
    'color'    => '#27ae60',
    'source'   => 'WHO Antenatal Care Guidelines & DOH Philippines Maternal Health Program',
    'intro'    => 'Ang bawat magandang gawi sa panahon ng pagbubuntis ay may direktang epekto sa kalusugan mo at ng iyong sanggol. Hindi lang ito tradisyon — may siyensya sa likod ng bawat rekomendasyon.',
    'sections' => [
        [
            'heading' => 'Nutrisyon',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Kumain ng iba-ibang uri ng pagkain araw-araw',
                    'why'   => 'Ang pagbubuntis ay nagpapataas ng pangangailangan ng katawan sa nutrients: +300 kcal/araw, +27 mg iron, +1,000 mg calcium, at +600 mcg folate. Walang iisang pagkain ang nagbibigay ng lahat ng ito. Ang pagkakaiba-iba ng pagkain (gulay, prutas, karne, isda, gatas) ang pinakamabisang paraan para masigurado ang kompletong nutrisyon para sa nanay at sanggol.',
                ],
                [
                    'label' => 'Uminom ng maraming tubig (8-10 baso araw-araw)',
                    'why'   => 'Ang amniotic fluid na nagpoprotekta kay baby ay binubuo ng tubig. Ang dehydration habang buntis ay maaaring magdulot ng Braxton-Hicks contractions, UTI, at sa matinding kaso, preterm labor. Ang dugo rin ay dumarami ng hanggang 50% sa pagbubuntis kaya kailangan ng mas maraming likido.',
                ],
                [
                    'label' => 'Kumain ng mga pagkaing mayaman sa iron: karne, atay, kangkong, monggo',
                    'why'   => 'Ang iron ay kailangan para gumawa ng hemoglobin na nagdadala ng oxygen sa dugo. Sa pagbubuntis, ang katawan ay gumagawa ng dagdag na dugo para sa sanggol. Ang iron-deficiency anemia ay nagpapataas ng panganib ng premature birth at low birth weight ng hanggang 2x, ayon sa Cochrane Reviews.',
                ],
            ],
        ],
        [
            'heading' => 'Ehersisyo at Pahinga',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Mag-ehersisyo nang 30 minuto, 3-5 beses sa isang linggo',
                    'why'   => 'Ayon sa American College of Obstetricians and Gynecologists (ACOG), ang moderate exercise tulad ng paglalakad, swimming, at prenatal yoga ay nagpapababa ng panganib ng gestational diabetes (ng 27%), pre-eclampsia (ng 40%), at excessive weight gain. Pinapalakas din nito ang mga kalamnan na gagamitin sa panganganak.',
                ],
                [
                    'label' => 'Matulog ng 8-9 oras at magpahinga sa kaliwang gilid',
                    'why'   => 'Ang pagtulog sa kaliwang gilid ay nagpapabuti ng daloy ng dugo sa placenta at sa bato, na nagpapababa ng presyon sa vena cava (malaking ugat). Isang pag-aaral sa journal Sleep Medicine ay nagpakita na ang supine sleeping (pahiga sa likod) sa huling trimester ay nagdodoble ng panganib ng stillbirth.',
                ],
            ],
        ],
        [
            'heading' => 'Emosyonal na Kalusugan',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Humingi ng suporta mula sa pamilya at asawa',
                    'why'   => 'Ang prenatal depression ay nakakaapekto sa 10-15% ng mga buntis na kababaihan. Ayon sa mga pag-aaral, ang mataas na stress habang buntis ay nagpapataas ng cortisol sa dugo, na maaaring makaapekto sa brain development ni baby. Ang social support ay nagpapababa ng stress hormones at nagpapabuti ng birth outcomes.',
                ],
                [
                    'label' => 'Maghanda ng birth plan kasama ang iyong doktor o midwife',
                    'why'   => 'Ang mga kababaihang may birth plan ay mas nasisiyahan sa kanilang birth experience at mas mababa ang emergency cesarean rates, ayon sa isang pag-aaral sa Journal of Midwifery. Kasama sa birth plan ang preferred delivery method, pain management, at kung sino ang kasama sa delivery room.',
                ],
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'pregnancy-donts' => [
    'title'    => "Pregnancy Don'ts: Mga Dapat Iwasan",
    'icon'     => 'fa-ban',
    'image'    => 'image/pic8.jpg',
    'color'    => '#e74c3c',
    'source'   => 'WHO, CDC, at DOH Philippines Guidelines on Maternal Health',
    'intro'    => 'Ang mga bagay na ito ay may siyentipikong patunay na mapanganib sa pagbubuntis. Ang pag-iwas sa mga ito ay isa sa pinakaepektibong paraan ng pag-protekta sa iyong sanggol.',
    'sections' => [
        [
            'heading' => 'Pagkain at Inumin',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Huwag uminom ng alak (kahit kaunti)',
                    'why'   => 'Walang "ligtas" na dami ng alak habang buntis, ayon sa CDC at WHO. Ang alcohol ay madaling tumatawid sa placenta at pumupunta sa dugo ni baby. Nagdudulot ito ng Fetal Alcohol Spectrum Disorder (FASD) — isang permanenteng kondisyon na kinabibilangan ng brain damage, facial abnormalities, at learning disabilities.',
                ],
                [
                    'label' => 'Huwag manigarilyo o malanghap ng usok ng sigarilyo',
                    'why'   => 'Ang nicotine at carbon monoxide sa sigarilyo ay nagpapaliit ng mga daluyan ng dugo sa placenta, na nagbabawas ng oxygen at nutrients para kay baby. Ang smoking habang buntis ay nagpapataas ng panganib ng miscarriage ng 2x, premature birth ng 2x, low birth weight, at SIDS (Sudden Infant Death Syndrome), ayon sa CDC.',
                ],
                [
                    'label' => 'Limitahan ang caffeine (max 200 mg/araw — 1 tasa ng kape)',
                    'why'   => 'Ang caffeine ay nagpapabagal ng metabolismo at kaya ng fetus. Ang mataas na caffeine intake (> 300 mg/araw) ay naka-ugnay sa mas mataas na panganib ng miscarriage at low birth weight, ayon sa isang meta-analysis sa British Medical Journal.',
                ],
                [
                    'label' => 'Iwasan ang hilaw o hindi luto ng pagkain (sashimi, burong isda, hilaw na itlog)',
                    'why'   => 'Ang listeria, salmonella, at toxoplasma ay bakterya at parasito na matatagpuan sa mga hilaw na pagkain. Sa mga buntis, ang immune system ay naturally na weakened kaya mas madaling maimpeksyon. Ang listeriosis habang buntis ay maaaring magdulot ng miscarriage, stillbirth, o meningitis sa newborn.',
                ],
            ],
        ],
        [
            'heading' => 'Gamot at Kemikal',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Huwag uminom ng gamot nang walang reseta ng doktor',
                    'why'   => 'Kahit ang mga over-the-counter na gamot tulad ng ibuprofen at aspirin ay kontraindikado sa ilang yugto ng pagbubuntis. Ang ibuprofen sa ikatlong trimester ay maaaring magsanhi ng premature closure ng ductus arteriosus — isang mahalagang daluyan ng dugo sa puso ni baby.',
                ],
                [
                    'label' => 'Iwasan ang mga insecticide, pesticide, at malakas na kemikal sa bahay',
                    'why'   => 'Ang mga organophosphate pesticides ay naka-ugnay sa mas mababang IQ at attention problems sa mga bata, ayon sa mga pag-aaral sa Environmental Health Perspectives. Kung kailangan gumamit ng cleaning products, siguraduhing may bentilasyon at gumamit ng gloves at mask.',
                ],
            ],
        ],
        [
            'heading' => 'Pisikal na Aktibidad',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Iwasan ang mabigat na pagbubuhat at matinding pisikal na aktibidad',
                    'why'   => 'Ang pagbubuhat ng mabibigat na bagay ay nagpapataas ng intra-abdominal pressure at maaaring magdulot ng placental abruption o premature labor. Ang mga aktibidad na may panganib na mahulog (equestrian sports, skiing) o matamaan ang tiyan ay dapat ding iwasan.',
                ],
                [
                    'label' => 'Huwag maligo sa napakainit na tubig (hot tub, sauna)',
                    'why'   => 'Ang pagtaas ng core body temperature ng nanay na higit sa 39°C sa unang trimester ay naka-ugnay sa neural tube defects. Ang mga hot tub at sauna ay maaaring mag-raise ng body temperature sa mapanganib na antas sa loob lamang ng 10-20 minuto.',
                ],
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'pamahiin' => [
    'title'    => 'Mga Pamahiin: Sabi-sabi vs. Agham',
    'icon'     => 'fa-book-open',
    'image'    => 'image/pic12.webp',
    'color'    => '#8e44ad',
    'source'   => 'DOH Philippines Health Literacy Program & Philippine Obstetrical and Gynecological Society',
    'intro'    => 'Ang mga pamahiin ay bahagi ng ating kultura, ngunit ang ilang maling paniniwala ay maaaring makapinsala sa iyo at sa iyong sanggol. Alamin ang katotohanan batay sa siyensya at medikal na pananaliksik.',
    'sections' => [
        [
            'heading' => 'Pamahiin #1: Ang kulay ng balat ay nagpapahiwatig ng kasarian',
            'type'    => 'myth',
            'myth'    => 'Kapag maitim ang leeg at singit ni nanay habang buntis, lalaki ang anak.',
            'truth'   => 'Ang pangingilim ng balat (melasma o "mask of pregnancy") ay dulot ng pagtaas ng estrogen at progesterone na nagpapalakas ng melanin production — hindi ito konektado sa kasarian ng baby. Ang kasarian ay natutukoy sa chromosome: XX para sa babae, XY para sa lalaki — natutukoy na ito sa sandali ng fertilization. Kumpirmasyon: ultrasound sa ika-18-20 linggo.',
        ],
        [
            'heading' => 'Pamahiin #2: Kambal na saging = kambal na anak',
            'type'    => 'myth',
            'myth'    => 'Kapag kumain ng kambal na saging ang buntis, kambal ang magiging anak.',
            'truth'   => 'Ang twin pregnancy ay nangyayari dahil sa dalawang dahilan: (1) ang isang fertilized egg ay nahahati (identical twins) o (2) dalawang hiwalay na itlog ang na-fertilize nang sabay (fraternal twins). Ang isa sa mga pagkain ay walang kapangyarihang baguhin ito. Bukod pa rito, ang saging ay isa sa mga pinaka-inirekomendang pagkain sa pagbubuntis dahil mayaman ito sa potassium, vitamin B6, at folate.',
        ],
        [
            'heading' => 'Pamahiin #3: Naglalagas ang ngipin sa bawat pagbubuntis',
            'type'    => 'myth',
            'myth'    => 'Normal ang paglagas ng ngipin sa bawat pagbubuntis.',
            'truth'   => 'Ang pagbubuntis ay nagpapataas ng panganib ng gum disease (gingivitis) dahil sa hormonal changes — ngunit hindi ito nagdudulot ng paglagas ng ngipin kung may sapat na calcium intake at maayos na oral hygiene. Ang sanggol ay kumukuha ng calcium mula sa mga calcium stores ng ina (pangunahin sa buto), hindi sa ngipin. Inirerekomenda ng DOH ang dental check-up sa ikalawa at ikatlong trimester.',
        ],
        [
            'heading' => 'Pamahiin #4: Bawal pumunta sa burol ang buntis',
            'type'    => 'myth',
            'myth'    => 'Malas o mapanganib para sa buntis ang pumunta sa libing o burol.',
            'truth'   => 'Walang siyentipikong batayan ang "malas" na ito. Ang praktikal na dahilan sa likod nito: (1) sa mga ospital, mataas ang risk ng pagkuha ng impeksyon; (2) sa mga malalaking pagtitipon, mataas ang exposure sa sakit at stress. Ang emosyonal na stress mula sa pagdadalamhati ay maaaring mag-trigger ng stress hormones. Kung malapit sa puso ang namatay, ang pagbibigay ng pahintulot na pumunta (sa malinis at bentiladong lugar) ay mas makatao.',
        ],
        [
            'heading' => 'Pamahiin #5: Bawal makipagtalik habang buntis',
            'type'    => 'myth',
            'myth'    => 'Baka masaktan ang baby o maagang manganak kung makikipagtalik habang buntis.',
            'truth'   => 'Para sa mga walang komplikasyon na pagbubuntis, ang sexual intercourse ay ligtas at normal sa karamihan ng mga yugto, ayon sa Mayo Clinic at ACOG. Ang baby ay protektado ng amniotic fluid at ng cervical mucus plug. Ang sexual activity ay maaaring kontraindikado lamang sa mga may placenta previa, premature labor history, o cervical incompetence — at ito ay dapat na malinaw na sabihin ng OB-GYN.',
        ],
        [
            'heading' => 'Pamahiin #6: Huwag kumain nang marami para hindi malaking masyado ang baby',
            'type'    => 'myth',
            'myth'    => 'Huwag kakain nang marami para madaling manganak.',
            'truth'   => 'Ang undereating habang buntis ay isa sa mga pinaka-mapanganib na maling paniniwala. Ang mga bata ng mga nanay na hindi sapat kumain ay mas mataas ang panganib ng: low birth weight (< 2.5 kg), stunting, at chronic diseases sa pagbata (ayon sa DOH at UNICEF Philippines). Ang rekomendasyon: dagdag na 300 kcal/araw sa ikalawa at ikatlong trimester.',
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'baby-growth' => [
    'title'    => 'Ang Paglaki ni Baby sa Sinapupunan',
    'icon'     => 'fa-baby',
    'image'    => 'image/pic13.jpg',
    'color'    => '#16a085',
    'source'   => 'Moore KL, Persaud TVN – The Developing Human: Clinically Oriented Embryology; DOH Philippines',
    'intro'    => 'Sa loob ng 40 linggo, ang isang simpleng cell ay nagiging isang kumpletong tao. Narito ang isang detalyadong patnubay — at ang mga bagay na maaari mong gawin para masuportahan ang bawat yugto ng pagbubuntis.',
    'sections' => [
        [
            'heading' => 'Unang Trimester (0–12 Linggo): Pagbuo ng Pundasyon',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => '0–4 na Linggo: Implantasyon at Neural Tube',
                    'why'   => 'Ang fertilized egg ay naglalakbay sa fallopian tube at nag-iimplant sa matris. Sa ika-3 linggo, nagsisimula ang neural tube — ang magiging utak at gulugod ni baby. Ito ang dahilan kung bakit kritikal ang folic acid BAGO pa mabuntis: ang neural tube ay nagsasara sa ika-28 araw, bago pa maraming kababaihan malamang buntis sila.',
                ],
                [
                    'label' => '4–8 na Linggo: Tumitibok na Puso',
                    'why'   => 'Sa ika-6 na linggo, nagsisimula nang tumibok ang puso ni baby — mga 150-170 beses bawat minuto. Nabubuo ang mga pangunahing organo: utak, puso, baga, tiyan, at bituka. Ito ang pinaka-sensitive na panahon — ang exposure sa alak, sigarilyo, at ilang gamot ay maaaring magdulot ng permanenteng damage sa mga organong ito.',
                ],
                [
                    'label' => '8–12 na Linggo: Naging Fetus na',
                    'why'   => 'Sa ika-8 linggo, opisyal na siyang "fetus" — may ulo, mukha, at mga daliri na. Ang mga pangunahing organo ay nabuo na. Sa ika-12 linggo, maaari nang makita ng ultrasound ang heartbeat at basic anatomy. Ito rin ang panahon na madalas na kumukupas ang morning sickness.',
                ],
            ],
        ],
        [
            'heading' => 'Ikalawang Trimester (13–27 Linggo): Paglaki at Paggalaw',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => '13–16 na Linggo: Nagkukuha ng Hugis',
                    'why'   => 'Ang baby ay nasa 10-12 cm na at nagsisimula nang gumalaw — ngunit hindi pa nararamdaman ng nanay. Nabubuo ang mga buto at ngipin. Maaari nang matukoy ang kasarian sa ultrasound. Ang placenta ay fully functional na — ito ang nagdadala ng oxygen at nutrients mula sa dugo ng nanay.',
                ],
                [
                    'label' => '17–20 na Linggo: Unang Sipa (Quickening)',
                    'why'   => 'Sa pagitan ng ika-18-20 linggo, mararamdaman na ng nanay ang mga galaw ni baby para sa unang beses — tinatawag itong "quickening." Ito ay isang positibong senyales ng kalusugan. Ang anomaly scan ultrasound sa panahong ito ay nagbibigay ng kompletong larawan ng anatomy ni baby at nagde-detect ng mga potensyal na birth defects.',
                ],
                [
                    'label' => '21–27 na Linggo: Umaayos ang Pandama',
                    'why'   => 'Nabubuo ang mga pandama: naririnig na ni baby ang boses ng nanay (at tugon siya sa musika!), nakikita ang liwanag kahit hindi pa bukas ang mga mata. Ang mga pag-aaral ay nagpapakita na ang mga bata na narinig ang paulit-ulit na tunog habang nasa tiyan ay kinikilala ito pagkatapos ipanganak. Magmamayamaya, mararamdaman na ang mga hiccup ni baby.',
                ],
            ],
        ],
        [
            'heading' => 'Ikatlong Trimester (28–40 Linggo): Paghahanda para sa Mundo',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => '28–32 na Linggo: Tinatahi ang Utak',
                    'why'   => 'Ito ang pinaka-rapid na yugto ng brain development. Ang utak ay nagdodoble ng sukat sa loob ng ilang linggo. Ang mga premature babies na ipinanganak sa panahong ito (28-32 weeks) ay may mataas na survival rate sa mga NICU ngunit may panganib na developmental delays. Kaya naman, ang pag-iwas sa premature labor sa panahong ito ay kritikal.',
                ],
                [
                    'label' => '33–36 na Linggo: Pagbuo ng Immune System at Taba',
                    'why'   => 'Ang baby ay nag-aambag ng taba para sa regulasyon ng temperatura. Tumatanggap din siya ng antibodies mula sa nanay sa pamamagitan ng placenta — ito ang nagbibigay sa kanya ng proteksyon laban sa mga sakit sa unang ilang buwan ng buhay. Ito ang dahilan kung bakit napakahalaga ng maternal vaccination (tulad ng flu at Tdap).',
                ],
                [
                    'label' => '37–40 na Linggo: Handa na si Baby!',
                    'why'   => 'Ang "full term" ay ika-39-40 linggo — hindi ika-37. Ayon sa mga pag-aaral, ang mga bata na ipinanganak sa ika-39-40 linggo ay may mas mataas na survival rate, mas mababang ICU admissions, at mas magandang long-term outcomes kumpara sa mga ipinanganak sa ika-37-38 linggo. Ang elective induction o C-section bago mag-39 linggo ay hindi dapat gawin nang walang medikal na dahilan.',
                ],
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'newborn-care' => [
    'title'    => 'Pangangalaga sa Bagong Silang na Sanggol',
    'icon'     => 'fa-hands-holding-child',
    'image'    => 'image/pic6.jpg',
    'color'    => '#e67e22',
    'source'   => 'DOH Philippines – Essential Newborn Care (ENC) Protocol; WHO Guidelines for Care of the Newborn',
    'intro'    => 'Ang mga unang minuto, oras, at araw ng buhay ni baby ay kritikal para sa kanyang kalusugan at pag-unlad. Ang bawat hakbang ng Essential Newborn Care ay may siyentipikong batayan.',
    'sections' => [
        [
            'heading' => 'Ang Essential Newborn Care (ENC) Protocol ng DOH',
            'type'    => 'highlight',
            'content' => 'Pinagtibay ng DOH Philippines ang ENC Protocol batay sa mga pag-aaral na nagpapakita na ang mga simpleng hakbang na ito ay maaaring makapagpigil ng hanggang 40% ng neonatal deaths. Ang protocol na ito ay ginagamit sa lahat ng birthing facilities sa Pilipinas.',
        ],
        [
            'heading' => 'Mga Unang Hakbang Pagkatapos Ipanganak',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Immediate Skin-to-Skin Contact (Unang Yakap)',
                    'why'   => 'Ang paglalagay ng hiwagang baby sa dibdib ng nanay ay nagpapalakas ng bonding, nagpapanatili ng init ng katawan ni baby (pinakaepektibo vs incubator), nagpapasigla ng unang pagsuso, at nagpapalakas ng immune system. Ayon sa Cochrane Review, ang skin-to-skin contact ay nagpapababa ng neonatal mortality ng 36% sa mga low-birth-weight babies.',
                ],
                [
                    'label' => 'Delayed Cord Clamping (pagputol ng pusod pagkatapos ng 1-3 minuto)',
                    'why'   => 'Ang pagantay bago putulin ang pusod ay nagbibigay kay baby ng dagdag na 80-100 mL ng dugo mula sa placenta. Naglalaman ito ng iron stores na sapat para sa unang 6 na buwan ng buhay. Nagpapababa din ito ng iron-deficiency anemia ng 50%, ayon sa WHO.',
                ],
                [
                    'label' => 'Breastfeeding sa loob ng 1 oras pagkatapos isilang',
                    'why'   => 'Ang colostrum (ang unang gatas ng ina) ay tinatawag na "liquid gold" — puno ito ng antibodies, white blood cells, at growth factors na nagpoprotekta kay baby laban sa impeksyon. Ang maagang pagsuso ay nagpapalakas din ng milk production ng nanay at nagpapababa ng panganib ng postpartum bleeding.',
                ],
                [
                    'label' => 'Pagpapaliban ng paliligo (hindi bababa sa 6 na oras, mas mainam ang 24 oras)',
                    'why'   => 'Ang vernix caseosa (ang puting layer sa balat ng newborn) ay isang natural na moisturizer at antimicrobial agent. Ang agarang paliligo ay nagpapababa ng body temperature ng newborn at maaaring mag-interfere sa breastfeeding initiation. Inirerekomenda ng WHO ang pagpapaliban ng paliligo ng hindi bababa sa 24 oras.',
                ],
            ],
        ],
        [
            'heading' => 'Mga Babala — Kailan Pumunta sa Ospital',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Ayaw o humina ang pagsuso',
                    'why'   => 'Ang malusog na newborn ay dapat sumuso ng 8-12 beses bawat 24 oras. Ang pagbaba ng pagsuso ay maaaring senyales ng impeksyon, metabolic disorder, o neurological problem.',
                ],
                [
                    'label' => 'Naninilaw ang balat at puting bahagi ng mata (jaundice)',
                    'why'   => 'Ang physiological jaundice ay normal sa mga 60% ng term newborns — ngunit kung ang nininilaw ay mabilis at matindi, maaaring senyales ito ng hemolytic disease o liver problem na nangangailangan ng phototherapy.',
                ],
                [
                    'label' => 'Lagnat na higit sa 38°C o temperatura na mas mababa sa 36.5°C',
                    'why'   => 'Ang immune system ng newborn ay hindi pa fully developed. Ang kahit anong lagnat sa isang batang wala pang 3 buwan ay itinuturing na serious emergency na nangangailangan ng agarang medikal na evaluasyon para maalis ang sepsis (blood infection).',
                ],
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'breastfeeding' => [
    'title'    => 'Eksklusibong Pagpapasuso: Ang Pinakamabisang Proteksyon',
    'icon'     => 'fa-heart-pulse',
    'image'    => 'image/pic1.jpg',
    'color'    => '#e91e8c',
    'source'   => 'WHO/UNICEF Global Breastfeeding Collective; DOH Philippines – National Breastfeeding Program',
    'intro'    => 'Ang gatas ng ina ay hindi lamang pagkain — ito ay isang buhay na sustansya na nagbabago araw-araw upang matugunan ang pangangailangan ng lumalaki at nagbabagong sanggol. Walang formula ang maaaring tularan ito.',
    'sections' => [
        [
            'heading' => 'Ano ang Laman ng Gatas ng Ina?',
            'type'    => 'highlight',
            'content' => 'Ang breast milk ay naglalaman ng higit sa 1,000 na iba-ibang bioactive molecules: antibodies (IgA, IgG, IgM), living white blood cells, stem cells, hormones, growth factors, at prebiotics. Ang formula ay makakapagtulad ng nutrients — ngunit hindi ang mga buhay na cells at immunologic components na ito.',
        ],
        [
            'heading' => 'Mga Benepisyo para kay Baby — Batay sa Pananaliksik',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Nagpoprotekta laban sa impeksyon',
                    'why'   => 'Ang mga breastfed na babies ay may 72% na mas mababang panganib ng hospitalization dahil sa respiratory infections at 64% na mas mababang panganib ng gastrointestinal infections, ayon sa Lancet medical journal. Ang secretory IgA antibodies sa breast milk ay naglilikha ng protective "coating" sa lining ng bituka ni baby.',
                ],
                [
                    'label' => 'Nagpapatalino (nagpapataas ng IQ)',
                    'why'   => 'Ang mga pag-aaral na sumasaklaw sa higit 18,000 mga bata (PROBIT study, Belarus) ay nagpapakita na ang mga breastfed na bata ay may average na 5.9 IQ points na mas mataas. Ang DHA at arachidonic acid sa breast milk ay kritikal para sa brain development.',
                ],
                [
                    'label' => 'Nagpapababa ng panganib ng SIDS',
                    'why'   => 'Ang breastfeeding ay nagpapababa ng panganib ng Sudden Infant Death Syndrome (SIDS) ng 50%, ayon sa isang meta-analysis ng 18 na pag-aaral. Ang mekanismo ay hindi pa ganap na naiintindihan ngunit maaaring may kinalaman sa mas malalim na arousal responses ng breastfed infants.',
                ],
                [
                    'label' => 'Nagpapababa ng panganib ng chronic diseases sa pagbata',
                    'why'   => 'Ang breastfed children ay may mas mababang panganib ng obesity (31% na mas mababa), type 2 diabetes, at childhood leukemia. Ang mga immunologic components ng breast milk ay mukhang nagpapahusay ng immune system development na nagtatagal hanggang sa pagtanda.',
                ],
            ],
        ],
        [
            'heading' => 'Mga Benepisyo para sa Nanay',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Nagpapababa ng panganib ng breast cancer',
                    'why'   => 'Bawat 12 buwan ng breastfeeding ay nagpapababa ng panganib ng breast cancer ng 4.3% at ovarian cancer ng 28%, ayon sa isang meta-analysis sa Lancet. Ang mekanismo ay kasama ang hormonal changes at ang differentiation ng breast tissue sa panahon ng lactation.',
                ],
                [
                    'label' => 'Nagpapabilis ng pagbawi pagkatapos ng panganganak',
                    'why'   => 'Ang oxytocin na nire-release sa bawat pagsuso ay nagpapaliit ng matris at nagpapababa ng postpartum bleeding. Ito rin ang dahilan ng "after pains" na nararamdaman ng ilang nanay sa pagsuso — tanda na gumagana ang katawan.',
                ],
                [
                    'label' => 'Natural na family planning (LAM)',
                    'why'   => 'Ang Lactational Amenorrhea Method (LAM) ay 98% epektibo kung: (1) ang baby ay wala pang 6 na buwan, (2) hindi pa bumabalik ang regla ng nanay, at (3) eksklusibong nagpapasuso (walang ibang pagkain o gatas). Nag-aambag ito sa birth spacing na inirerekomenda ng DOH na 2-3 taon.',
                ],
            ],
        ],
        [
            'heading' => 'Mga Praktikal na Tips para sa Matagumpay na Pagpapasuso',
            'type'    => 'list',
            'items'   => [
                'Simulan ang pagpapasuso sa loob ng 1 oras pagkatapos ipanganak — ang maagang pagsuso ay nagpapatibay ng supply',
                'Ipasuso on-demand — hindi sa schedule. Ang mas madalas na pagsuso = mas maraming gatas',
                'Tiyaking tama ang latch: ang bibig ni baby ay dapat sumasaklaw hindi lang sa nipple kundi sa karamihan ng areola',
                'Huwag magbigay ng tubig, formula, o iba pang pagkain hanggang 6 na buwan',
                'Kumain ng malusog at uminom ng sapat na tubig — ang hydration ng nanay ay nakakaapekto sa milk supply',
                'Humingi ng tulong sa Breastfeeding Peer Counselor ng iyong health center kung may problema',
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'baby-milestones' => [
    'title'    => 'Mga Developmental Milestones ng Sanggol',
    'icon'     => 'fa-chart-line',
    'image'    => 'image/pic7.webp',
    'color'    => '#2980b9',
    'source'   => 'WHO Child Growth Standards; CDC Developmental Milestones; DOH Philippines Integrated Child Development Service',
    'intro'    => 'Ang pag-unawa sa mga developmental milestones ay tumutulong sa mga magulang na suportahan ang pag-unlad ng kanilang anak at matuklasan ang mga posibleng problema nang maaga — kung saan ang maagang intervention ay pinaka-epektibo.',
    'sections' => [
        [
            'heading' => 'Bakit Mahalaga ang Developmental Milestones?',
            'type'    => 'highlight',
            'content' => 'Ayon sa WHO, ang unang 1,000 araw ng buhay (mula sa pagbubuntis hanggang sa ika-2 taon) ay ang pinaka-kritikal na panahon ng brain development. Ang mga karanasan at stimulation sa panahong ito ay nagtatakda ng pundasyon ng learning, behavior, at health sa buong buhay.',
        ],
        [
            'heading' => '0-3 Buwan: Pagtuklas ng Mundo',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Social Smile (6-8 na linggo)',
                    'why'   => 'Ang unang tunay na ngiti (hindi reflex) ay nagpapakita na ang social brain circuits ni baby ay nagsisimula nang gumana. Ito ang simula ng social-emotional development. Ang mga magulang na tumutugon sa ngiti ng kanilang baby ay nagpapalakas ng neural connections na kritikal para sa future relationships at mental health.',
                ],
                [
                    'label' => 'Visual Tracking at Pag-angat ng Ulo',
                    'why'   => 'Ang kakayahang sumunod ng mata sa gumagalaw na bagay ay nagpapakita ng development ng visual cortex. Ang "tummy time" (pagpapaligo ng baby sa tiyan habang gising at may bantay) ay mahalaga para mapalakas ang cervical muscles at maiwasan ang plagiocephaly (flat head syndrome).',
                ],
            ],
        ],
        [
            'heading' => '4-6 Buwan: Aktibo at Malikhaing Bata',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Pag-abot at Paghawak ng mga Bagay',
                    'why'   => 'Ang fine motor development na ito ay nagpapakita ng integration ng visual at motor cortex. Ang pagbibigay ng iba-ibang texture, hugis, at kulay ng mga laruan ay nagpapasigla ng sensory development at curiosity — ang pundasyon ng learning.',
                ],
                [
                    'label' => 'Pagsisimula ng Babbling ("ba-ba", "ma-ma")',
                    'why'   => 'Ang babbling ay hindi basta basta — ito ang pagsasanay ng utak at bibig para sa pagsasalita. Ang mga bata na mas madalas na kinakausap ng kanilang mga magulang ay nagtatayo ng mas malaking vocabulary. Ang "serve and return" interaction (pagtugon sa vocalizations ni baby) ay nagpapalakas ng neural connections ng 700,000 bawat segundo sa panahong ito.',
                ],
            ],
        ],
        [
            'heading' => '7-12 Buwan: Paglalakad at Unang Salita',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Object Permanence (8-9 na buwan)',
                    'why'   => 'Ang pag-unawa na ang mga bagay ay patuloy na umiiral kahit hindi nakikita ay isang malaking cognitive milestone. Ito ang batayan ng memory development. Ang peek-a-boo ay hindi lang laro — ito ay isang mahalagang cognitive exercise para sa developmental stage na ito.',
                ],
                [
                    'label' => 'Paglalakad (10-14 buwan)',
                    'why'   => 'Ang paglalakad ay nagre-require ng integration ng balance, coordination, motor planning, at vestibular system. Ang mga bata na nagpalipas ng mas maraming oras sa "baby walkers" ay kadalasang may delayed independent walking, ayon sa mga pag-aaral. Mas mainam ang floor play at barefoot walking sa ligtas na lugar.',
                ],
                [
                    'label' => 'Unang Salita (10-14 buwan)',
                    'why'   => 'Ang isang salita na may consistent meaning (hal., "mama" para sa ina) ay senyales na ang language centers ng utak ay fully connecting. Ang mga bata na napapalibutan ng rich language environment (maraming pagkukuwento, pagbabasa ng libro, kanta) ay karaniwang may mas malawak na vocabulary sa edad na 2.',
                ],
            ],
        ],
        [
            'heading' => 'Mga Babala na Dapat Suriin ng Doktor',
            'type'    => 'list',
            'items'   => [
                'Hindi ngingiti sa 3 buwan',
                'Hindi sumusunod ng mata sa gumagalaw na bagay sa 3 buwan',
                'Hindi nagbo-babble sa 6 na buwan',
                'Hindi tumutugon sa sariling pangalan sa 9 na buwan',
                'Hindi nagsasalita ng kahit isang salita sa 16 na buwan',
                'Nawala ang mga dating nakamit na kasanayan (developmental regression)',
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'baby-safety' => [
    'title'    => 'Kaligtasan ng Sanggol at Batang Bata',
    'icon'     => 'fa-shield-halved',
    'color'    => '#2c3e50',
    'source'   => 'DOH Philippines; American Academy of Pediatrics (AAP) Safe Sleep Guidelines',
    'intro'    => 'Ang maraming aksidente sa mga bata ay maaaring maiwasan. Ang pag-alam sa mga panganib at pagsunod sa mga simpleng hakbang ng kaligtasan ay nagliligtas ng buhay.',
    'sections' => [
        [
            'heading' => 'Safe Sleep para sa mga Newborn at Infant',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Palaging patihaya ang pagtulog (Back to Sleep)',
                    'why'   => 'Mula nang ipatupad ang "Back to Sleep" campaign ng AAP noong 1994, ang rate ng SIDS sa US ay bumaba ng higit sa 50%. Ang pagtulog sa tiyan ay nagpapataas ng panganib ng SIDS dahil maaaring mag-rebreathe ng carbon dioxide ang baby.',
                ],
                [
                    'label' => 'Walang malambot na bagay sa higaan (unan, malambot na kumot, stuffed animals)',
                    'why'   => 'Ang mga malambot na bagay ay maaaring pumigil sa paghinga ng baby (suffocation risk). Ang safe sleep environment: firm mattress, fitted sheet lang, walang iba.',
                ],
                [
                    'label' => 'Room sharing (hindi bed sharing) sa unang 6 na buwan',
                    'why'   => 'Ang pagtulog sa parehong kwarto ngunit sa hiwalay na kuna ay nagpapababa ng SIDS ng 50%, ayon sa AAP. Ang bed sharing (kasama ang mga matatanda sa parehong higaan) ay nagpapataas ng panganib ng accidental suffocation.',
                ],
            ],
        ],
        [
            'heading' => 'Pag-iwas sa Falls at Accidents',
            'type'    => 'list',
            'items'   => [
                'Huwag pabayaan ang baby nang mag-isa sa mataas na lugar (changing table, sofa, kama)',
                'Gumamit ng car seat sa bawat biyahe sa sasakyan — kahit maiksi ang layo',
                'Itago ang maliit na bagay na maaaring isubo ng bata',
                'Lagyan ng safety gates ang hagdanan',
                'Itago ang mga kemikal, gamot, at matatalas na bagay sa kabinet na may lock',
                'Huwag hayaang maligo nang mag-isa ang batang wala pang 6 na taon',
            ],
        ],
        [
            'heading' => 'Bakunasyon: Pinaka-epektibong Proteksyon',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Sundin ang vaccination schedule ng DOH',
                    'why'   => 'Ang mga bakuna ay nagprotekta sa milyun-milyong bata mula sa polio, measles, pertussis, at iba pang nakamamatay na sakit. Ang herd immunity — ang proteksyon ng buong komunidad kapag ang sapat na bilang ay nabakunahan — ay nagpoprotekta rin sa mga sanggol na masyadong bata para mabakunahan pa.',
                ],
                [
                    'label' => 'Huwag praning sa "vaccine myths"',
                    'why'   => 'Ang claim na ang MMR vaccine ay nagdudulot ng autism ay nanggaling sa isang pag-aaral noong 1998 na kalaunan ay na-retract dahil sa scientific fraud. Higit sa 1.2 milyong bata ang nasiyasat mula noon at walang nakitang koneksyon sa pagitan ng bakuna at autism, ayon sa Cochrane Review.',
                ],
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'unang-linggo' => [
    'title'    => 'Ang Unang Linggo ng Buhay ni Baby',
    'icon'     => 'fa-calendar-day',
    'color'    => '#f39c12',
    'source'   => 'DOH Philippines – Essential Newborn Care Protocol; AAP Guidelines',
    'intro'    => 'Ang unang 7 araw ay isa sa mga pinaka-kritikal na panahon sa buhay ni baby. Halos 75% ng neonatal deaths ay nangyayari sa loob ng unang linggo — kaya naman mahalaga ang tamang pag-aaruga.',
    'sections' => [
        [
            'heading' => 'Araw 1-2: Adaptation',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Pagmamasid sa Kalusugan ng Newborn',
                    'why'   => 'Sa loob ng unang 24 na oras, kailangan ng newborn ang: Apgar score assessment, Vitamin K injection (para sa blood clotting — ang newborns ay natural na kulang sa Vitamin K at nasa panganib ng serious bleeding), eye prophylaxis (erythromycin ointment para sa neonatal conjunctivitis), at Hepatitis B vaccine unang dosis.',
                ],
                [
                    'label' => 'Colostrum: Ang Unang Pagkain',
                    'why'   => 'Ang colostrum ay madilaw-dilaw at malapot — ito ay concentrado at puno ng: immunoglobulins (nagpoprotekta laban sa impeksyon), leukocytes (white blood cells), at laxative properties na tumutulong na mailabas ang meconium (unang dumi ni baby na berde at maitim). Huwag itapon ang "unang gatas" na ito — ito ang pinakamainam na pagkain para sa newborn.',
                ],
            ],
        ],
        [
            'heading' => 'Araw 3-5: Physiological Changes',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Physiological Jaundice',
                    'why'   => 'Ang 60% ng term at 80% ng preterm na mga newborn ay nagkakaroon ng physiological jaundice sa ika-2-3 araw. Ito ay normal at dulot ng breakdown ng fetal hemoglobin. Karaniwang nawawala sa loob ng 1-2 linggo. Ngunit kung ang jaundice ay lumabas sa unang 24 oras, o kung matindi at kumakalat sa tiyan at binti, ito ay red flag na kailangan ng phototherapy.',
                ],
                [
                    'label' => 'Weight Loss at Recovery',
                    'why'   => 'Normal na mawalan ng 5-10% ng birth weight sa unang 3-4 araw (dahil sa fluid loss at pagdadaan ng meconium). Dapat mabalik sa birth weight bago mag-2 linggo. Kung ang weight loss ay higit sa 10% o hindi nababalik ang timbang, maaaring may problema sa feeding na kailangan ng medikal na atensyon.',
                ],
            ],
        ],
        [
            'heading' => 'Pag-aalaga ng Pusod (Umbilical Cord)',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Dry cord care — huwag lagyan ng alkohol o kulay pula',
                    'why'   => 'Ang bagong rekomendasyon ng WHO at DOH ay "dry cord care" — ang pinakaepektibong paraan na mapabilis ang pagtuyo at paglagas ng pusod. Ang paggamit ng alkohol ay hindi kailangan at maaaring mag-delay ng cord separation. Ang paggamit ng kulay pula o toyo ay taliwas sa medikal na rekomendasyon at nagdudulot ng impeksyon.',
                ],
                [
                    'label' => 'Mga babala: impeksyon ng pusod (omphalitis)',
                    'why'   => 'Ang pamamaga, pamumula, masamang amoy, o pus na lumalabas sa pusod ay senyales ng omphalitis — isang buhay-buhay na emergency. Ang bacterial infection ng pusod ay maaaring mabilis na kumalat sa dugo (sepsis) sa mga newborn. Pumunta agad sa ospital.',
                ],
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'philhealth' => [
    'title'    => 'PhilHealth: Mga Benepisyo para sa Ina at Sanggol',
    'icon'     => 'fa-id-card',
    'color'    => '#1565c0',
    'source'   => 'Philippine Health Insurance Corporation (PhilHealth) – Updated Benefit Packages 2024',
    'intro'    => 'Ang PhilHealth ay nagbibigay ng financial protection para sa mga Pilipino sa panahon ng sakit at panganganak. Alamin ang iyong mga karapatan at paano gamitin ang mga benepisyo.',
    'sections' => [
        [
            'heading' => 'Bakit Mahalaga ang PhilHealth?',
            'type'    => 'highlight',
            'content' => 'Ayon sa Philippine Statistics Authority, ang medical expenses ang isa sa nangungunang dahilan ng pagkahulog sa kahirapan ng mga pamilya sa Pilipinas. Ang PhilHealth ay nagbibigay ng financial protection upang ang pangangailangan ng pangangalaga ng kalusugan ay hindi maging hadlang sa pagtanggap ng medikal na serbisyo.',
        ],
        [
            'heading' => 'Mga Package para sa Panganganak at Newborn',
            'type'    => 'table',
            'rows'    => [
                ['Package', 'Sakop', 'Halaga (Approx.)'],
                ['Maternity Care Package (NSD)', 'Normal na panganganak sa birthing home/infirmary', 'Hanggang ₱8,000'],
                ['Maternity Care Package (ospital)', 'Normal na panganganak sa ospital', 'Hanggang ₱6,500'],
                ['Cesarean Section Package', 'C-section na may medikal na indikasyon', 'Hanggang ₱19,000'],
                ['Newborn Care Package', 'ENC Protocol para sa newborn', 'Hanggang ₱1,750'],
                ['Z Benefit – Premature/LBW', 'Espesyal na care para sa premature babies', 'Comprehensive coverage'],
                ['TB-DOTS Package', 'TB treatment para sa ina o baby', 'Covered'],
            ],
        ],
        [
            'heading' => 'Paano Maging Miyembro ng PhilHealth',
            'type'    => 'steps',
            'items'   => [
                ['step' => 'Suriin kung miyembro ka na', 'note' => 'Tumawag sa PhilHealth hotline: (02) 8441-7442 o bumisita sa philhealth.gov.ph para ma-verify ang iyong membership status'],
                ['step' => 'Kung hindi pa miyembro — mag-apply bilang Indigent Member', 'note' => 'Lumapit sa inyong DSWD o Municipal Social Welfare and Development Office (MSWDO). Ang mga indigent families ay libre ang premium sa ilalim ng government subsidy'],
                ['step' => 'Kuhain ang PhilHealth ID at MDR (Member Data Record)', 'note' => 'Ang MDR ay kailangan sa pagproseso ng benepisyo sa ospital'],
                ['step' => 'Sa pag-admit sa ospital, ibigay agad ang PhilHealth ID at MDR', 'note' => 'Tanungin ang hospital social worker para sa tulong sa PhilHealth processing'],
            ],
        ],
        [
            'heading' => 'Mga Karapatan ng PhilHealth Member',
            'type'    => 'list',
            'items'   => [
                'Karapatan sa sapat na pagtrato at kalidad ng medikal na serbisyo',
                'Karapatan sa malinaw na impormasyon tungkol sa iyong sakit at opsyon sa paggamot',
                'Karapatan na tumanggi sa anumang medikal na pamamaraan na hindi mo naiintindihan',
                'Karapatan na magsampa ng reklamo laban sa anumang health provider na nangabuso',
                'Karapatan sa no-balance billing sa mga Indigent at sponsored members sa government hospitals',
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'family-planning' => [
    'title'    => 'Responsible Parenthood at Family Planning',
    'icon'     => 'fa-people-group',
    'color'    => '#6d4c41',
    'source'   => 'DOH Philippines – National Family Planning Program; Responsible Parenthood and Reproductive Health Act (RA 10354)',
    'intro'    => 'Ang family planning ay hindi lamang tungkol sa pagpipigil sa pagbubuntis — ito ay tungkol sa pagbibigay ng pagkakataon sa bawat pamilya na magkaroon ng healthy, planned, at financially prepared na mga anak. Ito ay karapatan ng bawat Pilipino.',
    'sections' => [
        [
            'heading' => 'Bakit Mahalaga ang Birth Spacing?',
            'type'    => 'highlight',
            'content' => 'Inirerekomenda ng WHO ang minimum na 24 na buwan na agwat sa pagitan ng kapanganakan. Ang maikling birth intervals (< 18 buwan) ay naka-ugnay sa mas mataas na panganib ng: preterm birth (1.4x), low birth weight (1.6x), at maternal mortality. Ang proper birth spacing ay nagbibigay din ng sapat na oras sa nanay para makabawi ang kanyang mga nutritional stores.',
        ],
        [
            'heading' => 'Mga Natural na Pamamaraan',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Lactational Amenorrhea Method (LAM)',
                    'why'   => '98% epektibo kung: (1) ang baby ay wala pang 6 buwan, (2) hindi pa bumabalik ang regla, at (3) eksklusibong nagpapasuso (walang supplement). Ang prolactin hormone na nire-release sa pagsuso ay nagpipigil sa ovulation. Kapag nawala ang isa sa tatlong kondisyon, kailangan na ng backup contraception.',
                ],
                [
                    'label' => 'Standard Days Method / Cycle Beads',
                    'why'   => '95% epektibo para sa mga babaeng may regular na cycle (26-32 araw). Gumagamit ng cycle beads para i-track ang fertile days (araw 8-19 ng cycle). Walang side effects. Pinaka-angkop para sa mga babaeng may parehong regular na cycle at kooperatibong partner.',
                ],
                [
                    'label' => 'Basal Body Temperature (BBT) Method',
                    'why'   => 'Sumusukat ng temperatura ng katawan araw-araw sa umaga bago bumangon. Ang temperatura ay tumataas ng 0.2-0.5°C pagkatapos ng ovulation. Mas epektibo kapang pinagsama sa Cervical Mucus Method (Symptothermal Method) — hanggang 99% epektibo kung tama ang paggamit.',
                ],
            ],
        ],
        [
            'heading' => 'Mga Hormonal na Pamamaraan',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Combined Oral Contraceptive Pills (COC)',
                    'why'   => '99% epektibo sa perfect use, 91% sa typical use. Nagpipigil sa ovulation sa pamamagitan ng estrogen at progestin. Nagreresulta rin sa mas regular at mas magaan na menstruation at nagpapababa ng panganib ng ovarian at endometrial cancer. HINDI para sa mga nagpapasuso (unang 6 buwan), naninigarilyo na higit sa 35 taon, o may kasaysayan ng blood clots.',
                ],
                [
                    'label' => 'Progestin-Only Pills (POP) / Mini-pill',
                    'why'   => 'Ligtas para sa mga nagpapasuso (hindi nakakaapekto sa supply ng gatas) at para sa mga may kontraindikasyon sa estrogen. Kailangang inumin sa parehong oras araw-araw (3-hour window lamang kumpara sa 12-hour window ng COC).',
                ],
                [
                    'label' => 'Injectable (DMPA — Depo-Provera, kada 3 buwan)',
                    'why'   => '99% epektibo. Maginhawa dahil quarterly lamang. Nagdudulot ng amenorrhea (wala o bihirang menstruation) sa maraming babae — ito ay normal at safe. Ang fertility ay maaaring mag-delay ng 6-12 buwan pagkatapos itigil.',
                ],
                [
                    'label' => 'Subdermal Implant (Implanon, 3 taon)',
                    'why'   => '> 99% epektibo — isa sa pinaka-epektibong contraceptive methods. Isang maliit na flexible rod (katulad ng palito ng posporo) na inilalagay sa ilalim ng balat ng braso. Epektibo agad at awtomatiko — hindi kailangan ng araw-araw na pag-alala.',
                ],
            ],
        ],
        [
            'heading' => 'Mga Pangmatagalang Pamamaraan',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Intrauterine Device (IUD) — 5 o 10 taon',
                    'why'   => '> 99% epektibo. Ang copper IUD ay nagsisilbing spermicide. Ang hormonal IUD (Mirena) ay nagpapababa rin ng menstrual bleeding ng 90%. Ligtas para sa mga nagpapasuso at maaaring gamitin kahit kakaroon lang ng panganganak (postpartum IUD). Ang fertility ay bumabalik agad pagkatapos alisin.',
                ],
                [
                    'label' => 'Bilateral Tubal Ligation (BTL) — Permanente para sa Babae',
                    'why'   => 'Ligasyon ng dalawang fallopian tubes upang maiwasan ang fertilization. Para sa mga babaeng tiyak na ayaw nang magkaroon ng dagdag na anak. Ang operasyon ay kadalasang tumatagal ng 20-30 minuto at maaaring gawin pagkatapos ng delivery (postpartum BTL). Ang reversal ay posible ngunit mahal at hindi guaranteed.',
                ],
                [
                    'label' => 'No-Scalpel Vasectomy (NSV) — Permanente para sa Lalaki',
                    'why'   => 'Mas simple, mas mura, at mas ligtas kaysa BTL. Ginagawa sa loob ng 15-20 minuto sa local anesthesia. Walang makabuluhang epekto sa sexual function o testosterone levels. Ang Pilipinas ay may mababang vasectomy rates (< 1%) kumpara sa ibang bansa — maaaring dahil sa cultural barriers at maling paniniwala.',
                ],
            ],
        ],
    ],
],

]; // end $articles

$article = $articles[$slug] ?? null;
if (!$article) { header("Location: user_dashboard.php"); exit(); }

// ── Computed stats ────────────────────────────────────────────────────────────
$word_count = 0;
foreach ($article['sections'] as $s) {
    if (!empty($s['items'])) foreach ($s['items'] as $i) {
        if (is_array($i)) $word_count += str_word_count(($i['label'] ?? '') . ' ' . ($i['why'] ?? '') . ' ' . ($i['note'] ?? '') . ' ' . ($i['step'] ?? ''));
        else $word_count += str_word_count($i);
    }
    if (!empty($s['content'])) $word_count += str_word_count($s['content']);
    if (!empty($s['myth']))    $word_count += str_word_count($s['myth']) + str_word_count($s['truth']);
    if (!empty($s['rows']))    foreach ($s['rows'] as $r) foreach ($r as $c) $word_count += str_word_count($c);
}
$read_min     = max(2, ceil($word_count / 180));
$section_count = count($article['sections']);
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
            --green:   #2d5016;
            --accent:  #3d6b22;
            --light:   #8fbf5a;
            --bg:      #f5f5f0;
            --white:   #ffffff;
            --text:    #1c1c1c;
            --muted:   #5a5a5a;
            --card-bg: #ffffff;
            --border:  #c8cfc8;
            --sidebar-width: 260px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.65;
            font-size: 14px;
        }

        /* PROGRESS BAR */
        .progress-bar { position: fixed; top: 0; left: 0; height: 3px; background: #2d5016; width: 0%; z-index: 9999; transition: width 0.1s; }

        /* SIDEBAR */
        .sidebar-container { width: var(--sidebar-width) !important; min-width: var(--sidebar-width) !important; height: 100vh; position: fixed; top: 0; left: 0; z-index: 300; overflow-y: auto; transition: transform 0.3s ease; }
        body.sidebar-closed .sidebar-container { transform: translateX(-100%); }

        /* TOPBAR */
        .topbar { background: #ffffff; border-bottom: 2px solid #2d5016; padding: 8px 20px; display: flex; align-items: center; gap: 12px; position: sticky; top: 0; z-index: 200; box-shadow: 0 1px 4px rgba(0,0,0,0.1); margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); transition: all 0.3s ease; }
        body.sidebar-closed .topbar { margin-left: 0; width: 100%; }
        .topbar .hamburger-btn { background: #2d5016; border: none; cursor: pointer; color: #fff; font-size: 0.9rem; width: 34px; height: 34px; border-radius: 4px; display: none; align-items: center; justify-content: center; flex-shrink: 0; }
        .topbar .hamburger-btn:hover { background: #3d6b22; }
        body.sidebar-closed .topbar .hamburger-btn { display: inline-flex; }
        .topbar .logo-img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid #2d5016; flex-shrink: 0; }
        .topbar .page-label { font-size: 0.88rem; font-weight: 700; color: #2d5016; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; letter-spacing: 0.2px; }

        /* MAIN */
        #main { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); transition: all 0.3s ease; padding: 24px 20px 80px; background: var(--bg); }
        body.sidebar-closed #main { margin-left: 0; width: 100%; }
        .article-wrap { max-width: 820px; margin: 0 auto; }

        /* ── PAMPHLET HEADER ── */
        .article-header {
            background: #2d5016;
            color: white;
            border-radius: 0;
            padding: 28px 36px;
            margin-bottom: 0;
            border-bottom: 4px solid #8fbf5a;
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }
        .article-header .header-body { flex: 1; }
        .article-header h1 {
            font-size: 1.45rem;
            font-weight: 700;
            margin-bottom: 6px;
            line-height: 1.3;
            letter-spacing: -0.2px;
        }
        .article-header p {
            font-size: 0.88rem;
            opacity: 0.88;
            line-height: 1.6;
            margin-bottom: 10px;
            max-width: 600px;
        }
        .source-tag {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 3px;
            padding: 3px 10px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /* ── META STRIP ── */
        .article-meta {
            display: flex; align-items: center; gap: 0;
            background: #3d6b22;
            padding: 8px 20px;
            margin-bottom: 20px;
            font-size: 0.76rem;
            color: rgba(255,255,255,0.85);
            flex-wrap: wrap; gap: 4px;
        }
        .article-meta span {
            display: flex; align-items: center; gap: 5px;
            padding: 2px 12px;
            border-right: 1px solid rgba(255,255,255,0.2);
        }
        .article-meta span:last-child { border-right: none; }
        .article-meta i { opacity: 0.75; }

        /* ── BACK BUTTON ── */
        .article-actions { display: flex; align-items: center; margin-bottom: 16px; }
        .btn-back {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 7px 16px;
            background: white;
            color: #2d5016;
            border: 1.5px solid #2d5016;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            transition: background 0.15s, color 0.15s;
        }
        .btn-back:hover { background: #2d5016; color: white; }
        .btn-back i { font-size: 0.75rem; }

        /* ── TOC — pamphlet index style ── */
        .toc-box {
            background: white;
            border: 1px solid var(--border);
            border-top: 3px solid #2d5016;
            border-radius: 0;
            padding: 16px 20px;
            margin-bottom: 18px;
        }
        .toc-box h3 {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #2d5016;
            font-weight: 700;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e0e0e0;
        }
        .toc-box ol { padding-left: 0; list-style: none; counter-reset: toc-counter; }
        .toc-box ol li {
            counter-increment: toc-counter;
            font-size: 0.83rem;
            color: #1c1c1c;
            padding: 5px 0 5px 28px;
            border-bottom: 1px dotted #ddd;
            position: relative;
            cursor: pointer;
            transition: color 0.15s;
        }
        .toc-box ol li:last-child { border-bottom: none; }
        .toc-box ol li::before {
            content: counter(toc-counter) ".";
            position: absolute; left: 0;
            color: #2d5016;
            font-weight: 700;
            font-size: 0.78rem;
            width: 22px;
        }
        .toc-box ol li:hover { color: #2d5016; text-decoration: underline; }

        /* ── KEY TAKEAWAY ── */
        .key-takeaway {
            background: #eef6e4;
            border: 1px solid #b8d98a;
            border-left: 4px solid #2d5016;
            border-radius: 0;
            padding: 14px 18px;
            margin-bottom: 18px;
            display: flex; gap: 12px; align-items: flex-start;
        }
        .key-takeaway-icon { font-size: 1.2rem; flex-shrink: 0; color: #2d5016; margin-top: 1px; }
        .key-takeaway h3 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.8px; color: #2d5016; font-weight: 700; margin-bottom: 4px; }
        .key-takeaway p { font-size: 0.86rem; color: #2a3d12; line-height: 1.65; }

        /* ── SECTION CARD — pamphlet section ── */
        .section-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 0;
            padding: 0;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .section-card h2 {
            font-size: 0.82rem;
            font-weight: 700;
            color: white;
            background: #2d5016;
            margin: 0;
            padding: 9px 16px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            display: flex; align-items: center; gap: 8px;
            border-bottom: 2px solid #8fbf5a;
        }
        .section-card h2::before { display: none; }
        .section-card-body { padding: 16px 18px; }

        /* ── DETAILED LIST ── */
        .detailed-item {
            margin-bottom: 0;
            border: none;
            border-bottom: 1px solid #e8eed8;
            border-radius: 0;
            overflow: hidden;
        }
        .detailed-item:last-child { border-bottom: none; }
        .detailed-label {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 11px 4px;
            background: white;
            cursor: pointer;
            transition: background 0.15s;
        }
        .detailed-label:hover { background: #f6faf2; }
        .detailed-bullet {
            width: 22px; height: 22px;
            background: #2d5016;
            color: white;
            border-radius: 2px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 0.72rem;
            margin-top: 1px;
        }
        .detailed-text { flex: 1; }
        .detailed-text strong { font-size: 0.88rem; color: #1c1c1c; display: block; font-weight: 600; }
        .detailed-toggle { color: #999; font-size: 0.7rem; transition: transform 0.2s; flex-shrink: 0; margin-top: 4px; }
        .detailed-why {
            padding: 10px 14px 14px 38px;
            background: #f8fbf4;
            border-top: 1px solid #dceeda;
            display: none;
        }
        .detailed-why .why-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #2d5016;
            margin-bottom: 5px;
            display: flex; align-items: center; gap: 4px;
            padding-bottom: 4px;
            border-bottom: 1px solid #cce8b0;
        }
        .detailed-why p { font-size: 0.84rem; color: #2a3a2a; line-height: 1.7; }
        .detailed-item.open .detailed-why { display: block; }
        .detailed-item.open .detailed-toggle { transform: rotate(180deg); }

        /* ── PLAIN LIST ── */
        .plain-list { list-style: none; padding: 0; margin: 0; }
        .plain-list li {
            padding: 8px 10px 8px 30px;
            border-bottom: 1px solid #eef4e4;
            font-size: 0.87rem;
            color: #1c1c1c;
            position: relative;
            line-height: 1.6;
        }
        .plain-list li:last-child { border-bottom: none; }
        .plain-list li::before {
            content: '▸';
            position: absolute; left: 8px; top: 8px;
            color: #2d5016;
            font-size: 0.75rem;
        }

        /* ── TABLE ── */
        .article-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
        .article-table th {
            background: #2d5016;
            color: white;
            padding: 9px 12px;
            text-align: left;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-right: 1px solid rgba(255,255,255,0.15);
        }
        .article-table th:last-child { border-right: none; }
        .article-table td {
            padding: 9px 12px;
            border-bottom: 1px solid #d8e8c0;
            border-right: 1px solid #e8eee8;
            vertical-align: top;
            color: #1c1c1c;
        }
        .article-table td:last-child { border-right: none; }
        .article-table tr:nth-child(even) td { background: #f4f9ee; }
        .article-table tr:last-child td { border-bottom: none; }
        .article-table tr:hover td { background: #eef6e4; }

        /* HIGHLIGHT BOX */
        .highlight-box {
            background: #f4f9ee;
            border: 1px solid #b8d98a;
            border-left: 4px solid #2d5016;
            border-radius: 0;
            padding: 14px 18px;
            font-size: 0.88rem;
            line-height: 1.7;
            color: #2a3d12;
        }
        .highlight-box::before { content: none; }

        /* MYTH / TRUTH*/
        .myth-box {
            border: 1px solid var(--border);
            margin-bottom: 12px;
            border-radius: 0;
            overflow: hidden;
        }
        .myth-label {
            background: #fff8e6;
            border-left: none;
            border-bottom: 1px solid #f0d080;
            padding: 10px 14px;
            border-radius: 0;
            font-style: normal;
            color: #5a3a00;
            font-size: 0.86rem;
            line-height: 1.6;
        }
        .myth-label .badge {
            font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
            background: #d97706; color: white;
            border-radius: 2px; padding: 2px 7px; margin-right: 6px;
            letter-spacing: 0.3px;
        }
        .truth-label {
            background: #f6faf0;
            border-left: none;
            border-top: none;
            border-radius: 0;
            padding: 10px 14px;
            color: #1a2e0a;
            font-size: 0.86rem;
            line-height: 1.7;
        }
        .truth-label .badge {
            font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
            background: #3d8c28; color: white;
            border-radius: 2px; padding: 2px 7px; margin-right: 6px;
        }

        /* ── STEPS ── */
        .steps-list { list-style: none; padding: 0; counter-reset: step-counter; }
        .steps-list li {
            counter-increment: step-counter;
            padding: 10px 12px 10px 46px;
            border-bottom: 1px solid #e8eed8;
            background: white;
            position: relative;
            font-size: 0.87rem;
        }
        .steps-list li:last-child { border-bottom: none; }
        .steps-list li::before {
            content: counter(step-counter);
            position: absolute; left: 12px; top: 10px;
            width: 22px; height: 22px;
            background: #2d5016; color: white;
            border-radius: 2px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.75rem;
        }
        .steps-list li strong { display: block; color: #1c1c1c; margin-bottom: 2px; font-weight: 600; }
        .steps-list li small { color: #5a5a5a; font-size: 0.8rem; }

        /* ── CTA CARD ── */
        .cta-card {
            background: #2d5016;
            border-radius: 0;
            padding: 18px 22px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            color: white;
            border-left: 5px solid #8fbf5a;
        }
        .cta-icon {
            width: 44px; height: 44px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 3px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .cta-text { flex: 1; }
        .cta-text h3 { font-size: 0.88rem; font-weight: 700; margin-bottom: 3px; text-transform: uppercase; letter-spacing: 0.3px; }
        .cta-text p  { font-size: 0.8rem; opacity: 0.85; line-height: 1.55; margin: 0; }
        .cta-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: white; color: #2d5016;
            font-weight: 700; font-size: 0.8rem;
            padding: 8px 16px; border-radius: 3px;
            text-decoration: none; white-space: nowrap;
            text-transform: uppercase; letter-spacing: 0.3px;
            transition: opacity 0.15s; flex-shrink: 0;
            border: none;
        }
        .cta-btn:hover { opacity: 0.88; }
        @media (max-width: 600px) {
            .cta-card { flex-direction: column; }
            .cta-btn { width: 100%; justify-content: center; }
        }

        /* ── RELATED ARTICLES ── */
        .related-section { margin-top: 20px; }
        .related-section h3 {
            font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: #2d5016;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid #2d5016;
            display: flex; align-items: center; gap: 7px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 10px;
        }
        .related-card {
            background: white;
            border: 1px solid var(--border);
            border-top: 3px solid #2d5016;
            border-radius: 0;
            padding: 12px 14px;
            text-decoration: none;
            color: var(--text);
            display: flex; align-items: flex-start; gap: 10px;
            transition: box-shadow 0.15s;
        }
        .related-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,0.10); }
        .related-card-icon {
            width: 32px; height: 32px;
            background: #eef6e4;
            color: #2d5016;
            border: 1px solid #b8d98a;
            border-radius: 3px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.88rem; flex-shrink: 0;
        }
        .related-card-title { font-size: 0.82rem; font-weight: 600; color: #2d5016; line-height: 1.35; }
        .related-card-sub   { font-size: 0.72rem; color: var(--muted); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.3px; }

        /*FOOTER*/
        .article-footer {
            background: #ffffff;
            border-top: 3px solid #2d5016;
            padding: 36px 20px 20px;
            margin-top: 40px;
            color: #1c1c1c;
        }
        .footer-inner { max-width: 820px; margin: 0 auto; }

        .footer-top {
            display: grid;
            grid-template-columns: 1.6fr 1fr 1fr;
            gap: 32px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e8dc;
            margin-bottom: 16px;
        }
        @media (max-width: 640px) {
            .footer-top { grid-template-columns: 1fr; gap: 20px; }
        }

        /*identity */
        .footer-brand { display: flex; align-items: flex-start; gap: 12px; }
        .footer-brand img {
            width: 48px; height: 48px; border-radius: 50%;
            object-fit: cover;
            border: 2px solid #2d5016;
            flex-shrink: 0;
        }
        .footer-brand-text h4 {
            font-size: 0.88rem; font-weight: 700; color: #2d5016;
            line-height: 1.3; margin-bottom: 3px;
        }
        .footer-brand-text span {
            font-size: 0.72rem; color: #666;
            display: block; line-height: 1.4;
        }
        .footer-tagline {
            font-size: 0.75rem;
            color: #666;
            margin-top: 10px;
            line-height: 1.6;
            font-style: italic;
        }

        /*info */
        .footer-col h5 {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #2d5016;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #d8e8c8;
        }
        .footer-col ul { list-style: none; padding: 0; margin: 0; }
        .footer-col ul li {
            font-size: 0.78rem;
            color: #444;
            padding: 4px 0;
            display: flex;
            align-items: flex-start;
            gap: 7px;
            line-height: 1.4;
        }
        .footer-col ul li i {
            color: #2d5016;
            font-size: 0.72rem;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .footer-col ul li strong { color: #2d5016; }
        .footer-col a { color: #3d6b22; text-decoration: none; }
        .footer-col a:hover { text-decoration: underline; }

        /* Bottom bar */
        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .footer-copy {
            font-size: 0.7rem;
            color: #888;
        }
        .footer-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: #eef6e4;
            border: 1px solid #c8e0a8;
            border-radius: 3px;
            padding: 3px 10px;
            font-size: 0.68rem;
            color: #2d5016;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /*READING PROGRESS */
        .reading-progress-bar {
            position: fixed;
            bottom: 0; left: 0;
            width: 0%;
            height: 3px;
            background: #8fbf5a;
            z-index: 9998;
            transition: width 0.1s;
        }            border-radius: 0 2px 0 0;
        

        /*CTA CARD*/
        .cta-card {
            background: linear-gradient(135deg, #2d5016, #3d6b1e);
            border-radius: 16px;
            padding: 24px 28px;
            margin-top: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            color: white;
            box-shadow: 0 4px 16px rgba(45,80,22,0.25);
        }
        .cta-icon {
            width: 54px; height: 54px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .cta-text { flex: 1; }
        .cta-text h3 { font-size: 1rem; font-weight: 700; margin-bottom: 4px; }
        .cta-text p  { font-size: 0.85rem; opacity: 0.88; line-height: 1.6; margin: 0; }
        .cta-btn {
            display: inline-flex; align-items: center; gap: 7px;
            background: white; color: #2d5016;
            font-weight: 700; font-size: 0.82rem;
            padding: 9px 18px; border-radius: 999px;
            text-decoration: none; white-space: nowrap;
            transition: opacity 0.2s; flex-shrink: 0;
        }
        .cta-btn:hover { opacity: 0.88; }
        @media (max-width: 600px) {
            .cta-card { flex-direction: column; text-align: center; }
            .cta-btn { width: 100%; justify-content: center; }
        }

        /*RELATED ARTICLES*/
        .related-section { margin-top: 28px; }
        .related-section h3 {
            font-size: 0.82rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: #5a7c3a; margin-bottom: 14px;
            display: flex; align-items: center; gap: 7px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        .related-card {
            background: white;
            border: 1px solid #e0edd4;
            border-radius: 12px;
            padding: 16px;
            text-decoration: none;
            color: var(--text);
            display: flex; align-items: flex-start; gap: 12px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .related-card:hover { box-shadow: 0 4px 14px rgba(45,80,22,0.12); transform: translateY(-2px); }
        .related-card-icon {
            width: 52px; height: 52px; border-radius: 4px;
            background: #eef6e6; color: #2d5016;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0; overflow: hidden;
        }
        .related-card-title { font-size: 0.85rem; font-weight: 600; color: #2d5016; line-height: 1.4; }
        .related-card-sub   { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }

        /* PRINT */
        @media print {
            .sidebar-container, .topbar, .article-actions, .toc-box, .progress-bar { display: none !important; }
            #main { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .article-wrap { max-width: 100%; }
            .detailed-why { display: block !important; }
        }
    </style>
</head>
<body class="sidebar-closed">
<div id="progressBar" class="progress-bar"></div>
<div class="reading-progress-bar" id="readingBar"></div>

<div class="sidebar-container">
    <?php include 'user_sidebar.php'; ?>
</div>

<div class="topbar">
    <button class="hamburger-btn" onclick="toggleSidebar()" title="Toggle Sidebar">
        <i class="fa fa-bars"></i>
    </button>
    <img src="image/logo.png" alt="Brgy Logo" class="logo-img" onerror="this.style.display='none'">
    <span class="page-label"><?= htmlspecialchars($article['title']) ?></span>
</div>

<div id="main">
<div class="article-wrap">

    <!-- HEADER -->
    <div class="article-header">
        <div class="header-body">
            <h1><?= htmlspecialchars($article['title']) ?></h1>
            <p><?= htmlspecialchars($article['intro']) ?></p>
        </div>
    </div>

    <!-- META -->
    <div class="article-meta">
        <span><img src="image/clock.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;margin-right:3px;"> <?= $read_min ?> min basahin</span>
        <span><img src="image/greenbook.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;"> <?= $section_count ?> seksyon</span>
        <span><img src="image/location.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;"> Alawihao Health Center</span>
        <span><img src="image/shield.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;"> DOH-verified</span>
    </div>

    <!-- BACK BUTTON -->
    <div class="article-actions">
        <a href="user_dashboard.php" class="btn-back">
            <img src="image/left arrow.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;"> Bumalik sa Dashboard
        </a>
    </div>

    <!-- TOC -->
    <?php if ($section_count > 2): ?>
    <div class="toc-box">
        <h3><i class="fa fa-book-open"></i> Mga Nilalaman</h3>
        <ol>
            <?php foreach ($article['sections'] as $i => $s): ?>
            <li onclick="document.getElementById('sec-<?= $i ?>').scrollIntoView({behavior:'smooth'})">
                <?= htmlspecialchars($s['heading']) ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>
    <?php endif; ?>

    <!-- KEY TAKEAWAY -->
    <?php
        $first = $article['sections'][0];
        $preview = '';
        if ($first['type'] === 'detailed' && !empty($first['items'][0]['label'])) $preview = $first['items'][0]['label'] . ' — ' . substr($first['items'][0]['why'] ?? '', 0, 160) . '...';
        elseif ($first['type'] === 'list'      && !empty($first['items']))           $preview = $first['items'][0];
        elseif ($first['type'] === 'highlight' && !empty($first['content']))         $preview = $first['content'];
        elseif ($first['type'] === 'text'      && !empty($first['content']))         $preview = substr($first['content'], 0, 200) . '...';
    ?>
    <?php if ($preview): ?>
    <div class="key-takeaway">
        <div class="key-takeaway-icon"><img src="image/light.png" alt="" style="width:22px;height:22px;object-fit:contain;"></div>
        <div>
            <h3>Pangunahing Aral</h3>
            <p><?= htmlspecialchars($preview) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- SECTIONS -->
    <?php foreach ($article['sections'] as $idx => $sec): ?>
    <div class="section-card" id="sec-<?= $idx ?>">
        <h2><?= htmlspecialchars($sec['heading']) ?></h2>
        <div class="section-card-body">

        <?php if ($sec['type'] === 'detailed'): ?>
            <?php foreach ($sec['items'] as $k => $item): ?>
            <div class="detailed-item" id="item-<?= $idx ?>-<?= $k ?>">
                <div class="detailed-label" onclick="toggleItem('item-<?= $idx ?>-<?= $k ?>')">
                    <div class="detailed-bullet"><i class="fa fa-check"></i></div>
                    <div class="detailed-text">
                        <strong><?= htmlspecialchars($item['label']) ?></strong>
                    </div>
                    <i class="fa fa-chevron-down detailed-toggle"></i>
                </div>
                <?php if (!empty($item['why'])): ?>
                <div class="detailed-why">
                    <div class="why-label"><img src="image/stethoscope.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;margin-right:3px;"> Bakit? — Batay sa Agham</div>
                    <p><?= htmlspecialchars($item['why']) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

        <?php elseif ($sec['type'] === 'list'): ?>
            <ul class="plain-list">
                <?php foreach ($sec['items'] as $item): ?>
                <li><?= htmlspecialchars($item) ?></li>
                <?php endforeach; ?>
            </ul>

        <?php elseif ($sec['type'] === 'highlight'): ?>
            <div class="highlight-box"><?= htmlspecialchars($sec['content']) ?></div>

        <?php elseif ($sec['type'] === 'text'): ?>
            <p style="line-height:1.85;color:var(--text);font-size:0.95rem;background:#f8fbf5;padding:16px 18px;border-radius:10px;border-left:3px solid #c8ddb0;"><?= htmlspecialchars($sec['content']) ?></p>

        <?php elseif ($sec['type'] === 'myth'): ?>
            <div class="myth-box">
                <div class="myth-label"><span class="badge"><i class="fa fa-triangle-exclamation"></i> Sabi-sabi</span><?= htmlspecialchars($sec['myth']) ?></div>
                <div class="truth-label"><span class="badge"><i class="fa fa-circle-check"></i> Ang Totoo</span><?= htmlspecialchars($sec['truth']) ?></div>
            </div>

        <?php elseif ($sec['type'] === 'table'): ?>
            <div style="overflow-x:auto;">
            <table class="article-table">
                <?php foreach ($sec['rows'] as $ri => $row): ?>
                <tr>
                    <?php foreach ($row as $ci => $cell): ?>
                    <?= $ri === 0 ? "<th>" . htmlspecialchars($cell) . "</th>" : "<td>" . htmlspecialchars($cell) . "</td>" ?>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </table>
            </div>

        <?php elseif ($sec['type'] === 'steps'): ?>
            <ol class="steps-list">
                <?php foreach ($sec['items'] as $step): ?>
                <li>
                    <strong><?= htmlspecialchars($step['step']) ?></strong>
                    <?php if (!empty($step['note'])): ?><small><?= htmlspecialchars($step['note']) ?></small><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        </div><!-- .section-card-body -->
    </div>
    <?php endforeach; ?>

    <?php
    // ── Related articles map ────────────────────────────────────────────────
    $all_related = [
        'warning-signs'    => ['icon'=>'fa-triangle-exclamation', 'image'=>'image/pic9.jpg',    'label'=>'Mga Babala Habang Buntis'],
        'prenatal-checkup' => ['icon'=>'fa-stethoscope',          'image'=>'image/pic10.jpg',   'label'=>'Pre-natal Check-up'],
        'pregnancy-dos'    => ['icon'=>'fa-thumbs-up',            'image'=>'image/pic11.jpg',   'label'=>"Pregnancy Do's"],
        'pregnancy-donts'  => ['icon'=>'fa-ban',                  'image'=>'image/pic8.jpg',    'label'=>"Pregnancy Don'ts"],
        'pamahiin'         => ['icon'=>'fa-book-open',            'image'=>'image/pic12.webp',  'label'=>'Sabi-sabi vs. Totoo'],
        'baby-growth'      => ['icon'=>'fa-baby',                 'image'=>'image/pic13.jpg',   'label'=>'Paglaki ni Baby'],
        'newborn-care'     => ['icon'=>'fa-hands-holding-child',  'image'=>'image/pic6.jpg',    'label'=>'Newborn Care'],
        'breastfeeding'    => ['icon'=>'fa-heart-pulse',          'image'=>'image/pic1.jpg',    'label'=>'Eksklusibong Pagpapasuso'],
        'baby-milestones'  => ['icon'=>'fa-chart-line',           'image'=>'image/pic7.webp',   'label'=>'Baby Milestones'],
        'baby-safety'      => ['icon'=>'fa-shield-halved',        'image'=>'image/pic2.jpg',    'label'=>'Kaligtasan ng Baby'],
        'unang-linggo'     => ['icon'=>'fa-calendar-day',         'image'=>'image/pic3.jpg',    'label'=>'Unang Linggo ni Baby'],
        'philhealth'       => ['icon'=>'fa-id-card',              'image'=>'image/pic13.webp',  'label'=>'PhilHealth Benefits'],
        'family-planning'  => ['icon'=>'fa-people-group',         'image'=>'image/pic14.webp',  'label'=>'Family Planning'],
    ];
    // Pick 3 related topics (exclude current)
    $related_keys = array_filter(array_keys($all_related), fn($k) => $k !== $slug);
    $related_keys = array_values($related_keys);
    // Simple seeded selection for variety
    $picks = array_slice($related_keys, 0, 3);
    ?>

    <!-- RELATED ARTICLES -->
    <?php if (!empty($picks)): ?>
    <div class="related-section">
        <h3><img src="image/greenbook.png" alt="" style="width:14px;height:14px;object-fit:contain;vertical-align:middle;"> Related Topics</h3>
        <div class="related-grid">
            <?php foreach ($picks as $key): ?>
            <?php $r = $all_related[$key]; ?>
            <a href="health_article.php?topic=<?= $key ?>" class="related-card">
                <div class="related-card-icon">
                    <?php if (!empty($r['image'])): ?>
                    <img src="<?= htmlspecialchars($r['image']) ?>" alt="<?= htmlspecialchars($r['label']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:3px;">
                    <?php else: ?>
                    <i class="fa <?= $r['icon'] ?>"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="related-card-title"><?= htmlspecialchars($r['label']) ?></div>
                    <div class="related-card-sub">Read more →</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>

<div class="article-footer">
    <div class="footer-inner">

        <div class="footer-top">

            <div>
                <div class="footer-brand">
                    <img src="image/logo.png" alt="Brgy Logo" onerror="this.style.display='none'">
                    <div class="footer-brand-text">
                        <h4>Barangay Alawihao Health Center</h4>
                        <span>Alawihao, Daet, Camarines Norte</span>
                    </div>
                </div>
                <p class="footer-tagline">Serving the health of every family in Barangay Alawihao.</p>
            </div>

            <div class="footer-col">
                <h5><i class="fa fa-address-book"></i> Contact Us</h5>
                <ul>
                    <li><img src="image/fb.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;flex-shrink:0;"> <a href="https://www.facebook.com/barangay.alawihao" target="_blank" rel="noopener">facebook.com/barangay.alawihao</a></li>
                    <li><img src="image/email.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;flex-shrink:0;"> <a href="mailto:alawihaohealth@gmail.com">alawihaohealth@gmail.com</a></li>
                    <li><img src="image/location.png" alt="" style="width:13px;height:13px;object-fit:contain;vertical-align:middle;flex-shrink:0;"> Alawihao, Daet, Camarines Norte, 4600</li>
                </ul>
            </div>

            <div class="footer-col">
                <h5><i class="fa fa-clock"></i> Office Hours</h5>
                <ul>
                    <li>Monday – Friday</li>
                    <li>8:00 AM – 5:00 PM</li>
                    <li>Closed on Saturdays, Sundays &amp; Holidays</li>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
            <span class="footer-copy">&copy; <?= date('Y') ?> Barangay Alawihao Health Center. All rights reserved.</span>
            <span class="footer-badge"><img src="image/shield.png" alt="" style="width:12px;height:12px;object-fit:contain;vertical-align:middle;"> DOH-Accredited Facility</span>
        </div>

    </div>
</div>

<script>
    window.addEventListener('scroll', () => {
        const pct = window.scrollY / (document.documentElement.scrollHeight - window.innerHeight) * 100;
        const val = isNaN(pct) ? 0 : Math.min(pct, 100);
        document.getElementById('progressBar').style.width  = val + '%';
        document.getElementById('readingBar').style.width   = val + '%';
    });

    function toggleItem(id) {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('open');
    }
</script>
</body>
</html>
