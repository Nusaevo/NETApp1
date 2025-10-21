<div>
<head>
    <style>
        /* Import monospace fonts for dot matrix printer compatibility */
        @font-face {
            font-family: 'Courier New';
            src: local('Courier New');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Lucida Console';
            src: local('Lucida Console');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Consolas';
            src: local('Consolas');
            font-weight: normal;
            font-style: normal;
        }
    </style>
</head>
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
    // Fungsi untuk mengganti font
    function switchFont(fontName) {
        document.querySelectorAll('.label-code, .label-price, .label-name, .label-descr').forEach(element => {
            element.style.fontFamily = fontName + ', monospace';
        });
        // Simpan preferensi di local storage
        localStorage.setItem('preferredPrintFont', fontName);
    }

    // Tambahkan opsi font
    function addFontSelector() {
        const fontSelector = document.createElement('div');
        fontSelector.className = 'font-selector';
        fontSelector.style.marginTop = '10px';
        fontSelector.innerHTML = `
            <label>Font Printer: </label>
            <select id="fontSelect" onchange="switchFont(this.value)">
                <option value="Courier New">Courier New (Default)</option>
                <option value="Lucida Console">Lucida Console (Lebih bersih)</option>
                <option value="Consolas">Consolas (Modern)</option>
                <option value="MS Sans Serif">MS Sans Serif (Non-monospace)</option>
                <option value="Draft 10cpi">Draft 10cpi/12cpi (Printer)</option>
            </select>
        `;
        document.querySelector('.col-xl-3').appendChild(fontSelector);

        // Load saved preference
        const savedFont = localStorage.getItem('preferredPrintFont');
        if (savedFont) {
            document.getElementById('fontSelect').value = savedFont;
            switchFont(savedFont);
        }
    }

    function formatText(text, maxLength) {
        // Handle empty text
        if (!text || text.trim() === '') {
            return '';
        }

        // Normalize whitespace and clean the text
        text = text.trim().replace(/\s+/g, ' ');

        let formattedText = '';
        let words = text.split(' ');
        let line = '';

        for (let i = 0; i < words.length; i++) {
            // For longer words, we may need to break them
            if (words[i].length > maxLength) {
                if (line.trim() !== '') {
                    formattedText += line.trim() + '\n';
                }
                // Break long word into chunks
                let longWord = words[i];
                let j = 0;
                while (j < longWord.length) {
                    let chunk = longWord.substr(j, maxLength);
                    formattedText += chunk;
                    j += maxLength;
                    // Only add newline if not at end
                    if (j < longWord.length) {
                        formattedText += '-\n';
                    }
                }
                line = ' '; // Add space after long word
            } else if ((line + words[i]).length > maxLength) {
                formattedText += line.trim() + '\n';
                line = words[i] + ' ';
            } else {
                line += words[i] + ' ';
            }
        }

        if (line.trim() !== '') {
            formattedText += line.trim();
        }

        return formattedText;
    }

    document.addEventListener('DOMContentLoaded', (event) => {
        const descrElement = document.getElementById('label-descr');
        const originalText = descrElement.innerText;
        descrElement.innerText = formatText(originalText, 12); // Lebih sedikit karakter per baris untuk monospace font

        // Add font selector
        addFontSelector();
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
        font-family: 'Courier New', monospace;
        /* Geser sedikit ke atas */
    }

    .label-price {
        font-size: 12px;
        font-family: 'Courier New', monospace;
        font-weight: bold;
    }

    .label-name {
        margin-top: 5px;
        font-size: 10px;
        font-family: 'Courier New', monospace;
        font-weight: bold;
        padding-left: 5px; /* Adjust the value as needed */
        letter-spacing: 0; /* Monospace fonts have consistent spacing */
    }

    .label-descr {
        font-family: 'Courier New', monospace; /* Best for dot matrix printers */
        font-size: 10px; /* Ukuran font konsisten untuk monospace */
        max-width: 100%;
        word-break: normal; /* Tidak memecah kata secara agresif */
        font-weight: bold; /* Bold standard untuk dot matrix */
        white-space: pre-wrap; /* Preserve whitespace and wrap as necessary */
        padding-left: 5px; /* Adjust the value as needed */
        letter-spacing: 0; /* Monospace sudah fixed width */
        line-height: 1.2; /* Optimal spacing for dot matrix */
        -webkit-font-smoothing: none; /* No smoothing for dot matrix style */
        text-rendering: optimizeLegibility;
    }

    /* Font selector styling */
    .font-selector {
        margin-top: 10px;
        margin-bottom: 10px;
    }

    .font-selector select {
        padding: 5px;
        border-radius: 3px;
        border: 1px solid #ced4da;
        margin-left: 5px;
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
            print-color-adjust: exact; /* Ensures colors print accurately */
            -webkit-print-color-adjust: exact;
        }

        /* Enhance print quality for dot matrix printers */
        .label-name, .label-descr {
            text-rendering: optimizeSpeed; /* Speed over quality for dot matrix */
            font-smooth: never;
            -webkit-font-smoothing: none;
            color: #000000 !important; /* Force black color for better print contrast */
        }

        /* Special optimization for description text */
        .label-descr {
            font-family: 'Courier New', monospace !important; /* Ideal for dot matrix */
            font-weight: bold !important; /* Standard bold works best with dot matrix */
            letter-spacing: 0 !important; /* Monospace has fixed spacing */
        }
    }

</style>

</div>
