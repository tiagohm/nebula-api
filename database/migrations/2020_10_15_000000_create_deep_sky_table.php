<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeepSkyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deepsky', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('m')->nullable();
            $table->integer('ngc')->nullable();
            $table->integer('ic')->nullable();
            $table->integer('c')->nullable();
            $table->integer('b')->nullable();
            $table->integer('sh2')->nullable();
            $table->integer('vdb')->nullable();
            $table->integer('rcw')->nullable();
            $table->integer('ldn')->nullable();
            $table->integer('lbn')->nullable();
            $table->integer('cr')->nullable();
            $table->integer('mel')->nullable();
            $table->integer('pgc')->nullable();
            $table->integer('ugc')->nullable();
            $table->integer('arp')->nullable();
            $table->integer('vv')->nullable();
            $table->integer('dwb')->nullable();
            $table->integer('tr')->nullable();
            $table->integer('st')->nullable();
            $table->integer('ru')->nullable();
            $table->integer('vdbha')->nullable();
            $table->text('ced')->nullable();
            $table->text('pk')->nullable();
            $table->text('png')->nullable();
            $table->text('snrg')->nullable();
            $table->text('aco')->nullable();
            $table->text('hcg')->nullable();
            $table->text('eso')->nullable();
            $table->text('vdbh')->nullable();
            $table->text('mType')->nullable();
            $table->double('bMag')->nullable();
            $table->double('vMag')->nullable();
            $table->double('majorAxisSize')->nullable();
            $table->double('minorAxisSize')->nullable();
            $table->integer('orientationAngle')->nullable();
            $table->double('distance')->nullable();
            $table->double('distanceErr')->nullable();
            $table->double('redshift')->nullable();
            $table->double('redshiftErr')->nullable();
            $table->double('parallax')->nullable();
            $table->double('parallaxErr')->nullable();
            $table->double('ra');
            $table->double('dec');
            $table->integer('type');
            $table->text('names')->nullable();
            $table->double('surfaceBrightness')->nullable();
            $table->text('constellation');
            $table->boolean('h400')->default(false);
            $table->boolean('bennett')->default(false);
            $table->boolean('dunlop')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deepsky');
    }
}
