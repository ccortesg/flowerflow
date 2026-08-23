<?php

namespace App\Http\Controllers\Panel;

use App\Actions\ForceCommunicationDelivery;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForceCommunicationDeliveryRequest;
use App\Models\CommunicationDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommunicationDeliveryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CommunicationDelivery::class);
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(CommunicationDeliveryStatus::class)],
            'type' => ['nullable', Rule::enum(CommunicationType::class)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'requires_attention' => ['nullable', 'boolean'],
        ]);
        $timezone = (string) config('flowerflow.timezone');
        $stalledBefore = now('UTC')->subMinutes((int) config('flowerflow.communication_ledger.stalled_after_minutes'));

        $deliveries = CommunicationDelivery::query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('notification_type', $type))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('created_at', '>=', CarbonImmutable::parse($from, $timezone)->startOfDay()->utc()))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('created_at', '<=', CarbonImmutable::parse($to, $timezone)->endOfDay()->utc()))
            ->when($request->boolean('requires_attention'), function ($query) use ($stalledBefore): void {
                $query->where(function ($attention) use ($stalledBefore): void {
                    $attention->whereIn('status', [CommunicationDeliveryStatus::Failed->value, CommunicationDeliveryStatus::Unknown->value])
                        ->orWhere(function ($stalled) use ($stalledBefore): void {
                            $stalled->where('status', CommunicationDeliveryStatus::Queued->value)
                                ->where('queued_at', '<=', $stalledBefore);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('panel.communication-deliveries.index', [
            'deliveries' => $deliveries,
            'statuses' => CommunicationDeliveryStatus::cases(),
            'types' => CommunicationType::cases(),
            'stalledBefore' => $stalledBefore,
        ]);
    }

    public function show(CommunicationDelivery $communicationDelivery): View
    {
        Gate::authorize('view', $communicationDelivery);

        return view('panel.communication-deliveries.show', [
            'delivery' => $communicationDelivery->load(['attempts' => fn ($query) => $query->orderBy('attempt_number')]),
        ]);
    }

    public function process(CommunicationDelivery $communicationDelivery): View
    {
        Gate::authorize('manage', $communicationDelivery);
        abort_unless($communicationDelivery->status->canBeForced(), 409, 'La comunicación ya no admite esta acción.');

        return view('panel.communication-deliveries.process', ['delivery' => $communicationDelivery]);
    }

    public function store(
        ForceCommunicationDeliveryRequest $request,
        CommunicationDelivery $communicationDelivery,
        ForceCommunicationDelivery $action,
    ): RedirectResponse {
        $delivery = $action->execute(
            $communicationDelivery,
            $request->user(),
            $request->integer('lock_version'),
            $request->string('reason')->toString(),
            $request->boolean('duplicate_risk_acknowledged'),
        );

        return redirect()->route('panel.communication-deliveries.show', $delivery)
            ->with('status', 'La comunicación quedó programada en la cola prioritaria.');
    }
}
