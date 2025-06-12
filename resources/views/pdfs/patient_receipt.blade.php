<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reçu - {{ $bill->bill_number }}</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
            font-size: 12px;
            background-color: #f0f8ff;
        }
        .container {
            width: 750px;
            margin: 30px auto;
            padding: 25px 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 12px rgba(0,0,0,0.08);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .clinic-name {
            font-size: 22px;
            font-weight: 700;
            color: #0056b3;
        }
        .specialty {
            font-size: 14px;
            color: #666;
        }
        .contact-info {
            font-size: 12px;
            color: #444;
            margin-top: 8px;
        }
        .receipt-title {
            margin-top: 15px;
            font-size: 20px;
            color: #28a745;
            text-transform: uppercase;
            font-weight: bold;
            border-top: 1px dashed #ccc;
            padding-top: 10px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h3 {
            font-size: 15px;
            color: #0056b3;
            border-bottom: 1px solid #ddd;
            margin-bottom: 10px;
            padding-bottom: 5px;
        }
        .section p {
            margin: 4px 0;
        }
        .items-table table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .items-table th, .items-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            font-size: 12px;
        }
        .items-table th {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-section {
            text-align: right;
            margin-top: 20px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .total-section p {
            font-size: 16px;
            color: #28a745;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 35px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 11px;
            color: #888;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
     
        <div class="clinic-name">Cabinet Médical {{ $doctorName }}</div>
        <div class="specialty">{{ $doctorSpecialty }}</div>
        <div class="contact-info">
            ☎ Téléphone : {{ $bill->doctor && $bill->doctor->phone ? $bill->doctor->phone : 'N/A' }} |
            ✉ Email : {{ $bill->doctor && $bill->doctor->email ? $bill->doctor->email : 'N/A' }}
        </div>
        <div class="receipt-title">Reçu de Paiement</div>
    </div>

    <div class="section">
        <h3>Détails du Reçu</h3>
        <p><strong>Numéro de Reçu :</strong> {{ $bill->bill_number }}</p>
        <p><strong>Date d'émission :</strong> {{ $bill->issue_date ? $bill->issue_date->format('d/m/Y') : 'N/A' }}</p>
        <p><strong>Méthode de Paiement :</strong> {{ $bill->payment_method ? ucfirst(str_replace('_', ' ', $bill->payment_method)) : 'N/A' }}</p>
    </div>

    <div class="section">
        <h3>Facturé à</h3>
        <p><strong>Nom du Patient :</strong> {{ $patientName }}</p>
        @if($bill->patient && $bill->patient->user && $bill->patient->user->email)
            <p><strong>Email du Patient :</strong> {{ $bill->patient->user->email }}</p>
        @endif
    </div>

    <div class="section items-table">
        <h3>Détail des Prestations</h3>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th>Type de Service</th>
                <th class="text-right">Prix</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($bill->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->description ?: 'N/A' }}</td>
                    <td>{{ $item->service_type ? str_replace('_', ' ', $item->service_type) : 'N/A' }}</td>
                    <td class="text-right">{{ number_format($item->price, 2, ',', ' ') }} MAD</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Aucun article trouvé.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="total-section">
        <p>Montant Total : {{ number_format($bill->amount, 2, ',', ' ') }} €</p>
    </div>

    <div class="footer">
        <p>Merci pour votre paiement !</p>
        <p>&copy; {{ date('Y') }} Cabinet Médical {{ $doctorName }}. Tous droits réservés.</p>
    </div>
</div>
</body>
</html>
