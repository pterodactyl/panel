<!DOCTYPE HTML>
<html>
    <head>
        <title>Invoice - #{{ $id }}</title>
        <meta charset="utf-8">
        <style>
            table {width: 100%;}
            .text-right {text-align: right;}
            .text-center {text-align: center;}
            .items {border: 1px solid #555;}
            .items tr:first-child {background-color: #ddd;}
        </style>
    <head>
    <body>
        
        <h4>GabLab</h4>
        <table>
            <tr>
                <td>
                    GabLab<br />
                    Via Delle Madonne 53,<br />
                    Brindisi 72100 IT<br />
                    P.IVA EU 0000000000<br />
                </td>
                <td class="text-right">
                    <b>{{ $billing_first_name }} {{ $billing_last_name }}</b><br />{{ $billing_address }},<br />
                    {{ $billing_city }} {{ $billing_zip }}, {{ $billing_country }}
                </td>
            </tr>
        </table>

        <table class="items">
            <tr>
                <th style="width: 100%">Item Name</th>
                <th style="min-width: 150px;">Price</th>
            </tr>
            <tr>
                <td>Platform credits</td>
                <td class="text-center">${{ number_format($amount, 2) }}</td>
            </tr>
        </table>

        <hr />

        Payment ID: #{{ $id }}<br />
        Payment amount: ${{ number_format($amount, 2) }}<br />
        Payment date: {{ date(__('strings.date_format'), strtotime($created_at)) }}<br />

    </body>
</html>