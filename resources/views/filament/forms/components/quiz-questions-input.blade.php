@php
    $statePath = $getStatePath();
    $questions = $getQuestionsFromState();
    $isReorderable = $isReorderable();
    $isRadioCorrect = $isRadioCorrect();
    $minAnswers = $getMinAnswers();
    $maxAnswers = $getMaxAnswers();
    $minQuestions = $getMinQuestions();
    $maxQuestions = $getMaxQuestions();
    $hint = $getHint();
    $questionsCount = count($questions);
@endphp

<div
    x-data="{
        questions: [],
        uidCounter: 0,
        statePath: '',
        init() {
            this.questions = JSON.parse(this.$el.getAttribute('data-questions') || '[]');
            this.uidCounter = 0;
            this.questions.forEach((q) => {
                this.uidCounter++;
                q._uid = q._uid || 'q_' + this.uidCounter;
                (q.answers || []).forEach((a) => {
                    this.uidCounter++;
                    a._uid = a._uid || 'a_' + this.uidCounter;
                });
            });
            this.statePath = this.$el.getAttribute('data-path') || '';
            this.$nextTick(() => this.initSortable());
        },
        initSortable() {
            const questionsEl = this.$el.querySelector('[data-sortable-questions]');
            if (questionsEl) {
                window.Sortable.create(questionsEl, {
                    handle: '[data-drag-handle]',
                    animation: 150,
                    onEnd: (evt) => {
                        const item = this.questions.splice(evt.oldIndex, 1)[0];
                        this.questions.splice(evt.newIndex, 0, item);
                        this.updateSortOrders();
                        this.sync();
                    },
                });
            }
            this.$el.querySelectorAll('[data-sortable-answers]').forEach((el) => {
                const questionUid = el.dataset.questionUid;
                if (!questionUid) return;
                window.Sortable.create(el, {
                    handle: '[data-drag-handle-answer]',
                    animation: 150,
                    onEnd: (evt) => {
                        const q = this.questions.find((q) => q._uid === questionUid);
                        if (!q) return;
                        const item = q.answers.splice(evt.oldIndex, 1)[0];
                        q.answers.splice(evt.newIndex, 0, item);
                        this.updateAnswerSortOrders(q);
                        this.sync();
                    },
                });
            });
        },
        addQuestion(focusNew) {
            this.uidCounter++;
            this.questions.push({
                _uid: 'q_' + this.uidCounter,
                id: null,
                content: '',
                sort_order: this.questions.length,
                answers: [],
            });
            this.updateSortOrders();
            this.sync();
            if (focusNew) {
                setTimeout(() => {
                    const el = this.$el.querySelector('[data-sortable-questions]');
                    if (!el) return;
                    const textareas = el.querySelectorAll('textarea');
                    const last = textareas[textareas.length - 1];
                    if (last) last.focus();
                }, 50);
            }
        },
        removeQuestion(uid) {
            this.questions = this.questions.filter((q) => q._uid !== uid);
            this.updateSortOrders();
            this.sync();
        },
        addAnswer(questionUid, shouldFocus) {
            this.uidCounter++;
            const q = this.questions.find((q) => q._uid === questionUid);
            if (!q) return;
            q.answers.push({
                _uid: 'a_' + this.uidCounter,
                id: null,
                content: '',
                is_correct: false,
                sort_order: q.answers.length,
            });
            this.updateAnswerSortOrders(q);
            this.$nextTick(() => {
                const container = this.$el.querySelector(`[data-sortable-answers][data-question-uid='${questionUid}']`);
                if (container) {
                    if (container.sortable) container.sortable.destroy();
                    window.Sortable.create(container, {
                        handle: '[data-drag-handle-answer]',
                        animation: 150,
                        onEnd: (evt) => {
                            const qq = this.questions.find((qq) => qq._uid === questionUid);
                            if (!qq) return;
                            const item = qq.answers.splice(evt.oldIndex, 1)[0];
                            qq.answers.splice(evt.newIndex, 0, item);
                            this.updateAnswerSortOrders(qq);
                            this.sync();
                        },
                    });
                    if (shouldFocus) {
                        setTimeout(() => {
                            const inputs = container.querySelectorAll(`input[type='text']`);
                            const last = inputs[inputs.length - 1];
                            if (last) last.focus();
                        }, 50);
                    }
                }
            });
            this.sync();
        },
        removeAnswer(questionUid, answerUid) {
            const q = this.questions.find((q) => q._uid === questionUid);
            if (!q) return;
            q.answers = q.answers.filter((a) => a._uid !== answerUid);
            this.updateAnswerSortOrders(q);
            this.sync();
        },
        setCorrectAnswer(questionUid, answerUid) {
            const q = this.questions.find((q) => q._uid === questionUid);
            if (!q) return;
            q.answers.forEach((a) => {
                a.is_correct = a._uid === answerUid;
            });
            this.sync();
        },
        updateSortOrders() {
            this.questions.forEach((q, i) => {
                q.sort_order = i;
            });
        },
        updateAnswerSortOrders(q) {
            q.answers.forEach((a, i) => {
                a.sort_order = i;
            });
        },
        sync() {
            const payload = this.questions.map((q) => ({
                id: q.id,
                content: q.content,
                sort_order: q.sort_order,
                answers: q.answers.map((a) => ({
                    id: a.id,
                    content: a.content,
                    is_correct: a.is_correct,
                    sort_order: a.sort_order,
                })),
            }));
            this.$wire.set(this.statePath, JSON.stringify(payload));
        },
    }"
    data-questions="{{ json_encode($questions) }}"
    data-count="{{ $questionsCount }}"
    data-path="{{ $statePath }}"
    wire:ignore
    wire:key="{{ $getId() }}"
    class="quiz-questions-input space-y-4"
