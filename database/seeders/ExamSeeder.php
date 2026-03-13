<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exam;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ExamSeeder extends Seeder
{
    public function run()
    {
        // Create or get a default school
        $school = School::firstOrCreate(
            ['name' => 'École Nationale Polytechnique'],
            [
                'sigle' => 'ENP',
                'email' => 'contact@enp.cm',
                'telephone' => '+237222123456',
                'address' => 'Yaoundé, Cameroun',
                'city' => 'Yaoundé',
                'status' => 'validee',
            ]
        );

        // Create test exams
        Exam::create([
            'title' => 'Concours d\'Entrée 2026',
            'slug' => Str::slug('Concours d\'Entrée 2026'),
            'description' => 'Concours d\'entrée pour l\'année académique 2026. Test avec des données d\'exemple pour validation du système.',
            'school_id' => $school->id,
            'year' => 2026,
            'session' => 1,
            'registration_start_date' => Carbon::now(),
            'registration_end_date' => Carbon::now()->addDays(7),
            'exam_date' => Carbon::now()->addDays(14),
            'registration_fee' => 25000,
            'status' => 'published',
            'is_public' => true,
        ]);

        Exam::create([
            'title' => 'Concours de Rattrapage',
            'slug' => Str::slug('Concours de Rattrapage'),
            'description' => 'Deuxième session de concours pour candidats ayant échoué à la première tentative.',
            'school_id' => $school->id,
            'year' => 2026,
            'session' => 2,
            'registration_start_date' => Carbon::now()->addMonths(1),
            'registration_end_date' => Carbon::now()->addMonths(1)->addDays(7),
            'exam_date' => Carbon::now()->addMonths(1)->addDays(14),
            'registration_fee' => 25000,
            'status' => 'published',
            'is_public' => true,
        ]);

        Exam::create([
            'title' => 'Concours Brouillon',
            'slug' => Str::slug('Concours Brouillon'),
            'description' => 'Concours en phase de préparation - non encore publié.',
            'school_id' => $school->id,
            'year' => 2026,
            'session' => 1,
            'registration_start_date' => Carbon::now()->addMonths(3),
            'registration_end_date' => Carbon::now()->addMonths(3)->addDays(7),
            'exam_date' => Carbon::now()->addMonths(3)->addDays(14),
            'registration_fee' => 25000,
            'status' => 'draft',
            'is_public' => false,
        ]);
    }
}
