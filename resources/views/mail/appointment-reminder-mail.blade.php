{{--
============================================================
STUDENT COUNSELING SESSION REMINDER EMAIL (20-10 minutes before)
------------------------------------------------------------
Passed from ReminderAppointmentMailable:
$student App\Models\Student
$appointment App\Models\Appointment
$logoData ?string raw image bytes, embedded inline
============================================================
--}}

@php
  $serif = "Georgia,'Times New Roman',Times,serif";

  $logoSrc = $logoData
    ? $message->embedData($logoData, 'MailLogo.png', 'image/png')
    : config('supabase-auth.url') . '/storage/v1/object/public/assets/MailLogo.svg';
@endphp

<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="color-scheme" content="light">
  <title>Your Counseling Session Is About to Begin</title>
</head>

<body style="margin:0; padding:0; background:#ffffff; font-family:{{ $serif }}; color:#1a1a1a;">

  <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
    Your counseling session with the Guidance &amp; Counseling Unit is about to begin.
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff;">
    <tr>
      <td align="center" style="padding:48px 20px;">

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; width:100%;">

          {{-- Letterhead --}}
          <tr>
            <td style="padding-bottom:8px;">
              <img src="{{ $logoSrc }}" alt="MoodLink" style="display:block; height:128px; width:128px; border:0;">

              <p style="margin:10px 0 0; font-family:{{ $serif }}; font-size:13px; font-style:italic; color:#555;">
                Guidance &amp; Counseling Unit
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:14px 0 30px;">
              <div style="border-top:1px solid #000; line-height:0; font-size:0;">
                &nbsp;
              </div>
            </td>
          </tr>


          {{-- Greeting --}}
          <tr>
            <td style="padding-bottom:20px;">
              <p style="margin:0; font-family:{{ $serif }}; font-size:17px; line-height:1.7;">
                Dear {{ $student->first_name }},
              </p>
            </td>
          </tr>


          {{-- Main message --}}
          <tr>
            <td style="padding-bottom:20px;">
              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.8;">
                This is a gentle reminder that your counseling session with the
                Guidance &amp; Counseling Unit will begin soon.
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding-bottom:20px;">
              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.8;">
                Your counselor is ready to meet with you and provide a safe space
                where you can share your thoughts, concerns, and experiences.
                We look forward to listening and supporting you.
              </p>
            </td>
          </tr>


          {{-- Appointment details --}}
          <tr>
            <td style="padding:4px 0 24px 28px;">

              <p style="margin:0 0 6px; font-family:{{ $serif }}; font-size:16px; line-height:1.9;">
                <span style="display:inline-block; width:72px; color:#555;">Date</span>
                {{ $appointment->display_date }}
              </p>

              <p style="margin:0 0 6px; font-family:{{ $serif }}; font-size:16px; line-height:1.9;">
                <span style="display:inline-block; width:72px; color:#555;">Time</span>
                {{ $appointment->display_time }}
              </p>

              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.9;">
                <span style="display:inline-block; width:72px; color:#555;">Venue</span>
                Guidance &amp; Counseling Unit
              </p>

            </td>
          </tr>


          {{-- Arrival instruction --}}
          <tr>
            <td style="padding-bottom:20px;">
              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.8;">
                Please proceed to the Guidance &amp; Counseling Unit at your scheduled
                time. Kindly present yourself to the counselor upon arrival so your
                attendance can be recorded.
              </p>
            </td>
          </tr>


          {{-- QR Check-in --}}
          <tr>
            <td style="padding-bottom:20px;">
              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.8;">
                After arriving at the Guidance &amp; Counseling Unit, please use the
                provided session QR code for your check-in. This will confirm your
                attendance for the scheduled counseling session.
              </p>
            </td>
          </tr>


          {{-- Late warning --}}
          <tr>
            <td style="padding-bottom:20px;">
              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.8;">
                Please be reminded that if you do not arrive and complete your
                check-in within 30 minutes after the scheduled session start time,
                your appointment will automatically be marked as missed.
              </p>
            </td>
          </tr>


          {{-- Support message --}}
          <tr>
            <td style="padding-bottom:30px;">
              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.8;">
                Remember that this session is your space to be heard without
                judgment. Whether you are facing challenges, feeling overwhelmed,
                or simply need someone to talk to, the Guidance &amp; Counseling Unit
                is here to listen and support you.
              </p>
            </td>
          </tr>


          {{-- Sign off --}}
          <tr>
            <td>

              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.7;">
                Respectfully,
              </p>

              <p style="margin:18px 0 0; font-family:{{ $serif }}; font-size:16px; line-height:1.6;">
                Guidance &amp; Counseling Unit<br>

                <span style="font-style:italic; color:#555;">
                  MoodLink · Student Well-being Platform
                </span>
              </p>


              <p style="margin:14px 0 0; font-family:{{ $serif }}; font-size:15px; line-height:1.9;">

                <a href="mailto:pogiako@example.com"
                  style="color:#000; text-decoration:none; border-bottom:1px solid #000; padding-bottom:6px;">
                  pogiako@example.com
                </a>

                <span style="color:#555;">
                  &nbsp;&middot;&nbsp;
                </span>

                <a href="tel:+639616665231"
                  style="color:#000; text-decoration:none; border-bottom:1px solid #000; padding-bottom:6px;">
                  +63 961 666 5231
                </a>

              </p>

            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>

</html>