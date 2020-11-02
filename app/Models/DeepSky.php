<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeepSky extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'deepsky';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'h400' => false,
        'bennett' => false,
        'dunlop' => false,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'm',
        'ngc',
        'ic',
        'c',
        'b',
        'sh2',
        'vdb',
        'rcw',
        'ldn',
        'lbn',
        'cr',
        'mel',
        'pgc',
        'ugc',
        'arp',
        'vv',
        'dwb',
        'tr',
        'st',
        'ru',
        'vdbha',
        'ced',
        'pk',
        'png',
        'snrg',
        'aco',
        'hcg',
        'eso',
        'vdbh',
        'mType',
        'bMag',
        'vMag',
        'majorAxisSize',
        'minorAxisSize',
        'orientationAngle',
        'distance',
        'distanceErr',
        'redshift',
        'redshiftErr',
        'parallax',
        'parallaxErr',
        'ra',
        'dec',
        'type',
        'names',
        'surfaceBrightness',
        'constellation',
        'h400',
        'bennett',
        'dunlop',
        'version',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'm' => 'integer',
        'ngc' => 'integer',
        'ic' => 'integer',
        'c' => 'integer',
        'b' => 'integer',
        'sh2' => 'integer',
        'vdb' => 'integer',
        'rcw' => 'integer',
        'ldn' => 'integer',
        'lbn' => 'integer',
        'cr' => 'integer',
        'mel' => 'integer',
        'pgc' => 'integer',
        'ugc' => 'integer',
        'arp' => 'integer',
        'vv' => 'integer',
        'dwb' => 'integer',
        'tr' => 'integer',
        'st' => 'integer',
        'ru' => 'integer',
        'vdbha' => 'integer',
        'bMag' => 'double',
        'vMag' => 'double',
        'majorAxisSize' => 'double',
        'minorAxisSize' => 'double',
        'orientationAngle' => 'integer',
        'distance' => 'double',
        'distanceErr' => 'double',
        'redshift' => 'double',
        'redshiftErr' => 'double',
        'parallax' => 'double',
        'parallaxErr' => 'double',
        'ra' => 'double',
        'dec' => 'double',
        'type' => 'integer',
        'surfaceBrightness' => 'double',
        'h400' => 'boolean',
        'bennett' => 'boolean',
        'dunlop' => 'boolean',
    ];
}
