<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransportHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transport_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->unsignedInteger('created_by');
            $table->unsignedInteger('last_updated_by')->nullable();

            $table->unsignedInteger('years_id');
            $table->unsignedInteger('routes_id')->nullable();
            $table->unsignedInteger('vehicles_id')->nullable();
            $table->unsignedInteger('travellers_id');
            $table->foreign('travellers_id')->references('id')->on('transport_users');
            $table->text('history_type');

            $table->boolean('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transport_histories');
    }
}
