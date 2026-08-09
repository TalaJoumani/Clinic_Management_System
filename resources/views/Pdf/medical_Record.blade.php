<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Medora - Medical Record Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: ltr;
            text-align: left;
            color: #333;
            background-color: #fdfdfd;
            margin: 0;
            padding: 15px;
            font-size: 13px;
            line-height: 1.5;
        }
        .report-header {
            width: 100%;
            border-bottom: 3px solid #0284c7;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo {
            width: 70px;
            height: auto;
        }
        .brand-title {
            font-size: 22px;
            font-weight: bold;
            color: #0284c7;
            margin: 0;
        }
        .brand-subtitle {
            font-size: 13px;
            color: #64748b;
            margin: 3px 0 0 0;
        }
        .patient-card {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .patient-card h4 {
            margin: 0 0 10px 0;
            color: #0369a1;
            font-size: 15px;
            border-bottom: 1px solid #e0f2fe;
            padding-bottom: 5px;
        }
        .info-row {
            width: 100%;
            margin-bottom: 5px;
        }
        .section-heading {
            font-size: 16px;
            color: #1e293b;
            border-left: 4px solid #0284c7;
            padding-left: 8px;
            margin-bottom: 15px;
        }
        .record-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .record-header-bar {
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 12px;
            font-weight: bold;
            color: #334155;
            font-size: 12px;
            border: 1px solid #f1f5f9;
        }
        .badge {
            background: #0284c7;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .label {
            font-weight: bold;
            color: #475569;
        }
        .record-content p {
            margin: 6px 0;
        }
        .medical-image {
            max-width: 120px;
            height: auto;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            margin-top: 8px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <!-- Header with Logo -->
    <div class="report-header">
        <table class="header-table">
            <tr>
                <td style="width: 80px;">
                    <img src="{{ public_path('images/logo.jpg') }}" alt="Medora Logo" class="logo">
                </td>
                <td>
                    <h2 class="brand-title">MEDORA MEDICAL SYSTEM</h2>
                    <p class="brand-subtitle">Official Patient Medical Record & History Report</p>
                </td>
                <td style="text-align: right; font-size: 12px; color: #64748b;">
                    <strong>Issued Date:</strong><br>{{ $date }}
                </td>
            </tr>
        </table>
    </div>

    <div class="patient-card">
        <h4>Patient Information</h4>
        <table class="info-row">
            <tr>
                <td><span class="label">Patient Name:</span> {{ $user->first_name }} {{ $user->last_name }}</td>
                <td><span class="label">Email:</span> {{ $user->email }}</td>
            </tr>
        </table>
    </div>

    <h3 class="section-heading">Medical History & Visits</h3>

    @forelse($medicalRecords as $record)
        <div class="record-card">
            <div class="record-header-bar">
                <span>Appointment: {{ $record['appointment_time'] ?? 'N/A' }}</span>
                <span style="float: right;" class="badge">{{ $record['type'] ?? 'General' }}</span>
            </div>
            
            <div class="record-content">
                <p><span class="label">Attending Doctor:</span> Dr. {{ $record['doctor_name'] ?? 'Not Specified' }} 
                   @if(!empty($record['doctor_specialization'])) ({{ $record['doctor_specialization'] }}) @endif
                </p>
                <p><span class="label">Diagnosis:</span> {{ $record['diagnosis'] ?? 'No diagnosis recorded' }}</p>
                <p><span class="label">Prescription:</span> {{ $record['prescription'] ?? 'None' }}</p>
                <p><span class="label">Lab Tests:</span> {{ $record['tests'] ?? 'None' }}</p>
                <p><span class="label">Doctor's Notes:</span> {{ $record['notes'] ?? 'No notes available' }}</p>

             @if(!empty($record['images']) && $record['images'] !== 'null')
    <div style="margin-top: 10px;">
        <span class="label" style="display: block; margin-bottom: 5px;">Attached Medical Image:</span>
        <!-- استخراج اسم الملف الفعلي وتوجيهه لمسار التخزين المحلي في الـ storage -->
        @php
            $imageName = basename($record['images']);
            $path = storage_path('app/public/patient-photo/' . $imageName); // عدل مسار المجلد حسب مكان تخزين الصور لديك
        @endphp

        @if(file_exists($path))
            <img src="{{ $path }}" alt="Medical Image" class="medical-image">
        @else
            <!-- حل بديل إذا كان التخزين في مجلد public العام -->
            <img src="{{ public_path('storage/patient-photo/' . $imageName) }}" alt="Medical Image" class="medical-image">
        @endif
    </div>
@endif
            </div>
        </div>
    @empty
        <div style="text-align: center; padding: 30px; color: #64748b; background: #f8fafc; border-radius: 8px;">
            <p>No medical records available at the moment.</p>
        </div>
    @endforelse

    <div class="footer">
        <p>This report is electronically generated by Medora Medical System & confidential.</p>
    </div>

</body>
</html>
