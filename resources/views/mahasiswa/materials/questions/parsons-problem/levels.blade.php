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
            position: relative;
        }

        .question-box.disabled {
            pointer-events: none;
            cursor: not-allowed;
            opacity: 0.65;
        }

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



        .code-block:hover {
            background: #f8f9fa;
            border-color: #007bff;
        }

        .code-block.dragging {
            opacity: 0.5;
        }

        .answer-area .code-block {
            cursor: move;
            background: #e7f3ff;
            border-color: #007bff;
        }

        .code-block {
            --indent: 0;
            position: relative;
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 10px;
            margin-left: calc(var(--indent) * 40px);
            cursor: move;
            font-family: 'Courier New', monospace;
            transition: all 0.2s ease;
            user-select: none;
        }

        .code-block::before {
            content: "";
            position: absolute;
            left: -14px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: rgba(0, 123, 255, 0.15);
            display: none;
        }

        .code-block.indented::before {
            content: "";
            position: absolute;
            left: -20px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: rgba(0, 123, 255, 0.4);
            border-radius: 2px;
        }

        .code-block[data-indent="2"]::before {
            box-shadow: -40px 0 0 rgba(0, 123, 255, 0.4);
        }

        .code-block[data-indent="3"]::before {
            box-shadow: -40px 0 0 rgba(0, 123, 255, 0.4),
                -80px 0 0 rgba(0, 123, 255, 0.4);
        }

        .code-block[data-indent="4"]::before {
            box-shadow: -40px 0 0 rgba(0, 123, 255, 0.4),
                -80px 0 0 rgba(0, 123, 255, 0.4),
                -120px 0 0 rgba(0, 123, 255, 0.4);
        }

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

        .parsons-slot.drag-over::after {
            content: "↓ Letakkan di sini";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #007bff;
            font-size: 12px;
            font-weight: 600;
            pointer-events: none;
        }

        .parsons-slot:has(.code-block)::after {
            display: none;
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

        .parsons-slot {
            background: #f8f9fa;
            border: 2px dashed #ced4da;
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 10px;
            min-height: 52px;
            position: relative;
            transition: all 0.2s ease;
        }

        .parsons-slot.drag-over {
            background: #e7f3ff;
            border-color: #007bff;
            border-style: solid;
        }

        .parsons-slot.filled {
            background: #d4edda;
            border-color: #28a745;
            border-style: solid;
        }

        .parsons-slot:has(.code-block) {
            padding: 0;
            background: transparent;
            border: none;
        }

        .parsons-slot-label {
            position: absolute;
            top: 4px;
            right: 8px;
            font-size: 11px;
            color: #6c757d;
            font-weight: 600;
            pointer-events: none;
        }

        .question-box.locked {
            background: #adb5bd !important;
            color: #fff;
            cursor: not-allowed;
            opacity: 0.7;
            pointer-events: none;
            position: relative;
        }

        .question-box.locked::after {
            content: "🔒";
            position: absolute;
            bottom: 6px;
            right: 6px;
            font-size: 14px;
        }
    </style>

    @push('scripts')
        <script>
            let currentQuestion = null;
            let currentQuestionType = null;
            let currentMaterialId = {{ $material->id }};
            let codeBlocks = [];
            let draggedElement = null;

            function loadQuestion(questionId, questionType) {
                currentQuestion = {
                    id: questionId
                };
                console.log('Loading question:', questionId, 'Type:', questionType);

                if (!currentMaterialId) {
                    alert('Material ID tidak ditemukan');
                    return;
                }

                currentQuestionType = questionType;

                document.querySelectorAll('.question-box').forEach(box => {
                    box.classList.remove('active');
                });
                const activeBox = document.querySelector(`[data-question-id="${questionId}"]`);
                if (activeBox) activeBox.classList.add('active');

                const label = questionType === 'parsons_problem_2d' ? 'Parsons Problem' : 'Drag and Drop';
                document.getElementById('question-type-label').textContent = label;

                if (questionType === 'parsons_problem_2d') {
                    document.getElementById('parsons-content').style.display = 'block';
                    document.getElementById('dragdrop-content').style.display = 'none';
                } else {
                    document.getElementById('parsons-content').style.display = 'none';
                    document.getElementById('dragdrop-content').style.display = 'block';
                }

                document.getElementById('question-text').textContent = 'Memuat soal...';
                document.getElementById('feedback-area').innerHTML = '';

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

            function displayParsonsQuestion(question) {
                document.getElementById('question-text').textContent = question.question_text;

                const codeContainer = document.getElementById('code-blocks');
                const answerArea = document.getElementById('answer-area');

                codeContainer.innerHTML = '';
                answerArea.innerHTML = '';

                const autoBlocks = [];
                const manualBlocks = [];

                question.answers.forEach(ans => {
                    if (ans.drag_source.trim() === '}') {
                        autoBlocks.push(ans);
                    } else {
                        manualBlocks.push(ans);
                    }
                });

                codeBlocks = shuffleArray(manualBlocks);
                window.autoParsonsBlocks = autoBlocks.sort((a, b) => a.drag_target - b.drag_target);

                renderManualBlocks();
                renderAutoBlocksAtCorrectPosition();
            }

            function renderManualBlocks() {
                const container = document.getElementById('code-blocks');
                container.innerHTML = '';

                codeBlocks.forEach(answer => {
                    const div = document.createElement('div');
                    div.className = 'code-block';
                    div.draggable = true;
                    div.dataset.answerId = answer.id;
                    div.dataset.dragTarget = answer.drag_target;
                    div.textContent = answer.drag_source;

                    div.addEventListener('dragstart', handleDragStart);
                    div.addEventListener('dragend', handleDragEnd);

                    container.appendChild(div);
                });
            }


            function renderAutoBlocksAtCorrectPosition() {
                const answerArea = document.getElementById('answer-area');
                answerArea.innerHTML = '';

                const allAnswers = [...window.autoParsonsBlocks, ...codeBlocks];
                const maxTarget = Math.max(...allAnswers.map(a => a.drag_target));

                for (let i = 1; i <= maxTarget; i++) {
                    const slot = document.createElement('div');
                    slot.className = 'parsons-slot drop-zone';
                    slot.dataset.position = i;
                    setupParsonsDropZone(slot);
                    answerArea.appendChild(slot);
                }

                window.autoParsonsBlocks.forEach(ans => {
                    const div = document.createElement('div');
                    div.className = 'code-block auto-block';
                    div.textContent = ans.drag_source;
                    div.dataset.answerId = ans.id;
                    div.draggable = false;
                    div.style.opacity = '0.7';
                    div.style.cursor = 'default';

                    const targetSlot = answerArea.querySelector(`[data-position="${ans.drag_target}"]`);
                    if (targetSlot) {
                        targetSlot.appendChild(div);
                        targetSlot.classList.add('filled');
                    }
                });

                updateIndentation();
            }


            function setupParsonsDropZone(zone) {
                zone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('drag-over');
                });

                zone.addEventListener('dragleave', function() {
                    this.classList.remove('drag-over');
                });

                zone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('drag-over');

                    if (!draggedElement) return;

                    const existingBlock = this.querySelector('.code-block:not(.auto-block)');
                    if (existingBlock) {
                        // Swap blocks
                        const draggedParent = draggedElement.parentElement;
                        draggedParent.appendChild(existingBlock);
                    }

                    this.appendChild(draggedElement);
                    this.classList.add('filled');

                    console.log('✅ Block dropped to position', this.dataset.position);
                    updateIndentation();
                });
            }


            function updateQuestionAccess() {
                const boxes = document.querySelectorAll('.question-box');
                let activeFound = false;

                boxes.forEach(box => {
                    box.classList.remove('locked');
                    box.style.pointerEvents = 'none';

                    if (box.classList.contains('answered')) {
                        return;
                    }

                    if (!activeFound) {
                        activeFound = true;
                        box.style.pointerEvents = 'auto';
                    } else {
                        box.classList.add('locked');
                    }
                });
            }

            function updateIndentation() {
                const answerArea = document.getElementById('answer-area');
                const slots = answerArea.querySelectorAll('.parsons-slot');
                let indentLevel = 0;

                slots.forEach((slot) => {
                    const block = slot.querySelector('.code-block');
                    if (!block) return;

                    const text = block.textContent.trim();

                    if (text === '}' || text.startsWith('}')) {
                        indentLevel = Math.max(indentLevel - 1, 0);
                    }

                    block.style.setProperty('--indent', indentLevel);
                    block.dataset.indent = indentLevel;

                    if (indentLevel > 0) {
                        block.classList.add('indented');
                    } else {
                        block.classList.remove('indented');
                    }

                    if (text.endsWith('{') || text === '{') {
                        indentLevel++;
                    }
                });
            }




            function displayCodeBlocks() {
                const container = document.getElementById('code-blocks');
                container.innerHTML = '';

                codeBlocks.forEach((answer, index) => {
                    const div = document.createElement('div');
                    div.className = 'code-block';
                    div.draggable = true;
                    div.dataset.answerId = answer.id;
                    div.dataset.blockIndex = index;
                    div.dataset.isAuto = answer.drag_source.trim() === '}' ? '1' : '0';
                    div.textContent = answer.drag_source;

                    div.addEventListener('dragstart', handleDragStart);
                    div.addEventListener('dragend', handleDragEnd);

                    container.appendChild(div);
                });

                autoPlaceClosingBraces();
            }

            function autoPlaceClosingBraces() {
                const answerArea = document.getElementById('answer-area');
                const blocks = document.querySelectorAll('.code-block[data-is-auto="1"]');

                if (blocks.length === 0) return;

                const placeholder = answerArea.querySelector('.text-muted');
                if (placeholder) placeholder.remove();

                blocks.forEach(block => {
                    block.draggable = false;
                    block.style.opacity = '0.8';
                    block.style.cursor = 'default';

                    answerArea.appendChild(block);
                });
            }

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

            function handleDragStart(e) {
                draggedElement = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                console.log(' Dragging:', this.textContent.trim());
            }

            function handleDragEnd(e) {
                this.classList.remove('dragging');
            }

            window.onload = function() {
                const firstUnanswered = document.querySelector('.question-box.unanswered');

                if (firstUnanswered) {
                    loadQuestion(
                        firstUnanswered.dataset.questionId,
                        firstUnanswered.dataset.questionType
                    );
                }

                updateQuestionAccess();
            };

            function resetAnswer() {
                if (!currentQuestion) return;

                if (currentQuestionType === 'parsons_problem_2d') {
                    displayParsonsQuestion(currentQuestion);
                } else {
                    displayDragDropQuestion(currentQuestion);
                }

                document.getElementById('feedback-area').innerHTML = '';
            }

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
                                    ${data.attempts ? `<small class="d-block mt-2 text-muted">
                                                Percobaan ke-${data.attempts}
                                            </small>` : ''}
                                </div>
                                `;

                                const questionBox = document.querySelector(
                                    `[data-question-id="${currentQuestion.id}"]`
                                );

                                if (questionBox) {
                                    questionBox.classList.remove('unanswered');
                                    questionBox.classList.add('answered', 'disabled');
                                    updateQuestionAccess();

                                    if (!questionBox.querySelector('.check-icon')) {
                                        const checkIcon = document.createElement('span');
                                        checkIcon.className = 'check-icon';
                                        checkIcon.textContent = '✓';
                                        questionBox.appendChild(checkIcon);
                                    }

                                    questionBox.removeAttribute('onclick');
                                    questionBox.style.pointerEvents = 'none';
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
                                    } else {
                                        feedbackArea.innerHTML = `
                                        <div class="alert alert-success">
                                            <i class="fas fa-trophy me-2"></i>
                                            <strong>Selamat!</strong>
                                            <p class="mb-0 mt-2">Anda telah menyelesaikan semua soal!</p>
                                        </div>
                                        `;
                                                                            }
                                                                        }, 2000);

                                                                    } else {
                                                                        feedbackArea.innerHTML = `
                                        <div class="alert alert-danger">
                                            <i class="fas fa-times-circle me-2"></i>
                                            <strong>Jawaban Anda Salah</strong>
                                            <p class="mb-0 mt-2">${data.explanation || 'Silakan coba lagi!'}</p>
                                        </div>
                                        `;
                                                                    }

                                                                } else {
                                                                    feedbackArea.innerHTML = `
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-circle me-2"></i>
                                            ${data.message || 'Terjadi kesalahan'}
                                        </div>
                                        `;
                                                                }
                                                            })
                                                            .catch(error => {
                                                                console.error('Error:', error);
                                                                feedbackArea.innerHTML = `
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-circle me-2"></i>
                                            Terjadi kesalahan server
                                        </div>
                                        `;
                    });
            }


            function loadNextQuestion() {
                const boxes = Array.from(document.querySelectorAll('.question-box'));
                const currentIndex = boxes.findIndex(
                    box => box.dataset.questionId == currentQuestion.id
                );

                for (let i = currentIndex + 1; i < boxes.length; i++) {
                    if (!boxes[i].classList.contains('answered')) {
                        loadQuestion(
                            boxes[i].dataset.questionId,
                            boxes[i].dataset.questionType
                        );
                        return;
                    }
                }
                document.getElementById('question-text').textContent =
                    'Semua soal telah diselesaikan!';
                document.getElementById('parsons-content').style.display = 'none';
                document.getElementById('dragdrop-content').style.display = 'none';
            }

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
