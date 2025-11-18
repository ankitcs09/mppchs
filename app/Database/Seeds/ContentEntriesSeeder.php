<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class ContentEntriesSeeder extends Seeder
{
    public function run()
    {
        $builder = $this->db->table('content_entries');
        $now     = Time::now('UTC')->toDateTimeString();

        $entries = [
            [
                'type'           => 'blog',
                'title'          => 'Digital Claim Desk Goes Live Across MPPGCL',
                'slug'           => 'digital-claim-desk-goes-live',
                'summary'        => 'The new claim desk inside the beneficiary portal now routes documents directly to ISA reviewers, cutting the average turnaround time to three days.',
                'body'           => '<p>The claim desk module has been activated for all beneficiaries. Upload discharge summaries, supporting bills, and HR approvals straight from the dashboard. Each submission receives an acknowledgement ID, and beneficiaries get SMS/email alerts when ISA teams pick up the request.</p><p>Hospital coordinators now see the same trail, enabling faster clarifications and reduced phone-based follow ups.</p>',
                'author_name'    => 'MPPCHS Programme Office',
                'author_title'   => 'Claims Modernisation Team',
                'featured_image' => 'https://images.unsplash.com/photo-1589758438368-0ad531db3366?auto=format&fit=crop&w=900&q=80',
                'tags'           => 'claims, digitisation',
                'status'         => 'published',
                'is_featured'    => 1,
                'display_order'  => 0,
                'published_at'   => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type'           => 'blog',
                'title'          => 'ISA Helpline Adds Weekend Shift',
                'slug'           => 'isa-helpline-weekend-shift',
                'summary'        => 'Beneficiaries can now reach the ISA helpdesk on Saturdays between 10 AM and 4 PM for admission co-ordination and hospital queries.',
                'body'           => '<p>To handle the surge in elective procedures, ISA has introduced a rotating weekend desk. The line is reachable at 0755-5551010 and focuses on hospital admission pre-authorisations, dependent coverage clarifications, and emergency referrals.</p><p>Weekday coverage remains unchanged with dedicated queues for pensioners and dependents.</p>',
                'author_name'    => 'ISA Operations',
                'author_title'   => 'Helpdesk Lead',
                'featured_image' => 'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=900&q=80',
                'tags'           => 'helpdesk, support',
                'status'         => 'published',
                'is_featured'    => 0,
                'display_order'  => 0,
                'published_at'   => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type'           => 'blog',
                'title'          => 'Preventive Health Camps Roll Out in Singrauli & Sarni',
                'slug'           => 'preventive-health-camps-singrauli-sarni',
                'summary'        => 'ISA, in partnership with empanelled hospitals, organised preventive check-up camps covering 600 families across Singrauli and Sarni.',
                'body'           => '<p>The camps included heart-health screening, diabetes panels, and consultations with dieticians. Beneficiaries enrolled under Option 2 and Option 3 utilised their annual preventive allowance at no additional cost.</p><p>Upcoming camps are planned for Jabalpur and Amarkantak; watch the dashboard announcements section for exact slots.</p>',
                'author_name'    => 'Benefit Enablement Cell',
                'author_title'   => 'Field Co-ordination',
                'featured_image' => 'https://images.unsplash.com/photo-1512069772995-ec65ed45afd6?auto=format&fit=crop&w=900&q=80',
                'tags'           => 'prevention, camps',
                'status'         => 'published',
                'is_featured'    => 0,
                'display_order'  => 0,
                'published_at'   => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type'           => 'testimonial',
                'title'          => 'Cardiac Care at Bhopal',
                'quote'          => 'The ISA team booked my admission in less than two hours and the cardiology unit handled all paperwork. I focused only on recovery.',
                'author_name'    => 'Shri S. Verma',
                'author_title'   => 'Retired Chief Engineer',
                'status'         => 'published',
                'is_featured'    => 1,
                'display_order'  => 1,
                'published_at'   => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type'           => 'testimonial',
                'title'          => 'Mother & Child Support',
                'quote'          => 'My daughter-in-law’s delivery was cashless at the empanelled hospital. All baby care bills were approved instantly.',
                'author_name'    => 'Smt. R. Dubey',
                'author_title'   => 'Family Pensioner, Sarni',
                'status'         => 'published',
                'is_featured'    => 1,
                'display_order'  => 2,
                'published_at'   => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type'           => 'testimonial',
                'title'          => 'Dependents Added Seamlessly',
                'quote'          => 'The “Edit my details” flow let me upload my grandson’s birth certificate and the approval arrived in two days.',
                'author_name'    => 'Shri V. Bhosale',
                'author_title'   => 'Pensioner, Jabalpur',
                'status'         => 'published',
                'is_featured'    => 0,
                'display_order'  => 3,
                'published_at'   => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
        ];

        foreach ($entries as $entry) {
            $builder->insert($entry);
        }
    }
}

