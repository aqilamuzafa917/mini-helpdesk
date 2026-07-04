<?php

namespace Database\Seeders;

use App\Enums\ClientStatus;
use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin user
        $admin = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => Role::Admin,
            'is_active' => true,
        ]);

        // 2. Create Engineer users
        $engineer1 = User::factory()->create([
            'name' => 'Engineer One',
            'email' => 'engineer1@example.com',
            'password' => Hash::make('password123'),
            'role' => Role::Engineer,
            'is_active' => true,
        ]);

        $engineer2 = User::factory()->create([
            'name' => 'Engineer Two',
            'email' => 'engineer2@example.com',
            'password' => Hash::make('password123'),
            'role' => Role::Engineer,
            'is_active' => true,
        ]);

        // 3. Create Clients
        $clientCompany1 = Client::factory()->create([
            'name' => 'Diskominfo Klungkung',
            'status' => ClientStatus::Active,
        ]);

        $clientCompany2 = Client::factory()->create([
            'name' => 'PT Example Manufacturing',
            'status' => ClientStatus::Active,
        ]);

        // 4. Create Client Users
        $clientUser1 = User::factory()->create([
            'name' => 'Client One User',
            'email' => 'client1@example.com',
            'password' => Hash::make('password123'),
            'role' => Role::Client,
            'client_id' => $clientCompany1->id,
            'is_active' => true,
        ]);

        $clientUser2 = User::factory()->create([
            'name' => 'Client Two User',
            'email' => 'client2@example.com',
            'password' => Hash::make('password123'),
            'role' => Role::Client,
            'client_id' => $clientCompany2->id,
            'is_active' => true,
        ]);

        // 5. Create 10+ tickets distributed across both clients and engineers.
        // Needs at least one for each TicketStatus (Open, InProgress, Resolved, Closed)
        // Needs at least one for each Priority (Low, Medium, High)

        // Ticket 1: Open, High, Client 1, Engineer 1
        $t1 = Ticket::factory()->create([
            'client_id' => $clientCompany1->id,
            'created_by' => $clientUser1->id,
            'assigned_engineer_id' => $engineer1->id,
            'title' => 'Server Network Intermittent Down',
            'priority' => Priority::High,
            'status' => TicketStatus::Open,
        ]);

        // Ticket 2: InProgress, Medium, Client 1, Engineer 2
        $t2 = Ticket::factory()->create([
            'client_id' => $clientCompany1->id,
            'created_by' => $clientUser1->id,
            'assigned_engineer_id' => $engineer2->id,
            'title' => 'Email client sync issues on Outlook',
            'priority' => Priority::Medium,
            'status' => TicketStatus::InProgress,
        ]);

        // Ticket 3: Resolved, Low, Client 2, Engineer 1
        $t3 = Ticket::factory()->create([
            'client_id' => $clientCompany2->id,
            'created_by' => $clientUser2->id,
            'assigned_engineer_id' => $engineer1->id,
            'title' => 'Printer setup for HR department',
            'priority' => Priority::Low,
            'status' => TicketStatus::Resolved,
        ]);

        // Ticket 4: Closed, High, Client 2, Engineer 2
        $t4 = Ticket::factory()->create([
            'client_id' => $clientCompany2->id,
            'created_by' => $clientUser2->id,
            'assigned_engineer_id' => $engineer2->id,
            'title' => 'Database replication lag on Production',
            'priority' => Priority::High,
            'status' => TicketStatus::Closed,
        ]);

        // Remaining 6 tickets to reach at least 10 tickets
        Ticket::factory()->create([
            'client_id' => $clientCompany1->id,
            'created_by' => $clientUser1->id,
            'assigned_engineer_id' => $engineer1->id,
            'title' => 'VPN Connection Drops',
            'priority' => Priority::Medium,
            'status' => TicketStatus::Open,
        ]);

        Ticket::factory()->create([
            'client_id' => $clientCompany1->id,
            'created_by' => $clientUser1->id,
            'assigned_engineer_id' => $engineer2->id,
            'title' => 'Software installation request',
            'priority' => Priority::Low,
            'status' => TicketStatus::Open,
        ]);

        Ticket::factory()->create([
            'client_id' => $clientCompany2->id,
            'created_by' => $clientUser2->id,
            'assigned_engineer_id' => $engineer1->id,
            'title' => 'New workstation provisioning',
            'priority' => Priority::Medium,
            'status' => TicketStatus::InProgress,
        ]);

        Ticket::factory()->create([
            'client_id' => $clientCompany2->id,
            'created_by' => $clientUser2->id,
            'assigned_engineer_id' => $engineer2->id,
            'title' => 'ERP access permissions update',
            'priority' => Priority::Low,
            'status' => TicketStatus::InProgress,
        ]);

        Ticket::factory()->create([
            'client_id' => $clientCompany1->id,
            'created_by' => $clientUser1->id,
            'assigned_engineer_id' => $engineer1->id,
            'title' => 'Backup verification check',
            'priority' => Priority::High,
            'status' => TicketStatus::Resolved,
        ]);

        Ticket::factory()->create([
            'client_id' => $clientCompany2->id,
            'created_by' => $clientUser2->id,
            'assigned_engineer_id' => $engineer2->id,
            'title' => 'Legacy server decommissioning',
            'priority' => Priority::Low,
            'status' => TicketStatus::Closed,
        ]);

        // 6. Create at least one public comment and at least one internal comment on each of at least 3 distinct seeded tickets.
        // We will comment on $t1, $t2, and $t3.

        // Ticket 1 comments
        TicketComment::factory()->create([
            'ticket_id' => $t1->id,
            'user_id' => $engineer1->id,
            'comment' => 'This is a public comment from the engineer. We are checking the gateway switch.',
            'is_internal' => false,
        ]);
        TicketComment::factory()->create([
            'ticket_id' => $t1->id,
            'user_id' => $admin->id,
            'comment' => 'This is an internal comment. ISP confirmed no outages, checking internal wiring.',
            'is_internal' => true,
        ]);

        // Ticket 2 comments
        TicketComment::factory()->create([
            'ticket_id' => $t2->id,
            'user_id' => $clientUser1->id,
            'comment' => 'This is a public comment from the client. Outlook hangs every time I open it.',
            'is_internal' => false,
        ]);
        TicketComment::factory()->create([
            'ticket_id' => $t2->id,
            'user_id' => $engineer2->id,
            'comment' => 'This is an internal comment. Might need to recreate the Outlook profile.',
            'is_internal' => true,
        ]);

        // Ticket 3 comments
        TicketComment::factory()->create([
            'ticket_id' => $t3->id,
            'user_id' => $engineer1->id,
            'comment' => 'This is a public comment. The printer driver has been reinstalled and test page printed successfully.',
            'is_internal' => false,
        ]);
        TicketComment::factory()->create([
            'ticket_id' => $t3->id,
            'user_id' => $admin->id,
            'comment' => 'This is an internal comment. Confirmed printer works on local subnet.',
            'is_internal' => true,
        ]);
    }
}
