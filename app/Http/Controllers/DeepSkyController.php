<?php

namespace App\Http\Controllers;

use App\Models\DeepSky;
use App\Http\Controllers\Controller as BaseController;
use App\Jobs\MakePhoto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DeepSkyController extends BaseController
{
    // API

    // GET /api/dso/search
    // Obtém uma lista de DSOs.
    public function search(Request $request)
    {
        $q = strtolower(trim($request->query('q')));
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
        $sortType = preg_split('/,/', $request->query('sort_type', 'id'), PREG_SPLIT_NO_EMPTY);
        $sortOrder = preg_split('/,/', $request->query('sort_order', 'asc'), PREG_SPLIT_NO_EMPTY);

        $query = DeepSky::query();

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
                    array_unshift($sortType, $name);
                    array_unshift($sortOrder, 'asc');
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
                    $isString = $value[0];

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
        $length = count($sortType);

        for ($i = 0; $i < $length; $i++) {
            $so = $i >= count($sortOrder) ? 'asc' : $sortOrder[$i];
            $query->orderBy($sortType[$i], $so);
        }

        // Paginate.

        $perPage = $request->get('per_page') ?: 25;
        $page = $query->paginate($perPage);
        $data = array_map(function ($item) {
            $a = $item->toArray();
            DeepSkyController::handleItem($a);
            return $a;
        }, $page->items());

        $res = [
            'current_page' => $page->currentPage(),
            'total' => $page->total(),
            'count' => count($data),
            'from' => ($page->currentPage() - 1) * $page->perPage(),
            'to' => ($page->currentPage() - 1) * $page->perPage() + count($data) - 1,
            'per_page' => $page->perPage(),
            'last_page' => $page->lastPage(),
            'prev_page' => $page->previousPageUrl() !== NULL,
            'next_page' => $page->nextPageUrl() !== NULL,
            'data' => $data,
        ];

        return $res;
    }

    // TODO: around (ra/dec ou id, arcmin ou arcsec)

    static private function handleItem(&$item)
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
            $item['names'] = $names;
        }

        foreach ($item as $key => $value) {
            if (empty($item[$key])) {
                unset($item[$key]);
            }
        }
    }

    // GET /api/dso/:id
    // Obtém o DSO.
    public function get(int $id)
    {
        $query = DeepSky::query();
        $dso = $query->where('id', '=', $id)->first();

        if (empty($dso)) {
            return response(NULL, 404);
        } else {
            $a = $dso->toArray();
            DeepSkyController::handleItem($a);
            return $a;
        }
    }



    // GET /api/dso/:id/photo
    // Obtém a foto.
    public function photo(int $id, Request $request)
    {
        $query = DeepSky::query();
        $dso = $query->where('id', '=', $id)->first();
        $format = $request->query('format', 'webp');
        $quality = intval($request->query('quality', '100'));

        if ($dso) {
            $job = new MakePhoto($dso, $format, $quality);
            $this->dispatchNow($job);
            $photo = $job->getPhoto();

            if ($photo) {
                return $photo;
            }
        }

        return response(NULL, 404);
    }

    // POST /api/dso/:id/report
    // Reporta a foto.
    public function report(int $id)
    {
        $query = DeepSky::query();
        $dso = $query->where('id', '=', $id)->first();

        if ($dso) {
            $index = array_search($dso->version, DeepSkyController::VERSIONS);

            if ($index === false) {
                $index = -1;
            } else if ($index === 0) {
                // Pular pra 3ª versão caso a atual seja a 1ª
                $index = 1;
            }

            // Remove o cache.
            $cachePath = storage_path("photos/{$dso->id}.webp");

            if (file_exists($cachePath)) {
                unlink($cachePath);
            }

            // Troca de versão.
            $index = ($index + 1) % count(DeepSkyController::VERSIONS);
            $dso->version = DeepSkyController::VERSIONS[$index];
            $dso->save();

            return response($dso->version);
        } else {
            return response(NULL, 404);
        }
    }

    // WEB

    // GET /catalog
    public function catalog(Request $request)
    {
        $page = $this->search($request);

        return view('catalog')
            ->with('data', $page)
            ->with('api_token', env('API_TOKEN'));
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

    const VERSIONS = ['poss2ukstu_blue', 'phase2_gsc2', 'poss1_blue', 'phase2_gsc1'];

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
