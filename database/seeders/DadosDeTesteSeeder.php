<?php

namespace Database\Seeders;

use App\Models\Arena;
use App\Models\ArenaBusinessHour;
use App\Models\Booking;
use App\Models\CashRegister;
use App\Models\CashRegisterEntry;
use App\Models\Client;
use App\Models\Court;
use App\Models\CourtSport;
use App\Models\Employee;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cenário de TESTE completo para exercitar o sistema ponta a ponta.
 *
 * NÃO entra no `db:seed` padrão (não sujar produção). Rode com:
 *     php artisan db:seed --class=DadosDeTesteSeeder
 *
 * Idempotente: pode rodar várias vezes. Usuários por e-mail (@teste.com),
 * arena por nome, e as reservas do cenário são recriadas do zero a cada run.
 *
 * Todos os logins usam a senha: 12345678
 */
class DadosDeTesteSeeder extends Seeder
{
    private const SENHA = '12345678';

    public function run(): void
    {
        DB::transaction(function () {
            // Garante as formas de pagamento (idempotente).
            (new PaymentMethodSeeder())->run();
            $pix  = PaymentMethod::where('type', 'pix')->first();
            $card = PaymentMethod::where('type', 'card')->first();
            $cash = PaymentMethod::where('type', 'cash')->first();

            // ---- Usuários (um por papel) --------------------------------------
            $donoUser = $this->criarUsuario('Paulo Proprietário', 'dono@teste.com', 'owner');
            $gerUser  = $this->criarUsuario('Gabriela Gerente', 'gerente@teste.com', 'employee');
            $atdUser  = $this->criarUsuario('André Atendente', 'atendente@teste.com', 'employee');
            $cli1User = $this->criarUsuario('Carla Cliente', 'cliente@teste.com', 'client');
            $cli2User = $this->criarUsuario('Caio Cliente', 'cliente2@teste.com', 'client');

            // ---- Perfis -------------------------------------------------------
            $owner = Owner::updateOrCreate(
                ['user_id' => $donoUser->id],
                ['company_name' => 'Empresa Teste Ltda', 'tax_id' => '12345678000199', 'active' => true]
            );
            $cli1 = Client::updateOrCreate(['user_id' => $cli1User->id], ['date_of_birth' => '1995-05-10']);
            $cli2 = Client::updateOrCreate(['user_id' => $cli2User->id], ['date_of_birth' => '1998-11-22']);

            // ---- Arena --------------------------------------------------------
            $arena = Arena::firstOrNew(['owner_id' => $owner->id, 'name' => 'Arena Teste']);
            $arena->fill([
                'description' => 'Arena de demonstração para testes locais.',
                'address_rua' => 'Rua das Quadras', 'address_bairro' => 'Centro', 'address_numero' => '100',
                'phone' => '(11) 3333-4444', 'contact_email' => 'contato@arenateste.com',
                'active' => true,
                'charges_cancellation_fee' => true,
                'cancellation_fee_type' => 'percent', 'cancellation_fee_value' => 20,
                'cancellation_fee_mode' => 'window', 'cancellation_fee_window_hours' => 6,
            ])->save();

            // Formas de pagamento aceitas pela arena.
            $arena->paymentMethods()->sync([$pix->id, $card->id, $cash->id]);

            // Horários: seg–sex 08:00–22:00, sáb/dom 08:00–20:00.
            ArenaBusinessHour::where('arena_id', $arena->id)->delete();
            foreach (range(0, 6) as $dia) {
                $fecha = in_array($dia, [0, 6]) ? '20:00:00' : '22:00:00';
                ArenaBusinessHour::create([
                    'arena_id' => $arena->id, 'day_of_week' => $dia,
                    'opens_at' => '08:00:00', 'closes_at' => $fecha,
                ]);
            }

            // Quadras + esportes.
            $q1 = $this->criarQuadra($arena, 'Quadra 1 — Beach Tennis', 80, ['beach_tennis']);
            $q2 = $this->criarQuadra($arena, 'Quadra 2 — Futsal', 120, ['futsal', 'five_a_side_football']);
            $q3 = $this->criarQuadra($arena, 'Quadra 3 — Vôlei', 100, ['indoor_volleyball', 'beach_volleyball']);

            // ---- Funcionários -------------------------------------------------
            Employee::updateOrCreate(
                ['user_id' => $gerUser->id],
                ['arena_id' => $arena->id, 'created_by' => $donoUser->id, 'position' => 'Gerente', 'access_level' => 'managerial', 'active' => true]
            );
            Employee::updateOrCreate(
                ['user_id' => $atdUser->id],
                ['arena_id' => $arena->id, 'created_by' => $donoUser->id, 'position' => 'Atendente', 'access_level' => 'basic', 'active' => true]
            );

            // ---- Limpa as reservas/pagamentos/caixa do cenário e recria -------
            $courtIds = Court::withTrashed()->where('arena_id', $arena->id)->pluck('id');
            $bookingIds = Booking::whereIn('court_id', $courtIds)->pluck('id');
            Payment::whereIn('booking_id', $bookingIds)->delete();
            CashRegisterEntry::whereIn('booking_id', $bookingIds)->delete();
            Booking::whereIn('court_id', $courtIds)->delete();
            CashRegisterEntry::whereIn('cash_register_id', CashRegister::where('arena_id', $arena->id)->pluck('id'))->delete();
            CashRegister::where('arena_id', $arena->id)->delete();

            $hoje = Carbon::today();

            // A) Confirmada e PAGA (online, cliente 1) — Quadra 1
            $bA = $this->reserva($q1, $cli1, $hoje->copy()->addDays(3), '19:00', '20:00', 80, $pix, 'confirmed');
            $this->pagamento($bA, $pix, 80, 'online');

            // B) PENDENTE (a confirmar, cliente 1) — Quadra 2
            $this->reserva($q2, $cli2, $hoje->copy()->addDays(4), '20:00', '21:00', 120, $card, 'pending');

            // C) Confirmada NÃO PAGA (a pagar, cliente 2) — Quadra 1
            $this->reserva($q1, $cli2, $hoje->copy()->addDays(5), '18:00', '19:00', 80, $pix, 'confirmed');

            // D) REALIZADA e paga (histórico, cliente 2) — Quadra 3
            $bD = $this->reserva($q3, $cli2, $hoje->copy()->subDays(7), '09:00', '10:00', 100, $card, 'completed');
            $this->pagamento($bD, $card, 100, 'online');

            // E) CANCELADA com taxa (cliente 1) — Quadra 2
            $bE = $this->reserva($q2, $cli1, $hoje->copy()->addDays(6), '21:00', '22:00', 120, $pix, 'cancelled');
            $bE->update(['cancelled_by' => $cli1User->id, 'cancelled_at' => now(), 'cancellation_reason' => 'Imprevisto (dados de teste).', 'cancellation_fee_amount' => 24]);

            // F) PRESENCIAL confirmada e paga em dinheiro (registrada pelo atendente) — Quadra 3, hoje
            $bF = $this->reservaPresencial($q3, 'Visitante Balcão', $atdUser, $hoje->copy(), '10:00', '11:00', 100, $cash);
            $this->pagamento($bF, $cash, 100, 'local');

            // ---- Caixa aberto do dia -----------------------------------------
            $caixa = CashRegister::create([
                'arena_id' => $arena->id, 'user_id' => $atdUser->id,
                'opened_at' => now(), 'opening_balance' => 100, 'status' => 'open',
            ]);
            CashRegisterEntry::create([
                'cash_register_id' => $caixa->id, 'booking_id' => $bF->id, 'type' => 'income',
                'amount' => 100, 'description' => 'Reserva presencial (Quadra 3)', 'created_by' => $atdUser->id,
            ]);
        });
    }

