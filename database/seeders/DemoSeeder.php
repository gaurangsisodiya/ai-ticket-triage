<?php

namespace Database\Seeders;

use App\Models\AiLog;
use App\Models\Category;
use App\Models\KbArticle;
use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Base class for a demo dataset (one product domain).
 * Child classes only provide data. This class wipes the previous demo and inserts the
 * new one, so you can switch domains with:  php artisan db:seed --class=GovTechDemoSeeder
 */
abstract class DemoSeeder extends Seeder
{
    /** @return array<string, string>  team name => description */
    abstract protected function teams(): array;

    /** @return array<string, array{0: string, 1: string, 2: string}>  slug => [name, description, team name] */
    abstract protected function categories(): array;

    /** @return array<int, array{slug: string, title: string, content: string}> */
    abstract protected function kbArticles(): array;

    /** @return array<int, array{0: string, 1: string, 2: string}>  [subject, body, customer email] */
    abstract protected function tickets(): array;

    public function run(): void
    {
        $this->wipeDemoData();

        $teamIds = [];
        foreach ($this->teams() as $name => $description) {
            $teamIds[$name] = Team::create(compact('name', 'description'))->id;
        }

        foreach ($this->categories() as $slug => [$name, $description, $teamName]) {
            Category::create([
                'slug' => $slug,
                'name' => $name,
                'description' => $description,
                'team_id' => $teamIds[$teamName], // fails loudly on a typo in the team name, which is what we want
            ]);
        }

        foreach ($this->kbArticles() as $article) {
            KbArticle::create($article);
        }

        // Tickets start unanalyzed. The AI fills in category, urgency, team, etc.
        foreach ($this->tickets() as [$subject, $body, $email]) {
            Ticket::create([
                'subject' => $subject,
                'body' => $body,
                'customer_email' => $email,
                'status' => 'new',
            ]);
        }
    }

    private function wipeDemoData(): void
    {
        // TRUNCATE refuses to run on tables referenced by foreign keys, so turn the checks off briefly.
        Schema::disableForeignKeyConstraints();

        AiLog::truncate();
        Ticket::truncate();
        KbArticle::truncate();
        Category::truncate();
        Team::truncate();
        // Queued analysis jobs would point to tickets that no longer exist.
        DB::table('jobs')->truncate();

        Schema::enableForeignKeyConstraints();
    }
}
