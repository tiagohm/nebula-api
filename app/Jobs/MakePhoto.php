<?php

namespace App\Jobs;

use App\Http\Controllers\DeepSkyController;
use App\Models\DeepSky;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MakePhoto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected DeepSky $dso;
    protected string $format;
    protected int $quality;
    protected StreamedResponse $photo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(DeepSky $dso, string $format, int $quality)
    {
        $this->dso = $dso;
        $this->format = $format;
        $this->quality = $quality;
    }

    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $index = array_search($this->dso->version, DeepSkyController::VERSIONS);

        if ($index === false) {
            $index = 0;
        }

        $length = count(DeepSkyController::VERSIONS);

        for ($i = $index; $i < $length; $i++) {
            $v = DeepSkyController::VERSIONS[$i];
            $photo = MakePhoto::makePhoto($this->dso, $this->format, $this->quality, $v);

            if ($photo) {
                if ($i !== $index) {
                    $this->dso->version = $v;
                    $this->dso->save();
                }

                $this->photo = $photo;
                return;
            }
        }

        $this->photo = NULL;
    }

    // Cria uma foto em um determinado formato e qualidade para um DSO.
    // $format: gif, jpeg, png, webp
    private static function makePhoto(
        $dso,
        string $format = 'webp',
        int $quality = 100,
        string $v = null
    ) {
        $v = $v ?: $dso->version;
        $ra = $dso->ra * 180 / M_PI;
        $dec = $dso->dec * 180 / M_PI;
        $arcmin = $dso->majorAxisSize == NULL ? 10 : $dso->majorAxisSize * 60;
        $width = $height = max(10, min($arcmin, 60));

        $cachePath = storage_path("photos/{$dso->id}.webp");
        $save = false;

        if (file_exists($cachePath)) {
            $data = file_get_contents($cachePath);
        } else {
            $a = MakePhoto::getPhotoUrl($v, $ra, $dec, $width, $height);
            $res = Http::get($a);

            if (str_contains($res->header('Content-Type'), 'image/gif')) {
                $data = $res->getBody();
                $save = true;
            } else {
                return false;
            }
        }

        $im = imagecreatefromstring($data);

        if ($save) {
            imagepalettetotruecolor($im);
            imagewebp($im, $cachePath, 90);
        }

        $headers['X-Survey'] = $v;
        $headers['X-RA'] = $ra;
        $headers['X-DEC'] = $dec;
        $headers['Content-Type'] = "image/$format";
        $headers['Content-Disposition'] = "Content-Disposition: inline; filename=\"{$dso->id}.$format\"";
        $headers['X-Width'] = imagesx($im);
        $headers['X-Height'] = imagesy($im);

        return MakePhoto::buildPhotoResponse($im, $format, $quality, $headers);
    }

    private static function buildPhotoResponse(&$im, string $format, int $quality, array &$headers)
    {
        return response()->stream(function () use (&$im, $format, $quality) {
            switch ($format) {
                case 'gif':
                    imagegif($im, NULL);
                    break;
                case 'jpeg':
                    imagejpeg($im, NULL, $quality);
                    break;
                case 'png':
                    imagepng($im, NULL, 9);
                    break;
                case 'webp':
                    imagepalettetotruecolor($im);
                    imagewebp($im, NULL, $quality);
                    break;
            }

            imagedestroy($im);
        }, 200, $headers);
    }

    private static function getPhotoUrl(string $v, float $ra, float $dec, int $w = 60, int $h = 60)
    {
        return "https://archive.stsci.edu/cgi-bin/dss_search?v=$v&r=$ra&d=$dec&e=J2000&h=$h&w=$w&f=gif&c=none&fov=NONE&v3";
    }
}
