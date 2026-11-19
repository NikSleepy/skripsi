<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Urutan seeder mengikuti dependensi foreign key.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TeacherSeeder::class,
            ParentSeeder::class,
            StudentSeeder::class,
            StudentParentSeeder::class,
            ClassSeeder::class,
            SchoolYearSeeder::class,
            SemesterSeeder::class,
            SubjectSeeder::class,
            ClassEnrollmentSeeder::class,
            ClassSubjectSeeder::class,
            GradeSeeder::class,
            GradeChangeLogSeeder::class,
            AttendanceSeeder::class,
            ScholarshipCriteriaSeeder::class,
            ScholarshipWeightSetSeeder::class,
            ScholarshipWeightSeeder::class,
            ScholarshipRankingRunSeeder::class,
            ScholarshipRankingResultSeeder::class,
            ClassRankingRunSeeder::class,
            ClassRankingResultSeeder::class,
        ]);
    }
}
