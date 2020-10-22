<?php

namespace App\Http\Controllers\Api;

use App\Models\DeepSky;
use App\Http\Controllers\Controller as BaseController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DeepSkyController extends BaseController
{
    // GET /dso/search
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

        foreach (DeepSkyController::CATALOGUE_LIST as $name => $isString) {
            if ($request->has($name)) {
                $value = $request->get($name);

                if (!empty($value)) {
                    $isNum = is_numeric($value);

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
                foreach (DeepSkyController::CATALOGUE_LIST as $name => $isString) {
                    if (substr($q, 0, strlen($name)) === $name) {
                        $a = trim(substr($q, strlen($name)));

                        $isNum = is_numeric($a);

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

        return $query->paginate(25)->appends($parameters);
    }

    // GET /dso/:id
    public function get(int $id)
    {
        $query = DeepSky::query();
        return $query->where('id', '=', $id)->get();
    }

    // GET /dso/:id/photo
    public function photo(int $id)
    {
        $name = str_pad($id, 5, '0', STR_PAD_LEFT) . ".webp";
        $filename = __DIR__ . "/../../../../../data/photos/$name";
        return file_exists($filename) ? response()->file($filename) : response(NULL, 404);
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

    const CATALOGUE_LIST = [
        'vdbha' => false,
        'snrg' => true,
        'vdbh' => true,
        'ngc' => false,
        'sh2' => false,
        'vdb' => false,
        'rcw' => false,
        'ldn' => false,
        'lbn' => false,
        'dwb' => false,
        'mel' => false,
        'pgc' => false,
        'ugc' => false,
        'arp' => false,
        'ced' => true,
        'png' => true,
        'aco' => true,
        'hcg' => true,
        'eso' => true,
        'cr' => false,
        'ic' => false,
        'vv' => false,
        'tr' => false,
        'st' => false,
        'ru' => false,
        'pk' => true,
        'm' => false,
        'c' => false,
        'b' => false,
    ];
}
