<?php

use App\Enums\Role;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ChildAttendanceController;
use App\Http\Controllers\ChildIncomeController;
use App\Http\Controllers\ClassRankingController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradeAuditController;
use App\Http\Controllers\GradeAverageController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\GradeReportController;
use App\Http\Controllers\GuardianIncomeController;
use App\Http\Controllers\HomeroomStudentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\ScholarshipWeightController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

// Master data hanya untuk admin.
Route::middleware(['auth', 'verified', 'role:'.Role::Admin->value])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('students', [StudentController::class, 'index'])->name('students.index');
        Route::get('students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('students', [StudentController::class, 'store'])->name('students.store');
        Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::patch('students/{student}/status', [StudentController::class, 'updateStatus'])->name('students.status');

        Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::get('teachers/create', [TeacherController::class, 'create'])->name('teachers.create');
        Route::post('teachers', [TeacherController::class, 'store'])->name('teachers.store');
        Route::get('teachers/{teacher}', [TeacherController::class, 'show'])->name('teachers.show');
        Route::get('teachers/{teacher}/edit', [TeacherController::class, 'edit'])->name('teachers.edit');
        Route::put('teachers/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::patch('teachers/{teacher}/status', [TeacherController::class, 'updateStatus'])->name('teachers.status');

        Route::get('classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
        Route::get('classrooms/create', [ClassroomController::class, 'create'])->name('classrooms.create');
        Route::post('classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
        Route::get('classrooms/{classroom}', [ClassroomController::class, 'show'])->name('classrooms.show');
        Route::get('classrooms/{classroom}/edit', [ClassroomController::class, 'edit'])->name('classrooms.edit');
        Route::put('classrooms/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');

        Route::get('subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::get('subjects/create', [SubjectController::class, 'create'])->name('subjects.create');
        Route::post('subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::get('subjects/{subject}', [SubjectController::class, 'show'])->name('subjects.show');
        Route::get('subjects/{subject}/edit', [SubjectController::class, 'edit'])->name('subjects.edit');
        Route::put('subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::patch('subjects/{subject}/status', [SubjectController::class, 'updateStatus'])->name('subjects.status');

        Route::get('school-years', [SchoolYearController::class, 'index'])->name('school-years.index');
        Route::get('school-years/create', [SchoolYearController::class, 'create'])->name('school-years.create');
        Route::post('school-years', [SchoolYearController::class, 'store'])->name('school-years.store');
        Route::get('school-years/{schoolYear}', [SchoolYearController::class, 'show'])->name('school-years.show');
        Route::get('school-years/{schoolYear}/edit', [SchoolYearController::class, 'edit'])->name('school-years.edit');
        Route::put('school-years/{schoolYear}', [SchoolYearController::class, 'update'])->name('school-years.update');
        Route::patch('school-years/{schoolYear}/activate', [SchoolYearController::class, 'activate'])->name('school-years.activate');

        Route::get('school-years/{schoolYear}/semesters/create', [SemesterController::class, 'create'])->name('school-years.semesters.create');
        Route::post('school-years/{schoolYear}/semesters', [SemesterController::class, 'store'])->name('school-years.semesters.store');
        Route::get('school-years/{schoolYear}/semesters/{semester}/edit', [SemesterController::class, 'edit'])->name('school-years.semesters.edit');
        Route::put('school-years/{schoolYear}/semesters/{semester}', [SemesterController::class, 'update'])->name('school-years.semesters.update');
        Route::delete('school-years/{schoolYear}/semesters/{semester}', [SemesterController::class, 'destroy'])->name('school-years.semesters.destroy');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        Route::get('scholarship-weights', [ScholarshipWeightController::class, 'index'])->name('scholarship-weights.index');
        Route::get('scholarship-weights/create', [ScholarshipWeightController::class, 'create'])->name('scholarship-weights.create');
        Route::post('scholarship-weights', [ScholarshipWeightController::class, 'store'])->name('scholarship-weights.store');
        Route::get('scholarship-weights/{weightSet}', [ScholarshipWeightController::class, 'show'])->name('scholarship-weights.show');
        Route::get('scholarship-weights/{weightSet}/edit', [ScholarshipWeightController::class, 'edit'])->name('scholarship-weights.edit');
        Route::put('scholarship-weights/{weightSet}', [ScholarshipWeightController::class, 'update'])->name('scholarship-weights.update');
        Route::patch('scholarship-weights/{weightSet}/lock', [ScholarshipWeightController::class, 'updateLock'])->name('scholarship-weights.lock');
        Route::delete('scholarship-weights/{weightSet}', [ScholarshipWeightController::class, 'destroy'])->name('scholarship-weights.destroy');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Absensi anak untuk orang tua: hanya anak yang terhubung dengan akunnya.
Route::middleware(['auth', 'verified', 'role:'.Role::OrangTua->value])
    ->prefix('children')
    ->name('children.')
    ->group(function () {
        Route::get('attendance', [ChildAttendanceController::class, 'index'])->name('attendance');
        Route::get('income', [ChildIncomeController::class, 'edit'])->name('income');
        Route::patch('income', [ChildIncomeController::class, 'update'])->name('income.update');
    });

// Pendapatan orang tua/wali: admin untuk semua siswa, wali kelas hanya siswa
// di kelas perwaliannya (dibatasi lagi di dalam controller).
Route::middleware(['auth', 'verified', 'role:'.Role::Admin->value.','.Role::WaliKelas->value])
    ->prefix('students/{student}/guardians/{guardian}')
    ->name('students.guardians.')
    ->group(function () {
        Route::patch('income', [GuardianIncomeController::class, 'update'])->name('income');
    });

// Penugasan mengajar: guru mapel dan wali kelas hanya melihat penugasan miliknya.
Route::middleware(['auth', 'verified', 'role:'.Role::GuruMapel->value.','.Role::WaliKelas->value])
    ->prefix('teaching')
    ->name('teaching.')
    ->group(function () {
        Route::get('assignments', [TeachingAssignmentController::class, 'index'])->name('assignments');
    });

// Input nilai untuk admin, wali kelas, dan guru mapel. Penugasan mengajar
// masing-masing guru dibatasi di dalam controller.
Route::middleware(['auth', 'verified', 'role:'.Role::Admin->value.','.Role::WaliKelas->value.','.Role::GuruMapel->value])
    ->prefix('grades')
    ->name('grades.')
    ->group(function () {
        Route::get('/', [GradeController::class, 'index'])->name('index');
        Route::get('{classSubject}', [GradeController::class, 'edit'])->name('edit');
        Route::put('{classSubject}', [GradeController::class, 'update'])->name('update');
        Route::patch('{classSubject}/submit', [GradeController::class, 'submit'])->name('submit');
        Route::patch('{classSubject}/lock', [GradeController::class, 'lock'])->name('lock');
    });

// Rekap nilai kelas: admin seluruh kelas, wali kelas hanya kelas perwaliannya.
Route::middleware(['auth', 'verified', 'role:'.Role::Admin->value.','.Role::WaliKelas->value])
    ->prefix('reports')
    ->name('reports.')
    ->group(function () {
        Route::get('grades', [GradeReportController::class, 'index'])->name('grades');
    });

// Absensi harian: admin seluruh kelas, wali kelas hanya kelas perwaliannya.
Route::middleware(['auth', 'verified', 'role:'.Role::Admin->value.','.Role::WaliKelas->value])
    ->prefix('attendances')
    ->name('attendances.')
    ->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::get('recap', [AttendanceController::class, 'recap'])->name('recap');
    });

// Siswa kelas perwalian: wali kelas hanya melihat siswa di kelasnya sendiri.
Route::middleware(['auth', 'verified', 'role:'.Role::WaliKelas->value])
    ->prefix('homeroom')
    ->name('homeroom.')
    ->group(function () {
        Route::get('students', [HomeroomStudentController::class, 'index'])->name('students');
        Route::get('students/{student}', [HomeroomStudentController::class, 'show'])->name('students.show');
    });

// Audit nilai: jejak perubahan nilai untuk admin dan kepala sekolah.
Route::middleware(['auth', 'verified', 'role:'.Role::Admin->value.','.Role::KepalaSekolah->value])
    ->prefix('reports')
    ->name('reports.')
    ->group(function () {
        Route::get('grade-audit', [GradeAuditController::class, 'index'])->name('grade-audit');
        Route::get('averages', [GradeAverageController::class, 'index'])->name('averages');
        Route::get('exports', [ReportExportController::class, 'index'])->name('exports.index');
        Route::get('exports/preview', [ReportExportController::class, 'preview'])->name('exports.preview');
        Route::get('exports/download', [ReportExportController::class, 'download'])->name('exports.download');
    });

// Peringkat kelas: admin & kepala sekolah memantau semua kelas, wali kelas
// hanya kelas perwaliannya dan boleh menghitung ulang.
Route::middleware(['auth', 'verified', 'role:'.Role::Admin->value.','.Role::WaliKelas->value.','.Role::KepalaSekolah->value])
    ->prefix('rankings')
    ->name('rankings.')
    ->group(function () {
        Route::get('classes', [ClassRankingController::class, 'index'])->name('classes');
        Route::post('classes/{classroom}/generate', [ClassRankingController::class, 'generate'])->name('classes.generate');
        Route::patch('classes/{classroom}/publish', [ClassRankingController::class, 'publish'])->name('classes.publish');
    });

require __DIR__.'/auth.php';
