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
use Illuminate\Support\Facades\Auth;
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
        $progressStats = DB::table('progress')->select('material_id', DB::raw('COUNT(DISTINCT question_id) as answered_questions'), DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers'))->where('user_id', $userId)->groupBy('material_id')->get();

        // Mendapatkan jumlah mahasiswa unik yang mencoba soal untuk setiap materi
        $studentCounts = DB::table('progress')->select('material_id', DB::raw('COUNT(DISTINCT user_id) as student_count'))->groupBy('material_id')->get()->keyBy('material_id');

        // Proses setiap materi
        $materials = $allMaterials->map(function ($material) use ($progressStats, $isGuest, $studentCounts) {
            // Hitung soal berdasarkan konfigurasi
            if ($isGuest) {
                // Untuk guest, batasi 3 soal per tingkat kesulitan
                $beginnerCount = min(3, $material->questions->where('difficulty', 'beginner')->count());
                $mediumCount = min(3, $material->questions->where('difficulty', 'medium')->count());
                $hardCount = min(3, $material->questions->where('difficulty', 'hard')->count());
                $configuredTotalQuestions = $beginnerCount + $mediumCount + $hardCount;
            } else {
                // Untuk pengguna terdaftar, gunakan konfigurasi admin
                $config = QuestionBankConfig::where('material_id', $material->id)->where('is_active', true)->first();

                if ($config) {
                    $configuredTotalQuestions = $config->beginner_count + $config->medium_count + $config->hard_count;
                } else {
                    $configuredTotalQuestions = $material->questions->count();
                }
            }

            $materialProgress = $progressStats->firstWhere('material_id', $material->id);
            $correctAnswers = $materialProgress ? $materialProgress->correct_answers : 0;

            $progressPercentage = $configuredTotalQuestions > 0 ? min(100, round(($correctAnswers / $configuredTotalQuestions) * 100)) : 0;

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
        $difficulty = $request->query('difficulty', 'parsons');
        $isGuest = !auth()->check() || (auth()->check() && auth()->user()->role_id === 4);
        $questions = $material
            ->questions()
            ->when($difficulty === 'parsons', function ($query) {
                return $query->where('difficulty', 'parsons')->whereIn('question_type', ['parsons_problem_2d', 'drag_and_drop']);
            })
            ->when($difficulty !== 'parsons', function ($query) use ($difficulty) {
                return $query->where('difficulty', $difficulty);
            })
            ->orderBy('id', 'asc')
            ->get();

        \Log::info('Parsons Questions Query', [
            'material_id' => $material->id,
            'difficulty' => $difficulty,
            'count' => $questions->count(),
        ]);

        $userId = auth()->id() ?? session()->getId();

        if ($isGuest) {
            $answeredQuestionIds = collect([]);
            $guestProgress = session('guest_progress', []);
            $materialProgress = session('guest_progress.' . $material->id, []);

            if (is_array($materialProgress)) {
                foreach (array_keys($materialProgress) as $questionId) {
                    if (isset($materialProgress[$questionId]['is_correct']) && $materialProgress[$questionId]['is_correct']) {
                        $answeredQuestionIds->push((int) $questionId);
                    }
                }
            }

            foreach ($guestProgress as $key => $progress) {
                if (is_array($progress) && isset($progress['is_correct']) && $progress['is_correct']) {
                    $parts = explode('_', $key);
                    if (count($parts) >= 2 && $parts[0] == $material->id) {
                        $questionId = (int) $parts[1];
                        if (!$answeredQuestionIds->contains($questionId)) {
                            $answeredQuestionIds->push($questionId);
                        }
                    }
                }
            }
        } else {
            $answeredQuestionIds = \App\Models\Progress::where('user_id', $userId)->where('material_id', $material->id)->where('is_correct', true)->pluck('question_id');
        }

        foreach ($questions as $question) {
            $question->is_answered = $answeredQuestionIds->contains($question->id);
        }

        $startQuestion = $questions->firstWhere('is_answered', false);

        if (!$startQuestion && $questions->count() > 0) {
            $startQuestion = $questions->first();
        }

        return view('mahasiswa.materials.questions.parsons-problem.levels', compact('material', 'materials', 'questions', 'difficulty', 'isGuest', 'startQuestion'));
    }

    public function getQuestionData(Material $material, $questionId)
    {
        try {
            $question = Question::where('material_id', $material->id)
                ->where('id', $questionId)
                ->whereIn('question_type', ['parsons_problem_2d', 'drag_and_drop'])
                ->with([
                    'answers' => function ($query) {
                        $query->orderBy('drag_target', 'asc')->orderBy('id', 'asc');
                    },
                ])
                ->firstOrFail();

            \Log::info('Question Data Loaded', [
                'question_id' => $question->id,
                'question_type' => $question->question_type,
                'answers_count' => $question->answers->count(),
            ]);

            $answersData = [];

            if ($question->question_type === 'parsons_problem_2d') {
                $answersData = $question->answers
                    ->map(function ($answer) {
                        return [
                            'id' => $answer->id,
                            'drag_source' => $answer->drag_source,
                            'drag_target' => $answer->drag_target,
                        ];
                    })
                    ->toArray();
            } else {
                $answersData = $question->answers
                    ->map(function ($answer) {
                        return [
                            'id' => $answer->id,
                            'answer_text' => $answer->answer_text,
                            'is_correct' => $answer->is_correct,
                            'explanation' => $answer->explanation,
                        ];
                    })
                    ->toArray();
            }

            return response()->json([
                'success' => true,
                'question' => [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'difficulty' => $question->difficulty,
                    'answers' => $answersData,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Question not found', [
                'material_id' => $material->id,
                'question_id' => $questionId,
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Soal tidak ditemukan atau tipe soal tidak didukung',
                ],
                404,
            );
        } catch (\Exception $e) {
            \Log::error('Error loading question', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
    // Method untuk submit jawaban Parsons Problem
    // public function submitParsonsAnswer(Material $material, Request $request)
    // {
    //     $request->validate([
    //         'question_id' => 'required|exists:questions,id',
    //         'answer_order' => 'required|array',
    //         'answer_order.*' => 'required|integer',
    //     ]);

    //     try {
    //         // Pastikan question belongs to material
    //         $question = Question::where('material_id', $material->id)->where('id', $request->question_id)->where('question_type', 'parsons_problem_2d')->with('answers')->firstOrFail();

    //         $isGuest = !auth()->check() || (auth()->check() && auth()->user()->role_id === 4);
    //         $userId = auth()->id() ?? session()->getId();

    //         // Get correct order from database (sorted by drag_target)
    //         // Urutan yang benar berdasarkan drag_target (1, 2, 3, dst)
    //         $correctOrder = $question->answers->sortBy('drag_target')->pluck('id')->toArray();

    //         // Compare with user's answer
    //         $userOrder = array_map('intval', $request->answer_order);
    //         $isCorrect = $correctOrder === $userOrder;

    //         // Log untuk debugging
    //         \Log::info('Answer Submission', [
    //             'question_id' => $question->id,
    //             'correct_order' => $correctOrder,
    //             'user_order' => $userOrder,
    //             'is_correct' => $isCorrect,
    //         ]);

    //         // Get attempts count
    //         if ($isGuest) {
    //             $sessionKey = "guest_progress.{$material->id}.{$question->id}";
    //             $currentProgress = session($sessionKey, []);
    //             $attempts = ($currentProgress['attempts'] ?? 0) + 1;

    //             session([
    //                 $sessionKey => [
    //                     'attempts' => $attempts,
    //                     'is_correct' => $isCorrect,
    //                     'last_attempt' => now()->toDateTimeString(),
    //                 ],
    //             ]);
    //         } else {
    //             // Save or update progress
    //             $progress = \App\Models\Progress::firstOrNew([
    //                 'user_id' => $userId,
    //                 'material_id' => $material->id,
    //                 'question_id' => $question->id,
    //             ]);

    //             $progress->attempts = ($progress->attempts ?? 0) + 1;
    //             $progress->is_correct = $isCorrect;
    //             $progress->save();

    //             $attempts = $progress->attempts;
    //         }

    //         // Get explanation from answers
    //         $explanation = $question->answers->first()->explanation ?? ($isCorrect ? 'Selamat! Urutan kode Anda sudah benar.' : 'Urutan kode belum tepat. Perhatikan logika alur program.');

    //         return response()->json([
    //             'success' => true,
    //             'correct' => $isCorrect,
    //             'attempts' => $attempts,
    //             'explanation' => $explanation,
    //             'message' => $isCorrect ? 'Jawaban benar!' : 'Jawaban salah, coba lagi!',
    //         ]);
    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         \Log::error('Question not found for submission', [
    //             'material_id' => $material->id,
    //             'question_id' => $request->question_id,
    //         ]);

    //         return response()->json(
    //             [
    //                 'success' => false,
    //                 'message' => 'Soal tidak ditemukan',
    //             ],
    //             404,
    //         );
    //     } catch (\Exception $e) {
    //         \Log::error('Error submitting parsons answer', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return response()->json(
    //             [
    //                 'success' => false,
    //                 'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
    //             ],
    //             500,
    //         );
    //     }
    // }

    public function answer(Request $request, $number)
    {
        $answered = session('answered', []);

        if (!in_array($number, $answered)) {
            $answered[] = $number;
            session(['answered' => $answered]);
        }

        return redirect()->route('parsons.show', min($number + 1, count($answered)));
    }

    public function submitParsons(Request $request, $materialId)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer_order' => 'required|array',
        ]);

        $userId = Auth::id();
        $questionId = $request->question_id;

        $question = Question::with('answers')->findOrFail($questionId);

        $attempt = Progress::where('user_id', $userId)->where('question_id', $questionId)->count() + 1;

        $correctOrder = $question->answers()->orderBy(column: 'drag_target')->pluck('id')->toArray();

        $isCorrect = $correctOrder === $request->answer_order;

        Progress::create([
            'user_id' => $userId,
            'material_id' => $materialId,
            'question_id' => $questionId,
            'is_answered' => 1,
            'is_correct' => $isCorrect ? 1 : 0,
            'attempt_number' => $attempt,
        ]);

        return response()->json([
            'success' => true,
            'correct' => $isCorrect,
            'attempts' => $attempt,
            'explanation' => $isCorrect ? 'Susunan kode sudah benar.' : 'Urutan kode masih salah, silakan coba lagi.',
        ]);
    }

    public function submitDragDrop(Request $request, $materialId)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answers' => 'required|array',
        ]);

        $userId = Auth::id();
        $questionId = $request->question_id;

        $attempt = Progress::where('user_id', $userId)->where('question_id', $questionId)->count() + 1;


        $isCorrect = collect($request->answers)->every(fn($a) => $a['is_correct'] == 1);

        Progress::create([
            'user_id' => $userId,
            'material_id' => $materialId,
            'question_id' => $questionId,
            'is_answered' => 1,
            'is_correct' => $isCorrect ? 1 : 0,
            'attempt_number' => $attempt,
        ]);

        return response()->json([
            'success' => true,
            'correct' => $isCorrect,
            'attempts' => $attempt,
            'explanation' => $isCorrect ? 'Jawaban Anda sudah tepat.' : 'Masih ada jawaban yang salah, silakan coba kembali.',
        ]);
    }
}
