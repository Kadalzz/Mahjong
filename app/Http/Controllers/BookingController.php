<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingOrder;
use App\Models\MahjongTable;
use App\Services\BookingOrderService;
use App\Services\DokuService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public const MAX_TABLES_PER_ORDER = 6;

    public function __construct(
        private DokuService $doku,
        private BookingOrderService $orders,
    ) {
    }

    public function index()
    {
        $tables = MahjongTable::with('pricing')
            ->where('status', '!=', 'maintenance')
            ->get();

        return view('booking.index', compact('tables'));
    }

    public function create(MahjongTable $table)
    {
        if ($table->status === 'maintenance') {
            return redirect()->route('booking.index')
                ->with('error', 'Meja ini sedang dalam maintenance.');
        }

        $tables = MahjongTable::with('pricing')
            ->where('status', '!=', 'maintenance')
            ->orderBy('id')
            ->get();

        return view('booking.create', [
            'table'     => $table,
            'tables'    => $tables,
            'maxTables' => self::MAX_TABLES_PER_ORDER,
        ]);
    }

    public function checkAvailability(Request $request, MahjongTable $table)
    {
        $request->validate([
            'booking_date'   => 'required|date|after_or_equal:today',
            'start_time'     => 'nullable|date_format:H:i',
            'duration_hours' => 'required|integer|min:1|max:8',
        ]);

        $busy = $table->busySlotsOn($request->booking_date);

        if (!$request->filled('start_time')) {
            return response()->json(['available' => false, 'reason' => 'incomplete', 'busy' => $busy]);
        }

        $durationHours = (int) $request->duration_hours;
        $allowPast     = $request->boolean('allow_past') && $request->user()?->isAdmin();
        $problem       = $this->orders->slotProblem($table, $request->booking_date, $request->start_time, $durationHours, $allowPast);
        $price         = $table->getCurrentPricePerHour() * $durationHours;

        return response()->json([
            'available'       => $problem === null,
            'reason'          => $problem['reason'] ?? null,
            'message'         => $problem['message'] ?? null,
            'end_time'        => Booking::computeEndTime($request->start_time, $durationHours),
            'ends_next_day'   => !Booking::startDateTime($request->booking_date, $request->start_time)
                ->addHours($durationHours)
                ->isSameDay(Carbon::parse($request->booking_date)),
            'price'           => $price,
            'price_formatted' => 'Rp ' . number_format($price, 0, ',', '.'),
            'busy'            => $busy,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'          => 'required|string|max:100',
            'customer_phone'         => 'required|digits_between:9,13',
            'booking_date'           => 'required|date|after_or_equal:today',
            'lines'                  => 'required|array|min:1|max:' . self::MAX_TABLES_PER_ORDER,
            'lines.*.table_id'       => 'required|integer|distinct|exists:mahjong_tables,id',
            'lines.*.start_time'     => 'required|date_format:H:i',
            'lines.*.duration_hours' => 'required|integer|min:1|max:8',
        ], [
            'lines.*.table_id.distinct' => 'Meja yang sama dipilih lebih dari sekali.',
        ]);

        $result = $this->orders->create($data, $data['lines'], 'customer');

        if (is_array($result)) {
            return back()->withInput()->withErrors(
                collect($result)->mapWithKeys(fn ($message, $index) => ["lines.{$index}.start_time" => $message])->all()
                + ['lines' => 'Ada meja yang bentrok. Ganti jam atau hapus meja yang ditandai, lalu coba lagi.']
            );
        }

        $order   = $result;
        $invoice = $this->doku->createInvoice($order, $order->code . '-' . time());

        if (!$invoice) {
            $this->orders->cancelUnpaid($order);

            return back()->withInput()
                ->with('error', 'Pembayaran sedang tidak bisa dibuat. Silakan coba lagi sebentar.');
        }

        $order->update([
            'payment_order_id' => $invoice['order_id'],
            'payment_url'      => $invoice['invoice_url'],
        ]);

        return redirect()->route('booking.confirm', $order->code);
    }

    public function confirm(string $code)
    {
        $order = BookingOrder::with('bookings.table')->where('code', $code)->firstOrFail();
        return view('booking.confirm', compact('order'));
    }

    public function status(string $code)
    {
        $order = BookingOrder::where('code', $code)->firstOrFail();

        return response()->json([
            'status'          => $order->status,
            'payment_expired' => $order->isPaymentExpired(),
        ]);
    }

    public function invoice(string $code)
    {
        $order = BookingOrder::with(['bookings.table', 'bookings.transaction'])->where('code', $code)->firstOrFail();

        if (!$order->isPaid()) {
            abort(404);
        }

        return view('booking.invoice', compact('order'));
    }

    public function lookup(Request $request)
    {
        $orders   = collect();
        $searched = $request->filled('q');

        if ($searched) {
            $query  = trim($request->q);
            $digits = preg_replace('/\D/', '', $query);
            $base   = BookingOrder::with('bookings.table')->where('source', 'customer');

            if (preg_match('/^MJG-[A-Z0-9]+$/i', $query)) {
                $orders = $base->where('code', strtoupper($query))->get();
            } elseif (strlen($digits) >= 6 && strlen($digits) >= strlen(preg_replace('/[\s\-\+]/', '', $query))) {
                $orders = $base->where('customer_phone', 'like', '%' . substr($digits, -9))
                    ->latest()->limit(20)->get();
            } else {
                $orders = $base->whereRaw('LOWER(customer_name) LIKE ?', ['%' . strtolower($query) . '%'])
                    ->latest()->limit(20)->get();
            }
        }

        return view('booking.lookup', compact('orders', 'searched'));
    }

    public function webhook(Request $request)
    {
        if (!$this->doku->verifyWebhookSignature($request)) {
            Log::warning('DOKU webhook rejected: invalid signature.');
            return response()->json(['status' => 'unauthorized'], 401);
        }

        try {
            $payload       = $request->all();
            $invoiceNumber = $payload['order']['invoice_number'] ?? null;
            $status        = $payload['transaction']['status'] ?? null;

            $order = BookingOrder::where('payment_order_id', $invoiceNumber)->firstOrFail();

            if ($status === 'SUCCESS') {
                $this->orders->markPaid($order, $payload);
            } elseif ($status === 'FAILED' && $order->status === 'pending_payment') {
                $this->orders->cancelUnpaid($order);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('DOKU webhook error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}
