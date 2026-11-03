<?php
return [

// ═══════════════════════════════════════════════════════════
'warning-signs' => [
    'title'    => 'Mga Babala Habang Buntis',
    'icon'     => 'fa-triangle-exclamation',
    'image'    => 'images/pic9.jpg',
    'color'    => '#5A6B47',
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
    'image'    => 'images/pic10.jpg',
    'color'    => '#5A6B47',
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
    'image'    => 'images/pic11.jpg',
    'color'    => '#5A6B47',
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
    'image'    => 'images/pic8.jpg',
    'color'    => '#5A6B47',
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
    'image'    => 'images/pic12.webp',
    'color'    => '#5A6B47',
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
    'image'    => 'images/pic13.jpg',
    'color'    => '#5A6B47',
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
    'image'    => 'images/pic6.jpg',
    'color'    => '#5A6B47',
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
    'image'    => 'images/breastfeeding_cover.jpg',
    'image_credit' => 'Larawan ni Vyacheslav Argenberg / Wikimedia Commons, CC BY 4.0',
    'color'    => '#5A6B47',
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
        [
            'heading' => 'Mga Posisyon sa Pagpapasuso',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Cradle Position',
                    'why'   => 'Ihele si baby sa braso sa panig ng suso na gagamitin at iharap ang katawan niya sa iyo. Suportahan ang ulo at leeg; ilagay ang ibabang braso ni baby sa paligid ng tagiliran mo, hindi nakasingit sa pagitan ng dibdib niya at ng katawan mo. Panatilihing tuwid at komportable ang leeg niya.',
                ],
                [
                    'label' => 'Cross-Cradle Position',
                    'why'   => 'Iharap si baby sa iyo at suportahan ang ulo at leeg gamit ang braso na nasa kabilang panig ng suso na gagamitin. Maaaring maglagay ng unan sa kandungan para masuportahan ang braso. Ilapit ang tiyan ni baby sa tiyan mo upang hindi niya kailangang pumihit para sumuso.',
                ],
                [
                    'label' => 'Football Hold o Underarm Position',
                    'why'   => 'Ilagay si baby sa ilalim ng braso sa panig ng suso na gagamitin, habang nakaturo sa likod mo ang mga paa niya at suportado ang ulo at leeg. Maaaring makatulong ito kung kambal ang pinapasuso o kung nais mong makita nang mabuti ang pagkakadikit ni baby. Huwag hayaang yumuko ang leeg niya o dumikit ang baba sa dibdib.',
                ],
                [
                    'label' => 'Laid-Back Position',
                    'why'   => 'Sumandal nang bahagya sa sofa o kama—huwag nakahiga nang patag—at gumamit ng mga unan upang masuportahan ang likod, balikat, at leeg. Ilagay si baby sa harap mo, tiyan niya sa tiyan mo, at tiyaking nakikita ang mukha at malayang nakakahinga. Kung hindi komportable, subukan ang posisyong nakatagilid.',
                ],
                [
                    'label' => 'Lying-Down o Side-Lying Position',
                    'why'   => 'Humiga nang patagilid habang nakaharap sa iyo si baby. Maaari itong maging komportable habang nagpapahinga o pagkatapos ng cesarean section. Itapat ang ilong ni baby sa utong upang hindi niya kailangang pilipitin o iyuko ang leeg para sumuso. Panatilihing malaya ang ilong at bibig niya; pagkatapos magpasuso, ilagay siya sa hiwalay at ligtas na tulugan.',
                ],
            ],
        ],
        [
            'heading' => 'Mga Benepisyo ng Gatas ng Ina',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Sustansyang angkop sa pangangailangan ni baby',
                    'why'   => 'Ang gatas ng ina ay nagbibigay ng mahahalagang sustansya para sa paglaki ng sanggol. Sa unang anim na buwan, inirerekomenda ang eksklusibong pagpapasuso kung maaari; pagdating ng anim na buwan, simulan ang angkop na karagdagang pagkain habang ipinagpapatuloy ang pagpapasuso.',
                ],
                [
                    'label' => 'May antibodies na tumutulong laban sa impeksyon',
                    'why'   => 'May antibodies at iba pang pananggalang ang gatas ng ina na tumutulong sa immune system ng sanggol. Hindi nito ginagarantiya na hindi magkakasakit si baby, kaya mahalaga pa rin ang bakuna at regular na check-up.',
                ],
                [
                    'label' => 'Madaling tunawin at handa nang ipasuso',
                    'why'   => 'Para sa maraming sanggol, madaling tunawin ang gatas ng ina at hindi na kailangang ihanda o bilhin. Nakakatulong din ang pagpapasuso sa pagbubuklod ng magulang at sanggol.',
                ],
                [
                    'label' => 'Nakakatulong sa kalusugan at kapakanan ng ina',
                    'why'   => 'Maaaring makatulong ang pagpapasuso sa paghilom pagkatapos manganak at may mga benepisyo rin ito sa kalusugan ng ina. Kung masakit ang pagpapasuso, nahihirapang sumuso si baby, o may alalahanin sa dami ng gatas, kumonsulta sa health worker o breastfeeding counselor.',
                ],
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'baby-milestones' => [
    'title'    => 'Mga Developmental Milestones ng Sanggol',
    'icon'     => 'fa-chart-line',
    'image'    => 'images/pic7.webp',
    'color'    => '#5A6B47',
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
'child-nutrition' => [
    'title'    => 'Gabay sa Pagkain at Nutrisyon ng Bata',
    'icon'     => 'fa-bowl-food',
    'image'    => 'images/child_nutrition.jpg',
    'color'    => '#5A6B47',
    'source'   => 'DOH Philippines complementary feeding guidance; National Nutrition Council Pinggang Pinoy for Kids',
    'intro'    => 'Mahalaga ang sapat at iba-ibang pagkain habang lumalaki ang bata. Gamitin ang gabay na ito bilang panimula at kumonsulta sa health center para sa payong angkop sa edad, kalusugan, at pangangailangan ng iyong anak.',
    'sections' => [
        [
            'heading' => 'Pinggang Pinoy para sa mga Bata',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'GO foods para sa enerhiya',
                    'why'   => 'Pumili ng kanin, mais, kamote, tinapay, o iba pang pagkaing nagbibigay-enerhiya. Iba-ibahin ang mga ito at iayon ang dami at lambot sa edad at kakayahan ng bata.',
                ],
                [
                    'label' => 'GROW foods para sa paglaki ng katawan',
                    'why'   => 'Isama ang pagkaing may protina tulad ng itlog, isda, karne, beans, o tokwa. Pumili ng ligtas na paghahanda at alisin ang tinik o matitigas na bahagi upang mabawasan ang panganib na mabulunan.',
                ],
                [
                    'label' => 'GLOW foods para sa kalusugan',
                    'why'   => 'Mag-alok ng iba-ibang prutas at gulay. Hugasan at ihanda ang mga ito nang malinis at gawing angkop ang laki at lambot sa kakayahan ng bata sa pagnguya at paglunok.',
                ],
                [
                    'label' => 'Tubig at masustansyang inumin',
                    'why'   => 'Mag-alok ng malinis na tubig ayon sa edad at gabay ng health worker. Limitahan ang matatamis na inumin; hindi dapat ipalit ang mga ito sa masustansyang pagkain o gatas na angkop sa edad.',
                ],
            ],
        ],
        [
            'heading' => 'Ligtas na Complementary Feeding Simula 6 na Buwan',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Simulan ang angkop na pagkain sa edad na 6 na buwan',
                    'why'   => 'Sa edad na ito, kailangan na ng karamihan sa mga sanggol ng karagdagang pagkain kasabay ng gatas ng ina. Magsimula sa kaunting dami at malambot na pagkain, at unti-unting dagdagan ang dami at lapot habang natututo siyang kumain.',
                ],
                [
                    'label' => 'Ipagpatuloy ang pagpapasuso hanggang 2 taon o higit pa',
                    'why'   => 'Panatilihin ang madalas at ayon-sa-pangangailangang pagpapasuso habang nagbibigay ng angkop na pagkain. Humingi ng payo sa health worker kung may alalahanin sa paglaki, pagkain, o pagpapasuso.',
                ],
                [
                    'label' => 'Pakainin nang tumutugon sa senyales ng bata',
                    'why'   => 'Tulungan at hikayatin ang bata na kumain nang matiyaga at mahinahon. Sundan ang senyales ng gutom at pagkabusog; huwag pilitin, pagalitan, o gambalain habang kumakain.',
                ],
                [
                    'label' => 'Panatilihin ang kalinisan at ligtas na paghahanda',
                    'why'   => 'Maghugas ng kamay bago maghanda at magpakain, gumamit ng malinis na kagamitan at ligtas na tubig, at ihanda nang sariwa at maayos ang pagkain. Bantayan palagi ang bata habang kumakain upang mabawasan ang panganib na mabulunan.',
                ],
                [
                    'label' => 'Iba-ibahin ang pagkain at unti-unting dagdagan ang texture',
                    'why'   => 'Mag-alok ng iba-ibang pagkain mula sa mga pangkat na GO, GROW, at GLOW, pati mga pagkaing mayaman sa iron ayon sa payo ng health worker. Unti-unting dagdagan ang lapot at texture habang lumalaki at natututong ngumunguya ang bata.',
                ],
                [
                    'label' => 'Iangkop ang dalas ng pagkain sa edad',
                    'why'   => 'Nagbabago ang dami at dalas ng pagkain habang lumalaki ang bata. Ang gabay na nasa ibinigay na materyal ay nagmumungkahi ng 2–3 pagkain bawat araw para sa 6–8 buwan at 3–4 para sa 9–23 buwan, na may 1–2 meryenda kung kailangan. Ipaangkop ito sa health worker batay sa pangangailangan ng iyong anak.',
                ],
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'developmental-red-flags' => [
    'title'    => 'Mga Palatandaan sa Pag-unlad na Dapat Ipasuri',
    'icon'     => 'fa-clipboard-check',
    'image'    => 'images/child_development.jpg',
    'color'    => '#5A6B47',
    'source'   => 'Preventive Pediatric Health Care Handbook (2018), Philippine Pediatric Society; DOH child development guidance',
    'intro'    => 'Nagkakaiba-iba ang bilis ng pag-unlad ng mga bata. Ang mga halimbawang palatandaan sa ibaba ay hindi diagnosis o kumpletong screening test. Kung may napapansin kang pagkaantala, pagkawala ng dating kakayahan, o alalahanin sa pag-unlad, kumonsulta sa pediatrician o health center.',
    'sections' => [
        [
            'heading' => 'Galaw at Malalaking Kalamnan',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Hindi gumugulong o nakauupo nang may suporta sa mga inaasahang buwan',
                    'why'   => 'Ayon sa gabay na nasa materyal, dapat ipasuri kung hindi pa gumugulong pagsapit ng mga 5 buwan o hindi nakauupo nang may suporta pagsapit ng mga 7–8 buwan. Iba-iba ang bata; ipasuri rin kung tila naninigas o sobrang malambot ang katawan.',
                ],
                [
                    'label' => 'Hindi nakakatayo habang may hawak o hindi pa nakakalakad',
                    'why'   => 'Ipinapakita ng materyal ang pagtayo habang may suporta sa mga 9–10 buwan at paglalakad pagsapit ng mga 15 buwan bilang mga kakayahang dapat subaybayan. Kung hindi ito nagagawa o nawawala ang dating kakayahan, magtanong sa health worker.',
                ],
                [
                    'label' => 'Patuloy na hirap sa pagtalon o pagbabalanse habang lumalaki',
                    'why'   => 'Ang kawalan ng kakayahang tumalon gamit ang dalawang paa sa bandang 2½ taon o tumayo sandali sa isang paa sa bandang 3 taon ay mga halimbawang dapat talakayin sa doktor, lalo na kung may iba pang alalahanin sa galaw.',
                ],
            ],
        ],
        [
            'heading' => 'Paggamit ng Kamay at Maliliit na Kalamnan',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Hindi kumukuha o humahawak ng maliliit na bagay habang lumalaki',
                    'why'   => 'Subaybayan kung hindi pa ginagamit ang hinlalaki at hintuturo sa pagkuha ng maliliit na bagay bandang 10–11 buwan, o kung hirap humawak ng laruan sa magkabilang kamay. Banggitin ito sa regular na check-up.',
                ],
                [
                    'label' => 'Hindi nagsisimulang magsulat o gumuhit ng simpleng marka',
                    'why'   => 'Maaaring ipasuri kung hindi nagsisimulang mag-scribble bandang 2 taon o hindi nakagagaya ng simpleng hugis habang papalapit sa edad na 4–5 taon. Isaalang-alang din ang pagkakataong nakapagsanay ang bata.',
                ],
            ],
        ],
        [
            'heading' => 'Pagsasalita at Pag-unawa',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Walang babbling o tunog ng pagsasalita sa bandang 6 na buwan',
                    'why'   => 'Kung hindi pa nagba-babble ang sanggol, kumonsulta upang masuri ang pandinig at komunikasyon. Ipaalam din kung hindi tumutugon ang bata sa tunog o boses.',
                ],
                [
                    'label' => 'Walang makabuluhang salita o hirap umunawa ng simpleng panuto',
                    'why'   => 'Ipaalam sa health worker kung walang makabuluhang salita sa bandang 16–18 buwan, o kung hindi nakauunawa ng simpleng panuto habang papalapit sa 2 taon. Ang maagang pagsusuri ay makakatulong matukoy kung anong suporta ang kailangan.',
                ],
                [
                    'label' => 'Nauutal, hindi malinaw, o hindi umuunlad ang komunikasyon',
                    'why'   => 'Kung hirap umunawa o gumamit ng salita kumpara sa dati, o may napansing pagkawala ng salita at iba pang kasanayan, huwag maghintay—ipakonsulta sa pediatrician o health center.',
                ],
            ],
        ],
        [
            'heading' => 'Pag-iisip, Pakikisalamuha, at Pag-uugali',
            'type'    => 'detailed',
            'items'   => [
                [
                    'label' => 'Hindi tumitingin, ngumingiti, o nakikipag-ugnayan sa tao',
                    'why'   => 'Banggitin sa doktor kung kakaunti o walang pagtugon sa mukha at boses, walang ngiti o pakikipaglaro habang lumalaki, o hindi naghahanap ng bagay na naitago sa simpleng laro.',
                ],
                [
                    'label' => 'Hindi nakikilahok sa simpleng laro o pang-araw-araw na gawain',
                    'why'   => 'Ipasuri kung hirap makilahok sa simpleng pakikipaglaro, hindi nakasusunod sa payak na tagubilin, o hindi natututo ng mga kasanayang inaasahan sa edad. Isulat ang mga napapansin at kailan ito nangyayari upang maibahagi sa health worker.',
                ],
                [
                    'label' => 'Nawawala ang dating kasanayan sa anumang edad',
                    'why'   => 'Ang pagkawala ng dati nang nagagawang salita, galaw, pakikisalamuha, o iba pang kasanayan ay dahilan para kumonsulta agad. Hindi kailangang hintayin ang susunod na milestone bago humingi ng tulong.',
                ],
            ],
        ],
        [
            'heading' => 'Ano ang Dapat Gawin?',
            'type'    => 'list',
            'items'   => [
                'Ibahagi sa pediatrician o health center ang partikular na napansin at edad kung kailan ito nagsimula',
                'Dalhin ang talaan ng bakuna, check-up, at mga halimbawa ng kilos o tunog ng bata kung mayroon',
                'Huwag gamitin ang isang palatandaan lamang para mag-diagnose; kailangan ng propesyonal na pagsusuri',
                'Kung may pagkawala ng dating kakayahan, magpakonsulta sa lalong madaling panahon',
            ],
        ],
    ],
],

// ═══════════════════════════════════════════════════════════
'baby-safety' => [
    'title'    => 'Kaligtasan ng Sanggol at Batang Bata',
    'icon'     => 'fa-shield-halved',
    'image'    => 'images/pic2.jpg',
    'color'    => '#5A6B47',
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
    'image'    => 'images/pic3.jpg',
    'color'    => '#5A6B47',
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
    'image'    => 'images/pic13.webp',
    'color'    => '#5A6B47',
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
    'image'    => 'images/pic14.webp',
    'color'    => '#5A6B47',
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

]; // end $articles;
