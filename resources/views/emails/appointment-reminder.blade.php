<!DOCTYPE html>
<html dir="ltr" lang="en">
<body style="font-family: sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f9f9f9;">

    <div style="text-align: center; margin-bottom: 20px; background-color: #001f3f; padding: 20px;">
        <img src="{{ $message->embed(public_path('images/logo.jpg')) }}" alt="Clinic Logo" style="width: 150px; height: auto;">
    </div>

    <div style="background-color: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #ddd;">
        <h2 style="color: #001f3f;">Hello {{ $appointment->patient->name }}</h2>
        <p>We would like to remind you of your upcoming medical appointment.</p>

        <div style="background: #f4f4f4; padding: 15px; border-radius: 8px; border-right: 5px solid #001f3f; margin-bottom: 20px;">
            <p style="margin: 5px 0;"><strong>Doctor:</strong> Dr. {{ $appointment->doctor->user->first_name }} {{ $appointment->doctor->user->last_name }}</p>
            <p style="margin: 5px 0;"><strong>Time:</strong> {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('l, j F Y - h:i A') }}</p>
        </div>

        <!-- النص الجديد لتوجيه المريض للتطبيق -->
        <p>Please open the <strong>Clinic App</strong> on your mobile device to confirm your attendance and complete the remaining payment to finalize your booking.</p>

        <!-- التنبيه المعدل ليتناسب مع الحجز من داخل التطبيق -->
        <p style="margin-top: 30px; font-size: 13px; color: #666;">
            <strong>Note:</strong> If you do not confirm your attendance through the app at least 6 hours before the appointment, it will be automatically cancelled.
        </p>
    </div>

    <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #999;">
        <p>Best regards,<br><strong>The Clinic Management</strong></p>
    </div>

</body>
</html>