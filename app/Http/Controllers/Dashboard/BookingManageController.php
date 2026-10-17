<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BookingController;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\MahjongTable;
use App\Models\Transaction;
use App\Services\BookingOrderService;
use App\Services\TableDeviceService;
use Illuminate\Http\Request;

class BookingManageController extends Controller
{
    public function __construct(
        private TableDeviceService $device,
        private BookingOrderService $orders,
    ) {
    }

    public function index(Request $request)
    {
        $query = Booking::with(['table', 'order'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('date')) {
            $query->whereDate('booking_date', $request->date);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', "%{$request->search}%")
                  ->orWhere('customer_phone', 'like', "%{$request->search}%")
                  ->orWhere('booking_code', 'like', "%{$request->search}%");
            });
        }

        $bookings = $query->paginate(20)->withQueryString();

        return view('dashboard.bookings', compact('bookings'));
    }

    public function create()
    {
        $tables = MahjongTable::with('pricing')->orderBy('id')->get();

        return view('dashboard.bookings-create', [
            'tables'    => $tables,
            'sources'   => array_diff_key(BookingOrder::SOURCES, ['customer' => true]),
            'maxTables' => BookingController::MAX_TABLES_PER_ORDER,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'source'                 => 'required|in:owner,cash,block',
            'customer_name'          => 'nullable|string|max:100',
            'customer_phone'         => 'nullable|digits_between:9,13',
            'notes'                  => 'nullable|string|max:255',
            'booking_date'           => 'required|date|after_or_equal:today',
            'lines'                  => 'required|array|min:1|max:' . BookingController::MAX_TABLES_PER_ORDER,
            'lines.*.table_id'       => 'required|integer|distinct|exists:mahjong_tables,id',
            'lines.*.start_time'     => 'required|date_format:H:i',
            'lines.*.duration_hours' => 'required|integer|min:1|max:' . Booking::MAX_DURATION_HOURS,
        ], [
            'lines.*.table_id.distinct' => 'Meja yang sama dipilih lebih dari sekali.',
        ]);

        $data['customer_name'] = ($data['customer_name'] ?? null) ?: match ($data['source']) {
            'owner' => 'Owner',
            'cash'  => 'Pelanggan Tunai',
            'block' => 'Jadwal Diblokir',
        };

        $result = $this->orders->create($data, $data['lines'], $data['source'], allowPastStart: true);

        if (is_array($result)) {
            return back()->withInput()->withErrors(
                collect($result)->mapWithKeys(fn ($message, $index) => ["lines.{$index}.start_time" => $message])->all()
                + ['lines' => 'Ada meja yang bentrok. Ganti jam atau hapus meja yang ditandai.']
            );
        }

        return redirect()->route('dashboard.bookings')
            ->with('success', "Booking {$result->source_label} {$result->code} berhasil dibuat.");
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => 'required|in:active,done,cancelled',
        ]);

        $oldStatus = $booking->status;

        $updates = ['status' => $request->status];
        if ($request->status === 'cancelled') {
            $updates['refund_status'] = $booking->determineRefundStatus();
            $updates['cancelled_at']  = now();
        }
        $booking->update($updates);

        if ($request->status === 'active' && $oldStatus !== 'active') {
            $this->device->activate($booking->table, $booking);
        }

        $isRunningNow = now()->between($booking->startsAt(), $booking->endsAt());
        if (in_array($request->status, ['done', 'cancelled']) && $oldStatus === 'active' && $booking->source !== 'block' && $isRunningNow) {
            $this->device->deactivate($booking->table, $booking);
        }

        if ($request->status === 'done' && !$booking->transaction && $booking->source !== 'block') {
            Transaction::create([
                'booking_id'     => $booking->id,
                'amount'         => $booking->total_price,
                'payment_method' => 'cash',
                'paid_at'        => now(),
                'notes'          => 'Pembayaran manual oleh admin',
            ]);
        }

        if ($booking->order) {
            $this->orders->syncOrderStatus($booking->order);
        }

        return back()->with('success', "Status booking #{$booking->booking_code} berhasil diperbarui.");
    }
}
