<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Label</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/label.css') }}">
</head>
<body>
<div>
    <x-ui-button clickEvent="" type="Back" button-name="Back"/>
</div>
<div class="card">
    <div class="card-body">
        <div class="container mb-5 mt-3">
            <div class="row d-flex align-items-baseline">
                <div class="col-xl-3 float-end">
                    <button class="btn btn-light text-capitalize border-0" onclick="printInvoice()"><i class="fas fa-print text-primary"></i> Print</button>
                </div>
            </div>
            <hr>
            <div id="print">
                <!-- Label -->
                <div class="label-container">
                    <div class="label-code">{{ $object->code }}</div>
                    <div class="label-price">{{ dollar(currencyToNumeric($object->jwl_selling_price)) }}</div>
                    <div class="label-name">{{ $object->name }}</div>
                    <div class="label-descr">{{ $object->descr }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function printInvoice() {
        window.print();
    }
</script>
</body>
</html>
