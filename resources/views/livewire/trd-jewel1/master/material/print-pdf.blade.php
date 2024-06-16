<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Label</title>
    <style>
        @page {
            size: 8cm 3cm;
            margin: 0.5cm 0 0 0.5cm; /* Atas 0.5cm, kiri 0.5cm, kanan dan bawah 0cm */
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .label-container {
            width: 7cm; /* Kurangi lebar untuk memperhitungkan margin kiri */
            height: 2.5cm; /* Kurangi tinggi untuk memperhitungkan margin atas */
            padding: 0 10px 0 0.5cm; /* Tambahkan padding kanan 10px dan padding kiri sedikit */
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: flex-start; /* Sebarkan konten secara vertikal */
            align-items: flex-end; /* Posisikan di kanan secara horizontal */
            margin-right: 30px;
        }

        .label-code, .label-price {

            padding: 0;
            text-align: right; /* Align ke kanan */
            width: auto; /* Pastikan elemen menggunakan seluruh lebar container */
        }

        .label-code {
            font-size: 16px;
            font-weight: bold;
            margin-top: -20px; /* Geser ke atas */
        }

        .label-price {
            font-size: 12px;
        }

        .label-name{
            margin-top: 20px;
        }
        .label-name, .label-descr {
            font-size: 8px;
            padding: 0;
            text-align: right; /* Align ke kanan */
            width: auto; /* Pastikan elemen menggunakan seluruh lebar container */
        }

        .label-descr {
        }

        @media print {
            body * {
                visibility: hidden;
            }
            #print, #print * {
                visibility: visible;
            }
            #print {
                position: fixed;
                left: 0;
                top: 0;
                width: 8cm;
                height: 3cm;
                padding: 0;
                margin: 0.5cm 0 0 0.5cm;
                box-sizing: border-box;
                border: none;
                display: flex;
                justify-content: flex-start;
                align-items: flex-end;
                flex-direction: column;
                padding-right: 10px; /* Tambahkan padding kanan 10px */
            }
        }
    </style>
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
                    <div class="label-price">{{ currencyToNumeric($object->jwl_selling_price) }}</div>
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
