<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Faq extends BaseConfig
{
    /**
     * FAQ categories rendered on the public site.
     *
     * Each category contains:
     * - id: string slug used for filters
     * - title: display heading
     * - description: helper copy
     * - icon: Font Awesome class
     * - questions: array of Q&A pairs (slug, question, answer, hindi)
     *
     * @var array<int,array<string,mixed>>
     */
    public array $categories = [
        [
            'id' => 'scheme-basics',
            'title' => 'Scheme Basics',
            'description' => 'Eligibility, coverage, and what the cashless programme offers.',
            'icon' => 'fa-circle-info',
            'questions' => [
                [
                    'slug' => 'overview',
                    'question' => 'What is the MPPGCL Cashless Health Scheme?',
                    'answer' => 'A contributory and optional family-floater health plan for employees, pensioners, work-charge staff, and eligible dependents of MPPGCL and the associated power companies. Treatment at empanelled hospitals is cashless through the Implementation Support Agency (ISA).',
                    'hindi' => 'यह एक अंशदायी नकद रहित स्वास्थ्य योजना है जो MPPGCL तथा संबद्ध पावर कंपनियों के कर्मचारियों, पेंशनर्स, वर्क-चार्ज स्टाफ और आश्रितों को पैनल अस्पतालों में कैशलेस उपचार उपलब्ध कराती है।'
                ],
                [
                    'slug' => 'eligibility',
                    'question' => 'Who is eligible to join the scheme?',
                    'answer' => 'All regular, contractual, work-charge, and retired employees (including family pensioners) of MPPGCL, MPPTCL, DISCOMs, and MPPMCL along with their eligible dependents as defined in the policy.',
                    'hindi' => 'MPP GCL, MPPTCL, डिस्कॉम और MPPMCL के नियमित, संविदा, वर्क-चार्ज तथा सेवानिवृत्त कर्मचारी (परिवार पेंशनर्स सहित) तथा उनकी परिभाषित आश्रित जोड़ सकते हैं।'
                ],
                [
                    'slug' => 'coverage-options',
                    'question' => 'What coverage options are available?',
                    'answer' => 'Three options are offered. Premiums start at ₹1,500 / ₹1,800 / ₹2,200 per family per month with corresponding health risk covers of ₹5 lakh / ₹10 lakh / ₹25 lakh. Room rent limits and preventive check-up benefits scale with the selected option.',
                    'hindi' => 'योजना में 5 लाख, 10 लाख और 25 लाख रुपये के स्वास्थ्य कवर वाले तीन विकल्प हैं। प्रत्येक विकल्प के साथ मासिक अंशदान, कक्ष किराया सीमा तथा वार्षिक प्रिवेंटिव हेल्थ चेक-अप की सुविधा निर्धारित है।'
                ],
                [
                    'slug' => 'what-covered',
                    'question' => 'What expenses are covered?',
                    'answer' => 'In-patient hospitalization, approved daycare procedures, maternity, organ transplant, chemotherapy, dialysis, ambulance, and other CGHS/GIPSA aligned benefits. Preventive check-ups are cashless for higher options.',
                    'hindi' => 'योजना में भर्ती उपचार, डे-केयर प्रक्रियाएं, मातृत्व, अंग प्रत्यारोपण, कीमोथेरपी, डायलिसिस, एम्बुलेंस तथा CGHS / GIPSA मानकों के अनुरूप अन्य खर्च शामिल हैं।'
                ],
            ],
        ],
        [
            'id' => 'enrolment',
            'title' => 'Enrollment & Profile',
            'description' => 'Registration, corrections, and documents to keep ready.',
            'icon' => 'fa-id-card',
            'questions' => [
                [
                    'slug' => 'how-to-register',
                    'question' => 'How can a beneficiary enroll?',
                    'answer' => 'Use the registration link available under the Login section of this portal. Pensioners or existing employees register with their PPO/employee details and OTP on the registered mobile.',
                    'hindi' => 'लॉगिन अनुभाग में उपलब्ध रजिस्ट्रेशन लिंक से पेंशनर अथवा कर्मचारी OTP के माध्यम से पंजीकरण कर सकते हैं।'
                ],
                [
                    'slug' => 'already-registered',
                    'question' => 'I registered earlier via Zoho/ERP. Do I need to register again?',
                    'answer' => 'No. Use the registered mobile number to sign in with OTP and continue. Duplicate enrolment is not required.',
                    'hindi' => 'यदि आपने पहले ही रजिस्टर कर लिया है तो दोबारा रजिस्टर करने की आवश्यकता नहीं है। अपने पंजीकृत मोबाइल से OTP द्वारा लॉगिन करें।'
                ],
                [
                    'slug' => 'edit-request',
                    'question' => 'What if I made a mistake while filling the form?',
                    'answer' => 'Login, open “View Submitted Form”, and use “Request Edit”. The edit option is allowed only once, so review before resubmitting.',
                    'hindi' => 'लॉगिन कर “View Submitted Form” खोलें और “Request Edit” पर क्लिक करें। यह सुविधा केवल एक बार दी जाती है, इसलिए बदलाव ध्यान से करें।'
                ],
                [
                    'slug' => 'info-required',
                    'question' => 'What information should I keep ready for registration?',
                    'answer' => 'Keep Aadhaar, PPO/employee number, date of joining or retirement, PRAN (if applicable), bank details, and the mobile handset for OTP. Also keep Aadhaar numbers of all dependents and review the definition of family.',
                    'hindi' => 'रजिस्ट्रेशन से पहले आधार, PPO/कर्मचारी नंबर, नियुक्ति/सेवानिवृत्ति तिथि, PRAN (यदि लागू हो), बैंक विवरण और OTP हेतु मोबाइल तैयार रखें। सभी आश्रितों के आधार नंबर भी तैयार रखें।'
                ],
            ],
        ],
        [
            'id' => 'dependents-network',
            'title' => 'Dependents & Network Hospitals',
            'description' => 'Managing dependents, locating hospitals, and claim routing.',
            'icon' => 'fa-hospital-user',
            'questions' => [
                [
                    'slug' => 'dependents',
                    'question' => 'How do I know which dependents are eligible?',
                    'answer' => 'Refer to the “Definition of Family” in the policy. Only dependents that meet the age, relationship, and income criteria should be added to the portal.',
                    'hindi' => 'नीति में दी गई “परिवार की परिभाषा” देखें। केवल वही आश्रित जोड़ें जो आयु, संबंध और आय मानकों पर खरे उतरते हों।'
                ],
                [
                    'slug' => 'hospital-categories',
                    'question' => 'What are the categories of network hospitals?',
                    'answer' => 'Category 1: CGHS(+) contracted hospitals. Category 2: Government/Charitable/PSU hospitals. Category 3: Power company contracted hospitals at non-CGHS rates. Category 4: ISA group contracted hospitals. Category 5: AYUSH centres approved by government/NABH/CGHS/power company.',
                    'hindi' => 'केटेगरी 1 CGHS(+) दरों पर अनुबंधित अस्पताल, केटेगरी 2 सरकारी/ट्रस्ट/PSU अस्पताल, केटेगरी 3 पावर कंपनी द्वारा अनुबंधित अस्पताल, केटेगरी 4 ISA समूह द्वारा अनुबंधित अस्पताल और केटेगरी 5 में स्वीकृत आयुष केंद्र आते हैं।'
                ],
                [
                    'slug' => 'where-treated',
                    'question' => 'Which hospitals are included?',
                    'answer' => 'Cashless treatment is available in empanelled hospitals across Madhya Pradesh and selected cities nationwide. Always refer to the latest panel before admission.',
                    'hindi' => 'मध्यप्रदेश और अन्य चुनिंदा शहरों के पैनल अस्पतालों में कैशलेस उपचार उपलब्ध है। भर्ती से पहले ताज़ा सूची अवश्य देखें।'
                ],
                [
                    'slug' => 'find-hospitals',
                    'question' => 'Where can I find the list of empanelled hospitals?',
                    'answer' => 'Use the “Search Hospital” widget on this page. Select state and city to download the panel or view contact details instantly.',
                    'hindi' => 'इस पृष्ठ पर “Search Hospital” अनुभाग में राज्य और शहर चुन कर सूची देखी जा सकती है।'
                ],
                [
                    'slug' => 'claim-process',
                    'question' => 'How are claims processed?',
                    'answer' => 'Cashless claims are settled directly between the hospital and ISA. If you opt for reimbursement (e.g., non-network hospital), submit documents through the nodal office as per CGHS/GIPSA norms.',
                    'hindi' => 'कैशलेस दावा अस्पताल और ISA के बीच निपटाया जाता है। प्रतिपूर्ति के मामलों में दस्तावेज़ नोडल कार्यालय के माध्यम से CGHS/GIPSA मानकों के अनुसार जमा करें।'
                ],
            ],
        ],
        [
            'id' => 'support',
            'title' => 'Support & Contacts',
            'description' => 'Implementation Support Agency and helpline information.',
            'icon' => 'fa-headset',
            'questions' => [
                [
                    'slug' => 'scheme-manager',
                    'question' => 'Who manages the scheme?',
                    'answer' => 'An IRDAI-approved Implementation Support Agency (ISA) handles registration, hospital empanelment, cashless processing, reimbursements, and compliance on behalf of MPPGCL.',
                    'hindi' => 'यह योजना IRDAI द्वारा अनुमोदित Implementation Support Agency (ISA) के माध्यम से चलाई जाती है जो रजिस्ट्रेशन, अस्पताल पैनल, क्लेम और अनुपालन का प्रबंधन करती है।'
                ],
                [
                    'slug' => 'what-is-isa',
                    'question' => 'What is an ISA?',
                    'answer' => 'The ISA operates the portal, issues e-cards, coordinates with hospitals, settles claims, runs the 24×7 helpdesk, and ensures policy compliance.',
                    'hindi' => 'ISA पोर्टल चलाता है, ई-कार्ड जारी करता है, अस्पताल समन्वय और क्लेम निपटान करता है तथा 24×7 हेल्पडेस्क संचालित करता है।'
                ],
                [
                    'slug' => 'isa-name',
                    'question' => 'Who is the ISA for this scheme?',
                    'answer' => 'M/s MedSave Health Insurance TPA Ltd., New Delhi is the appointed Implementation Support Agency.',
                    'hindi' => 'इस योजना के लिए नियुक्त ISA एम/एस मेडसेव हेल्थ इंश्योरेंस TPA लिमिटेड, नई दिल्ली है।'
                ],
                [
                    'slug' => 'isa-contact',
                    'question' => 'How can I contact the ISA?',
                    'answer' => 'Toll-free: 1800-120-111234. Senior citizen helpline: 9319810070. Email: info@medsave.in. Website: www.medsave.in.',
                    'hindi' => 'टोल-फ्री 1800-120-111234, वरिष्ठ नागरिक हेल्पलाइन 9319810070, ईमेल info@medsave.in, वेबसाइट www.medsave.in।'
                ],
                [
                    'slug' => 'mppgcl-helpline',
                    'question' => 'How can I reach the MPPGCL helpline?',
                    'answer' => 'Helpline-1: 7587951332, Helpline-2: 7587951334, Technical support email: cashless.mppgcl@mppgcl.mp.gov.in (10:00 AM–6:00 PM on working days).',
                    'hindi' => 'हेल्पलाइन-1: 7587951332, हेल्पलाइन-2: 7587951334 और तकनीकी सहायता ईमेल cashless.mppgcl@mppgcl.mp.gov.in (कार्यालय समय में)।'
                ],
            ],
        ],
        [
            'id' => 'claims-policy',
            'title' => 'Claims & Policy Rules',
            'description' => 'Reimbursements, option changes, PED cover, no-claim bonus, and copay slabs.',
            'icon' => 'fa-scale-balanced',
            'questions' => [
                [
                    'slug' => 'non-network',
                    'question' => 'How are non-network hospital expenses reimbursed?',
                    'answer' => 'Submit bills through ISA. Expenses up to the selected health risk cover (₹5/10/25 lakh) are reimbursable. Amounts beyond the cover limit are reimbursed only for working employees; pensioners bear expenses above their option limit.',
                    'hindi' => 'गैर-पैनल अस्पताल में इलाज कराने पर बिल ISA के माध्यम से जमा करें। चुने गए स्वास्थ्य कवर लिमिट तक का खर्च प्रतिपूर्ति योग्य है। लिमिट से ऊपर का खर्च केवल कार्यरत कर्मचारी के लिए पात्र है, पेंशनर स्वयं वहन करेंगे।'
                ],
                [
                    'slug' => 'pension-company',
                    'question' => 'I worked for Company X but receive pension from Company Y. Which company treats me as beneficiary?',
                    'answer' => 'You are treated as a beneficiary of the company that disburses your pension. So, if you draw pension from the Generating Company you fall under it, even if you served in a DISCOM, and vice versa.',
                    'hindi' => 'जिस कंपनी से आप पेंशन प्राप्त कर रहे हैं वहीं आपको लाभार्थी माना जाएगा, भले ही सेवा किसी अन्य पावर कंपनी में हुई हो।'
                ],
                [
                    'slug' => 'join-exit',
                    'question' => 'What are the provisions for joining, exiting, or renewing the scheme?',
                    'answer' => 'No mid-year enrollments are allowed once an annual period starts. Unless you opt out one month before the next cycle, the plan auto-renews with the same option and contributions. Requests to join, exit, or change option must reach the nodal officer at least a month before the new cycle.',
                    'hindi' => 'वार्षिक अवधि शुरू होने के बाद बीच में प्रवेश नहीं दिया जाता। यदि अगले वर्ष से पहले एक महीना पूर्व आप विकल्प नहीं बदलते तो योजना स्वतः नवीनीकृत हो जाएगी। नया जुड़ाव/निकास/विकल्प परिवर्तन के अनुरोध अगले चक्र से एक माह पहले जमा करें।'
                ],
                [
                    'slug' => 'change-option',
                    'question' => 'Can I change my option mid-year?',
                    'answer' => 'No. Option changes are only allowed from the next annual period. If you upgrade, the differential contribution becomes payable.',
                    'hindi' => 'विकल्प में बदलाव वर्ष के बीच नहीं किया जा सकता। नया विकल्प केवल अगले वार्षिक चक्र में प्रभावी होगा और बढ़े हुए अंशदान का भुगतान करना पड़ेगा।'
                ],
                [
                    'slug' => 'ped',
                    'question' => 'Are pre-existing diseases covered?',
                    'answer' => 'Yes. All pre-existing diseases (PED) and ongoing treatments of eligible beneficiaries are covered from day one.',
                    'hindi' => 'हाँ, सभी प्री-एग्जिस्टिंग डिज़ीज़ (PED) का उपचार योजना में सम्मिलित है।'
                ],
                [
                    'slug' => 'no-claim-bonus',
                    'question' => 'Is there a No Claim Bonus?',
                    'answer' => 'If no claim (including preventive check-up) is made in a year, the health risk cover can increase by 5% the next year, up to a 50% cap on the base cover.',
                    'hindi' => 'यदि पूरे वर्ष कोई दावा नहीं होता तो अगले वर्ष स्वास्थ्य कवर राशि में 5% तक वृद्धि (अधिकतम 50% सीमा तक) की जा सकती है।'
                ],
                [
                    'slug' => 'copay',
                    'question' => 'What are the copay rules?',
                    'answer' => 'Category 1 and 2 hospitals have 0% copay. Category 3 copay is 25%, Category 4 is 30%, Category 5 (AYUSH) is 50% (subject to ₹5k/₹12k/₹24k annual caps for Options 1/2/3). Non-network reimbursements follow CGHS(+) rates.',
                    'hindi' => 'कैटेगरी 1 एवं 2 अस्पतालों में को-पे 0% है। कैटेगरी 3 में 25%, कैटेगरी 4 में 30% तथा कैटेगरी 5 (आयुष) में 50% को-पे लागू है (विकल्प 1/2/3 के लिए क्रमशः ₹5k/₹12k/₹24k सीमा)। गैर-पैनल प्रतिपूर्ति CGHS(+) दरों पर होती है।'
                ],
            ],
        ],
    ];
}
