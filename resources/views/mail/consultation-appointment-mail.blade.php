{{--
============================================================
OFFICE-SCHEDULED CONSULTATION EMAIL (classic letter, plain HTML)
------------------------------------------------------------
Sent when the Guidance Office schedules a consultation with a
student (e.g. an at-risk follow-up) — the student did not request
it, so the copy reflects the Unit reaching out.

Passed from ConsultationAppointmentMailable:
$student App\Models\Student
$appointment App\Models\Appointment
$logoData ?string raw PNG bytes, embedded inline
============================================================
--}}
@php
  $serif = "Georgia,'Times New Roman',Times,serif";

  $logoSrc = $logoData
    ? $message->embedData($logoData, 'MoodlinkLogo.svg', 'image/svg+xml')
    : config('supabase-auth.url') . '/storage/v1/object/public/assets/MoodlinkLogo.svg';
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="color-scheme" content="light">
  <title>Consultation scheduled</title>
</head>

<body style="margin:0; padding:0; background:#ffffff; font-family:{{ $serif }}; color:#1a1a1a;">
  <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
    The Guidance &amp; Counseling Unit has scheduled a consultation with you on
    {{ $appointment->display_date }} at {{ $appointment->display_time }}.
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff;">
    <tr>
      <td align="center" style="padding:48px 20px;">

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; width:100%;">

          {{-- Letterhead --}}
          <tr>
            <td style="padding-bottom:8px;">
              <img src="{{ $logoSrc }}" alt="MoodLink" height="128"
                style="display:block; height:128px; width:128px; border:0;">
              <p style="margin:10px 0 0; font-family:{{ $serif }}; font-size:13px; font-style:italic; color:#555555;">
                Guidance &amp; Counseling Unit
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:14px 0 30px;">
              <div style="border-top:1px solid #000000; line-height:0; font-size:0;">&nbsp;</div>
            </td>
          </tr>

          {{-- Salutation --}}
          <tr>
            <td style="padding-bottom:20px;">
              <p style="margin:0; font-family:{{ $serif }}; font-size:17px; line-height:1.7; color:#1a1a1a;">
                Dear {{ $student->first_name }},
              </p>
            </td>
          </tr>

          {{-- Body --}}
          <tr>
            <td style="padding-bottom:20px;">
              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.8; color:#1a1a1a;">
                The Guidance &amp; Counseling Unit would like to check in with you and has set aside a
                consultation for you on the date and time below. There is nothing you need to do beforehand —
                we simply wanted to make some time to talk.
              </p>
            </td>
          </tr>

          {{-- Details, as an indented letter passage --}}
          <tr>
            <td style="padding:4px 0 24px 28px;">
              <p style="margin:0 0 6px; font-family:{{ $serif }}; font-size:16px; line-height:1.9; color:#1a1a1a;">
                <span
                  style="display:inline-block; width:72px; color:#555555;">Date</span>{{ $appointment->display_date }}
              </p>
              <p style="margin:0 0 6px; font-family:{{ $serif }}; font-size:16px; line-height:1.9; color:#1a1a1a;">
                <span
                  style="display:inline-block; width:72px; color:#555555;">Time</span>{{ $appointment->display_time }}
              </p>
              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.9; color:#1a1a1a;">
                <span style="display:inline-block; width:72px; color:#555555;">Venue</span>Guidance &amp; Counseling
                Unit
              </p>
            </td>
          </tr>

          {{-- Prep + reassurance --}}
          <tr>
            <td style="padding-bottom:20px;">
              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.8; color:#1a1a1a;">
                Kindly arrive about ten minutes early. If this time does not work for you, or you would prefer
                to reschedule, please reach the Unit through the email below and we will gladly find another time.
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding-bottom:30px;">
              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.8; color:#1a1a1a;">
                This is a space for you to be heard, with no pressure and no judgment. We look forward to
                meeting you.
              </p>
            </td>
          </tr>

          {{-- Sign-off --}}
          <tr>
            <td>
              <p style="margin:0; font-family:{{ $serif }}; font-size:16px; line-height:1.7; color:#1a1a1a;">
                Thank you,
              </p>
              <p style="margin:18px 0 0; font-family:{{ $serif }}; font-size:16px; line-height:1.6; color:#000000;">
                Guidance &amp; Counseling Unit<br>
                <span style="font-style:italic; color:#555555;">MoodLink · Student Well-being Platform</span>
              </p>
              <p style="margin:14px 0 0; font-family:{{ $serif }}; font-size:15px; line-height:1.9; color:#1a1a1a;">
                <a href="mailto:feuguidance@example.com"
                  style="color:#000000; text-decoration:none; border-bottom:1px solid #000000; padding-bottom:6px;">pogiako@example.com</a>
                <span style="color:#555555;">&nbsp;&middot;&nbsp;</span>
                <a href="tel:+639000000000"
                  style="color:#000000; text-decoration:none; border-bottom:1px solid #000000; padding-bottom:6px;">+63
                  961 666 5231</a>
              </p>
            </td>
          </tr>
        </table>

      </td>
    </tr>
  </table>
</body>

</html>