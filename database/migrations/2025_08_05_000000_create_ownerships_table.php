<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ownerships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('owner_type');
            $table->unsignedBigInteger('ownable_id');
            $table->string('ownable_type');
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['owner_id', 'owner_type']);
            $table->index(['ownable_id', 'ownable_type']);
            $table->index(['is_current']);
            $table->index(['ownable_id', 'ownable_type', 'is_current']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ownerships');
    }
};
