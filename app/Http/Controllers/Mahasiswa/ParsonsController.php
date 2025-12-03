<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Progress;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Http\Request;
use App\Models\QuestionBankConfig;
use Illuminate\Support\Facades\DB;
use App\Models\StudentAnswer;
use Illuminate\Support\Facades\Log;

class ParsonsController extends Controller
{
    public function index()
    {
        $isGuest = !auth()->check() || (auth()->check() && auth()->user()->role_id === 4);
        $userId = $isGuest ? session()->getId() : auth()->id();

        $allMaterials = Material::with(['questions', 'media'])->get();

        // Untuk guest, hanya tampilkan setengah dari total materi
        if ($isGuest) {
            $totalMaterials = $allMaterials->count();
            $materialsToShow = ceil($totalMaterials / 2);
            $allMaterials = $allMaterials->take($materialsToShow);
        }

        // Mendapatkan statistik progress
        $progressStats = DB::table('progress')
            ->select(
                'material_id',
                DB::raw('COUNT(DISTINCT question_id) as answered_questions'),
                DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers')
            )
            ->where('user_id', $userId)
            ->groupBy('material_id')
            ->get();

        // Mendapatkan jumlah mahasiswa unik yang mencoba soal untuk setiap materi
        $studentCounts = DB::table('progress')
            ->select('material_id', DB::raw('COUNT(DISTINCT user_id) as student_count'))
            ->groupBy('material_id')
            ->get()
            ->keyBy('material_id');

        // Proses setiap materi
        $materials = $allMaterials->map(function($material) use ($progressStats, $isGuest, $studentCounts) {
            // Hitung soal berdasarkan konfigurasi
            if ($isGuest) {
                // Untuk guest, batasi 3 soal per tingkat kesulitan
                $beginnerCount = min(3, $material->questions->where('difficulty', 'beginner')->count());
                $mediumCount = min(3, $material->questions->where('difficulty', 'medium')->count());
                $hardCount = min(3, $material->questions->where('difficulty', 'hard')->count());
                $configuredTotalQuestions = $beginnerCount + $mediumCount + $hardCount;
            } else {
                // Untuk pengguna terdaftar, gunakan konfigurasi admin
                $config = QuestionBankConfig::where('material_id', $material->id)
                    ->where('is_active', true)
                    ->first();

                if ($config) {
                    $configuredTotalQuestions = $config->beginner_count + $config->medium_count + $config->hard_count;
                } else {
                    $configuredTotalQuestions = $material->questions->count();
                }
            }

            $materialProgress = $progressStats->firstWhere('material_id', $material->id);
            $correctAnswers = $materialProgress ? $materialProgress->correct_answers : 0;

            $progressPercentage = $configuredTotalQuestions > 0
                ? min(100, round(($correctAnswers / $configuredTotalQuestions) * 100))
                : 0;

            // Ambil jumlah mahasiswa yang sudah mencoba soal ini
            $studentCount = isset($studentCounts[$material->id]) ? $studentCounts[$material->id]->student_count : 0;

            $material->progress_percentage = $progressPercentage;
            $material->total_questions = $configuredTotalQuestions;
            $material->completed_questions = $correctAnswers;
            $material->student_count = $studentCount;

            return $material;
        });

        return view('mahasiswa.materials.questions.parsons-problem.index', compact('materials', 'isGuest'));
    }

    public function levels(Material $material, Request $request)
{
    $materials = Material::orderBy('created_at', 'asc')->get();
    $difficulty = $request->query('difficulty', 'beginner');
    $isGuest = !auth()->check() || (auth()->check() && auth()->user()->role_id === 4);

    // Filter questions based on difficulty
    $questions = $material->questions()
        ->when($difficulty !== 'all', function($query) use ($difficulty) {
            return $query->where('difficulty', $difficulty);
        })
        ->get();

    // Determine which questions have been answered correctly
    $userId = auth()->id() ?? session()->getId();

    // For guests, special handling needed (both session formats)
    if ($isGuest) {
        $answeredQuestionIds = collect([]);

        // Check both formats of guest progress storage
        $guestProgress = session('guest_progress', []);
        $materialProgress = session('guest_progress.' . $material->id, []);

        // FORCE ADD QUESTION IDs FROM MATERIAL PROGRESS
        // This ensures that if a question is marked as correct in the material progress
        // it gets added to the answered questions list
        if (is_array($materialProgress)) {
            foreach (array_keys($materialProgress) as $questionId) {
                $answeredQuestionIds->push((int)$questionId);
            }
        }

        // Also check format 1 (additional check, can be removed if not needed)
        foreach ($guestProgress as $key => $progress) {
            // Format 1: "material_id_question_id" => ["is_correct" => true]
            if (is_array($progress) && isset($progress['is_correct']) && $progress['is_correct']) {
                $parts = explode('_', $key);
                if (count($parts) >= 2 && $parts[0] == $material->id) {
                    $questionId = (int)$parts[1];
                    if (!$answeredQuestionIds->contains($questionId)) {
                        $answeredQuestionIds->push($questionId);
                    }
                }
            }
        }
    } else {
        // Regular user progress from database
        $answeredQuestionIds = Progress::where('user_id', $userId)
            ->where('material_id', $material->id)
            ->where('is_correct', true)
            ->pluck('question_id');
    }

    $levels = [];
    $questionsArray = $questions->toArray();

    foreach ($questions as $index => $question) {
        $questionIndex = $index + 1;
        $isAnswered = $answeredQuestionIds->contains($question->id);

        if ($isAnswered) {
            // Question already answered correctly, mark as completed
            $status = 'completed';
        } elseif ($questionIndex === 1) {
            // First question is always unlocked
            $status = 'unlocked';
        } elseif ($index > 0 && $answeredQuestionIds->contains($questions[$index-1]->id)) {
            // Previous question was answered correctly, unlock this one
            $status = 'unlocked';
        } else {
            // Previous question not answered correctly, keep this locked
            $status = 'locked';
        }

        $levels[] = [
            'level' => $questionIndex,
            'question_id' => $question->id,
            'status' => $status,
            'difficulty' => $question->difficulty
        ];
    }

    return view('mahasiswa.materials.questions.parsons-problem.levels', compact(
        'material',
        'materials',
        'levels',
        'difficulty',
        'isGuest',
        'questions' // TAMBAHKAN INI
    ));
}
    public function show($number)
{
    $questions = Question::where('question_type', 'parsons')->get();
    $question = $questions[$number - 1];

    return view('parsons.cbt', [
        'totalQuestions' => $questions->count(),
        'currentQuestion' => $number,
        'answeredQuestions' => session('answered', []),
        'question' => $question,
        'answers' => Answer::where('question_id', $question->id)->get()
    ]);
}

public function answer(Request $request, $number)
{
    $answered = session('answered', []);

    if (!in_array($number, $answered)) {
        $answered[] = $number;
        session(['answered' => $answered]);
    }

    return redirect()->route('parsons.show', min($number + 1, count($answered)));
}

}
