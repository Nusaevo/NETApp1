<!DOCTYPE html>
<html>
<head>
    <title>Sales Return - {{ $return_data->tr_id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 5px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        .items-table th { background-color: #f2f2f2; }
        .total-row { font-weight: bold; background-color: #f8f9fa; }
        .section-title { font-weight: bold; font-size: 14px; margin: 15px 0 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h2>SALES RETURN</h2>
        <h3>Return #{{ $return_data->tr_id }}</h3>
    </div>

    <table class="info-table">
        <tr>
            <td width="20%"><strong>Return Date:</strong></td>
            <td width="30%">{{ \Carbon\Carbon::parse($return_data->tr_date)->format('d M Y') }}</td>
            <td width="20%"><strong>Status:</strong></td>
            <td width="30%">{{ $return_data->status_code === 'D' ? 'Draft' : ($return_data->status_code === 'P' ? 'Posted' : 'Cancelled') }}</td>
        </tr>
        <tr>
            <td><strong>Customer:</strong></td>
            <td colspan="3">{{ $return_data->Partner->code ?? '' }} - {{ $return_data->Partner->name ?? '' }}</td>
        </tr>
    </table>

    <div class="section-title">RETURN ITEMS (Barang yang dikembalikan)</div>
    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Code</th>
                <th>Name</th>
                <th>UOM</th>
                <th>Qty Return</th>
            </tr>
        </thead>
        <tbody>
            @php $returnItemNo = 1; @endphp
            @foreach($return_data->OrderDtls as $detail)
                @if($detail->qty < 0) {{-- Return items have negative qty --}}
                <tr>
                    <td>{{ $returnItemNo++ }}</td>
                    <td>{{ $detail->Material->code ?? '' }}</td>
                    <td>{{ $detail->Material->name ?? '' }}</td>
                    <td>{{ $detail->matl_uom }}</td>
                    <td>{{ abs($detail->qty) }}</td>
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    @php 
        $hasExchangeItems = $return_data->OrderDtls->where('qty', '>', 0)->count() > 0;
    @endphp

    @if($hasExchangeItems)
    <div class="section-title">EXCHANGE ITEMS (Barang Pengganti/Tukar Barang)</div>
    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Code</th>
                <th>Name</th>
                <th>UOM</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $exchangeItemNo = 1; $totalAmount = 0; @endphp
            @foreach($return_data->OrderDtls as $detail)
                @if($detail->qty > 0) {{-- Exchange items have positive qty --}}
                <tr>
                    <td>{{ $exchangeItemNo++ }}</td>
                    <td>{{ $detail->Material->code ?? '' }}</td>
                    <td>{{ $detail->Material->name ?? '' }}</td>
                    <td>{{ $detail->matl_uom }}</td>
                    <td style="text-align: right;">{{ number_format($detail->price, 0, ',', '.') }}</td>
                    <td>{{ $detail->qty }}</td>
                    <td style="text-align: right;">{{ number_format($detail->amt, 0, ',', '.') }}</td>
                </tr>
                @php $totalAmount += $detail->amt; @endphp
                @endif
            @endforeach
            <tr class="total-row">
                <td colspan="6" style="text-align: right;"><strong>TOTAL</strong></td>
                <td style="text-align: right;"><strong>{{ number_format($totalAmount, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>
    @endif

    <div style="margin-top: 50px;">
        <table width="100%">
            <tr>
                <td width="33%" style="text-align: center;">
                    <div style="margin-bottom: 60px;">Customer</div>
                    <div>(_________________)</div>
                </td>
                <td width="33%" style="text-align: center;">
                    <div style="margin-bottom: 60px;">Staff</div>
                    <div>(_________________)</div>
                </td>
                <td width="33%" style="text-align: center;">
                    <div style="margin-bottom: 60px;">Manager</div>
                    <div>(_________________)</div>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 10px;">
        <p>Printed on {{ now()->format('d M Y H:i:s') }}</p>
    </div>
</body>
</html>
