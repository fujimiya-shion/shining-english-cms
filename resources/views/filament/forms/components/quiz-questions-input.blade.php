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
        _tick: 0,
        _keylock: 0,

        init() {
            this._root = this.$el;
            let raw = this._root.getAttribute('data-questions');
            this.questions.splice(0, this.questions.length, ...(raw ? JSON.parse(raw) : []));
            this.uidCounter = 0;
            this.questions.forEach(q => {
                this.uidCounter++;
                q._uid = q._uid || 'q_' + this.uidCounter;
                (q.answers || []).forEach(a => {
                    this.uidCounter++;
                    a._uid = a._uid || 'a_' + this.uidCounter;
                });
            });
            this.statePath = this._root.getAttribute('data-path') || '';
            setTimeout(() => this.initSortable(), 500);
        },

        initSortable() {
            this.initQuestionSortable();
            this.initAllAnswerSortables();
        },

        revertSortableMove(evt) {
            let itemEl = evt.item;
            if (evt.oldIndex !== evt.newIndex) {
                let parent = itemEl.parentNode;
                let sibling = parent.children[evt.oldIndex > evt.newIndex ? evt.oldIndex + 1 : evt.oldIndex];
                parent.insertBefore(itemEl, sibling);
            }
        },

        initQuestionSortable() {
            let questionsEl = this._root.querySelector('[data-sortable-questions]');
            if (!questionsEl) return;
            if (questionsEl.sortable) questionsEl.sortable.destroy();
            window.Sortable.create(questionsEl, {
                handle: '[data-drag-handle]',
                animation: 150,
                onEnd: (evt) => {
                    let raw = Alpine.raw(this.questions);
                    let [moved] = raw.splice(evt.oldIndex, 1);
                    raw.splice(evt.newIndex, 0, moved);
                    raw.forEach((q, i) => q.sort_order = i);
                    this.$nextTick(() => this.sync());
                },
            });
        },

        initAnswerSortable(questionUid) {
            let container = this._root.querySelector(`[data-sortable-answers][data-question-uid='${questionUid}']`);
            if (!container) {
                let qEl = this._root.querySelector(`[data-uid='${questionUid}']`);
                if (qEl) container = qEl.closest('.rounded-lg')?.querySelector('[data-sortable-answers]');
            }
            if (!container) return;
            if (container.sortable) container.sortable.destroy();
            window.Sortable.create(container, {
                handle: '[data-drag-handle-answer]',
                animation: 150,
                onEnd: (evt) => {
                    let foundQ = this.questions.find(item => item._uid === questionUid);
                    if (!foundQ) return;
                    let raw = Alpine.raw(foundQ.answers);
                    let [moved] = raw.splice(evt.oldIndex, 1);
                    raw.splice(evt.newIndex, 0, moved);
                    raw.forEach((a, i) => a.sort_order = i);
                    this.$nextTick(() => this.sync());
                },
            });
        },

        initAllAnswerSortables() {
            this._root.querySelectorAll('[data-sortable-answers]').forEach(el => {
                let questionUid = el.getAttribute('data-question-uid');
                if (!questionUid) {
                    let qEl = el.closest('.rounded-lg');
                    if (qEl) questionUid = qEl.querySelector('textarea')?.getAttribute('data-uid');
                }
                if (!questionUid) return;
                this.initAnswerSortable(questionUid);
            });
        },

        addQuestion(focusNew, event) {
            if (event && event.shiftKey) {
                this._keylock = 0;
                let qUid = event.target.getAttribute('data-uid');
                if (qUid) { this.addAnswer(qUid, true); return; }
            }
            if (Date.now() - this._keylock < 300) return;
            this._keylock = Date.now();
            this.uidCounter++;
            let newUid = 'q_' + this.uidCounter;
            this.questions.splice(this.questions.length, 0, {
                _uid: newUid,
                id: null,
                content: '',
                sort_order: this.questions.length,
                answers: [],
            });
            this.$nextTick(() => {
                this.initQuestionSortable();
                this.initAllAnswerSortables();
                this.initAnswerSortable(newUid);
                this.sync();
            });
            if (focusNew && this._root) {
                setTimeout(() => {
                    let el = this._root.querySelector(`[data-uid='${newUid}']`);
                    if (!el) el = this._root.querySelector('textarea');
                    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus(); }
                }, 100);
            }
        },

        removeQuestion(uid) {
            this.questions = this.questions.filter(q => q._uid !== uid);
            this.updateSortOrders();
            this.sync();
        },

        addAnswer(questionUid, shouldFocus) {
            if (Date.now() - this._keylock < 300) return;
            this._keylock = Date.now();
            this.uidCounter++;
            let newUid = 'a_' + this.uidCounter;
            let q = this.questions.find(item => item._uid === questionUid);
            if (!q) return;
            q.answers.splice(q.answers.length, 0, {
                _uid: newUid,
                id: null,
                content: '',
                is_correct: false,
                sort_order: q.answers.length,
            });
            this._tick++;
            this.$nextTick(() => {
                this.initAnswerSortable(questionUid);
                this.sync();
            });
            if (shouldFocus && this._root) {
                this.$nextTick(() => {
                    let el = this._root.querySelector(`[data-uid='${newUid}']`);
                    if (!el) {
                        let qEl = this._root.querySelector(`[data-uid='${questionUid}']`);
                        if (qEl) {
                            let container = qEl.closest('.rounded-lg')?.querySelector('[data-sortable-answers]');
                            if (container) {
                                let inputs = container.querySelectorAll('input');
                                el = inputs[inputs.length - 1];
                            }
                        }
                    }
                    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus(); }
                });
            }
        },

        removeAnswer(questionUid, answerUid) {
            let q = this.questions.find(item => item._uid === questionUid);
            if (!q) return;
            let filtered = q.answers.filter(a => a._uid !== answerUid);
            filtered.forEach((a, i) => a.sort_order = i);
            this.questions = this.questions.map(item =>
                item._uid === questionUid ? {...item, answers: filtered} : item
            );
            this.$nextTick(() => this.sync());
        },

        setCorrectAnswer(questionUid, answerUid) {
            let q = this.questions.find(item => item._uid === questionUid);
            if (!q) return;
            let updated = q.answers.map(a => ({...a, is_correct: a._uid === answerUid}));
            this.questions = this.questions.map(item =>
                item._uid === questionUid ? {...item, answers: updated} : item
            );
            this.$nextTick(() => this.sync());
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
            let payload = this.questions.map(q => ({
                id: q.id,
                content: q.content,
                sort_order: q.sort_order,
                answers: (q.answers || []).map(a => ({
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
            @click="addQuestion(true)"
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
                                    :data-uid="question._uid"
                                    @blur="sync()"
                                    @keydown.enter.ctrl.prevent="addQuestion(true, $event)"
                                    rows="2"
                                    placeholder="Enter question content..."
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                ></textarea>
                                <p class="text-[10px] text-gray-400 mt-0.5">Ctrl+Enter: add question | Ctrl+Shift+Enter: add answer to this question</p>
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
                                            :data-uid="answer._uid"
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