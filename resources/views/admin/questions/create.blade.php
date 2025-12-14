<x-layout bodyClass="g-sidenav-show bg-gray-200">
    @push('head')
        <x-head.tinymce-config />
    @endpush

    <x-navbars.sidebar activePage="questions" :userName="auth()->user()->name" :userRole="auth()->user()->role->role_name" />
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <x-navbars.navs.auth titlePage="Tambah Soal" />
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card my-4">
                        <br><br>

                        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                            <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                                <h6 class="text-white text-capitalize ps-3">Tambah Soal Baru </h6>
                            </div>
                        </div>
                        <div class="card-body px-0 pb-2">
                            @if (isset($material))
                                <form method="POST" action="{{ route('admin.materials.questions.store', $material) }}"
                                    class="p-4" id="questionForm">
                                @else
                                    <form method="POST" action="{{ route('admin.questions.store') }}" class="p-4"
                                        id="questionForm">
                            @endif
                            @csrf

                            {{-- ERROR HANDLING --}}
                            @if ($errors->any())
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    @foreach ($errors->all() as $error)
                                        {{ $error }}<br>
                                    @endforeach
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            @if (session('warning'))
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    {{ session('warning') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif


                            {{-- MATERIAL --}}
                            <div class="mb-3">
                                <label class="form-label">Material</label>
                                <div class="input-group input-group-outline">
                                    @if (isset($material))
                                        <input type="hidden" name="material_id" value="{{ $material->id }}">
                                        <input type="text" class="form-control" value="{{ $material->title }}"
                                            disabled>
                                    @else
                                        <select name="material_id" class="form-control" required>
                                            <option value="">Pilih Material</option>
                                            @foreach ($materials as $material)
                                                <option value="{{ $material->id }}">{{ $material->title }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            </div>


                            {{-- PERTANYAAN --}}
                            <div class="mb-3">
                                <label class="form-label">Pertanyaan</label>
                                <textarea id="content-editor" name="question_text">{{ old('question_text') }}</textarea>
                            </div>


                            {{-- TIPE SOAL --}}
                            <div class="mb-3">
                                <label class="form-label">Tipe Soal</label>
                                <select name="question_type" class="form-control" required>
                                    <option value="fill_in_the_blank">Fill in the Blank</option>
                                    <option value="radio_button">Radio Button</option>
                                    <option value="drag_and_drop">Drag and Drop</option>
                                    <option value="parsons_problem_2d">Parsons Problem 2D</option>
                                </select>
                            </div>

                            <div class="mb-3" id="parsons-mode-wrapper" style="display:none;">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="parsons_mode" value="1" id="parsonsModeCheck">
        <label class="form-check-label" for="parsonsModeCheck">
            Parsons Mode (Drag jawaban saja)
        </label>
    </div>
    <small class="text-muted">Centang ini jika drag and drop termasuk Parsons Problem.</small>
</div>



                            {{-- DIFFICULTY --}}
                            <div class="mb-3">
                                <label class="form-label">Tingkat Kesulitan</label>
                                <select name="difficulty" class="form-control" required>
                                    <option value="beginner">Beginner</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>


                            {{-- JAWABAN BIASA --}}
                            <div id="answers-container">
                                <h6 class="mb-3">Jawaban</h6>

                                <div class="answer-entry mb-3">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <input type="text" name="answers[0][answer_text]" class="form-control"
                                                placeholder="Jawaban" required>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="correct_answer"
                                                    value="0">
                                                <label class="form-check-label">Jawaban Benar</label>
                                                <input type="hidden" name="answers[0][is_correct]" value="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            {{-- PARSONS PROBLEM --}}
                            <div id="answers-parsons" style="display:none;">
                                <h6 class="mb-3">Potongan Kode (Parsons 2D)</h6>

                                <div class="parsons-entry mb-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Drag Source</label>
                                            <input type="text" name="answers[0][drag_source]" class="form-control"
                                                placeholder="Potongan kode / source">
                                        </div>
                                        <div class="col-md-6">
                                            <label>Drag Target</label>
                                            <input type="text" name="answers[0][drag_target]" class="form-control"
                                                placeholder="Tempat target / posisi benar">
                                        </div>
                                    </div>
                                </div>
                            </div>


                            {{-- TAMBAH JAWABAN NORMAL --}}
                            <button type="button" id="add-answer-btn" class="btn btn-outline-primary btn-sm mb-3"
                                onclick="addAnswer()">
                                Tambah Jawaban
                            </button>

                            {{-- TAMBAH PARSONS --}}
                            <button type="button" id="add-parsons-btn" class="btn btn-outline-primary btn-sm mb-3"
                                onclick="addParsonsAnswer()" style="display:none;">
                                Tambah Potongan Kode
                            </button>


                            {{-- SUBMIT --}}
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">Simpan
                                        Soal</button>

                                    @if (isset($material))
                                        <a href="{{ route('admin.materials.questions.index', $material) }}"
                                            class="btn btn-outline-secondary">Batal</a>
                                    @else
                                        <a href="{{ route('admin.questions.index') }}"
                                            class="btn btn-outline-secondary">Batal</a>
                                    @endif
                                </div>
                            </div>

                            </form> {{-- <- INI WAJIB ADA DAN SUDAH DIBENARKAN --}}

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @push('js')
       <script>
let answerCount = 1;

function handleQuestionTypeChange() {
    const questionType = document.querySelector('[name="question_type"]').value;

    const normalContainer = document.getElementById('answers-container');
    const parsonsContainer = document.getElementById('answers-parsons');

    const normalAddBtn = document.getElementById('add-answer-btn');
    const parsonsAddBtn = document.getElementById('add-parsons-btn');

    const parsonsModeWrapper = document.getElementById('parsons-mode-wrapper');
    const parsonsModeCheck = document.getElementById('parsonsModeCheck');

    // Reset fields first
    normalContainer.style.display = 'none';
    parsonsContainer.style.display = 'none';
    normalAddBtn.style.display = 'none';
    parsonsAddBtn.style.display = 'none';

    // ========== DRAG AND DROP ==========
    if (questionType === 'drag_and_drop') {
        // Parsons Mode OPTIONAL
        parsonsModeWrapper.style.display = 'block';

        // ✅ PERBAIKAN: Jangan reset checkbox, biarkan user yang pilih
        // Hanya set default jika belum pernah di-initialize
        if (parsonsModeCheck.dataset.initialized !== 'true') {
            parsonsModeCheck.checked = false;
            parsonsModeCheck.dataset.initialized = 'true';
        }

        // Show normal answer form
        normalContainer.style.display = 'block';
        normalAddBtn.style.display = 'block';
        normalContainer.innerHTML = `<h6 class="mb-3">Jawaban</h6>`;
        addAnswer();
        addAnswer();
        return;
    }

    // ========== PARSONS PROBLEM 2D ==========
    if (questionType === 'parsons_problem_2d') {
        // Parsons Mode ALWAYS ON
        parsonsModeWrapper.style.display = 'block';
        parsonsModeCheck.checked = true;
        parsonsModeCheck.disabled = true; // ✅ Disable agar tidak bisa diubah

        // Show Parsons inputs
        parsonsContainer.style.display = 'block';
        parsonsAddBtn.style.display = 'block';

        parsonsContainer.innerHTML = `
            <h6 class="mb-3">Potongan Kode (Parsons 2D)</h6>
        `;
        addParsonsAnswer();
        return;
    }

    // ========== FILL IN THE BLANK ==========
    if (questionType === 'fill_in_the_blank') {
        parsonsModeWrapper.style.display = 'none';
        parsonsModeCheck.disabled = false;

        normalContainer.style.display = 'block';
        normalContainer.innerHTML = `<h6 class="mb-3">Jawaban</h6>`;
        addAnswer(); // satu saja
        return;
    }

    // ========== RADIO BUTTON ==========
    if (questionType === 'radio_button') {
        parsonsModeWrapper.style.display = 'none';
        parsonsModeCheck.disabled = false;

        normalContainer.style.display = 'block';
        normalContainer.innerHTML = `<h6 class="mb-3">Jawaban</h6>`;
        addAnswer();
        addAnswer();
        normalAddBtn.style.display = 'block';
        return;
    }
}

function addParsonsAnswer() {
    const container = document.getElementById('answers-parsons');
    const index = container.getElementsByClassName('parsons-entry').length;

    const html = `
        <div class="parsons-entry mb-3">
            <div class="row">
                <div class="col-md-6">
                    <label>Drag Source</label>
                    <input type="text" name="answers[${index}][drag_source]" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Drag Target</label>
                    <input type="text" name="answers[${index}][drag_target]" class="form-control" required>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
}

function addAnswer() {
    const type = document.querySelector('[name="question_type"]').value;
    if (type === 'parsons_problem_2d') return;

    const container = document.getElementById('answers-container');
    const index = container.getElementsByClassName('answer-entry').length;

    const html = `
        <div class="answer-entry mb-3">
            <div class="row">
                <div class="col-md-8">
                    <input type="text" name="answers[${index}][answer_text]" class="form-control" placeholder="Jawaban" required>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="correct_answer" value="${index}">
                        <label class="form-check-label">Jawaban Benar</label>
                        <input type="hidden" name="answers[${index}][is_correct]" value="0">
                    </div>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
}


document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Script loaded!');

    // Cek apakah form ada
    const form = document.querySelector('form');
    if (!form) {
        console.error('❌ FORM TIDAK DITEMUKAN!');
        return;
    }
    console.log('✅ Form ditemukan:', form);

    // Cek apakah checkbox ada
    const checkbox = document.getElementById('parsonsModeCheck');
    if (!checkbox) {
        console.error('❌ CHECKBOX parsonsModeCheck TIDAK DITEMUKAN!');
    } else {
        console.log('✅ Checkbox ditemukan:', checkbox);
    }

    // Setup question type change
    const questionTypeSelect = document.querySelector('[name="question_type"]');
    if (questionTypeSelect) {
        questionTypeSelect.addEventListener('change', handleQuestionTypeChange);
        handleQuestionTypeChange();
        console.log('✅ Question type handler terpasang');
    }

    // Setup form submit listener
    form.addEventListener('submit', function(e) {
        console.log('🚀 FORM SEDANG DI-SUBMIT!');

        const checkbox = document.getElementById('parsonsModeCheck');

        if (!checkbox) {
            console.error('❌ Checkbox tidak ditemukan saat submit!');
            return;
        }

        console.log('=== FORM SUBMIT DEBUG ===');
        console.log('Checkbox checked:', checkbox.checked);
        console.log('Checkbox value:', checkbox.value);
        console.log('Checkbox disabled:', checkbox.disabled);
        console.log('Checkbox name:', checkbox.name);
        console.log('Checkbox type:', checkbox.type);
        console.log('Checkbox akan dikirim:', checkbox.checked && !checkbox.disabled ? 'YES (value=1)' : 'NO');

        // Tampilkan semua data form
        const formData = new FormData(e.target);
        console.log('FormData entries:');
        let hasParsonsMode = false;
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: ${value}`);
            if (key === 'parsons_mode') {
                hasParsonsMode = true;
            }
        }

        // Warning jika checkbox checked tapi tidak masuk FormData
        if (checkbox.checked && !hasParsonsMode) {
            console.error('⚠️⚠️⚠️ WARNING: Checkbox CHECKED tapi TIDAK masuk FormData!');
            console.error('Penyebab:');
            if (checkbox.disabled) {
                console.error('  - Checkbox DISABLED (disabled checkbox tidak dikirim)');
            }
            if (!checkbox.name) {
                console.error('  - Checkbox tidak punya NAME attribute');
            }
            if (!form.contains(checkbox)) {
                console.error('  - Checkbox berada DI LUAR form');
            }
        } else if (checkbox.checked && hasParsonsMode) {
            console.log('✅ Checkbox berhasil masuk FormData!');
        }

        // Jangan prevent default, biar form tetap submit
        // e.preventDefault(); // JANGAN aktifkan ini
    });

    console.log('✅ Form submit listener terpasang');
});
</script>


    @endpush
    <x-admin.tutorial />
</x-layout>
