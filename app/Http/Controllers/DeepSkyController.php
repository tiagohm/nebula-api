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
        $sortType = $request->query('sort_type', 'id');
        $sortOrder = $request->query('sort_order', 'asc');
        $reported = $request->has('reported');

        $query = DeepSky::query();

        // Reported.
        if ($reported) {
            $query->where('reported', '=', true);
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

        $perPage = $request->get('per_page') ?: 25;
        $page = $query->paginate($perPage);
        $data = $page->items();

        foreach ($data as $item) {
            DeepSkyController::handleItem($item);
        }

        return $page->appends($parameters);
    }

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
            $item['title'] = join(' | ', $names);
        }

        return $item;
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
            return DeepSkyController::handleItem($dso);
        }
    }

    // GET /api/dso/:id/photo
    // Obtém a foto.
    public function photo(int $id)
    {
        $query = DeepSky::query();
        $dso = $query->where('id', '=', $id)->first();

        if ($dso) {
            $name = str_pad($id, 5, '0', STR_PAD_LEFT) . ".webp";
            $filename = storage_path("photos/$name");;

            if (file_exists($filename)) {
                return response()->file($filename);
            }
        }

        return response(NULL, 404);
    }

    // GET /api/dso/:id/original
    // Redireciona para a versão original da foto.
    public function original(int $id)
    {
        $query = DeepSky::query();
        $dso = $query->where('id', '=', $id)->first();

        if ($dso) {
            if ($dso->reported) {
                $versions = DeepSkyController::ALT_VERSIONS;
            } else if (empty($dso->version)) {
                $versions = DeepSkyController::VERSIONS;
            } else {
                $versions = [$dso->version];
            }

            foreach ($versions as $v) {
                $ra = $dso->ra * 180 / M_PI;
                $dec = $dso->dec * 180 / M_PI;
                $a = "https://archive.stsci.edu/cgi-bin/dss_search?v=$v&r=$ra&d=$dec&e=J2000&h=1&w=1&f=gif&c=none&fov=NONE&v3";
                $res = Http::head($a);

                if (str_contains($res->header('Content-Type'), 'image/gif')) {
                    $dso->version = $v;
                    $dso->save();
                    $a = "https://archive.stsci.edu/cgi-bin/dss_search?v=$v&r=$ra&d=$dec&e=J2000&h=60&w=60&f=gif&c=none&fov=NONE&v3";
                    return response()->redirectTo($a, 308);
                }
            }
        }

        return response(NULL, 404);
    }

    // POST /api/dso/:id/report
    // Reporta a foto do DSO para que utilize uma outra versão.
    function report(int $id)
    {
        $query = DeepSky::query();
        $dso = $query->where('id', '=', $id)->first();

        if ($dso) {
            if ($dso->reported) {
                return;
            }

            $ra = $dso->ra * 180 / M_PI;
            $dec = $dso->dec * 180 / M_PI;

            // TODO: Talvez usar uma outra versão alternativa sempre q reportar.

            foreach (DeepSkyController::ALT_VERSIONS as $v) {
                $a = "https://archive.stsci.edu/cgi-bin/dss_search?v=$v&r=$ra&d=$dec&e=J2000&h=1&w=1&f=gif&c=none&fov=NONE&v3";
                $res = Http::head($a);

                if (str_contains($res->header('Content-Type'), 'image/gif')) {
                    $dso->version = $v;
                    $dso->reported = true;
                    $dso->save();
                    return;
                }
            }
        }

        return response(NULL, 404);
    }

    // DELETE /api/dso/:id/report
    // Desreporta a foto do DSO.
    function unreport(int $id)
    {
        $query = DeepSky::query();
        $dso = $query->where('id', '=', $id)->first();

        if ($dso) {
            $ra = $dso->ra * 180 / M_PI;
            $dec = $dso->dec * 180 / M_PI;

            foreach (DeepSkyController::VERSIONS as $v) {
                $a = "https://archive.stsci.edu/cgi-bin/dss_search?v=$v&r=$ra&d=$dec&e=J2000&h=1&w=1&f=gif&c=none&fov=NONE&v3";
                $res = Http::head($a);

                if (str_contains($res->header('Content-Type'), 'image/gif')) {
                    $dso->version = $v;
                    $dso->reported = false;
                    $dso->save();
                    return;
                }
            }
        } else {
            return response(NULL, 404);
        }
    }

    // WEB

    // GET /catalog
    public function catalog()
    {
        $query = DeepSky::query();
        $query->orderBy('id', 'asc');
        $page = $query->paginate(1000);
        $data = $page->items();

        foreach ($data as $item) {
            DeepSkyController::handleItem($item);
        }

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

    const ALT_VERSIONS = ['poss1_blue', 'phase2_gsc1'];
    const VERSIONS = ['poss2ukstu_blue', 'phase2_gsc2'];

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
