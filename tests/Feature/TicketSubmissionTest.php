<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeTicketJob;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TicketSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Queue::fake(); // capture dispatched jobs instead of running them
    }

    public function test_form_page_loads(): void
    {
        $this->get(route('tickets.create'))->assertOk()->assertSee('Submit a support ticket');
    }

    public function test_valid_ticket_is_saved_and_queued_for_analysis(): void
    {
        $response = $this->post(route('tickets.store'), [
            'customer_email' => 'jane@example.com',
            'subject' => 'Cannot export report',
            'body' => 'The export button does nothing when I click it.',
        ]);

        $ticket = Ticket::sole();
        $response->assertRedirect(route('tickets.show', $ticket))->assertSessionHas('success');
        $this->assertSame('new', $ticket->status);
        $this->assertNull($ticket->category); // no AI work happened during the request

        Queue::assertPushed(AnalyzeTicketJob::class, fn ($job) => $job->ticket->is($ticket));
    }

    public function test_invalid_ticket_is_rejected(): void
    {
        $this->post(route('tickets.store'), ['customer_email' => 'not-an-email', 'subject' => '', 'body' => 'short'])
            ->assertSessionHasErrors(['customer_email', 'subject', 'body']);

        $this->assertSame(0, Ticket::count());
        Queue::assertNothingPushed();
    }

    public function test_error_messages_use_form_labels(): void
    {
        $this->post(route('tickets.store'), ['customer_email' => 'x@example.com', 'subject' => 'Hello', 'body' => 'short'])
            ->assertSessionHasErrors(['body' => 'The problem description field must be at least 10 characters.']);
    }

    public function test_live_tickets_go_on_the_default_queue_and_backfill_on_its_own(): void
    {
        Ticket::create(['subject' => 'Seeded', 'body' => 'Seeded ticket', 'customer_email' => 's@example.com', 'status' => 'new']);
        $this->artisan('tickets:queue-pending')->assertSuccessful();

        $this->post(route('tickets.store'), ['customer_email' => 'live@example.com', 'subject' => 'Live one', 'body' => 'Submitted by a real customer.']);

        Queue::assertPushedOn('backfill', AnalyzeTicketJob::class, fn ($job) => $job->ticket->subject === 'Seeded');
        Queue::assertPushed(AnalyzeTicketJob::class, fn ($job) => $job->ticket->subject === 'Live one' && $job->queue === null);
    }
}
