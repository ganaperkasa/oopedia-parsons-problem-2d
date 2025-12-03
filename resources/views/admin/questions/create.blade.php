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
    <form method="POST" action="{{ route('admin.materials.questions.store', $material) }}" class="p-4" id="questionForm">
@else
    <form method="POST" action="{{ route('admin.questions.store') }}" class="p-4" id="questionForm">
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
            <input type="text" class="form-control" value="{{ $material->title }}" disabled>
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
                    <input class="form-check-input" type="radio" name="correct_answer" value="0">
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
<button type="button" id="add-answer-btn" class="btn btn-outline-primary btn-sm mb-3" onclick="addAnswer()">
    Tambah Jawaban
</button>

{{-- TAMBAH PARSONS --}}
<button type="button" id="add-parsons-btn" class="btn btn-outline-primary btn-sm mb-3" onclick="addParsonsAnswer()" style="display:none;">
    Tambah Potongan Kode
</button>


{{-- SUBMIT --}}
<div class="row">
    <div class="col-12">
        <button type="submit" class="btn btn-primary" id="submitBtn">Simpan Soal</button>

        @if (isset($material))
            <a href="{{ route('admin.materials.questions.index', $material) }}" class="btn btn-outline-secondary">Batal</a>
        @else
            <a href="{{ route('admin.questions.index') }}" class="btn btn-outline-secondary">Batal</a>
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

    // Jika Parsons Problem → tampilkan input Parsons, sembunyikan input normal
    if (questionType === 'parsons_problem_2d') {
        normalContainer.style.display = 'none';
        parsonsContainer.style.display = 'block';

        // PENTING: Hapus required dari input yang disembunyikan
        normalContainer.querySelectorAll('input[required]').forEach(input => {
            input.removeAttribute('required');
        });

        // Tambahkan required ke input Parsons
        parsonsContainer.querySelectorAll('input').forEach(input => {
            input.setAttribute('required', 'required');
        });

        // Sembunyikan tombol normal, tampilkan tombol Parsons
        if (normalAddBtn) normalAddBtn.style.display = 'none';
        if (parsonsAddBtn) parsonsAddBtn.style.display = 'block';
        return;
    }

    // Jika bukan Parsons Problem → tampilkan input jawaban normal
    normalContainer.style.display = 'block';
    parsonsContainer.style.display = 'none';

    // PENTING: Hapus required dari input Parsons yang disembunyikan
    parsonsContainer.querySelectorAll('input[required]').forEach(input => {
        input.removeAttribute('required');
    });

    // Sembunyikan tombol Parsons, tampilkan tombol normal
    if (parsonsAddBtn) parsonsAddBtn.style.display = 'none';

    // Reset container normal
    normalContainer.innerHTML = `<h6 class="mb-3">Jawaban</h6>`;

    // Untuk fill in the blank → hanya 1 jawaban
    if (questionType === 'fill_in_the_blank') {
        addAnswer();
        if (normalAddBtn) normalAddBtn.style.display = 'none';
    } else {
        // Selain itu → minimal 2 jawaban
        addAnswer();
        addAnswer();
        if (normalAddBtn) normalAddBtn.style.display = 'block';
    }

    updateAnswerUI(questionType);
}

function addParsonsAnswer() {
    const container = document.getElementById('answers-parsons');
    const index = container.getElementsByClassName('parsons-entry').length;

    const newEntry = document.createElement('div');
    newEntry.className = 'parsons-entry mb-3';

    const questionType = document.querySelector('[name="question_type"]').value;
    const isRequired = questionType === 'parsons_problem_2d' ? 'required' : '';

    newEntry.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <label>Drag Source</label>
                <input type="text" name="answers[${index}][drag_source]" class="form-control"
                    placeholder="Potongan kode / source" ${isRequired}>
            </div>
            <div class="col-md-6">
                <label>Drag Target</label>
                <input type="text" name="answers[${index}][drag_target]" class="form-control"
                    placeholder="Tempat target / posisi benar" ${isRequired}>
            </div>
        </div>
    `;

    container.appendChild(newEntry);
}

function addAnswer() {
    const type = document.querySelector('[name="question_type"]').value;

    // Jika parsons problem → gunakan generator khusus
    if (type === 'parsons_problem_2d') {
        addParsonsAnswer();
        return;
    }

    // Fungsi lama (radio button, fill, dll)
    const container = document.getElementById('answers-container');
    const index = container.getElementsByClassName('answer-entry').length;

    const newAnswer = document.createElement('div');
    newAnswer.className = 'answer-entry mb-3';

    newAnswer.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <div class="input-group input-group-outline">
                    <input type="text" name="answers[${index}][answer_text]" class="form-control"
                           placeholder="Jawaban" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="correct_answer" value="${index}">
                    <label class="form-check-label">Jawaban Benar</label>
                    <input type="hidden" name="answers[${index}][is_correct]" value="0">
                </div>
            </div>
        </div>
    `;

    container.appendChild(newAnswer);
}

function updateAnswerUI(questionType) {
    const answerEntries = document.querySelectorAll('.answer-entry');

    answerEntries.forEach((entry, index) => {
        const radioInput = entry.querySelector('input[type="radio"]');
        const hiddenCorrect = entry.querySelector('input[name$="[is_correct]"]');

        if (questionType === 'fill_in_the_blank') {
            if (index === 0) {
                radioInput.checked = true;
                hiddenCorrect.value = 1;
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const questionTypeSelect = document.querySelector('[name="question_type"]');
    const form = document.getElementById('questionForm');

    questionTypeSelect.addEventListener('change', handleQuestionTypeChange);

    document.addEventListener('change', function(e) {
        if (e.target.type === 'radio' && e.target.name === 'correct_answer') {
            const container = document.getElementById('answers-container');
            const answers = container.getElementsByClassName('answer-entry');

            Array.from(answers).forEach((entry, i) => {
                const hiddenCorrect = entry.querySelector('input[name$="[is_correct]"]');
                hiddenCorrect.value = (i == e.target.value) ? '1' : '0';
            });
        }
    });

    // Validasi submit
    form.addEventListener('submit', function(e) {
        const type = questionTypeSelect.value;

        // Parsons Problem → tidak pakai validasi correct_answer
        if (type === 'parsons_problem_2d') {
            return; // langsung submit
        }

        if (type === 'radio_button') {
            const selected = document.querySelector('input[name="correct_answer"]:checked');
            if (!selected) {
                alert('Pilih jawaban benar');
                e.preventDefault();
                return;
            }
        }
    });

    handleQuestionTypeChange();
});
        </script>
    @endpush
    <x-admin.tutorial />
</x-layout>
