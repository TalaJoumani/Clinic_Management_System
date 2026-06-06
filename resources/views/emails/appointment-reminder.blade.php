<!DOCTYPE html>
<html dir="rtl" lang="ar">
<body>
    <h2>Hello {{ $appointment->patient->name }}</h2>
    <p>We would like to remind you that you have a medical appointment tomorrow.</p>

    <div style="background: #f4f4f4; padding: 15px; border-radius: 8px;">
        <p><strong>Doctor:</strong> Dr. {{ $appointment->doctor->user->first_name }} {{ $appointment->doctor->user->last_name }}</p>
        <p><strong>Time:</strong> {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('l,j F Y - h:i A') }}</p>
    </div>

    <p>We look forward to seeing you at your appointment.</p>
    <br>
    <p>Best regards, the Clinic Management.</p>
</body>
</html>