<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Feed;

class CreateTableFeedsContent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $feed = new Feed();

        Schema::create($feed->getTable(), function (Blueprint $table) {
            $table->id();
            $table->string("keyword")->nullable()->index();
            $table->string("channel")->nullable();
            $table->json("content")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $feed = new Feed();
        Schema::dropIfExists($feed->getTable());
    }
}
