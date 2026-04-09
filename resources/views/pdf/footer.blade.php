<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 5px;
            font-size: 9pt;
            border-top: 1px solid #000;
        }

        .footer-left {
            float: left;
            width: 60%;
        }

        .footer-right {
            float: right;
            width: 35%;
            text-align: right;
        }
    </style>
    <script>
        function subst() {
            var vars = {};
            var query_strings_from_url = document.location.search.substring(1).split('&');
            for (var query_string in query_strings_from_url) {
                if (query_strings_from_url.hasOwnProperty(query_string)) {
                    var temp_var = query_strings_from_url[query_string].split('=', 2);
                    vars[temp_var[0]] = decodeURI(temp_var[1]);
                }
            }
            var css_selector_classes = ['page', 'topage', 'date', 'time'];
            for (var css_class in css_selector_classes) {
                var element = document.getElementsByClassName(css_selector_classes[css_class]);
                for (var j = 0; j < element.length; ++j) {
                    element[j].textContent = vars[css_selector_classes[css_class]];
                }
            }
        }
    </script>
</head>

<body onload="subst()">
    <div class="footer-left">
        <strong>Fecha y hora de impresión:</strong> <span class="date"></span> <span class="time"></span>
    </div>
    <div class="footer-right">
        <strong>Página:</strong> <span class="page"></span> de <span class="topage"></span>
    </div>
</body>

</html>