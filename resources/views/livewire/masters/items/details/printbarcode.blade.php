<!DOCTYPE html>
<html>
<head>
    <title>Barcode</title>
    <style>
        /* Add CSS styles for the barcode here */
        .barcode-container {
            width: {{ $barcodeSize[0] }}in;
            height: {{ $barcodeSize[1] }}in;
        }
    </style>
</head>
<body>
    <div class="barcode-container">
        <img src="data:image/png;base64,{{ $barcode }}" alt="Barcode">
        <div>{{ $item }}</div>
    </div>
</body>
</html>
