<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 5px;
            border-collapse: collapse;
        }

        .header-logo-td {
            width: 100px;
            text-align: left;
            vertical-align: middle;
        }

        .header-logo-td img {
            width: 90px;
            height: auto;
            max-height: 90px;
        }

        .header-content-td {
            text-align: center;
            vertical-align: middle;
            padding-right: 100px;
            /* Balance the logo on the left */
        }

        .header-title {
            font-weight: bold;
            font-size: 18px;
            color: #000;
            margin-bottom: 5px;
        }

        .header-subtitle {
            font-weight: bold;
            font-size: 14px;
            color: #000;
            margin-top: 2px;
        }
    </style>
</head>

<body>
    @include('pdf.header_content')
</body>

</html>