    private function criarUsuario(string $nome, string $email, string $tipo): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $nome,
                'password_hash' => self::SENHA,   // cast "hashed" gera o bcrypt
                'type' => $tipo,
                'active' => true,
                'phone' => '(11) 90000-0000',
                'terms_accepted_at' => now(),
                'email_verified_at' => now(),     // já verificado (testa mesmo com verificação ligada)
            ]
        );
    }

    private function criarQuadra(Arena $arena, string $nome, float $valor, array $esportes): Court
    {
        $court = Court::firstOrNew(['arena_id' => $arena->id, 'name' => $nome]);
        $court->fill(['hourly_rate' => $valor, 'active' => true, 'description' => null])->save();
        CourtSport::where('court_id', $court->id)->delete();
        foreach ($esportes as $e) {
            CourtSport::create(['court_id' => $court->id, 'sport' => $e]);
        }
        return $court;
    }

    private function reserva(Court $court, Client $cliente, Carbon $data, string $ini, string $fim, float $total, PaymentMethod $forma, string $status): Booking
    {
        return Booking::create([
            'court_id' => $court->id, 'client_id' => $cliente->id, 'origin' => 'site',
            'date' => $data->toDateString(), 'start_time' => $ini, 'end_time' => $fim,
            'total_amount' => $total, 'payment_method_id' => $forma->id, 'status' => $status,
        ]);
    }

    private function reservaPresencial(Court $court, string $nome, User $operador, Carbon $data, string $ini, string $fim, float $total, PaymentMethod $forma): Booking
    {
        return Booking::create([
            'court_id' => $court->id, 'client_id' => null, 'guest_name' => $nome,
            'created_by' => $operador->id, 'origin' => 'presencial',
            'date' => $data->toDateString(), 'start_time' => $ini, 'end_time' => $fim,
            'total_amount' => $total, 'payment_method_id' => $forma->id, 'status' => 'confirmed',
        ]);
    }

    private function pagamento(Booking $booking, PaymentMethod $forma, float $valor, string $origem): Payment
    {
        return Payment::create([
            'booking_id' => $booking->id, 'payment_method_id' => $forma->id,
            'amount' => $valor, 'status' => 'paid', 'origin' => $origem, 'paid_at' => now(),
        ]);
    }
}
