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
            // Check if adding this word would exceed maxLength
            let testLine = line + words[i];
            if (testLine.length > maxLength && line.trim() !== '') {
                // If current line is not empty, start new line
                formattedText += line.trim() + '\n';
                line = words[i] + ' ';
            } else {
                line += words[i] + ' ';
            }
        }

        formattedText += line.trim();
        return formattedText;
    }

    function detectBOMCount(text) {
        // Count BOM items dari string kontinyu seperti "1 EM:0.98 35 TP:0.65 74"
        const bomMatches = text.match(/[A-Z]{2,}:\d+(\.\d+)?/g);
        return bomMatches ? bomMatches.length : 0;
    }

    function formatBOMText(text, bomCount) {
        if (bomCount <= 3) {
            // Normal vertical layout untuk ≤ 3 items
            return formatText(text, 18);
        } else {
            // Smart BOM formatting - group items by pairs
            const words = text.split(' ');
            const bomItems = [];

            // Group setiap 2 kata sebagai satu item BOM (qty + type:value)
            for (let i = 0; i < words.length; i += 2) {
                if (words[i] && words[i + 1]) {
                    bomItems.push(words[i] + ' ' + words[i + 1]);
                } else if (words[i]) {
                    bomItems.push(words[i]);
                }
            }

            if (bomItems.length === 0) {
                return formatText(text, 18);
            }

            let result = '';
            let currentLine = '';

            for (let i = 0; i < bomItems.length; i++) {
                let testLine = currentLine + (currentLine ? ' ' : '') + bomItems[i];

                // Jika menambah item ini melebihi 18 karakter, buat baris baru
                if (testLine.length > 18 && currentLine !== '') {
                    result += currentLine + '\n';
                    currentLine = bomItems[i];
                } else {
                    currentLine = testLine;
                }
            }

            result += currentLine;
            return result;
        }
    }

    document.addEventListener('DOMContentLoaded', (event) => {
        const descrElement = document.getElementById('label-descr');
        const originalText = descrElement.innerText;
        const bomCount = detectBOMCount(originalText);

        console.log('Original text:', originalText);
        console.log('BOM count:', bomCount);

        // Apply dynamic font size based on BOM count
        if (bomCount > 6) {
            descrElement.style.fontSize = '7px';
            descrElement.style.lineHeight = '1.0';
            descrElement.style.letterSpacing = '-0.2px';
        } else if (bomCount > 3) {
            descrElement.style.fontSize = '8px';
            descrElement.style.lineHeight = '1.1';
        }

        const formattedText = formatBOMText(originalText, bomCount);
        console.log('Formatted text:', formattedText);
        descrElement.innerText = formattedText;
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
        margin-top: -2px;
        font-size: 10px; /* Ukuran font lebih besar */
        font-family: Arial;
        font-weight: bold;
        padding-left: 3px; /* Adjust the value as needed */
        text-shadow: 0.25px 0px 0px black, -0.25px 0px 0px black; /* Menambahkan shadow untuk efek lebih tebal */
        -webkit-text-stroke: 0.2px black; /* Efek stroke tipis untuk ketebalan */
    }

    .label-descr {
        font-family: Arial, sans-serif;
        font-size: 10px; /* Default font size */
        max-width: 100%;
        word-break: break-word;
        font-weight: bold;
        white-space: pre-wrap;
        padding-left: 0px;
        letter-spacing: -0.1px;
        text-shadow: 0.25px 0px 0px black, -0.25px 0px 0px black;
        -webkit-text-stroke: 0.2px black;
        line-height: 1.2;
        overflow: hidden;
        margin-top: -3px;
    }

    /* Dynamic BOM styling */
    .label-descr.bom-small {
        font-size: 8px;
        line-height: 1.1;
    }

    .label-descr.bom-tiny {
        font-size: 7px;
        line-height: 1.0;
        letter-spacing: -0.2px;
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
