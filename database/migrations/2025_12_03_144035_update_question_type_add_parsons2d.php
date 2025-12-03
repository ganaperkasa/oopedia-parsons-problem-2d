<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        
        DB::statement("ALTER TABLE questions MODIFY COLUMN question_type
            ENUM('radio_button', 'drag_and_drop', 'fill_in_the_blank', 'parsons_problem_2d')
            NOT NULL");
    }

    public function down()
    {

        DB::statement("ALTER TABLE questions MODIFY COLUMN question_type
            ENUM('radio_button', 'drag_and_drop', 'fill_in_the_blank')
            NOT NULL");
    }
};
