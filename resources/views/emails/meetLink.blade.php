<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Appointment Confirmed Link - Medora</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            margin: auto;
        }
        .logo {
            max-width: 120px;
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
            font-size: 24px;
        }
        p {
            color: #555555;
            font-size: 16px;
            line-height: 1.5;
        }
        .appointment-box {
            background-color: #f9f9f9;
            border: 1px solid #e1e4e8;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            direction: ltr;
        }
        .appointment-box p {
            margin: 5px 0;
            color: #333333;
            font-size: 15px;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #999999;
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- شعار العيادة المضمن -->
        <img src="{{ $message->embed(public_path('images/logo.jpg')) }}" alt="Clinic Logo" style="width: 150px; height: auto;" class="logo">

        <h1>Appointment Confirmed!</h1>
        
        <p>Dear Patient,</p>
        <p>Your appointment has been successfully confirmed following the completion of your payment.</p>

        <!-- صندوق تفاصيل الموعد (التاريخ والوقت) -->
        <div class="appointment-box">
            <p><strong>Appointment Details:</strong></p>
            <p><strong>Time:</strong> {{ $appointment->appointment_time }}</p>
        </div>

        <p>You can join the online consultation meeting via the link below:</p>

        <a href="{{ $link }}" class="btn" target="_blank">Join Google Meet</a>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Medora Clinic. All rights reserved.</p>
        </div>
    </div>

</body>
</html>