@php($payments = $payments ?? null)
<x-admin::layouts>
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-bold">FortunePay Payments</h1>
        </div>
    </div>

    <div class="page-content">
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Order No</th>
                <th>Invoice ID</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Pay No</th>
                <th>Redirect</th>
                <th>Client IP</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
        @forelse($payments as $p)
            <tr>
                <td style="border:1px solid #e5e7eb;padding:8px;">{{ $p->id }}</td>
                <td style="border:1px solid #e5e7eb;padding:8px;">{{ $p->order_no }}</td>
                <td style="border:1px solid #e5e7eb;padding:8px;">{{ $p->invoice_id }}</td>
                <td style="border:1px solid #e5e7eb;padding:8px;">
                    {{ $p->status }}
                </td>
                <td style="border:1px solid #e5e7eb;padding:8px;">{{ number_format((float) $p->amount, 2) }}</td>
                <td style="border:1px solid #e5e7eb;padding:8px;">{{ $p->currency }}</td>
                <td style="border:1px solid #e5e7eb;padding:8px;">{{ $p->pay_no }}</td>
                <td style="border:1px solid #e5e7eb;padding:8px;">{{ $p->redirect ? 'Yes' : 'No' }}</td>
                <td style="border:1px solid #e5e7eb;padding:8px;">{{ $p->client_ip }}</td>
                <td style="border:1px solid #e5e7eb;padding:8px;">{{ $p->created_at }}</td>
            </tr>
        @empty
            <tr><td style="border:1px solid #e5e7eb;padding:8px;" colspan="13">No payments found.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div style="margin-top:12px;">
        {{ $payments->links() }}
    </div>
    </div>

</x-admin::layouts>