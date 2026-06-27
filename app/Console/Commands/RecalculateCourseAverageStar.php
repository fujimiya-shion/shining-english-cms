<?php

namespace App\Console\Commands;

use App\Models\Course;
use Illuminate\Console\Command;

class RecalculateCourseAverageStar extends Command
{
    protected $signature = 'app:recalculate-course-average-star';

    protected $description = 'Recalculate average star rating for all courses based on their reviews';

    public function handle(): void
    {
        $courses = Course::query()
            ->whereHas('reviews')
            ->withAvg('reviews as avg_rating', 'rating')
            ->get(['id', 'name']);

        if ($courses->isEmpty()) {
            $this->info('No courses with reviews found.');

            return;
        }

        $bar = $this->output->createProgressBar($courses->count());
        $bar->start();

        $updated = 0;

        foreach ($courses as $course) {
            $average = round((float) $course->avg_rating, 1);

            Course::withoutTimestamps(fn () => Course::where('id', $course->id)->update(['rating' => $average]));

            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Recalculated average star rating for {$updated} course(s).");
    }
}
