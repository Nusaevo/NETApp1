<div>
<div>
    <x-ui-button clickEvent="" type="Back" button-name="Back" />
</div>
<div class="card">
    <div class="card-body">
        <div class="container mb-5 mt-3">
            <div class="row d-flex align-items-baseline">
                <div class="col-xl-3 float-end">
                    <button class="btn btn-light text-capitalize border-0" onclick="printInvoice()"><i class="fas fa-print text-primary"></i> Print</button>
                </div>
            </div>
            <hr style="margin-bottom: 30px;">
            <div id="print">
                <!-- Label -->
                <div class="label-container">
                    <div class="label-code">{{ $object->code }}</div>
                    @if ($object->isOrderedMaterial())
                        <div class="label-price">{{ numberFormat($object->jwl_selling_price_idr) }}</div>
                    @else
                        <div class="label-price">{{ numberFormat($object->jwl_selling_price_usd) }}</div>
                    @endif
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
        let words = text.split(' ');
        let line = '';

        for (let i = 0; i < words.length; i++) {
            if ((line + words[i]).length > maxLength) {
                formattedText += line.trim() + '\n';
                line = '';
            }
            line += words[i] + ' ';
        }

        formattedText += line.trim();
        return formattedText;
    }

    document.addEventListener('DOMContentLoaded', (event) => {
        const descrElement = document.getElementById('label-descr');
        const originalText = descrElement.innerText;
        descrElement.innerText = formatText(originalText, 14); // Mengurangi karakter per baris untuk font yang lebih besar
    });

    function printInvoice() {
        window.print();
    }

</script>
<style>
    @page {
        size: 8cm 3cm;
        margin: 0.5cm 0 0 0.5cm;
        /* Margin atas 0.5cm, kiri 0.5cm, kanan dan bawah 0cm */
    }

    html,
    body {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    .label-container {
        width: 8cm;
        /* Kurangi lebar untuk memperhitungkan margin kiri */
        height: 3cm;
        /* Kurangi tinggi untuk memperhitungkan margin atas */
        padding: 0 0.5cm;
        /* Padding kiri dan kanan */
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        /* Posisikan di kiri secara horizontal */
        padding-left: 4cm;
    }

    .label-code,
    .label-price,
    .label-name,
    .label-descr {
        padding: 0;
        text-align: left;
        width: 100%;
        margin: 0;
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
        font-smooth: always;
    }

    .label-code {
        font-size: 16px;
        font-weight: bold;
        margin-top: -6mm;
        font-family: Arial;
        /* Geser sedikit ke atas */
    }

    .label-price {
        font-size: 12px;
        font-family: Arial;
        font-weight: bold;
    }

    .label-name {
        margin-top: 5px;
        font-size: 10px; /* Ukuran font lebih besar */
        font-family: Arial;
        font-weight: bold;
        padding-left: 5px; /* Adjust the value as needed */
        text-shadow: 0.25px 0px 0px black, -0.25px 0px 0px black; /* Menambahkan shadow untuk efek lebih tebal */
        -webkit-text-stroke: 0.2px black; /* Efek stroke tipis untuk ketebalan */
    }

    .label-descr {
        font-family: Arial, sans-serif;
        font-size: 10px; /* Ukuran font yang sesuai */
        max-width: 100%;
        word-break: break-all;
        font-weight: bold; /* Menggunakan bold standar seperti label-code */
        white-space: pre-wrap; /* Preserve whitespace and wrap as necessary */
        padding-left: 5px; /* Adjust the value as needed */
        letter-spacing: -0.1px; /* Sedikit mengurangi jarak antar huruf untuk keterbacaan lebih baik */
        text-shadow: 0.25px 0px 0px black, -0.25px 0px 0px black; /* Menambahkan shadow untuk efek lebih tebal */
        -webkit-text-stroke: 0.2px black; /* Memberikan outline tipis untuk menebalkan teks */
    }


    @media print {
        body * {
            visibility: hidden;
        }

        #print,
        #print * {
            visibility: visible;
        }

        #print {
            position: fixed;
            left: 0;
            top: 0;
            width: 8cm;
            height: 3cm;
            padding: 0.5cm;
            /* Padding sesuai margin halaman */
            box-sizing: border-box;
            border: none;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
        }

        /* Memastikan ketebalan font saat dicetak */
        .label-name, .label-descr, .label-code {
            font-weight: bold !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            -webkit-text-stroke: 0.3px black !important;
            text-shadow: 0.25px 0px 0px black, -0.25px 0px 0px black !important;
        }
    }

</style>

</div>
