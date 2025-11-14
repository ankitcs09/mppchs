<?php

namespace App\Controllers;

use App\Models\BeneficiaryModel;
use App\Models\CityModel;
use App\Models\ContentEntryModel;
use App\Models\HospitalModel;
use App\Models\StateModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Faq as FaqConfig;

class Home extends BaseController
{
    protected StateModel $states;
    protected CityModel $cities;
    protected HospitalModel $hospitals;
    protected BeneficiaryModel $beneficiaries;
    protected ContentEntryModel $contentEntries;

    public function __construct()
    {
        $this->states         = new StateModel();
        $this->cities         = new CityModel();
        $this->hospitals      = new HospitalModel();
        $this->beneficiaries  = new BeneficiaryModel();
        $this->contentEntries = new ContentEntryModel();
    }

    public function index(): string
    {
        $session      = session();
        $stats        = $this->collectStats();
        $stories      = $this->latestStories();
        $testimonials = $this->latestTestimonials();
        $faqConfig    = config(FaqConfig::class);

        return view('site/index', [
            'stats' => $stats,
            'isLoggedIn'   => (bool) $session->get('isLoggedIn'),
            'userName'     => $session->get('bname') ?? $session->get('username'),
            'dashboardUrl' => site_url('dashboard'),
            'loginUrl'     => site_url('login'),
            'logoutUrl'    => site_url('logout'),
            'stories'      => $stories,
            'testimonials' => $testimonials,
            'faqCategories' => $faqConfig->categories ?? [],
            'activeNav'    => 'home',
        ]);
    }

    public function benefits(): string
    {
        return view('site/pages/benefits', ['activeNav' => 'benefits']);
    }

    public function coverage(): string
    {
        return view('site/pages/coverage', ['activeNav' => 'coverage']);
    }

    public function contribution(): string
    {
        return view('site/pages/contribution', ['activeNav' => 'contribution']);
    }

    public function hospitals(): string
    {
        return view('site/pages/hospitals', [
            'stats'     => $this->collectStats(),
            'activeNav' => 'hospitals',
        ]);
    }

    public function faq(): string
    {
        $faqConfig = config(FaqConfig::class);
        return view('site/pages/faq', [
            'faqCategories' => $faqConfig->categories ?? [],
            'activeNav'     => 'faq',
        ]);
    }

    public function contact(): string
    {
        return view('site/pages/contact', ['activeNav' => 'contact']);
    }

    public function stats(): ResponseInterface
    {
        return $this->response->setJSON($this->collectStats());
    }

    private function collectStats(): array
    {
        $db            = db_connect();
        $hospitalTable = $this->hospitals->tableName();

        $coveredStates = $db->table($hospitalTable . ' n')
            ->select('s.state_id')
            ->join('cities c', 'c.city_id = n.city_id', 'inner')
            ->join('states s', 's.state_id = c.state_id', 'inner')
            ->distinct()
            ->countAllResults(false);

        $coveredCities = $db->table($hospitalTable . ' n')
            ->select('c.city_id')
            ->join('cities c', 'c.city_id = n.city_id', 'inner')
            ->distinct()
            ->countAllResults(false);

        return [
            'states'        => (int) $coveredStates,
            'cities'        => (int) $coveredCities,
            'hospitals'     => (int) $this->hospitals->countAllResults(),
            'beneficiaries' => (int) $this->beneficiaries->countAllResults(),
            'generatedAt'   => date(DATE_ATOM),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function latestStories(): array
    {
        $rows = $this->contentEntries
            ->where('type', 'blog')
            ->where('status', 'published')
            ->orderBy('is_featured', 'DESC')
            ->orderBy('published_at', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->findAll(3);

        return array_map(fn ($entry) => $this->withExcerpt($entry), $rows);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function latestTestimonials(): array
    {
        $rows = $this->contentEntries
            ->where('type', 'testimonial')
            ->where('status', 'published')
            ->orderBy('display_order', 'ASC')
            ->orderBy('published_at', 'DESC')
            ->findAll(6);

        return array_map(fn ($entry) => $this->withExcerpt($entry, 220), $rows);
    }

    private function withExcerpt(array $entry, int $limit = 180): array
    {
        $source = $entry['summary'] ?? '';
        if ($source === '' && ! empty($entry['quote'])) {
            $source = $entry['quote'];
        }

        if ($source === '' && ! empty($entry['body'])) {
            $source = strip_tags((string) $entry['body']);
        }

        $source = trim((string) $source);

        if ($source !== '' && mb_strlen($source) > $limit) {
            $source = rtrim(mb_substr($source, 0, $limit - 3)) . '...';
        }

        $entry['excerpt'] = $source;

        return $entry;
    }
}
