<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Label</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/barcode.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/barcodes/JsBarcode.code128.min.js"></script>
</head>
<body>
<div>
    <x-ui-button click-event="" type="Back" button-name="Back"/>
</div>
<div class="card">
    <div class="card-body">
        <div class="container mb-5 mt-3">
            <div class="row d-flex align-items-baseline">
                <div class="col-xl-9">
                    <p style="color: #7e8d9f;font-size: 20px;">Barcode >> <strong>Nama Barang: {{$barcodeName }}</strong></p>
                </div>
                <div class="col-xl-3 float-end">
                    <button class="btn btn-light text-capitalize border-0" onclick="printBarcode()"><i class="fas fa-print text-primary"></i> Print</button>
                </div>
            </div>
            <hr>
            <div id="print">
                <!-- Barcode 1 -->
                <div class="barcode-container">
                    <h4 class="barcode-name">{{ $barcodeName }}</h4>
                    <svg class="barcode" id="barcode1"></svg>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function goBack() {
        // You can implement the back functionality here, e.g., history.back()
    }

    // Use DOMContentLoaded event instead of window.onload
    document.addEventListener("DOMContentLoaded", function() {
        generateBarcode();
    });

    function generateBarcode() {
        var barcodeName = "{{$barcodeName}}"; // Assuming $barcodeName is defined
        var barcodeValue = "{{$barcode}}";   // Assuming $barcode is defined

        // Generate Barcode 1 using JsBarcode
        JsBarcode("#barcode1", barcodeValue, {
            format: "CODE128",
            width: 1.3,
            height: 13,
            fontSize: 7,
            displayValue: true,
            margin: 0,
            marginLeft: 15,
            marginRight: 0,
            marginTop: 5,
            marginBottom: 0
        });

    }

    function printBarcode() {
                var page = document.getElementById("print");
                var newWin = window.open('', 'Print-Window');
                newWin.document.open();
                newWin.document.write('<html><link rel="stylesheet" type="text/css" href="/customs/css/barcode.css"><body onload="window.print()">' + page.innerHTML + '</body></html>');
                newWin.document.close();
                setTimeout(function() {
                    newWin.close();
                }, 10);
            }
</script>
</body>
</html>
