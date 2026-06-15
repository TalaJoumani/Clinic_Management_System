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

        <p>Please confirm your attendance to finalize your booking.</p>

        <table border="0" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
            <tr>
                <td align="center" style="border-radius: 5px;" bgcolor="#001f3f">
                    <a href="{{ url('/appointment/confirm/' . $appointment->id) }}" 
                       style="font-size: 14px; font-family: sans-serif; color: #ffffff; text-decoration: none; padding: 12px 20px; display: block; font-weight: bold;">
                       Confirm Attendance
                    </a>
                </td>
                
                <td width="20">&nbsp;</td>
                
                <td align="center" style="border-radius: 5px;" bgcolor="#dc3545">
                    <a href="{{ url('/appointment/cancel/' . $appointment->id) }}" 
                       style="font-size: 14px; font-family: sans-serif; color: #ffffff; text-decoration: none; padding: 12px 20px; display: block; font-weight: bold;">
                       Cancel Appointment
                    </a>
                </td>
            </tr>
        </table>

        <p style="margin-top: 30px; font-size: 13px; color: #666;">
            Note: If you do not confirm your attendance at least 6 hours before the appointment, it will be automatically cancelled.
        </p>
    </div>

    <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #999;">
        <p>Best regards,<br><strong>The Clinic Management</strong></p>
    </div>

</body>
</html>