>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $getLabel() ?? 'Questions' }}
            </span>
            <span class="text-xs text-gray-400" x-text="`(${questions.length})`"></span>
            @if ($minQuestions)
                <span class="text-xs text-gray-400">(min {{ $minQuestions }})</span>
            @endif
            @if ($maxQuestions)
                <span class="text-xs text-gray-400">(max {{ $maxQuestions }})</span>
            @endif
        </div>
        <button
            type="button"
            @click="addQuestion()"
            class="fi-btn fi-btn-sm fi-btn-primary"
        >
            + Add Question
        </button>
    </div>

    <div data-sortable-questions class="space-y-3">
        <template x-for="(question, qIndex) in questions" :key="question._uid">
            <div class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    @if ($isReorderable)
                        <div data-drag-handle class="mt-2 cursor-grab text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                            </svg>
                        </div>
                    @endif
                    <div class="flex-1 space-y-3">
                        <div class="flex items-start gap-2">
                            <span class="text-xs font-semibold text-gray-500 mt-2 min-w-[20px]" x-text="`#${qIndex + 1}`"></span>
                            <div class="flex-1">
                                <textarea
                                    x-model="question.content"
                                    @blur="sync()"
                                    @keydown.enter.ctrl.prevent="addQuestion(true)"
                                    rows="2"
                                    placeholder="Enter question content..."
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                ></textarea>
                                <p class="text-[10px] text-gray-400 mt-0.5">Ctrl+Enter to add question</p>
                            </div>
                            <button
                                type="button"
                                @click="removeQuestion(question._uid)"
                                class="mt-1 text-danger-500 hover:text-danger-700"
                                title="Remove question"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>

                        <div class="ml-5 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-500">Answers <span x-text="`(${question.answers.length})`"></span></span>
                                <button
                                    type="button"
                                    @click="addAnswer(question._uid, true)"
                                    class="text-xs text-primary-600 hover:text-primary-800 font-medium"
                                >
                                    + Add Answer
                                </button>
                            </div>

                            <div data-sortable-answers :data-question-uid="question._uid" class="space-y-1">
                                <template x-for="(answer, aIndex) in question.answers" :key="answer._uid">
                                    <div class="flex items-center gap-2 py-1 px-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 group">
                                        @if ($isReorderable)
                                            <div data-drag-handle-answer class="cursor-grab text-gray-300 hover:text-gray-500 opacity-0 group-hover:opacity-100">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <input
                                            type="text"
                                            x-model="answer.content"
                                            @blur="sync()"
                                            @keydown.enter.prevent="addAnswer(question._uid, true)"
                                            placeholder="Answer option... (Enter to add)"
                                            class="flex-1 rounded border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm focus:ring-2 focus:ring-primary-500"
                                        />
                                        <label class="flex items-center gap-1.5 cursor-pointer whitespace-nowrap select-none">
                                            @if ($isRadioCorrect)
                                                <div
                                                    @click.prevent="setCorrectAnswer(question._uid, answer._uid)"
                                                    class="relative flex items-center justify-center w-7 h-7"
                                                >
                                                    <svg x-show="!answer.is_correct"
                                                        class="w-5 h-5 text-gray-400 hover:text-primary-500 transition-colors"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    >
                                                        <circle cx="12" cy="12" r="10" stroke-width="1.5"/>
                                                    </svg>
                                                    <svg x-show="answer.is_correct"
                                                        class="w-5 h-5 text-primary-500 transition-colors"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    >
                                                        <circle cx="12" cy="12" r="10" stroke-width="1.5" fill="currentColor" fill-opacity="0.15"/>
                                                        <circle cx="12" cy="12" r="4" fill="currentColor"/>
                                                    </svg>
                                                </div>
                                            @else
                                                <div
                                                    @click.prevent="answer.is_correct = !answer.is_correct; sync();"
                                                    class="relative flex items-center justify-center w-7 h-7"
                                                >
                                                    <svg x-show="!answer.is_correct"
                                                        class="w-5 h-5 text-gray-400 hover:text-primary-500 transition-colors"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    >
                                                        <rect x="3" y="3" width="18" height="18" rx="3" stroke-width="1.5"/>
                                                    </svg>
                                                    <svg x-show="answer.is_correct"
                                                        class="w-5 h-5 text-primary-500 transition-colors"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    >
                                                        <rect x="3" y="3" width="18" height="18" rx="3" stroke-width="1.5" fill="currentColor" fill-opacity="0.15"/>
                                                        <polyline points="7,13 10,16 17,8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </div>
                                            @endif
                                            <span class="text-xs text-gray-500">Correct</span>
                                        </label>
                                        <button
                                            type="button"
                                            @click="removeAnswer(question._uid, answer._uid)"
                                            class="text-danger-400 hover:text-danger-600 opacity-0 group-hover:opacity-100"
                                            title="Remove answer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <template x-if="question.answers.length === 0">
                                <p class="text-xs text-gray-400 italic ml-1">No answers yet. Click &quot;+ Add Answer&quot; to add one.</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <template x-if="questions.length === 0">
        <div class="text-center py-8 text-gray-400 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
            <p class="text-sm">No questions yet. Click &quot;Add Question&quot; to create one.</p>
        </div>
    </template>

    @if ($hint)
        <p class="text-xs text-gray-500">{{ $hint }}</p>
    @endif
</div>
