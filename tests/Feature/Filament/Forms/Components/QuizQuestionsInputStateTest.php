<?php

use App\Filament\Forms\Components\QuizQuestionsInput;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

it('handles string state hydration', function (): void {
    $host = new class extends Component implements HasSchemas
    {
        public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver
        {
            return null;
        }

        public function getOldSchemaState(string $statePath): mixed
        {
            return null;
        }

        public function getSchemaComponent(string $key, bool $withHidden = false, array $skipComponentsChildContainersWhileSearching = []): \Filament\Schemas\Components\Component|\Filament\Actions\Action|\Filament\Actions\ActionGroup|null
        {
            return null;
        }

        public function getSchema(string $name): ?Schema
        {
            return null;
        }

        public function currentlyValidatingSchema(?Schema $schema): void {}

        public function getDefaultTestingSchemaName(): ?string
        {
            return null;
        }
    };

    $schema = Schema::make($host);
    $component = QuizQuestionsInput::make('questions');
    $schema->components([$component]);

    $component->state('[{"content":"Q1","answers":[{"content":"A1","is_correct":true}]}]');
    $questions = $component->getQuestionsFromState();
    expect($questions)->toHaveCount(1);
    expect($questions[0]['content'])->toBe('Q1');
});

it('handles array state hydration', function (): void {
    $host = new class extends Component implements HasSchemas
    {
        public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver
        {
            return null;
        }

        public function getOldSchemaState(string $statePath): mixed
        {
            return null;
        }

        public function getSchemaComponent(string $key, bool $withHidden = false, array $skipComponentsChildContainersWhileSearching = []): \Filament\Schemas\Components\Component|\Filament\Actions\Action|\Filament\Actions\ActionGroup|null
        {
            return null;
        }

        public function getSchema(string $name): ?Schema
        {
            return null;
        }

        public function currentlyValidatingSchema(?Schema $schema): void {}

        public function getDefaultTestingSchemaName(): ?string
        {
            return null;
        }
    };

    $schema = Schema::make($host);
    $component = QuizQuestionsInput::make('questions');
    $schema->components([$component]);

    $component->state([['content' => 'test']]);
    $questions = $component->getQuestionsFromState();
    expect($questions)->toHaveCount(1);
});
