<?php

namespace App\Http\Controllers;

use App\Models\DeepSky;
use App\Http\Controllers\Controller as BaseController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DeepSkyController extends BaseController
{
    // API

    // GET /api/dso/search
    public function search(Request $request)
    {
        $q = strtolower(trim($request->query('q')));
        $id = $request->query('id');
        $constellation = $request->query('constellation');
        $type = $request->query('type');
        $raMax = $request->query('ra_max');
        $raMin = $request->query('ra_min');
        $decMax = $request->query('dec_max');
        $decMin = $request->query('dec_min');
        $distMin = $request->query('dist_min');
        $distMax = $request->query('dist_max');
        $magMax = $request->query('mag_max');
        $magMin = $request->query('mag_min');
        $sortType = $request->query('sort_type', 'id');
        $sortOrder = $request->query('sort_order', 'asc');

        $query = DeepSky::query();

        // Id.

        if (is_numeric($id)) {
            $query->where('id', '=',  (int) $id);
        }

        // Catalogue.

        if ($request->has('h400')) {
            $query->where('h400', '=', true);
        }

        if ($request->has('dunlop')) {
            $query->where('dunlop', '=', true);
        }

        if ($request->has('bennett')) {
            $query->where('bennett', '=', true);
        }

        foreach (DeepSkyController::CATALOGUE_LIST as $name => $value) {
            if ($request->has($name)) {
                $value = $request->get($name);

                if (!empty($value)) {
                    $isNum = is_numeric($value);
                    $isString = $value[0];

                    if ($isNum && !$isString) {
                        $query->where($name, '=', (int) $value);
                    } else if ($isString) {
                        $query->where($name, '=', $value);
                    }
                } else {
                    $query->whereNotNull($name);
                }
            }
        }

        // Constellation.

        if ($constellation) {
            $query->where('constellation', '=', strtoupper($constellation));
        }

        // Type.

        if (is_numeric($type)) {
            $query->where('type', '=', (int) $type);
        } else {
            $index = array_search($type, DeepSkyController::TYPES);

            if ($index !== false) {
                $query->where('type', '=', (int) $index);
            }
        }

        // RA (rad).

        if (is_numeric($raMax)) {
            $query->where('ra', '<=', (float) $raMax);
        }

        if (is_numeric($raMin)) {
            $query->where('ra', '>=', (float) $raMin);
        }

        // Declination (rad).

        if (is_numeric($decMax)) {
            $query->where('dec', '<=', (float) $decMax);
        }

        if (is_numeric($decMin)) {
            $query->where('dec', '>=', (float) $decMin);
        }

        // Distance (ly).

        if (is_numeric($distMax)) {
            $query->where('distance', '<=', (float) $distMax);
        }

        if (is_numeric($distMin)) {
            $query->where('distance', '>=', (float) $distMin);
        }

        // V and B Magnitude.

        if (is_numeric($magMax)) {
            $query->where(function ($query) use ($magMax) {
                $query->where('bMag', '<=', (float) $magMax)
                    ->orWhere('vMag', '<=', (float) $magMax);
            });
        }

        if (is_numeric($magMin)) {
            $query->where(function ($query) use ($magMin) {
                $query->where('bMag', '>=', (float) $magMin)
                    ->orWhere('vMag', '>=', (float) $magMin);
            });
        }

        // Query.

        if (!empty($q)) {
            $query->where(function (Builder $query) use ($q) {
                foreach (DeepSkyController::CATALOGUE_LIST as $name => $value) {
                    if (substr($q, 0, strlen($name)) === $name) {
                        $a = trim(substr($q, strlen($name)));

                        $isNum = is_numeric($a);
                        $isString = $value[0];

                        if ($isNum && !$isString) {
                            $query->orWhere($name, '=', (int) $a);
                        } else if ($isString) {
                            $query->orWhere($name, '=', $a);
                        }
                    }

                    $b = $q;

                    $isNum = is_numeric($b);

                    if ($isNum && !$isString) {
                        $query->orWhere($name, '=', (int) $b);
                    } else if ($isString) {
                        $query->orWhere($name, 'ilike', "%$b%");
                    }
                }

                $query->orWhere('names', '~*', "\\[.*$q.*\\]");
            });
        }

        // Order.

        $query->orderBy($sortType, $sortOrder);

        $parameters = $request->except('page');

        foreach ($parameters as $key => $value) {
            $parameters[$key] = $parameters[$key] ?: '';
        }

        $page = $query->paginate(25);
        $data = $page->items();

        $report = $this->getReportData();

        foreach ($data as $item) {
            DeepSkyController::handleItem($item, $report);
        }

        return $page->appends($parameters);
    }

    private function getReportData()
    {
        $filename = DeepSkyController::REPORT_FILEPATH;
        $data = file_exists($filename) ? json_decode(file_get_contents($filename), true) : NULL;
        return $data;
    }

    static private function handleItem(&$item, $report)
    {
        $names = [];

        foreach (DeepSkyController::CATALOGUE_LIST as $name => $value) {
            if (!empty($item[$name])) {
                $title = $value[1] . $item[$name];
                array_unshift($names, $title);
            }
        }

        if (!empty($item['names'])) {
            preg_match_all("/\\[(.*?)\\]/i", $item['names'], $matches, PREG_PATTERN_ORDER);
            $names = array_merge($matches[1], $names);
            $item['names'] = $matches[1];
        }

        if (!empty($names)) {
            $item['title'] = join(' | ', $names);
        }

        if ($report != NULL && array_key_exists($item['id'], $report)) {
            $item['reported'] = $report[$item['id']];
        }

        return $item;
    }

    // GET /api/dso/:id
    public function get(int $id)
    {
        $query = DeepSky::query();
        $item = $query->where('id', '=', $id)->first();

        if (empty($item)) {
            return response(NULL, 404);
        } else {
            $report = $this->getReportData();
            return DeepSkyController::handleItem($item, $report);
        }
    }

    // GET /api/dso/:id/photo
    public function photo(int $id)
    {
        $name = str_pad($id, 5, '0', STR_PAD_LEFT) . ".webp";
        $filename = __DIR__ . "/../../../../data/photos/$name";
        return file_exists($filename) ? response()->file($filename) : response(NULL, 404);
    }

    // GET /api/dso/:id/original
    public function original(int $id)
    {
        $query = DeepSky::query();
        $dso = $query->where('id', '=', $id)->first();

        if ($dso) {
            $ra = $dso->ra * 180 / M_PI;
            $dec = $dso->dec * 180 / M_PI;
            $report = $this->getReportData();

            if (
                $report != NULL &&
                array_key_exists($dso['id'], $report)
                && $report[$dso['id']]
            ) {
                $versions = ['poss1_blue', 'phase2_gsc1'];
            } else {
                $versions = ['poss2ukstu_blue', 'phase2_gsc2'];
            }

            foreach ($versions as $v) {
                $a = "https://archive.stsci.edu/cgi-bin/dss_search?v=$v&r=$ra&d=$dec&e=J2000&h=60&w=60&f=gif&c=none&fov=NONE&v3";
                $res = Http::head($a);

                if (str_contains($res->header('Content-Type'), 'image/gif')) {
                    return response()->redirectTo($a, 308);
                }
            }
        }

        return response(NULL, 404);
    }

    // POST /api/dso/:id/report
    function report(int $id)
    {
        $filename = DeepSkyController::REPORT_FILEPATH;
        $json = file_exists($filename) ? json_decode(file_get_contents($filename), true) : NULL;

        if (!empty($json)) {
            $json[$id] = true;
        } else {
            $json = [$id => true];
        }

        file_put_contents($filename, json_encode($json));
    }

    // WEB

    // GET /catalog
    public function catalog()
    {
        $query = DeepSky::query();
        $page = $query->paginate(1000);
        $data = $page->items();
        $report = $this->getReportData();

        foreach ($data as $item) {
            DeepSkyController::handleItem($item, $report);
        }

        return view('catalog')->with('data', $page);
    }

    const TYPES = [
        'galaxy',
        'activeGalaxy',
        'radioGalaxy',
        'interactingGalaxy',
        'quasar',
        'starCluster',
        'openStarCluster',
        'globularStarCluster',
        'stellarAssociation',
        'starCloud',
        'nebula',
        'planetaryNebula',
        'darkNebula',
        'reflectionNebula',
        'bipolarNebula',
        'emissionNebula',
        'clusterAssociatedWithNebulosity',
        'hiiRegion',
        'supernovaRemnant',
        'interstellarMatter',
        'emissionObject',
        'blLacertaeObject',
        'blazar',
        'molecularCloud',
        'youngStellarObject',
        'possibleQuasar',
        'possiblePlanetaryNebula',
        'protoplanetaryNebula',
        'star',
        'symbioticStar',
        'emissionLineStar',
        'supernovaCandidate',
        'superNovaRemnantCandidate',
        'clusterOfGalaxies',
        'partOfGalaxy',
        'regionOfTheSky',
        'unknown',
    ];

    const REPORT_FILEPATH = __DIR__ . "/../../../../data/report.json";

    const CATALOGUE_LIST = [
        'vdbha' => [false, 'vdB-Ha '],
        'snrg' => [true, 'SNR G'],
        'vdbh' => [true, 'vdBH '],
        'sh2' => [false, 'SH 2-'],
        'vdb' => [false, 'vdB '],
        'rcw' => [false, 'RCW '],
        'ldn' => [false, 'LDN '],
        'lbn' => [false, 'LBN '],
        'dwb' => [false, 'DWB '],
        'ugc' => [false, 'UGC '],
        'arp' => [false, 'Arp '],
        'ced' => [true, 'Ced '],
        'png' => [true, 'PN G'],
        'aco' => [true, 'ACO '],
        'hcg' => [true, 'HCG '],
        'eso' => [true, 'ESO '],
        'pgc' => [false, 'PGC '],
        'mel' => [false, 'Mel '],
        'ngc' => [false, 'NGC '],
        'vv' => [false, 'VV '],
        'pk' => [true, 'PK '],
        'tr' => [false, 'Tr '],
        'st' => [false, 'St '],
        'ru' => [false, 'Ru '],
        'cr' => [false, 'Cr '],
        'ic' => [false, 'IC '],
        'b' => [false, 'B '],
        'c' => [false, 'C '],
        'm' => [false, 'M '],
    ];
}
