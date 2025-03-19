<div>
<div>
    <x-ui-button click-event="goBack()" type="Back" button-name="Back"/>
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
                <div class="barcode-container2">
                    <h4 class="barcode-name2">{{ $barcodeName }}</h4>
                    <svg class="barcode2" id="barcode2"></svg>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script type="text/javascript">
    function goBack() {
        // Implement the back functionality here, e.g., history.back()
        history.back();
    }

    document.addEventListener("DOMContentLoaded", function() {
        generateBarcode();
    });

    function generateBarcode() {
        var barcodeName = "{{$barcodeName}}"; // Assuming $barcodeName is defined
        var barcodeValue = "{{$barcode}}";   // Assuming $barcode is defined

        // Set fixed dimensions for the barcode
        var barcodeOptions = {
            format: "CODE128",
            width: 2.0,
            height: 13,
            fontSize: 10,
            displayValue: true,
            margin: 0,
            marginLeft: 0,
            marginRight: 0,
            marginTop: 5,
            marginBottom: 0
        };

        // Calculate left margin based on barcode length
        var leftMargin = barcodeValue.length > 15 ? '20px' : '30px';
        document.querySelector(".barcode-container").style.paddingLeft = leftMargin;
        document.querySelector(".barcode-container2").style.paddingLeft = leftMargin;

        JsBarcode("#barcode1", barcodeValue, barcodeOptions);
        JsBarcode("#barcode2", barcodeValue, barcodeOptions);
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
