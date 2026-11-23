<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $label  = (string) $request->query('label', '');
        $status = (string) $request->query('status', '');

        $vouchers = Voucher::with('order')
            ->when($label !== '', fn ($q) => $q->where('label', $label))
            ->latest('id')
            ->get();

        if (array_key_exists($status, Voucher::STATUSES)) {
            $vouchers = $vouchers->filter(fn (Voucher $v) => $v->status() === $status)->values();
        }

        return view('dashboard.vouchers.index', [
            'vouchers'  => $vouchers,
            'labels'    => Voucher::select('label')->distinct()->orderBy('label')->pluck('label'),
            'label'     => $label,
            'status'    => $status,
            'available' => $vouchers->filter(fn (Voucher $v) => $v->status() === 'available')->pluck('code'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label'          => 'required|string|max:60',
            'count'          => 'required|integer|min:1|max:' . Voucher::MAX_PER_BATCH,
            'discount_type'  => ['required', Rule::in([Voucher::TYPE_AMOUNT, Voucher::TYPE_PERCENT])],
            'discount_value' => ['required', 'integer', 'min:1', Rule::when($request->input('discount_type') === Voucher::TYPE_PERCENT, 'max:100', 'max:100000000')],
            'max_discount'   => 'nullable|integer|min:1|max:100000000',
            'expires_on'     => 'nullable|date|after_or_equal:today',
        ], [
            'discount_value.max' => 'Persen maksimal 100.',
        ]);

        $label = trim($data['label']);

        DB::transaction(function () use ($data, $label, $request) {
            for ($i = 0; $i < (int) $data['count']; $i++) {
                Voucher::create([
                    'code'           => Voucher::generateCode(),
                    'label'          => $label,
                    'discount_type'  => $data['discount_type'],
                    'discount_value' => (int) $data['discount_value'],
                    'max_discount'   => $data['discount_type'] === Voucher::TYPE_PERCENT ? ($data['max_discount'] ?? null) : null,
                    'expires_on'     => $data['expires_on'] ?? null,
                    'created_by'     => $request->user()->id,
                ]);
            }
        });

        $sample = Voucher::where('label', $label)->latest('id')->first();
        ActivityLog::record($request->user(), 'voucher_create', detail: "{$data['count']} voucher \"{$label}\" ({$sample->discount_label})");

        return redirect()->route('dashboard.vouchers.index', ['label' => $label, 'status' => 'available'])
            ->with('success', "{$data['count']} kode voucher \"{$label}\" berhasil dibuat.");
    }

    public function destroy(Request $request, Voucher $voucher): RedirectResponse
    {
        if (in_array($voucher->status(), ['used', 'held'], true)) {
            return back()->with('error', "Voucher {$voucher->code} sudah dipakai atau sedang menunggu pembayaran, jadi tidak bisa dihapus.");
        }

        $voucher->delete();
        ActivityLog::record($request->user(), 'voucher_delete', detail: "{$voucher->code} ({$voucher->label})");

        return back()->with('success', "Voucher {$voucher->code} dihapus.");
    }
}
