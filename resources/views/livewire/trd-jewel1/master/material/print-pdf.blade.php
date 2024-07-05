<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Label</title>
    <style>
        @page {
            size: 8cm 3cm;
            margin: 0.5cm 0 0 0.5cm; /* Margin atas 0.5cm, kiri 0.5cm, kanan dan bawah 0cm */
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .label-container {
            width: 8cm; /* Kurangi lebar untuk memperhitungkan margin kiri */
            height: 3cm; /* Kurangi tinggi untuk memperhitungkan margin atas */
            padding: 0 0.5cm; /* Padding kiri dan kanan */
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: flex-start; /* Posisikan di kiri secara horizontal */
            padding-left: 4cm;
        }

        .label-code, .label-price, .label-name, .label-descr {
            padding: 0;
            text-align: left; /* Align ke kiri */
            width: 100%; /* Pastikan elemen menggunakan seluruh lebar container */
            margin: 0; /* Hilangkan margin default */
        }

        .label-code {
            font-size: 16px;
            font-weight: bold;
            margin-top: -6mm; /* Geser sedikit ke atas */
        }

        .label-price {
            font-size: 12px;
        }

        .label-name {
            margin-top: 5px;
            font-size: 8px;
        }

        .label-descr {
            font-size: 8px;
            max-width: 100%; /* Set max width to 100% */
            margin-top: 5px;
            word-break: break-all; /* Ensure words break to next line */
            white-space: pre-wrap; /* Preserve whitespace and wrap as necessary */
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
                padding: 0.5cm; /* Padding sesuai margin halaman */
                box-sizing: border-box;
                border: none;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                align-items: flex-start;
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
                    <div class="label-price">{{ numberFormat(currencyToNumeric($object->jwl_selling_price)) }}</div>
                    <div class="label-name">{{ $object->name }}</div>
                    <div class="label-descr" id="label-descr">{{ $object->descr }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function formatText(text, maxLength) {
        let formattedText = '';
        for (let i = 0; i < text.length; i += maxLength) {
            formattedText += text.substring(i, i + maxLength) + '\n';
        }
        return formattedText;
    }

    document.addEventListener('DOMContentLoaded', (event) => {
        const descrElement = document.getElementById('label-descr');
        const originalText = descrElement.innerText;
        descrElement.innerText = formatText(originalText, 18);
    });

    function printInvoice() {
        window.print();
    }
</script>
</body>
</html>
