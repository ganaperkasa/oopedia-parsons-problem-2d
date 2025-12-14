@extends('mahasiswa.layouts.app')

@section('title', 'Level Soal - ' . $material->title)

@section('content')
    <div class="container-fluid">
        <div class="dashboard-header text-center">
            <h1 class="main-title">Soal: {{ $material->title }}</h1>
            <div class="title-underline"></div>

            @if ($difficulty != 'all')
                <div class="difficulty-badge mb-4">
                    <i class="fas fa-signal me-2"></i>
                    <span>Menampilkan Soal: {{ ucfirst($difficulty) }}</span>
                </div>
            @endif
        </div>

        <div class="level-container">
            @if (auth()->check() && auth()->user()->role_id === 3)
                <div class="alert alert-info mb-4" role="alert">
                    <h5><i class="fas fa-info-circle"></i> Sistem Penilaian Pada Leaderboard</h5>
                    <p>Perhatikan bahwa nilai Anda di leaderboard bergantung pada jumlah percobaan yang dibutuhkan untuk
                        menjawab soal dengan benar:</p>
                    <div class="mt-2 fw-bold text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Pastikan jawaban Anda sudah benar sebelum mengirim untuk
                        mendapatkan nilai maksimal!
                    </div>
                </div>
            @endif

            <!-- Indikator Nomor Soal -->
            @if ($questions->count() > 0)
                <div class="question-indicators mb-4">
                    <div class="row g-2 justify-content-center">
                        @foreach ($questions as $index => $question)
                            <div class="col-auto">
                                <div class="question-box
                            {{ $question->is_answered ? 'answered disabled' : 'unanswered' }}"
                                    data-question-id="{{ $question->id }}"
                                    data-question-type="{{ $question->question_type }}" {{-- ❗ HANYA BOLEH KLIK JIKA BELUM DIJAWAB --}}
                                    @if (!$question->is_answered) onclick="loadQuestion({{ $question->id }}, '{{ $question->question_type }}')" @endif>
                                    {{-- ✅ TANDA CENTANG --}}
                                    @if ($question->is_answered)
                                        <span class="check-icon">✓</span>
                                    @endif

                                    {{ $index + 1 }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Belum ada soal untuk materi ini dengan difficulty: {{ $difficulty }}
                </div>
            @endif


            <!-- Area Soal (Parsons Problem) -->
            <div class="parsons-container card shadow-sm" id="parsons-area">
                <div class="card-header text-white" style="background: linear-gradient(135deg, #004E98 0%, #0074D9 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-code me-2"></i>
                        <span id="question-type-label">Parsons Problem</span>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Pertanyaan -->
                    <div class="question-section mb-4">
                        <h6 class="fw-bold">Soal:</h6>
                        <p id="question-text">Pilih soal dari nomor di atas untuk memulai...</p>
                    </div>

                    <!-- Area Drag & Drop untuk Parsons -->
                    <div id="parsons-content">
                        <div class="row">
                            <!-- Code Blocks -->
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Blok Kode:</h6>
                                <div id="code-blocks" class="code-blocks-area p-3 border rounded bg-light">
                                    <p class="text-muted text-center">Memuat blok kode...</p>
                                </div>
                            </div>

                            <!-- Answer Area -->
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Jawaban Anda:</h6>
                                <div id="answer-area" class="answer-area p-3 border rounded bg-white"
                                    style="min-height: 300px;">
                                    <p class="text-muted text-center">Drag blok kode ke sini</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Area Drag & Drop untuk Drag and Drop -->
                    <div id="dragdrop-content" style="display: none;">
                        <div class="row">
                            <!-- Available Answers -->
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Pilihan Jawaban:</h6>
                                <div id="dragdrop-options" class="dragdrop-options p-3 border rounded bg-light">
                                    <p class="text-muted text-center">Memuat pilihan jawaban...</p>
                                </div>
                            </div>

                            <!-- Drop Zones -->
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Area Jawaban:</h6>
                                <div id="dragdrop-zones" class="dragdrop-zones p-3 border rounded bg-white">
                                    <p class="text-muted text-center">Drag jawaban ke sini</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 d-flex justify-content-between">
                        <button type="button" class="btn btn-warning" onclick="resetAnswer()">
                            <i class="fas fa-redo me-2"></i>Reset
                        </button>
                        <button type="button" class="btn btn-success" onclick="submitAnswer()">
                            <i class="fas fa-check me-2"></i>Submit Jawaban
                        </button>
                    </div>

                    <!-- Feedback Area -->
                    <div id="feedback-area" class="mt-3"></div>
                </div>
            </div>

            <div class="level-actions mt-4">
                <a href="{{ route('mahasiswa.materials.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Materi
                </a>
            </div>
        </div>
    </div>

    <style>
        /* Question Indicators */
        .question-indicators {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .question-box {
            width: 50px;
            height: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

            /* ➕ tambahan */
            position: relative;
        }

        /* 🔒 SOAL SUDAH DIKERJAKAN */
        .question-box.disabled {
            pointer-events: none;
            cursor: not-allowed;
            opacity: 0.65;
        }

        /* ✅ TANDA CENTANG */
        .question-box .check-icon {
            position: absolute;
            top: -6px;
            right: -6px;
            background-color: #28a745;
            color: #fff;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            font-size: 12px;
            line-height: 18px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .question-box.unanswered {
            background: #dc3545;
            color: white;
        }

        .question-box.answered {
            background: #28a745;
            color: white;
        }

        .question-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .question-box.active {
            border: 3px solid #007bff;
            transform: scale(1.1);
        }

        /* Parsons Problem Container */
        .parsons-container {
            margin-bottom: 30px;
        }

        .code-blocks-area,
        .answer-area {
            min-height: 300px;
        }

        .code-block {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 10px;
            cursor: move;
            font-family: 'Courier New', monospace;
            transition: all 0.2s ease;
            user-select: none;
        }

        .code-block:hover {
            background: #f8f9fa;
            border-color: #007bff;
            transform: translateX(5px);
        }

        .code-block.dragging {
            opacity: 0.5;
        }

        .answer-area .code-block {
            cursor: move;
            background: #e7f3ff;
            border-color: #007bff;
        }

        /* Drag and Drop Styles */
        .dragdrop-options,
        .dragdrop-zones {
            min-height: 250px;
        }

        .drag-item {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 10px;
            cursor: move;
            transition: all 0.2s ease;
            user-select: none;
        }

        .drag-item:hover {
            background: #f8f9fa;
            border-color: #007bff;
            transform: translateX(5px);
        }

        .drag-item.dragging {
            opacity: 0.5;
        }

        .drop-zone {
            background: #f8f9fa;
            border: 2px dashed #6c757d;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            min-height: 60px;
            transition: all 0.2s ease;
            position: relative;
        }

        .drop-zone.drag-over {
            background: #e7f3ff;
            border-color: #007bff;
            border-style: solid;
        }

        .drop-zone.filled {
            background: #d4edda;
            border-color: #28a745;
            border-style: solid;
        }

        .drop-zone .dropped-item {
            background: white;
            border: 2px solid #28a745;
            border-radius: 6px;
            padding: 10px 15px;
            cursor: move;
        }

        .drop-zone-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .difficulty-badge {
            display: inline-block;
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.2);
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s ease;
        }
    </style>

    @push('scripts')
        <script>
            const START_QUESTION_ID = {{ $startQuestion->id ?? 'null' }};
    const START_QUESTION_TYPE = "{{ $startQuestion->question_type ?? '' }}";
            let currentQuestion = null;
            let currentQuestionType = null;
            let currentMaterialId = {{ $material->id }};
            let codeBlocks = [];
            let draggedElement = null;

            // Load Question
            function loadQuestion(questionId, questionType) {
                currentQuestion = { id: questionId };
                console.log('Loading question:', questionId, 'Type:', questionType);

                if (!currentMaterialId) {
                    alert('Material ID tidak ditemukan');
                    return;
                }

                currentQuestionType = questionType;

                // Update active indicator
                document.querySelectorAll('.question-box').forEach(box => {
                    box.classList.remove('active');
                });
                const activeBox = document.querySelector(`[data-question-id="${questionId}"]`);
                if (activeBox) activeBox.classList.add('active');

                // Update header label
                const label = questionType === 'parsons_problem_2d' ? 'Parsons Problem' : 'Drag and Drop';
                document.getElementById('question-type-label').textContent = label;

                // Show/hide appropriate content areas
                if (questionType === 'parsons_problem_2d') {
                    document.getElementById('parsons-content').style.display = 'block';
                    document.getElementById('dragdrop-content').style.display = 'none';
                } else {
                    document.getElementById('parsons-content').style.display = 'none';
                    document.getElementById('dragdrop-content').style.display = 'block';
                }

                // Show loading
                document.getElementById('question-text').textContent = 'Memuat soal...';
                document.getElementById('feedback-area').innerHTML = '';

                // Fetch question data
                const url = `/mahasiswa/materials/${currentMaterialId}/questions/${questionId}/data`;

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Question data received:', data);
                        if (data.success) {
                            currentQuestion = data.question;
                            if (questionType === 'parsons_problem_2d') {
                                displayParsonsQuestion(data.question);
                            } else {
                                displayDragDropQuestion(data.question);
                            }
                        } else {
                            alert('Gagal memuat soal: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error loading question:', error);
                        alert('Terjadi kesalahan saat memuat soal: ' + error.message);
                    });
            }

            // Display Parsons Question
            function displayParsonsQuestion(question) {
                document.getElementById('question-text').textContent = question.question_text;
                document.getElementById('code-blocks').innerHTML = '';
                document.getElementById('answer-area').innerHTML =
                    '<p class="text-muted text-center">Drag blok kode ke sini</p>';

                if (!question.answers || question.answers.length === 0) {
                    document.getElementById('code-blocks').innerHTML =
                        '<p class="text-danger">Tidak ada blok kode untuk soal ini</p>';
                    return;
                }

                // Shuffle answers
                codeBlocks = shuffleArray([...question.answers]);
                displayCodeBlocks();
            }

            // Display Code Blocks
            function displayCodeBlocks() {
                const container = document.getElementById('code-blocks');
                container.innerHTML = '';

                codeBlocks.forEach((answer, index) => {
                    const div = document.createElement('div');
                    div.className = 'code-block';
                    div.draggable = true;
                    div.dataset.answerId = answer.id;
                    div.dataset.blockIndex = index;
                    div.textContent = answer.drag_source;

                    div.addEventListener('dragstart', handleDragStart);
                    div.addEventListener('dragend', handleDragEnd);

                    container.appendChild(div);
                });
            }

            // Display Drag and Drop Question
            function displayDragDropQuestion(question) {
                document.getElementById('question-text').textContent = question.question_text;

                const optionsContainer = document.getElementById('dragdrop-options');
                const zonesContainer = document.getElementById('dragdrop-zones');

                optionsContainer.innerHTML = '';
                zonesContainer.innerHTML = '';

                if (!question.answers || question.answers.length === 0) {
                    optionsContainer.innerHTML = '<p class="text-danger">Tidak ada pilihan jawaban untuk soal ini</p>';
                    return;
                }

                // Shuffle and display answer options
                const shuffledAnswers = shuffleArray([...question.answers]);

                shuffledAnswers.forEach((answer, index) => {
                    const div = document.createElement('div');
                    div.className = 'drag-item';
                    div.draggable = true;
                    div.dataset.answerId = answer.id;
                    div.dataset.isCorrect = answer.is_correct;
                    div.textContent = answer.answer_text;

                    div.addEventListener('dragstart', handleDragStart);
                    div.addEventListener('dragend', handleDragEnd);

                    optionsContainer.appendChild(div);
                });

                // Create drop zones based on number of correct answers
                const correctAnswersCount = question.answers.filter(a => a.is_correct == 1).length;

                for (let i = 0; i < correctAnswersCount; i++) {
                    const zone = document.createElement('div');
                    zone.className = 'drop-zone';
                    zone.dataset.zoneIndex = i;
                    zone.innerHTML = `<div class="drop-zone-label">Area Jawaban ${i + 1}</div>`;

                    setupDropZone(zone);
                    zonesContainer.appendChild(zone);
                }
            }

            // Setup Drop Zone for Drag and Drop
            function setupDropZone(zone) {
                zone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('drag-over');
                });

                zone.addEventListener('dragleave', function(e) {
                    this.classList.remove('drag-over');
                });

                zone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('drag-over');

                    if (!draggedElement) return;

                    // Remove existing item in this zone
                    const existingItem = this.querySelector('.dropped-item');
                    if (existingItem) {
                        document.getElementById('dragdrop-options').appendChild(existingItem);
                        existingItem.classList.remove('dropped-item');
                    }

                    // Add new item
                    const label = this.querySelector('.drop-zone-label');
                    draggedElement.classList.add('dropped-item');
                    if (label) {
                        this.insertBefore(draggedElement, label.nextSibling);
                    } else {
                        this.appendChild(draggedElement);
                    }

                    this.classList.add('filled');
                });
            }

            // Drag and Drop Handlers
            function handleDragStart(e) {
                draggedElement = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            }

            function handleDragEnd(e) {
                this.classList.remove('dragging');
            }

            document.addEventListener('DOMContentLoaded', function() {
    const answerArea = document.getElementById('answer-area');

    answerArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    });

    answerArea.addEventListener('drop', function(e) {
        e.preventDefault();
        if (!draggedElement) return;

        const placeholder = answerArea.querySelector('.text-muted');
        if (placeholder) placeholder.remove();

        answerArea.appendChild(draggedElement);
    });

    // 🔥 LOAD SOAL AWAL DARI BACKEND
    if (START_QUESTION_ID && START_QUESTION_TYPE) {
        loadQuestion(START_QUESTION_ID, START_QUESTION_TYPE);
    }
});


            // Reset Answer
            function resetAnswer() {
                if (!currentQuestion) return;

                if (currentQuestionType === 'parsons_problem_2d') {
                    displayParsonsQuestion(currentQuestion);
                } else {
                    displayDragDropQuestion(currentQuestion);
                }

                document.getElementById('feedback-area').innerHTML = '';
            }

            // Submit Answer
            function submitAnswer() {
                if (!currentMaterialId || !currentQuestion) {
                    alert('Data tidak lengkap');
                    return;
                }

                const feedbackArea = document.getElementById('feedback-area');

                if (currentQuestionType === 'parsons_problem_2d') {
                    submitParsonsAnswer(feedbackArea);
                } else {
                    submitDragDropAnswer(feedbackArea);
                }
            }

            // Submit Parsons Answer
            function submitParsonsAnswer(feedbackArea) {
                const answerArea = document.getElementById('answer-area');
                const orderedBlocks = answerArea.querySelectorAll('.code-block');

                if (orderedBlocks.length === 0) {
                    feedbackArea.innerHTML =
                        '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Silakan susun kode terlebih dahulu!</div>';
                    return;
                }

                const currentOrder = Array.from(orderedBlocks).map(block => parseInt(block.dataset.answerId));

                feedbackArea.innerHTML =
                    '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Memeriksa jawaban...</div>';

                submitToServer('/mahasiswa/materials/' + currentMaterialId + '/questions/submit-parsons', {
                    question_id: currentQuestion.id,
                    answer_order: currentOrder
                }, feedbackArea);
            }

            // Submit Drag and Drop Answer
            function submitDragDropAnswer(feedbackArea) {
                const zones = document.querySelectorAll('.drop-zone');
                const answers = [];

                zones.forEach(zone => {
                    const item = zone.querySelector('.dropped-item');
                    if (item) {
                        answers.push({
                            answer_id: parseInt(item.dataset.answerId),
                            is_correct: parseInt(item.dataset.isCorrect)
                        });
                    }
                });

                if (answers.length === 0) {
                    feedbackArea.innerHTML =
                        '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Silakan drag jawaban ke area yang tersedia!</div>';
                    return;
                }

                feedbackArea.innerHTML =
                    '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Memeriksa jawaban...</div>';

                submitToServer('/mahasiswa/materials/' + currentMaterialId + '/questions/submit-dragdrop', {
                    question_id: currentQuestion.id,
                    answers: answers
                }, feedbackArea);
            }

            // Submit to Server
            function submitToServer(url, data, feedbackArea) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                const headers = {
                    'Content-Type': 'application/json',
                };

                if (csrfToken) {
                    headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
                }

                fetch(url, {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.correct) {
                                feedbackArea.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Jawaban Anda Benar!</strong>
                        <p class="mb-0 mt-2">${data.explanation || 'Selamat!'}</p>
                        ${data.attempts ? `<small class="d-block mt-2 text-muted">Percobaan ke-${data.attempts}</small>` : ''}
                    </div>
                `;

                                const questionBox = document.querySelector(`[data-question-id="${currentQuestion.id}"]`);
                                if (questionBox) {
                                    questionBox.classList.remove('unanswered');
                                    questionBox.classList.add('answered');
                                }

                                setTimeout(() => {
    const allBoxes = document.querySelectorAll('.question-box');
    const nextUnanswered = Array.from(allBoxes)
        .find(box => !box.classList.contains('answered'));

    if (nextUnanswered) {
        loadQuestion(
            nextUnanswered.dataset.questionId,
            nextUnanswered.dataset.questionType
        );
    }
}, 2000);

                            } else {
                                feedbackArea.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle me-2"></i>
                        <strong>Jawaban Anda Salah</strong>
                        <p class="mb-0 mt-2">${data.explanation || 'Silakan coba lagi!'}</p>
                        ${data.attempts ? `<small class="d-block mt-2 text-muted">Percobaan ke-${data.attempts}</small>` : ''}
                    </div>
                `;
                            }
                        } else {
                            feedbackArea.innerHTML =
                                `<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>${data.message || 'Terjadi kesalahan'}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        feedbackArea.innerHTML =
                            '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Terjadi kesalahan saat mengirim jawaban</div>';
                    });
            }

            // Utility: Shuffle Array
            function shuffleArray(array) {
                const newArray = [...array];
                for (let i = newArray.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
                }
                return newArray;
            }
        </script>
    @endpush
@endsection
