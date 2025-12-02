<x-layout bodyClass="g-sidenav-show bg-gray-200">
    @push('head')
        <x-head.tinymce-config />
    @endpush

    <x-navbars.sidebar activePage="questions" :userName="auth()->user()->name" :userRole="auth()->user()->role->role_name" />
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <x-navbars.navs.auth titlePage="Edit Soal" />
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card my-4">
                    <br><br>

                        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                            <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                                <h6 class="text-white text-capitalize ps-3">Edit Soal</h6>
                            </div>
                        </div>
                        <div class="card-body px-0 pb-2">
                            <form method="POST" action="{{ $material 
                                ? route('admin.materials.questions.update', ['material' => $material, 'question' => $question]) 
                                : route('admin.questions.update', $question) }}" class="p-4" id="questionForm">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Material</label>
                                            <div class="input-group input-group-outline">
                                                @if(isset($material))
                                                    <input type="hidden" name="material_id" value="{{ $material->id }}">
                                                    <input type="text" class="form-control" value="{{ $material->title }}" disabled>
                                                @else
                                                    <select name="material_id" id="material_id" class="form-control" required>
                                                        <option value="">Pilih Material</option>
                                                        @foreach($materials as $mat)
                                                            <option value="{{ $mat->id }}" {{ $question->material_id == $mat->id ? 'selected' : '' }}>
                                                                {{ $mat->title }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Pertanyaan</label>
                                            <div class="my-3">
                                                <textarea id="content-editor" name="question_text">{{ $question->question_text }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Tipe Soal</label>
                                            <div class="input-group input-group-outline">
                                                <select name="question_type" class="form-control" required>
                                                    <option value="radio_button" {{ $question->question_type == 'radio_button' ? 'selected' : '' }}>Radio Button</option>
                                                    <option value="drag_and_drop" {{ $question->question_type == 'drag_and_drop' ? 'selected' : '' }}>Drag and Drop</option>
                                                    <option value="fill_in_the_blank" {{ $question->question_type == 'fill_in_the_blank' ? 'selected' : '' }}>Fill in the Blank</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Tingkat Kesulitan</label>
                                            <div class="input-group input-group-outline">
                                                <select name="difficulty" class="form-control" required>
                                                    <option value="beginner" {{ $question->difficulty == 'beginner' ? 'selected' : '' }}>Beginner</option>
                                                    <option value="medium" {{ $question->difficulty == 'medium' ? 'selected' : '' }}>Medium</option>
                                                    <option value="hard" {{ $question->difficulty == 'hard' ? 'selected' : '' }}>Hard</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="answers-container">
                                    <h6 class="mb-3">Jawaban</h6>
                                    @foreach($question->answers as $index => $answer)
                                        <div class="answer-entry mb-3">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="input-group input-group-outline">
                                                        <input type="text" name="answers[{{ $index }}][answer_text]" class="form-control" placeholder="Jawaban" required value="{{ $answer->answer_text }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        @if($question->question_type === 'radio_button')
                                                            <input class="form-check-input" type="radio" name="correct_answer" value="{{ $index }}" {{ $answer->is_correct ? 'checked' : '' }}>
                                                            <label class="form-check-label">Jawaban Benar</label>
                                                            <input type="hidden" name="answers[{{ $index }}][is_correct]" value="{{ $answer->is_correct ? '1' : '0' }}">
                                                        @else
                                                            <input class="form-check-input" type="checkbox" name="answers[{{ $index }}][is_correct]" value="1" {{ $answer->is_correct ? 'checked' : '' }}>
                                                            <label class="form-check-label">Jawaban Benar</label>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" id="add-answer-btn" class="btn btn-outline-primary btn-sm mb-3" onclick="addAnswer()">
                                    Tambah Jawaban
                                </button>

                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">Simpan Perubahan</button>
                                        @if($material)
                                            <a href="{{ route('admin.materials.questions.index', $material) }}" class="btn btn-outline-secondary">Batal</a>
                                        @else
                                            <a href="{{ route('admin.questions.index') }}" class="btn btn-outline-secondary">Batal</a>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @push('js')
    <script>
        let answerCount = 1; // Mulai dari 1 karena sudah ada 1 jawaban awal

        function handleQuestionTypeChange() {
            const questionType = document.querySelector('[name="question_type"]').value;
            const answerContainer = document.getElementById('answers-container');
            const addAnswerBtn = document.getElementById('add-answer-btn');
            
            // Reset container kecuali heading
            const heading = answerContainer.querySelector('h6');
            answerContainer.innerHTML = '';
            answerContainer.appendChild(heading);
            
            // Reset counter
            answerCount = 0;
            
            // Tambah jawaban berdasarkan tipe soal
            if (questionType === 'fill_in_the_blank') {
                addAnswer(); // Hanya satu jawaban untuk fill in the blank
                if (addAnswerBtn) {
                    addAnswerBtn.style.display = 'none';
                }
            } else {
                // Untuk tipe soal lain, tambahkan dua jawaban dan tampilkan tombol tambah
                addAnswer();
                addAnswer();
                if (addAnswerBtn) {
                    addAnswerBtn.style.display = 'inline-block';
                }
            }
        }

        function addAnswer() {
            const container = document.getElementById('answers-container');
            const questionType = document.querySelector('[name="question_type"]').value;
            
            // Jangan menambahkan jawaban lagi jika tipe soal adalah fill_in_the_blank dan sudah ada jawaban
            if (questionType === 'fill_in_the_blank' && container.getElementsByClassName('answer-entry').length >= 1) {
                return;
            }
            
            const newAnswer = document.createElement('div');
            newAnswer.className = 'answer-entry mb-3';
            
            if (questionType === 'radio_button') {
                newAnswer.innerHTML = `
                    <div class="row">
                        <div class="col-md-8">
                            <div class="input-group input-group-outline">
                                <input type="text" name="answers[${answerCount}][answer_text]" class="form-control" placeholder="Jawaban" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input correct-radio" type="radio" name="correct_answer" value="${answerCount}">
                                <label class="form-check-label">Jawaban Benar</label>
                                <input type="hidden" name="answers[${answerCount}][is_correct]" value="0">
                            </div>
                        </div>
                    </div>
                `;
            } else if (questionType === 'drag_and_drop') {
                newAnswer.innerHTML = `
                    <div class="row">
                        <div class="col-md-8">
                            <div class="input-group input-group-outline">
                                <input type="text" name="answers[${answerCount}][answer_text]" class="form-control" placeholder="Jawaban" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="answers[${answerCount}][is_correct]" value="1">
                                <label class="form-check-label">Jawaban Benar</label>
                            </div>
                        </div>
                    </div>
                `;
            } else if (questionType === 'fill_in_the_blank') {
                newAnswer.innerHTML = `
                    <div class="row">
                        <div class="col-md-8">
                            <div class="input-group input-group-outline">
                                <input type="text" name="answers[${answerCount}][answer_text]" class="form-control" placeholder="Jawaban" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input correct-radio" type="radio" name="correct_answer" value="${answerCount}" checked>
                                <label class="form-check-label">Jawaban Benar</label>
                                <input type="hidden" name="answers[${answerCount}][is_correct]" value="1">
                            </div>
                        </div>
                    </div>
                `;
            }
            
            container.appendChild(newAnswer);
            answerCount++; // Increment counter setelah menambahkan elemen
            
            // Tambahkan event listener ke semua radio button
            setupRadioButtonListeners();
        }

        function setupRadioButtonListeners() {
            // Hapus event listener lama untuk menghindari duplikasi
            const correctRadios = document.querySelectorAll('.correct-radio');
            correctRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Ketika radio button dipilih, perbarui semua hidden input
                    updateAllHiddenInputs();
                });
            });
        }

        function updateAllHiddenInputs() {
            const container = document.getElementById('answers-container');
            const entries = container.getElementsByClassName('answer-entry');
            const selectedRadio = document.querySelector('input[name="correct_answer"]:checked');
            
            if (!selectedRadio) return;
            
            // Nilai terpilih
            const selectedValue = selectedRadio.value;
            
            // Update semua hidden input
            Array.from(entries).forEach((entry, index) => {
                const hiddenInput = entry.querySelector('input[type="hidden"]');
                if (hiddenInput) {
                    hiddenInput.value = (index.toString() === selectedValue) ? '1' : '0';
                }
            });
            
            console.log('Hidden inputs updated. Selected value:', selectedValue);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const questionTypeSelect = document.querySelector('[name="question_type"]');
            
            // Event listener untuk perubahan tipe soal
            questionTypeSelect.addEventListener('change', handleQuestionTypeChange);
            
            // Setup radio button listeners for initial elements
            setupRadioButtonListeners();
            
            // Inisialisasi tipe soal
            handleQuestionTypeChange();
        });
    </script>
    @endpush
    <x-admin.tutorial />

</x-layout>