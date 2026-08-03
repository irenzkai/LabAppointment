<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @php
        // 1. Detect if the image is natively in a landscape orientation
        $isLandscape = (isset($imgWidth) && isset($imgHeight) && $imgWidth > $imgHeight);
        
        // 2. Dynamically assign page size and aspect-ratio limits based on orientation
        if ($isLandscape) {
            $pageSize = 'A4 landscape';
            // A4 landscape aspect ratio limit is 297/210 ≈ 1.414
            $isHeightLimited = (isset($imgWidth) && isset($imgHeight) && $imgHeight > 0)
                ? (($imgWidth / $imgHeight) < 1.414)
                : false;
        } else {
            $pageSize = 'A4 portrait';
            // A4 portrait aspect ratio limit is 210/297 ≈ 0.707
            $isHeightLimited = (isset($imgWidth) && isset($imgHeight) && $imgHeight > 0)
                ? (($imgWidth / $imgHeight) < 0.707)
                : true;
        }
    @endphp
    <style>
        @page { 
            /* FIXED: Reverted margin to 0px to prevent DomPDF's relative height collapse bug */
            margin: 0px; 
            size: {{ $pageSize }};
        }
        html, body { 
            margin: 0px; 
            padding: 0px; 
            width: 100%;
            height: 100%;
            background-color: #ffffff;
        }
        .wrapper-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }
        .wrapper-td {
            text-align: center;
            vertical-align: middle;
            padding: 0;
            margin: 0;
        }
    </style>
</head>
<body>
    {{-- Centering wrapper table --}}
    <table class="wrapper-table">
        <tr>
            <td class="wrapper-td">
                {{-- FIXED: Use 96% scaling under a 0px page margin to render a clean, even 2% border safely --}}
                <img src="{{ $base64Image }}" style="{{ $isHeightLimited ? 'height: 96%; width: auto;' : 'width: 96%; height: auto;' }} display: inline-block; vertical-align: middle;">
            </td>
        </tr>
    </table>
</body>
</html>