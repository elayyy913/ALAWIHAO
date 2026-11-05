<?php

function registrationFormGuideNormalize(string $text): string
{
    $text = function_exists('mb_strtolower')
        ? mb_strtolower($text, 'UTF-8')
        : strtolower($text);
    $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
    return trim(is_string($normalized) ? $normalized : $text);
}

function registrationFormGuideMatch(string $question, string $audience): ?array
{
    $guides = [
        [
            'terms' => ['phic cat', 'phic-cat', 'phic category', 'philhealth category', 'philcat'],
            'answer' => 'Sa maternal registration form, ang field ay “PHIC CAT,” ibig sabihin ay PhilHealth Category—ang kategorya o uri ng PhilHealth membership/coverage. Kung ang tinutukoy mo naman ay “PhilCAT,” ibang acronym iyon para sa Philippine Coalition Against Tuberculosis. Kung hindi ka sigurado sa ilalagay sa PHIC CAT, magtanong sa health center o tingnan ang iyong PhilHealth record; huwag manghula.',
            'user_url' => 'user_maternal_reg.php',
            'admin_url' => 'admin_maternal_reg.php',
        ],
        [
            'terms' => ['philhealth number', 'philhealth no', 'philhealth #', 'phic number'],
            'answer' => 'Ang PhilHealth number ay iyong personal na identification number sa PhilHealth. Ilagay lamang ang sarili mong numero mula sa iyong PhilHealth record/card. Huwag ipadala ang numero rito sa chat.',
            'user_url' => 'user_maternal_reg.php',
            'admin_url' => 'admin_maternal_reg.php',
        ],
        [
            'terms' => ['family serial number', 'family serial', 'family no', 'family number'],
            'answer' => 'Ang Family Serial/No. ay household o family reference number para maiugnay ang mga record ng pamilya. Kung wala kang naibigay o hindi mo alam ang numero, magtanong muna sa health center sa halip na gumawa ng sariling numero.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['lmp', 'last menstrual period', 'huling regla'],
            'answer' => 'Ang LMP ay “Last Menstrual Period”—karaniwang inilalagay ang petsa kung kailan nagsimula ang pinakahuling regla. Kung hindi sigurado sa petsa, sabihin ito sa health worker sa halip na manghula.',
            'user_url' => 'user_maternal_reg.php',
            'admin_url' => 'admin_maternal_reg.php',
        ],
        [
            'terms' => ['rhu', 'rural health unit'],
            'answer' => 'Ang RHU ay “Rural Health Unit,” isang lokal na pasilidad/serbisyo ng pampublikong kalusugan. Sa Birth Plan field, piliin lang ito kung RHU ang planong pasilidad; maaari ring piliin ang hospital o lying-in clinic.',
            'user_url' => 'user_maternal_reg.php',
            'admin_url' => 'admin_maternal_reg.php',
        ],
        [
            'terms' => ['birth plan facility', 'birth plan', 'planned facility'],
            'answer' => 'Ang Birth Plan (Facility) ay kung saan planong manganak—halimbawa, hospital, RHU, o lying-in clinic. Talakayin sa health worker kung aling pasilidad ang angkop para sa iyo.',
            'user_url' => 'user_maternal_reg.php',
            'admin_url' => 'admin_maternal_reg.php',
        ],
        [
            'terms' => ['living children', 'number of living children', 'no of living children'],
            'answer' => 'Ang “No. of Living Children” ay bilang ng mga anak na buhay sa kasalukuyan. Ilagay ang bilang; kung hindi sigurado kung paano ito dapat bilangin sa iyong sitwasyon, magtanong sa health worker.',
            'user_url' => 'user_maternal_reg.php',
            'admin_url' => 'admin_maternal_reg.php',
        ],
        [
            'terms' => ['number of pregnancies', 'how many pregnancies', 'num preg', 'ilang beses na nanganak'],
            'answer' => 'Ang pregnancy/delivery history ay nagtatanong tungkol sa mga naunang pagbubuntis o panganganak. Hindi pareho ang bilang ng pagbubuntis at bilang ng mga batang naipanganak (halimbawa, kambal). Kung hindi malinaw kung aling bilang ang hinihingi, magtanong sa health worker.',
            'user_url' => 'user_maternal_reg.php',
            'admin_url' => 'admin_maternal_reg.php',
        ],
        [
            'terms' => ['blood type', 'blood group'],
            'answer' => 'Ang Blood Type ay uri ng dugo, gaya ng A+, O−, o AB+. Piliin lamang kung alam mo ito mula sa mapagkakatiwalaang medical record; kung hindi, piliin ang Unknown/N/A kung mayroon o magtanong sa health worker.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['middle initial', 'mi'],
            'answer' => 'Ang Middle Initial ay unang letra ng iyong middle name. Kung wala kang middle name, sundin ang hinihingi ng form o magtanong sa health center kung paano ito dapat punan.',
            'user_url' => 'user_maternal_reg.php',
            'admin_url' => 'admin_maternal_reg.php',
        ],
        [
            'terms' => ['ext', 'extension name', 'jr sr'],
            'answer' => 'Ang “Ext.” ay suffix o dugtong sa pangalan, gaya ng Jr. o Sr., kung bahagi ito ng legal na pangalan. Iwanang walang laman kung wala.',
            'user_url' => 'user_maternal_reg.php',
            'admin_url' => 'admin_maternal_reg.php',
        ],
        [
            'terms' => ['date of birth', 'dob', 'birth date'],
            'answer' => 'Ang Date of Birth/Birth Date ay petsa ng kapanganakan. Tiyaking tama ang araw, buwan, at taon ayon sa birth o valid ID record.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['place of birth', 'pob'],
            'answer' => 'Ang Place of Birth ay lugar kung saan ipinanganak ang bata, gaya ng pangalan ng ospital, clinic, o lugar na nakasaad sa birth record.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['birth weight', 'birth height'],
            'answer' => 'Ang Birth Weight at Birth Height ay timbang at haba/tangkad ng sanggol noong ipinanganak, hindi ang kasalukuyang sukat. Tingnan ang birth o medical record; kung wala nito, magtanong sa health center bago maglagay ng halaga.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['administered by', 'nagtrok', 'nagturok', 'health facility'],
            'answer' => 'Ang “Administered By” o “Nagturok / Health Facility” ay kung sinong health worker o anong pasilidad ang nagbigay ng bakuna. Ilagay ang pangalan ng health facility o provider na nakatala sa vaccination record.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['bcg'],
            'answer' => 'Ang BCG ay bakunang ginagamit laban sa malulubhang anyo ng tuberculosis (TB), lalo na sa mga bata. Itala lamang ito kung nasa vaccination record ng bata.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['hepa b', 'hepatitis b', 'hep b'],
            'answer' => 'Ang Hepa B ay bakuna laban sa Hepatitis B. Kung tinatanong kung naibigay na ito, tingnan ang vaccination record ng bata.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['pentavalent', 'penta vaccine'],
            'answer' => 'Ang Pentavalent vaccine ay pinagsamang bakuna laban sa limang sakit: diphtheria, tetanus, pertussis, hepatitis B, at Haemophilus influenzae type b (Hib). Itala lamang ang dose na nakasulat sa vaccination record.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['opv', 'oral polio vaccine'],
            'answer' => 'Ang OPV ay “Oral Polio Vaccine,” bakunang iniinom/ibinibigay sa bibig laban sa polio. Tingnan ang vaccination card para sa petsa at dose.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['ipv', 'inactivated polio vaccine'],
            'answer' => 'Ang IPV ay “Inactivated Polio Vaccine,” bakunang iniiniksyon laban sa polio. Tingnan ang vaccination card para sa petsa at dose.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['pcv', 'pneumococcal conjugate vaccine'],
            'answer' => 'Ang PCV ay “Pneumococcal Conjugate Vaccine,” bakunang tumutulong magprotekta laban sa ilang impeksiyong dulot ng pneumococcus. Itala lamang ito kung nasa vaccination record.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['mmr', 'measles mumps rubella'],
            'answer' => 'Ang MMR ay bakunang laban sa measles (tigdas), mumps, at rubella. Tingnan ang vaccination record ng bata para malaman kung naibigay na ito.',
            'user_url' => 'user_infant_reg.php',
            'admin_url' => 'admin_child_reg.php',
        ],
        [
            'terms' => ['miscarriage', 'nakunan'],
            'answer' => 'Ang “Miscarriage” ay pagkawala ng pagbubuntis bago mabuhay ang sanggol sa labas ng sinapupunan. Kung hindi sigurado kung aling outcome ang ilalagay, magtanong sa health worker.',
            'user_url' => 'user_maternal_reg.php',
            'admin_url' => 'admin_maternal_reg.php',
        ],
        [
            'terms' => ['stillbirth', 'patay na ipinanganak'],
            'answer' => 'Ang “Stillbirth” ay panganganak ng sanggol na walang senyales ng buhay. Para sa tamang pag-uuri sa form, magtanong sa health worker kung hindi malinaw sa iyong medical record.',
            'user_url' => 'user_maternal_reg.php',
            'admin_url' => 'admin_maternal_reg.php',
        ],
        [
            'terms' => ['twins', 'kambal', 'multiple birth'],
            'answer' => 'Ang “Twins” ay kambal—dalawang sanggol sa iisang panganganak. Ang “Multiple” ay higit sa dalawang sanggol. Gamitin ang bilang na nasa birth record.',
            'user_url' => 'user_maternal_reg.php',
            'admin_url' => 'admin_maternal_reg.php',
        ],
    ];

    $normalizedQuestion = ' ' . registrationFormGuideNormalize($question) . ' ';
    $bestMatch = null;
    $bestLength = 0;

    foreach ($guides as $guide) {
        foreach ($guide['terms'] as $term) {
            $normalizedTerm = registrationFormGuideNormalize($term);
            if ($normalizedTerm === '' || strpos($normalizedQuestion, ' ' . $normalizedTerm . ' ') === false) {
                continue;
            }

            $termLength = strlen($normalizedTerm);
            if ($termLength > $bestLength) {
                $bestMatch = $guide;
                $bestLength = $termLength;
            }
        }
    }

    if ($bestMatch === null) {
        return null;
    }

    $form = strpos($bestMatch['user_url'], 'maternal') !== false ? 'maternal' : 'child';
    $url = $audience === 'admin'
        ? $bestMatch['admin_url']
        : $bestMatch['user_url'];

    return [
        'answer' => $bestMatch['answer'],
        'links' => [[
            'label' => $form === 'maternal' ? 'Buksan ang Maternal Registration Form' : 'Buksan ang Child Registration Form',
            'url' => $url,
        ]],
    ];
}
