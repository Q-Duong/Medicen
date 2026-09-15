<?php

return [
    'seo' => [
        'title' => 'Clinical Insights & The Truth About Gonstead | DG Gonstead',
        'description' => 'A collection of objective and transparent medical answers about the Gonstead chiropractic method from leading experts.',
    ],

    'header' => [
        'title' => 'FAQ & Truths',
        'subtitle' => 'All your questions and doubts about the Gonstead method are answered transparently and objectively.',
    ],

    'defense_notice' => 'DG Gonstead is committed to providing accurate medical information based on scientific evidence and legal operating licenses. We are always ready for transparent dialogue with our clients.',

    'meta' => [
        'read_more' => 'Read detailed analysis',
        'author_label' => 'Medical Advisor:',
        'author' => 'Expert Dao Anh Dung',
        'author_title' => 'President of DG Gonstead',
        'disclaimer' => '* Medical Disclaimer: This analysis is for objective scientific reference only and is not a substitute for professional medical diagnosis or treatment protocols from a specialist.'
    ],

    'questions' => [
        // SLUG 1: Giữ nguyên key tiếng việt để đồng bộ URL
        'su-that-ve-phuong-phap-gonstead' => [
            'seo_title' => 'The Truth About the Gonstead Method in Vietnam | DG Gonstead',
            'seo_description' => 'An objective evaluation of practical efficacy, advantages, and common misconceptions among patients about the Gonstead method.',

            'question' => 'The truth about the Gonstead method in Vietnam: Is it as miraculous as rumored?',
            'date' => 'June 11, 2026', // Format YYYY-MM-DD cho Schema JSON-LD

            // Câu trả lời ngắn (Hiển thị ở Accordion)
            'short_answer' => 'Many social media ads tend to "glorify" chiropractic adjustment (Gonstead), claiming that a few "cracks" will completely cure a herniated disc. The truth is completely different. Gonstead is a mechanical medical technique that requires gradual intervention, not an instant magic cure.',

            // Phân tích chi tiết (Hiển thị ở trang Show)
            'full_content' => '
                <h3>1. Gonstead is not a "magic" cure for all diseases</h3>
                <p>Medical analysis content goes here... It is crucial to understand that chiropractic adjustments focus on the nervous system and spinal alignment, requiring a proper and realistic treatment plan.</p>

                <h3>2. Is it absolutely safe or are there risks?</h3>
                <p>Any medical intervention comes with risks if performed by someone without proper expertise. At reputable clinics, doctors must read full-spine X-rays before touching the patient...</p>
            ',
        ],

        // SLUG 2
        'phong-kham-dg-gonstead-co-uy-tin-khong' => [
            'seo_title' => 'Is DG Gonstead Clinic Reputable? Objective Evaluation',
            'seo_description' => 'Transparent information regarding operating licenses from the Department of Health and medical practice certificates of the doctors at DG Gonstead.',

            'question' => 'Is DG Gonstead Clinic reputable? Do you have an operating license?',
            'date' => 'June 11, 2026',
            'short_answer' => 'The reputation of a medical facility does not lie in advertising, but in the legality and expertise of its team. DG Gonstead is officially licensed by the Department of Health, and 100% of our practitioners hold valid medical practice certificates.',
            'full_content' => '
                <h3>1. Clear legal basis</h3>
                <p>DG Gonstead operates under the official license of the HCMC Department of Health (License No: 10406/HCM-GPHĐ). This ensures that our facility meets all strict safety and medical standards.</p>
                
                <h3>2. Certified Expertise</h3>
                <p>We strictly require all our doctors and therapists to hold valid Medical Practice Certificates issued by the Ministry of Health. Patients have the right to request these certificates before beginning any treatment.</p>
            ',
        ]
    ]
];
